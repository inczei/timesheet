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
use Timesheet\Bundle\HrBundle\Entity\Companies;
use \DateTime;
use \DateTimeZone;
use Symfony\Component\Validator\Constraints\IsNull;
use Timesheet\Bundle\HrBundle\Entity\TimesheetCheck;
use Symfony\Component\HttpFoundation\RequestStack;
use Timesheet\Bundle\HrBundle\Entity\ResidentPlacements;
use DoctrineExtensions\Query\Mysql\DateDiff;


class Functions extends ContainerAware
{
	
	protected $doctrine;
	protected $requestStack;
	public $link_length = 50;
	
	
	
	public function __construct($doctrine, RequestStack $requestStack) {
		$this->doctrine = $doctrine;
		$this->requestStack = $requestStack;
	}

	
	public function getCurrentStatus($userId) {
// error_log('getCurrentStatus');
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('s.name')
			->from('TimesheetHrBundle:Info', 'i')
			->leftJoin('TimesheetHrBundle:Status', 's', 'WITH', 's.id=i.statusId')
			->where('i.userId=:userId')
			->andWhere('i.timestamp<CURRENT_TIMESTAMP()')
			->orderBy('i.timestamp', 'DESC')
			->setParameter('userId', $userId)
			->setMaxResults(1);

		$query=$qb->getQuery();
		$result=$query->getArrayResult();
	
		if ($result && count($result)==1) {
			return $result[0]['name'];
		} else {
			return null;
		}
	}
	
	
	public function getUsersList($userId=null, $name=null, $all=false, $groupId=null, $qualificationId=null, $locationId=null, $extra=true, $domainId=null) {
		
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
		$results=$query->getArrayResult();

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
			$users=$query->getArrayResult();
				
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
			}
			
			$users[-1]['found']=$found;
		}
		
		return $users;
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
			$result=$query->getArrayResult();
			$currentUser=reset($result);
			return trim($currentUser['title'].' '.$currentUser['firstName'].' '.$currentUser['lastName']);
		}
		return null;
	}
	
	
	public function getUserVisas($userId) {
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
				
			$query=$qb->getQuery();
			// error_log('query:'.$query->getDql());
			return $query->getArrayResult();
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
		$results=$query->getArrayResult();
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
			$results=$query->getArrayResult();
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
			$results=$query->getArrayResult();
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
			return $query->getArrayResult();
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
			$results=$query->getArrayResult();
			if ($results && count($results)) {
				foreach ($results as $r) {
					$rooms[$r['id']]=$r['roomNumber'].' - '.$r['name'];
				}
			}
			return $rooms;
			
		} else {
			return $query->getArrayResult();
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
			$results=$query->getArrayResult();
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
			return $query->getArrayResult();
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
			return $query->getArrayResult();
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
		$results=$query->getArrayResult();

		if ($results) {
			foreach ($results as $result) {
				$shifts[$result['locationId']][$result['id']]=array(
					'timings'=>$result['startTime']->format('H:i').' - '.$result['finishTime']->format('H:i'),
					'days'=>$this->getShiftDays($result['id']),
					'title'=>$result['title']
				);
			}
		}
/*		
		$results=$this->doctrine
			->getRepository('TimesheetHrBundle:Shifts')
			->findBy(
				$arr,
				array('startTime'=>'ASC')
		);
		
		if ($results) {
			foreach ($results as $result) {
				$shifts[$result->getLocationId()][$result->getId()]=array(
					'timings'=>$result->getStartTime()->format('H:i').' - '.$result->getFinishTime()->format('H:i'),
					'days'=>$this->getShiftDays($result->getId()),
					'title'=>$result->getTitle()
				);
			}
		}
*/
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

	
	public function getAvailableRoles($currentRole=array('ROLE_USER')) {
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
		$results=$query->getArrayResult();
/*		
		$conn=$this->doctrine->getConnection();
		 
		$query='SELECT'.
				' `g`.`name`,'.
				' `sr`.`numberOfStaff`'.
				' FROM `Shifts` `s`'.
					' JOIN `StaffRequirements` `sr` ON `s`.`id`=`sr`.`shiftId`'.
					' JOIN `Groups` `g` ON `sr`.`groupId`=`g`.`id`'.
				' WHERE `s`.`locationId`=:lId';
		 
		$stmt=$conn->prepare($query);
		$stmt->bindValue('lId', $locationId);
		$stmt->execute();
	
		$results=$stmt->fetchAll();
*/
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
		if ($domainId) {
			$search=array('domainId'=>$domainId);
		} else {
			$search=array();
		}
		$results=$this->doctrine
			->getRepository('TimesheetHrBundle:Groups')
			->findBy(
				$search,
				array('name'=>'ASC')
			);
		 
		$arr=array();
		if ($results) {
			foreach ($results as $result) {
				$arr[$result->getId()]=$result->getName();
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
		$results=$query->getArrayResult();

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
		$results=$query->getArrayResult();
		
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
		$result=$this->doctrine
			->getRepository('TimesheetHrBundle:Companies')
			->findOneBy(
				array('domain'=>$domain)
			);
		if ($result) {
			return $result->getId();
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
	
	
	public function getStatuses($id=null) {
		 
		$statuses=array();
		$first=null;
		 
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('s.id')
			->addSelect('s.start')
			->addSelect('s.name')
			->addSelect('s.active')
			->addSelect('s.level')
			->addSelect('s.multi')
			->addSelect('s.color')
			->addSelect('s.pair')
			->from('TimesheetHrBundle:Status', 's')
			->orderBy('s.id', 'ASC');
			
		if ($id) {
			$qb->where('s.id=:id OR s.pair=:id')
				->setParameter('id', $id);
		}
		$query=$qb->getQuery();
	
		$results=$query->getArrayResult();
	
		if ($results) {
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
		$results=$query->getArrayResult();
		
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
		
		$search=array();
		if ($id) {
			$search['id']=$id;
		}
		if ($domainId) {
			$search['domainId']=$domainId;
		}
		$results=$this->doctrine
			->getRepository('TimesheetHrBundle:Location')
			->findBy(
				($search),
				array('name'=>'ASC')
			);
	
		$arr=array();
		if ($results) {
			foreach ($results as $result) {
				if ($nameOnly) {
					$arr[$result->getId()]=$result->getName();
				} else {
					$tmp=array(
							'id'=>$result->getId(),
							'name'=>$result->getName(),
							'address'=>array(
									'line1'=>$result->getAddressLine1(),
									'line2'=>$result->getAddressLine2(),
									'city'=>$result->getAddressCity(),
									'county'=>$result->getAddressCounty(),
									'country'=>$result->getAddressCountry(),
									'postcode'=>$result->getAddressPostcode()
							),
							'phone'=>array(
									'landline'=>$result->getPhoneLandline(),
									'mobile'=>$result->getPhoneMobile(),
									'fax'=>$result->getPhoneFax()
							),
							'active'=>$result->getActive(),
							'fixedipaddress'=>$result->getFixedIpAddress(),
							'ipaddress'=>$this->getIpAddress($result->getId()),
							'members'=>$this->getMembers($result->getId())
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
	
	
	public function getIpAddress($locationId) {
		$results=$this->doctrine
			->getRepository('TimesheetHrBundle:LocationIpAddress')
			->findBy(
				(($locationId)?(array('locationId'=>$locationId)):(array())),
				array('ipAddress'=>'ASC')
			);
		
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
			->addSelect('s.title')
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
		$results=$query->getArrayResult();

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
							'days'=>$this->getShiftDays($result['id']),
							'staffReq'=>$this->getRequirementsForShift($result['id']),
							'qualReq'=>$this->getQualRequirementsForShift($result['id'])
					);
				}
			}
		}
		
/*		
		
		$results=$this->doctrine
			->getRepository('TimesheetHrBundle:Shifts')
			->findBy(
				(($id)?(array('id'=>$id)):(array())),
				array('locationId'=>'ASC', 'startTime'=>'ASC')
			);
	
		$arr=array();
		if ($results) {
			if ($id) {
				foreach ($results as $result) {
					$arr[$result->getId()]=$this->getLocation($result->getLocationId(), true);
				}	
			} else {
				foreach ($results as $result) {
					$loc=$this->getLocation($result->getLocationId(), true);
					
					$arr[$result->getId()]=array(
						'id'=>$result->getId(),
						'title'=>$result->getTitle(),
						'locationId'=>$result->getLocationId(),
						'locationName'=>$loc[$result->getLocationId()],
						'startTime'=>$result->getStartTime(),
						'finishTime'=>$result->getFinishTime(),
						'days'=>$this->getShiftDays($result->getId()),
						'staffReq'=>$this->getRequirementsForShift($result->getId()),
						'qualReq'=>$this->getQualRequirementsForShift($result->getId())
					);
				}
			}
		}
*/	
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
		$ret=$query->getArrayResult();
/*		
		$query='SELECT'.
				' `qr`.`id`,'.
				' `qr`.`qualificationId`,'.
				' `qr`.`numberOfStaff`,'.
				' `s`.`startTime`,'.
				' `s`.`finishTime`,'.
				' `q`.`title` as `name`'.
				' FROM `QualRequirements` `qr`'.
				' JOIN `Qualifications` `q` ON `qr`.`qualificationId`=`q`.`id`'.
				' JOIN `Shifts` `s` ON `qr`.`shiftId`=`s`.`id`'.
				' WHERE `qr`.`shiftId`=:sId'.
				' ORDER BY `q`.`title`';
			
		$stmt=$conn->prepare($query);
		$stmt->bindValue('sId', $sId);
		$stmt->execute();
	
		$ret=$stmt->fetchAll();
*/	
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

		return $qb->getQuery()->getArrayResult();	
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
			} else {
				$minutes=round((strtotime($result['finishTime'])-strtotime($result['startTime']))/60);
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
	
	
	public function getHolidayEntitlement($userId = null) {
error_log('getHolidayEntitlement, userId:'.$userId);
		$request=$this->requestStack->getCurrentRequest();
		$ret=array(
			'yearstart'=>null,
			`csd`=>null,
			'annualholidays'=>0,
			'untilToday'=>0,
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
			$contracts=$this->getContracts($userId);

			if ($contracts && count($contracts)) {
// error_log('contracts:'.print_r($contracts, true));
				end($contracts);
				$lastContract=$contracts[key($contracts)];
				/*
				 * If the start date is on Year Start, use that based on the configuration,
				 * anyway use Contract Start Date
				 */
				$ret['untilToday']+=$lastContract['initHolidays'];
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
				$ret['list']=$this->getHolidaysList($userId, max($ret['yearstart'], $ret['csd']), (($lastContract['ced'])?(min($lastContract['ced'], date('Y-m-d'))):(date('Y-m-d'))));
				if (count($ret['list'])) {
					foreach ($ret['list'] as $l) {
						if ($l['entitlement']) {
							/*
							 * Remaining holidays - taken holidays
							 * entitlement:
							 *  -1 if taken from holiday entitlement
							 *  +1 if additionam holiday entitlement (as extra holiday)
							 */
							$ret['untilToday']+=$l['entitlement']*$l['days'];
						}
					}
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
			$ts=strtotime(max($ret['yearstart']->format('Y-m-d H:i:s'), $ret['csd']->format('Y-m-d H:i:s')));
			$ret['currentDay']=floor((mktime(0, 0, 0, date('m'), date('d'), date('Y'))-mktime(0, 0, 0, date('m', $ts), date('d', $ts), date('Y', $ts)))/(60*60*24));
			$ret['untilToday']+=$ret['annualholidays']/$ret['daysOfYear']*$ret['currentDay'];
		}

		return $ret;
	}
	
	
	public function getWeeklySchedule($userId, $timestamp) {
		
		$monday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp)));
		$sunday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+7, date('Y', $timestamp)));

		$query='SELECT'.
			' `a`.`date`,'.
			' `l`.`name`,'.
			' `s`.`title`,'.
			' `s`.`startTime`,'.
			' `s`.`finishTime`'.
			' FROM `Allocation` `a`'.
				' JOIN `Shifts` `s` ON `a`.`shiftId`=`s`.`id`'.
				' JOIN `Location` `l` ON `s`.`locationId`=`l`.`id`'.
			' WHERE `a`.`published`'.
				' AND `a`.`userId`=:uId'.
				' AND `a`.`date` BETWEEN :date1 AND :date2'.
			' ORDER BY `a`.`date`';
		
		
		$conn=$this->doctrine->getConnection();
		$stmt=$conn->prepare($query);
		$stmt->bindValue('uId', $userId);
		$stmt->bindValue('date1', $monday);
		$stmt->bindValue('date2', $sunday);
		$stmt->execute();
		
		$results=$stmt->fetchAll();
		$data=array();
		$ts=strtotime($monday);
		$holidays=$this->getHolidaysForMonth($conn, $userId, $monday, $sunday);
		
		for ($i=0; $i<7; $i++) {
			$ts1=mktime(0, 0, 0, date('m', $ts), date('d', $ts)+$i, date('Y', $ts));
			$data[date('Y-m-d', $ts1)]=array(
				'day'=>date('l', $ts1),
				'holidays'=>((isset($holidays[date('Y-m-d', $ts1)]))?($holidays[date('Y-m-d', $ts1)]):(null))
			);
		}
		if ($results && count($results)) {
			foreach ($results as $result) {
				$data[$result['date']]['timings'][]=$result;
			}
		}
		
		return $data;
		
	}


	public function getMonthlySchedule($userId, $timestamp) {
		
		$first=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), 1, date('Y', $timestamp)));
		$last=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('t', $timestamp), date('Y', $timestamp)));

		$query='SELECT'.
			' `a`.`date`,'.
			' `l`.`name`,'.
			' `s`.`startTime`,'.
			' `s`.`finishTime`'.
			' FROM `Allocation` `a`'.
				' JOIN `Shifts` `s` ON `a`.`shiftId`=`s`.`id`'.
				' JOIN `Location` `l` ON `s`.`locationId`=`l`.`id`'.
			' WHERE `a`.`published`'.
				' AND `a`.`userId`=:uId'.
				' AND `a`.`date` BETWEEN :date1 AND :date2'.
			' ORDER BY `a`.`date`';
		
		
		$conn=$this->doctrine->getConnection();
		$stmt=$conn->prepare($query);
		$stmt->bindValue('uId', $userId);
		$stmt->bindValue('date1', $first);
		$stmt->bindValue('date2', $last);
		$stmt->execute();
		
		$results=$stmt->fetchAll();
		$data=array();
		$ts=strtotime($first);
		$holidays=$this->getHolidaysForMonth($conn, $userId, $first, $last);

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
				$data[date('W', strtotime($result['date']))][$result['date']]['timings'][]=$result;
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
		$results=$query->getArrayResult();
		
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
		$results=$query->getArrayResult();

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
		while ($d <= max($data['monthly']['next']['last'], $data['weekly']['next']['last'])) {
			$timings=$this->getTimingsForDay($userId, $d);
// error_log('timings ('.date('Y-m-d', $d).'):'.print_r($timings, true));
			$whr=$this->calculateHours($timings, $domainId);
			
			foreach ($data as $k=>$v) {
				foreach ($v as $k1=>$v1) {
					if ($d>=$v1['first'] && $d<=$v1['last']) {
// error_log('data: '.$k.' : '.$k1.' ('.date('Y-m-d', $v1['first']).' - '.date('Y-m-d', $v1['last']).')'.' : '.print_r($timings, true));
						$data[$k][$k1]['whr']+=$whr;
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
error_log('userId:'.$userId.', start:'.print_r($startDate, true).', finish:'.print_r($finishDate, true));

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
		
		
		return $qb->getQuery()->getResult();
	}
	
	
	public function getConfig($key, $domainId=null) {
		/*
		 * read the config table by name
		 */
// error_log('getConfig');
		if ($domainId) {
// error_log('domain:'.$domainId);
			$em=$this->doctrine->getManager();
			
			$qb=$em
				->createQueryBuilder()
				->select('c.'.$key)
				->from('TimesheetHrBundle:Companies', 'c')
				->where('c.id=:dId')
				->setParameter('dId', $domainId);
	
			$query=$qb->getQuery();

			$result=$query->getArrayResult();
// error_log('result:'.print_r($result, true));
			if ($result && count($result)) {
				return $result[0][$key];	
			} else {
				return $this->getConfig($key);
			}			
		} else {
// error_log('no domain, key:'.print_r($key, true));
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
		
		$conn=$this->doctrine->getConnection();
		
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
		if ($result) {
			return $result['published']?true:false;
		} else {
			return false;
		}
		
	}
	
	public function allocateUserToSchedule($date, $locationId, $shiftId, $userId) {
// error_log('allocateUserToSchedule');
		$message='';		
//		$conn=$this->doctrine->getConnection();
		$em=$this->doctrine->getManager();
		
		$ok=true;
		if ($this->isHolidayOrDayoff($userId, $date)) {
			$message='Sorry, not allowed. Holiday/Day off booked and approved';
		} else if ($this->isPublished($shiftId, $date)) {
			$message='Sorry, not allowed. Published';
		} else {
			$qb=$em
				->createQueryBuilder()
				->select('a')
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

		$results=$query->getArrayResult();		
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
				
				$em->persist($allocation);
				$em->flush($allocation);
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
			' GROUP BY `a`.`id`';

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
			' GROUP BY `a`.`id`';
	
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
error_log('createAllocationDiv');
		return '<div class="'.
				'allocation allocationNormal'.
				'">'.
					'<table><tr>'.
					(($published)?
						(''):
						('<td>'.
						'<span class="allocationRemove" data-id="'.str_replace('-', '', $date).'_'.$locationId.'_'.$shiftId.'_'.$userId.'" title="Click here to remove">X</span>'.
						'</td>')
					).
					'<td>'.
					'<span class="allocationName" title="'.(($published)?('Published'.PHP_EOL):('')).'Username: '.$username.((strlen($groupname))?(PHP_EOL.'Group: '.$groupname):('')).((strlen($qualifications))?(PHP_EOL.'Qualifications:'.PHP_EOL.' - '.$qualifications):('')).'">'.$fullname.'</span>'.
					'</td></tr></table>'.
				'</div>';
	}

	
	public function getAllocationForLocation($locationId, $date, $divs=true) {
error_log('getAllocationForLocation');
		$conn=$this->doctrine->getConnection();

		$timestamp=strtotime($date);
    	$monday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp)));
    	$sunday=date('Y-m-d', mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+7, date('Y', $timestamp)));
    	 
		$query='SELECT'.
			' `u`.`username`,'.
			' `u`.`firstName`,'.
			' `u`.`lastName`,'.
			' `a`.`date`,'.
			' `s`.`startTime`,'.
			' `s`.`finishTime`,'.
			' `a`.`userId`,'.
			' `a`.`published`,'.
			' `g`.`name` as `groupname`'.
			' FROM `Allocation` `a`'.
				' JOIN `Users` `u` ON `a`.`userId`=`u`.`id`'.
				' JOIN `Location` `l` ON `a`.`locationId`=`l`.`id`'.
				' JOIN `Shifts` `s` ON `a`.`shiftId`=`s`.`id`'.
				' JOIN `Groups` `g` ON `u`.`groupId`=`g`.`id`'.
				' LEFT JOIN `StaffRequirements` `sr` ON `s`.`id`=`sr`.`shiftId` AND `sr`.`groupId`=`g`.`id`'.
			' WHERE `a`.`date` BETWEEN :monday AND :sunday'.
				' AND `a`.`locationId`=:lId'.
			' ORDER BY `u`.`firstName`, `u`.`lastName`';
// error_log($query);
// error_log('monday:'.$monday.', sunday:'.$sunday.', location:'.$locationId);		
		$stmt=$conn->prepare($query);
		$stmt->bindValue('monday', $monday);
		$stmt->bindValue('sunday', $sunday);
		$stmt->bindValue('lId', $locationId);
		$stmt->execute();
		
		$results=$stmt->fetchAll();

		$ret=array();
		
		if ($results && count($results)) {
				
			foreach ($results as $r) {
				if (!isset($ret[$r['userId']])) {
					$ret[$r['userId']]=array(
						'username'=>$r['username'],
						'groupname'=>$r['groupname'],
						'name'=>trim($r['firstName'].' '.$r['lastName']),
						'AWH'=>$this->getAWH($r['userId'], $r['date']),
						'WH'=>0);
				}
				if ($r['finishTime'] > $r['startTime']) {
					$ret[$r['userId']]['WH']+=round((strtotime($r['date'].' '.$r['finishTime'])-strtotime($r['date'].' '.$r['startTime']))/60);
				} else {
					$d1=strtotime($r['date']);
					$d2=date('Y-m-d', mktime(0, 0, 0, date('m', $d1), date('d', $d1)+1, date('Y', $d1)));
					$ret[$r['userId']]['WH']+=round((strtotime($d2.' '.$r['finishTime'])-strtotime($r['date'].' '.$r['startTime']))/60);
				}
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
		
		$results=$qb->getQuery()->getArrayResult();
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
// error_log('createWeeklyDiv');
		$ret='';
	
		if ($results && count($results)) {
			foreach ($results as $k=>$v) {
				
				if ($k > 0) {
					$class='allocationNormal';
					if ($v['AWH']) {
						$ok=(round($v['WH']/6)/10)-$v['AWH'];
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
					
					$ret.='<div class="allocation '.$class.'" title="Username: '.$v['username'].((isset($v['groupname']))?(PHP_EOL.'Group: '.$v['groupname']):('')).PHP_EOL.'Agreed Weekly Hours: '.$v['AWH'].'">'.
						$v['name'].':'.
						'<br>'.
						'This week: '.(round($v['WH']/6)/10).' Hrs'.
						(($v['AWH']<1)?('<br>NO CONTRACT !!!'):('')).
						$holidays_html.
						((strlen($exceptions_html))?('<span style="color: #cc0000">'.$exceptions_html.'</span>'):('')).
						'</div>';
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
		
		$results=$qb->getQuery()->getArrayResult();
		
/*
		$conn=$this->doctrine->getConnection();
		$query='SELECT'.
			' `a`.`id`,'.
			' `a`.`date`,'.
			' `s`.`locationId`,'.
			' `s`.`startTime`,'.
			' `s`.`finishTime`,'.
			' `l`.`name`'.
			' FROM `Allocation` `a`'.
				' JOIN `Shifts` `s` ON `a`.`shiftId`=`s`.`id`'.
				' JOIN `Location` `l` ON `a`.`locationId`=`l`.`id` AND `s`.`locationId`=`l`.`id`'.
				' JOIN `ShiftDays` `sd` ON `s`.`id`=`sd`.`shiftId`'.
			' WHERE `a`.`userId`=:uId'.
			(($monday)?(' AND `a`.`date`>=:monday'):('')).
			(($sunday)?(' AND `a`.`date`<=:sunday'):('')).
			' GROUP BY `a`.`id`';
// error_log($query);
		$stmt=$conn->prepare($query);
// error_log('uId:'.$userId);
		$stmt->bindValue('uId', $userId);
		if ($monday) {
// error_log('monday:'.$monday);
			$stmt->bindValue('monday', $monday);
		}
		if ($sunday) {
// error_log('sunday:'.$sunday);
			$stmt->bindValue('sunday', $sunday);
		}
		$stmt->execute();
		
		$results=$stmt->fetchAll();
*/
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
// $time=microtime();
		$query=$qb->getQuery();
// error_log('query:'.$query->getDql());
		$results=$query->getArrayResult();
// error_log('time1:'.(microtime()-$time));
// error_log('results:'.print_r($results, true));
/*		
		$query='SELECT'.
			' `u`.`username`,'.
			' `u`.`firstName`,'.
			' `u`.`lastName`,'.
			' `u`.`groupId`,'.
			' GROUP_CONCAT(DISTINCT CONCAT_WS("#", `q`.`id`, `ql`.`rank`) ORDER BY `q`.`title` SEPARATOR "|") as `qualifications`,'.
			' `a`.`date`,'.
			' `a`.`locationId`,'.
			' `s`.`id` as `shiftId`,'.
			' `a`.`userId`,'.
			' `g`.`name` as `groupname`,'.
			' `s`.`startTime`,'.
			' `s`.`finishTime`'.
			' FROM `Allocation` `a`'.
				' JOIN `Users` `u` ON `a`.`userId`=`u`.`id`'.
				' JOIN `Location` `l` ON `a`.`locationId`=`l`.`id`'.
				' JOIN `Shifts` `s` ON `a`.`shiftId`=`s`.`id`'.
				' JOIN `Groups` `g` ON `u`.`groupId`=`g`.`id`'.
				' LEFT JOIN (`Qualifications` `q` JOIN `UserQualifications` `uq` ON `q`.`id`=`uq`.`qualificationId` LEFT JOIN `QualificationLevels` `ql` ON `uq`.`levelId`=`ql`.`id`) ON `uq`.`userId`=`u`.`id`'.
			' WHERE `a`.`date` BETWEEN :monday AND :sunday'.
			(($locationId)?(' AND `a`.`locationId`=:lId'):('')).
			' GROUP BY `a`.`id`'.
			' ORDER BY `u`.`firstName`, `u`.`lastName`';
// error_log($query);		
$time=microtime();
		$conn=$this->doctrine->getConnection();
		$stmt=$conn->prepare($query);
		if ($timestamp) {
// error_log('monday:'.$monday.', sunday:'.$sunday);
			$stmt->bindValue('monday', $monday);
			$stmt->bindValue('sunday', $sunday);
		}
		if ($locationId) {
// error_log('lId:'.$locationId);
			$stmt->bindValue('lId', $locationId);
		}
		$stmt->execute();
		
		$results2=$stmt->fetchAll();
error_log('time2:'.(microtime()-$time));
error_log('results1'.print_r($results2, true));
*/
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
				if ($r['finishTime'] > $r['startTime']) {
					$tmp[$r['locationId']][$r['userId']]['WH']+=round((strtotime($r['date']->format('Y-m-d').' '.$r['finishTime']->format('H:i:s'))-strtotime($r['date']->format('Y-m-d').' '.$r['startTime']->format('H:i:s')))/60);
				} else {
					$d1=strtotime($r['date']->format('Y-m-d'));
					$d2=date('Y-m-d', mktime(0, 0, 0, date('m', $d1), date('d', $d1)+1, date('Y', $d1)));
					$tmp[$r['locationId']][$r['userId']]['WH']+=round((strtotime($d2.' '.$r['finishTime']->format('H:i:s'))-strtotime($r['date']->format('Y-m-d').' '.$r['startTime']->format('H:i:s')))/60);
				}
			}
		}

		if (count($tmp)) {
			foreach ($tmp as $k=>$v) {
				$ret[$k]=$this->createWeeklyDiv($v, $monday, $sunday);
			}
		}
/*
		$staff=$this->getRequiredStaffList($staffMembers, $monday, $locationId);
		$qual=$this->getRequiredQualificationsList($qualificationMembers, $monday, $locationId);

		if (count($qual)) {
			$ret[-1]='<div class="allocation allocationHigh">Required qualifications:<div name="showhide" id="rs_showhidebutton" column="req_qual_div">Show</div><div class="req_qual_div" id="rs_showhide" style="display: none">'.((count($qual))?(implode('<br>', $qual)):('')).'</div></div>';
		}
		if (count($staff)) {
			$ret[0]='<div class="allocation allocationHigh">Required staff:<div name="showhide" id="rq_showhidebutton" column="req_staff_div">Show</div><div class="req_staff_div" id="rq_showhide" style="display: none">'.((count($staff))?(implode('<br>', $staff)):('')).'</div></div>';
		}
*/			
//		error_log(print_r($ret, true));
		
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
	
		return $qb->getQuery()->getArrayResult();
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
	
		return $qb->getQuery()->getArrayResult();
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
		
		return $qb->getQuery()->getArrayResult();
	}
	

	public function getCurrentlyAllocatedStaff($locationId, $date) {
error_log('getCurrentlyAllocatedStaff');
// error_log('locationId:'.$locationId.', date:'.$date);
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('a.userId')
			->addSelect('a.shiftId')
			->addSelect('u.groupId')
			->addSelect('g.name')
			->from('TimesheetHrBundle:Allocation', 'a')
			->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'a.shiftId=s.id')
			->join('TimesheetHrBundle:Location', 'l', 'WITH', 'a.locationId=l.id')
			->join('TimesheetHrBundle:User', 'u', 'WITH', 'u.id=a.userId')
			->join('TimesheetHrBundle:Groups', 'g', 'WITH', 'u.groupId=g.id')
			->where('l.id=:lId')
			->andWhere('a.date=:date')
			->groupBy('a.id')
			->setParameter('lId', $locationId)
			->setParameter('date', new \DateTime($date));
	
		return $qb->getQuery()->getArrayResult();	
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
		
		$results=$qb->getQuery()->getArrayResult();
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
		
		$results=$qb->getQuery()->getArrayResult();
		
		if ($results && count($results)==1) {
			return $results[0]['awh'];
		} else {
			return 0;
		}
		
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
error_log('getCalendarDay, userId:'.$userId.', timestamp:'.$timestamp.', date:'.date('Y-m-d', $timestamp).', data:'.$data);		
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
		$results=$qb->getQuery()->getArrayResult();
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

	
	public function getUsersForManager($user, $limit=0) {
		
		$ret=array();
		$em=$this->doctrine->getManager();
		
		$qb=$em->createQueryBuilder()
			->select('u.id')
			->addSelect('u.username')
			->addSelect('u.title')
			->addSelect('u.firstName')
			->addSelect('u.lastName')
			->from('TimesheetHrBundle:User', 'u')
			->where('u.isActive=true')
			->andWhere('u.domainId=:dId')
			->setParameter('dId', $user->getDomainId());
	
		$where=array();	
		if ($user->getGroupAdmin()) {
//			$where[]='u.groupId IS NULL OR u.groupId=:gId';
			$where[]='u.groupId=:gId';
			$qb->setParameter('gId', $user->getGroupId());
		}
		if ($user->getLocationAdmin()) {
//			$where[]='u.locationId IS NULL OR u.locationId=:lId';
			$where[]='u.locationId=:lId';
			$qb->setParameter('lId', $user->getLocationId());
		}
		if (count($where)) {
			$qb->andWhere('('.implode(' OR ', $where).')');
		}
		if ($limit) {
			$qb->setMaxResults($limit)
				->orderBy('u.lastTime', 'DESC');
		} else {
			$qb->orderBy('u.firstName', 'ASC')
				->addOrderBy('u.lastName', 'ASC');		
		}

		$results=$qb->getQuery()->getArrayResult();
		
		if ($results) {
			foreach ($results as $result) {
				$ret[$result['id']]=$result;
			}
		}
		return $ret;
	}
	
	public function getTimesheet($userId, $timestamp, $usersearch, $session, $domainId=0, $selectedUserId=0, $availableUsers=null) {
error_log('getTimesheet');
// error_log('1 memory:'.memory_get_usage());
// error_log('userId:'.$userId.', timestamp:'.$timestamp.', usersearch:'.$usersearch.', domainId:'.$domainId.', selectedUserId:'.$selectedUserId.', availableUsers ('.count($availableUsers).'):'.print_r($availableUsers, true));	
		$conn=$this->doctrine->getConnection();
		$ret=array();
		$admin=false;
		$groupId=null;
		$locationId=null;
		$totalUsers=0;

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
//error_log('admin:'.(($admin)?'true':'false'));
$time=microtime(true);		
		$em=$this->doctrine->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('i.timestamp')
			->addSelect('i.ipAddress')
			->addSelect('i.comment')
			->addSelect('i.userId')
			->addSelect('i.statusId')
			->addSelect('i.id')

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
			
			
			->from('TimesheetHrBundle:Info', 'i')
			->join('TimesheetHrBundle:Status', 's', 'WITH', 'i.statusId=s.id')
			->join('TimesheetHrBundle:User', 'u', 'WITH', 'i.userId=u.id')
			->leftJoin('TimesheetHrBundle:Contract', 'c', 'WITH', 'c.userId=u.id AND c.csd<=DATE(i.timestamp) AND (c.ced>=DATE(i.timestamp) OR c.ced IS NULL)')
			->leftJoin('TimesheetHrBundle:Allocation', 'a', 'WITH', 'a.userId=u.id AND a.date=DATE(i.timestamp)')
			->leftJoin('TimesheetHrBundle:Shifts', 'sh', 'WITH', 'sh.id=a.shiftId AND sh.locationId=a.locationId')
			->leftJoin('TimesheetHrBundle:Location', 'l', 'WITH', 'l.id=a.locationId')
			->where('u.domainId=:dId')
			->groupBy('i.id')
			->orderBy('u.firstName', 'ASC')
			->orderBy('u.lastName', 'ASC')
			->addOrderBy('i.timestamp', 'ASC')
			->setParameter('dId', $domainId);

		if (!$admin && $userId) {
			$qb->andWhere('u.id=:uId')
				->setParameter('uId', $userId);
		}
//		if ($admin && $groupId) {
//			$qb->andWhere('u.groupId=:gId')
//				->setParameter('gId', $groupId);
//		}
//		if ($usersearch) {
//			$qb->andWhere('u.username LIKE :uSearch OR u.firstName LIKE :uSearch OR u.lastName LIKE :uSearch')
//				->setParameter('uSearch', '%'.$usersearch.'%');
//		}
		if ($selectedUserId) {
			if ($selectedUserId > 0) {
				$qb->andWhere('u.id=:userId')
					->setParameter('userId', $selectedUserId);
			}
		} else {
			// if not selected any user, result should be empty
			$qb->andWhere('u.id<0');
		}
//		if ($admin && $locationId) {
//			$qb->andWhere('u.locationId=:lId')
//				->setParameter('lId', $locationId);
//		}
		if (isset($availableUsers) && is_array($availableUsers) && count($availableUsers)) {
			$qb->andWhere('u.id IN (\''.implode('\',\'', array_keys($availableUsers)).'\')');
		}
		if ($timestamp) {
			$startTime=date('Y-m-01 00:00:00', $timestamp);
			$finishTime=date('Y-m-t 23:59:59', $timestamp);
			$qb->andWhere('i.timestamp BETWEEN :dateStart AND :dateFinish')
				->setParameter('dateStart', $startTime)
				->setParameter('dateFinish', $finishTime);
		}
		$query=$qb->getQuery();
error_log('1 sql:'.$query->getDql());
		$results=$query->getArrayResult();
// error_log('1st no of results:'.count($results).', time:'.(microtime(true)-$time));
// error_log('results:'.print_r($results, true));		
		if ($results) {

			$lastUser=null;
			$lastDate=null;
			$otherId=0;
			$holidays=array();
			$userLastSignIn=array();

			$timezone=$session->get('timezone');
			
			foreach ($results as $result) {
// error_log('result:'.print_r($result, true));
				
				// Overwrite timestamp with local time as string				
				$d=new \DateTime($result['timestamp']->format('Y-m-d H:i:s'), new \DateTimeZone('UTC'));
				$d->setTimezone(new \DateTimeZone($timezone));
				$result['timestamp']=$d->format('Y-m-d H:i:s');
				if ($result['startTime'] && is_object($result['startTime'])) {
					$d->setTime($result['startTime']->format('H'), $result['startTime']->format('i'), $result['startTime']->format('s'));
					$result['startTime']=clone $d;
				}
				if ($result['finishTime'] && is_object($result['finishTime'])) {
					$d->setTime($result['finishTime']->format('H'), $result['finishTime']->format('i'), $result['finishTime']->format('s'));
					$result['finishTime']=clone $d;
				}
				
				if (!isset($holidays[$result['userId']])) {
					$holidays[$result['userId']]=$this->getHolidaysForMonth($conn, $result['userId'], $startTime, $finishTime);
				}
				
				$date=$d->format('Y-m-d');
// error_log('date:'.$date.', d:'.$d->format('Y-m-d'));				
				if (isset($ret[$result['username']][$date][$result['statusId']])) {
					// Data already exists, check the time
					// if start, check the first
					// if end, check the last
					if (isset($ret[$result['username']][$date][$result['statusId']]['comment'])) {
						$ret[$result['username']][$date][$result['statusId']]['comment'].=$result['comment'];
					} else {
						$ret[$result['username']][$date][$result['statusId']]['comment']=$result['comment'];
					}

					if ($result['multi']) {
// error_log('multi entry '.print_r($result, true));

						if ($lastUser!=$result['username'] && $lastDate!=$date) {
							$lastUser=$result['username'];
							$lastDate=$date;
							$otherId=0;
						}
						// Multi entry allowed per day	
						if ($result['start']) {
							$ret[$result['username']][$date][$result['statusId']]['multi'][0][++$otherId]=$result['timestamp'];
						} else {
							$ret[$result['username']][$date][$result['statusId']]['multi'][1][$otherId]=$result['timestamp'];
						}
					} else {
						// Single entry allowed per day
// error_log('single entry');
						if ($result['start']) {
// error_log('start');
							if (isset($ret[$result['username']][$date][$result['statusId']]['timestamp'])) {
								$ret[$result['username']][$date][$result['statusId']]['timestamp']=min($ret[$result['username']][$date][$result['statusId']]['timestamp'], $result['timestamp']);
							} else {
								$ret[$result['username']][$date][$result['statusId']]['timestamp']=$result['timestamp'];
							}
						} else {
							if (isset($ret[$result['username']][$date][$result['statusId']]['timestamp'])) {
								$tmp=$ret[$result['username']][$date][$result['statusId']]['timestamp'];
								$ret[$result['username']][$date][$result['statusId']]['timestamp']=max($ret[$result['username']][$date][$result['statusId']]['timestamp'], $result['timestamp']);
								if ($ret[$result['username']][$date][$result['statusId']]['timestamp'] != $tmp) {
									if (isset($userLastSignIn[$result['username']])) {
										// if signed out next day, we note it
										// and if corrected, add the corrected date and time and a note
										end($userLastSignIn[$result['username']]);
										$tmpDate=date('Y-m-d', strtotime(prev($userLastSignIn[$result['username']])));
										$tmpTime=date('H:i', strtotime($tmp));
										if (isset($ret[$result['username']][$tmpDate][2]['comment'])) {
											$ret[$result['username']][$tmpDate][2]['comment'].=', ';
										} else {
											$ret[$result['username']][$tmpDate][2]['comment']='';
										}
										if (count($userLastSignIn[$result['username']])==2) {
											$ret[$result['username']][$tmpDate][2]['timestamp']=$tmp;
											$ret[$result['username']][$tmpDate][2]['comment'].='Next day signed out ('.$tmpTime.')';
											$ret[$result['username']][$tmpDate][0]['class']='PunchMissing';
											// if signed out next day, we give option to correct it
											$ret[$result['username']][$tmpDate][2]['changeable']=true;
										} elseif (count($userLastSignIn[$result['username']]) > 2) {
											// if already corrected, note only
											$ret[$result['username']][$tmpDate][2]['comment'].='Next day signed out ('.$tmpTime.')';
										}
									}
								}
							} else {
								$ret[$result['username']][$date][$result['statusId']]['timestamp']=$result['timestamp'];
							}
						}
					}
					
					
						
				} else {
					// new data
// error_log('new data, id:'.$result['id'].', username:'.$result['username'].', timestamp:'.$result['timestamp']);
					if ($result['start'] && $result['statusId'] == 1) {
						$userLastSignIn[$result['username']][]=$result['timestamp'];
					}
					$tmpAgreed=null;
					$tmpAgreedOrig=null;
					if ($result['start']) {
						if (!is_null($result['startTime'])) {
							$tmpAgreed=new \DateTime($date.' '.$result['startTime']->format('H:i:s'));
							$tmpAgreedOrig=clone $tmpAgreed;
						}
					} else {
						if (!is_null($result['finishTime'])) {
							$tmpAgreed=new \DateTime($date.' '.$result['finishTime']->format('H:i:s'));
							$tmpAgreedOrig=clone $tmpAgreed;
						}
					}
					$tmpHolidays=((isset($holidays[$result['userId']][$date]))?($holidays[$result['userId']][$date]):(null));
					
					$ret[$result['username']][$date][$result['statusId']]=array(
						'userId'=>$result['userId'],
						'comment'=>$result['comment'],
						'username'=>$result['username'],
						'name'=>trim($result['firstName'].' '.$result['lastName']),
						'status'=>$result['name'],
						'day'=>date('D jS M', strtotime($result['timestamp'])),
						'timestamp'=>$result['timestamp'],
						'agreed'=>$tmpAgreed,
						'agreedOrig'=>$tmpAgreedOrig,
						'location'=>$result['locationName'],
						'holidays'=>$tmpHolidays
					);

					$ret[$result['username']][$date][0]=array(
						'checked'=>$this->getTimesheetChecked($result['userId'], $date),
						'userId'=>$result['userId'],
						'username'=>$result['username'],
						'agreedStart'=>$result['startTime'],
						'agreedFinish'=>$result['finishTime'],						
						'name'=>trim($result['firstName'].' '.$result['lastName']),
						'day'=>date('D jS M', strtotime($result['timestamp'])),
						'location'=>$result['locationName'],
						'holidays'=>$tmpHolidays
					);

					if (isset($holidays[$result['userId']][$date]) && count($holidays[$result['userId']][$date])) {
						foreach ($holidays[$result['userId']][$date] as $h) {
							if (isset($h['agreedStart']) && $result['statusId']==1) {
// error_log('orig agreed check in:'.$ret[$result['username']][$date][$result['StatusId']]['agreed']);
								$ret[$result['username']][$date][$result['statusId']]['agreedOrig']=$ret[$result['username']][$date][$result['statusId']]['agreed'];
								$ret[$result['username']][$date][$result['statusId']]['agreed']=$h['agreedStart'];
							}
							if (isset($h['agreedFinish']) && $result['statusId']==2) {
// error_log('orig agreed check out:'.$ret[$result['username']][$date][$result['StatusId']]['agreed']);
								$ret[$result['username']][$date][$result['statusId']]['agreedOrig']=$ret[$result['username']][$date][$result['statusId']]['agreed'];
								$ret[$result['username']][$date][$result['statusId']]['agreed']=$h['agreedFinish'];
							}
						}
					}
						
					$class='';
					switch ($result['statusId']) {
						case 1 : {
/*
error_log('sign in');
error_log('startTime:'.print_r($result['startTime'], true));
error_log('timestamp:'.print_r($result['timestamp'], true));
*/
							// Signing in
							if ($result['startTime'] && $result['startTime']->format('H:i:s') >= date('H:i:s', strtotime($result['timestamp']))) {
								$class='PunchCorrect';
							} else {								
								$class='PunchIncorrect'; // .$result['startTime'];
							}
// error_log('punch:'.$class);
							break;
						}
						case 2 : {
/*
error_log('sign out');
error_log('finishTime:'.print_r($result['finishTime'], true));
error_log('timestamp:'.print_r($result['timestamp'], true));
*/
							// Signing out
							if ($result['finishTime'] && $result['finishTime']->format('H:i:s') <= date('H:i:s', strtotime($result['timestamp']))) {
								$class='PunchCorrect';
							} else {
								$class='PunchIncorrect'; // .$result['finishTime'];
							}
// error_log('punch:'.$class);
							break;
						}
					}
					$ret[$result['username']][$date][$result['statusId']]['class']=$class;
//					if (!$class) {
//						error_log('class:'.$class.' on '.$date);
//					}
					
				}
				$lastUser=$result['username'];
				$lastDate=$date;
			}
			
			$loginRequired=array();
			foreach ($ret as $k=>$v) {
				if (!isset($loginRequired[$k])) {
					$loginRequired[$k]=$this->isLoginRequired($k, $domainId);
				}
				for ($i=1; $i<=date('t', $timestamp); $i++) {
					$ts=mktime(0, 0, 0, date('m', $timestamp), $i, date('Y', $timestamp));
// error_log('ts:'.date('Y-m-d', $ts));
					if (isset($v[date('Y-m-d', $ts)])) {
// error_log('data setted on '.date('Y-m-d', $ts));
						if (!isset($ret[$k][date('Y-m-d', $ts)][1]['agreed']) || !isset($ret[$k][date('Y-m-d', $ts)][2]['agreed'])) {
// error_log('no agreed time in or out');
							$ret[$k][date('Y-m-d', $ts)][0]['class']='PunchMissing';
							$ret[$k][date('Y-m-d', $ts)][0]['comment']='No allocated shift';
						}
//						$this->getCorrectedTimes($ret[$k][date('Y-m-d', $ts)], $domainId);
					} else {
						if ($loginRequired[$k]) {
							// Login required, here something missing, not logged in even the shift allocated
							$uId=$this->getUserId($k);
/*
error_log('login required:'.$k);
error_log('logged in:'.((isset($ret[$k][date('Y-m-d', $ts)][1]))?('true'):('false')));
error_log('logged out:'.((isset($ret[$k][date('Y-m-d', $ts)][2]))?('true'):('false')));
if (isset($ret[$k][date('Y-m-d', $ts)][0])) {
	error_log('data:'.print_r($ret[$k][date('Y-m-d', $ts)][0], true));
} else {
	error_log('no data');
}
error_log('is allocated? '.(($this->isAllocatedShift($uId, date('Y-m-d', $ts)))?('yes'):('no')));
*/
							$arr=array(
								'userId'=>$k,
								'comment'=>'',
								'day'=>'',
								'timestamp'=>null,
								'startTime'=>null,
								'finishTime'=>null
							);
							
							$ret[$k][date('Y-m-d', $ts)][1]=$arr;
							$ret[$k][date('Y-m-d', $ts)][2]=$arr;
							if ($ts<time() && $this->isAllocatedShift($uId, date('Y-m-d', $ts))) {
								$tmpTimings=$this->getTimingsForDay($uId, $ts);
								$ret[$k][date('Y-m-d', $ts)][1]['agreed']=$tmpTimings['startTime'];
								$ret[$k][date('Y-m-d', $ts)][1]['agreedOrig']=$tmpTimings['startTime'];
								$ret[$k][date('Y-m-d', $ts)][2]['agreed']=$tmpTimings['finishTime'];
								$ret[$k][date('Y-m-d', $ts)][2]['agreedOrig']=$tmpTimings['finishTime'];
								
								$ret[$k][date('Y-m-d', $ts)][0]['agreedStart']=$tmpTimings['startTime'];
								$ret[$k][date('Y-m-d', $ts)][0]['agreedFinish']=$tmpTimings['finishTime'];
								$ret[$k][date('Y-m-d', $ts)][0]['class']='PunchMissing';
								$ret[$k][date('Y-m-d', $ts)][0]['comment']='Missing sign in/out';
							} else {
//if (isset($ret[$k][date('Y-m-d', $ts)][0]['class'])) {
//	error_log('class:'.$ret[$k][date('Y-m-d', $ts)][0]['class'].' on '.date('Y-m-d', $ts));
//} else {
//	error_log('no class on '.date('Y-m-d', $ts));
//}
								if ($ts < time()) {
									$ret[$k][date('Y-m-d', $ts)][0]['class']='PunchDayoff';
									$ret[$k][date('Y-m-d', $ts)][0]['comment']='Dayoff';
								}
							}
							$ret[$k][date('Y-m-d', $ts)][0]['userId']=$k;
//							$this->getCorrectedTimes($ret[$k][date('Y-m-d', $ts)], $domainId);
						} else {
							// Login not required, we add the shift details to sign in/out
// error_log('login not required:'.$k);
							$uId=$this->getUserId($k);
							$tmpTimings=$this->getTimingsForDay($uId, $ts);
							if ($tmpTimings && count($tmpTimings)) {
// error_log('timings:'.print_r($tmpTimings, true));

								$location=$this->getLocation($tmpTimings['locationId'], true);
								$arr0=array(
									'class'=>'PunchCorrect',
									'comment'=>'',
									'agreedStart'=>$tmpTimings['startTime'],
									'agreedFinish'=>$tmpTimings['finishTime'],
									'userId'=>$uId,
									'WorkTime'=>0,
									'Late'=>0,
									'Leave'=>0,
									'Overtime'=>0,
									'OvertimeAgreed'=>0
								);
								$arr1=array(
									'userId'=>$uId,
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
									'userId'=>$uId,
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
								$ret[$k][date('Y-m-d', $ts)][1]=$arr1;
								$ret[$k][date('Y-m-d', $ts)][2]=$arr2;
								$ret[$k][date('Y-m-d', $ts)][0]=$arr0;
								
								
							} else {
								$arr=array(
										'userId'=>$k,
										'comment'=>'',
										'day'=>'',
										'timestamp'=>null,
										'startTime'=>null,
										'finishTime'=>null
								);
									
								$ret[$k][date('Y-m-d', $ts)][1]=$arr;
								$ret[$k][date('Y-m-d', $ts)][2]=$arr;
//								$ret[$k][date('Y-m-d', $ts)][0]['class']='PunchDayoff';
//								$ret[$k][date('Y-m-d', $ts)][0]['comment']='Dayoff';
//								$ret[$k][date('Y-m-d', $ts)][0]['userId']=$k;
								
							}
						}
					}
					$this->getCorrectedTimes($ret[$k][date('Y-m-d', $ts)], $domainId);
				}
				ksort($ret[$k]);
			}
		}
//		 else
		{
			// No sign in/out data
			// If sign in/out not required, use the allocation data
error_log('no sign in/out data');			

$time=microtime(true);
			$em=$this->doctrine->getManager();
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
			
error_log('dId:'.$domainId);
				
			if (!$admin && $userId) {
				$qb->andWhere('u.id=:uId')
					->setParameter('uId', $userId);
error_log('uId:'.$userId);
			}
//			if ($admin && $groupId) {
//				$qb->andWhere('u.groupId=:gId')
//					->setParameter('gId', $groupId);
//error_log('gId:'.$groupId);
//			}
//			if ($usersearch) {
//				$qb->andWhere('u.username LIKE :uSearch OR u.firstName LIKE :uSearch OR u.lastName LIKE :uSearch')
//					->setParameter('uSearch', '%'.$usersearch.'%');
//error_log('uSearch:'.$usersearch);
//			}
			if ($selectedUserId) {
				if ($selectedUserId > 0) {
					$qb->andWhere('u.id=:userId')
						->setParameter('userId', $selectedUserId);
error_log('selectedUserId:'.$selectedUserId);
				}
			} else {
				// if not selected any user, result should be empty
				$qb->andWhere('u.id<0');
			}
			if (isset($availableUsers) && is_array($availableUsers) && count($availableUsers)) {
				$qb->andWhere('u.id IN (\''.implode('\',\'', array_keys($availableUsers)).'\')');
			}
//			if ($admin && $locationId) {
//				$qb->andWhere('u.locationId=:lId')
//					->setParameter('lId', $locationId);
//error_log('lId:'.$locationId);
//			}
			if ($timestamp) {
				$startTime=date('Y-m-01', $timestamp);
				$finishTime=date('Y-m-t', $timestamp);
				$qb->andWhere('a.date BETWEEN :dateStart AND :dateFinish')
					->andWhere('c.csd<=:dateStart')
					->andWhere('c.ced>=:dateStart OR c.ced IS NULL')
					->setParameter('dateStart', $startTime)
					->setParameter('dateFinish', $finishTime);
error_log('dateStart:'.print_r($startTime, true));
error_log('dateFinish:'.print_r($finishTime, true));
			}
			$query=$qb->getQuery();
error_log('2 sql:'.$query->getDql());
			$users=$query->getArrayResult();
error_log('1st results:'.count($users).', time:'.(microtime(true)-$time));
			if (!count($users)) {
				$users=array();
				if ($availableUsers) {
					foreach ($availableUsers as $au) {
						$totalUsers++;
						if (count($users) < 10) {
							if ($selectedUserId == -1 || $selectedUserId == $au['id']) {
error_log('user added:'.$au['username']);
								$users[]=array('id'=>$au['id'], 'username'=>$au['username']);
							}
						}
					}
				}
				
/*				
				$find=array();
				$find+=array('domainId'=>$domainId);
				if ($admin) {
					if ($locationId) {
						$find+array('locationId'=>$locationId);
					}
					if ($groupId) {
						$find+array('groupId'=>$groupId);
					}
				} else {
					$find+=array('id'=>$userId);
				}
				$findUsers=$this->doctrine
					->getRepository('TimesheetHrBundle:User')
					->findBy($find, array('firstName'=>'ASC', 'lastName'=>'ASC'));

error_log('find:'.print_r($find, true));
error_log('findUsers:'.print_r($findUsers, true));
				unset($users);
				$users=array();
				if ($findUsers) {
					foreach ($findUsers as $fu) {
						if (count($users) < 20) {
							$users[]=array('id'=>$fu->getId(), 'username'=>$fu->getUsername());
						}
					}
				}
*/
				
			} else {
				$totalUsers=count($users);
			}

			$holidays=array();
			foreach ($users as $uTmp) {
			//
			//
			//
				$userId=$uTmp['id'];
				$username=$uTmp['username'];
				$ts=strtotime($startTime);
				$d=strtotime($finishTime);
				if ($userId && !isset($holidays[$userId])) {
					$holidays[$userId]=$this->getHolidaysForMonth($conn, $userId, $startTime, $finishTime);
// error_log('holidays:'.count($holidays[$userId]));
				}
				// error_log('userId:'.$userId.', login:'.(($this->isLoginRequired($username))?'yes':'no'));
				if ($userId && !$this->isLoginRequired($username, $domainId)) {
//				if ($userId) {
					// but we have userId
					
					while ($ts <= $d) {
error_log('ts:'.$ts.'='.date('Y-m-d', $ts).', d:'.$d.'='.date('Y-m-d', $d));
						$tmpTimings=$this->getTimingsForDay($userId, $ts);
						if (!isset($ret[$username][date('Y-m-d', $ts)][0])) {
error_log('not exists '.$username.' '.date('Y-m-d', $ts));
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
									'class'=>'PunchCorrect',
									'comment'=>'',
									'agreedStart'=>$agreedStartTime,
									'agreedFinish'=>$agreedFinishTime,
									'userId'=>$userId,
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
								
								$ret[$username][date('Y-m-d', $ts)][1]=$arr1;
								$ret[$username][date('Y-m-d', $ts)][2]=$arr2;
								$ret[$username][date('Y-m-d', $ts)][0]=$arr0;
								
								$this->getCorrectedTimes($ret[$username][date('Y-m-d', $ts)], $domainId);
								
							} else {
								$arr=array(
										'userId'=>$userId,
										'comment'=>'',
										'day'=>'',
										'timestamp'=>null,
										'startTime'=>null,
										'finishTime'=>null
								);
									
								$ret[$username][date('Y-m-d', $ts)][1]=$arr;
								$ret[$username][date('Y-m-d', $ts)][2]=$arr;
								$ret[$username][date('Y-m-d', $ts)][0]['class']='PunchDayoff';
								$ret[$username][date('Y-m-d', $ts)][0]['comment']='Dayoff';
								$ret[$username][date('Y-m-d', $ts)][0]['userId']=$userId;
								
							}
						}
						$ts=mktime(0, 0, 0, date('n', $ts), date('j', $ts)+1, date('Y', $ts));
					}

				} else {
error_log('login not required');					
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
							}
							$arr0=array(
									'class'=>'PunchCorrect',
									'comment'=>'',
									'agreedStart'=>$agreedStartTime,
									'agreedFinish'=>$agreedFinishTime,
									'userId'=>$userId,
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
							
							$ret[$username][date('Y-m-d', $ts)][1]=$arr1;
							$ret[$username][date('Y-m-d', $ts)][2]=$arr2;
							$ret[$username][date('Y-m-d', $ts)][0]=$arr0;
// error_log('2');							
							$this->getCorrectedTimes($ret[$username][date('Y-m-d', $ts)], $domainId);
/*
						} else {
error_log('no holidays...');
							if ($tmpTimings && count($tmpTimings)) {
								$arr=array(
										'userId'=>$userId,
										'comment'=>'',
										'day'=>'',
										'timestamp'=>null,
										'startTime'=>null,
										'finishTime'=>null
								);
								
								$ret[$username][date('Y-m-d', $ts)][1]=$arr;
								$ret[$username][date('Y-m-d', $ts)][2]=$arr;
								$ret[$username][date('Y-m-d', $ts)][0]['class']='PunchMissing';
								$ret[$username][date('Y-m-d', $ts)][0]['comment']='';
								$ret[$username][date('Y-m-d', $ts)][0]['userId']=$userId;
								
								
							} else {
								$arr=array(
									'userId'=>$userId,
									'comment'=>'',
									'day'=>'',
									'timestamp'=>null,
									'startTime'=>null,
									'finishTime'=>null
								);
										
								$ret[$username][date('Y-m-d', $ts)][1]=$arr;
								$ret[$username][date('Y-m-d', $ts)][2]=$arr;
								$ret[$username][date('Y-m-d', $ts)][0]['class']='PunchDayoff';
								$ret[$username][date('Y-m-d', $ts)][0]['comment']='Dayoff';
								$ret[$username][date('Y-m-d', $ts)][0]['userId']=$userId;
							}
							
							$this->getCorrectedTimes($ret[$username][date('Y-m-d', $ts)], $domainId);
*/
						}
						$ts=mktime(0, 0, 0, date('n', $ts), date('j', $ts)+1, date('Y', $ts));
					}
					
				}
			}
		}
// error_log('2 memory:'.memory_get_usage());
// error_log('users : '.count($users).' of '.$totalUsers);	
		unset($users);
		unset($results);
		unset($holidays);
		unset($loginRequired);
// error_log('3 memory:'.memory_get_usage());
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
				$results=$qb->getQuery()->getArrayResult();
				
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
	
	
	public function getCorrectedTimes(&$data, $domainId) {
error_log('getCorrectedTimes');
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

		$lunchtimeUnpaid=$this->getConfig('lunchtimeUnpaid', $domainId);
		$lunchtimePaid=$this->getConfig('lunchtime', $domainId);
		$minTimeForLunch=60*$this->getConfig('minhoursforlunch');
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

		if (isset($data[0]['holidays']) && count($data[0]['holidays'])) {
// error_log('data0:'.print_r($data[0], true));
// error_log('data1:'.print_r($data[1], true));
// error_log('data2:'.print_r($data[2], true));
			foreach ($data[0]['holidays'] as $h) {
				switch ($h['typeId']) {
					case 3 : {
error_log('sick leave');
						$data[0]['agreedStart']=((isset($data[1]['agreedOrig']))?($data[1]['agreedOrig']->format('Y-m-d H:i:s')):(''));
						$data[1]['agreed']=((isset($data[1]['agreedOrig']))?($data[1]['agreedOrig']->format('Y-m-d H:i:s')):(''));
						$data[0]['agreedFinish']=((isset($data[2]['agreedOrig']))?($data[2]['agreedOrig']->format('Y-m-d H:i:s')):(''));
						$data[2]['agreed']=((isset($data[2]['agreedOrig']))?($data[2]['agreedOrig']->format('Y-m-d H:i:s')):(''));
						break;
					}
					case 4 : {
error_log('time off');
						$d1=$h['start']->getTimestamp();
						$d2=$h['finish']->getTimestamp();
						$timeoff+=($d2-$d1)/60;
						break;
					}
				}
			}
		}
		
		if (isset($data[1]['agreed']) && $data[1]['agreed'] && isset($data[2]['agreed']) && $data[2]['agreed']) {
			
			if (!is_object($data[1]['agreed'])) {
				$data[1]['agreed']=new \DateTime($data[1]['agreed']);
			}
			if (!is_object($data[2]['agreed'])) {
				$data[2]['agreed']=new \DateTime($data[2]['agreed']);
			}
			$d1=strtotime($data[1]['agreed']->format('H:i:s'));
			$d2=strtotime($data[2]['agreed']->format('H:i:s'));
				
			if ($data[2]['agreed']->format('His') <= $data[1]['agreed']->format('His')) {
				// finish next day
				$data[2]['agreed']->modify('+1 day');
			}
			if (isset($data[2]['agreedOrig']) || isset($data[1]['agreedOrig'])) {
				if (isset($data[2]['agreedOrig'])) {
					$d2=$data[2]['agreedOrig']->getTimestamp();
				}	
				if (isset($data[1]['agreedOrig'])) {
					$d1=$data[1]['agreedOrig']->getTimestamp();
				}
				$ret['AgreedTimeOrig']=($d2-$d1)/60;
				if ($ret['AgreedTimeOrig'] >= $minTimeForLunch) {
					$ret['AgreedTimeOrig'] -= $lunchtimeUnpaid;
				}
			}
			$ret['AgreedTime']=($data[2]['agreed']->getTimestamp()-$data[1]['agreed']->getTimestamp())/60-$timeoff;
			if ($ret['AgreedTime'] >= $minTimeForLunch) {
				$ret['AgreedTime'] -= $lunchtimeUnpaid;
				$deducted += $lunchtimeUnpaid+$timeoff;
			}
			if (isset($data[1]['timestamp']) && $data[1]['timestamp'] && isset($data[2]['timestamp']) && $data[2]['timestamp']) {
				$date1=date('Y-m-d', strtotime($data[1]['timestamp']));
				if ($data[1]['agreed']->format('H:i:s') > $data[2]['agreed']->format('H:i:s')) {
					$date2=date('Y-m-d', strtotime($data[1]['timestamp']));
				} else {
					$d3=strtotime($data[1]['timestamp']);
					$date2=date('Y-m-d', mktime(0, 0, 0, date('n', $d3), date('j', $d3)+1, date('Y', $d3)));
				}

				$d1=max(strtotime($data[1]['timestamp']), strtotime($date1.' '.$data[1]['agreed']->format('H:i:s')));
				$d2=min(strtotime($data[2]['timestamp']), strtotime($date2.' '.$data[2]['agreed']->format('H:i:s')));
				if (date('H:i', $d1).':00' > $data[1]['agreed']->format('H:i:s')) {
					$late=(strtotime(date('Y-m-d H:i:s', $d1))-strtotime($data[1]['agreed']->format('Y-m-d H:i:s')))/60;
					// less than 5 minutes is acceptable
					// if more than 5 minutes, round up to the next 15 minutes
					if ($late < 5) {
						$late=0;
						$ret['LateLess']++;
					} elseif ($late % 15 > 5) {
						$late=15*ceil($late/15);
					} else {
						$late=16*(int)($late/15);
					}
					if ($late > 0) {
						$ret['LateMore']++;
					}
					$ret['Late']=$late;
					$deducted+=$late;
				}

				if (date('H:i', $d2).':00' < $data[2]['agreed']->format('H:i:s')) {
					$leave=(strtotime($data[2]['agreed']->format('H:i:s'))-strtotime(date('H:i:s', $d2)))/60;
					// less than 5 minutes is acceptable
					// if more than 5 minutes, round up to the next 15 minutes
					if ($leave < 5) {
						$leave=0;
						$ret['LeaveLess']++;
					} elseif ($leave % 15 > 5) {
						$leave=15*ceil($leave/15);
					} else {
						$leave=16*(int)($leave/15);
					}
					if ($leave > 0) {
						$ret['LeaveMore']++;
					}
					$ret['Leave']=$leave;
					$deducted+=$leave;
				}
				$ret['WorkTime']=$ret['AgreedTime']-$ret['Late']-$ret['Leave'];
				$ret['SignedIn']=date('H:i', $d1);
				$ret['SignedOut']=date('H:i', $d2);
				// Overtime calculation
				$overtime=0;
				if (date('H:i', $d1).':00' < $data[1]['agreed']->format('H:i:s')) {
					$overtime+=(strtotime($data[1]['agreed']->format('H:i:s'))-strtotime(date('H:i:s', $d1)))/60;
				}
				if (date('H:i', $d2).':00' > $data[2]['agreed']->format('H:i:s')) {
					$overtime+=(strtotime(date('H:i:s', $d2))-strtotime($data[2]['agreed']->format('H:i:s')))/60;
				}
				$ret['Overtime']=$overtime;
				if (isset($ret['AgreedTime']) && isset($ret['AgreedTimeOrig']) && $ret['AgreedTime'] > $ret['AgreedTimeOrig']) {
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

		$results=$qb->getQuery()->getArrayResult();
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
	
	
	public function getHolidaysForMonth($conn, $userId, $startDate, $finishDate) {
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
		$results=$qb->getQuery()->getArrayResult();
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
// error_log('getHolidaysForMonth userId:'.$userId);
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
		
		$results=$qb->getQuery()->getArrayResult();
/*		
		$conn=$this->doctrine->getConnection();
		
		$query='SELECT'.
			' `r`.`id`'.
			' FROM `Requests` `r`'.
				' JOIN `RequestType` `rt` ON `r`.`typeId`=`rt`.`id`'.
			' WHERE `r`.`accepted`=1'.
				' AND `rt`.`fullday`'.
				' AND `r`.`userId`=:uId'.
				' AND :date BETWEEN DATE(`r`.`start`) AND DATE(`r`.`finish`)';

		$stmt=$conn->prepare($query);
		$stmt->bindValue('date', $date);
		$stmt->bindValue('uId', $userId);
		$stmt->execute();
		
		$results=$stmt->fetch();
*/		
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
			
			$results=$qb->getQuery()->getArrayResult();
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
error_log('getTimingsForDay, userId:'.$userId.', ts:'.$timestamp.', date:'.date('Y-m-d', $timestamp));
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

			$results=$qb->getQuery()->getArrayResult();
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
		
		$results=$qb->getQuery()->getArrayResult();
/*		
		$conn=$this->doctrine->getConnection();
		
		$query='SELECT'.
			' `r`.*,'.
			' `rt`.`name` as `requestname`,'.
			' `rt`.`comment` as `requestcomment`,'.
			' `rt`.`fullday`,'.
			' `rt`.`paid`,'.
			' `rt`.`initial`,'.
			' `rt`.`entitlement`,'.
			' `u`.`username`,'.
			' `u`.`firstName`,'.
			' `u`.`lastName`,'.
			' (SELECT CONCAT(`u1`.`firstName`, " ", `u1`.`lastName`) FROM `Users` `u1` WHERE `u1`.`id`=`r`.`createdBy`) as `createdByName`'.
			' FROM `Requests` `r`'.
				' JOIN `RequestType` `rt` ON `r`.`typeId`=`rt`.`id`'.
				' JOIN `Users` `u` ON `r`.`userId`=`u`.`id`'.
			' WHERE (`r`.`start`>=:date OR `r`.`finish`>=:date)'.
			(($userId)?(' AND `r`.`userId`=:uId'):('')).
			' ORDER BY `r`.`start`, `u`.`firstName`, `u`.`lastName`';
		
// error_log($query);
		$stmt=$conn->prepare($query);
		if ($userId) {
// error_log('userId:'.$userId);
			$stmt->bindValue('uId', $userId);
		}
		$stmt->bindValue('date', date('Y-m-d').' 00:00:00');
		$stmt->execute();
		$results=$stmt->fetchAll();
*/		
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
		
		return $result->getId();
				
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

		return $query->getArrayResult();
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
		
		$total=$qb->getQuery()->getArrayResult();
		
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
			'headers'=>$query->getArrayResult());
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
		
		return $query->getArrayResult();
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
		
		return $query->getSingleScalarResult();
	}
	
	
	public function getNextShift($userId) {
	
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
			->orderBy('s.startTime', 'ASC')
			->setParameter('userId', $userId)
			->setParameter('date', date('Y-m-d'));
	
		$query=$qb->getQuery();

		return $query->getArrayResult();
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
// error_log('domain:'.$domainId);
// error_log('lastContract:'.print_r($lastContract, true));
		$ahew=$lastContract['ahew'];
		if (!$ahew) {
			$ahew=$this->getDomainAHEW($domainId);
		}
		if (!$ahew) {
			$ahew=$this->getConfig('ahew', $domainId);
		}

		// usually 12.07% of working hours/days/weeks/months
		$p=(52/(52-$ahew)-1)*100;
		if (!$lastContract['hct']) {
			$lastContract['hct']=$this->getDefaultHolidayCalculation($domainId);
		}
		if (!$lastContract['hct']) {
			$lastContract['hct']=$this->getConfig('hct', null);
		}

		switch ($lastContract['hct']) {
			case 0 : {
				// Company default
				// This should not happen
//				error_log('no default holiday calculation type'); 
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
				$avgData=$this->getAverageWorkingHours($userId, ($timestamp?$timestamp:time()));
				if ($avgData['days'] > 0) {
					$ahe=($p*$avgData['hours']/100)/$avgData['days'];
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
			$results=$query->getArrayResult();
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
		$today=mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp), date('Y', $timestamp));
		$contracts=$this->getContracts($userId);
		$csd=null;
		if ($contracts && count($contracts)) {
			foreach ($contracts as $contract) {
				if ($csd==null || $contract['csd']<date('Y-m-d', $csd)) {
					$csd=strtotime($contract['csd']->format('Y-m-d H:i:s'));
				}
			}
		}
		if ($csd) {
			$ts=mktime(0, 0, 0, date('n', $csd), date('j', $csd), date('Y', $csd));
			
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
				}
				$ts=mktime(0, 0, 0, date('n', $ts), date('j', $ts)+1, date('Y', $ts));
			}
		}
		if ($totalDays > 0) {
			$ret=array(
				'hours'=>$totalWhr,
				'dailyhours'=>$totalWhr/$totalDays,
				'days'=>$totalDays
			);
		} else {
			$ret=array(
				'hours'=>0,
				'dailyhours'=>0,
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
		$result=$query->getArrayResult();

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
		$results=$query->getArrayResult();

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
		$results=$query->getArrayResult();

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
		$results=$query->getArrayResult();
		$religions=array();
		if ($results && count($results)) {
			foreach ($results as $result) {
				$religions[$result['id']]=$result['name'];
			}
		}

		return $religions;
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
		$results=$query->getArrayResult();
		
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
		$results=$query->getArrayResult();
		
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
		$results=$query->getArrayResult();
		
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
		$results=$query->getArrayResult();
			
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
		$results=$query->getArrayResult();
		
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
		$result=$this->doctrine
			->getRepository('TimesheetHrBundle:Companies')
			->findOneBy(array('id'=>$this->getDomainId($request->getHttpHost())));
		 
		return $title.(($result && count($result))?(' - '.$result->getCompanyname()):(''));
	}
	
	
	public function isDailyScheduleProblem($locationId, $date) {
error_log('isDailyScheduleProblem');
// error_log('locationId:'.$locationId.', date:'.$date);
		$required=$this->getCurrentlyRequiredStaff($locationId, $date);
		$allocated=$this->getCurrentlyAllocatedStaff($locationId, $date);
		$requiredQualifications=$this->getCurrentlyRequiredQualifications($locationId, $date);
		$allocatedQualifications=$this->getCurrentlyAllocatedQualifications($locationId, $date);
// error_log('required qual:'.print_r($requiredQualifications, true));
// error_log('allocated qual:'.print_r($allocatedQualifications, true));

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
	
}
