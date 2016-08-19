<?php

/*
 * Author: Imre Incze
 * 
 */

namespace Timesheet\Bundle\HrBundle\Service;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator;
use Timesheet\Bundle\HrBundle\TimesheetHrBundle;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerAware;
use Timesheet\Bundle\HrBundle\Entity\Allocation;
use Timesheet\Bundle\HrBundle\Entity\AWWH;
use Timesheet\Bundle\HrBundle\Entity\Info;
use Timesheet\Bundle\HrBundle\Entity\Companies;
use Timesheet\Bundle\HrBundle\Entity\Constants;
use \DateTime;
use \DateTimeZone;
use Symfony\Component\Validator\Constraints\IsNull;
use Timesheet\Bundle\HrBundle\Entity\TimesheetCheck;
use Symfony\Component\HttpFoundation\RequestStack;
use Timesheet\Bundle\HrBundle\Entity\ResidentPlacements;
use DoctrineExtensions\Query\Mysql\DateDiff;
use Symfony\Bundle\AsseticBundle\Factory\Worker\UseControllerWorker;
use Timesheet\Bundle\HrBundle\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\DBAL\Query\QueryException;
use Symfony\Component\Intl\Intl;
// use both zk library
use Timesheet\Bundle\HrBundle\Classes\ZKLib;
use Timesheet\Bundle\HrBundle\Classes\TAD;
use Timesheet\Bundle\HrBundle\Classes\TADFactory;

use Timesheet\Bundle\HrBundle\Entity\FPReaderAttendance;
use Assetic\Exception\Exception;
use Timesheet\Bundle\HrBundle\Entity\FPReaderToUser;
use Timesheet\Bundle\HrBundle\Entity\FPReaderTemplates;


class Functions extends ContainerAware
{
	
	protected $doctrine;
	protected $requestStack;
	public $link_length = 50;
	
	
	
	public function __construct($doctrine, RequestStack $requestStack) {
		$this->doctrine = $doctrine;
		$this->requestStack = $requestStack;
	}

	
	public function addNewAdmin($username, $password, $email, $domainId) {
error_log('addNewAdmin');
		$user=new User();
		$userManager = $this->container->get('fos_user.user_manager');
		$user->setUsername($username);
		$user->setPlainPassword($password);
		$user->setEmail($email);
		$user->setBirthday(null);
		$user->setNationality(0);
		$user->setNI('');
		$user->setPhoneLandline('');
		$user->setPhoneMobile('');
		$user->setFirstName('');
		$user->setLastName('');
		$user->setNokName('');
		$user->setNokPhone('');
		$user->setNokRelation('');
		$user->setAddressLine1('');
		$user->setAddressLine2('');
		$user->setAddressCity('');
		$user->setAddressCounty('');
		$user->setAddressCountry('');
		$user->setAddressPostcode('');
		$user->setGroupId(0);
		$user->setLocationId(0);
		$user->setPayrolCode('');
		$user->setLoginRequired(false);
		$user->setNotes('');
		$user->setTitle('');
		$user->setIsActive(true);
		$user->setExEmail(false);
		$user->setEnabled(true);
		$user->setRoles(array('ROLE_ADMIN'));
		$user->setDomainId($domainId);
		$user->setGroupAdmin(false);
		$user->setLocationAdmin(false);
		$message='';

		try {
			$userManager->updateUser($user);
		} catch (\Exception $e) {
			if (strpos($e->getMessage(), '1062') === false) {
error_log('Database error:'.$e->getMessage());
			}
		}
		if ($message) {
			return $message;
		}
		return ' and added administrator as '.$username.'. Please login on the specified domain with this username/password.';
	}
	
	public function getCurrentStatus($userId, $full=false) {
// error_log('getCurrentStatus');
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('s.name')
			->from('TimesheetHrBundle:Info', 'i')
			->leftJoin('TimesheetHrBundle:Status', 's', 'WITH', 's.id=i.statusId')
			->where('i.deleted=0')
			->andWhere('i.userId=:userId')
			->andWhere('i.timestamp<CURRENT_TIMESTAMP()')
			->orderBy('i.timestamp', 'DESC')
			->setParameter('userId', $userId)
			->setMaxResults(1);

		if ($full) {
			$qb
				->addSelect('i.timestamp')
				->addSelect('i.ipAddress')
				->addSelect('i.comment');
		
		}
		$query=$qb->getQuery();
		$result=$query->useResultCache(true)->getArrayResult();
	
		if ($full) {
			return reset($result);	
		} else {
			if ($result && count($result)==1) {
				return $result[0]['name'];
			} else {
				return null;
			}
		}
	}
	
	
	public function getCurrentStatusesForManager($userId) {
error_log('getCurrentStatusesForManager');
		$list=array();
		
		$user=$this->doctrine
			->getRepository('TimesheetHrBundle:User')
			->findOneBy(array('id'=>$userId));
		
		$users=$this->getUsersForManager($user, null, 0, $user->getRoles());
		
		if ($users && count($users)) {
			$statuses=$this->getStatuses(null, true);
// error_log('statuses:'.print_r($statuses, true));
			foreach ($users as $u) {
				$list[$u['id']]=array(
					'status'=>((isset($statuses[$u['lastStatus']]))?($statuses[$u['lastStatus']]):('')),
					'timestamp'=>$u['lastTime'],
					'username'=>$u['username'],
					'fullname'=>trim($u['title'].' '.$u['firstName'].' '.$u['lastName']),
					'ipAddress'=>$u['lastIpAddress'],
					'comment'=>$u['lastComment']
				);
			}
		}
	
		return $list;
	}
	
	
	public function getUsersList($userId=null, $name=null, $all=false, $groupId=null, $qualificationId=null, $locationId=null, $extra=true, $domainId=null) {
// error_log('getUsersList');
// error_log('userId:'.$userId.', name:'.$name.', all:'.$all.', groupId:'.$groupId.', qualificationId:'.$qualificationId.', locationId:'.$locationId.', extra:'.$extra.', domainId:'.$domainId);
//		$request=$this->requestStack->getCurrentRequest();
		$users=array();
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('u.id')

			->from('TimesheetHrBundle:User', 'u')
			->leftJoin('TimesheetHrBundle:Groups', 'g', 'WITH', 'u.groupId=g.id')
			->leftJoin('TimesheetHrBundle:Location', 'l', 'WITH', 'u.locationId=l.id')
			->leftJoin('TimesheetHrBundle:Status', 's', 'WITH', 'u.lastStatus=s.id')
			->leftJoin('TimesheetHrBundle:UserQualifications', 'uq', 'WITH', 'u.id=uq.userId')
			
			->where('u.id>0')
			->groupBy('u.id')
			->orderBy('u.username', 'ASC');

		if ($userId) {
			$qb->andWhere('u.id=:uId')
				->setParameter('uId', $userId);
		}
		if ($domainId) {
			$qb->andWhere('u.domainId=:dId')
				->setParameter('dId', $domainId);
		}
		if ($groupId) {
			$qb->andWhere('u.groupId=:gId')
				->setParameter('gId', $groupId);
		}
		if ($qualificationId) {
			$qb->andWhere('uq.qualificationId=:qId')
				->setParameter('qId', $qualificationId);
		}
		if ($locationId) {
			$qb->andWhere('u.locationId=:lId')
				->setParameter('lId', $locationId);
		}
		if ($name) {
			$qb->andWhere('u.username LIKE :name OR u.firstName LIKE :name OR u.lastName LIKE :name')
				->setParameter(':name', '%'.$name.'%');
		}
		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
// error_log('results:'.print_r($results, true));
		if ($results && count($results)) { 
			$found=count($results);

			$qb->addSelect('u.isActive')
				->addSelect('u.username')
				->addSelect('u.title')
				->addSelect('u.firstName')
				->addSelect('u.lastName')
				->addSelect('u.email')
				->addSelect('u.lastTime')
				->addSelect('u.groupAdmin')
				->addSelect('u.locationAdmin')
				->addSelect('u.domainId')
				->addSelect('g.name as groupname')
				->addSelect('l.name as locationname')
				->addSelect('s.name as statusname')
				->addSelect('s.color');
			
			if (!$all) {
				$qb->setMaxResults(30);
			}

			$query=$qb->getQuery();
			$users=$query->useResultCache(true)->getArrayResult();
				
		} else {
			$found=0;
		}
		
		if ($users && $extra) {
			foreach ($users as $k=>$v) {
				$users[$k]['contracts']=$this->getContracts($v['id']);
				$users[$k]['timings']=$this->getTimings($v['id'], true);
				$users[$k]['userqualifications']=$this->getQualifications($v['id'], true);
				$users[$k]['holidays']=$this->getHolidayEntitlement($v['id']);
				$users[$k]['photos']=$this->getUserPhotos($v['id'], $domainId, false, true, 50);
				$users[$k]['visas']=$this->getUserVisas($v['id']);
				$users[$k]['dbs']=$this->getUserDBSCheck($v['id']);
			}
			
			$users[-1]['found']=$found;
		}
		
		return $users;
	}
	
	
	public function getUserShiftStatus($userId, $timestamp, $locationId, $domainId) {
		$shift=null;
		if ($userId && $timestamp && $locationId && $domainId) {
error_log('getUserShiftStatus');
//error_log('uId:'.$userId.', ts:'.$timestamp->format('Y-m-d H:i:s').', day:'.$timestamp->format('w').', lId:'.$locationId.', dId:'.$domainId);
			$em=$this->doctrine->getManager();
			$ts=clone $timestamp;
			$day=$ts->format('w');
			$prevDay=$day-1;
			$valid=false;
			if ($prevDay<0) {
				$prevDay=6;
			}
			$qb=$em
				->createQueryBuilder()
				->select('s.id')
				->addSelect('s.startTime')
				->addSelect('s.finishTime')
				->addSelect('s.fpStartTime')
				->addSelect('s.fpFinishTime')
				->addSelect('s.fpStartBreak')
				->addSelect('s.fpFinishBreak')
				->addSelect('s.title')
				->addSelect('l.name')
				->from('TimesheetHrBundle:Allocation', 'a')
				->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'a.shiftId=s.id')
				->join('TimesheetHrBundle:Location', 'l', 'WITH', 'a.locationId=l.id')
				->join('TimesheetHrBundle:ShiftDays', 'sd', 'WITH', 'sd.shiftId=s.id')
				->where('a.published=1')
				->andWhere('l.domainId=:dId')
				->andWhere('a.date<=:date')
				->andWhere('sd.dayId IN (:day,:pDay)')
				->andWhere('a.userId=:uId')
				->andWhere('a.locationId=:lId')
				->orderBy('a.date', 'DESC')
				->setMaxResults(2)
				->setParameter('uId', $userId)
				->setParameter('dId', $domainId)
				->setParameter('lId', $locationId)
				->setParameter('date', $ts->format('Y-m-d'))
				->setParameter('day', $day)
				->setParameter('pDay', $prevDay);
		
			$query=$qb->getQuery();
			$results=$query->useResultCache(true)->getArrayResult();
			if ($results && count($results)) {
// error_log('Results:'.print_r($results, true));
				$status='Invalid';
				$result=reset($results);
				$d1=clone $ts;
				$d1->setTime($result['fpStartTime']->format('H'), $result['fpStartTime']->format('m'), $result['fpStartTime']->format('i'));
				$d2=clone $ts;
				$d2->setTime($result['fpFinishTime']->format('H'), $result['fpFinishTime']->format('m'), $result['fpFinishTime']->format('i'));
				if ($result['fpStartTime']->format('H:i:s') > $result['fpFinishTime']->format('H:i:s')) {
					$d2->modify('+1 day');
				}
				if ($timestamp->format('Y-m-d H:i:s') < $d1->format('Y-m-d H:i:s')) {
					// probably previous day
					if (count($results) > 1) {
// error_log('previous day');
						$result=next($results);
						$d1=clone $ts;
						$d1->setTime($result['fpStartTime']->format('H'), $result['fpStartTime']->format('m'), $result['fpStartTime']->format('i'));
						$d2=clone $ts;
						$d2->setTime($result['fpFinishTime']->format('H'), $result['fpFinishTime']->format('m'), $result['fpFinishTime']->format('i'));
						if ($result['fpStartTime']->format('H:i:s') > $result['fpFinishTime']->format('H:i:s')) {
							$d2->modify('+1 day');
						}
						
					} else {
						// only 1 result, no previous shift
// error_log('no previous shift');
					}
					
				}
// error_log('d1:'.$d1->format('Y-m-d H:i:s').', d2:'.$d2->format('Y-m-d H:i:s'));
				if ($timestamp->format('Y-m-d H:i:s') >= $d1->format('Y-m-d H:i:s') && $timestamp->format('Y-m-d H:i:s') <= $d2->format('Y-m-d H:i:s')) {
					// same day, in the time limit
					if (!is_null($result['fpStartBreak'])) {
						$d3=clone $d1;
						$d3->setTime($result['fpStartBreak']->format('H'), $result['fpStartBreak']->format('m'), $result['fpStartBreak']->format('i'));
						$d4=clone $d1;
						$d4->setTime($result['fpFinishBreak']->format('H'), $result['fpFinishBreak']->format('m'), $result['fpFinishBreak']->format('i'));
						if ($result['fpStartBreak']->format('H:i:s') > $result['fpFinishBreak']->format('H:i:s')) {
							$d4->modify('+1 day');
						}
						if ($timestamp->format('Y-m-d H:i:s') < $d3->format('Y-m-d H:i:s')) {
							$status='Check In';
						} elseif ($timestamp->format('Y-m-d H:i:s') > $d4->format('Y-m-d H:i:s')) {
							$status='Check Out';
						} else {
							$status='Break';
						}
						error_log('status:'.$status);
					} else {
						$status='Check In/Out';
// error_log($timestamp->format('Y-m-d H:i:s').' BETWEEN '.$result['startTime']->format('H:i:s').' AND '.$result['finishTime']->format('H:i:s'));
					}
					$valid=true;
//				} else {
//					error_log('Invalid timestamp:'.$timestamp->format('Y-m-d H:i:s'));
				}
				$shift=array(
					'date'=>(($valid)?($d1->format('Y-m-d')):(null)),
					'title'=>$result['title'],
					'status'=>$status,
					'shiftId'=>$result['id'],
					'startTime'=>$result['startTime'],
					'finishTime'=>$result['finishTime'],
					'location'=>$result['name']
				);
			}
		}
		return $shift;
	}
	
	
	public function getUserFullNameById($userId) {
		if ($userId) {
			$em=$this->doctrine->getManager();
			$qb=$em
				->createQueryBuilder()
				->select('u.title')
				->addSelect('u.firstName')
				->addSelect('u.lastName')
				->from('TimesheetHrBundle:User', 'u')
				->where('u.id=:uId')
				->setParameter('uId', $userId);
		
			$query=$qb->getQuery();
			$result=$query->useResultCache(true)->getArrayResult();
			$currentUser=reset($result);
			return trim($currentUser['title'].' '.$currentUser['firstName'].' '.$currentUser['lastName']);
		}
		return null;
	}
	
	
	public function getUserRole($userId) {
		if ($userId) {
			$em=$this->doctrine->getManager();
			$qb=$em
				->createQueryBuilder()
				->select('u.roles')
				->from('TimesheetHrBundle:User', 'u')
				->where('u.id=:uId')
				->setParameter('uId', $userId);
		
			$query=$qb->getQuery();
			$result=$query->useResultCache(true)->getArrayResult();
			$currentUser=reset($result);
			return $currentUser['roles'];
		}
		return null;
	}
	
	
	public function getUserDBSCheck($userId, $latest=false) {
error_log('getUserDBSCheck');
		if ($userId) {
			$em=$this->doctrine->getManager();
			$qb=$em
				->createQueryBuilder()
				->select('udc.id')
				->addSelect('udc.disclosureNo')
				->addSelect('udc.issueDate')
				->addSelect('udc.createdOn')
				->addSelect('udc.notes')
				->addSelect('dct.title')
				->from('TimesheetHrBundle:UserDBSCheck', 'udc')
				->join('TimesheetHrBundle:DBSCheckTypes', 'dct', 'WITH', 'dct.id=udc.typeId')
				->where('udc.userId=:uId')
				->orderBy('udc.issueDate', 'DESC')
				->setParameter('uId', $userId);
				
			if ($latest) {
				$qb->andWhere('udc.issueDate<=:date')
					->setParameter('date', date('Y-m-d'))
					->setMaxResults(1);
			}
			$query=$qb->getQuery();
			// error_log('query:'.$query->getDql());
			if ($latest) {
				$tmp=$query->useResultCache(true)->getArrayResult();
				if (isset($tmp) && is_array($tmp) && count($tmp) && $tmp) {
					return reset($tmp);
				}
			} else {
				return $query->useResultCache(true)->getArrayResult();
			}
		}
		return null;
	}

	
	public function getUserVisas($userId, $latest=false) {
error_log('getUserVisas');
		if ($userId) {
			$em=$this->doctrine->getManager();
			$qb=$em
				->createQueryBuilder()
				->select('uv.id')
				->addSelect('uv.startDate')
				->addSelect('uv.endDate')
				->addSelect('uv.notExpire')
				->addSelect('uv.createdOn')
				->addSelect('uv.notes')
				->addSelect('v.title')
				->from('TimesheetHrBundle:UserVisas', 'uv')
				->join('TimesheetHrBundle:Visa', 'v', 'WITH', 'v.id=uv.visaId')
				->where('uv.userId=:uId')
				->orderBy('uv.notExpire', 'DESC')
				->addOrderBy('uv.endDate', 'DESC')
				->setParameter('uId', $userId);
				
			if ($latest) {
				$qb->andWhere('uv.startDate<=NOW()')
					->andWhere('uv.endDate>=NOW()')
					->setMaxResults(1);
			}
				
			$query=$qb->getQuery();
			// error_log('query:'.$query->getDql());
			return $query->useResultCache(true)->getArrayResult();
		}
		return null;
	}
	
	
	public function getVisaList($domainId) {
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('v.id')
			->addSelect('v.title')
			->from('TimesheetHrBundle:Visa', 'v')
			->where('v.active=1')
			->orderBy('v.title', 'ASC');
		
		$query=$qb->getQuery();
		// error_log('query:'.$query->getDql());
		$results=$query->useResultCache(true)->getArrayResult();
		$ret=array();
		if ($results && count($results)) {
			foreach ($results as $r) {
				$ret[$r['id']]=$r['title'];
			}
		}
		return $ret;		
	}
	

	public function getDBSTypeList($domainId) {
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('dct.id')
			->addSelect('dct.title')
			->from('TimesheetHrBundle:DBSCheckTypes', 'dct')
			->orderBy('dct.title', 'ASC');
	
		$query=$qb->getQuery();
		// error_log('query:'.$query->getDql());
		$results=$query->useResultCache(true)->getArrayResult();
		$ret=array();
		if ($results && count($results)) {
			foreach ($results as $r) {
				$ret[$r['id']]=$r['title'];
			}
		}
		return $ret;
	}
	
	
	public function getUserPhotos($userId=null, $domainId=null, $all=false, $resize=false, $newSize=80, $photoId=null) {
error_log('getUserPhotos');
		if ($userId || $domainId) {
			$em=$this->doctrine->getManager();
			$qb=$em
				->createQueryBuilder()
				->select('up.id')
				->addSelect('up.userId')
				->addSelect('up.photo')
				->addSelect('up.createdOn')
				->addSelect('up.notes')
				->addSelect('up.type')
				->from('TimesheetHrBundle:UserPhotos', 'up')
				->join('TimesheetHrBundle:User', 'u', 'WITH', 'u.id=up.userId')
				->where('up.id>0')
				->orderBy('up.createdOn', 'DESC');
			
			if ($domainId) {
				$qb->andWhere('u.domainId=:dId')
					->setParameter('dId', $domainId);
			}
			if ($userId) {
				$qb->andWhere('up.userId=:uId')
					->setParameter('uId', $userId);
			}
			if ($photoId) {
				$qb->andWhere('up.id=:pId')
					->setParameter('pId', $photoId);
			}
			if (!$all) {
				$qb->setMaxResults(1);
			}
			$query=$qb->getQuery();
// error_log('query:'.$query->getDql());
			$results=$query->useResultCache(true)->getArrayResult();
// error_log('results no:'.count($results));
			foreach ($results as $k=>$v) {
				$imgData=stream_get_contents($v['photo']);
				$size=@getimagesizefromstring($imgData);
				$results[$k]['origWidth']=$size[0];
				$results[$k]['origHeight']=$size[1];
				if ($resize) {
					$data=$this->photoResize($imgData, $newSize);
// error_log('size: '.strlen($imgData).' == '.strlen($data['photo']));
					if (!is_null($data['photo'])) {
						$results[$k]['photo']=base64_encode($data['photo']);
						$results[$k]['width']=$data['width'];
						$results[$k]['height']=$data['height'];
						$results[$k]['type']=$data['type'];
// error_log('resize, width:'.$data['width'].', height:'.$data['height']);
					} else {
						$results[$k]['photo']=base64_encode($imgData);
						$results[$k]['width']=$data['width'];
						$results[$k]['height']=$data['height'];
					}
				} else {
					$size=@getimagesizefromstring($imgData);
					$results[$k]['photo']=base64_encode($imgData);
					$results[$k]['width']=$size[0];
					$results[$k]['height']=$size[1];
// error_log('no resize, sizes:'.print_r($size, true));
				}

			}
			return $results; 
		}
		return null;
	}
	
	
	public function getResidentPhotos($residentId=null, $domainId=null, $all=false, $resize=false, $newSize=80, $photoId=null) {
error_log('getUserPhotos');
		if ($residentId || $domainId) {
			$em=$this->doctrine->getManager();
			$qb=$em
				->createQueryBuilder()
				->select('rp.id')
				->addSelect('rp.residentId')
				->addSelect('rp.photo')
				->addSelect('rp.createdOn')
				->addSelect('rp.notes')
				->addSelect('rp.type')
				->from('TimesheetHrBundle:ResidentPhotos', 'rp')
				->join('TimesheetHrBundle:Residents', 'r', 'WITH', 'r.id=rp.residentId')
				->where('rp.id>0')
				->orderBy('rp.createdOn', 'DESC');
			
			if ($domainId) {
				$qb->andWhere('r.domainId=:dId')
					->setParameter('dId', $domainId);
			}
			if ($residentId) {
				$qb->andWhere('rp.residentId=:rId')
					->setParameter('rId', $residentId);
			}
			if ($photoId) {
				$qb->andWhere('rp.id=:pId')
					->setParameter('pId', $photoId);
			}
			if (!$all) {
				$qb->setMaxResults(1);
			}
			$query=$qb->getQuery();
// error_log('query:'.$query->getDql());
			$results=$query->useResultCache(true)->getArrayResult();
// error_log('results no:'.count($results));
			foreach ($results as $k=>$v) {
				$imgData=stream_get_contents($v['photo']);
				$size=@getimagesizefromstring($imgData);
				$results[$k]['origWidth']=$size[0];
				$results[$k]['origHeight']=$size[1];
				if ($resize) {
					$data=$this->photoResize($imgData, $newSize);
// error_log('size: '.strlen($imgData).' == '.strlen($data['photo']));
					if (!is_null($data['photo'])) {
						$results[$k]['photo']=base64_encode($data['photo']);
						$results[$k]['width']=$data['width'];
						$results[$k]['height']=$data['height'];
						$results[$k]['type']=$data['type'];
// error_log('resize, width:'.$data['width'].', height:'.$data['height']);
					} else {
						$results[$k]['photo']=base64_encode($imgData);
						$results[$k]['width']=$data['width'];
						$results[$k]['height']=$data['height'];
					}
				} else {
					$size=@getimagesizefromstring($imgData);
					$results[$k]['photo']=base64_encode($imgData);
					$results[$k]['width']=$size[0];
					$results[$k]['height']=$size[1];
// error_log('no resize, sizes:'.print_r($size, true));
				}

			}
			return $results; 
		}
		return null;
	}
	
	
	public function photoResize($imgData, $newSize=null) {
		
		$data=array('photo'=>null, 'width'=>null, 'height'=>null);
		
		$ok=false;
		try {
			$img=@imagecreatefromstring($imgData);
			$size=@getimagesizefromstring($imgData);
			$ok=true;
		} catch (\Exception $e) {
			error_log('Exception:'.$e->getMessage());
		}
			
		if ($ok && isset($size[1])) {
			$width=$size[0];
			$height=$size[1];
			$ratio1=$width/$height;
			$ratio2=$height/$width;
			if ($newSize && ($width>$height && $width>$newSize) || ($width<$height && $height>$newSize)) {
				if ($width<$height && $height>$newSize) {
					$newWidth=(int)round($newSize*$ratio1);
					$newHeight=$newSize;
				} else {
					$newWidth=$newSize;
					$newHeight=(int)round($newSize*$ratio2);
				}
				$newImg=imagecreatetruecolor($newWidth, $newHeight);
				$r=imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
				if ($r) {
					ob_start();
					imagepng($newImg);
					$data['photo']=ob_get_clean();
					$data['type']='png';
				} else {
					error_log('Error resizing image');
				}
			} else {
				$newWidth=$width;
				$newHeight=$height;
			}
			$data['width']=$newWidth;
			$data['height']=$newHeight;
		}

		if (isset($img)) {
			imagedestroy($img);
		
		}
		if (isset($newImg)) {
			imagedestroy($newImg);
		}

		return $data;
	}
	

	public function getResidentContacts($residentId=null, $domainId=null) {
		error_log('getResidentContacts');
		if ($residentId || $domainId) {
			$em=$this->doctrine->getManager();
			$qb=$em
			->createQueryBuilder()
			->select('rc.id')
			->addSelect('rc.residentId')
			->addSelect('rc.title')
			->addSelect('rc.firstName')
			->addSelect('rc.lastName')
			->addSelect('rc.relation')
			->addSelect('rc.addressLine1')
			->addSelect('rc.addressLine2')
			->addSelect('rc.addressCity')
			->addSelect('rc.addressCounty')
			->addSelect('rc.addressCountry')
			->addSelect('rc.addressPostcode')
			->addSelect('rc.phoneMobile')
			->addSelect('rc.phoneLandline')
			->addSelect('rc.phoneOther')
			->addSelect('rc.preferredPhone')
			->addSelect('rc.email')
			->addSelect('rc.emergency')
			->from('TimesheetHrBundle:ResidentContacts', 'rc')
			->join('TimesheetHrBundle:Residents', 'r', 'WITH', 'r.id=rc.residentId')
			->where('rc.id>0')
			->orderBy('rc.emergency', 'DESC')
			->orderBy('rc.lastName', 'ASC');
	
			if ($domainId) {
				$qb->andWhere('r.domainId=:dId')
				->setParameter('dId', $domainId);
			}
			if ($residentId) {
				$qb->andWhere('rc.residentId=:rId')
				->setParameter('rId', $residentId);
			}
			$query=$qb->getQuery();
			// error_log('query:'.$query->getDql());
			return $query->useResultCache(true)->getArrayResult();
		}
		return null;
	}

	
	public function getRooms($domainId=null, $all=false, $nameOnly=false, $except=null) {
error_log('getRooms');
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('r.roomNumber')
			->addSelect('r.id')
			->addSelect('l.name')
			->from('TimesheetHrBundle:Rooms', 'r')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 'l.id=r.locationId')
			->where('r.id>0')
			->orderBy('l.name', 'ASC')
			->orderBy('r.roomNumber', 'ASC');
	
		if ($domainId) {
			$qb->andWhere('l.domainId=:dId')
			->setParameter('dId', $domainId);
		}
		if ($all) {
			$qb->andWhere('r.active=true');
		}
		if ($except) {
			$qb->andWhere('r.id!=:eId')
				->setParameter('eId', $except);
		}
		if (!$nameOnly) {
			$qb->addSelect('r.notes')
				->addSelect('r.places')
				->addSelect('r.extraPlaces')
				->addSelect('r.open')
				->addSelect('r.active')
				->addSelect('r.locationId');		
		}
		$query=$qb->getQuery();
		// error_log('query:'.$query->getDql());
		if ($nameOnly) {
			$rooms=array();
			$results=$query->useResultCache(true)->getArrayResult();
			if ($results && count($results)) {
				foreach ($results as $r) {
					$rooms[$r['id']]=$r['roomNumber'].' - '.$r['name'];
				}
			}
			return $rooms;
			
		} else {
			return $query->useResultCache(true)->getArrayResult();
		}
	}

	
	public function getResidentLocation($residentId, $date) {
		if ($residentId) {
			$em=$this->doctrine->getManager();
			$qb=$em->createQueryBuilder();
			$qb
				->select('rm.id')
				->addSelect('rm.roomNumber')
				->addSelect('l.name')
				->from('TimesheetHrBundle:ResidentPlacements', 'rp')
				->join('TimesheetHrBundle:Rooms', 'rm', 'WITH', 'rm.id=rp.roomId')
				->join('TimesheetHrBundle:Location', 'l', 'WITH', 'l.id=rm.locationId')
				->where('rp.residentId=:rId')
				->andWhere($qb->expr()->isNull('rp.moveOut'))
//				->andWhere('rp.moveOut=null or rp.movOut>:date')
				->orderBy('rp.moveIn', 'DESC')
//				->setParameter('date', $date->format('Y-m-d'))
				->setParameter('rId', $residentId)
				->setMaxResults(1);
				
			$query=$qb->getQuery();
			// error_log('query:'.$query->getDql());
			$results=$query->useResultCache(true)->getArrayResult();
			if ($results && count($results)) {
				$result=reset($results);
			
				return $result;
			}			
		}
		return null;
	}
	
	
	public function getLatestRoom($residentId) {
error_log('getLatestRoom');
		if ($residentId) {
			$em=$this->doctrine->getManager();
			$qb=$em
				->createQueryBuilder()
				->select('rm.id')
				->addSelect('rm.roomNumber')
				->addSelect('rm.notes as roomNotes')
				->addSelect('rm.places')
				->addSelect('rm.extraPlaces')
				->addSelect('rm.open')
				->addSelect('rm.active')
				->addSelect('rm.locationId')
				->addSelect('rp.notes')
				->addSelect('rp.moveIn')
				->addSelect('rp.moveOut')
				->addSelect('l.name')
				->from('TimesheetHrBundle:ResidentPlacements', 'rp')
				->join('TimesheetHrBundle:Rooms', 'rm', 'WITH', 'rm.id=rp.roomId')
				->join('TimesheetHrBundle:Location', 'l', 'WITH', 'l.id=rm.locationId')
				->where('rp.residentId=:rId')
				->orderBy('rp.moveIn', 'DESC')
				->setMaxResults(1)
				->setParameter('rId', $residentId);
					
			$query=$qb->getQuery();
	// error_log('query:'.$query->getDql());
			return $query->useResultCache(true)->getArrayResult();
		} else {
			return array();
		}
	}
	
	
	public function setResidentMoveIn($residentId, $roomId, $date, $notes, $userId, $currentRoomId=null) {
error_log('setResidentMoveIn');
		$em=$this->doctrine->getManager();		
		$room=new ResidentPlacements();
		$room->setResidentId($residentId);
		$room->setRoomId($roomId);
		$room->setMoveIn($date);
		$room->setCreatedOn(new \DateTime());
		$room->setCreatedBy($userId);
		$room->setNotes(''.$notes);
		
		$em->persist($room);
		$em->flush($room);
			
		if ($room->getId()) {
				
			if ($currentRoomId) {
				return $this->setResidentMoveOut($residentId, $currentRoomId, $date, $userId);
			}
			return true;
		}
		return false;
	}
	

	public function setResidentMoveOut($residentId, $currentRoomId, $date, $notes, $userId) {
error_log('setResidentMoveOut');
		$em=$this->doctrine->getManager();		
		$rooms=$this->doctrine
			->getRepository('TimesheetHrBundle:ResidentPlacements')
			->findBy(array('residentId'=>$residentId, 'roomId'=>$currentRoomId, 'moveOut'=>null));
					
		if ($rooms && count($rooms)) {
			foreach ($rooms as $room) {
				$room->setMoveOut($date);
				$room->setMoveOutNotes(''.$notes);
				$room->setModifiedOn(new \DateTime());
				$room->setModifiedBy($userId);
	
				$em->flush($room);
			}
			return true;
		} else {
			return false;
		}
	}

	
	public function getResidentHistory($residentId, $domainId) {
error_log('getResidentHistory');
		if ($residentId) {
			$em=$this->doctrine->getManager();
			$qb=$em
				->createQueryBuilder()
				->select('rm.id')
				->addSelect('rm.roomNumber')
				->addSelect('rm.notes as roomNotes')
				->addSelect('rm.locationId')
				->addSelect('rp.notes')
				->addSelect('rp.moveIn')
				->addSelect('rp.moveOut')
				->addSelect('l.name')
				->from('TimesheetHrBundle:Residents', 'r')
				->join('TimesheetHrBundle:ResidentPlacements', 'rp', 'WITH', 'r.id=rp.residentId')
				->join('TimesheetHrBundle:Rooms', 'rm', 'WITH', 'rm.id=rp.roomId')
				->join('TimesheetHrBundle:Location', 'l', 'WITH', 'l.id=rm.locationId')
				->where('r.id=:rId')
				->andWhere('r.domainId=:dId')
				->orderBy('rp.moveIn', 'DESC')
				->addOrderBy('rp.createdOn', 'DESC')
				->setParameter('rId', $residentId)
				->setParameter('dId', $domainId);
				
			$query=$qb->getQuery();
			// error_log('query:'.$query->getDql());
			return $query->useResultCache(true)->getArrayResult();
		} else {
			return array();
		}
	}
	
	public function getShifts($id=null, $userId=null, $locationId=null, $domainId=null) {
/*
		$arr=array();
		if ($id) {
			$arr['id']=$id;
		}
		if ($userId) {
			$arr['userId']=$userId;
		}
		if ($locationId) {
			$arr['locationId']=$locationId;
		}
		
		$ret=array();
*/		
		$shifts=array();
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('s.id')
			->addSelect('s.locationId')
			->addSelect('s.startTime')
			->addSelect('s.finishTime')
			->addSelect('s.title')
			->addSelect('s.minWorkTime')
			->from('TimesheetHrBundle:Shifts', 's')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 's.locationId=l.id')
			->where('s.id>0')
			->orderBy('s.startTime', 'ASC');
		
		if ($id) {
			$qb->andWhere('s.id=:id')
				->setParameter('id', $id);
		}
		if ($userId) {
			$qb->andWhere('s.userId=:uId')
				->setParameter('uId', $userId);
		}
		if ($locationId) {
			$qb->andWhere('s.locationId=:lId')
				->setParameter('lId', $locationId);
		}
		if ($domainId) {
			$qb->andWhere('l.domainId=:dId')
			->setParameter('dId', $domainId);
		}
		
		
		$query=$qb->getQuery();
// error_log('query:'.$query->getDql());
		$results=$query->useResultCache(true)->getArrayResult();

		if ($results) {
			foreach ($results as $result) {
				$shifts[$result['locationId']][$result['id']]=array(
					'timings'=>$result['startTime']->format('H:i').' - '.$result['finishTime']->format('H:i'),
					'days'=>$this->getShiftDays($result['id']),
					'title'=>$result['title']
				);
			}
		}

		return $shifts;
	}
	

	public function getShiftsWithDetails($id=null, $userId=null, $locationId=null) {
	
		$arr=array();
		if ($id) {
			$arr[]='`s`.`id`="'.$id.'"';
		}
		if ($userId) {
			$arr[]='`s`.`userId`="'.$userId.'"';
		}
		if ($locationId) {
			$arr[]='`s`.`locationId`="'.$locationId.'"';
		}
	
		$shifts=array();

		$conn=$this->doctrine->getConnection();
		 
		$query='SELECT'.
				' `s`.*,'.
				' `sd`.`dayId`,'.
				' `l`.`name`'.
				' FROM `Shifts` `s`'.
				' JOIN `ShiftDays` `sd` ON `s`.`id`=`sd`.`shiftId`'.
				' LEFT JOIN `Location` `l` ON `s`.`locationId`=`l`.`id`'.
				((count($arr))?(' WHERE '.implode(' AND ', $arr)):('')).
				' ORDER BY `l`.`name`, `s`.`startTime`';
		 
		$stmt=$conn->prepare($query);
		$stmt->execute();
	
		$results=$stmt->fetchAll();
		
		if ($results) {
			foreach ($results as $result) {
				if ($result['locationId'] != null && $result['name']) {
					$shifts[$result['dayId']][$result['id']]=$result['name'].' '.substr($result['startTime'], 0, 5).'-'.substr($result['finishTime'], 0, 5);
				}
			}
		}
	
		return $shifts;
	}

	
	public function getFPUsersByLocalId($userId) {
error_log('getFPUsersByLocalId');
// error_log('userId:'.$userId);
		$em=$this->doctrine->getManager();
		
		$qb=$em
			->createQueryBuilder()
			->select('fpru.name')
			->addSelect('fpru.role')
			->addSelect('fprtu.readerUserId')
			->addSelect('fprtu.userId')
			->addSelect('fpr.id')
			->addSelect('fpr.ipAddress')
			->addSelect('fpr.port')
			->addSelect('fpr.deviceName')
			->addSelect('fpr.comment')
			->from('TimesheetHrBundle:FPReaderUsers', 'fpru')
			->join('TimesheetHrBundle:FPReaderToUser', 'fprtu', 'WITH', 'fpru.readerId=fprtu.readerId AND fpru.userId=fprtu.readerUserId')
			->join('TimesheetHrBundle:FPReaders', 'fpr', 'WITH', 'fprtu.readerId=fpr.id')
			->where('fprtu.userId=:uId')
			->setParameter('uId', $userId);
		
		$query=$qb->getQuery();
// error_log('query:'.$query->getDql());
		$results=$query->useResultCache(true)->getArrayResult();
		return $results;
	}
	

	public function getFPUsers() {
error_log('getFPUsers');
		$users=array();
		$em=$this->doctrine->getManager();
	
		$qb=$em
			->createQueryBuilder()
			->select('fpru.name')
			->addSelect('fpru.userId')
			->addSelect('fpru.readerId')
			->from('TimesheetHrBundle:FPReaderUsers', 'fpru')
			->orderBy('fpru.name', 'ASC');
	
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
		if ($results) {
			foreach ($results as $r) {
				$users[$r['readerId']][$r['userId']]=$r['name'];
			}
		}
		return $users;
	}
	
	
	public function getAvailableRoles($currentRole=array('ROLE_USER'), $currentUserRoles) {
		$roles=array(
			'ROLE_SYSADMIN'=>'Sysadmin',
			'ROLE_ADMIN'=>'Administrator',
			'ROLE_MANAGER'=>'Manager',
			'ROLE_USER'=>'User'
		);

		$available=array();
		$ok=false;
		foreach ($roles as $k=>$v) {
			if ($ok) {
				$available[$k]=$v;
			} elseif (in_array($k, $currentUserRoles)) {
				$available[$k]=$v;
				$ok=true;
			} elseif (in_array($k, $currentRole)) {
				$ok=true;
			}
		}
// error_log('available:'.print_r($available, true));
		return $available;
	}
	

	public function getMembers($locationId) {
error_log('getMembers');
		$em=$this->doctrine->getManager();
		
		$qb=$em
			->createQueryBuilder()
			->select('g.name')
			->addSelect('sr.numberOfStaff')
			->from('TimesheetHrBundle:Shifts', 's')
			->join('TimesheetHrBundle:StaffRequirements', 'sr', 'WITH', 'sr.shiftId=s.id')
			->join('TimesheetHrBundle:Groups', 'g', 'WITH', 'g.id=sr.groupId')
			->where('s.locationId=:lId')
			->setParameter('lId', $locationId);
		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
		$arr=array();
		if ($results) {
			
			foreach ($results as $result) {
				$arr[$result['name']]=$result['numberOfStaff'];
			}
		}
	
		return $arr;
	}
	
	
	public function getGroups($domainId=null) {
		/*
		 * read the groups table by name
		 */
		$em=$this->doctrine->getManager();
		 
		$qb=$em
			->createQueryBuilder()
			->select('g.id')
			->addSelect('g.name')
			->addSelect('c.companyname')
			->from('TimesheetHrBundle:Groups', 'g')
			->join('TimesheetHrBundle:Companies', 'c', 'WITH', 'g.domainId=c.id')
			->orderBy('c.companyname', 'ASC')
			->addOrderBy('g.name', 'ASC');

		if ($domainId) {
			$qb->andWhere('g.domainId=:dId')
				->setParameter('dId', $domainId);
		}
					
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
		 
		$arr=array();
		if ($results) {
			foreach ($results as $result) {
				$arr[$result['id']]=$result['name'].($domainId?(''):(' - '.$result['companyname']));
			}
		}
	
		return $arr;
	}
	

	public function getQualifications($userId=null, $full=false, $domainId=null) {
		/*
		 * read the qualifications table by name
		 */
		
		$em=$this->doctrine->getManager();
		 
		$qb=$em
			->createQueryBuilder()
			->select('q.id')
			->addSelect('q.title')
			->from('TimesheetHrBundle:Qualifications', 'q')
			->orderBy('q.title', 'ASC');
			
		if ($userId && $full) {
			$qb->addSelect('uq.id as uqId')
				->addSelect('uq.comments')
				->addSelect('uq.achievementDate')
				->addSelect('uq.expiryDate')
				->addSelect('uq.levelId');
		}
		if ($userId) {
			$qb->addSelect('ql.level')
				->join('TimesheetHrBundle:UserQualifications', 'uq', 'WITH', 'q.id=uq.qualificationId')
				->leftJoin('TimesheetHrBundle:QualificationLevels', 'ql', 'WITH', 'uq.levelId=ql.id')
				->andWhere('uq.userId=:uId')
				->groupBy('q.id')
				->addOrderBy('uq.achievementDate', 'ASC')
				->setParameter('uId', $userId);
		}
		if ($domainId) {
			$qb->andWhere('q.domainId=:dId')
				->setParameter('dId', $domainId);
		}
		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();

		$arr=array();
		if ($results && !$full) {
			foreach ($results as $result) {
				$arr[$result['id']]=$result['title'];
			}
		} else {
			$arr=$results;
		}
// error_log('arr:'.print_r($arr, true));	
		return $arr;
	}
	
	
	public function getQualificationLevels() {
		/*
		 * read the qualification levels table by rank
		 */
		
		$em=$this->doctrine->getManager();
		 
		$qb=$em
			->createQueryBuilder()
			->select('ql.id')
			->addSelect('ql.level')
			->from('TimesheetHrBundle:QualificationLevels', 'ql')
			->orderBy('ql.rank', 'ASC');
			
		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
		
		$arr=array();
		if ($results && count($results)) {
			foreach ($results as $result) {
				$arr[$result['id']]=$result['level'];
			}
		}
	
		return $arr;
	}
	
	
	public function getDomainId($domain) {
		/*
		 * get the location id by domain name
		 */
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('c.id')
			->from('TimesheetHrBundle:Companies', 'c')
			->where('c.domain=:domain')
			->setParameter('domain', $domain);
			
		$query=$qb->getQuery();
	
		$result=$query->useResultCache(true)->getOneOrNullResult();

		if ($result) {
			return $result['id'];
		} else {
			error_log('wrong domain:'.$domain);
			return 0;
		}
	}
	

	public function getDomains() {
		/*
		 * get all the domain names
		 */
		$results=$this->doctrine
			->getRepository('TimesheetHrBundle:Companies')
			->findBy(
				array(),
				array('domain'=>'ASC')
		);
		
		$ret=array();
		if ($results && count($results)) {
			foreach ($results as $r) {
				$ret[$r->getId()]=$r->getDomain();
			}
		}
		return $ret;
	}
	
	
	public function getStatusColors() {
	
		$arr=array();
		$arr['000000']='Black';
		$arr['ff0000']='Light Red';
		$arr['880000']='Red';
		$arr['00ff00']='Light Green';
		$arr['008800']='Green';
		$arr['0000ff']='Light Blue';
		$arr['000088']='Blue';
		$arr['800080']='Purple';
		$arr['ff00ff']='Magenta';
		$arr['ffd700']='Gold';
		$arr['ffff00']='Yellow';
		$arr['ffa500']='Orange';
	
		return $arr;
	}
	
	public function getStatusLevels() {
	
		$arr=array();
		$arr[0]='Punch in/out';
		$arr[1]='Other';
	
		return $arr;
	}

	
	public function getModules($id=null) {
			
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('m.id')
			->addSelect('m.name')
			->from('TimesheetHrBundle:Modules', 'm')
			->orderBy('m.name', 'ASC');
			
		if ($id) {
			$qb->where('m.id=:id')
				->setParameter('id', $id);
		}
		$query=$qb->getQuery();
	
		$results=$query->useResultCache(true)->getArrayResult();

		$modules=array();
		
		if ($results) {
			foreach ($results as $result) {
				$first=$result['id'];
				$modules[$result['id']]=array(
					'id'=>$result['id'],
					'name'=>$result['name']
				);
				if (!isset($modules[$result['id']]['domains'])) {
					$modules[$result['id']]['domains']=$this->getSelectedModules($result['id']);
				}
			}
		}
		 
		if ($id) {
			return $modules[$first];
		}
	
		return $modules;
	}
	
	
	public function getStatuses($id=null, $onlyNames=false) {
		 
		$statuses=array();
		$first=null;
		 
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('s.id')
			->addSelect('s.name')
			->from('TimesheetHrBundle:Status', 's')
			->orderBy('s.id', 'ASC');
			
		if ($id) {
			$qb->where('s.id=:id OR s.pair=:id')
				->setParameter('id', $id);
		}
		if (!$onlyNames) {
			$qb
				->addSelect('s.start')
				->addSelect('s.active')
				->addSelect('s.level')
				->addSelect('s.multi')
				->addSelect('s.color')
				->addSelect('s.pair');
		}
		$query=$qb->getQuery();
	
		$results=$query->useResultCache(true)->getArrayResult();
	
		if ($results) {
			if ($onlyNames) {
				foreach ($results as $result) {
					$statuses[$result['id']]=$result['name'];
				}
			} else {
				$statusColors=$this->getStatusColors();
				$statusLevels=$this->getStatusLevels();
				 
				foreach ($results as $result) {
					if ($result['start']) {
						$first=$result['id'];
						$statuses[$result['id']]=array(
							'id'=>$result['id'],
							'nameStart'=>$result['name'],
							'nameFinish'=>'',
							'active'=>$result['active'],
							'activeName'=>$result['active']?'Active':'Inactive',
							'level'=>$result['level'],
							'levelName'=>$statusLevels[$result['level']],
							'multi'=>$result['multi'],
							'color'=>$result['color'],
							'colorName'=>$statusColors[$result['color']]
						);
						if (!isset($statuses[$result['id']]['domains'])) {
							$statuses[$result['id']]['domains']=$this->getSelectedCompanies($result['id']);
						}
					} else {
						$statuses[$result['pair']]['nameFinish']=$result['name'];
					}
				}
			}
		}
		 
		if ($id) {
			return $statuses[$first];
		}
	
		return $statuses;
	}

	
	public function getSelectedCompanies($statusId) {
		
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('std.domainId')
			->from('TimesheetHrBundle:StatusToDomain', 'std')
			->where('std.statusId=:sId')
			->orderBy('std.domainId', 'ASC')
			->groupBy('std.domainId')
			->setParameter('sId', $statusId);
		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
		
		$ret=array();
		if ($results && count($results)) {
			foreach ($results as $result) {
				$ret[]=$result['domainId'];
			}
		}

		return $ret;
	}
	
	
	public function getSelectedModules($moduleId) {
		
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('mc.domainId')
			->from('TimesheetHrBundle:ModulesCompanies', 'mc')
			->where('mc.moduleId=:mId')
			->orderBy('mc.domainId', 'ASC')
			->groupBy('mc.domainId')
			->setParameter('mId', $moduleId);
		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
		
		$ret=array();
		if ($results && count($results)) {
			foreach ($results as $result) {
				$ret[]=$result['domainId'];
			}
		}

		return $ret;
	}
	
	
	public function getCompanies() {
		/*
		 * get all the company names
		 */
		$results=$this->doctrine
			->getRepository('TimesheetHrBundle:Companies')
			->findBy(
				array(),
				array('companyname'=>'ASC')
		);
		
		$ret=array();
		if ($results && count($results)) {
			foreach ($results as $r) {
				$ret[$r->getId()]=$r->getCompanyName();
			}
		}
		return $ret;
	}
	
	
	public function getDomainAHEW($domainId) {
		/*
		 * get the location id by domain name
		 */
		$result=$this->doctrine
			->getRepository('TimesheetHrBundle:Companies')
			->findOneBy(
				array('id'=>$domainId)
			);
		if ($result) {
			return $result->getAHEW();
		} else {
			error_log('wrong domain id:'.$domainId);
			return 0;
		}
	}
	
	
	public function getLocation($id=null, $nameOnly=true, $domainId=null) {
		/*
		 * read the location table by name
		 */
		
		$em=$this->doctrine->getManager();
			
		$qb=$em
			->createQueryBuilder()
			->select('l.id')
			->addSelect('l.name')
			->addSelect('l.addressLine1')
			->addSelect('l.addressLine2')
			->addSelect('l.addressCity')
			->addSelect('l.addressCounty')
			->addSelect('l.addressCountry')
			->addSelect('l.addressPostcode')
			->addSelect('l.phoneLandline')
			->addSelect('l.phoneMobile')
			->addSelect('l.phoneFax')
			->addSelect('l.active')
			->addSelect('l.fixedIpAddress')
			->addSelect('l.latitude')
			->addSelect('l.longitude')
			->addSelect('l.radius')
			->addSelect('c.companyname')
			->from('TimesheetHrBundle:Location', 'l')
			->join('TimesheetHrBundle:Companies', 'c', 'WITH', 'l.domainId=c.id')
			->orderBy('c.companyname', 'ASC')
			->addOrderBy('l.name', 'ASC');
		
		if ($domainId) {
			$qb->andWhere('l.domainId=:dId')
				->setParameter('dId', $domainId);
		}
		if ($id) {
			$qb->andWhere('l.id=:lId')
				->setParameter('lId', $id);
		}
			
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
	
		$arr=array();
		if ($results) {
			foreach ($results as $result) {
				if ($nameOnly) {
					$arr[$result['id']]=$result['name'].($domainId?'':' - '.$result['companyname']);
				} else {
					$tmp=array(
							'id'=>$result['id'],
							'name'=>$result['name'],
							'address'=>array(
									'line1'=>$result['addressLine1'],
									'line2'=>$result['addressLine2'],
									'city'=>$result['addressCity'],
									'county'=>$result['addressCounty'],
									'country'=>$result['addressCountry'],
									'postcode'=>$result['addressPostcode']
							),
							'phone'=>array(
									'landline'=>$result['phoneLandline'],
									'mobile'=>$result['phoneMobile'],
									'fax'=>$result['phoneFax']
							),
							'active'=>$result['active'],
							'fixedipaddress'=>$result['fixedIpAddress'],
							'ipaddress'=>$this->getIpAddress($result['id']),
							'members'=>$this->getMembers($result['id'])
					);
					if ($id) {
						$arr=$tmp;
					} else {
						$arr[]=$tmp;
					}
				}
			}
		}
	
		return $arr;
	}
	
	
	public function getIpAddress($locationId, $toArray=false) {
		
		$em=$this->doctrine->getManager();
		$qb=$em->createQueryBuilder()
			->select('ip.id, ip.locationId, ip.ipAddress, ip.startTime, ip.endTime')
			->from('TimesheetHrBundle:LocationIpAddress', 'ip')
			->orderBy('ip.ipAddress', 'ASC');
		
		if ($locationId) {
			$qb->andWhere('ip.locationId=:lId')
				->setParameter('lId', $locationId);
		}
		if ($toArray) {
			$qb->addSelect('l.name')
				->leftJoin('TimesheetHrBundle:Location', 'l', 'WITH', 'l.id=ip.locationId');
		}
		
		$results=$qb->getQuery()->getArrayResult();
		if ($toArray) {
			$ips=array();
			if ($results && count($results)) {
				foreach ($results as $r) {
					$ips[$r['ipAddress']]=$r['name'];
				}
			}
			
			return $ips;
		}
		
		return $results;
	}
	

	public function getShift($id=null, $domainId=null) {
		/*
		 * read the shifts table by name
		 */
		
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('s.id')
			->addSelect('s.locationId')
			->addSelect('s.startTime')
			->addSelect('s.finishTime')
			->addSelect('s.startBreak')
			->addSelect('s.finishBreak')
			->addSelect('s.strictBreak')
			->addSelect('s.fpStartTime')
			->addSelect('s.fpFinishTime')
			->addSelect('s.fpStartBreak')
			->addSelect('s.fpFinishBreak')
			->addSelect('s.title')
			->addSelect('s.minWorkTime')
			->addSelect('l.name as locationName')
			->from('TimesheetHrBundle:Shifts', 's')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 's.locationId=l.id')
			->where('s.id>0')
			->orderBy('s.locationId', 'ASC')
			->addOrderBy('s.startTime', 'ASC');
		
		if ($id) {
			$qb->andWhere('s.id=:id')
				->setParameter('id', $id);
		}
		if ($domainId) {
			$qb->andWhere('l.domainId=:dId')
				->setParameter('dId', $domainId);
		}
		
		
		$query=$qb->getQuery();
// error_log('query:'.$query->getDql());
		$results=$query->useResultCache(true)->getArrayResult();

		$arr=array();
		if ($results) {
			if ($id) {
				foreach ($results as $result) {
					$arr[$result['id']]=$this->getLocation($result['locationId'], true);
				}
			} else {
				foreach ($results as $result) {
					$arr[$result['id']]=array(
						'id'=>$result['id'],
						'title'=>$result['title'],
						'locationId'=>$result['locationId'],
						'locationName'=>$result['locationName'],
						'startTime'=>$result['startTime'],
						'finishTime'=>$result['finishTime'],
						'fpStartTime'=>$result['fpStartTime'],
						'fpFinishTime'=>$result['fpFinishTime'],
						'fpStartBreak'=>$result['fpStartBreak'],
						'fpFinishBreak'=>$result['fpFinishBreak'],
						'startBreak'=>$result['startBreak'],
						'finishBreak'=>$result['finishBreak'],
						'strictBreak'=>$result['strictBreak'],
						'minWorkTime'=>$result['minWorkTime'],
						'days'=>$this->getShiftDays($result['id']),
						'staffReq'=>$this->getRequirementsForShift($result['id']),
						'qualReq'=>$this->getQualRequirementsForShift($result['id'])
					);
				}
			}
		}
		
		return $arr;
	}

	
	public function getRequirementsForShift($sId) {
		$ret=array();

		$conn=$this->doctrine->getConnection();
		 
		$query='SELECT'.
				' `sr`.`id`,'.
				' `sr`.`groupId`,'.
				' `sr`.`numberOfStaff`,'.
				' `s`.`startTime`,'.
				' `s`.`finishTime`,'.
				' `g`.`name`'.
				' FROM `StaffRequirements` `sr`'.
					' JOIN `Groups` `g` ON `sr`.`groupId`=`g`.`id`'.
					' JOIN `Shifts` `s` ON `sr`.`shiftId`=`s`.`id`'.
				' WHERE `sr`.`shiftId`=:sId'.
				' ORDER BY `g`.`name`';
		 
		$stmt=$conn->prepare($query);
		$stmt->bindValue('sId', $sId);
		$stmt->execute();
	
		$ret=$stmt->fetchAll();
				
		return $ret;
	}
	
	
	public function getQualRequirementsForShift($sId) {
		$ret=array();
	
		$em=$this->doctrine->getManager();
			
		$qb=$em
			->createQueryBuilder()
			->select('qr.id')
			->addSelect('qr.qualificationId')
			->addSelect('qr.numberOfStaff')
			->addSelect('s.startTime')
			->addSelect('s.finishTime')
			->addSelect('q.title as name')
			->addSelect('qr.levelId')
			->addSelect('ql.level')
			->from('TimesheetHrBundle:QualRequirements', 'qr')
			->join('TimesheetHrBundle:Qualifications', 'q', 'WITH', 'qr.qualificationId=q.id')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'qr.shiftId=s.id')
			->leftJoin('TimesheetHrBundle:QualificationLevels', 'ql', 'WITH', 'qr.levelId=ql.id')
			->where('qr.shiftId=:sId')
			->orderBy('q.title', 'ASC')
			->setParameter('sId', $sId);
		
		$query=$qb->getQuery();
		$ret=$query->useResultCache(true)->getArrayResult();

		return $ret;
	}
	
	
	public function getShiftDays($shiftId) {
		$ret=array();
		
		$results=$this->doctrine
			->getRepository('TimesheetHrBundle:ShiftDays')
			->findBy(array('shiftId'=>$shiftId));
		
		if ($results && count($results)) {
			foreach ($results as $result) {
				$ret[$result->getDayId()]=true;
			}
		}
		return $ret;
	}
	
	
	public function getContracts($userId) {

		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('c')
			->from('TimesheetHrBundle:Contract', 'c')
			->where('c.userId=:uId')
			->orderBy('c.csd', 'ASC')
			->setParameter('uId', $userId);

		return $qb->getQuery()->useResultCache(true)->getArrayResult();	
	}
	
	
	public function getTimings($userId, $domainId) {
		 
		$timings=array();
		$conn=$this->doctrine->getConnection();
		 
		$query='SELECT'.
				' `t`.`id`,'.
				' `t`.`shiftId`,'.
				' `s`.`startTime`,'.
				' `s`.`finishTime`,'.
				' `sd`.`dayId`,'.
				' `s`.`locationId`,'.
				' `u`.`domainId`,'.
				' `l`.`name`'.
				' FROM `Timing` `t`'.
					' JOIN `Shifts` `s` ON `t`.`shiftId`=`s`.`id`'.
					' JOIN `Location` `l` ON `s`.`locationId`=`l`.`id`'.
					' JOIN `ShiftDays` `sd` ON `s`.`id`=`sd`.`shiftId` AND `t`.`dayId`=`sd`.`dayId`'.
					' JOIN `Users` `u` ON `t`.`userId`=`u`.`id`'.
				' WHERE `t`.`userId`=:uId';
//				(($latest)?(' ORDER BY `sd`.`dayId`, `s`.`id` LIMIT 1'):(''));
// error_log($query);	
		$stmt=$conn->prepare($query);
		$stmt->bindValue('uId', $userId);
		$stmt->execute();
	
		$results=$stmt->fetchAll();
		 
		if ($results) {
			$ts=mktime(0, 0, 0, date('n'), date('j')-date('N')+1, date('Y'));
			foreach ($results as $result) {
				$timings[$result['dayId']][]=array(
					'id'=>$result['id'],
					'dayId'=>$result['dayId'],
					'day'=>date('l', mktime(0, 0, 0, date('m', $ts), date('d', $ts)+$result['dayId']-1, date('Y', $ts))),
					'hours'=>$this->calculateHours($result, $result['domainId']),
					'start'=>$result['startTime'],
					'finish'=>$result['finishTime'],
					'location'=>$result['name']
				);
			}
		}
// error_log('timings:'.print_r($timings, true));
		return $timings;
		 
	}
	
	
	public function calculateHours(&$result, $domainId, $lunchtime=null, $minhoursforlunch=null, $withLunchtime=false) {
	
		if ($withLunchtime) {
			$lunchtime=0;
		} else {
			if ($lunchtime == null) {
				$lunchtime=$this->getConfig('lunchtimeUnpaid', $domainId);
			}
		}
		if ($minhoursforlunch == null) {
			$minhoursforlunch=$this->getConfig('minhoursforlunch');
		}
		 
		if ($result['startTime'] && $result['finishTime']) {
			if (is_object($result['startTime'])) {
				$minutes=round((strtotime($result['finishTime']->format('H:i:s'))-strtotime($result['startTime']->format('H:i:s')))/60);
				if ($minutes < 0) {
					$minutes=$minutes+24*60;
				}
			} else {
				$minutes=round((strtotime($result['finishTime'])-strtotime($result['startTime']))/60);
				if ($minutes < 0) {
					$minutes=$minutes+24*60;
				}
			}
			if ($minutes>=$minhoursforlunch*60) {
				$minutes-=$lunchtime;
			}
			$result['hours']=$minutes/60;
		} else {
			$minutes=0;
			$result['hours']=0;
		}
	
		return $minutes/60;
	}
	
	
	public function getHolidayEntitlement($userId = null, $contracts=null) {
error_log('getHolidayEntitlement, userId:'.$userId);
		$test=null;
		$request=$this->requestStack->getCurrentRequest();
		$ret=array(
			'yearstart'=>null,
			`csd`=>null,
			'annualholidays'=>0,
			'untilToday'=>0,
			'afterToday'=>0,
			'afterEOC'=>0,
			'taken'=>0,
			'daysOfYear'=>0,
			'currentDay'=>0
		);
		$ts=0;

		if (!$ret['yearstart']) {
			/*
			 * If the start date of the year not specified by person, get start date from domain table,
			 * if not there, get from config table
			 * then calculate holiday entitlement until today based on the current date and last year start date
			 */
			$result=$this->doctrine
				->getRepository('TimesheetHrBundle:Companies')
				->findOneBy(array('domain'=>$request->getHttpHost()));
		
			if ($result && count($result)) {
				if ($result->getYearstart() != null) {
					$ret['yearstart']=$result->getYearstart();
					$ret['annualholidays']=$result->getAHE();
				}
			}
			if (!$ret['yearstart']) {
				$tmp=$this->getConfig('yearstart');
				if ($tmp) {
					$t=explode('-', $tmp);
					$ts=strtotime(date('Y').'-'.$t[0].'-'.$t[1]);
				}
			} else {
				$ts=strtotime($ret['yearstart']->format('Y-m-d H:i:s'));	
			}
			if (date('Y-m-d', $ts) > date('Y-m-d')) {
				$ts=mktime(0, 0, 0, date('m', $ts), date('d', $ts), date('Y')-1);
			}
			$ret['yearstart']=new \DateTime();
			$ret['yearstart']->setTimestamp($ts);
			$ret['csd']=$ret['yearstart'];
			
			if (!$ret['annualholidays']) {
				$ret['annualholidays']=floatval($this->getConfig('annualholidays'));
			}
		}
		
		if ($userId) {
// error_log('userId:'.$userId);			
			if (!$contracts) {
				$contracts=$this->getContracts($userId);
			}

			if ($contracts && count($contracts)) {
// error_log('contracts:'.print_r($contracts, true));
				end($contracts);
				$lastContract=$contracts[key($contracts)];
if ($userId==$test) error_log('lastContract:'.print_r($lastContract, true));
				/*
				 * If the start date is on Year Start, use that based on the configuration,
				 * anyway use Contract Start Date
				 */
				$ret['initHolidays']=$lastContract['initHolidays'];
//				$ret['untilToday']+=$lastContract['initHolidays'];
				$ret['csd']=$lastContract['csd'];
				if (!$lastContract['AHEonYS']) {
					$ret['yearstart']=$lastContract['csd'];
				}
//				if ($lastContract['AHE']) {
//					$ret['annualholidays']=$lastContract['AHE'];
//				}
				$ret['annualholidays']=$this->getCalculatedAHE($userId, $lastContract, $this->getDomainId($request->getHttpHost()));
				/*
				 * collect all the holidays (paid full day off)
				 * from contract start date or year start which is later
				 * until end of contract or today which is earlier 
				 */
				$sd=max($ret['yearstart'], $ret['csd']);
				$sd=$sd->format('Y-m-d');
				$ed=(($lastContract['ced'])?(min($lastContract['ced'], date('Y-m-d'))):(date('Y-m-d')));
if ($userId==$test)  error_log('approved holiday checking between '.print_r($sd, true).' and '.print_r($ed, true));
				$ret['list']=$this->getHolidaysList($userId, $sd, $ed);
				if (count($ret['list'])) {
if ($userId==$test)  error_log('approved holidays:'.print_r($ret['list'], true));
					foreach ($ret['list'] as $l) {
						$ret['taken']+=$l['days'];
						if ($l['entitlement']) {
							/*
							 * Remaining holidays - taken holidays
							 * entitlement:
							 *  -1 if taken from holiday entitlement
							 *  +1 if additionam holiday entitlement (as extra holiday)
							 */
							$ret['untilToday']+=$l['entitlement']*$l['days'];
if ($userId==$test) error_log('untilToday:'.$ret['untilToday']);
						}
					}
				}				
if ($userId==$test) error_log('last contract CED:'.print_r($lastContract['ced'], true));
				if ($lastContract['ced'] && ($lastContract['ced'] >= date('Y-m-d')) || (!$lastContract['ced'])) {

					$tomorrow=date('Y-m-d', mktime(0, 0, 0, date('m'), date('d')+1, date('Y')));
					
					$tmp1=clone $ret['yearstart'];
					$tmp1->modify('+1 year');
					$eoc=$tmp1->format('Y-m-d');
					if ($lastContract['ced']) {
if ($userId==$test) error_log('min('.print_r($eoc, true).' , '.print_r($lastContract['ced']->format('Y-m-d'), true).')');
						$eoc=min($eoc, $lastContract['ced']->format('Y-m-d'));
					}
					
if ($userId==$test) error_log('future holiday checking between '.$tomorrow.' and '.$eoc);
					$fh=$this->getHolidaysList($userId, $tomorrow, $eoc);
if ($userId==$test) error_log('future holidays:'.print_r($fh, true));
					if (count($fh)) {
if ($userId==$test) error_log('future holiday approved');
						foreach ($fh as $l) {
							if ($l['entitlement']) {
								/*
								 * Remaining holidays - taken holidays
								 * entitlement:
								 *  -1 if taken from holiday entitlement
								 *  +1 if additionam holiday entitlement (as extra holiday)
								 */
								$ret['afterToday']-=$l['entitlement']*$l['days'];
if ($userId==$test) error_log('afterToday:'.$ret['afterToday']);
							}
						}
					}
if ($userId==$test) error_log('holiday checking after '.$eoc);
					$heoc=$this->getHolidaysList($userId, $eoc, null);
if ($userId==$test) error_log('holidays after EOC:'.print_r($heoc, true));
					if (count($heoc)) {
if ($userId==$test) error_log('holiday approved');
						foreach ($heoc as $l) {
							if ($l['entitlement']) {
								/*
								 * Remaining holidays - taken holidays
								 * entitlement:
								 *  -1 if taken from holiday entitlement
								 *  +1 if additionam holiday entitlement (as extra holiday)
								 */
								$ret['afterEOC']-=$l['entitlement']*$l['days'];
if ($userId==$test) error_log('afterEOC:'.$ret['afterEOC']);
							}
						}
					}
//				} elseif () {
				
				}
			} else {
				$ret['annualholidays']=0;
			}
		}

		/*
		 * Holiday entitlement calculation:
		 * count how many days in the current year
		 * entitlement until today = entitlement per year / number of days per year * days until today in this year
		 */
		if ($ret['annualholidays'] && date('Y-m-d', $ts)<=date('Y-m-d')) {
			$ret['daysOfYear']=floor((mktime(0, 0, 0, date('m', $ts), date('d', $ts), date('Y', $ts)+1)-mktime(0, 0, 0, date('m', $ts), date('d', $ts), date('Y', $ts)))/(60*60*24));
if ($userId==$test) error_log('max('.$ret['yearstart']->format('Y-m-d H:i:s').' , '.$ret['csd']->format('Y-m-d H:i:s').')');
			$ts=strtotime(max($ret['yearstart']->format('Y-m-d H:i:s'), $ret['csd']->format('Y-m-d H:i:s')));
			$ret['currentDay']=floor((mktime(0, 0, 0, date('m'), date('d'), date('Y'))-mktime(0, 0, 0, date('m', $ts), date('d', $ts), date('Y', $ts)))/(60*60*24));
if ($userId==$test) error_log('pre '.$ret['untilToday']);
if ($userId==$test) error_log($ret['annualholidays'].' / '.$ret['daysOfYear'].' * '.$ret['currentDay']);
			$ret['untilToday']+=$ret['annualholidays']/$ret['daysOfYear']*$ret['currentDay'];
			$ret['untilToday']+=$ret['initHolidays'];
if ($userId==$test) error_log('post '.$ret['untilToday']);
		}
if ($userId==$test) error_log('ret:'.print_r($ret, true));
		return $ret;
	}
	
	
	public function getWeeklySchedule($userId, $timestamp) {

		$monday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp)));
		$sunday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+7, date('Y', $timestamp)));

		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('a.date')
			->addSelect('l.name')
			->addSelect('s.title')
			->addSelect('s.startTime')
			->addSelect('s.finishTime')
			->from('TimesheetHrBundle:Allocation', 'a')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'a.shiftId=s.id')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 's.locationId=l.id')
			->where('a.published!=0')
			->andWhere('a.userId=:uId')
			->andWhere('a.date BETWEEN :date1 AND :date2')
			->orderBy('a.date', 'ASC')
			->setParameter('uId', $userId)
			->setParameter('date1', $monday)
			->setParameter('date2', $sunday);
		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();

		$data=array();
		$ts=strtotime($monday);
		$holidays=$this->getHolidaysForMonth($userId, $monday, $sunday);
		
		for ($i=0; $i<7; $i++) {
			$ts1=mktime(0, 0, 0, date('m', $ts), date('d', $ts)+$i, date('Y', $ts));
			$data[date('Y-m-d', $ts1)]=array(
				'day'=>date('l', $ts1),
				'holidays'=>((isset($holidays[date('Y-m-d', $ts1)]))?($holidays[date('Y-m-d', $ts1)]):(null))
			);
		}
		if ($results && count($results)) {
			foreach ($results as $result) {
				$data[$result['date']->format('Y-m-d')]['timings'][]=$result;
			}
		}

		return $data;
		
	}


	public function getMonthlySchedule($userId, $timestamp) {
		
		$first=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), 1, date('Y', $timestamp)));
		$last=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('t', $timestamp), date('Y', $timestamp)));

		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('a.date')
			->addSelect('l.name')
			->addSelect('s.title')
			->addSelect('s.startTime')
			->addSelect('s.finishTime')
			->from('TimesheetHrBundle:Allocation', 'a')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'a.shiftId=s.id')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 's.locationId=l.id')
			->where('a.published!=0')
			->andWhere('a.userId=:uId')
			->andWhere('a.date BETWEEN :date1 AND :date2')
			->orderBy('a.date', 'ASC')
			->setParameter('uId', $userId)
			->setParameter('date1', $first)
			->setParameter('date2', $last);
		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
		$data=array();
		$ts=strtotime($first);
		$holidays=$this->getHolidaysForMonth($userId, $first, $last);

		for ($i=0; $i<date('t', $timestamp); $i++) {
			$ts1=mktime(0, 0, 0, date('m', $ts), date('d', $ts)+$i, date('Y', $ts));
			$data[date('W', $ts1)][date('Y-m-d', $ts1)]=array(
				'day'=>date('l', $ts1),
				'dayno'=>date('N', $ts1),
				'holidays'=>((isset($holidays[date('Y-m-d', $ts1)]))?($holidays[date('Y-m-d', $ts1)]):(null))
			);
		}
		if ($results && count($results)) {
			foreach ($results as $result) {
				$data[$result['date']->format('W')][$result['date']->format('Y-m-d')]['timings'][]=$result;
			}
		}
		
		return $data;
		
	}

	
	public function getWeeklyLocationSchedule2($locationId, $timestamp) {
error_log('getWeeklyLocationSchedule2');
		$monday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp)));
		$sunday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+7, date('Y', $timestamp)));
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('a.date')
			->addSelect('a.shiftId')
			->addSelect('a.userId')
			->addSelect('l.name')
			->addSelect('u.title as userTitle')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->addSelect('u.username')
			->addSelect('s.title')
			->addSelect('s.startTime')
			->addSelect('s.finishTime')
			->from('TimesheetHrBundle:Allocation', 'a')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'a.shiftId=s.id')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 's.locationId=l.id')
			->join('TimesheetHrBundle:User', 'u', 'WITH', 'a.userId=u.id')
			->where('a.published=1')
			->andWhere('a.locationId=:lId')
			->andWhere('a.date BETWEEN :date1 AND :date2')
			->orderBy('a.date', 'ASC')
			->addOrderBy('s.startTime', 'ASC')
			->addOrderBy('u.firstName', 'ASC')
			->setParameter('lId', $locationId)
			->setParameter('date1', $monday)
			->setParameter('date2', $sunday);
		
		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
		
		$data=array();
		$ts=strtotime($monday);
		for ($i=0; $i<7; $i++) {
			$ts1=mktime(0, 0, 0, date('m', $ts), date('d', $ts)+$i, date('Y', $ts));
			$data[date('Y-m-d', $ts1)]=array(
				'day'=>date('D', $ts1),
				'holidays'=>null
			);
		}
		if ($results && count($results)) {
			foreach ($results as $result) {
				$data[$result['date']->format('Y-m-d')]['shifts'][$result['shiftId']][]=$result;
			}
		}
		
		return $data;
		
	}
	

	public function getWeeklyLocationSchedule($locationId, $timestamp) {
error_log('getWeeklyLocationSchedule');
		$monday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp)));
		$sunday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+7, date('Y', $timestamp)));
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('a.date')
			->addSelect('a.shiftId')
			->addSelect('l.name')
			->addSelect('u.title as userTitle')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->addSelect('u.username')
			->addSelect('s.title')
			->addSelect('s.startTime')
			->addSelect('s.finishTime')
			->from('TimesheetHrBundle:Allocation', 'a')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'a.shiftId=s.id')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 's.locationId=l.id')
			->join('TimesheetHrBundle:User', 'u', 'WITH', 'a.userId=u.id')
			->where('a.published=1')
			->andWhere('a.locationId=:lId')
			->andWhere('a.date BETWEEN :date1 AND :date2')
			->orderBy('a.date', 'ASC')
			->addOrderBy('s.startTime', 'ASC')
			->addOrderBy('u.firstName', 'ASC')
			->setParameter('lId', $locationId)
			->setParameter('date1', $monday)
			->setParameter('date2', $sunday);

		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();

		$data=array();
		$ts=strtotime($monday);
		for ($i=0; $i<7; $i++) {
			$ts1=mktime(0, 0, 0, date('m', $ts), date('d', $ts)+$i, date('Y', $ts));
			$data[date('Y-m-d', $ts1)]=array(
				'day'=>date('l', $ts1),
				'holidays'=>null
			);
		}
		if ($results && count($results)) {
			foreach ($results as $result) {
				$data[$result['date']->format('Y-m-d')]['timings'][]=$result;
			}
		}

		return $data;
	
	}
	
	
	public function getWorkingHours($userId, $timestamp) {
// error_log('getWorkingHours');
		$tsLastMonth=mktime(0, 0, 0, date('n', $timestamp)-1, 1, date('Y', $timestamp));
		$tsCurrentMonth=mktime(0, 0, 0, date('n', $timestamp), 1, date('Y', $timestamp));
		$tsNextMonth=mktime(0, 0, 0, date('n', $timestamp)+1, 1, date('Y', $timestamp));
// error_log('timestamp:'.date('Y-m-d', $timestamp));
// error_log('lastMonth:'.date('Y-m-d', $tsLastMonth));
// error_log('currentMonth:'.date('Y-m-d', $tsCurrentMonth));
// error_log('nextMonth:'.date('Y-m-d', $tsNextMonth));
		$data=array(
			'weekly'=>array(
				'last'=>array(
					'first'=>mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+1-7, date('Y', $timestamp)),
					'last'=>mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+7-7, date('Y', $timestamp)),
					'whr'=>0
				),
				'current'=>array(
					'first'=>mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp)),
					'last'=>mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+7, date('Y', $timestamp)),
					'whr'=>0
				),
				'next'=>array(
					'first'=>mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+1+7, date('Y', $timestamp)),
					'last'=>mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+7+7, date('Y', $timestamp)),
					'whr'=>0
				)
			),
			'monthly'=>array(
				'last'=>array(
					'first'=>mktime(0, 0, 0, date('n', $tsLastMonth), 1, date('Y', $tsLastMonth)),
					'last'=>mktime(0, 0, 0, date('n', $tsLastMonth), date('t', $tsLastMonth), date('Y', $tsLastMonth)),
					'whr'=>0
				),
				'current'=>array(
					'first'=>mktime(0, 0, 0, date('n', $tsCurrentMonth), 1, date('Y', $tsCurrentMonth)),
					'last'=>mktime(0, 0, 0, date('n', $tsCurrentMonth), date('t', $tsCurrentMonth), date('Y', $tsCurrentMonth)),
					'whr'=>0
				),
				'next'=>array(
					'first'=>mktime(0, 0, 0, date('n', $tsNextMonth), 1, date('Y', $tsNextMonth)),
					'last'=>mktime(0, 0, 0, date('n', $tsNextMonth), date('t', $tsNextMonth), date('Y', $tsNextMonth)),
					'whr'=>0
				)
			)				
		);
// error_log('data:'.print_r($data, true));
		
		$d=min($data['monthly']['last']['first'], $data['weekly']['last']['first']);
// error_log('d:'.$d.', date:'.date('Y-m-d', $d));
		$domainId=$this->getDomainIdForUser($userId);
		$last=max($data['monthly']['next']['last'], $data['weekly']['next']['last']);
		while ($d <= $last) {
			$timings=$this->getTimingsForDay($userId, $d);
error_log('timings ('.date('Y-m-d', $d).'):'.print_r($timings, true));
			$whr=$this->calculateHours($timings, $domainId);
error_log('whr:'.print_r($whr, true));			
			foreach ($data as $k=>$v) {
				foreach ($v as $k1=>$v1) {
					if ($d>=$v1['first'] && $d<=$v1['last']) {
error_log('data: '.$k.' : '.$k1.' ('.date('Y-m-d', $v1['first']).' - '.date('Y-m-d', $v1['last']).')'.' : '.print_r($timings, true));
						if (isset($data[$k][$k1]['whr'])) {
error_log('add:'.$data[$k][$k1]['whr'].'+'.$whr);
							$data[$k][$k1]['whr']+=$whr;
						} else {
							$data[$k][$k1]['whr']=$whr;
						}
error_log('added:'.$data[$k][$k1]['whr']);
					}
				}
			}
				
			$d=mktime(0, 0, 0, date('n', $d), date('j', $d)+1, date('Y', $d));
		}
				
		return $data;
	}
	
	public function getHolidaysList($userId, $startDate, $finishDate) {
error_log('getHolidaysList');
//		$ret=array();
// error_log('userId:'.$userId.', start:'.print_r($startDate, true).', finish:'.print_r($finishDate, true));

		if (!$finishDate) {
			$finishDate='9999-12-31';
		}
		$em=$this->doctrine->getManager();
		
		$qb=$em->createQueryBuilder();
		$qb->select('r.id')
			->addSelect('r.start')
			->addSelect('r.finish')
			->addSelect('DATEDIFF(DATE(r.finish), DATE(r.start))+1 as days')
			->addSelect('r.acceptedComment')
			->addSelect('r.comment')
			->addSelect('rt.name')
			->addSelect('rt.entitlement')
			->addSelect('rt.comment as typeComment')
			->from('TimesheetHrBundle:Requests', 'r')
			->join('TimesheetHrBundle:RequestType', 'rt', 'WITH', 'r.typeId=rt.id')
			->where('r.userId=:uId')
			->andWhere('rt.fullday=1')
			->andWhere('rt.paid=1')
			->andWhere('r.accepted>0')
			->andWhere('((DATE(r.start)<=:startDate AND DATE(r.finish)>=:startDate) OR (DATE(r.start)<=:finishDate AND DATE(r.finish)>=:finishDate) OR (DATE(r.start)>=:startDate AND DATE(r.finish)<=:finishDate))')
			->setParameter('uId', $userId)
			->setParameter('startDate', $startDate)
			->setParameter('finishDate', $finishDate);	
		
		return $qb->getQuery()->useResultCache(true)->getResult();
	}
	
	
	public function getConfig($key, $domainId=null) {
		/*
		 * read the config table by name
		 */
// error_log('getConfig');
		if ($domainId) {
			$em=$this->doctrine->getManager();
			
			$qb=$em
				->createQueryBuilder()
				->select('c.'.$key)
				->from('TimesheetHrBundle:Companies', 'c')
				->where('c.id=:dId')
				->setParameter('dId', $domainId);
	
			$query=$qb->getQuery();

			$result=$query->useResultCache(true)->getArrayResult();
			if ($result && count($result) && isset($result[0][$key]) && $result[0][$key]) {
				return $result[0][$key];	
			} else {
				return $this->getConfig($key);
			}			
		} else {
			$result=$this->doctrine
				->getRepository('TimesheetHrBundle:Config')
				->findBy(
					array(
						'name'=>$key,
					),
					array(),
					1
			);
		
			return ((count($result))?($result[0]->getValue()):(''));
		}
	}
	
	
	public function isPublished($shiftId, $date) {
error_log('isPublished');
// error_log('shiftId:'.$shiftId.', date:'.print_r($date, true));
		$em=$this->doctrine->getManager();
		
		$qb=$em->createQueryBuilder()
			->select('a.published')
			->from('TimesheetHrBundle:Allocation', 'a')
			->where('a.date=:date')
			->andWhere('a.shiftId=:sId')
			->setParameter('date', $date)
			->setParameter('sId', $shiftId);
		
		$query=$qb->getQuery();
// error_log('query:'.$query->getDql());
		$results=$query->useResultCache(true)->getArrayResult();		
			
/*		
		$query='SELECT'.
				' `published`'.
				' FROM `Allocation`'.
				' WHERE `date`=:date'.
				' AND `shiftId`=:sId';
			
		$stmt=$conn->prepare($query);
		$stmt->bindValue('date', $date);
		$stmt->bindValue('sId', $shiftId);
		$stmt->execute();
		
		$result=$stmt->fetch();
*/
// error_log('results:'.print_r($results, true));
		if ($results) {
			$result=reset($results);

			return $result['published']?true:false;
		} else {
			return false;
		}
		
	}
	
	public function allocateUserToSchedule($date, $locationId, $shiftId, $userId) {
error_log('allocateUserToSchedule');
		$message='';		
		$em=$this->doctrine->getManager();
		
		$ok=true;
		if ($this->isHolidayOrDayoff($userId, $date)) {
			$message='Sorry, not allowed. Holiday/Day off booked and approved already.';
		} else if ($this->isPublished($shiftId, $date)) {
			$message='Sorry, not allowed. Published already.';
		} else {
			$qb=$em
				->createQueryBuilder()
				->select('a.id')
				->from('TimesheetHrBundle:Allocation', 'a')
				->where('a.locationId=:lId')
				->andWhere('a.userId=:uId')
				->andWhere('a.shiftId=:sId')
				->andWhere('a.date=:date')
				->setParameter('uId', $userId)
				->setParameter('sId', $shiftId)
				->setParameter('lId', $locationId)
				->setParameter('date', $date);

			$query=$qb->getQuery();

			$results=$query->useResultCache(true)->getArrayResult();
			if ($results && count($results)>0) {
				$ok=false;
			}
			
			if ($ok) {
				$allocation=new Allocation();
				
				$allocation->setCreatedOn(new \DateTime('now'));
				$allocation->setDate(new \DateTime($date));
				$allocation->setUserId($userId);
				$allocation->setLocationId($locationId);
				$allocation->setShiftId($shiftId);
				$allocation->setPublished(false);

				try {
					$em->persist($allocation);
					$em->flush($allocation);
				} catch (\Exception $e) {
					error_log('saving error:'.print_r($e->getMessage(), true));
				}
			} else {
				$message='Already allocated';
			}
		}

		return $message;
	}
	
	
	public function removeUserFromSchedule($date, $locationId, $shiftId, $userId) {
error_log('removeUserFromSchedule');		
		$message='Not allowed to remove';
		$em=$this->doctrine->getManager();

		$results=$this->doctrine
			->getRepository('TimesheetHrBundle:Allocation')
			->findBy(
				array(
					'published'=>false,
					'date'=>new \DateTime($date),
					'locationId'=>$locationId,
					'shiftId'=>$shiftId,
					'userId'=>$userId
				)
			);
		if ($results && count($results) == 1) {
			foreach ($results as $result) {
				$em->remove($result);
				$em->flush();
			}
			$message='';
		}
		
		return $message;		
	}
	
	
	public function getAllocationList($date, $locationId, $shiftId) {
error_log('getAllocationList');
		$conn=$this->doctrine->getConnection();
		
		$query='SELECT DISTINCT'.
			' `u`.`username`,'.
			' `u`.`firstName`,'.
			' `u`.`lastName`,'.
			' `a`.`date`,'.
			' `a`.`locationId`,'.
			' `a`.`shiftId`,'.
			' `a`.`userId`,'.
			' `a`.`published`,'.
			' `g`.`name` as `groupname`,'.
			' GROUP_CONCAT(DISTINCT CONCAT_WS(" ", `q`.`title`, `ql`.`level`) ORDER BY `q`.`title` SEPARATOR "\n - ") as `qualifications`,'.
			' `c`.`AWH`'.
			' FROM `Allocation` `a`'.
				' JOIN `Users` `u` ON `a`.`userId`=`u`.`id`'.
				' JOIN `Location` `l` ON `a`.`locationId`=`l`.`id`'.
				' LEFT JOIN `Contract` `c` ON `u`.`id`=`c`.`userId`'.
				' LEFT JOIN `Groups` `g` ON `u`.`groupId`=`g`.`id`'.
				' LEFT JOIN `Shifts` `s` ON `l`.`id`=`s`.`locationId`'.
				' LEFT JOIN (`UserQualifications` `uq`'.
					' JOIN `Qualifications` `q` ON `uq`.`qualificationId`=`q`.`id`) ON `uq`.`userId`=`u`.`id`'.
				' LEFT JOIN `QualificationLevels` `ql` ON `uq`.`levelId`=`ql`.`id`'.
			' WHERE `a`.`date`=:date'.
				' AND `a`.`locationId`=:lId'.
				' AND `a`.`shiftId`=:sId'.
			' GROUP BY `a`.`id`'.
			' ORDER BY `u`.`firstName`, `u`.`lastName`';

		$stmt=$conn->prepare($query);
		$stmt->bindValue('date', $date);
		$stmt->bindValue('sId', $shiftId);
		$stmt->bindValue('lId', $locationId);
		$stmt->execute();

		$usernames=$stmt->fetchAll();
		
		$html='';
		if ($usernames && count($usernames)) {
			$content=array();
			$cnt=count($usernames);
			 
			foreach ($usernames as $u) {
				$content[]=$this->createAllocationDiv($cnt, 0, $u['username'], trim($u['firstName'].' '.$u['lastName']), $u['groupname'], $u['qualifications'], $u['date'], $u['locationId'], $u['shiftId'], $u['userId'], $u['published']);
			}
			$html=implode('', $content).((count($content)>2)?('<br><br>'):(''));
		}
		
		return $html;
	}

	
	public function getAllAllocationDivs($locationId=null, $timestamp=null) {
error_log('getAllocationDivs');	
		$conn=$this->doctrine->getConnection();

		if ($timestamp) {
			$monday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp)));
			$sunday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+7, date('Y', $timestamp)));
		}
		
		$query='SELECT'.
				' `u`.`username`,'.
				' `u`.`firstName`,'.
				' `u`.`lastName`,'.
				' `a`.`date`,'.
				' `a`.`locationId`,'.
				' `a`.`shiftId`,'.
				' `a`.`userId`,'.
				' `a`.`published`,'.
				' `g`.`name` as `groupname`,'.
				' GROUP_CONCAT(DISTINCT CONCAT_WS(" ", `q`.`title`, `ql`.`level`) ORDER BY `q`.`title` SEPARATOR "\n - ") as `qualifications`,'.
				' `sr`.`numberOfStaff`'.
			' FROM `Allocation` `a`'.
				' JOIN `Users` `u` ON `a`.`userId`=`u`.`id`'.
				' JOIN `Location` `l` ON `a`.`locationId`=`l`.`id`'.
				' LEFT JOIN `Groups` `g` ON `u`.`groupId`=`g`.`id`'.
				' LEFT JOIN `Shifts` `s` ON `l`.`id`=`s`.`locationId`'.
				' LEFT JOIN `StaffRequirements` `sr` ON `sr`.`shiftId`=`s`.`id` AND `sr`.`groupId`=`g`.`id`'.
				' LEFT JOIN `UserQualifications` `uq` ON `uq`.`userId`=`u`.`id`'.
				' LEFT JOIN `QualificationLevels` `ql` ON `uq`.`levelId`=`ql`.`id`'.
				' LEFT JOIN `Qualifications` `q` ON `uq`.`qualificationId`=`q`.`id`'.
			' WHERE 1'.
			(($locationId)?(' AND `a`.`locationId`=:lId'):('')).
			(($timestamp)?(' AND `a`.`date` BETWEEN :monday AND :sunday'):('')).
			' GROUP BY `a`.`id`'.
			' ORDER BY `u`.`username`';
	
		$stmt=$conn->prepare($query);
		if ($timestamp) {
			$stmt->bindValue('monday', $monday);
			$stmt->bindValue('sunday', $sunday);
		}
		if ($locationId) {
			$stmt->bindValue('lId', $locationId);
		}
		$stmt->execute();
	
		$usernames=$stmt->fetchAll();
	
		$ret=array();
		
		if ($usernames && count($usernames)) {
			
			$cnt=count($usernames);
			foreach ($usernames as $u) {
				$ret[$u['locationId']][str_replace('-', '', $u['date'])][$u['shiftId']][$u['userId']]
						= $this->createAllocationDiv($cnt, $u['numberOfStaff'], $u['username'], trim($u['firstName'].' '.$u['lastName']), $u['groupname'], $u['qualifications'], $u['date'], $u['locationId'], $u['shiftId'], $u['userId'], $u['published']);
			}
		}
	
		return $ret;
	}
	
	
	public function createAllocationDiv($count, $numberOfStaff, $username, $fullname, $groupname, $qualifications, $date, $locationId, $shiftId, $userId, $published = false) {
// error_log('createAllocationDiv');
		return $this->renderView('TimesheetHrBundle:Internal:allocationdiv.html.twig', array(
			'published'		=> $published,
			'data_id'		=> str_replace('-', '', $date).'_'.$locationId.'_'.$shiftId.'_'.$userId,
			'username'		=> $username,
			'groupname'		=> $groupname,
			'fullname'		=> $fullname,
			'qualifications'=> $qualifications
		));
	}

	
	public function getAllocationForLocation($locationId, $date, $divs=true) {
error_log('getAllocationForLocation');
		$conn=$this->doctrine->getConnection();

		$timestamp=strtotime($date);
    	$monday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp)));
    	$sunday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+7, date('Y', $timestamp)));
    	 
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('u.username')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->addSelect('u.domainId')
			->addSelect('a.date')
			->addSelect('s.startTime')
			->addSelect('s.finishTime')
			->addSelect('a.userId')
			->addSelect('a.published')
			->addSelect('g.name as groupname')
			->from('TimesheetHrBundle:Allocation', 'a')
			->join('TimesheetHrBundle:User', 'u', 'WITH', 'a.userId=u.id')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 'a.locationId=l.id')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'a.shiftId=s.id')			
			->join('TimesheetHrBundle:Groups', 'g', 'WITH', 'u.groupId=g.id')
			->leftJoin('TimesheetHrBundle:StaffRequirements', 'sr', 'WITH', 's.id=sr.shiftId AND sr.groupId=g.id')
			->where('a.date BETWEEN :monday AND :sunday')
			->orderBy('u.firstName', 'ASC')
			->addOrderBy('u.lastName', 'ASC')
			->setParameter('monday', new \DateTime($monday))
			->setParameter('sunday', new \DateTime($sunday));
		
		if ($locationId) {
			$qb->andWhere('a.locationId=:lId')
				->setParameter('lId', $locationId);
		}
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();

		$ret=array();
		
		if ($results && count($results)) {
				
			foreach ($results as $r) {
				if (!isset($ret[$r['userId']])) {
					$ret[$r['userId']]=array(
						'username'=>$r['username'],
						'groupname'=>$r['groupname'],
						'name'=>trim($r['firstName'].' '.$r['lastName']),
						'AWH'=>$this->getAWH($r['userId'], $r['date']->format('Y-m-d')),
						'WH'=>0
					);
				}
				$ret[$r['userId']]['WH']+=$this->calculateHours($r, $r['domainId']);
			}
		}
		
		
		//
		$query='SELECT'.
			' `u`.`username`,'.
			' `u`.`firstName`,'.
			' `u`.`lastName`,'.
			' `u`.`groupId`,'.
			' GROUP_CONCAT(DISTINCT CONCAT_WS("#", `q`.`id`, `ql`.`rank`) ORDER BY `q`.`title` SEPARATOR "|") as `qualifications`,'.
			' `a`.`date`,'.
			' `a`.`locationId`,'.
			' `a`.`published`,'.
			' `a`.`shiftId`,'.
			' `s`.`startTime`,'.
			' `s`.`finishTime`,'.
			' `a`.`userId`'.
			' FROM `Allocation` `a`'.
				' JOIN `Users` `u` ON `a`.`userId`=`u`.`id`'.
				' JOIN `Location` `l` ON `a`.`locationId`=`l`.`id`'.
				' JOIN `Shifts` `s` ON `a`.`shiftId`=`s`.`id`'.
				' LEFT JOIN `Groups` `g` ON `u`.`groupId`=`g`.`id`'.
				' LEFT JOIN (`Qualifications` `q` JOIN `UserQualifications` `uq` ON `q`.`id`=`uq`.`qualificationId` LEFT JOIN `QualificationLevels` `ql` ON `uq`.`levelId`=`ql`.`id`) ON `uq`.`userId`=`u`.`id`'.
			' WHERE `a`.`date` BETWEEN :monday AND :sunday'.
			(($locationId)?(' AND `a`.`locationId`=:lId'):('')).
			' GROUP BY `a`.`id`'.
			' ORDER BY `u`.`firstName`, `u`.`lastName`';
		
		$stmt=$conn->prepare($query);
		if ($timestamp) {
			$stmt->bindValue('monday', $monday);
			$stmt->bindValue('sunday', $sunday);
		}
		if ($locationId) {
			$stmt->bindValue('lId', $locationId);
		}
		$stmt->execute();
		
		$results=$stmt->fetchAll();
		
		$staffMembers=array(); // members of allocation per group per day
		$qualificationMembers=array(); // members allocated per group per day
		
		if ($results && count($results)) {
		
			foreach ($results as $r) {
				$day=date('w', strtotime($r['date']));
				if (!isset($staffMembers[$r['groupId']][$day][$r['shiftId']])) {
					$staffMembers[$r['groupId']][$day][$r['shiftId']]['noOfStaff']=0;
				}
				$staffMembers[$r['groupId']][$day][$r['shiftId']]['noOfStaff']++;
				/*
				 * Check all the qualifications per person
				 */
				$q=((strlen($r['qualifications']))?(explode('|', $r['qualifications'])):(array()));
				if (count($q)) {
					foreach ($q as $q1) {
						$qTmp=explode('#', $q1);
						if (!isset($qualificationMembers[$day][$r['shiftId']][$qTmp[0]]['number'])) {
							$qualificationMembers[$day][$r['shiftId']][$qTmp[0]]['number']=0;
							$qualificationMembers[$day][$r['shiftId']][$qTmp[0]]['rank']=0;
						}
						$qualificationMembers[$day][$r['shiftId']][$qTmp[0]]['number']++;
						// if the level not specified, we'll use the lowest level =1
						$qualificationMembers[$day][$r['shiftId']][$qTmp[0]]['rank']=((isset($qTmp[1]))?($qTmp[1]):(1));
					}
				}
				
			}
		}
		$staff=array();
		$qual=array();
// error_log('members:'.print_r($members, true));		
/*
		$staff=$this->getRequiredStaffList($staffMembers, $monday, $locationId);
		$qual=$this->getRequiredQualificationsList($qualificationMembers, $monday, $locationId);
*/
		if (count($ret)) {
			if ($divs) {
				$rs=$this->createWeeklyDiv($ret, $monday, $sunday);				
				if (isset($staff) && count($staff)) {
					$rs='<div class="allocation allocationHigh">Required staff:<div name="showhide" id="rs_showhidebutton" column="req_staff_div">Show</div><div class="req_staff_div" id="rs_showhide" style="display: none">'.((count($staff))?(implode('<br>', $staff)):('')).'</div></div>'.$rs;
				}
				if (isset($qual) && count($qual)) {
					$rs='<div class="allocation allocationHigh">Required qualifications:<div name="showhide" id="rq_showhidebutton" column="req_qual_div">Show</div><div class="req_qual_div" id="rq_showhide" style="display: none">'.((count($qual))?(implode('<br>', $qual)):('')).'</div></div>'.$rs;
				}
				return $rs;
			} else {
				return $ret;
			}	
		} else {
			if ($divs) {
				$rs='';
				if (isset($staff) && count($staff)) {
					$rs='<div class="allocation allocationHigh">Required staff:<br>'.((count($staff))?(implode('<br>', $staff)):('')).'</div>'.$rs;
				}
				if (isset($qual) && count($qual)) {
					$rs='<div class="allocation allocationHigh">Required qualifications:<br>'.((count($qual))?(implode('<br>', $qual)):('')).'</div>'.$rs;
				}
				return $rs;
			} else {
				return $ret;
			}
		}
	}

	
	public function getRequiredQualificationsList($members, $monday, $locationId) {
error_log('getRequiredQualificationsList');
		/*
		 * create a list for required qualifications per locations for each shifts
		 */
		$ret=array();
		
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('q.title')
			->addSelect('qr.qualificationId')
			->addSelect('qr.numberOfStaff')
			->addSelect('qr.levelId')
			->addSelect('ql.level')
			->addSelect('ql.rank')
			->addSelect('qr.shiftId')
			->addSelect('sd.dayId')
			->addSelect('s.title as shiftTitle')
			->addSelect('s.startTime')
			->addSelect('s.finishTime')
			->addSelect('CASE WHEN (sd.dayId=0) THEN 7 ELSE sd.dayId END as orderby')
			->from('TimesheetHrBundle:Qualifications', 'q')
			->join('TimesheetHrBundle:QualRequirements', 'qr', 'WITH', 'q.id=qr.qualificationId')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'qr.shiftId=s.id')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 's.locationId=l.id')
			->join('TimesheetHrBundle:ShiftDays', 'sd', 'WITH', 's.id=sd.shiftId')
			->leftJoin('TimesheetHrBundle:QualificationLevels', 'ql', 'WITH', 'ql.id=qr.levelId')
			->orderBy('q.title')
			->addOrderBy('sd.dayId')
//			->addOrderBy('IF(sd.dayId=0, 7, sd.dayId)')
			->addOrderBy('orderby', 'ASC')
			->addOrderBy('s.startTime');
		
		if ($locationId) {
			$qb->where('l.id=:lId')
				->setParameter('lId', $locationId);
		}
		
		$results=$qb->getQuery()->useResultCache(true)->getArrayResult();
		$ts=strtotime($monday);
		$tmp=array();
		$days=array();
		if ($results && count($results)) {
			foreach ($results as $r) {
				$day=date('D', mktime(0, 0, 0, date('n', $ts), date('j', $ts)-date('N', $ts)+$r['dayId'], date('Y', $ts)));
				$days[$r['dayId']]=$day;
				$tmp[$r['qualificationId']]['title']=$r['title'].(($r['level'])?(' '.$r['level']):(''));
				$tmp[$r['qualificationId']]['requirement'][$r['dayId']][$r['shiftId']]=array(
					'number'=>$r['numberOfStaff'],
					'rank'=>$r['rank'],
					'title'=>$r['shiftTitle'],
					'time'=>$r['startTime']->format('H:i').'-'.$r['finishTime']->format('H:i'));
			}
			if (count($tmp)) {
				$lastQ=null;
				$lastQualification=null;
				$lastD=null;
				$lastDay=null;
				$ok=false;
				foreach ($tmp as $qId=>$tmp1) {
					if ($lastQ != $qId) {
						$lastQ=$qId;
						$lastQualification=$tmp1['title'];
						$lastD=null;
					}
					if (count($tmp1['requirement'])) {
						foreach ($tmp1['requirement'] as $kd=>$tmp2) {
							if ($lastD!=$kd) {
								$lastD=$kd;
								$lastDay=$days[$kd];
							}
							foreach ($tmp2 as $sId=>$v) {
								if (isset($v['rank'])) {
									error_log('day:'.$kd.', shiftId:'.$sId.', qId:'.$qId.', rank:'.$v['rank']);
								}
								if (isset($members[$kd][$sId][$qId]) && $members[$kd][$sId][$qId]['rank']>=$v['rank'] && $members[$kd][$sId][$qId]['number'] >= $v['number']) {
									$ok=true;
								} else {
									$ok=false;
								}
								if (!$ok) {
									if ($lastQualification) {
										$ret[]=$lastQualification;
										$lastQualification=null;
									}
									if ($lastDay) {
										$ret[]=$lastDay;
										$lastDay=null;
									}
//									$ret[]=$v['time'].' x'.$v['number'];
									$ret[]=$v['title'].' x'.$v['number'];
								}
							}
						}
					}
					
				}
			}
		}
		return $ret;
	}

	
	public function createWeeklyDiv($results, $monday, $sunday) {
		/*
		 * create a div per person into schedule calendar for a location
		 * included:
		 * - agreed weekly hours
		 * - currently allocated weekly hours
		 * - requested and approved holidays
		 */
error_log('createWeeklyDiv');
		$ret='';
	
		if ($results && count($results)) {
			foreach ($results as $k=>$v) {
				
				if ($k > 0) {
					$class='allocationNormal';
					if ($v['AWH']) {
						$ok=$v['WH']-$v['AWH'];
						if ($ok != 0) {
							if ($ok > 0) {
								$class='allocationHigh';
							} else {
								$class='allocationLow';
							}
						}
					} else {
						$class='nocontract';
					}
					$holidays=array();
					$current=strtotime($monday);
					while (date('Y-m-d', $current) <= date('Y-m-d', strtotime($sunday))) {
						$tmp=$this->getCalendarDay($k, $current, true);
						if (count($tmp)) {
							foreach ($tmp as $tmp1) {
								if ($tmp1['accepted'] != -1) {
									$holidays[$k][$tmp1['id']]=$tmp1;
								}
							}
						}
						
						$current=mktime(0, 0, 0, date('m', $current), date('d', $current)+1, date('Y', $current));
					}
					$holidays_html='';
					$holiday_arr=array();
					if (isset($holidays[$k]) && count($holidays[$k])) {
						foreach ($holidays[$k] as $hv) {
							$holiday_arr[]=$this->createHolidayDiv($hv);
						}
						
						if (count($holiday_arr)) {
							$holidays_html='<hr>'.implode(' ', $holiday_arr).'<br><br>';
						}
					}
					$exceptions=$this->getAllocationExceptions($k, $monday, $sunday);
					$exceptions_html='';
					if ($exceptions && count($exceptions)) {
						foreach ($exceptions as $exDate=>$ex) {
							$exceptions_html.='<br>Multiple allocations<br>on '.date('jS M', strtotime($exDate)).'<br>'.implode(', ', $ex['locations']);
						}
					}
					
					$ret.=$this->renderView('TimesheetHrBundle:Internal:allocationdivuser.html.twig', array(
						'class'		=> $class,
						'name'		=> $v['name'],
						'username'	=> $v['username'],
						'groupname'	=> $v['groupname'],
						'AWH'		=> $v['AWH'],
						'WH'		=> $v['WH'],
						'holidays'	=> $holidays_html,
						'exceptions'=> $exceptions_html
					));
				}
			}
		}
		
		return $ret;
	}
	
	
	public function getAllocationExceptions($userId, $monday=null, $sunday=null) {
		
		$ret=array();
		
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('a.id')
			->addSelect('a.date')
			->addSelect('s.locationId')
			->addSelect('s.startTime')
			->addSelect('s.finishTime')
			->addSelect('l.name')
			->from('TimesheetHrBundle:Allocation', 'a')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'a.shiftId=s.id')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 'a.locationId=l.id')
			->join('TimesheetHrBundle:ShiftDays', 'sd', 'WITH', 's.id=sd.shiftId')
			->where('a.userId=:uId')
			->groupBy('a.id')
			->setParameter('uId', $userId);
		
		if ($monday) {
			$qb->andWhere('a.date>=:monday')
			->setParameter('monday', $monday);
		}
		if ($sunday) {
			$qb->andWhere('a.date<=:sunday')
			->setParameter('sunday', $sunday);
		}
		
		$results=$qb->getQuery()->useResultCache(true)->getArrayResult();
		
		if ($results && count($results)) {
			$tmp=array();
			foreach ($results as $res) {
				$date=$res['date']->format('Y-m-d');
// error_log('current date:'.$date.' - '.$res['startTime'].' - '.$res['finishTime']);
				if (count($tmp) && isset($tmp[$date])) {
					foreach ($tmp[$date] as $v) {

						if ((($v['startTime'] <= $res['startTime'] && $v['finishTime'] >= $res['startTime']) || ($v['startTime'] <= $res['finishTime'] && $v['finishTime'] >= $res['finishTime'])) && ($v['locationId']!=$res['locationId'])) {
// error_log('found on '.$date);
							if (!isset($ret[$date])) {
								$ret[$date]=array('count'=>0, 'locations'=>array());
							}
							$ret[$date]['count']++;
							$ret[$date]['locations'][$res['locationId']]=$res['name'];
							$ret[$date]['locations'][$v['locationId']]=$v['name'];
						}
					}
				}
//				if (!isset($tmp[$date])) {
//					$tmp[$date]=array();
//				}
				$tmp[$date][]=array(
					'startTime'=>$res['startTime']->format('H:i:s'),
					'finishTime'=>$res['finishTime']->format('H:i:s'),
					'locationId'=>$res['locationId'],
					'name'=>$res['name']
				);
			}
		}
		
		return $ret;
	}
	
	
	public function createHolidayDiv($data) {
error_log('createHolidayDiv');
		$ret='';
		
		if (isset($data) && count($data)) {
			$ret='<span style="'.
					'padding: 2px;'.
					' margin: 2px;'.
					' font-size: normal;'.
					' font-weight: bold;'.
					' -moz-border-radius: 10px;'.
					' -webkit-border-radius: 10px;'.
					' border-radius: 10px;'.
					' -khtml-border-radius: 10px;'.
			 		' border: #'.$data['borderColor'].' solid 3px;'.
			 		' color: #'.$data['textColor'].';'.
			 		' background-color: #'.$data['backgroundColor'].
					'" title="'.
						$data['name'].': '.trim($data['firstName'].' '.$data['lastName']).
						(($data['comment'])?(PHP_EOL.'Comment: '.$data['comment']):('')).
						PHP_EOL.$this->createHolidayDate($data['typeId'], strtotime($data['start']->format('Y-m-d H:i:s')), strtotime($data['finish']->format('Y-m-d H:i:s'))).
						(($data['accepted']==1)?(PHP_EOL.'Approved by '.trim($data['approvedBy']).' on '.$data['acceptedOn']->format('d/m/Y H:i')):('')).
					'">'.
						$data['initial'].(($data['accepted'])?(''):('<sup style="color: #ff0000; position: relative; left: -3px">*</sup>')).
					'</span>';
		}
		
		
		return $ret;
	}
	
	
	public function createHolidayDate($type, $ts1, $ts2) {

		$holiday=$this->doctrine
			->getRepository('TimesheetHrBundle:RequestType')
			->findOneBy(array('id'=>$type));

		if ($holiday->getFullday()) {
			if (date('Y-m-d', $ts1) == date('Y-m-d', $ts2)) {
				return date('d/m/Y', $ts1);
			} else {
				return date('d/m/Y', $ts1).'-'.date('d/m/Y', $ts2);
			}
		} else {
			switch ($holiday->getBothtime()) {
				case 0 : {
					return date('d/m/Y', $ts1).'-'.date('H:i', $ts2);
					break;
				}
				case 1 : {
					return date('d/m/Y H:i', $ts1).'-'.date('H:i', $ts2);
					break;
				}
				case -1 : {
					return date('d/m/Y H:i', $ts2);
					break;
				}
			}
		}
	}
	
	
	public function getAllLocationDivs($locationId=null, $timestamp=null) {
error_log('getAllLocationDivs');
		
		if ($timestamp) {
			$monday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp)));
			$sunday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+7, date('Y', $timestamp)));
		}

		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('u.username')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->addSelect('u.groupId')
			->addSelect('u.domainId')
			->addSelect('a.date')
			->addSelect('a.locationId')
			->addSelect('a.shiftId')
			->addSelect('a.userId')
			->addSelect('g.name as groupname')
			->addSelect('s.startTime')
			->addSelect('s.finishTime')
//			->addSelect('GROUP_CONCAT(DISTINCT CONCAT_WS(\'#\', q.id, ql.rank) ORDER BY q.title SEPARATOR \'|\') as qualifications')
			->addSelect('GROUP_CONCAT(DISTINCT q.id, \'#\', ql.level ORDER BY q.title SEPARATOR \'|\') as qualifications')
			->from('TimesheetHrBundle:Allocation', 'a')
			->join('TimesheetHrBundle:User', 'u', 'WITH', 'a.userId=u.id')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 'a.locationId=l.id')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'a.shiftId=s.id')			
			->join('TimesheetHrBundle:Groups', 'g', 'WITH', 'u.groupId=g.id')
			->leftJoin('TimesheetHrBundle:UserQualifications', 'uq', 'WITH', 'u.id=uq.userId')
			->leftJoin('TimesheetHrBundle:Qualifications', 'q', 'WITH', 'q.id=uq.qualificationId')
			->leftJoin('TimesheetHrBundle:QualificationLevels', 'ql', 'WITH', 'ql.id=uq.levelId')
			->where('a.date BETWEEN :monday AND :sunday')
			->groupBy('a.id')
			->orderBy('u.firstName', 'ASC')
			->addOrderBy('u.lastName', 'ASC')
			->setParameter('monday', new \DateTime($monday))
			->setParameter('sunday', new \DateTime($sunday));
		
		if ($locationId) {
			$qb->andWhere('a.locationId=:lId')
				->setParameter('lId', $locationId);
		}

		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
		$tmp=array();
		$ret=array();
		$staffMembers=array(); // number of members allocated per group per day per shift
		$qualificationMembers=array();
		
		if ($results && count($results)) {
				
			foreach ($results as $r) {
				$day=date('w', strtotime($r['date']->format('Y-m-d')));
				if (!isset($staffMembers[$r['groupId']][$day][$r['shiftId']])) {
					$staffMembers[$r['groupId']][$day][$r['shiftId']]['noOfStaff']=0;
				}
				$staffMembers[$r['groupId']][$day][$r['shiftId']]['noOfStaff']++;
				/*
				 * Check all the qualifications per person
				 */
				$q=((strlen($r['qualifications']))?(explode('|', $r['qualifications'])):(array()));
				if (count($q)) {
					foreach ($q as $q1) {
						$qTmp=explode('#', $q1);
						if (!isset($qualificationMembers[$day][$r['shiftId']][$qTmp[0]]['number'])) {
							$qualificationMembers[$day][$r['shiftId']][$qTmp[0]]['number']=0;
							$qualificationMembers[$day][$r['shiftId']][$qTmp[0]]['rank']=0;
						}
						$qualificationMembers[$day][$r['shiftId']][$qTmp[0]]['number']++;
						// if the level not specified, we'll use the lowest level =1
						$qualificationMembers[$day][$r['shiftId']][$qTmp[0]]['rank']=((isset($qTmp[1]))?($qTmp[1]):(1));
					}
				}
				
				

				if (!isset($tmp[$r['locationId']][$r['userId']])) {
					$tmp[$r['locationId']][$r['userId']]=array(
						'username'=>$r['username'],
						'groupname'=>$r['groupname'],
						'name'=>trim($r['firstName'].' '.$r['lastName']),
						'AWH'=>$this->getAWH($r['userId'], $r['date']->format('Y-m-d')),
						'WH'=>0
					);
				}
				$tmp[$r['locationId']][$r['userId']]['WH']+=$this->calculateHours($r, $r['domainId']);
			}
		}

		if (count($tmp)) {
			foreach ($tmp as $k=>$v) {
				$ret[$k]=$this->createWeeklyDiv($v, $monday, $sunday);
			}
		}
		
		return $ret;
	}


	public function getCurrentlyRequiredStaff($locationId, $date) {
error_log('getCurrentlyRequiredStaff');
// error_log('locationId:'.$locationId.', date:'.$date.', day:'.date('w', strtotime($date)));
		$em=$this->doctrine->getManager();
		$qb=$em
		->createQueryBuilder()
		->select('sr.shiftId')
		->addSelect('sr.numberOfStaff')
		->addSelect('sr.groupId')
		->addSelect('g.name')
		->addSelect('sd.dayId')
		->from('TimesheetHrBundle:StaffRequirements', 'sr')
		->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'sr.shiftId=s.id')
		->join('TimesheetHrBundle:Groups', 'g', 'WITH', 'sr.groupId=g.id')
		->join('TimesheetHrBundle:Location', 'l', 'WITH', 's.locationId=l.id')
		->join('TimesheetHrBundle:ShiftDays', 'sd', 'WITH', 's.id=sd.shiftId')
		->where('l.id=:lId')
		->andWhere('sd.dayId=:dayId')
		->setParameter('lId', $locationId)
		->setParameter('dayId', date('w', strtotime($date)));
	
		return $qb->getQuery()->useResultCache(true)->getArrayResult();
	}

	
	public function getCurrentlyAllocatedQualifications($locationId, $date) {
error_log('getCurrentlyAllocatedQualifications');
// error_log('locationId:'.$locationId.', date:'.$date);
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('a.userId')
			->addSelect('a.shiftId')
			->addSelect('uq.qualificationId')
			->addSelect('q.title')
			->addSelect('ql.rank')
			->from('TimesheetHrBundle:Allocation', 'a')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'a.shiftId=s.id')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 'a.locationId=l.id')
			->join('TimesheetHrBundle:User', 'u', 'WITH', 'u.id=a.userId')
			->leftJoin('TimesheetHrBundle:UserQualifications', 'uq', 'WITH', 'u.id=uq.userId')
			->leftJoin('TimesheetHrBundle:Qualifications', 'q', 'WITH', 'uq.qualificationId=q.id')
			->leftJoin('TimesheetHrBundle:QualificationLevels', 'ql', 'WITH', 'uq.levelId=ql.id')
			->where('l.id=:lId')
			->andWhere('a.date=:date')
			->setParameter('lId', $locationId)
			->setParameter('date', new \DateTime($date));
	
		return $qb->getQuery()->useResultCache(true)->getArrayResult();
	}
	
	
	public function getCurrentlyRequiredQualifications($locationId, $date) {
error_log('getCurrentlyRequiredQualifications');
// error_log('locationId:'.$locationId.', date:'.$date.', day:'.date('w', strtotime($date)));
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('qr.shiftId')
			->addSelect('qr.numberOfStaff')
			->addSelect('q.title')
			->addSelect('qr.levelId')
			->addSelect('qr.qualificationId')
			->addSelect('ql.level')
			->addSelect('ql.rank')
			->addSelect('sd.dayId')
			->from('TimesheetHrBundle:QualRequirements', 'qr')
			->join('TimesheetHrBundle:Qualifications', 'q', 'WITH', 'qr.qualificationId=q.id')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'qr.shiftId=s.id')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 's.locationId=l.id')
			->join('TimesheetHrBundle:ShiftDays', 'sd', 'WITH', 's.id=sd.shiftId')
			->leftJoin('TimesheetHrBundle:QualificationLevels', 'ql', 'WITH', 'qr.levelId=ql.id')
			->where('l.id=:lId')
			->andWhere('sd.dayId=:dayId')
			->orderBy('q.title')
			->setParameter('lId', $locationId)
			->setParameter('dayId', date('w', strtotime($date)));
		
		return $qb->getQuery()->useResultCache(true)->getArrayResult();
	}
	

	public function getCurrentlyAllocatedStaff($locationId, $date1, $date2=null) {
error_log('getCurrentlyAllocatedStaff');
// error_log('locationId:'.$locationId.', date:'.$date);
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('a.userId')
			->addSelect('a.shiftId')
			->addSelect('u.groupId')
			->addSelect('g.name')
			->addSelect('s.startTime')
			->addSelect('s.finishTime')
			->from('TimesheetHrBundle:Allocation', 'a')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'a.shiftId=s.id')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 'a.locationId=l.id')
			->join('TimesheetHrBundle:User', 'u', 'WITH', 'u.id=a.userId')
			->join('TimesheetHrBundle:Groups', 'g', 'WITH', 'u.groupId=g.id')
			->where('l.id=:lId')
			->groupBy('a.id')
			->orderBy('s.startTime')
			->setParameter('lId', $locationId);
	
		if (isset($date2) && $date2) {
			$qb->andWhere('a.date BETWEEN :date1 AND :date2')
				->setParameter('date1', new \DateTime($date1))
				->setParameter('date2', new \DateTime($date2));
		} else {
			$qb->andWhere('a.date=:date')
				->setParameter('date', new \DateTime($date1));
		}
		return $qb->getQuery()->useResultCache(true)->getArrayResult();	
	}
	
	
	public function getRequiredStaffList($members, $monday, $locationId) {
error_log('getRequiredStaffList');
		$staff=array();
		$notreq=array();

		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('sr.shiftId')
			->addSelect('sr.numberOfStaff')
			->addSelect('sr.groupId')
			->addSelect('g.name')
			->addSelect('GROUP_CONCAT(sd.dayId ORDER BY sd.dayId SEPARATOR \'|\') as days')
			->from('TimesheetHrBundle:StaffRequirements', 'sr')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'sr.shiftId=s.id')
			->join('TimesheetHrBundle:Groups', 'g', 'WITH', 'sr.groupId=g.id')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 's.locationId=l.id')
			->join('TimesheetHrBundle:ShiftDays', 'sd', 'WITH', 's.id=sd.shiftId')
			->where('l.id=:lId')
			->groupBy('sr.id')
			->setParameter('lId', $locationId);
		
		$results=$qb->getQuery()->useResultCache(true)->getArrayResult();
		$tmp=array();
		
		if ($results && count($results)) {
		
			foreach ($results as $r) {
				$days=explode('|', $r['days']);
				if ($days && count($days)) {
					foreach ($days as $d) {
						if ($d>6) {
							$i=0;
						} else {
							$i=$d;
						}
						$tmp[$i][$r['groupId']][$r['shiftId']]=$r['numberOfStaff'];
					}
				}
			}
// error_log('tmp:'.print_r($tmp, true));
			$groups=$this->getGroups();
			$keys=array();
			$ts=strtotime($monday);
			$i1=-1;
			for ($j=1; $j<8; $j++) {
				$i=$j;
				if ($i>6) {
					$i=0;
				}
				$day=date('l', mktime(0, 0, 0, date('m', $ts), date('d', $ts)+$i-1, date('Y', $ts)));
				$date=date('Y-m-d', mktime(0, 0, 0, date('m', $ts), date('d', $ts)+$i-1, date('Y', $ts)));
					
				foreach ($results as $r) {
					$days=explode('|', $r['days']);
					$keys[$r['groupId']]=$r['name'];
					$group=$r['name'];
					
					if (isset($members[$r['groupId']][$i][$r['shiftId']]['noOfStaff']) && $members[$r['groupId']][$i][$r['shiftId']]['noOfStaff'] && in_array($i, $days)) {
						
						// If any staff member already allocated from the same group
						// and the number of required is different
						if ($r['numberOfStaff'] != $members[$r['groupId']][$i][$r['shiftId']]['noOfStaff']) {
// error_log('1');
						$tmp[$date][$r['shiftId']][$group]['required']=$r['numberOfStaff'];
						$tmp[$date][$r['shiftId']][$group]['already']=$members[$r['groupId']][$i][$r['shiftId']]['noOfStaff'];
// error_log('no of staff:'.$members[$r['groupId']][$i][$r['shiftId']]['noOfStaff']);
						if ($i!=$i1) {
								// if the date different, show day in the new line 
								$staff[]='<u>'.$day.'</u>';
								$i1=$i;
							}
							// add the type of staff and the required number - already have
							$staff[]=sprintf('%s: %+d', $r['name'], $r['numberOfStaff']-((isset($members[$r['groupId']][$i][$r['shiftId']]['noOfStaff']))?($members[$r['groupId']][$i][$r['shiftId']]['noOfStaff']):(0)));
						}
						if ($r['numberOfStaff'] == $members[$r['groupId']][$i][$r['shiftId']]['noOfStaff'] && count($members[$r['groupId']][$i]) != 1) {
							if ($i!=$i1) {
								$staff[]='<u>'.$day.'</u>';
								$i1=$i;
							}
							$staff[]=sprintf('%s: %+d', $r['name'], $r['numberOfStaff']-(count($members[$r['groupId']][$i])+1));
						}
					} elseif ((!array_key_exists($r['groupId'], $members) || (!array_key_exists($i, $members[$r['groupId']]))) && in_array($i, $days)) {
// error_log('2');
						$tmp[$date][$r['shiftId']][$group]['required']=$r['numberOfStaff'];
						$tmp[$date][$r['shiftId']][$group]['already']=0;
						if ($i!=$i1) {
							$staff[]='<u>'.$day.'</u>';
							$i1=$i;
						}
						$staff[]=sprintf('%s: %+d', $r['name'], $r['numberOfStaff']);
					} elseif (isset($members[$r['groupId']][$i]) && count($members[$r['groupId']][$i]) == 1) {
// error_log('3');
						$tmp[$date][$r['shiftId']][$group]['required']=0;
						$tmp[$date][$r['shiftId']][$group]['already']=$members[$r['groupId']][$i];
// error_log('date:'.$date.', members:'.print_r($members[$r['groupId']][$i], true));
// error_log('no of staff:'.$members[$r['groupId']][$i][$r['shiftId']]['noOfStaff']);
						if ($i!=$i1) {
							$staff[]='<u>'.$day.'</u>';
							$i1=$i;
						}
						$staff[]=sprintf('%s: %+d', $r['name'], -count($members[$r['groupId']][$i]));
					}
				}
			}
			$i1=-1;
			
			foreach ($members as $k=>$m) {
				if (!in_array($k, array_keys($keys))) {
					if ($i1 == -1) {
						$notreq[-1][]='<u><b>Not required</b></u>';
						$i1=$k;
					}
					foreach ($m as $k1=>$v1) {
						foreach ($v1 as $v2) {
							$notreq[($k1==0)?(7):($k1)][]=sprintf('%s: %s %d', (isset($groups[$k])?($groups[$k]):('')), date('l', mktime(0, 0, 0, date('m', $ts), date('d', $ts)+$k1-1, date('Y', $ts))), $v2['noOfStaff']);
						}
					}
				}
			}
		}
		
//		error_log('tmp:'.print_r($tmp, true));
		
		if (count($notreq)) {
			ksort($notreq);
			foreach ($notreq as $v) {
				foreach ($v as $v1) {
					$staff[]=$v1;
				}
			}
		}
		
		return $staff;
		
	}
	
	
	public function getAWH($userId, $date) {

		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('c.awh')
			
			->from('TimesheetHrBundle:User', 'u')
			->leftJoin('TimesheetHrBundle:Contract', 'c', 'WITH', 'c.userId=u.id')
			->where('u.id=:uId')
			->andWhere('c.csd<=:date')
			->andWhere('c.ced>=:date OR c.ced IS NULL')
			->orderBy('c.csd', 'DESC')
			->setParameter('uId', $userId)
			->setParameter('date', $date)
			->setMaxResults(1);
		
		$results=$qb->getQuery()->useResultCache(true)->getArrayResult();
		
		if ($results && count($results)==1) {
			return $results[0]['awh'];
		} else {
			return 0;
		}
		
	}
	

	public function getCalculatedAWH($userId, $date) {
	// Calculate real Average Weekly Hours, 12 weeks before the specified date
		$test=null;
		$awh=0;
		$avgData=$this->getAverageWorkingHours($userId, strtotime($date));
if ($userId == $test) error_log('avgData at '.$date.':'.print_r($avgData, true));
		if ($avgData['days'] > 0) {
			$awh=$avgData['weeklyhours'];
		}
		return $awh;
	}
	
	
	public function getHolidays($userId, $timestamp, $domainId=null) {
error_log('getHolidays');
		$ret=array();
		
		$last=date('t', $timestamp);
		
		for ($i=0; $i<$last; $i++) {
			$current=mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)+$i, date('Y', $timestamp));
			$ret[date('W', $current)][date('N', $current)]=array(
				'class'=>'currentMonth',
				'day'=>date('D jS M', $current),
				'date'=>date('Y-m-d', $current),
				'content'=>$this->getCalendarDay($userId, $current, 0, $domainId)
			);
		}
		foreach (array_keys($ret) as $k) {
			for ($i=1; $i<=7; $i++) {
				if (!isset($ret[$k][$i])) {
					$ret[$k][$i]=array(
						'class'=>'otherMonth',
						'day'=>'',
						'date'=>'',
						'content'=>''
					);
				}
			}
			ksort($ret[$k]);
		}
		
		return $ret;
	}


	
	public function getCalendarDay($userId, $timestamp, $data=0, $domainId=null) {
// error_log('getCalendarDay, userId:'.$userId.', timestamp:'.$timestamp.', date:'.date('Y-m-d', $timestamp).', data:'.$data);		
//		$conn=$this->doctrine->getConnection();
		$em=$this->doctrine->getManager();
		$ret=array();
		$securityContext = $this->container->get('security.context');
		$admin=false;
		$groupId=null;
		$locationId=null;
		 
		if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
			if (TRUE === $securityContext->isGranted('ROLE_ADMIN')) {
				$admin=true;
			} elseif (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
				$currentUser=$securityContext->getToken()->getUser();
				if ($currentUser->getGroupAdmin()) {
					$groupId=$currentUser->getGroupId();
				}
				if ($currentUser->getLocationAdmin()) {
					$locationId=$currentUser->getLocationId();
				}
				if ($groupId || $locationId) {
					$admin=true;
				}
			}
		}

		$qb=$em
			->createQueryBuilder();
		
		$qb->select('r.id')
			->addSelect('r.typeId')
			->addSelect('r.start')
			->addSelect('r.finish')
			->addSelect('r.accepted')
			->addSelect('r.comment')
			->addSelect('r.acceptedOn')
			->addSelect('rt.name')
			->addSelect('rt.textColor')
			->addSelect('rt.backgroundColor')
			->addSelect('rt.borderColor')
			->addSelect('rt.initial')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->addSelect('(SELECT CONCAT(u1.firstName, \' \', u1.lastName) FROM TimesheetHrBundle:User as u1 WHERE u1.id=r.acceptedBy) as approvedBy')
			
			->from('TimesheetHrBundle:Requests', 'r')
			->join('TimesheetHrBundle:RequestType', 'rt', 'WITH', 'rt.id=r.typeId')
			->join('TimesheetHrBundle:User', 'u', 'WITH', 'u.id=r.userId')
			->where('u.id>0')			
			->orderBy('r.createdOn', 'ASC');
		
		if ($domainId) {
			$qb->andWhere('u.domainId=:dId')
				->setParameter('dId', $domainId);
		}
		if ((!$admin && $userId) || $data) {
			$qb->andWhere('u.id=:uId')
				->setParameter('uId', $userId);
		}
		if ($admin && $groupId) {
			$qb->andWhere('u.groupId=:gId')
				->setParameter('gId', $groupId);	
		}
		if ($admin && $locationId) {
			$qb->andWhere('u.locationId=:lId')
				->setParameter('lId', $locationId);
		}
		if ($timestamp) {
			$qb->andWhere(':date BETWEEN DATE_FORMAT(r.start, \'%Y-%m-%d\') AND DATE_FORMAT(r.finish, \'%Y-%m-%d\')');
		}

		$qb->setParameter('date', date('Y-m-d', $timestamp));
		$results=$qb->getQuery()->useResultCache(true)->getArrayResult();
// error_log('results:'.print_r($results, true));
		if ($data) {
			/*
			 * If we need data only, return
			 */
			return $results;
		}
		if ($results) {
			/*
			 * Create html div
			 */
			foreach ($results as $result) {
				$span='<span style="'.
					'padding: 2px;'.
					' margin: 2px;'.
					' font-size: normal;'.
					' font-weight: bold;'.
					' -moz-border-radius: 10px;'.
					' -webkit-border-radius: 10px;'.
					' border-radius: 10px;'.
					' -khtml-border-radius: 10px;'.
			 		' border: #'.(($result['accepted'] == -1)?('aaaaaa'):($result['borderColor'])).' solid 3px;'.
			 		' color: #'.(($result['accepted'] == -1)?('aaaaaa'):($result['textColor'])).';'.
			 		' background-color: #'.$result['backgroundColor'].
					'" title="'.
						$result['name'].': '.trim($result['firstName'].' '.$result['lastName']).
						(($result['comment'])?(PHP_EOL.'Comment: '.$result['comment']):('')).
						(($result['accepted']==1)?(PHP_EOL.'Approved by '.trim($result['approvedBy']).' on '.$result['acceptedOn']->format('d/m/Y H:i')):('')).
						(($result['accepted'] == 0)?(PHP_EOL.'Pending...'):('')).
						(($result['accepted'] == -1)?(PHP_EOL.'Denied'):('')).
					'">'.
						$result['initial'].(($result['accepted'])?(''):('<sup style="color: #ff0000; position: relative; left: -3px">*</sup>')).
					'</span>';
				$ret[]=$span;
			}
		}
		
		return ((count($ret))?(implode(' ', $ret)):(''));
	}
	
	
	public function importSageXML($parser) {
error_log('importSageXML');

		$return=array();
		$details=array();
		$vals=array();
		$index=null;
		$i=0;
		
		$details = array (
			'Employees.WorkStartDate',
			'Employees.WorkEndDate',
			'Employees.Surname',
			'Employees.Forename',
			'Employees.Title',
			'Employees.Address1',
			'Employees.Address2',
			'Employees.Address3',
			'Employees.Address4',
		#	'Employees.Address5',
			'Employees.Postcode',
			'Employees.Relationship',
			'Employees.DepartmentName',
			'Employees.CurrentJobTitle',
			'Employees.Telephone',
			'Employees.MobileTelephone',
			'Employees.CostCentreName',
			'Employees.EmailAddress',
			'Employees.Gender',
			'Employees.MaritalStatus',
			'Employees.DateOfBirth',
			'Employees.EthnicOrigin',
			'Employees.Nationality',
			'Employees.Contact',
			'Employees.ContactAddress1',
			'Employees.ContactAddress2',
			'Employees.ContactAddress3',
			'Employees.ContactAddress4',
		#	'Employees.ContactAddress5',
			'Employees.ContactPostcode',
			'Employees.ContactTel',
			'Employees.ContactMobile',
			'Employees.ContactEmail',
			'Employees.Analysis1',
			'Employees.Analysis2',
		#	'Employees.Analysis3',
			'Employees.TaxCode',
			'Employees.NINumber',
			'Employees.NICat',
			'Employees.PaymentPeriod',
			'Employees.Reference'
		);
		$p = xml_parser_create();
		xml_parse_into_struct($p, $parser, $vals, $index);
		xml_parser_free($p);
		foreach ($vals as $value1) {
			if ($value1['tag'] == 'SECTION' && $value1['type'] == 'open' && $value1['level'] == 4 && $value1['attributes']['NAME'] == 'Details') {
				$i++;
			}
			if (count($value1) && $value1['tag'] == 'COLUMN' && $value1['level'] == 6) {
				if (in_array($value1['attributes']['NAME'], $details)) {
					$v = explode('.', $value1['attributes']['NAME']);
					$details[$i]-> $v[1] = $value1['value'];
				}
			}
		}
/*
 * 
 */
	
//		$queries = array ();
		$staffmembers = array ();
//		$staff=0;
		if (count($details)) {
			$userRepo=$this->doctrine->getRepository('TimesheetHrBundle:User');
			$em=$this->doctrine->getManager();

			$currentUser=$this->container->get('security.context')->getToken()->getUser();
			$domainId=$this->getDomainIdForUser($currentUser->getId());
			
			foreach ($details as $data) {
				$user=$userRepo->findOneBy(array('PN'=>$data->Reference));
				if (isset($user) && $user) {
					$new=false;
				} else {
					$user=new User();
					$new=true;
				}
//				$query = 'SELECT `empfullname` FROM `employees` WHERE `pn`="'.$data->Reference.'"';
				
//				$result = $mysql->database2->query($query);
				
				foreach ($data as $k => $v) {
					$data-> $k = str_replace("'", '', $data-> $k);
				}
			
				if (strpos(strtolower($data->DepartmentName), "centre") !== false || strpos(strtolower($data->DepartmentName), "marketing") !== false) {
//					$staff = 1;
					$staffmembers[] = $data->Forename . " " . $data->Surname;
				} else {
//					$staff = 0;
				}
			
				$n=explode('-', $data->Nationality);
			
				if (count($n) > 1) {
					$data->Nationality=$n[0];
					$data->VisaType=$n[1];
					$data->VisaExpire=date_deformat((strlen($n[2])==10?($n[2]):(substr($n[2],0,6).'20'.substr($n[2],6,2))));
				} else {
					$data->VisaType='';
					$data->VisaExpire='';
				}

				$user->setTitle(ucwords(strtolower($data->Title)));
				$user->setFirstName($data->Forename);
				$user->setLastName($data->Surname);
				$user->setPhoneMobile($data->MobileTelephone);
				$user->setPhoneLandline($data->Telephone);
				$user->setEthnic($data->EthnicOrigin);
				$user->setNationality($data->Nationality); // check
				$user->setBirthday($data->DateOfBirth);
				$user->setEmail($data->EmailAddress);
				$user->setAddressLine1($data->Address1);
				$user->setAddressLine2('');
				$user->setAddressCity($data->Address2);
				$user->setAddressCounty($data->Address3);
				$user->setAddressCountry($data->Address4);
				$user->setAddressPostcode($data->Postcode);
				$user->setNokName($data->Contact);
				$user->setNokPhone($data->ContactMobile);
				$user->setNokRelation($data->Relationship);
				
				$user->setNI($data->NINumber);
				$user->setTaxCode($data->TaxCode);
				$user->setNICategory($data->NICat);
				$user->setPaymentFrequency($data->PaymentPeriod);
				$user->setMaritalStatus($data->MaritalStatus);
				$user->setIsActive($data->WorkEndDate > 0 && $data->WorkEndDate < date('Y-m-d'));
				$user->setNotes('');
				$user->setEnabled(true);
				
				if ($new) {
					$user->setPlainPassword('123456'); // default password
					$user->setRoles(array('ROLE_USER'));
					
					$generatedUsername=$this->generateUsername($data->Forename, $data->Surname);
					$username=$generatedUsername;
					$user->setUsername($username);
					
					$user->setLoginRequired(true);
					$user->setDomainId($domainId);
					
					$em->persist($user);
				}
				$em->flush($user);
			}
		}
		if (count($staffmembers)) {
			$return[]='Staff members: ' . implode(', ', $staffmembers);
		}

/*
 * 
 */
		
		return ((count($return))?(implode(',', $return)):(''));
	}

	
	public function generateUsername($firstname, $lastname) {
		 
		$fname=preg_replace("/[^a-zA-Z0-9]+/", '', strtolower(trim($firstname)));
		$lname=preg_replace("/[^a-zA-Z0-9]+/", '', strtolower(trim($lastname)));
		$uname=$fname.'.'.substr($lname, 0, 1);
		 
		$i=0;
		$ok=false;
	
		while (!$ok) {
			$username=$uname.(($i)?($i):(''));
			error_log('username check:'.$username);
			$u=$this->doctrine
				->getRepository('TimesheetHrBundle:User')
				->findOneBy(array('username'=>$username));
			 
			if (!$u) {
				$ok=true;
			}
			$i++;
		}
		 
		return $username;
	}
	
	
	public function getUsersForManager($user, $name=null, $limit=0, $role='ROLE_USER') {
		
		$ret=array();
		if (isset($user) && is_object($user)) {
			$em=$this->doctrine->getManager();
			
			$qb=$em->createQueryBuilder()
				->select('u.id')
				->addSelect('u.username')
				->addSelect('u.title')
				->addSelect('u.firstName')
				->addSelect('u.lastName')
				->addSelect('u.lastTime')
				->addSelect('u.lastStatus')
				->addSelect('u.lastIpAddress')
				->addSelect('u.payrolCode')
				->addSelect('u.lastComment')
				->from('TimesheetHrBundle:User', 'u')
				->where('u.isActive=true');
			
			if ($role!='ROLE_SYSADMIN') {
				$qb->andWhere('u.domainId=:dId')
					->setParameter('dId', $user->getDomainId());
			}
			if ($role=='ROLE_USER') {
				$qb->andWhere('u.id=:uId')
					->setParameter('uId', $user->getId());
			}
			$where=array();	
			if ($role=='ROLE_MANAGER' && $user->getGroupAdmin()) {
				$where[]='u.groupId=:gId';
				$qb->setParameter('gId', $user->getGroupId());
			}
			if ($role=='ROLE_MANAGER' && $user->getLocationAdmin()) {
				$where[]='u.locationId=:lId';
				$qb->setParameter('lId', $user->getLocationId());
			}
			if (count($where)) {
				$qb->andWhere('('.implode(' OR ', $where).')');
			}
			if ($name) {
				$qb->andWhere('u.username LIKE :name OR u.firstName LIKE :name OR u.lastName LIKE :name')
					->setParameter(':name', '%'.$name.'%');
			}
			if ($limit) {
				$qb->setMaxResults($limit)
					->orderBy('u.lastTime', 'DESC');
			} else {
				$qb->orderBy('u.firstName', 'ASC')
					->addOrderBy('u.lastName', 'ASC');		
			}
	
			$results=$qb->getQuery()->useResultCache(true)->getArrayResult();
			
			if ($results) {
				foreach ($results as $result) {
					$ret[$result['id']]=$result;
				}
			}
		}
		return $ret;
	}
	
	public function getTimesheet($userId, $timestamp, $usersearch, $session, $domainId=0, $selectedUserId=0, $availableUsers=null) {
error_log('getTimesheet');
		$test=null;
// error_log('1 memory:'.memory_get_usage());
// error_log('userId:'.$userId.', timestamp:'.$timestamp.', usersearch:'.$usersearch.', domainId:'.$domainId.', selectedUserId:'.$selectedUserId.', availableUsers ('.count($availableUsers).'):'.print_r($availableUsers, true));	
		$em=$this->doctrine->getManager();
		
		$ret=array();
		$admin=false;
		$groupId=null;
		$locationId=null;
		$totalUsers=0;
		$holidays=array();
		
		if ($this->isAdmin()) {
			$admin=true;
		} else if ($this->isManager()) {

			$currentUser=$this->container->get('security.context')->getToken()->getUser();
			if ($currentUser->getGroupAdmin()) {
				$groupId=$currentUser->getGroupId();
			}
			if ($currentUser->getLocationAdmin()) {
				$locationId=$currentUser->getLocationId();
			}
			if ($groupId || $locationId) {
				$admin=true;
			}
		}
		$locationIps=$this->getIpAddress(null, true);
// error_log('admin:'.(($admin)?'true':'false'));
// $time=microtime(true);		
		
		$qb=$em
			->createQueryBuilder()
			
			->select('i.timestamp')
			->addSelect('i.ipAddress')
			->addSelect('i.comment')
			->addSelect('i.userId')
			->addSelect('i.statusId')
			->addSelect('i.id')
			->addSelect('i.createdOn')
			->addSelect('i.createdBy')

			->addSelect('s.name')
			->addSelect('s.start')
			->addSelect('s.pair')
			->addSelect('s.level')
			->addSelect('s.multi')
			->addSelect('s.color')
			
			->addSelect('sh.startTime')
			->addSelect('sh.finishTime')
			
			->addSelect('l.name as locationName')
			
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->addSelect('u.username')
			
			->addSelect('a.locationId')
			
			
			->from('TimesheetHrBundle:Info', 'i')
			->join('TimesheetHrBundle:Status', 's', 'WITH', 'i.statusId=s.id')
			->join('TimesheetHrBundle:User', 'u', 'WITH', 'i.userId=u.id')
			->leftJoin('TimesheetHrBundle:Contract', 'c', 'WITH', 'c.userId=u.id AND c.csd<=DATE(i.timestamp) AND (c.ced>=DATE(i.timestamp) OR c.ced IS NULL)')
			->leftJoin('TimesheetHrBundle:Allocation', 'a', 'WITH', 'a.userId=u.id AND a.date=DATE(i.timestamp)')
			->leftJoin('TimesheetHrBundle:Shifts', 'sh', 'WITH', 'sh.id=a.shiftId AND sh.locationId=a.locationId')
			->leftJoin('TimesheetHrBundle:Location', 'l', 'WITH', 'l.id=a.locationId')
			->where('u.domainId=:dId')
			->andWhere('i.deleted=0')
			->groupBy('i.id')
			->orderBy('u.firstName', 'ASC')
			->orderBy('u.lastName', 'ASC')
			->addOrderBy('i.timestamp', 'ASC')
			->setParameter('dId', $domainId);

		if (!$admin && $userId) {
			$qb->andWhere('u.id=:uId')
				->setParameter('uId', $userId);
		}
		if ($selectedUserId) {
			if ($selectedUserId > 0) {
				$qb->andWhere('u.id=:userId')
					->setParameter('userId', $selectedUserId);
			}
		} else {
			// if not selected any user, result should be empty
			$qb->andWhere('u.id<0');
		}
		if (isset($availableUsers) && is_array($availableUsers) && count($availableUsers)) {
			$qb->andWhere('u.id IN (\''.implode('\',\'', array_keys($availableUsers)).'\')');
		}
		if ($timestamp) {

//error_log('timestamp:'.date('Y-m-d H:i:s', $timestamp));
			$startTime=date('Y-m-01 00:00:00', $timestamp);
			$finishTime=date('Y-m-t 23:59:59', $timestamp);
			
			$tmpDate=new \DateTime(date('Y-m-01 00:00:00', $timestamp));
			$tmpDate->modify('-1 day');
			$startTimePrev=$tmpDate->format('Y-m-d H:i:s');
			
			$tmpDate=new \DateTime(date('Y-m-t 23:59:59', $timestamp));
			$tmpDate->setTime(23, 59, 59);
			$tmpDate->modify('+1 day');
			$finishTimeNext=$tmpDate->format('Y-m-d H:i:s');


			$qb->andWhere('((s.start=1 AND i.timestamp>=:dateStartPrev) OR (i.timestamp>=:dateStart)) AND ((s.start=0 AND i.timestamp<=:dateFinishNext) OR (i.timestamp<=:dateFinish))')
				->setParameter('dateStart', $startTime)
				->setParameter('dateFinish', $finishTime)
				->setParameter('dateStartPrev', $startTimePrev)
				->setParameter('dateFinishNext', $finishTimeNext);
// error_log('startTime:'.$startTime);
// error_log('finishTime:'.$finishTime);
// error_log('startTimePrev:'.$startTimePrev);
// error_log('finishTimeNext:'.$finishTimeNext);
		}
// error_log('1 sql:'.$qb->getQuery()->getDql());
		$iterableResult=$qb->getQuery()->useResultCache(true)->iterate();

// error_log('1st no of results:'.count($results).', time:'.(microtime(true)-$time));
		if (isset($iterableResult)) {

			$last=array();
			$lastUser=null;
			$lastDate=null;
			$otherId=0;
			$userLastSignIn=array();

			$timezone=$session->get('timezone');
			
//			while (($result1 = $iterableResult->next()) !== false) {
			foreach ($iterableResult as $result1) {
// error_log('result1:'.print_r($result1, true));

				$result=reset($result1);
				unset($result1);

				// Overwrite timestamp with local time as string				
				$d=new \DateTime($result['timestamp']->format('Y-m-d H:i:s'), new \DateTimeZone('UTC'));
				$d->setTimezone(new \DateTimeZone($timezone));
				$d1=clone $d;
				$d2=clone $d;
				$result['timestamp']=$d->format('Y-m-d H:i:s');
// error_log('timestamp:'.print_r($result['timestamp'], true));
// error_log('startTime:'.print_r($result['startTime'], true));
// error_log('finishTime:'.print_r($result['finishTime'], true));
				if ($result['startTime'] && is_object($result['startTime'])) {
					$d1->setTime($result['startTime']->format('H'), $result['startTime']->format('i'), $result['startTime']->format('s'));
					$result['startTime']=clone $d1;
				} else {
					if (isset($last[$result['username']]['startTime']) && $last[$result['username']]['startTime']) {
						$result['startTime']=$last[$result['username']]['startTime'];
					}
				}
				if ($result['finishTime'] && is_object($result['finishTime'])) {
					$d2->setTime($result['finishTime']->format('H'), $result['finishTime']->format('i'), $result['finishTime']->format('s'));
					$result['finishTime']=clone $d2;
				} else {
					if (isset($last[$result['username']]['finishTime']) && $last[$result['username']]['finishTime']) {
						$result['finishTime']=$last[$result['username']]['finishTime'];
					}
				}
				
				if (!isset($holidays[$result['userId']])) {
					$holidays[$result['userId']]=$this->getHolidaysForMonth($result['userId'], $startTime, $finishTime);
				}
				
				$date=$d->format('Y-m-d');
// error_log('date:'.$date.', d:'.$d->format('Y-m-d'));				
				if (isset($ret[$result['userId']][$date][$result['statusId']])) {
// error_log('existing data');
					// Data already exists, check the time
					// if start, check the first
					// if end, check the last
					if (isset($ret[$result['userId']][$date][$result['statusId']]['comment'])) {
						$ret[$result['userId']][$date][$result['statusId']]['comment'].=$result['comment'];
					} else {
						$ret[$result['userId']][$date][$result['statusId']]['comment']=$result['comment'];
					}

					if ($result['multi']) {
// error_log('multi entry '.print_r($result, true));

						if ($lastUser!=$result['username'] && $lastDate!=$date) {
							$lastUser=$result['username'];
							$lastDate=$date;
							$otherId=0;
							$last[$result['username']]['startTime']=null;
							$last[$result['username']]['finishTime']=null;
						}
						// Multi entry allowed per day	
						if ($result['start']) {
							$ret[$result['userId']][$date][$result['statusId']]['multi'][0][++$otherId]=$result['timestamp'];
						} else {
							$ret[$result['userId']][$date][$result['statusId']]['multi'][1][$otherId]=$result['timestamp'];
						}
					} else {
						// Single entry allowed per day
// error_log('single entry');
						if ($result['start']) {
// error_log('start');
							if (isset($ret[$result['userId']][$date][$result['statusId']]['timestamp'])) {
								$ret[$result['userId']][$date][$result['statusId']]['timestamp']=min($ret[$result['userId']][$date][$result['statusId']]['timestamp'], $result['timestamp']);
							} else {
								$ret[$result['userId']][$date][$result['statusId']]['timestamp']=$result['timestamp'];
							}
						} else {
// error_log('finish');
							if (isset($ret[$result['userId']][$date][$result['statusId']]['timestamp'])) {
								$tmp=$ret[$result['userId']][$date][$result['statusId']]['timestamp'];
								$ret[$result['userId']][$date][$result['statusId']]['timestamp']=max($ret[$result['userId']][$date][$result['statusId']]['timestamp'], $result['timestamp']);
								if ($ret[$result['userId']][$date][$result['statusId']]['timestamp'] != $tmp) {
									if (isset($userLastSignIn[$result['username']])) {
										// if signed out next day, we note it
										// and if corrected, add the corrected date and time and a note
										end($userLastSignIn[$result['username']]);
										$tmpDate=date('Y-m-d', strtotime(prev($userLastSignIn[$result['username']])));
//										$tmpTime=date('H:i', strtotime($tmp));
										if (isset($ret[$result['userId']][$tmpDate][2]['comment'])) {
											$ret[$result['userId']][$tmpDate][2]['comment'].=', ';
										} else {
											$ret[$result['userId']][$tmpDate][2]['comment']='';
										}
										if (count($userLastSignIn[$result['username']])==2) {
											$ret[$result['userId']][$tmpDate][2]['timestamp']=$tmp;
//											$ret[$result['username']][$tmpDate][2]['comment'].='Next day signed out ('.$tmpTime.')';
											$ret[$result['userId']][$tmpDate][0]['class']='PunchMissing';
											// if signed out next day, we give option to correct it
											$ret[$result['userId']][$tmpDate][2]['changeable']=true;
										} elseif (count($userLastSignIn[$result['username']]) > 2) {
											// if already corrected, note only
//											$ret[$result['username']][$tmpDate][2]['comment'].='Next day signed out ('.$tmpTime.')';
										}
									}
								}
							} else {
								$ret[$result['userId']][$date][$result['statusId']]['timestamp']=$result['timestamp'];
							}
						}
					}
					
					
						
				} else {
					// new data
// error_log('new data, id:'.$result['id'].', username:'.$result['username'].', timestamp:'.$result['timestamp']);
					if ($result['start'] && $result['statusId'] == 1) {
						$userLastSignIn[$result['username']][]=$result['timestamp'];
					}
					if (isset($userLastSignIn[$result['username']])) {
						$lastDate=end($userLastSignIn[$result['username']]);
					} else {
						$lastDate=null;
					}

					// check if the check out date and time is related to the previous check in time?
					if (!$result['start'] && !isset($ret[$result['userId']][$lastDate][$result['statusId']]['timestamp'])) {
// error_log('date:'.$date.', last day:'.$lastDate.', timestamp:'.$result['timestamp']);
// error_log('startTime:'.print_r($result['startTime'], true).', finishTime:'.print_r($result['finishTime'], true));
						if (isset($result['startTime']) && $date != substr($lastDate, 0, 10)) {
							$result['startTime']->setDate(substr($lastDate, 0, 4), substr($lastDate, 5, 2), substr($lastDate, 8, 2));
						}
						// if so, use the last check in date
						$date=substr($lastDate, 0, 10);
//						$date=$tmpDate;
//					} else {
//						$tmpDate=$date;
					}
						
					$tmpAgreed=null;
					$tmpAgreedOrig=null;
					if ($result['start']) {
						if (!is_null($result['startTime'])) {
							$tmpAgreed=clone $result['startTime'];
							$tmpAgreedOrig=clone $tmpAgreed;
// error_log('startTime, agreed:'.print_r($tmpAgreed, true));
						}
					} else {
						if (!is_null($result['finishTime'])) {
							$tmpAgreed=clone $result['finishTime'];
							$tmpAgreedOrig=clone $tmpAgreed;
// error_log('finishTime, agreed:'.print_r($tmpAgreed, true));
						}
					}
					$tmpHolidays=((isset($holidays[$result['userId']][$date]))?($holidays[$result['userId']][$date]):(null));
//					$date=$tmpDate;
					
					$ret[$result['userId']][$date][$result['statusId']]=array(
						'userId'=>$result['userId'],
						'comment'=>$result['comment'],
						'username'=>$result['username'],
						'name'=>trim($result['firstName'].' '.$result['lastName']),
						'status'=>$result['name'],
						'day'=>date('D jS M', strtotime($result['timestamp'])),
						'timestamp'=>$result['timestamp'],
						'agreed'=>$tmpAgreed,
						'agreedOrig'=>$tmpAgreedOrig,
						'location'=>((isset($locationIps[$result['ipAddress']]))?($locationIps[$result['ipAddress']]):('')), // $result['locationName'],
						'ipAddress'=>$result['ipAddress'],
						'createdBy'=>$result['createdBy'],
						'createdByName'=>$this->getUserFullNameById($result['createdBy']),
						'createdOn'=>$result['createdOn'],
						'holidays'=>$tmpHolidays
					);
					$ret[$result['userId']][$date][0]=array(
						'checked'=>$this->getTimesheetChecked($result['userId'], $date),
						'userId'=>$result['userId'],
						'username'=>$result['username'],
						'agreedStart'=>$result['startTime'],
						'agreedFinish'=>$result['finishTime'],						
						'name'=>trim($result['firstName'].' '.$result['lastName']),
						'day'=>date('D jS M', strtotime($result['timestamp'])),
						'location'=>$result['locationName'],
						'locationId'=>$result['locationId'],
						'holidays'=>$tmpHolidays,
						'minWorkTime'=>0 // $result['minWorkTime']
					);

					if (isset($holidays[$result['userId']][$date]) && count($holidays[$result['userId']][$date])) {
						foreach ($holidays[$result['userId']][$date] as $h) {
							if (isset($h['agreedStart']) && $result['statusId']==1) {
// error_log('orig agreed check in:'.$ret[$result['username']][$date][$result['StatusId']]['agreed']);
								$ret[$result['userId']][$date][$result['statusId']]['agreedOrig']=$ret[$result['userId']][$date][$result['statusId']]['agreed'];
								$ret[$result['userId']][$date][$result['statusId']]['agreed']=$h['agreedStart'];
							}
							if (isset($h['agreedFinish']) && $result['statusId']==2) {
// error_log('orig agreed check out:'.$ret[$result['username']][$date][$result['StatusId']]['agreed']);
								$ret[$result['userId']][$date][$result['statusId']]['agreedOrig']=$ret[$result['userId']][$date][$result['statusId']]['agreed'];
								$ret[$result['userId']][$date][$result['statusId']]['agreed']=$h['agreedFinish'];
							}
						}
					}
						
					$class='';
					switch ($result['statusId']) {
						case 1 : {
							// Signing in
							if ($result['startTime'] && $result['startTime']->format('H:i:s') >= date('H:i:s', strtotime($result['timestamp']))) {
								$class='PunchCorrect';
							} else {								
								$class='PunchIncorrect';
							}
							break;
						}
						case 2 : {
							// Signing out
							if ($result['finishTime'] && $result['finishTime']->format('H:i:s') <= date('H:i:s', strtotime($result['timestamp']))) {
								$class='PunchCorrect';
							} else {
								$class='PunchIncorrect';
							}
							break;
						}
					}
					$ret[$result['userId']][$date][$result['statusId']]['class']=$class;
					
				}
				$lastUser=$result['username'];
				$last[$result['userId']]['startTime']=$result['startTime'];
				$last[$result['userId']]['finishTime']=$result['finishTime'];
//				$lastDate=$date;
				unset($result1);
				unset($result);
			}
			$em->flush();
			$em->clear();
			
			
			
			
			
			
			
if ($test && isset($ret[$test])) error_log('1.ret:'.print_r(array_keys($ret[$test]), true));
			
			
			$loginRequired=array();
			foreach ($ret as $userId=>$v) {
				if (!isset($loginRequired[$userId])) {
					$loginRequired[$userId]=$this->isLoginRequiredById($userId, $domainId);
				}
				if (!isset($holidays[$userId])) {
error_log('get holidays:'.$startTime.' - '.$finishTime);
					$holidays[$userId]=$this->getHolidaysForMonth($userId, $startTime, $finishTime);
				} else {
error_log('holidays:'.print_r($holidays[$userId], true));
				}
				for ($i=1; $i<=date('t', $timestamp); $i++) {
					$ts=mktime(0, 0, 0, date('m', $timestamp), $i, date('Y', $timestamp));
					$tmpHolidays=((isset($holidays[$userId][date('Y-m-d', $ts)]))?($holidays[$userId][date('Y-m-d', $ts)]):(null));
error_log('tmpHolidays on '.date('Y-m-d', $ts).':'.print_r($tmpHolidays, true));
					if (isset($v[date('Y-m-d', $ts)])) {
// error_log('data setted on '.date('Y-m-d', $ts));
						if (!isset($ret[$userId][date('Y-m-d', $ts)][1]['agreed']) || !isset($ret[$userId][date('Y-m-d', $ts)][2]['agreed'])) {
// error_log('no agreed time in or out');
							$ret[$userId][date('Y-m-d', $ts)][0]['userId']=$userId;
							$ret[$userId][date('Y-m-d', $ts)][0]['class']='PunchMissing';
							$ret[$userId][date('Y-m-d', $ts)][0]['comment']='No allocated shift';
						}
//						$this->getCorrectedTimes($ret[$k][date('Y-m-d', $ts)], $domainId);
					} else {
						if ($loginRequired[$userId]) {
							// Login required, here something missing, not logged in even the shift allocated
error_log('login required');
							$arr=array(
								'userId'=>$userId,
								'comment'=>'',
								'day'=>'',
								'timestamp'=>null,
								'startTime'=>null,
								'finishTime'=>null
							);
							
							$ret[$userId][date('Y-m-d', $ts)][1]=$arr;
							$ret[$userId][date('Y-m-d', $ts)][2]=$arr;
							if ($ts<time() && $this->isAllocatedShift($userId, date('Y-m-d', $ts))) {
error_log('user allocated');
								$tmpTimings=$this->getTimingsForDay($userId, $ts);
								$ret[$userId][date('Y-m-d', $ts)][1]['agreed']=$tmpTimings['startTime'];
								$ret[$userId][date('Y-m-d', $ts)][1]['agreedOrig']=$tmpTimings['startTime'];
								$ret[$userId][date('Y-m-d', $ts)][2]['agreed']=$tmpTimings['finishTime'];
								$ret[$userId][date('Y-m-d', $ts)][2]['agreedOrig']=$tmpTimings['finishTime'];
								
//								$ret[$userId][date('Y-m-d', $ts)][0]['userId']=$userId;
								$ret[$userId][date('Y-m-d', $ts)][0]['agreedStart']=$tmpTimings['startTime'];
								$ret[$userId][date('Y-m-d', $ts)][0]['agreedFinish']=$tmpTimings['finishTime'];
								$ret[$userId][date('Y-m-d', $ts)][0]['class']='PunchMissing';
								$ret[$userId][date('Y-m-d', $ts)][0]['comment']='Missing sign in/out';
							} else {
error_log('user not allocated');
//if (isset($ret[$k][date('Y-m-d', $ts)][0]['class'])) {
//	error_log('class:'.$ret[$k][date('Y-m-d', $ts)][0]['class'].' on '.date('Y-m-d', $ts));
//} else {
//	error_log('no class on '.date('Y-m-d', $ts));
//}
								if ($ts < time()) {
									$ret[$userId][date('Y-m-d', $ts)][0]['class']='PunchDayoff';
									$ret[$userId][date('Y-m-d', $ts)][0]['comment']='Dayoff';
								}
							}
							$ret[$userId][date('Y-m-d', $ts)][0]['userId']=$userId;
//							$this->getCorrectedTimes($ret[$k][date('Y-m-d', $ts)], $domainId);
						} else {
							// Login not required, we add the shift details to sign in/out
error_log('login not required');
							$tmpTimings=$this->getTimingsForDay($userId, $ts);
							if ($tmpTimings && count($tmpTimings)) {
error_log('timing entered');

								$location=$this->getLocation($tmpTimings['locationId'], true);
								$arr0=array(
									'userId'=>$userId,
									'class'=>'PunchCorrect',
									'comment'=>'',
									'agreedStart'=>$tmpTimings['startTime'],
									'agreedFinish'=>$tmpTimings['finishTime'],
									'userId'=>$userId,
									'WorkTime'=>0,
									'Late'=>0,
									'Leave'=>0,
									'Overtime'=>0,
									'OvertimeAgreed'=>0
								);
								$arr1=array(
									'userId'=>$userId,
									'comment'=>'',
									'day'=>date('D jS M', $ts),
									'timestamp'=>date('Y-m-d ', mktime(8, 0, 0, date('m', $ts), date('d', $ts), date('Y', $ts))).$tmpTimings['startTime']->format('H:i:s'),
									'agreed'=>$tmpTimings['startTime'], // date('H:i:s', mktime(8, 0, 0, date('m', $ts), date('d', $ts), date('Y', $ts))),
									'agreedOrig'=>$tmpTimings['startTime'], // date('H:i:s', mktime(8, 0, 0, date('m', $ts), date('d', $ts), date('Y', $ts))),
									'location'=>$location[$tmpTimings['locationId']],
									'startTime'=>null,
									'finishTime'=>null
								);
								$arr2=array(
									'userId'=>$userId,
									'comment'=>'',
									'day'=>date('D jS M', $ts),
									'timestamp'=>date('Y-m-d ', mktime(16, 0, 0, date('m', $ts), date('d', $ts), date('Y', $ts))).$tmpTimings['finishTime']->format('H:i:s'),
									'agreed'=>$tmpTimings['finishTime'], // date('H:i:s', mktime(16, 0, 0, date('m', $ts), date('d', $ts), date('Y', $ts))),
									'agreedOrig'=>$tmpTimings['finishTime'], //date('H:i:s', mktime(16, 0, 0, date('m', $ts), date('d', $ts), date('Y', $ts))),
									'location'=>$location[$tmpTimings['locationId']],
									'startTime'=>null,
									'finishTime'=>null
								);
//error_log('timestamp:'.print_r($tmpTimings, true));									
								$ret[$userId][date('Y-m-d', $ts)][1]=$arr1;
								$ret[$userId][date('Y-m-d', $ts)][2]=$arr2;
								$ret[$userId][date('Y-m-d', $ts)][0]=$arr0;
								
								
							} else {
error_log('no timings entered');
								$arr=array(
									'userId'=>$userId,
									'comment'=>'',
									'day'=>'',
									'timestamp'=>null,
									'startTime'=>null,
									'finishTime'=>null
								);
									
								$ret[$userId][date('Y-m-d', $ts)][1]=$arr;
								$ret[$userId][date('Y-m-d', $ts)][2]=$arr;
								
							}
						}
					}

					$ret[$userId][date('Y-m-d', $ts)][0]['holidays']=$tmpHolidays;
					$ret[$userId][date('Y-m-d', $ts)][1]['holidays']=$tmpHolidays;
					$ret[$userId][date('Y-m-d', $ts)][2]['holidays']=$tmpHolidays;
					
					
					$this->getCorrectedTimes($ret[$userId][date('Y-m-d', $ts)], $domainId);
				}
				foreach (array_keys($ret[$userId]) as $ak) {
					if ($ak < substr($startTime, 0, 10) || $ak > substr($finishTime, 0, 10)) {
error_log('unset '.$ak);
						unset($ret[$userId][$ak]);
					}
				}
				ksort($ret[$userId]);
			}
		}





if ($test && isset($ret[$test])) error_log('2.ret:'.print_r(array_keys($ret[$test]), true));






// get fingerprint reader informations
error_log('get fingerprint records');

		if ($domainId) {
			$shiftStatus=array();

			
			$fpreaders=$this->doctrine
				->getRepository('TimesheetHrBundle:FPReaders')
				->findBy(array('domainId'=>$domainId));

			foreach ($fpreaders as $fpr) {
error_log('reader id:'.$fpr->getId());
				$qb=$em->createQueryBuilder()
				
					->select('fpa.userId')
					->addSelect('fpa.status')
					->addSelect('fpa.verified')
					->addSelect('fpa.timestamp')
					->addSelect('u.id')
					->addSelect('u.username')
							
					->from('TimesheetHrBundle:FPReaderAttendance', 'fpa')
					->leftJoin('TimesheetHrBundle:FPReaderToUser', 'fptu', 'WITH', 'fptu.readerId=:rId AND fpa.userId=fptu.readerUserId')
					->leftJoin('TimesheetHrBundle:User', 'u', 'WITH', 'fptu.userId=u.id')
					->where('fpa.readerId=:rId')
					->orderBy('fpa.timestamp', 'ASC')
					->setParameter('rId', $fpr->getId());
				
				if (!$admin && $userId) {
					$qb->andWhere('u.id=:uId')
						->setParameter('uId', $userId);
				}
				if ($selectedUserId) {
					if ($selectedUserId > 0) {
						$qb->andWhere('u.id=:userId')
							->setParameter('userId', $selectedUserId);
					}
				} else {
					// if not selected any user, result should be empty
					$qb->andWhere('u.id<0');
				}
				if (isset($availableUsers) && is_array($availableUsers) && count($availableUsers)) {
					$qb->andWhere('u.id IN (\''.implode('\',\'', array_keys($availableUsers)).'\')');
				}
				if ($timestamp) {
					$qb->andWhere('fpa.timestamp>=:dateStartPrev AND fpa.timestamp<=:dateFinishNext')
						->setParameter('dateStartPrev', $startTimePrev)
						->setParameter('dateFinishNext', $finishTimeNext);
				}

				$results=$qb->getQuery()->useResultCache(true)->getArrayResult();
				if ($results && count($results)) {
					foreach ($results as $r) {
						$locationId=$fpr->getLocationId();
						$location=$this->getLocation($locationId, true);
						$shift=$this->getUserShiftStatus($r['id'], $r['timestamp'], $locationId, $fpr->getDomainId());
						if ($shift && is_array($shift) && count($shift) && isset($shift['shiftId']) && !is_null($shift['date'])) {
							if (!isset($shiftStatus[$r['id']][$shift['date']][$shift['shiftId']])) {
// error_log('not exists, create');
								$shiftStatus[$r['id']][$shift['date']][$shift['shiftId']]=array(
									'Check In'=>null,
									'Check Out'=>null,
									'Break Start'=>null,
									'Break Finish'=>null,
									'shiftId'=>$shift['shiftId'],
									'shift'=>$shift['title'].' '.$shift['startTime']->format('H:i').'-'.$shift['finishTime']->format('H:i'),
									'startTime'=>$shift['startTime'],
									'finishTime'=>$shift['finishTime'],
									'location'=>$shift['location'],
									'username'=>$r['username']
								);
							}
							if ($shift['status'] == 'Check In/Out') {
								if (!isset($shiftStatus[$r['id']][$shift['date']][$shift['shiftId']]['Check In'])) {
// error_log('Check In not exists, status=Check In');
									// 1st check in/out is check in
									$shiftStatus[$r['id']][$shift['date']][$shift['shiftId']]['Check In']=$r['timestamp'];
								} else {
// error_log('Check In exists, status=Check Out');
									// last check in/out is check out
									$shiftStatus[$r['id']][$shift['date']][$shift['shiftId']]['Check Out']=$r['timestamp'];
								}
							}
							if ($shift['status'] == 'Break In/Out') {
								if (!isset($shiftStatus[$r['id']][$shift['date']][$shift['shiftId']]['Break Start'])) {
									// 1st break in/out is break start
									$shiftStatus[$r['id']][$shift['date']][$shift['shiftId']]['Break Start']=$r['timestamp'];
								} else {
									// last break in/out is break finish
									$shiftStatus[$r['id']][$shift['date']][$shift['shiftId']]['Break Finish']=$r['timestamp'];
								}								
							}
						}
					}
					
				}
			}
			$tmpUsers=array();
// if ($test) error_log('check shiftStatus:'.print_r($shiftStatus, true));
// if ($test && isset($shiftStatus[$test])) error_log('2. shiftStatus:'.print_r($shiftStatus[$test], true));
			if (isset($shiftStatus) && $shiftStatus && count($shiftStatus)) {
// if ($test && isset($shiftStatus[$test])) error_log('**** shiftStatus:'.print_r($shiftStatus[$test], true));
				
				foreach ($shiftStatus as $userId=>$ss) {
					foreach ($ss as $tmpDate=>$sTS) {
						$tmp2=array_keys($sTS);
						$tmpShiftId=reset($tmp2);
						if (!in_array($userId, $tmpUsers)) {
							$tmpUsers[]=$userId;
						}						
//
						$tmpD1=new \DateTime($tmpDate.' '.$sTS[$tmpShiftId]['startTime']->format('H:i:s'));
						$tmpD2=new \DateTime($tmpDate.' '.$sTS[$tmpShiftId]['finishTime']->format('H:i:s'));

						$arr0=array(
							'userId'=>$userId,
							'class'=>((isset($ss[$tmpDate][$tmpShiftId]['Check In']) && isset($ss[$tmpDate][$tmpShiftId]['Check Out']))?('PunchCorrect'):('PunchMissing')),
							'comment'=>'',
							'agreedStart'=>$tmpD1,
							'agreedFinish'=>$tmpD2,
							'userId'=>$userId,
							'WorkTime'=>0,
							'Late'=>0,
							'Leave'=>0,
							'Overtime'=>0,
							'OvertimeAgreed'=>0
						);
						$arr1=array(
							'userId'=>$userId,
							'comment'=>'',
							'day'=>date('D jS M', strtotime($tmpDate)),
							'timestamp'=>((isset($ss[$tmpDate][$tmpShiftId]['Check In']))?($ss[$tmpDate][$tmpShiftId]['Check In']->format('Y-m-d H:i:s')):(null)),
							'agreed'=>$tmpD1,
							'agreedOrig'=>$tmpD1,
							'location'=>$location[$locationId],
							'startTime'=>null,
							'finishTime'=>null,
							'ipAddress'=>'FP Reader'
						);
						$arr2=array(
							'userId'=>$userId,
							'comment'=>'',
							'day'=>date('D jS M', strtotime($tmpDate)),
							'timestamp'=>((isset($ss[$tmpDate][$tmpShiftId]['Check Out']))?($ss[$tmpDate][$tmpShiftId]['Check Out']->format('Y-m-d H:i:s')):(null)),
							'agreed'=>$tmpD2,
							'agreedOrig'=>$tmpD2,
							'location'=>$location[$locationId],
							'startTime'=>null,
							'finishTime'=>null,
							'ipAddress'=>'FP Reader'
						);
						$ret[$userId][$tmpDate][1]=$arr1;
						$ret[$userId][$tmpDate][2]=$arr2;
						$ret[$userId][$tmpDate][0]=$arr0;
						unset($arr0);
						unset($arr1);
						unset($arr2);
//
// error_log('2 ********************* uId:'.$userId);
						$this->getCorrectedTimes($ret[$userId][$tmpDate], $domainId);
					}
						
				}
				if (count($tmpUsers)) {
					foreach ($tmpUsers as $userId) {
						$ts=strtotime($startTime);
						$d=strtotime($finishTime);
						while ($ts <= $d) {
							if (!isset($ret[$userId][date('Y-m-d', $ts)])) {
								$arr=array(
										'userId'=>$userId,
										'comment'=>'',
										'day'=>'',
										'timestamp'=>null,
										'startTime'=>null,
										'finishTime'=>null
								);
								$ret[$userId][date('Y-m-d', $ts)][1]=$arr;
								$ret[$userId][date('Y-m-d', $ts)][2]=$arr;
								$ret[$userId][date('Y-m-d', $ts)][0]['class']='PunchDayoff';
								$ret[$userId][date('Y-m-d', $ts)][0]['comment']='Dayoff';
								$ret[$userId][date('Y-m-d', $ts)][0]['userId']=$userId;
							}
							
							$ts=mktime(0, 0, 0, date('n', $ts), date('j', $ts)+1, date('Y', $ts));
						}
						ksort($ret[$userId]);
					}
				}
			}
		}

		
if ($test && isset($ret[$test])) error_log('3.ret:'.print_r(array_keys($ret[$test]), true));

//		 else
		{
			// No sign in/out data
			// If sign in/out not required, use the allocation data
error_log('no sign in/out data');			

$time=microtime(true);
			$qb=$em
				->createQueryBuilder()
				->select('u.id')
				->addSelect('u.username')
					
				->from('TimesheetHrBundle:User', 'u')
				->leftJoin('TimesheetHrBundle:Contract', 'c', 'WITH', 'c.userId=u.id')
				->leftJoin('TimesheetHrBundle:Allocation', 'a', 'WITH', 'a.userId=u.id')
				->leftJoin('TimesheetHrBundle:Shifts', 'sh', 'WITH', 'sh.id=a.shiftId')
				->leftJoin('TimesheetHrBundle:Location', 'l', 'WITH', 'l.id=a.locationId')
				->where('u.domainId=:dId')
				->andWhere('a.locationId=sh.locationId')
				->groupBy('u.id')
				->orderBy('u.id', 'ASC')
				->setParameter('dId', $domainId);
				
			if (!$admin && $userId) {
				$qb->andWhere('u.id=:uId')
					->setParameter('uId', $userId);
			}
			if ($selectedUserId) {
				if ($selectedUserId > 0) {
					$qb->andWhere('u.id=:userId')
						->setParameter('userId', $selectedUserId);
				}
			} else {
				// if not selected any user, result should be empty
				$qb->andWhere('u.id<0');
			}
			if (isset($availableUsers) && is_array($availableUsers) && count($availableUsers)) {
				$qb->andWhere('u.id IN (\''.implode('\',\'', array_keys($availableUsers)).'\')');
			}
			if ($timestamp) {
				$startTime=date('Y-m-01', $timestamp);
				$finishTime=date('Y-m-t', $timestamp);
				$qb->andWhere('a.date BETWEEN :dateStart AND :dateFinish')
					->andWhere('c.csd<=:dateFinish')
					->andWhere('c.ced>=:dateFinish OR c.ced IS NULL')
					->setParameter('dateStart', $startTime)
					->setParameter('dateFinish', $finishTime);
			}
			$query=$qb->getQuery();
			$users=$query->useResultCache(true)->getArrayResult();
			if (!count($users)) {
				$users=array();
				if ($availableUsers) {
					foreach ($availableUsers as $au) {
						$totalUsers++;
						if (count($users) < 10) {
							if ($selectedUserId == -1 || $selectedUserId == $au['id']) {
								$users[]=array('id'=>$au['id'], 'username'=>$au['username']);
							}
						}
					}
				}
				
				
			} else {
				$totalUsers=count($users);
			}
// error_log('users:'.print_r($users, true));
			foreach ($users as $uTmp) {
			//
			//
			//
				$userId=$uTmp['id'];
				$username=$uTmp['username'];
				$ts=strtotime($startTime);
				$d=strtotime($finishTime);
				if ($userId && !isset($holidays[$userId])) {
					$holidays[$userId]=$this->getHolidaysForMonth($userId, $startTime, $finishTime);
// error_log('start:'.print_r($startTime, true));
// error_log('finish:'.print_r($finishTime, true));
// error_log('holidays for '.$userId.':'.print_r($holidays, true));
				}
				if ($userId) {
					// but we have userId
					if (!$this->isLoginRequired($username, $domainId)) {
							
						while ($ts <= $d) {
// error_log('ts:'.$ts);
							$tmpTimings=$this->getTimingsForDay($userId, $ts);
							if (!isset($ret[$username][date('Y-m-d', $ts)][0])) {
// error_log('not exists');
								if (($tmpTimings && count($tmpTimings)) || (isset($holidays[$userId][date('Y-m-d', $ts)]))) {
									$agreedStartTime=null;
									$agreedFinishTime=null;
									$origStartTime=null;
									$origFinishTime=null;
									$actualHolidays=null;
									$overtime=0;
									$agreedOvertime=0;
									if ($tmpTimings && count($tmpTimings)) {
										$location=$this->getLocation($tmpTimings['locationId'], true);
										$tmpTimings['startTime']=$tmpTimings['startTime']->setDate(date('Y', $ts), date('m', $ts), date('d', $ts));
										$tmpTimings['finishTime']=$tmpTimings['finishTime']->setDate(date('Y', $ts), date('m', $ts), date('d', $ts));
										$origStartTime=$tmpTimings['startTime'];
										$origFinishTime=$tmpTimings['finishTime'];
										$agreedStartTime=$origStartTime;
										$agreedFinishTime=$origFinishTime;
									}
									if (isset($holidays[$userId][date('Y-m-d', $ts)])) {
										$actualHolidays=$holidays[$userId][date('Y-m-d', $ts)];
										$this->currentStartAndFinishTime($agreedStartTime, $agreedFinishTime, $agreedOvertime, $actualHolidays, $userId);
									}
									$arr0=array(
										'userId'=>$userId,
										'class'=>'PunchCorrect',
										'comment'=>'',
										'agreedStart'=>$agreedStartTime,
										'agreedFinish'=>$agreedFinishTime,
										'WorkTime'=>0,
										'Late'=>0,
										'Leave'=>0,
										'Overtime'=>$overtime,
										'OvertimeAgreed'=>$agreedOvertime,
										'holidays'=>$actualHolidays
									);
									$arr1=array(
										'userId'=>$userId,
										'comment'=>'',
										'day'=>date('D jS M', $ts),
										'timestamp'=>date('Y-m-d ', mktime(8, 0, 0, date('m', $ts), date('d', $ts), date('Y', $ts))).((is_null($agreedStartTime))?('??:??'):($agreedStartTime->format('H:i:s'))),
										'agreed'=>$agreedStartTime,
										'agreedOrig'=>$origStartTime,
										'location'=>((isset($location[$tmpTimings['locationId']]))?($location[$tmpTimings['locationId']]):(null)),
										'startTime'=>null,
										'finishTime'=>null,
										'holidays'=>$actualHolidays
									);
									$arr2=array(
										'userId'=>$userId,
										'comment'=>'',
										'day'=>date('D jS M', $ts),
										'timestamp'=>date('Y-m-d ', mktime(16, 0, 0, date('m', $ts), date('d', $ts), date('Y', $ts))).((is_null($agreedFinishTime))?('??:??'):($agreedFinishTime->format('H:i:s'))),
										'agreed'=>$agreedFinishTime,
										'agreedOrig'=>$origFinishTime,
										'location'=>((isset($location[$tmpTimings['locationId']]))?($location[$tmpTimings['locationId']]):(null)),
										'startTime'=>null,
										'finishTime'=>null,
										'holidays'=>$actualHolidays
									);
// error_log('********************** username:'.$username);
									$ret[$userId][date('Y-m-d', $ts)][1]=$arr1;
									$ret[$userId][date('Y-m-d', $ts)][2]=$arr2;
									$ret[$userId][date('Y-m-d', $ts)][0]=$arr0;
// error_log('3 ********************* uId:'.$uId);
									$this->getCorrectedTimes($ret[$userId][date('Y-m-d', $ts)], $domainId);
									
								} else {
// error_log('exists');
									$arr=array(
										'userId'=>$userId,
										'comment'=>'',
										'day'=>'',
										'timestamp'=>null,
										'startTime'=>null,
										'finishTime'=>null
									);
// error_log('********************** username:'.$username);
									$ret[$userId][date('Y-m-d', $ts)][1]=$arr;
									$ret[$userId][date('Y-m-d', $ts)][2]=$arr;
									$ret[$userId][date('Y-m-d', $ts)][0]['class']='PunchDayoff';
									$ret[$userId][date('Y-m-d', $ts)][0]['comment']='Dayoff';
									$ret[$userId][date('Y-m-d', $ts)][0]['userId']=$userId;
									
								}
							}
							$ts=mktime(0, 0, 0, date('n', $ts), date('j', $ts)+1, date('Y', $ts));
						}

					}
					else 
					{
// error_log('login not required');					
//						$uId=$this->getUserId($username);
						while ($ts <= $d) {
// error_log('ts:'.$ts.'='.date('Y-m-d', $ts).', d:'.$d.'='.date('Y-m-d', $d));
							$agreedStartTime=null;
							$agreedFinishTime=null;
							$overtime=0;
							$agreedOvertime=0;
							$actualHolidays=null;
							$origStartTime=null;
							$origFinishTime=null;
							$tmpTimings=$this->getTimingsForDay($userId, $ts);
							if (!isset($ret[$username][date('Y-m-d', $ts)][0]['holidays']) && isset($holidays[$userId][date('Y-m-d', $ts)]) && !isset($ret[$username][date('Y-m-d', $ts)][1]['timestamp'])) {
error_log('holidays but not signed in');
								if ($tmpTimings && count($tmpTimings)) {
									$location=$this->getLocation($tmpTimings['locationId'], true);
									$tmpTimings['startTime']=$tmpTimings['startTime']->setDate(date('Y', $ts), date('m', $ts), date('d', $ts));
									$tmpTimings['finishTime']=$tmpTimings['finishTime']->setDate(date('Y', $ts), date('m', $ts), date('d', $ts));
									$origStartTime=$tmpTimings['startTime'];
									$origFinishTime=$tmpTimings['finishTime'];
									$agreedStartTime=$origStartTime;
									$agreedFinishTime=$origFinishTime;
								}
								if (isset($holidays[$userId][date('Y-m-d', $ts)])) {
									$actualHolidays=$holidays[$userId][date('Y-m-d', $ts)];
									$this->currentStartAndFinishTime($agreedStartTime, $agreedFinishTime, $agreedOvertime, $actualHolidays, $userId);
								} else {
									error_log('No holidays on '.date('Y-m-d', $ts));
								}
								$arr0=array(
									'userId'=>$userId,
									'class'=>'PunchCorrect',
									'comment'=>'',
									'agreedStart'=>$agreedStartTime,
									'agreedFinish'=>$agreedFinishTime,
									'WorkTime'=>0,
									'Late'=>0,
									'Leave'=>0,
									'Overtime'=>$overtime,
									'OvertimeAgreed'=>$agreedOvertime,
									'holidays'=>$actualHolidays
								);
								$arr1=array(
									'userId'=>$userId,
									'comment'=>'',
									'day'=>date('D jS M', $ts),
									'timestamp'=>((isset($agreedStartTime))?(date('Y-m-d ', mktime(8, 0, 0, date('m', $ts), date('d', $ts), date('Y', $ts))).$agreedStartTime->format('H:i:s')):(null)),
									'agreed'=>$agreedStartTime,
									'agreedOrig'=>$origStartTime,
									'location'=>((isset($location[$tmpTimings['locationId']]))?($location[$tmpTimings['locationId']]):(null)),
									'startTime'=>null,
									'finishTime'=>null,
									'holidays'=>$actualHolidays
								);
								$arr2=array(
									'userId'=>$userId,
									'comment'=>'',
									'day'=>date('D jS M', $ts),
									'timestamp'=>((isset($agreedFinishTime))?(date('Y-m-d ', mktime(16, 0, 0, date('m', $ts), date('d', $ts), date('Y', $ts))).$agreedFinishTime->format('H:i:s')):(null)),
									'agreed'=>$agreedFinishTime,
									'agreedOrig'=>$origFinishTime,
									'location'=>((isset($location[$tmpTimings['locationId']]))?($location[$tmpTimings['locationId']]):(null)),
									'startTime'=>null,
									'finishTime'=>null,
									'holidays'=>$actualHolidays
								);
	
								$ret[$userId][date('Y-m-d', $ts)][1]=$arr1;
								$ret[$userId][date('Y-m-d', $ts)][2]=$arr2;
								$ret[$userId][date('Y-m-d', $ts)][0]=$arr0;
// error_log('3 ********************* uId:'.$uId);
								$this->getCorrectedTimes($ret[$userId][date('Y-m-d', $ts)], $domainId);
							}
							$ts=mktime(0, 0, 0, date('n', $ts), date('j', $ts)+1, date('Y', $ts));
						}
					}					
				}
			}
		}
if ($test) error_log('4.ret:'.print_r(array_keys($ret[$test]), true));
		unset($users);
		unset($holidays);
		unset($loginRequired);
//error_log('ret:'.print_r(array_keys($ret), true));
//		ksort($ret);

		return $ret;
	}
	
	
	public function getRequestsToAnswer($userId, $domainId, $full=true) {
		$ret=null;
		$groupId=null;
		$locationId=null;
		if ($userId) {
			$em=$this->doctrine->getManager();
			
			$user=$this->doctrine
				->getRepository('TimesheetHrBundle:User')->findOneBy(array('id'=>$userId));
			
			
			if ($user) {
				if ($user->getGroupAdmin()) {
					$groupId=$user->getGroupId();
				}
				if ($user->getLocationAdmin()) {
					$locationId=$user->getLocationId();
				}

				$qb=$em
					->createQueryBuilder()
					->select('r.id')
					->addSelect('r.typeId')
					->addSelect('r.start')
					->addSelect('r.finish')
					->addSelect('r.comment')
					->addSelect('r.createdBy')
					->addSelect('r.createdOn')
					->addSelect('u.title')
					->addSelect('u.firstName')
					->addSelect('u.lastName')
								
					->from('TimesheetHrBundle:Requests', 'r')
					->join('TimesheetHrBundle:User', 'u', 'WITH', 'r.userId=u.id')
					->where('r.accepted=0')
					->orderBy('r.start', 'ASC');

				if ($domainId) {
error_log('domainId:'.$domainId);
					$qb->andWhere('u.domainId=:dId')
						->setParameter('dId', $domainId);
				}
					
				$expr=array();
				if ($groupId) {
error_log('groupId:'.$groupId);
					$expr[]=$qb->expr()->eq('u.groupId', $groupId);
				}
				if ($locationId) {
error_log('locationId:'.$locationId);
					$expr[]=$qb->expr()->eq('u.locationId', $locationId);
				}
				if ($expr) {
					$qb->andWhere(join(' OR ', $expr));
				}
				$results=$qb->getQuery()->useResultCache(true)->getArrayResult();
				
				if (!$full) {
					return count($results);
				}
				if ($results && count($results)) {
					foreach ($results as $r) {
						$ret[]=$r;
					}
				}
					
			}
		}
		return $ret;
	}
	
	
	public function getCorrectedTimes(&$data, $domainId, $grace=null, $rounding=null) {
// error_log('getCorrectedTimes');
// error_log('data 0:'.print_r($data[0], true));
// error_log('data 2:'.print_r($data[2], true));
		$log=false;
		$ret=array(
			'SignedIn'=>'',
			'SignedOut'=>'',
			'WorkTime'=>0,
			'Late'=>0,
			'LateLess'=>0,
			'LateMore'=>0,
			'Leave'=>0,
			'LeaveLess'=>0,
			'LeaveMore'=>0,
			'LunchTime'=>'',
			'userId'=>$data[0]['userId']
		);
// error_log('userId:'.$ret['userId']);
		$lunchtimeUnpaid=$this->getConfig('lunchtimeUnpaid', $domainId);
		$lunchtimePaid=$this->getConfig('lunchtime', $domainId);
		$minTimeForLunch=60*$this->getConfig('minhoursforlunch');
		if (!$grace) {
			$grace=$this->getConfig('grace', $domainId);
		}
		if (!$rounding) {
			$rounding=$this->getConfig('rounding', $domainId);
		}
// error_log('grace:'.$grace.', rounding:'.$rounding);
		$deducted=0;
 		$timeoff=0;
			
		$i=1;
		$d=null;
		while ($i<=6) {
			if (isset($data[$i])) {
				$d=$data[$i];
			}
			$i++;
		}
		if ($d == null) {
			$d=date('Y-m-d');
		}
// error_log('data[0]:'.print_r($data[0], true));	
//		$minWorkTime=((isset($data[0]['minWorkTime']))?($data[0]['minWorkTime']):(false));

		if (isset($data[0]['holidays']) && count($data[0]['holidays'])) {
			foreach ($data[0]['holidays'] as $h) {
if ($log) error_log('holiday type:'.$h['typeId']);
				switch ($h['typeId']) {
					case 2 : {
if ($log) error_log('unpaid leave');						
						$data[0]['agreedStart']=null;
						$data[0]['agreedFinish']=null;
						$data[1]['agreed']=null;
						$data[2]['agreed']=null;
						break;
					}
					case 7 : {
if ($log) error_log('late');
if ($log) error_log('agreedStart:'.print_r($data[0]['agreedStart'], true));
						$data[0]['agreedStart']=$h['start'];
if ($log) error_log('agreedStart after:'.print_r($data[0]['agreedStart'], true));
						break;
					}
					case 8 : {
//						$log=true;
if ($log) error_log('leave early');
if ($log) error_log('agreedFinish:'.print_r($data[0]['agreedFinish'], true));
						$data[0]['agreedFinish']=$h['finish'];
if ($log) error_log('agreedFinish after:'.print_r($data[0]['agreedFinish'], true));
if ($log) error_log('agreedOrig after:'.print_r($data[2]['agreedOrig'], true));
if ($log) error_log('timestamp after:'.print_r($data[2]['timestamp'], true));
						break;
					}
					case 3 : {
if ($log) error_log('sick leave');
						$data[0]['agreedStart']=((isset($data[1]['agreedOrig']))?($data[1]['agreedOrig']->format('Y-m-d H:i:s')):(''));
						$data[1]['agreed']=((isset($data[1]['agreedOrig']))?($data[1]['agreedOrig']->format('Y-m-d H:i:s')):(''));
						$data[0]['agreedFinish']=((isset($data[2]['agreedOrig']))?($data[2]['agreedOrig']->format('Y-m-d H:i:s')):(''));
						$data[2]['agreed']=((isset($data[2]['agreedOrig']))?($data[2]['agreedOrig']->format('Y-m-d H:i:s')):(''));
						break;
					}
					case 4 : {
if ($log) error_log('time off');
						$d1=$h['start']->getTimestamp();
						$d2=$h['finish']->getTimestamp();
						$timeoff+=($d2-$d1)/60;
						break;
					}
				}
			}
		}

//		if (isset($data[1]['agreed']) && $data[1]['agreed'] && isset($data[2]['agreed']) && $data[2]['agreed']) {
		if (isset($data[1]['agreed']) && isset($data[2]['agreed'])) {
			if (!is_object($data[1]['agreed'])) {
				$data[1]['agreed']=new \DateTime($data[1]['agreed']);
			}
			if (!is_object($data[2]['agreed'])) {
				$data[2]['agreed']=new \DateTime($data[2]['agreed']);
			}
			$minWorkTime=$this->getMinWorkTime($data[0]['userId'], $data[1]['agreed']->getTimestamp());
			$d1=strtotime($data[1]['agreed']->format('H:i:s'));
			$d2=strtotime($data[2]['agreed']->format('H:i:s'));
// error_log('dates:'.$data[2]['agreed']->format('YmdHis').' == '.$data[1]['agreed']->format('YmdHis'));
			if ($data[2]['agreed']->format('His') <= $data[1]['agreed']->format('His')) {
				// finish next day
//				$data[2]['agreed']->modify('+1 day');
				$data[2]['agreed']->setDate($data[1]['agreed']->format('Y'), $data[1]['agreed']->format('m'), $data[1]['agreed']->format('d')+1);
// error_log('agreed finish +1 day');
			} else {
				$data[2]['agreed']->setDate($data[1]['agreed']->format('Y'), $data[1]['agreed']->format('m'), $data[1]['agreed']->format('d'));
				if (!isset($data[2]['agreedOrig'])) {
					$data[2]['agreedOrig']=clone $data[2]['agreed'];
				}
				$data[2]['agreedOrig']->setDate($data[1]['agreed']->format('Y'), $data[1]['agreed']->format('m'), $data[1]['agreed']->format('d'));
				
// error_log('correcting finish date');
// error_log('data[2]:'.print_r($data[2], true));
			}
			
			if (isset($data[2]['agreedOrig']) || isset($data[1]['agreedOrig'])) {
				if (isset($data[2]['agreedOrig'])) {
					$d2=$data[2]['agreedOrig']->getTimestamp();
				}	
				if (isset($data[1]['agreedOrig'])) {
					$d1=$data[1]['agreedOrig']->getTimestamp();
				}
				$ret['AgreedTimeOrig']=($d2-$d1)/60;
				if ($ret['AgreedTimeOrig'] < 0) {
					// it means finished next day
					$ret['AgreedTimeOrig']=1440+$ret['AgreedTimeOrig'];
				}
				if ($ret['AgreedTimeOrig'] >= $minTimeForLunch) {
					$ret['AgreedTimeOrig'] -= $lunchtimeUnpaid;
				}
			}
// error_log('AgreedTimeOrig:'.$ret['AgreedTimeOrig']);
			$late=0;
			$leave=0;
			if ($minWorkTime) {
error_log('Variable start time with minimum working time:'.$minWorkTime.' minutes');
				// if minimum working time is specified in the shift, that should be agreed time
				$ret['AgreedTime']=$minWorkTime;
				$ret['AgreedTimeOrig']=$minWorkTime;
				// agreed start time is latest of the real signed in time or agreed time in
// error_log('1-timestamp:'.$data[1]['timestamp']);
// error_log('2-agreed in:'.$data[1]['agreed']->format('Y-m-d H:i:s'));
// error_log('3-agreed out:'.$data[2]['agreed']->format('Y-m-d H:i:s'));
				$mStartTimestamp=max(strtotime($data[1]['timestamp']), $data[1]['agreed']->getTimestamp());
// error_log('4-latest agreed in:'.date('Y-m-d H:i:s', $data[2]['agreed']->getTimestamp() - $minWorkTime*60));
				$mStartTimestamp=min($mStartTimestamp, ($data[2]['agreed']->getTimestamp()-$minWorkTime*60));
// error_log('mStartTimestamp:'.$mStartTimestamp.', mStart datetime:'.date('Y-m-d H:i:s', $mStartTimestamp));
// error_log('data[2]:'.print_r($data[2], true));
				$data[0]['agreedStart']=new \DateTime(date('Y-m-d H:i:s', $mStartTimestamp));
				
				$mTmp2=clone $data[0]['agreedStart'];
				$mTmp2->modify('+'.$minWorkTime.' minute');

				if (strtotime($data[1]['timestamp']) > $data[0]['agreedStart']->getTimestamp()) {
					$late=(strtotime($data[1]['timestamp'])-$data[0]['agreedStart']->getTimestamp())/60;
// error_log('late:'.$late);
				}
				
//				if ($mTmp2->getTimestamp() > $data[1]['agreed']->getTimestamp()) {
//					$mTmp2->modify('+1 day');
//				}
// error_log('mTmp:'.$mTmp2->format('Y-m-d H:i:s'));
// error_log('data2:'.$data[2]['agreed']->format('Y-m-d H:i:s'));
				if ($mTmp2->getTimestamp() < $data[2]['agreed']->getTimestamp()) {
					$data[0]['agreedFinish']=$mTmp2;
				} else {
					$data[0]['agreedFinish']=clone $data[2]['agreed'];
				}
			} else {
				$ret['AgreedTime']=($data[2]['agreed']->getTimestamp()-$data[1]['agreed']->getTimestamp())/60-$timeoff;
// error_log('data2 agreed:'.$data[2]['agreed']->format('Y-m-d H:i:s'));
// error_log('data1 agreed:'.$data[1]['agreed']->format('Y-m-d H:i:s'));
// error_log('timeoff:'.$timeoff);
			}

			if ($ret['AgreedTime'] >= $minTimeForLunch) {
				$ret['AgreedTime'] -= $lunchtimeUnpaid;
				$deducted += $lunchtimeUnpaid+$timeoff;
			}
// error_log('AgreedTime:'.$ret['AgreedTime']);
			if (isset($data[1]['timestamp']) && $data[1]['timestamp'] && isset($data[2]['timestamp']) && $data[2]['timestamp']) {
				$date1=date('Y-m-d', strtotime($data[1]['timestamp']));
				if ($data[1]['agreed']->format('H:i:s') > $data[2]['agreed']->format('H:i:s')) {
					$date2=date('Y-m-d', strtotime($data[1]['timestamp']));
				} else {
					$date2=date('Y-m-d', strtotime($data[1]['timestamp']));
				}

				$overtime=0;
				$d1=max(strtotime($data[1]['timestamp']), strtotime($date1.' '.$data[1]['agreed']->format('H:i:s')));
				$d1=strtotime($data[1]['timestamp']);
				$d2=min(strtotime($data[2]['timestamp']), strtotime($date2.' '.$data[2]['agreed']->format('H:i:s')));
				$d2=strtotime($data[2]['timestamp']);
// error_log('d1:'.$d1.', agreed:'.$data[1]['agreed']->getTimestamp());
// error_log('d1:'.date('Y-m-d H:i:s', $d1).', agreed:'.$data[1]['agreed']->format('Y-m-d H:i:s'));
// error_log('d2:'.$d2.', agreed:'.$data[2]['agreed']->getTimestamp());
// error_log('d2:'.date('Y-m-d H:i:s', $d2).', agreed:'.$data[2]['agreed']->format('Y-m-d H:i:s'));
				if (date('Y-m-d H:i', $d1).':00' > $data[1]['agreed']->format('Y-m-d H:i:s')) {
// error_log('late signed in');
// error_log('minWorkTime:'.$minWorkTime);
					if ($minWorkTime) {
//						$late=(strtotime($data[0]['agreedStart']->getTimestamp())-strtotime($data[1]['agreed']->format('Y-m-d H:i:00')))/60;
//error_log('late:'.$late);						
						
//						$late=0;
					} else {
// error_log('no minWorkTime');
						$late=(strtotime(date('Y-m-d H:i:00', $d1))-strtotime($data[1]['agreed']->format('Y-m-d H:i:00')))/60;
// error_log('late:'.$late);
						// less than 5 minutes is acceptable
						// if more than 5 minutes, round up to the next 15 minutes
// error_log('grace:'.$grace);
						if ($late <= $grace) {
							$overtime-=$late;
							$late=0;
							$ret['LateLess']++;
							
						} elseif ($late % $rounding > $grace) {
							$late=$rounding*ceil($late/$rounding);
						} else {
							$late=($rounding)*((int)($late/$rounding));
						}
// error_log('final late:'.$late);
					}
					if ($late > 0) {
						$ret['LateMore']++;
					}
					$ret['Late']=$late;
					$deducted+=$late;
				} else {
					$overtime+=(strtotime($data[1]['agreed']->format('H:i:s'))-strtotime(date('H:i:s', $d1)))/60;
// error_log('late-overtime:'.$overtime);
				}

				if (date('Y-m-d H:i', $d2).':00' < $data[2]['agreed']->format('Y-m-d H:i:s')) {
					if ($minWorkTime) {
						if (!$late) {
							$leave=((strtotime(date('Y-m-d H:i:s', $d1))+$minWorkTime*60)-strtotime(date('Y-m-d H:i:s', $d2)))/60;
// error_log('leave:'.$leave);
						}
						$otTmp=($d2-$data[2]['agreed']->getTimestamp())/60;
						if ($otTmp) {
// error_log('otTmp:'.$otTmp);
							$overtime+=$otTmp;
						}
// error_log('leave:'.$leave);
// error_log('d2:'.$d2.'='.date('Y-m-d H:i:s', $d2).', agreed out:'.$data[2]['agreed']->getTimestamp().'='.$data[2]['agreed']->format('Y-m-d H:i:s'));
// error_log('otTmp:'.$otTmp);
						if ($leave < 0) {
							// if finished later than start + minWorkTime, should be overtime
							$overtime-= $leave;
						}
					} else {
						$leave=(strtotime($data[2]['agreed']->format('Y-m-d H:i:s'))-strtotime(date('Y-m-d H:i:s', $d2)))/60;
// error_log('leave:'.$leave);
					}
					// less than 5 minutes is acceptable
					// if more than 5 minutes, round up to the next 15 minutes
					if ($leave < $grace) {
						$leave=0;
						$ret['LeaveLess']++;
					} elseif ($leave % $rounding > $grace) {
						$leave=$rounding*ceil($leave/$rounding);
					} else {
						$leave=($rounding)*((int)($leave/$rounding));
					}
					
					if ($leave > 0) {
						$ret['LeaveMore']++;
					}
					$ret['Leave']=$leave;
					$deducted+=$leave;
				} else {
					$overtime+=(strtotime(date('Y-m-d H:i:s', $d2))-strtotime($data[2]['agreed']->format('Y-m-d H:i:s')))/60;
// error_log('leave-overtime:'.$overtime);
				}
				$ret['WorkTime']=$ret['AgreedTime']-$ret['Late']-$ret['Leave'];
				$ret['SignedIn']=date('H:i', $d1);
				$ret['SignedOut']=date('H:i', $d2);
				$ret['Overtime']=$overtime;
				if (isset($ret['AgreedTime']) && isset($ret['AgreedTimeOrig']) && $ret['AgreedTimeOrig'] > 0 && $ret['AgreedTime'] > $ret['AgreedTimeOrig']) {
error_log('AgreedTime:'.$ret['AgreedTime']);
error_log('AgreedTimeOrig:'.$ret['AgreedTimeOrig']);
					$ret['OvertimeAgreed']=$ret['AgreedTime']-$ret['AgreedTimeOrig'];
				}
			}
			
			if (isset($data[5]['timestamp']) && $data[5]['timestamp'] && isset($data[6]['timestamp']) && $data[6]['timestamp']) {
				$d1=$data[5]['timestamp']->getTimestamp();
				$d2=$data[6]['timestamp']->getTimestamp();
				
				$ret['LunchTime']=($d2-$d1)/60;
			} else {
				$ret['LunchTime']=$lunchtimeUnpaid;
			}
			
			if (isset($data[3]['timestamp']) && $data[3]['timestamp'] && isset($data[4]['timestamp']) && $data[4]['timestamp']) {
				$d1=$data[3]['timestamp']->getTimestamp();
				$d2=$data[4]['timestamp']->getTimestamp();
				
				$ret['BreakTime']=($d2-$d1)/60;
			}
			if (isset($data[7]['timestamp']) && $data[7]['timestamp'] && isset($data[8]['timestamp']) && $data[8]['timestamp']) {
				$d1=$data[7]['timestamp']->getTimestamp();
				$d2=$data[8]['timestamp']->getTimestamp();
				
				$ret['OtherTime']=($d2-$d1)/60;
			}
			$ret['Deducted']=$deducted;
			$ret['TotalDeductedTime']=$deducted+((isset($ret['LunchTime']) && $ret['LunchTime']>=($lunchtimeUnpaid+$lunchtimePaid))?($ret['LunchTime']-($lunchtimeUnpaid+$lunchtimePaid)):(0))+((isset($ret['BreakTime']))?($ret['BreakTime']):(0))+((isset($ret['OtherTime']))?($ret['OtherTime']):(0));
		}
		if (count($ret)) {
			foreach ($ret as $k=>$v) {
				$data[0][$k]=$v;
			}
		}
if ($log && isset($data[1]['agreedOrig'])) error_log('agreedOrig in:'.print_r($data[1]['agreedOrig'], true));
if ($log && isset($data[1]['timestamp'])) error_log('timestamp in:'.print_r($data[1]['timestamp'], true));
if ($log && isset($data[2]['agreedOrig'])) error_log('agreedOrig out:'.print_r($data[2]['agreedOrig'], true));
if ($log && isset($data[2]['timestamp'])) error_log('timestamp out:'.print_r($data[2]['timestamp'], true));
	}
	
	
	public function getAgreedTimes($userId, $timestamp, $conn) {
error_log('getAgreedTimes');
// error_log('userId:'.$userId.', date:'.date('Y-m-d', $timestamp));
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('a.locationId')
			->addSelect('l.Name as locationName')
			->addSelect('sh.startTime')
			->addSelect('sh.finishTime')
					
			->from('TimesheetHrBundle:User', 'u')
			->join('TimesheetHrBundle:Contract', 'c', 'WITH', 'c.userId=u.id')
			->join('TimesheetHrBundle:Allocation', 'a', 'WITH', 'a.userId=u.id')
			->join('TimesheetHrBundle:Shifts', 'sh', 'WITH', 'sh.id=a.shiftId')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 'l.id=a.locationId')
			->where('a.locationId=sh.locationId')
			->andWhere('u.id=:uId')
			->andWhere('c.CSD<=:date AND (`c`.`CED`>=:date OR `c`.`CED` IS NULL)')
			->setParameter('uId', $userId)
			->setParameter('data', date('Y-m-d', $timestamp))
			->setMaxResults(1);

		$results=$qb->getQuery()->useResultCache(true)->getArrayResult();
		if (isset($results) && count($results)) {
// error_log('results:'.print_r($results[0], true));
			return $results[0];
		} else {
			return array();
		}
	}

	
	public function isAllocatedShift($userId, $date) {
// error_log('isAllocatedShift');
// error_log('userId:'.$userId.', date:'.$date);
		$shifts=$this->doctrine
			->getRepository('TimesheetHrBundle:Allocation')
			->findBy(array('published'=>true, 'userId'=>$userId, 'date'=>new \DateTime($date)));
		
		return ($shifts && count($shifts));
	}
	
	public function getRequestTypes($id=null) {
		/*
		 * read the requestTypes table by name
		 */
		
		if ($id) {
			$types=$this->doctrine
				->getRepository('TimesheetHrBundle:RequestType')
				->find(array('id'=>$id));
		} else {
			$types=$this->doctrine
				->getRepository('TimesheetHrBundle:RequestType')
				->findAll();
		}
			
		return $types;
	}
		
	
	public function createTimesheetSummary($timesheet) {
error_log('createTimesheetSummary');
		$test_userId=0;
		$summary=array();
		if (isset($timesheet) && is_array($timesheet) && count($timesheet)) {
			foreach ($timesheet as $userId=>$t) {
				if (isset($t) && is_array($t) && count($t)) {
					foreach ($t as $date=>$t1) {
if ($userId == $test_userId) error_log('userId:'.$userId.', date:'.$date.', t1:'.print_r($t1[0], true));
						if (!isset($summary[$t1[0]['userId']])) {
							$summary[$t1[0]['userId']]=array(
								'UserId'=>$t1[0]['userId'],
								'pn'=>$this->getPayrolCodeById($t1[0]['userId']),
								'Breaks'=>0,
								'Additions'=>0,
								'FinalCalc'=>0,
								'TotalHours'=>0,
								'Deductions'=>0,
								'Overtime'=>0,
								'AgreedOvertime'=>0,
								'PeriodOfSicknessOff'=>0,
								'DaysOfSicknessOff'=>0,
								'DaysOfSickness'=>0,
								'DaysOfOff'=>0,
								'DaysOfHolidays'=>0,
								'Late'=>0,
								'LateLess'=>0,
								'LateMore'=>0,
								'Leave'=>0,
								'LeaveLess'=>0,
								'LeaveMore'=>0
							);
							$sickPeriods=0;
							$inSickPeriod=false;
						}

						if (isset($t1[0]['class']) && $t1[0]['class'] == 'PunchDayoff') {
if ($userId == $test_userId) error_log('dayoff');
							$summary[$t1[0]['userId']][$date]['type']='Dayoff';
							$summary[$t1[0]['userId']][$date]['typesign']='D/O';
						} elseif (isset($t1[0]['class']) && $t1[0]['class'] == 'PunchMissing') {
if ($userId == $test_userId) error_log('incorrect');
							$summary[$t1[0]['userId']][$date]['type']='Incorrect';
							$summary[$t1[0]['userId']][$date]['typesign']='???';
							$inSickPeriod=false;
//						} elseif (isset($t1[0]['class'])) {
						} else {
if ($userId == $test_userId) error_log('normal');
							if (!isset($summary[$t1[0]['userId']][$date]['type'])) {
								$summary[$t1[0]['userId']][$date]['type']='Normal';
								$inSickPeriod=false;
							}
//							if (!isset($summary[$t1[0]['userId']])) {
//								$summary[$t1[0]['userId']]['name']=$t1[0]['name'];
//							}
							if (isset($t1[0]['holidays']) && is_array($t1[0]['holidays']) && count($t1[0]['holidays'])) {
if ($userId == $test_userId) error_log('holidays:'.print_r(($t1[0]['holidays']), true));
								$style=null;
								foreach (($t1[0]['holidays']) as $h) {
									$style=false;
									if ($h['typeId'] == 1 && $h['paid'] == 1) { // Paid holiday
										$summary[$t1[0]['userId']]['DaysOfHolidays']++;
										$style='background-color: #'.$h['backgroundColor'].'; color: #'.$h['textColor'].'; border: #'.$h['borderColor'].' solid 1px;';
									}
									if ($h['typeId'] == 2 && $h['paid'] == 0) { // Unpaid leave = off
										$summary[$t1[0]['userId']][$date]['type']='Holiday';
										$summary[$t1[0]['userId']][$date]['typesign']=$h['initial'];
										$summary[$t1[0]['userId']]['DaysOfOff']++;
										$summary[$t1[0]['userId']]['DaysOfSicknessOff']++;
										if (!$inSickPeriod) {
											$inSickPeriod=true;
											$sickPeriods++;
										}
										$style='background-color: #'.$h['backgroundColor'].'; color: #'.$h['textColor'].'; border: #'.$h['borderColor'].' solid 1px;';
									}
									if ($h['typeId'] == 3) { // Sickness
										$summary[$t1[0]['userId']][$date]['type']='Holiday';
										$summary[$t1[0]['userId']][$date]['typesign']=$h['initial'];
										$summary[$t1[0]['userId']]['DaysOfSickness']++;
										$summary[$t1[0]['userId']]['DaysOfSicknessOff']++;
										if (!$inSickPeriod) {
											$inSickPeriod=true;
											$sickPeriods++;
										}
										$style='background-color: #'.$h['backgroundColor'].'; color: #'.$h['textColor'].'; border: #'.$h['borderColor'].' solid 1px;';
									} else {
										$inSickPeriod=false;
									}
									if ($h['typeId'] == 4) { // Time off
										$summary[$t1[0]['userId']]['Breaks']+=($h['finish']->getTimestamp()-$h['start']->getTimestamp())/60;
									}
									if ($h['typeId'] == 7) { // Late
										$summary[$t1[0]['userId']]['Breaks']+=($h['start']->getTimestamp()-$t1[1]['agreedOrig']->getTimestamp())/60;
									}
									if ($h['typeId'] == 8) { // Leave early
										$summary[$t1[0]['userId']]['Breaks']+=($t1[2]['agreedOrig']->getTimestamp()-$h['finish']->getTimestamp())/60;
									}
									if ($h['typeId'] == 9) { // Overtime
										$summary[$t1[0]['userId']]['Additions']+=($h['finish']->getTimestamp()-$t1[2]['agreedOrig']->getTimestamp())/60;
									}
								}
								if ($style != null) {
									$summary[$t1[0]['userId']][$date]['typestyle']=$style;
								}
							}
							if (!isset($summary[$t1[0]['userId']][$date]['times'])) {
								$summary[$t1[0]['userId']][$date]['times']=array(
									'WorkTime'=>$t1[0]['WorkTime']
								);
								$summary[$t1[0]['userId']]['FinalCalc']+=$t1[0]['WorkTime'];
								$summary[$t1[0]['userId']]['TotalHours']+=$t1[0]['WorkTime'];
							}
							if (isset($t1[0]['Late']) && $t1[0]['Late']>0) {
if ($userId == $test_userId) error_log('late:'.$t1[0]['Late']);
								$summary[$t1[0]['userId']][$date]['times']['Late']=$t1[0]['Late'];
								$summary[$t1[0]['userId']]['Deductions']+=$t1[0]['Late'];
								$summary[$t1[0]['userId']]['Late']+=$t1[0]['Late'];
							}
							if (isset($t1[0]['Leave']) && $t1[0]['Leave']>0) {
if ($userId == $test_userId) error_log('leave:'.$t1[0]['Leave']);
								$summary[$t1[0]['userId']][$date]['times']['Leave']=$t1[0]['Leave'];
								$summary[$t1[0]['userId']]['Deductions']+=$t1[0]['Leave'];
								$summary[$t1[0]['userId']]['Leave']+=$t1[0]['Leave'];
							}
							if (isset($t1[0]['Overtime']) && $t1[0]['Overtime']>0) {
if ($userId == $test_userId) error_log('overtime:'.$t1[0]['Overtime']);
								$summary[$t1[0]['userId']]['Overtime']+=$t1[0]['Overtime'];
							}
							if (isset($t1[0]['OvertimeAgreed']) && $t1[0]['OvertimeAgreed']>0) {
if ($userId == $test_userId) error_log('overtime agreed:'.$t1[0]['OvertimeAgreed']);
								$summary[$t1[0]['userId']]['AgreedOvertime']+=$t1[0]['OvertimeAgreed'];
							}
								
							if (isset($t1[0]['LateLess']) && $t1[0]['LateLess']>0) {
if ($userId == $test_userId) error_log('late less:'.$t1[0]['LateLess']);
								$summary[$t1[0]['userId']]['LateLess']+=$t1[0]['LateLess'];
							}
							if (isset($t1[0]['LateMore']) && $t1[0]['LateMore']>0) {
if ($userId == $test_userId) error_log('late more:'.$t1[0]['LateMore']);
								$summary[$t1[0]['userId']]['LateMore']+=$t1[0]['LateMore'];
							}
							if (isset($t1[0]['LeaveLess']) && $t1[0]['LeaveLess']>0) {
if ($userId == $test_userId) error_log('leave less:'.$t1[0]['LeaveLess']);
								$summary[$t1[0]['userId']]['LeaveLess']+=$t1[0]['LeaveLess'];
							}
							if (isset($t1[0]['LeaveMore']) && $t1[0]['LeaveMore']>0) {
if ($userId == $test_userId) error_log('leave more:'.$t1[0]['LeaveMore']);
								$summary[$t1[0]['userId']]['LeaveMore']+=$t1[0]['LeaveMore'];
							}
								
						}
						if ($sickPeriods) {
							$summary[$t1[0]['userId']]['PeriodOfSicknessOff']=$sickPeriods;
						}
					}
				}
			}
// error_log('summary:'.print_r($summary, true));
			usort($summary, 'self::summary_sort');
// error_log('summary:'.print_r($summary, true));
		}
		return $summary;
	}
	
	
	public function createLatenessReport($timesheet) {
error_log('createLatenessReport');
		$test_userId=0;
		$summary=array();
		if (isset($timesheet) && is_array($timesheet) && count($timesheet)) {
			foreach ($timesheet as $userId=>$t) {
				if (isset($t) && is_array($t) && count($t)) {
					foreach ($t as $date=>$t1) {
if ($userId == $test_userId) error_log('userId:'.$userId.', date:'.$date.', t1:'.print_r($t1[0], true));
						if (!isset($summary[$t1[0]['userId']])) {
							$summary[$t1[0]['userId']]=array(
								'UserId'=>$t1[0]['userId'],
								'pn'=>$this->getPayrolCodeById($t1[0]['userId']),
								'LateLess'=>0,
								'LateMore'=>0,
								'LeaveLess'=>0,
								'LeaveMore'=>0,
								'PeriodsOfSickness'=>0,
								'DaysOfSickness'=>0,
								'DaysOfOff'=>0,
								'DaysOfHolidays'=>0
							);
							$sickPeriods=0;
							$inSickPeriod=false;
						}

						if (isset($t1[0]['class']) && $t1[0]['class'] == 'PunchDayoff') {
if ($userId == $test_userId) error_log('dayoff');
							$summary[$t1[0]['userId']][$date]['type']='Dayoff';
							$summary[$t1[0]['userId']][$date]['typesign']='D/O';
						} elseif (isset($t1[0]['class']) && $t1[0]['class'] == 'PunchMissing') {
if ($userId == $test_userId) error_log('incorrect');
							$summary[$t1[0]['userId']][$date]['type']='Incorrect';
							$inSickPeriod=false;
//						} elseif (isset($t1[0]['class'])) {
						} else {
if ($userId == $test_userId) error_log('normal');
							if (!isset($summary[$t1[0]['userId']][$date]['type'])) {
								$summary[$t1[0]['userId']][$date]['type']='Normal';
								$inSickPeriod=false;
							}
							if (isset($t1[0]['holidays']) && is_array($t1[0]['holidays']) && count($t1[0]['holidays'])) {
if ($userId == $test_userId) error_log('holidays:'.print_r(($t1[0]['holidays']), true));
								foreach (($t1[0]['holidays']) as $h) {
									if ($h['typeId'] == 1 && $h['paid'] == 1) { // Paid holiday
										$summary[$t1[0]['userId']]['DaysOfHolidays']++;
									}
									if ($h['typeId'] == 2 && $h['paid'] == 0) { // Unpaid leave = off
if ($userId == $test_userId) error_log('Off');
										$summary[$t1[0]['userId']][$date]['type']='Holiday';
										$summary[$t1[0]['userId']]['DaysOfOff']++;
									}
									if ($h['typeId'] == 3) { // Sickness
if ($userId == $test_userId) error_log('Sickness');
										$summary[$t1[0]['userId']][$date]['type']='Holiday';
										$summary[$t1[0]['userId']]['DaysOfSickness']++;
										if (!$inSickPeriod) {
											$inSickPeriod=true;
											$sickPeriods++;
										}
									} else {
										$inSickPeriod=false;
									}
								}
							}
							if (isset($t1[0]['LateLess']) && $t1[0]['LateLess']>0) {
if ($userId == $test_userId) error_log('late less:'.$t1[0]['LateLess']);
								$summary[$t1[0]['userId']]['LateLess']+=$t1[0]['LateLess'];
							}
							if (isset($t1[0]['LateMore']) && $t1[0]['LateMore']>0) {
if ($userId == $test_userId) error_log('late more:'.$t1[0]['LateMore']);
								$summary[$t1[0]['userId']]['LateMore']+=$t1[0]['LateMore'];
							}
							if (isset($t1[0]['LeaveLess']) && $t1[0]['LeaveLess']>0) {
if ($userId == $test_userId) error_log('leave less:'.$t1[0]['LeaveLess']);
								$summary[$t1[0]['userId']]['LeaveLess']+=$t1[0]['LeaveLess'];
							}
							if (isset($t1[0]['LeaveMore']) && $t1[0]['LeaveMore']>0) {
if ($userId == $test_userId) error_log('leave more:'.$t1[0]['LeaveMore']);
								$summary[$t1[0]['userId']]['LeaveMore']+=$t1[0]['LeaveMore'];
							}
								
						}
						if ($sickPeriods) {
							$summary[$t1[0]['userId']]['PeriodsOfSickness']=$sickPeriods;
						}
					}
				}
			}
// error_log('summary:'.print_r($summary, true));
			usort($summary, 'self::summary_sort');
// error_log('summary:'.print_r($summary, true));
		}
		return $summary;
	}
	
	
	public function createSageReport($users) {
error_log('createSageReport');
		$ret=array();
		if (isset($users) && is_array($users) && count($users)) {
			$userRepo=$this->doctrine->getRepository('TimesheetHrBundle:User');
			$locationRepo=$this->doctrine->getRepository('TimesheetHrBundle:Location');
			$groupRepo=$this->doctrine->getRepository('TimesheetHrBundle:Groups');
//			$contractRepo=$this->doctrine->getRepository('TimesheetHrBundle:Contract');
			$jobtitleRepo=$this->doctrine->getRepository('TimesheetHrBundle:JobTitles');
			$userVisaRepo=$this->doctrine->getRepository('TimesheetHrBundle:UserVisas');
			$visaRepo=$this->doctrine->getRepository('TimesheetHrBundle:Visa');
			$countries=Intl::getRegionBundle()->getCountryNames();
			foreach ($users as $u) {
// error_log('user:'.print_r($user, true));
				$user=$userRepo->findOneBy(array('id'=>$u['id']));
				$location=$locationRepo->findOneBy(array('id'=>$user->getLocationId()));
				$group=$groupRepo->findOneBy(array('id'=>$user->getGroupId()));
				$contracts=$this->getContracts($u['id']);
				$DBSCheck=$this->getUserDBSCheck($u['id'], true);
				$fsd=null;
				$asd=null;
				$nextReview=null;
				$wdpw=0;
				$los=0;
				$awh=0;
				$jobtitle='';
				$departmentAdmins=array();
				$visaType='undefined';
				$visaExpire='undefined';
				$he=null;
				$ahe=0;
				$real_awh=0;
				$leaver=false;
				if ($contracts && count($contracts)) {
					foreach ($contracts as $c) {
						if ($fsd == null || $c['csd']->format('Ymd') < $fsd->format('Ymd')) {
							$fsd=$c['csd'];
						}
						if ($c['csd']->format('Ymd') <= date('Ymd')) {
							$asd=$c['csd'];
							$wdpw=$c['wdpw'];
							$awh=$c['awh'];
							if ($c['jobTitleId']) {
								$jtTmp=$jobtitleRepo->findOneBy(array('id'=>$c['jobTitleId']));
								if ($jtTmp) {
									$jobtitle=$jtTmp->getTitle();
								}
							}
							
							if ($c['ahe']) {
								$ahe=$c['ahe'];
							} elseif ($c['ahew']) {
								$ahe=ceil(MIN(5, $c['wdpw'])*$c['ahew']);
							} elseif ($c['wdpw']) {
								$ahe=ceil(MIN(5, $c['wdpw'])*$this->getConfig('AHEW'));
							}
// error_log('User:'.$u['id']);
							if (isset($c['ced']) && !is_null($c['ced']) && $c['ced']->format('Ymd')) {
// error_log('CED exists, min:'.min(date('Y-m-d'), $c['ced']->format('Ymd')));
								$leaver=true;
								$real_awh=$this->getCalculatedAWH($u['id'], min(date('Y-m-d'), $c['ced']->format('Ymd')));
							} else {
// error_log('Use current date');
								$real_awh=$this->getCalculatedAWH($u['id'], date('Y-m-d'));
							}
// error_log('real AWH:'.$real_awh);
						}
						// Next review date is the last Monday 1 week before contract end date
						// or 1 week before anniversary
						if ($c['csd']->format('Ymd') <= date('Ymd')) {
							if (is_null($c['ced'])) {
								$tmp=new \DateTime();
								$nextReview=clone $c['csd'];
								while ($nextReview->format('Y-m-d') < date('Y-m-d')) {
									$nextReview->modify('+1 year');
								}
								
							} elseif ($c['ced']->format('Ymd') <= date('Ymd')) {
								$tmp=clone $c['ced'];
								$nextReview=clone $c['ced'];
							}
							if (isset($tmp)) {
								$diff=$tmp->diff($c['csd']);
								$los+=$diff->format('%a');
							}
							if (isset($nextReview)) {
								$nextReview->modify('-1 week');
								if ($nextReview->format('N') != 1) {
									$nextReview->modify('- '.($nextReview->format('N') -1).' day');
								}
							}
						}
					}
					$he=$this->getHolidayEntitlement($u['id'], $contracts);
// error_log('he:'.print_r($he, true));
				}
				$phones=array();
				if ($user->getPhoneMobile()) {
					$phones[]=$user->getPhoneMobile();
				}
				if ($user->getPhoneLandline()) {
					$phones[]=$user->getPhoneLandline();
				}

				if ($user->getGroupId() || $user->getLocationId()) {
					$admins=$this->getAdminsForGroupAndLocation($user->getGroupId(), $user->getLocationId());
					if ($admins && count($admins)) {
						foreach ($admins as $a) {
							$departmentAdmins[]=trim($a['title'].' '.$a['firstName'].' '.$a['lastName']);
						}
					}
				}

				$uservisa=$userVisaRepo->findBy(array('userId'=>$u['id']));
				if ($uservisa) {
					foreach ($uservisa as $v) {
						if ($v->getStartDate()->format('Ymd') <= date('Ymd') && (is_null($v->getEndDate()) || $v->getEndDate()->format('Ymd') >= date('Ymd') || $v->getNotExpire())) {
							if ($v->getNotExpire()) {
								$visaExpire='never';
							} elseif (is_null($v->getEndDate())) {
								$visaExpire='unknown';
							} else {
								$visaExpire=$v->getEndDate()->format('d/m/Y');
							}
							if ($v->getVisaId()) {
								$visaTmp=$visaRepo->findOneBy(array('id'=>$v->getVisaId()));
								if ($visaTmp) {
									$visaType=$visaTmp->getTitle();
								}
							}
						}
					}
				}
				
				$ret[]=array(
					'id'=>$u['id'],
					'title'=>$u['title'],
					'firstName'=>$u['firstName'],
					'lastName'=>$u['lastName'],
					'payrolCode'=>$u['payrolCode'],
						
					'username'=>$user->getUsername(),
					'street'=>trim($user->getAddressLine1().' '.$user->getAddressLine2()),
					'city'=>trim($user->getAddressCity().' '.$user->getAddressCounty()),
					'postcode'=>$user->getAddressPostcode(),
					'status'=>(($user->getIsActive())?('Active'):('Inactive')),
					'employeeStatus'=>'',
					'nextReview'=>(($nextReview)?($nextReview->format('d/m/Y')):('')),
					'visaType'=>$visaType,
					'visaExpire'=>$visaExpire,
					'dbsCheck'=>($DBSCheck?'Enclosure No:'.$DBSCheck['disclosureNo'].', Issue date:'.$DBSCheck['issueDate']->format('d/m/Y'):''),
					'location'=>(($location)?($location->getName()):('')),
					'department'=>(($group)?($group->getName()):('')),
					'currentJobTitle'=>$jobtitle,
					'departmentAdmin'=>((count($departmentAdmins))?(implode(', ', $departmentAdmins)):('')),
					'fsd'=>(($fsd)?($fsd->format('d/m/Y')):('')),
					'asd'=>(($asd)?($asd->format('d/m/Y')):('')),
					'lengthOfService'=>$los,
					'weeklyWorkingHours'=>$awh,
					'workingDays'=>$wdpw,
					'initHolidays'=>(($he)?($he['initHolidays']):('')),
					'holidayEntitlementUntilDate'=>(($he)?($he['untilToday']+$he['taken']):('')),
					'annualHolidayEntitlement'=>(($he)?($he['annualholidays']):('')), // (($ahe)?($ahe):('')),
					'holidaysTaken'=>(($he)?($he['taken']):('')),
					'holidaysRemainingUntilDate'=>(($he)?($he['untilToday']):('')),
					'holidaysRemainingUntilEOC'=>(($he)?($he['untilToday']-$he['afterToday']):('')),
					'agreedHolidaysBetweenTodayAndEOC'=>(($he)?($he['afterToday']):('')),
					'agreedHolidaysAfterEOC'=>(($he)?($he['afterEOC']):('')),
					'phones'=>implode(', ', $phones),
					'companysEmail'=>$user->getEmail(),
					'personalEmail'=>$user->getEmail(),
					'nationality'=>(($user->getNationality())?($countries[$user->getNationality()]):('')),
					'ni'=>$user->getNI(),
					'nextOfKin'=>$user->getNokName(),
					'nokPhone'=>$user->getNokPhone(),
					'averageHoursPerWeek'=>$real_awh,
					'payAtEOCHours'=>(($leaver && $he)?(($he['untilToday']-$he['afterToday'])*$real_awh/7):''),
					'leaver'=>($leaver?'Yes':'No')
				);
			}
		}
		
		return $ret;
	}
	
	
	public function getAdminsForGroupAndLocation($groupId=null, $locationId=null) {
error_log('getAdminsForGroupAndLocation');
		if ($groupId==null && $locationId==null) {
			return null;
		}
		$em=$this->doctrine->getManager();
		$qb=$em->createQueryBuilder()
			->select('u.id')
			->addSelect('u.title')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->from('TimesheetHrBundle:User', 'u')
			->where('u.isActive=1');
		
		if ($groupId) {
			$expr1=$qb->expr()->andX($qb->expr()->eq('u.groupId', $groupId), $qb->expr()->eq('u.groupAdmin', 1));
		}
		if ($locationId) {
			$expr2=$qb->expr()->andX($qb->expr()->eq('u.locationId', $locationId), $qb->expr()->eq('u.locationAdmin', 1));
			if (isset($expr1)) {
				$qb->andWhere($qb->expr()->orX($expr1, $expr2));
			} else {
				$qb->andWhere($expr2);
			}
		}
		$query=$qb->getQuery();

		$results=$query->useResultCache(true)->getArrayResult();
		
		return $results;
	}
	
	
	public function summary_sort($a, $b) {
// error_log('compare:'.print_r($a['pn'], true).' = '.print_r($b['pn'], true));
		if ($a['pn'] == $b['pn']) {
			return 0;
		}
		if ($a['pn'] > $b['pn']) {
			return 1;
		} else {
			return -1;
		}
	}
	

	public function timesheet_sort($a, $b) {
		if (isset($a['userId']) && isset($b['userId'])) {
error_log('compare:'.print_r($a['userId'], true).' = '.print_r($b['userId'], true));
			if ($a['userId'] == $b['userId']) {
				return 0;
			}
			if ($a['userId'] > $b['userId']) {
				return 1;
			} else {
				return -1;
			}
		}
error_log('not compared:'.print_r($a, true).' = '.print_r($b, true));
		return 0;
	}
	
	
	public function getPayrolCodeById($userId) {

		$pn=null;
		if ($userId) {
			$em=$this->doctrine->getManager();
			$qb=$em
				->createQueryBuilder()
				->select('u.payrolCode')
				->from('TimesheetHrBundle:User', 'u')
				->where('u.id=:uId')
				->setParameter('uId', $userId);
		
			$query=$qb->getQuery();
			try {
				$result=$query->useResultCache(true)->getOneOrNullResult();
				if ($result) {
					$pn=$result['payrolCode'];
				}
			} catch (QueryException $e) {
				
			}
		}
		return $pn;
		
	}
	
	
	public function getHolidaysForMonth($userId, $startDate, $finishDate) {
error_log('getHolidaysForMonth');
		$ret=array();
		
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('r.start')
			->addSelect('r.finish')
			->addSelect('r.typeId')
			->addSelect('r.comment')
			->addSelect('r.acceptedComment')
			->addSelect('rt.name')
			->addSelect('rt.fullday')
			->addSelect('rt.paid')
			->addSelect('rt.initial')
			->addSelect('rt.textColor')
			->addSelect('rt.backgroundColor')
			->addSelect('rt.borderColor')
			->addSelect('rt.bothtime')					
			->from('TimesheetHrBundle:Requests', 'r')
			->join('TimesheetHrBundle:RequestType', 'rt', 'WITH', 'rt.id=r.typeId')
			->where('r.accepted=1')
			->andWhere('DATE(r.start)>=:date1')
			->andWhere('DATE(r.finish)<=:date2')
			->setParameter('date1', $startDate)
			->setParameter('date2', $finishDate);

		if ($userId) {
			$qb->andWhere('r.userId=:uId')
				->setParameter('uId', $userId);
		}
		$results=$qb->getQuery()->useResultCache(true)->getArrayResult();
		if (isset($results) && count($results)) {
// error_log('getHolidayForMonth:'.print_r($results, true));
			foreach ($results as $result) {
				$d1=strtotime($result['start']->format('Y-m-d H:i:s'));
				$d2=strtotime($result['finish']->format('Y-m-d H:i:s'));

				switch ($result['typeId']) {
					case 1 : {
						// Holiday
						$result['fulldayPaid']=true;
						break;
					}
					case 3 : {
error_log('sick leave');
// error_log('result:'.print_r($result, true));
						// Sick leave
						$result['fulldayPaid']=false;
						$result['start']=null;
						$result['finish']=null;
						$result['agreedStart']=null;
						$result['agreedFinish']=null;
						break;
					}
					case 7 : {
						// Late
						$result['agreedStart']=$result['start']->format('Y-m-d H:i:s');
						break;
					}
					case 8 : {
						// Leave early
						$result['agreedFinish']=$result['finish']->format('Y-m-d H:i:s');
						break;
					}
					case 9 : {
						// Overtime
						if ($result['start']->format('H:i:s') != '00:00:00') {
							$result['agreedStart']=$result['start']->format('Y-m-d H:i:s');
						}
						if ($result['finish']->format('H:i:s') != '23:59:59') {
							$result['agreedFinish']=$result['finish']->format('Y-m-d H:i:s');
						}
						break;
					}
				}
				if (date('Y-m-d', $d1) != date('Y-m-d', $d2)) {					
					while ($d1 < $d2) {
						$ret[date('Y-m-d', $d1)][]=$result;
						$d1=mktime(0, 0, 0, date('m', $d1), date('d', $d1)+1, date('Y', $d1));
					}
				} else {
					$ret[date('Y-m-d', $d1)][]=$result;
				}
			}
		}
// error_log('date: '.$startDate.'-'.$finishDate.', data:'.print_r($ret, true));
// error_log($query);
		unset($results);
		
		return $ret;
		
	}
	
	
	public function isAdmin() {
		$securityContext = $this->container->get('security.context');
		if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') && (TRUE === $securityContext->isGranted('ROLE_ADMIN'))) {
			return true;
		}
		return false;
	}
	
	
	public function isManager() {
		$securityContext = $this->container->get('security.context');
		if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') && (TRUE === $securityContext->isGranted('ROLE_ADMIN') || TRUE === $securityContext->isGranted('ROLE_MANAGER'))) {
			// Admin is manager too
			return true;
		}
		return false;
	}
	
	
	public function getTimezone($domain = null) {

		if ($domain) {
			$results=$this->doctrine
				->getRepository('TimesheetHrBundle:Companies')
				->findOneBy(array('domain'=>$domain));

			return ((isset($results) && count($results))?($results->getTimezone()):('UTC'));
		} else {
			$data=array();
			$tz=\DateTimeZone::listIdentifiers();
			foreach ($tz as $result) {
				$data[$result]=$result;
			}
			return $data;
		}
	}
	
	
	public function cleanSchedule($locationId, $timestamp) {
		$ret=array('error'=>'');
		$conn=$this->doctrine->getConnection();
		
		$date1=date('Y-m-d', mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp)));
		$date2=date('Y-m-d', mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp)-date('N', $timestamp)+7, date('Y', $timestamp)));
		
		$query='DELETE FROM `Allocation` WHERE !`published` AND `locationId`=:lId AND `date` BETWEEN :date1 AND :date2';

		$stmt=$conn->prepare($query);
		$stmt->bindValue('lId', $locationId);
		$stmt->bindValue('date1', $date1);
		$stmt->bindValue('date2', $date2);
		$stmt->execute();
		
		return $ret;
	}

	
	public function publishSchedule($locationId, $timestamp, $value=1) {
		$ret=array('error'=>'');
		$conn=$this->doctrine->getConnection();
		
		$date1=date('Y-m-d', mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp)));
		$date2=date('Y-m-d', mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp)-date('N', $timestamp)+7, date('Y', $timestamp)));
		
		$currentUser=$this->container->get('security.context')->getToken()->getUser();
		$query='UPDATE `Allocation`'.
			' SET `published`='.sprintf('%d', $value).
				(($value)?(', `publishedOn`=NOW(), `publishedBy`="'.$currentUser->getId().'"'):(', `publishedOn`=null, `publishedBy`=null')).
			' WHERE `locationId`=:lId'.
				' AND `date` BETWEEN :date1 AND :date2';

		$stmt=$conn->prepare($query);
		$stmt->bindValue('lId', $locationId);
		$stmt->bindValue('date1', $date1);
		$stmt->bindValue('date2', $date2);
		$stmt->execute();
		
		return $ret;
	}
	
	
	public function copySchedule($locationId, $timestamp, $days) {
		$ret=array('error'=>'');
		$conn=$this->doctrine->getConnection();
		
		$date1=date('Y-m-d', mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp)-date('N', $timestamp)+1+$days, date('Y', $timestamp)));
		$date2=date('Y-m-d', mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp)-date('N', $timestamp)+7+$days, date('Y', $timestamp)));
		$date3=date('Y-m-d', mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp)));
		$date4=date('Y-m-d', mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp)-date('N', $timestamp)+7, date('Y', $timestamp)));
		
		$query='DELETE FROM `Allocation` WHERE `locationId`=:lId AND `date` BETWEEN :date3 AND :date4';

		$stmt=$conn->prepare($query);
		$stmt->bindValue('lId', $locationId);
		$stmt->bindValue('date3', $date3);
		$stmt->bindValue('date4', $date4);
		$stmt->execute();

		$query='SELECT DATE_ADD(`date`, INTERVAL (DATEDIFF(:date3, :date1)) DAY) as `date`, `userId`, `locationId`, `shiftId`, NOW() FROM `Allocation` WHERE `locationId`=:lId AND `date` BETWEEN :date1 AND :date2';
		
		$stmt=$conn->prepare($query);
		$stmt->bindValue('lId', $locationId);
		$stmt->bindValue('date1', $date1);
		$stmt->bindValue('date2', $date2);
		$stmt->bindValue('date3', $date3);
		$stmt->execute();
		$results=$stmt->fetchAll();
		if ($results && count($results)) {
			foreach ($results as $result) {
				if (!$this->isHolidayOrDayoff($result['userId'], $result['date'])) {
					$query='INSERT INTO `Allocation` (`date`, `userId`, `locationId`, `shiftId`, `createdOn`) VALUES (:date, :userId, :locationId, :shiftId, NOW())';
				
					$stmt1=$conn->prepare($query);
					$stmt1->bindValue('date', $result['date']);
					$stmt1->bindValue('userId', $result['userId']);
					$stmt1->bindValue('locationId', $locationId);
					$stmt1->bindValue('shiftId', $result['shiftId']);
					$stmt1->execute();
				}
			}
			
		}
		
		
		return $ret;
	}
	

	public function fillSchedule($locationId, $timestamp) {
		/*
		 * fill the current week's schedule with preferred timings
		 */
		$ret=array('error'=>'');
		$conn=$this->doctrine->getConnection();
		
//		$date1=date('Y-m-d', mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp)));
		
		$query='SELECT `t`.`userId`,'.
			' `t`.`dayId`,'.
			' `t`.`shiftId`,'.
			' `s`.`locationId`'.			
			' FROM `Timing` `t`'.
				' JOIN `Shifts` `s` ON `s`.`id`=`t`.`shiftId` '.
				' JOIN `ShiftDays` `sd` ON `s`.`id`=`sd`.`shiftId` AND `t`.`dayId`=`sd`.`dayId`'.
			' WHERE `s`.`locationId`=:lId';
// error_log('lId:'.$locationId.', date1:'.$date1);
// error_log($query);
		$stmt=$conn->prepare($query);
		$stmt->bindValue('lId', $locationId);
		$stmt->execute();

		$results=$stmt->fetchAll();
		
		if ($results && count($results)) {
			foreach ($results as $result) {
				$d=$result['dayId'];
				if ($d == 0) {
					$d=7;
				}
				$ts=mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp)-date('N', $timestamp)+$d, date('Y', $timestamp));
				$date2=date('Y-m-d', $ts);

				if (!$this->isHolidayOrDayoff($result['userId'], $date2)) {
					try {
						$query='INSERT INTO `Allocation`'.
							' (`date`, `userId`, `locationId`, `shiftId`, `createdOn`)'.
							' VALUES (:date, :uId, :lId, :sId, NOW())';
					
						$stmt=$conn->prepare($query);
						$stmt->bindValue('date', $date2);
						$stmt->bindValue('lId', $locationId);
						$stmt->bindValue('uId', $result['userId']);
						$stmt->bindValue('sId', $result['shiftId']);
						$stmt->execute();
					} catch (\Exception $e) {
						if (strpos($e->getMessage(), '1062') === false) {
							error_log('Database error:'.$e->getMessage());
						}
					}
				}
			}
		}
		return $ret;
	}
	
	
	public function isHolidayOrDayoff($userId, $date) {
error_log('isHolidayOrDayoff');		
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('r.id')
			->from('TimesheetHrBundle:Requests', 'r')
			->join('TimesheetHrBundle:RequestType', 'rt', 'WITH', 'rt.id=r.typeId')
			->where('r.accepted=1')
			->andWhere('rt.fullday=1')
			->andWhere('r.userId=:uId')
			->andWhere(':date BETWEEN DATE(r.start) AND DATE(r.finish)')
			->setParameter('uId', $userId)
			->setParameter('date', $date);
		
		$results=$qb->getQuery()->useResultCache(true)->getArrayResult();

		return ($results && count($results));
	}
	
	
	public function getUsersFutureShifts($userId=null, $shiftId=null, $date=null, $noUserId=null, $domainId) {
		
		$conn=$this->doctrine->getConnection();
		
		$query='SELECT DISTINCT'.
			' `u`.`id`,'.
			' `u`.`username`,'.
			' `u`.`firstName`,'.
			' `u`.`lastName`'.
			' FROM `Shifts` `s`'.
				' JOIN `Allocation` `a` ON `s`.`id`=`a`.`shiftId`'.
				' JOIN `Location` `l` ON `s`.`locationId`=`l`.`id`'.
				' JOIN `Users` `u` ON `a`.`userId`=`u`.`id`'.
			' WHERE `a`.`date`>DATE(NOW())'.
				(($shiftId)?(' AND `a`.`shiftId`!=:sId'):('')).
				(($date)?(' AND `a`.`date`!=:date'):('')).
				' AND `u`.`domainId`=:dId'.
				(($userId)?(' AND `u`.`id`=:uId'):('')).
				(($noUserId)?(' AND `u`.`id`!=:nuId'):('')).
			' ORDER BY `u`.`firstName`, `u`.`lastName`, `s`.`startTime`';

		$stmt=$conn->prepare($query);
		if ($userId) {
			$stmt->bindValue('uId', $userId);
		}
		if ($noUserId) {
			$stmt->bindValue('nuId', $noUserId);
		}
		if ($date) {
			$stmt->bindValue('date', $date);
		}
		if ($shiftId) {
			$stmt->bindValue('sId', $shiftId);
		}
		$stmt->bindValue('dId', $domainId);
		$stmt->execute();
// error_log($query);		 
		return $stmt->fetchAll();
	}

	
	public function isLoginRequired($username, $domainId) {
error_log('isLoginRequired');
error_log('username:'.$username);
		$req=true;
		if ($username && strlen($username)) {
			$em=$this->doctrine->getManager();
			$qb=$em
				->createQueryBuilder()
				->select('u.loginRequired')
					
				->from('TimesheetHrBundle:User', 'u')
				->where('u.username=:uName')
				->andWhere('u.domainId=:dId')
				->setParameter('uName', $username)
				->setParameter('dId', $domainId);
			
			$results=$qb->getQuery()->useResultCache(true)->getArrayResult();
			if ($results && count($results)) {
				$result=reset($results);
				if (isset($result['loginRequired'])) {
					$req=$result['loginRequired'];
				}
			}
		
		}

		return $req;
	}

	
	public function isLoginRequiredById($userId, $domainId) {
		error_log('isLoginRequired');
		error_log('userId:'.$userId);
		$req=true;
		if ($userId && $userId>0) {
			$em=$this->doctrine->getManager();
			$qb=$em
				->createQueryBuilder()
				->select('u.loginRequired')
					
				->from('TimesheetHrBundle:User', 'u')
				->where('u.id=:uId')
				->andWhere('u.domainId=:dId')
				->setParameter('uId', $userId)
				->setParameter('dId', $domainId);
				
			$results=$qb->getQuery()->useResultCache(true)->getArrayResult();
			if ($results && count($results)) {
				$result=reset($results);
				if (isset($result['loginRequired'])) {
					$req=$result['loginRequired'];
				}
			}
	
		}
	
		return $req;
	}
	
	
	public function getTimingsForDay($userId, $timestamp) {
// error_log('getTimingsForDay, userId:'.$userId.', ts:'.$timestamp.', date:'.date('Y-m-d', $timestamp));
		if ($userId && $timestamp) {

			$em=$this->doctrine->getManager();
			$qb=$em
				->createQueryBuilder()
				->select('s')
					
				->from('TimesheetHrBundle:Shifts', 's')
				->join('TimesheetHrBundle:Allocation', 'a', 'WITH', 'a.shiftId=s.id')
				->where('a.published=1')
				->andWhere('a.userId=:uId')
				->andWhere('a.date=:date')
				->setParameter('uId', $userId)
				->setParameter('date', date('Y-m-d', $timestamp));

			$results=$qb->getQuery()->useResultCache(true)->getArrayResult();
// error_log('results:'.print_r($results, true));
			return (($results && count($results))?($results[0]):(null));
		}

		return null;
	}
	
	
	public function getFutureSwapRequests($userId = null) {

		$conn=$this->doctrine->getConnection();
		
		$query='SELECT'.
			' `sw`.*,'.
			' (SELECT `l1`.`name` FROM `Location` `l1` JOIN `Shifts` `sh1` ON `sh1`.`locationId`=`l1`.`id` WHERE `sh1`.`id`=`sw`.`shiftId1`) as `location1`,'.
			' (SELECT CONCAT(SUBSTR(`sh1`.`startTime`, 1, 5), "-", SUBSTR(`sh1`.`finishTime`, 1, 5)) FROM `Location` `l1` JOIN `Shifts` `sh1` ON `sh1`.`locationId`=`l1`.`id` WHERE `sh1`.`id`=`sw`.`shiftId1`) as `time1`,'.
			' (SELECT `l2`.`name` FROM `Location` `l2` JOIN `Shifts` `sh2` ON `sh2`.`locationId`=`l2`.`id` WHERE `sh2`.`id`=`sw`.`shiftId2`) as `location2`,'.
			' (SELECT CONCAT(SUBSTR(`sh2`.`startTime`, 1, 5), "-", SUBSTR(`sh2`.`finishTime`, 1, 5)) FROM `Location` `l2` JOIN `Shifts` `sh2` ON `sh2`.`locationId`=`l2`.`id` WHERE `sh2`.`id`=`sw`.`shiftId2`) as `time2`,'.
			' (SELECT CONCAT(`u1`.`firstName`, " ", `u1`.`lastName`) FROM `Users` `u1` WHERE `u1`.`id`=`sw`.`userId1`) as `name1`,'.
			' (SELECT CONCAT(`u2`.`firstName`, " ", `u2`.`lastName`) FROM `Users` `u2` WHERE `u2`.`id`=`sw`.`userId2`) as `name2`'.
			' FROM `SwapRequest` `sw`'.
			' WHERE `sw`.`date1`>=:date AND `sw`.`date2`>=:date'.
			(($userId)?(' AND (`sw`.`userId1`=:uId OR `sw`.`userId2`=:uId)'):('')).
			' ORDER BY `sw`.`createdOn`';
		
// error_log($query);
		$stmt=$conn->prepare($query);
		if ($userId) {
// error_log('userId:'.$userId);
			$stmt->bindValue('uId', $userId);
		}
		$stmt->bindValue('date', date('Y-m-d').' 00:00:00');
		$stmt->execute();
		return $stmt->fetchAll();
	}

	
	public function getFutureRequests($userId = null) {
error_log('getFutureRequests, userId:'.$userId);
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('r.id')
			->addSelect('r.userId')
			->addSelect('r.typeId')
			->addSelect('r.start')
			->addSelect('r.finish')
			->addSelect('r.comment')
			->addSelect('r.createdBy')
			->addSelect('r.createdOn')
			->addSelect('r.accepted')
			->addSelect('r.acceptedBy')
			->addSelect('r.acceptedOn')
			->addSelect('r.acceptedComment')
			->addSelect('rt.name as requestname')
			->addSelect('rt.comment as requestcomment')
			->addSelect('rt.fullday')
			->addSelect('rt.paid')
			->addSelect('rt.initial')
			->addSelect('rt.entitlement')
			->addSelect('u.username')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
//			->addSelect('(SELECT CONCAT(u1.firstName, \' \', u1.lastName) FROM Users u1 WHERE u1.id=r.createdBy) as createdByName')
				
			->from('TimesheetHrBundle:Requests', 'r')
			->join('TimesheetHrBundle:RequestType', 'rt', 'WITH', 'r.typeId=rt.id')
			->join('TimesheetHrBundle:User', 'u', 'WITH', 'r.userId=u.id')
			->where('r.start>=:date OR r.finish>=:date')
			->orderBy('r.start', 'ASC')
			->addOrderBy('u.firstName', 'ASC')
			->addOrderBy('u.lastName', 'ASC')
			->setParameter('date', date('Y-m-d').' 00:00:00');
		
		if ($userId) {
			$qb->andWhere('r.userId=:uId')
				->setParameter('uId', $userId);
		}
		
		$results=$qb->getQuery()->useResultCache(true)->getArrayResult();

		if ($results) {
			$cbn=array();
			$repo=$this->doctrine
				->getRepository('TimesheetHrBundle:User');
			foreach ($results as $k=>$result) {
				$results[$k]['times']=$this->createHolidayDate($result['typeId'], strtotime($result['start']), strtotime($result['finish']));
				if (!isset($cbn[$result['createdBy']])) {
					$cbnTmp=$repo->findOneBy(array('id'=>$result['createdBy']));
					$cbn[$result['createdBy']]=trim($cbnTmp->getFirstName().' '.$cbnTmp->getLastName());
				}
				$results[$k]['createdByName']=$cbn[$result['createdBy']];
			}	
		}
		
		return $results;
	}
	
	
	public function getUserId($username) {
		
		$result=$this->doctrine
			->getRepository('TimesheetHrBundle:User')
			->findOneBy(array('username'=>$username));

		if ($result) {
			return $result->getId();
		} else {
			return null;
		}
				
	}
	

	public function setTimezoneSession(&$session) {
		$request=$this->requestStack->getCurrentRequest();
		$session->set('timezone', $this->getTimezone($request->getHttpHost()));
		
		return null;
	}
	
	
	public function getDefaultHolidayCalculation($companyId) {
		$conn=$this->doctrine->getConnection();
		
		$query='SELECT'.
			' `hct`'.
			' FROM `Companies`'.
			' WHERE `id`=:cId';
		
		$stmt=$conn->prepare($query);
		$stmt->bindValue('cId', $companyId);
		$stmt->execute();
		$result=$stmt->fetch();
				
		return $result['hct'];
	}
	
	
	public function getTodayShift($userId) {

		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('s.title')
			->addSelect('s.startTime')
			->addSelect('s.finishTime')
			->from('TimesheetHrBundle:Allocation', 'a')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'a.shiftId=s.id')
			->where('a.published=1')
			->andWhere('a.userId=:userId')
			->andWhere('a.date=:date')
			->orderBy('s.startTime', 'ASC')
			->setParameter('userId', $userId)
			->setParameter('date', date('Y-m-d'));

		$query=$qb->getQuery();

		return $query->useResultCache(true)->getArrayResult();
	}
	

	
	public function getMessageHeaders($userId, $folder, $page) {
error_log('getMessageHeaders');
		$em=$this->doctrine->getManager();
		
		$qb=$em->createQueryBuilder();
		$qb->select('m.id')
			->from('TimesheetHrBundle:Messages', 'm');
		
		switch ($folder) {
			case 'Inbox' : {
				$qb->join('TimesheetHrBundle:User', 'u', 'WITH', 'm.createdBy=u.id')
					->where('m.recipient=:userId')
					->andWhere($qb->expr()->isNotNull('m.status'));
			 	break;
			}
			case 'Draft' : {
				$qb->join('TimesheetHrBundle:User', 'u', 'WITH', 'm.recipient=u.id')
					->where('m.createdBy=:userId')
					->andWhere($qb->expr()->isNull('m.status'));
			 	break;
			}
			case 'Sent' : {
				$qb->join('TimesheetHrBundle:User', 'u', 'WITH', 'm.recipient=u.id')
					->where('m.createdBy=:userId')
					->andWhere($qb->expr()->isNotNull('m.status'));
			 	break;
			}
		}
		
		$mpp=$this->getConfig('mpp');
		$qb->andWhere('m.deleted=false')
			->orderBy('m.createdOn', 'DESC')
			->setParameter('userId', $userId);
		
		$total=$qb->getQuery()->useResultCache(true)->getArrayResult();
		
		$qb->addSelect('m.subject')
			->addSelect('m.status')
			->addSelect('m.createdOn')
			->addSelect('m.readOn')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->addSelect('u.username')
			->setFirstResult($page*$mpp)
			->setMaxResults($mpp);
		$query=$qb->getQuery();
		
		return array(
			'total'=>count($total),
			'pages'=>ceil(count($total)/$mpp),
			'current'=>$page+1,
			'headers'=>$query->useResultCache(true)->getArrayResult());
	}
	
	
	public function getMessageContent($messageId) {
		$em=$this->doctrine->getManager();

		$qb=$em
			->createQueryBuilder()
			->select('m.content')
			->from('TimesheetHrBundle:Messages', 'm')
			->join('TimesheetHrBundle:User', 'u', 'WITH', 'm.createdBy=u.id')
			->where('m.id=:messageId')
			->orderBy('m.createdOn', 'DESC')
			->setParameter('messageId', $messageId);

		$query=$qb->getQuery();
		
		return $query->useResultCache(true)->getArrayResult();
	}

	
	public function getNumberOfUnreadMessages($userId) {
		$em=$this->doctrine->getManager();
		
		$qb=$em->createQueryBuilder();
		
		$qb->select('COUNT(m.id)')
			->from('TimesheetHrBundle:Messages', 'm')
			->where('m.status=0')
			->andWhere($qb->expr()->isNull('m.readOn'))
			->andWhere('m.recipient=:userId')
			->andWhere('m.deleted=false')
			->setParameter('userId', $userId);
		
		$query=$qb->getQuery();
		
		return $query->useResultCache(true)->getSingleScalarResult();
	}
	
	
	public function getNextShift($userId, $all=true) {
	
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('s.title')
			->addSelect('s.startTime')
			->addSelect('s.finishTime')
			->addSelect('a.date')
			->from('TimesheetHrBundle:Allocation', 'a')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'a.shiftId=s.id')
			->where('a.published=1')
			->andWhere('a.userId=:userId')
			->andWhere('a.date>:date')
			->orderBy('a.date', 'ASC')
			->addOrderBy('s.startTime', 'ASC')
			->setParameter('userId', $userId)
			->setParameter('date', date('Y-m-d'));
	
		$results=$qb->getQuery()->useResultCache(true)->getArrayResult();
		if ($all) {
			return $results;
		}
		if ($results && count($results)) {
			return array(reset($results));
		}		
		return null;
	}


	public function getEthnics() {
	
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('e.title')
			->addSelect('e.code')
			->from('TimesheetHrBundle:Ethnics', 'e')
			->orderBy('e.id', 'ASC');
	
		$results=$qb->getQuery()->useResultCache(true)->getArrayResult();
		if ($results && count($results)) {
			$ret=array();
			foreach ($results as $r) {
				$ret[$r['code']]=$r['title'];
			}
			return $ret;
		}
		return array();
	}
	
	
	public function getTitles() {
		$titles=array(
			'Mr'=>'Mr',
			'Mrs'=>'Mrs',
			'Miss'=>'Miss',
			'Dr'=>'Dr',
			'Prof'=>'Prof'
		);
		
		return $titles;
	}
	
	
	public function getHolidayCalculations($domainId) {
		$data=array(
			'0'=>'Company Default',
			'1'=>'Basic',
			'2'=>'Part Time Workers',
			'3'=>'Casual/Irregular Workers',
			'4'=>'Shift Workers',
//			'5'=>'Term-time Workers'
		);
		$hct=$this->getDefaultHolidayCalculation($domainId);
		if ($hct == 0) {
			$hct=$this->getConfig('hct');
		}
		$data['0'].=' ('.$data[$hct].')';
		
		return $data;
	}
	
	
	public function getCalculatedAHE($userId, $lastContract, $domainId, $timestamp=null) {
error_log('getCalculatedAHE');
		$test=null;
		
if ($userId==$test) error_log('domain:'.$domainId);
if ($userId==$test) error_log('lastContract:'.print_r($lastContract, true));
		
		$ahew=$lastContract['ahew'];
		if (!$ahew) {
			$ahew=$this->getDomainAHEW($domainId);
		}
		if (!$ahew) {
			$ahew=$this->getConfig('ahew', $domainId);
		}
if ($userId==$test) error_log('ahew:'.$ahew);
		// usually 12.07% of working hours/days/weeks/months
		$p=(52/(52-$ahew)-1)*100;
		if (!$lastContract['hct']) {
			$lastContract['hct']=$this->getDefaultHolidayCalculation($domainId);
		}
		if (!$lastContract['hct']) {
			$lastContract['hct']=$this->getConfig('hct', null);
		}
if ($userId==$test) error_log('hct:'.$lastContract['hct']);
		switch ($lastContract['hct']) {
			case 0 : {
				// Company default
				// This should not happen
				error_log('no default holiday calculation type'); 
				$ahe=0;
				break;
			}
			case 1 : {
				// Basic
				$ahe=round(5*$ahew*100)/100;
				break;
			}
			case 2 : {
				// Part time workers
				$ahe=round($p*$lastContract['wdpw']*100)/100;
				break;
			}
			case 3 : {
				// Casual/Irregular Workers
				$tmpTimestamp=($timestamp?$timestamp:time());
				$avgData=$this->getAverageWorkingHours($userId, $tmpTimestamp);
if ($userId==$test) error_log('avgData (ts:'.($timestamp?'timestamp':'current').'):'.print_r($avgData, true));
				if ($avgData['days'] > 0) {
					$ahe=($p*$avgData['hours']/100)/$avgData['dailyhours'];
if ($userId==$test) error_log('last contract:'.print_r($lastContract, true));
					
					if ($lastContract['csd']->format('md') > date('md', $tmpTimestamp)) {
						$tmp=new \DateTime(($lastContract['csd']->format('Y')-1).'-'.$lastContract['csd']->format('m-d'));
					} else {
						$tmp=clone $lastContract['csd'];
					}
if ($userId==$test) error_log('tmp:'.print_r($tmp, true));
					$tmp2=new \DateTime(date('Y-m-d H:i:s', $tmpTimestamp));
if ($userId==$test) error_log('tmp2:'.print_r($tmp2, true));
					$diff1=$tmp2->diff($tmp, true);
if ($userId==$test) error_log('days:'.print_r($diff1, true));
/*
					$tmp3=clone $tmp2;
					$tmp3->modify('+1 year');
if ($userId==$test) error_log('tmp3:'.print_r($tmp3, true));
					$diff2=$tmp2->diff($tmp3, true);
if ($userId==$test) error_log('diff2:'.print_r($diff2, true));
*/
//					$avg=round(min(5, ($avgData['days']/$diff1->days)*7));
					$avg=round(min(5, $avgData['weeklydays']));
					$ahe=round($avg*$ahew*100)/100;
if ($userId==$test) error_log('avg:'.print_r($avg, true));
				} else {
					$ahe=0;
				}
				break;
			}
			case 4 : {
				// Shift workers
				$avgData=$this->getAverageWeeklyWorkingHours($userId, ($timestamp?$timestamp:time()));
				if ($avgData['dailyhours'] > 0) {
					$ahe=($p*$avgData['weeklyhours']/100)/$avgData['dailyhours'];
				} else {
					$ahe=0;
				}
				break;
			}
			case 5 : {
				// Term-time workers
				$ahe=-1;
				break;
			}
		}
if ($userId==$test) error_log('ahe:'.$ahe);
		return $ahe;
	}

	
	public function getAverageWeeklyWorkingHours($userId, $timestamp, $withLunchtime=false, $recalculate=false) {
		// Shift workers
error_log('getAverageWeeklyWorkingHours, userId:'.$userId.', ts:'.$timestamp);
		$monday=mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp));
		
		$tmpYear=date('Y', $monday);
		$tmpWeek=date('W', $monday);
		$weeks=12;
		$maxweeks=12;
		$totalWeeks=0;
		$totalDays=0;
		$totalWhr=0;

		if (!$recalculate) {
			$em=$this->doctrine->getManager();
			$qb=$em
				->createQueryBuilder()
				->select('awwh.average')
				->addSelect('awwh.days')
				->from('TimesheetHrBundle:AWWH', 'awwh')
				->where('awwh.average>0')
				->andWhere('awwh.userId=:userId')
				->andWhere('awwh.year=:year')
				->andWhere('awwh.week<=:week')
				->orderBy('awwh.week', 'DESC')
				->setParameter('userId', $userId)
				->setParameter('year', $tmpYear)
				->setParameter('week', $tmpWeek)
				->setMaxResults($maxweeks);
		
			$query=$qb->getQuery();
			$results=$query->useResultCache(true)->getArrayResult();
			if ($results && count($results)>0) {
				foreach ($results as $r) {
					$totalDays+=$r['days'];
					$totalWhr+=$r['average'];
					$totalWeeks++;
				}
				$ret=array(
						'hours'=>$totalWhr,
						'weeklyhours'=>$totalWhr/$totalWeeks,
						'dailyhours'=>$totalWhr/$totalDays,
						'days'=>$totalDays,
						'weeks'=>$totalWeeks
				);
error_log('cached AWWH:'.print_r($ret, true));
	
				return $ret;
			}
		}
// error_log('no record');

		$domainId=$this->getDomainIdForUser($userId);
		if ($withLunchtime) {
			// if we need the full working time without deducting lunch time
			$lunchtime=0;	
		} else {
			// normal, paid working time
			$lunchtime=$this->getConfig('lunchtimeUnpaid', $domainId);
		}
		$minhoursforlunch=$this->getConfig('minhoursforlunch');
	
		while ($weeks > 0 && $maxweeks > 0) {
			$ts=mktime(0, 0, 0, date('n', $monday), date('j', $monday)-7, date('Y', $monday));
			$sunday=mktime(0, 0, 0, date('n', $ts), date('j', $ts)+6, date('Y', $ts));
			$whr=0;
			$days=0;
			$currentYear=date('Y', $ts);
			$currentWeek=date('W', $ts);
			while ($ts <= $sunday) {
				$timings=$this->getTimingsForDay($userId, $ts);
				$dhr=$this->calculateHours($timings, $domainId, $lunchtime, $minhoursforlunch, $withLunchtime);
				if ($dhr>0) {
					$whr+=$dhr;
					$totalDays++;
					$days++;
				}
				$ts=mktime(0, 0, 0, date('n', $ts), date('j', $ts)+1, date('Y', $ts));
			}
			
			$this->updateAWWH($userId, $currentYear, $currentWeek, $whr, $days);
			
			if ($whr > 0) {
				$totalWeeks++;
				$totalWhr+=$whr;
				$weeks--;
			}
			$maxweeks--;
			$monday=mktime(0, 0, 0, date('n', $monday), date('j', $monday)-7, date('Y', $monday));
		}
		if ($totalWeeks > 0) {
			$ret=array(
				'hours'=>$totalWhr,
				'weeklyhours'=>$totalWhr/$totalWeeks,
				'dailyhours'=>$totalWhr/$totalDays,
				'days'=>$totalDays,
				'weeks'=>$totalWeeks
			);
		} else {
			$ret=array(
				'hours'=>0,
				'weeklyhours'=>0,
				'dailyhours'=>0,
				'days'=>0,
				'weeks'=>0
			);
		}
error_log('calculated:'.print_r($ret, true));
		return $ret;
	}
	
	
	public function updateAWWH($userId, $year, $week, $average, $days) {
		$em=$this->doctrine->getManager();
		$awwh=$this->doctrine
			->getRepository('TimesheetHrBundle:AWWH')
			->findOneBy(array('userId'=>$userId, 'year'=>$year, 'week'=>$week));
		
		if ($awwh) {
			$new=false;
		} else {
			$new=true;
			$awwh=new AWWH();
			$awwh->setUserId($userId);
			$awwh->setYear($year);
			$awwh->setWeek($week);
		}
		
		$awwh->setAverage($average);
		$awwh->setDays($days);
		
		if ($new) {
			$em->persist($awwh);
		}
		$em->flush($awwh);
	}
	
	public function getAverageWorkingHours($userId, $timestamp) {
		// Irregular workers
error_log('getAverageWorkingHours');

		$test=null;
		$today=mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp), date('Y', $timestamp));
		$ts12weeks=mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-(12*7), date('Y', $timestamp));
		$contracts=$this->getContracts($userId);
		$csd=null;
		$weeks=array();
		if ($contracts && count($contracts)) {
if ($userId==$test) error_log('contracts:'.print_r($contracts, true));
			foreach ($contracts as $contract) {
				if ($csd==null || $contract['csd']<date('Y-m-d', $csd)) {
					$csd=$contract['csd']->getTimestamp();
				}
			}
		}
if ($userId==$test) error_log('csd:'.print_r($csd, true));
		if ($csd) {
			$ts_csd=mktime(0, 0, 0, date('n', $csd), date('j', $csd), date('Y', $csd));
			
			$ts=max($ts_csd, $ts12weeks);
			
			$totalDays=0;
			$totalWhr=0;
			$domainId=$this->getDomainIdForUser($userId);
			$lunchtime=$this->getConfig('lunchtimeUnpaid', $domainId);
			$minhoursforlunch=$this->getConfig('minhoursforlunch');

			while ($ts < $today) {
				$timings=$this->getTimingsForDay($userId, $ts);
				$dhr=$this->calculateHours($timings, $domainId, $lunchtime, $minhoursforlunch);
				if ($dhr>0) {
					$totalWhr+=$dhr;
					$totalDays++;

					if (!isset($weeks[date('YW', $ts)])) {
						$weeks[date('YW', $ts)]=0;
					}
					$weeks[date('YW', $ts)]+=$dhr;
				}
				$ts=mktime(0, 0, 0, date('n', $ts), date('j', $ts)+1, date('Y', $ts));
			}
		}
		if ($totalDays > 0) {
			$ret=array(
				'hours'=>$totalWhr,
				'dailyhours'=>$totalWhr/$totalDays,
				'weeklyhours'=>array_sum($weeks)/count($weeks),
				'weeklydays'=>array_sum($weeks)/count($weeks)/7,
				'days'=>$totalDays
			);
		} else {
			$ret=array(
				'hours'=>0,
				'dailyhours'=>0,
				'weeklyhours'=>0,
				'weeklydays'=>0,
				'days'=>0
			);
		}
		return $ret;
	}
	
	
	public function getDomainIdForUser($userId) {
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('u.domainId')
			->from('TimesheetHrBundle:User', 'u')
			->where('u.id=:uId')
			->setParameter('uId', $userId);
		
		$query=$qb->getQuery();
		$result=$query->useResultCache(true)->getArrayResult();

		if ($result && count($result)==1) {
			return $result[0]['domainId'];
		} else {
			return 0;
		}
		
	}
	

	public function getRecipients($domainId, $userId) {
		$em=$this->doctrine->getManager();
		$ret=array();

		$qb=$em
			->createQueryBuilder()
			->select('u.id')
			->addSelect('u.username')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->from('TimesheetHrBundle:User', 'u')
			->where('u.domainId=:dId')
			->andWhere('u.id<>:uId')
			->orderBy('u.firstName', 'ASC')
			->setParameter('uId', $userId)
			->setParameter('dId', $domainId);
		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();

		if ($results && count($results)) {
			foreach ($results as $result) {
				$ret[$result['id']]=trim($result['firstName'].' '.$result['lastName']).' ('.$result['username'].')';
			}
		}

		return $ret;
		
	}
	
	
	public function createMessageView($message, $folder=null, $raw=false) {
		$sender=$this->doctrine
			->getRepository('TimesheetHrBundle:User')
			->findOneBy(array('id'=>$message->getCreatedBy()));
		
		$recipient=$this->doctrine
			->getRepository('TimesheetHrBundle:User')
			->findOneBy(array('id'=>$message->getRecipient()));
		
		$msgView=$this->renderView('TimesheetHrBundle:Internal:message.html.twig', array(
			'sender'	=> $sender,
			'recipient'	=> $recipient,
			'message'	=> $message,
			'folder'	=> $folder,
			'raw'		=> $raw
		));
		
		return $msgView;
	}

	
	public function renderView($view, array $parameters=array()) {
		return $this->container->get('templating')->render($view, $parameters);
	}
	
	
	public function createUniqueId() {

		$link='';
		for ($i=0; $i < $this->link_length; $i++) {
			$link.=chr(ord('A')+rand(0,25));
		}
		
		return $link;
	}
	
	
	public function getTimesheetChecked($userId, $date) {
		$em=$this->doctrine->getManager();
				
		$qb=$em
			->createQueryBuilder()
			->select('tc.checkedBy')
			->addSelect('tc.checkedOn')
			->addSelect('tc.comment')
			->addSelect('u.username')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->addSelect('u.title')
			->from('TimesheetHrBundle:TimesheetCheck', 'tc')
			->join('TimesheetHrBundle:User', 'u', 'WITH', 'tc.checkedBy=u.id')
			->where('tc.userId=:uId')
			->andWhere('tc.date=:date')
			->setMaxResults(1)
			->setParameter('uId', $userId)
			->setParameter('date', $date);
		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();

		return ((isset($results[0]))?($results[0]):(null));
	}

	
	public function getReligions() {
		$em=$this->doctrine->getManager();
				
		$qb=$em
			->createQueryBuilder()
			->select('r.id')
			->addSelect('r.name')
			->from('TimesheetHrBundle:Religions', 'r')
			->orderBy('r.name', 'ASC');
		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
		$religions=array();
		if ($results && count($results)) {
			foreach ($results as $result) {
				$religions[$result['id']]=$result['name'];
			}
		}

		return $religions;
	}
	
	
	public function getJobTitles($domainId=null) {
		$em=$this->doctrine->getManager();
				
		$qb=$em
			->createQueryBuilder()
			->select('jt.id')
			->addSelect('jt.title')
			->from('TimesheetHrBundle:JobTitles', 'jt')
			->orderBy('jt.title', 'ASC');
	
		if ($domainId) {
			$qb->andWhere('jt.domainId=:dId')
				->setParameter('dId', $domainId);
		}
			
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
		$jobTitles=array();
		if ($results && count($results)) {
			foreach ($results as $result) {
				$jobTitles[$result['id']]=$result['title'];
			}
		}

		return $jobTitles;
	}
	
	
	public function getMaritalStatuses() {
		return array(
			'Single',
			'Married/Civil Partner',
			'Divorced',
			'Widowed',
			'Separated',
			'Not disclosed'
		);
	}
	
	
	public function setTimesheetChecked($userId, $date, $checkedBy, $comment) {

		$checked=$this->getTimesheetChecked($userId, $date->format('Y-m-d'));
		
		$tc=new TimesheetCheck();
		
		$tc->setUserId($userId);
		$tc->setDate($date);
		$tc->setCheckedBy($checkedBy);
		$tc->setCheckedOn(new \DateTime('now'));
		$tc->setComment($comment);
		
		$em=$this->doctrine->getManager();
		
		if ($checked && count($checked)) {
			error_log('update timesheet check');
		} else {
			error_log('save timesheet check');
			$em->persist($tc);
		}
		$em->flush($tc);
		
		return (($tc->getId())?(true):(false));
	}

	
	public function getProblems($domainId, $userId=null, $returnText=true) {
		
		$problems=array();
		$noContract=array();
		$notSignedIn=array();
		$noVisaStatus=array();
		$visaExpires=array();
		$visaExpired=array();
		
		$em=$this->doctrine->getManager();
				
		$qb=$em
			->createQueryBuilder()
			->select('u.title')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->from('TimesheetHrBundle:User', 'u')
			->leftJoin('TimesheetHrBundle:Contract', 'c', 'WITH', 'c.userId=u.id AND c.csd<=:date AND (c.ced is null OR c.ced>:date)')
			->where('u.isActive=1')
			->andWhere('u.domainId=:dId')
			->andWhere('c.id is null')
			->groupBy('u.id')
			->orderBy('u.firstName', 'ASC')
			->addOrderBy('u.lastName', 'ASC')
			->setParameter('dId', $domainId)
			->setParameter('date', date('Y-m-d'));
		
		if ($userId) {
			$qb->andWhere('u.id=:uId')
				->setParameter('uId', $userId);
		}
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
		
		if ($results && count($results)) {
			foreach ($results as $result) {
				$noContract[]=trim($result['title'].' '.$result['firstName'].' '.$result['lastName']);
			}
		}

		$qb=$em
			->createQueryBuilder()
			->select('u.title')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->addSelect('CONCAT_WS(\'-\', SUBSTRING(s.startTime, 1, 5), SUBSTRING(s.finishTime, 1, 5)) as time')
			->addSelect('l.name as location')
			->from('TimesheetHrBundle:User', 'u')
			->join('TimesheetHrBundle:Allocation', 'a', 'WITH', 'a.userId=u.id AND a.date=:date')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 'a.locationId=l.id')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'a.shiftId=s.id')
			->leftJoin('TimesheetHrBundle:Info', 'i', 'WITH', 'i.userId=u.id AND DATE(i.timestamp)=:date')
			->where('u.isActive=1')
			->andWhere('a.published=1')
			->andWhere('u.loginRequired=1')
			->andWhere('u.domainId=:dId')
			->andWhere('i.id is null')
			->groupBy('u.id')
			->orderBy('u.firstName', 'ASC')
			->addOrderBy('u.lastName', 'ASC')
			->setParameter('dId', $domainId)
			->setParameter('date', date('Y-m-d'));

		if ($userId) {
			$qb->andWhere('u.id=:uId')
				->setParameter('uId', $userId);
		}
				
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
		
		if ($results && count($results)) {
			foreach ($results as $result) {
				$notSignedIn[]=trim($result['title'].' '.$result['firstName'].' '.$result['lastName']).' ('.$result['location'].' '.$result['time'].')';
			}
		}

		$qb=$em
			->createQueryBuilder()
			->select('u.title')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->from('TimesheetHrBundle:User', 'u')
			->leftJoin('TimesheetHrBundle:UserVisas', 'uv', 'WITH', 'uv.userId=u.id')
			->where('u.isActive=1')
			->andWhere('uv.id is null')
			->groupBy('u.id')
			->orderBy('u.firstName', 'ASC')
			->addOrderBy('u.lastName', 'ASC');
		
		if ($domainId) {
			$qb->andWhere('u.domainId=:dId')
				->setParameter('dId', $domainId);
		}			
		if ($userId) {
			$qb->andWhere('u.id=:uId')
				->setParameter('uId', $userId);
		}
		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
		
		if ($results && count($results)) {
			foreach ($results as $result) {
				$noVisaStatus[]=trim($result['title'].' '.$result['firstName'].' '.$result['lastName']);
			}
		}

		$qb_lastvisa=$em
			->createQueryBuilder()
			->select('uv1.id')
			->from('TimesheetHrBundle:UserVisas', 'uv1')
			->where('uv1.userId=uv.userId')
			->groupBy('uv1.userId')
			->orderBy('uv1.startDate', 'DESC')
			->setMaxResults(1);

		$qb=$em
			->createQueryBuilder()
			->select('u.title')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->addSelect('uv.endDate')
			->addSelect('('.$qb_lastvisa->getDql().') as uv2')
			->addSelect('uv.notExpire')
			->addSelect('uv.endDate')
			->from('TimesheetHrBundle:UserVisas', 'uv')
			->leftJoin('TimesheetHrBundle:User', 'u', 'WITH', 'uv.userId=u.id')
			->where('u.isActive=1')
			->groupBy('u.id');
		if ($domainId) {
			$qb->andWhere('u.domainId=:dId')
				->setParameter('dId', $domainId);
		}
		if ($userId) {
			$qb->andWhere('u.id=:uId')
				->setParameter('uId', $userId);
		}
		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
			
		if ($results && count($results)) {
			foreach ($results as $result) {
				if ($result['notExpire'] == 0 && $result['endDate']->format('Y-m-d') <= date('Y-m-d')) {
					$visaExpired[]=trim($result['title'].' '.$result['firstName'].' '.$result['lastName']).' ('.$result['endDate']->format('d/m/Y').')';
				}
			}
		}
				
		$qb=$em
			->createQueryBuilder()
			->select('u.title')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->addSelect('uv.endDate')
			->from('TimesheetHrBundle:User', 'u')
			->leftJoin('TimesheetHrBundle:UserVisas', 'uv', 'WITH', 'uv.userId=u.id')
			->where('u.isActive=1')
			->andWhere('uv.id is not null')
			->andWhere('uv.notExpire=0')
			->andWhere('uv.endDate BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), 1, \'MONTH\')')
			->groupBy('u.id');
		
		if ($domainId) {
			$qb->andWhere('u.domainId=:dId')
				->setParameter('dId', $domainId);
		}			
		if ($userId) {
			$qb->andWhere('u.id=:uId')
				->setParameter('uId', $userId);
		}
		
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();
		
		if ($results && count($results)) {
			foreach ($results as $result) {
				$visaExpires[]=trim($result['title'].' '.$result['firstName'].' '.$result['lastName']).' ('.$result['endDate']->format('d/m/Y').')';
			}
		}
		
		if (count($noContract)) {
			if ($returnText) {
				$problems['noContract']='No contract ('.count($noContract).'): '.implode(', ', $noContract);
			} else {
				$problems['noContract']=$noContract;
			}
		}
		if (count($noVisaStatus)) {
			if ($returnText) {
				$problems['noVisa']='No visa status entered ('.count($noVisaStatus).'): '.implode(', ', $noVisaStatus);
			} else {
				$problems['noVisa']=$noVisaStatus;
			}
		}
		if (count($visaExpired)) {
			if ($returnText) {
				$problems['visaExpired']='Visa expired ('.count($visaExpired).'): '.implode(', ', $visaExpired);
			} else {
				$problems['visaExpired']=$visaExpired;
			}
		}
		if (count($visaExpires)) {
			if ($returnText) {
				$problems['visaExpires']='Visa expire in 1 month ('.count($visaExpires).'): '.implode(', ', $visaExpires);
			} else {
				$problems['visaExpires']=$visaExpires;
			}
		}
		if (count($notSignedIn)) {
			if ($returnText) {
				$problems['notSigned']='Not signed in Today ('.count($notSignedIn).'): '.implode(', ', $notSignedIn);
			} else {
				$problems['notSigned']=$notSignedIn;
			}
		}
		
		return $problems;
	}
	
	
	public function getPageTitle($title) {

		$request=$this->requestStack->getCurrentRequest();
		
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('c.companyname')
			->from('TimesheetHrBundle:Companies', 'c')
			->where('c.id=:id')
			->setParameter('id', $this->getDomainId($request->getHttpHost()))
			->setMaxResults(1);

		$query=$qb->getQuery();
		try {
			$result=$query->useResultCache(true)->getOneOrNullResult();
			if ($result) {
				return $title.' - '.$result['companyname'];
			}
		} catch (QueryException $e) {
			
		}

		return $title;
	}
	
	
	public function isDailyScheduleProblem($locationId, $date) {
error_log('isDailyScheduleProblem');
//error_log('locationId:'.$locationId.', date:'.$date);
		$required=$this->getCurrentlyRequiredStaff($locationId, $date);
		$allocated=$this->getCurrentlyAllocatedStaff($locationId, $date);
		$requiredQualifications=$this->getCurrentlyRequiredQualifications($locationId, $date);
		$allocatedQualifications=$this->getCurrentlyAllocatedQualifications($locationId, $date);
//error_log('allocated:'.print_r($allocated, true));
// error_log('required qual:'.print_r($requiredQualifications, true));
// error_log('allocated qual:'.print_r($allocatedQualifications, true));

		if ($allocated && count($allocated)) {
			$users=array();
			foreach ($allocated as $a) {
				if (isset($users[$a['userId']])) {
//					$users[$a['userId']]++;
					return true;
error_log('multiple shift allocation');
				} else {
					$users[$a['userId']]=1;
				}
			}
		}
		$noReq=true;
		if ($required && count($required)) {
			$noReq=false;
			if ($allocated && count($allocated)) {
				foreach ($required as $r) {
					if ($r['numberOfStaff'] > 0) {
						$groupId=$r['groupId'];
						$shiftId=$r['shiftId'];
						$requiredNumber=$r['numberOfStaff'];
						$current=0;
						foreach ($allocated as $a) {
							if ($a['groupId'] == $groupId && $a['shiftId'] == $shiftId) {
								$current++;
							}
						}
						if ($requiredNumber > $current) {
							return true;
						}
					} else {
						return true;
					}					
				}
			} else {
				return true;
			}
		}
		if ($requiredQualifications && count($requiredQualifications)) {
			$noReq=false;
			if ($allocatedQualifications && count($allocatedQualifications)) {
				foreach ($requiredQualifications as $rq) {
					if ($rq['numberOfStaff'] > 0) {
						$qualificationId=$rq['qualificationId'];
						$shiftId=$rq['shiftId'];
						$minimumLevel=$rq['rank'];
						$requiredNumber=$rq['numberOfStaff'];
						$current=0;
						foreach ($allocatedQualifications as $aq) {
							if ($aq['qualificationId'] == $qualificationId && $aq['shiftId'] == $shiftId && ($aq['rank']==null || $aq['rank']>=$minimumLevel)) {
								$current++;
							}
						}
						if ($requiredNumber > $current) {
							return true;
						}
					} else {
						return true;
					}
				}
			} else {
				return true;
			}	
		}
		if ($noReq) {
// error_log('null');
			return null;
		} else {
// error_log('false');
			return false;
		}
	}
	
	public function currentStartAndFinishTime(&$startTime, &$finishTime, &$agreedOvertime, $holidays, $userId) {
error_log('currentStartAndFinishTime');
// error_log('agreedOvertime:'.$agreedOvertime);
		if ($holidays!=null && is_array($holidays) && count($holidays)) {
			foreach ($holidays as $h) {
				if (isset($h['typeId'])) {
					switch ($h['typeId']) {
						case 1 : {
error_log('holiday');
							if (is_null($startTime)) {
								$startTime=$h['start'];
								$tmp=clone $h['start'];
								$awwh=$this->getAverageWeeklyWorkingHours($userId, $tmp->getTimestamp(), true, true);
								$tmp->modify('+'.ceil($awwh['dailyhours']).' hour');
								$finishTime=$tmp;
							} else {
								$startTime=$h['start'];
								$finishTime=$h['finish'];
							}
							break;
						}
						case 2 : {
error_log('unpaid leave');
							$startTime=$h['start'];
							$finishTime=$h['start'];
							break;
						}
						case 3 : {
error_log('sick leave');
							$startTime=$h['start'];
							$finishTime=$h['start'];
							break;
						}
						case 7 : {
error_log('late');
							if ($h['start']->format('HHii') > 0) {
								$startTime=$h['start'];
							}
							break;
						}
						case 8 : {
error_log('leave early');
							if ($h['finish']->format('HHii') > 0) {
								$finishTime=$h['finish'];
							}
							break;
						}
						case 9 : {
error_log('overtime');
							if ($h['start']->format('HHii') > 0) {
								$tmp=$h['start']->diff($startTime);
								$agreedOvertime+=60*$tmp->h+$tmp->i;
								$startTime=$h['start'];
							}
							if ($h['finish']->format('HHii') > 0) {
								$tmp=$finishTime->diff($h['finish']);
								$agreedOvertime+=60*$tmp->h+$tmp->i;
								$finishTime=$h['finish'];
							}
							break;
						}
					}
				}
			}
		}

		return true;
	}
	
	
	public function changeDateFormat($date, $format) {
// error_log('changeDateFormat');
		$ret='';
		switch ($format) {
			case 'dd/mm' : {
				if (is_string($date)) {
// error_log('string');
					$tmp=preg_split('/(\-.)+/', $date);
// error_log('tmp:'.print_r($tmp, true));
					if (count($tmp) == 2) {
						$ret=sprintf('%02d/%02d', $tmp[1], $tmp[0]);
					}
				}
				if (is_object($date)) {
// error_log('object');
					$ret=$date->format('dd/mm');
				}
				break;
			}
		}
// error_log('ret:'.$ret);
		return $ret;
	}
	
	
	public function getExportTemplateNames($domainId = null) {

		$em=$this->doctrine->getManager();
		
		$qb=$em
			->createQueryBuilder()
			->select('et')
			->from('TimesheetHrBundle:ExportTemplates', 'et')
			->orderBy('et.name');
		
		if ($domainId) {
			$qb->andWhere('et.domainId=:dId')
				->setParameter('dId', $domainId);
		}
		
		$query=$qb->getQuery();
		$templates=$query->useResultCache(true)->getArrayResult();
		
		return $templates;
	}
	
	
	public function isModuleAvailable($moduleId, $domainId) {
		$em=$this->doctrine->getManager();
		
		$qb=$em
			->createQueryBuilder()
			->select('mc.id')
			->from('TimesheetHrBundle:ModulesCompanies', 'mc')
			->where('mc.moduleId=:mId')
			->andWhere('mc.domainId=:dId')
			->setParameter('mId', $moduleId)
			->setParameter('dId', $domainId);
		
		$result=$qb->getQuery()->useResultCache(true)->getArrayResult();
		
		return ($result && count($result));
		
	}

	
	public function getAvailablePages() {
		return array(
			0=>'Timesheet'
		);
	}
	
	
	public function getAvailableFormats() {
		return array('pdf'=>'PDF', 'csv'=>'CSV for Excel');
	}
	

	public function getMinWorkTime($userId, $timestamp) {
// error_log('getMinWorkTime');
		$minWorkTime=0;

		$em=$this->doctrine->getManager();
		
		$qb=$em
			->createQueryBuilder()
			->select('s.minWorkTime')
			->from('TimesheetHrBundle:Shifts', 's')
			->join('TimesheetHrBundle:Allocation', 'a', 'WITH', 'a.shiftId=s.id')
			->where('a.userId=:uId')
			->andWhere('a.date=:date')
			->setParameter('uId', $userId)
			->setParameter('date', date('Y-m-d', $timestamp))
			->setMaxResults(1);

		try {
			$result=$qb->getQuery()->useResultCache(true)->getOneOrNullResult(); //SingleResult();
			if ($result) {
				$minWorkTime=$result['minWorkTime'];
			}
		} catch (QueryException $e) {
			error_log('query exception:'.$e->getMessage());
		}
//		if ($minWorkTime) error_log('min:'.$minWorkTime);

		return $minWorkTime;
	}


	public function getFPReaders($domainId = null) {

		$em=$this->doctrine->getManager();
		
		$qb=$em
			->createQueryBuilder()
			->select('fpr.id')
			->addSelect('fpr.deviceId')
			->addSelect('fpr.deviceName')
			->addSelect('fpr.ipAddress')
			->addSelect('fpr.port')
			->addSelect('fpr.platform')
			->addSelect('fpr.version')
			->addSelect('fpr.serialnumber')
			->addSelect('fpr.status')
			->addSelect('fpr.lastAccess')
			->addSelect('fpr.password')
			->addSelect('fpr.comment')
			->addSelect('fpr.locationId')
			->addSelect('c.companyname')
			->from('TimesheetHrBundle:FPReaders', 'fpr')
			->leftJoin('TimesheetHrBundle:Companies', 'c', 'WITH', 'c.id=fpr.domainId')
			->orderBy('fpr.id');
		
		if ($domainId) {
			$qb->andWhere('fpr.domainId=:dId')
				->setParameter('dId', $domainId);
		}
		
		$query=$qb->getQuery();
		$fp=$query->useResultCache(true)->getArrayResult();
		
		return $fp;
	}
	

	public function getAvailableFPReaders($domainId = null) {

		$em=$this->doctrine->getManager();
		
		$qb=$em
			->createQueryBuilder()
			->select('COUNT(fpr.id)')
			->from('TimesheetHrBundle:FPReaders', 'fpr')
			->where('fpr.status=1');
		
		if ($domainId) {
			$qb->andWhere('fpr.domainId=:dId')
				->setParameter('dId', $domainId);
		}
		
		$query=$qb->getQuery();
		$fp=$query->useResultCache(true)->getSingleScalarResult();
		
		return $fp;
	}
	
	
	public function updateAttendanceFromReader() {
error_log('updateAttendanceFromReader');

		$updated=array();
		$fpreaders=$this->getFPReaders();
		foreach ($fpreaders as $fpreader) {
			if ($fpreader['status']) {
// error_log('fpreader:'.print_r($fpreader, true));
				$zk = new ZKLib($fpreader['ipAddress'], $fpreader['port']);
				$ret = $zk->connect();
				sleep(1);
				if ( $ret ) {
					$updated[$fpreader['id']]=$fpreader+array('updated'=>0, 'total'=>0, 'attendance'=>array());
					$zk->disableDevice();
					sleep(1);
				
					$readerAttendance=$zk->getAttendance();
					
					if ($readerAttendance && is_array($readerAttendance) && count($readerAttendance)) {
						error_log('No of attendance record:'.count($readerAttendance).' in reader '.$fpreader['id']);
						$em=$this->doctrine->getManager();
						
						$updated[$fpreader['id']]['total']=count($readerAttendance);
						foreach ($readerAttendance as $data) {
							
							$att=new FPReaderAttendance();
							$att->setReaderId($fpreader['id']);
							$att->setUserId($data[1]);
							$att->setStatus(($data[2]==14)?'Check out':'Check in');
//							$att->setStatus($data[2]);
							$att->setTimestamp(new \DateTime($data[3]));
		
							$error=false;
							try {
								$em->persist($att);
								$em->flush($att);
							} catch (\Exception $e) {
								$error=true;
								if (strpos($e->getMessage(), '1062') === false) {
									if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
										if (!$em->isOpen()) {
											$em = $em->create($em->getConnection(), $em->getConfiguration());
										}
										if ($em->isOpen()) {
		//									error_log('Entity manager is reopened');
										} else {
											error_log('Entity manager is closed');
										}
									} else {
										error_log('Database error:'.$e->getMessage());
									}
								} else {
									error_log('Data already stored');
								}
							}
							if (!$error) {
								$updated[$fpreader['id']]['updated']++;
								$updated[$fpreader['id']]['attendance'][]=array('userId'=>$data[1], 'status'=>$data[2], 'timestamp'=>$data[3]);
							}
						}
//						$zk->clearAttendance(); // temporarily leave all the attendance in the reader
					}
					$zk->enableDevice();
					sleep(1);
					$zk->disconnect();
				} else {
					error_log('Reader '.$fpreader['id'].' ('.$fpreader['ipAddress'].':'.$fpreader['port'].') not connected');
				}
			}
		}
			
		return $updated;
	}
	
	
	public function fpEnrol($user) {
		$fpReaders=$this->getFPReaders($user->getDomainId());
		$em=$this->doctrine->getManager();
		$success=false;
		$uId=$user->getId();
		$name=trim($user->getTitle().' '.$user->getFirstName().' '.$user->getLastName());
		if ($fpReaders) {
			foreach ($fpReaders as $fpr) {
				if ($fpr['status']) {
					try {
						$tad_factory=new TADFactory(['ip'=>$fpr['ipAddress'], 'udp_port'=>$fpr['port'], 'com_key'=>$fpr['password'], 'connection_timeout'=>10]);
						$tad=$tad_factory->get_instance();
						
						if ($tad) {
							if ($tad->is_alive()) {
								$result=$tad->set_user_info(array('pin'=>$uId, 'name'=>$name, 'password'=>null, 'privilege'=>Constants::LEVEL_USER, 'card'=>0, 'pin2'=>$uId, 'group'=>1));
								$tmp=$result->get_response(['format'=>'array']);
								if ($tmp['Row']['Result'] == 1) {
									error_log('Enrolled successfully');
									$success=true;
									
									$fpUser=$tad->get_user_info(array('pin'=>$uId));
								} else {
									error_log('Problem with enrolling');
								}
							} else {
								error_log('Reader is not alive');
							}
						} else {
							error_log('Reader not connected');
						}
						
					} catch (\Exception $e) {
						$success=false;
						error_log('Error in saving user ('.$name.') in reader:'.$e->getMessage());
					}
					if ($success) {
						$tmp=$fpUser->get_response(['format'=>'array']);
						if ($tmp && is_array($tmp) && isset($tmp['Row']['PIN'])) {
							
							$fpru=new FPReaderToUser();
							
							$fpru->setReaderId($fpr['id']);
							$fpru->setReaderUserId($tmp['Row']['PIN']);
							$fpru->setUserId($uId);
							
							try {
								$em->persist($fpru);
								$em->flush($fpru);
							} catch (\Exception $e) {
								if (strpos($e->getMessage(), '1062') === false) {
									if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
										if (!$em->isOpen()) {
											$em = $em->create($em->getConnection(), $em->getConfiguration());
										}
										if ($em->isOpen()) {
//											error_log('Entity manager is reopened');
										} else {
											error_log('Entity manager is closed');
										}
									} else {
										error_log('Database error:'.$e->getMessage());
									}
								} else {
									error_log('Data already stored');
								}
							}
						}
					}
				}
			}
		}
		return $success;
	}
	
	
	public function updateFPReaderAdmin($domainId, $readerId=null) {
error_log('updateFPReaderAdmin');
error_log('domainId:'.$domainId.', readerId:'.$readerId);
		$fpReaders=$this->getFPReaders($domainId);
		$success=false;
		
		if ($fpReaders) {
			foreach ($fpReaders as $fpr) {
				if ($fpr['status'] && ($readerId==null || $readerId == $fpr['id'])) {
					$zk=new ZKLib($fpr['ipAddress'], $fpr['port']);
					$zk_connect=$zk->connect();
					if ($zk_connect) {
						$zk->disableDevice();

						$zk->clearAdmin();
						$password=rand(100000, 899999);
						$zk->setUser(255, 255, 'ReaderAdmin', $password, Constants::LEVEL_ADMIN);
error_log('Reader '.$fpr['id'].' new admin password:'.$password);
						$success=true;
						
						$zk->enableDevice();
						$zk->disconnect();
					}
				}
			}
		}
		
		return $success;
	}
	

	public function fpSyncFingerprint($domainId) {
error_log('fpSyncFingerprint');
error_log('domainId:'.$domainId);
		$fpReaders=$this->getFPReaders($domainId);
		$success=array();
		$em=$this->doctrine->getManager();
		if ($fpReaders) {
			$fprtRepo=$this->doctrine->getRepository('TimesheetHrBundle:FPReaderTemplates');
			
			foreach ($fpReaders as $fpr) {
				if ($fpr['status']) {
					$tad=(new TADFactory(['ip'=>$fpr['ipAddress'], 'udp_port'=>$fpr['port'], 'com_key'=>$fpr['password'], 'connection_timeout'=>10]))->get_instance();
						
					if ($tad) {
// error_log('tad:'.print_r($tad, true));
						if ($tad->is_alive()) {
error_log('reader '.$fpr['id'].' is online');
							$fpUsers=$tad->get_all_user_info()->get_response(['format'=>'array']);
							if (isset($fpUsers['Row']) && count($fpUsers['Row'] > 0)) {
								foreach ($fpUsers['Row'] as $u) {
error_log('reader user PIN:'.print_r($u['PIN2'], true));
									$fpTemplates=$tad->get_user_template(array('pin'=>$u['PIN2']))->get_response(['format'=>'array']);
error_log('fingerprint:'.print_r($fpTemplates, true));
									if ($fpTemplates && isset($fpTemplates['Row']) && is_array($fpTemplates['Row']) && count($fpTemplates['Row'])) {
										foreach ($fpTemplates['Row'] as $fpt) {
											if (is_array($fpt) && count($fpt)==5 && isset($fpt['FingerID'])) {
error_log('fpt:'.print_r($fpt, true));
												$fId=$fpt['FingerID'];
												$fprt=$fprtRepo
													->findOneBy(array(
														'readerId'=>$fpr['id'],
														'readerUserId'=>$fpt['PIN'],
														'fingerId'=>$fId
													));
error_log('fprt:'.print_r($fprt, true));
												if (!isset($success[$fpt['PIN']])) {
													$success[$fpt['PIN']]=0;
												}
												$success[$fpt['PIN']]++;
												if ($fprt) {
													
													if ($fprt->getTemplate() != $fpt['Template']) {
														error_log('Fingerprint template is different, save again');
														$fprt->setTemplate($fpt['Template']);
														$fprt->setUpdatedOn(new \DateTime('now'));
													} else {
														error_log('Same template stored');
													}
												} else {
													error_log('Template not exists, try to save');
													$error=false;
													try {
														$fprt=new FPReaderTemplates();
														
														$fprt->setReaderId($fpr['id']);
														$fprt->setReaderUserId($fpt['PIN']);
														$fprt->setFingerId($fId);
														$fprt->setTemplate($fpt['Template']);
														$fprt->setUpdatedOn(new \DateTime('now'));
													
														$em->persist($fprt);
														$em->flush($fprt);
													} catch (\Exception $e) {
														if (strpos($e->getMessage(), '1062') === false) {
															$error=true;
															if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
																if (!$em->isOpen()) {
																	$em = $em->create($em->getConnection(), $em->getConfiguration());
																}
																if ($em->isOpen()) {
	//																error_log('Entity manager is reopened');
																} else {
																	error_log('Entity manager is closed');
																}
															} else {
																error_log('Database error:'.$e->getMessage());
															}
														} else {
															error_log('Data already stored');
														}
													}
													if (!$error) {
														$success[$fpt['PIN']]++;
													}
												}
											}
										}
									}
								}
error_log('no more user');
							} else {
								error_log('No user created on the FP Reader');
							}
						} else {
							error_log('Reader '.$fpr['id'].' is offline');
						}
					} else {
						error_log('Reader not connected');
					}
				}
			}
		}
error_log('end fpSyncFingerprint');
		return $success;
	}
	
	
	public function savePunchStatus($userId, $statusId, $comment, $ref=null) {
		 
		$now=new \DateTime('now');
		$now->setTimeZone(new \DateTimeZone('UTC'));
		 
		$em=$this->doctrine->getManager();
		 
		$user=$this->doctrine
			->getRepository('TimesheetHrBundle:User')
			->findOneBy(array('id'=>$userId));
	
		if ($user) {
			$ip=$this->container->get('request')->getClientIp();
	
			$punch=new Info();
	
			$punch->setUserId($userId);
			$punch->setStatusId($statusId);
			$punch->setTimestamp($now);
			$punch->setComment($comment?$comment:'');
			$punch->setIpAddress($ip);
			$punch->setCreatedOn($now);
			$punch->setCreatedBy($userId);
			if (isset($ref) && isset($ref['latitude']) && isset($ref['longitude'])) {
				$punch->setLatitude($ref['latitude']);
				$punch->setLongitude($ref['longitude']);
			}
	
			$error=false;
			try {
				$em->persist($punch);
				$em->flush($punch);
			} catch (\Exception $e) {
				error_log('savePunchStatus error:'.$e->getMessage());
				$error=true;
			}
			if ($error || !$punch->getId()) {
				error_log('Failed to write punch information');
		   
				return '[write error:info]';
			} else {
				 
				$user->setLastStatus($statusId);
				$user->setLastTime($now);
				$user->setLastIpAddress($ip);
				$user->setLastComment($comment);
		   
				$em->flush($user);
			}
	
		} else {
			error_log('Failed to write user information');
	
			return '[write error: user]';
		}
		 
		return '';
	}
	
	
	public function distance($lat1, $lon1, $lat2, $lon2, $unit) {
	
		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;
		$unit = strtoupper($unit);
	
		if ($unit == "K") {
			return ($miles * 1.609344);
		} else if ($unit == "N") {
			return ($miles * 0.8684);
		} else {
			return $miles;
		}
	}
	
}
