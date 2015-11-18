<?php

/*
 * Author: Imre Incze
 */

namespace Timesheet\Bundle\HrBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Timesheet\Bundle\HrBundle\Entity\Config;
use Timesheet\Bundle\HrBundle\Entity\Groups;
use Timesheet\Bundle\HrBundle\Entity\Info;
use Timesheet\Bundle\HrBundle\Entity\Location;
use Timesheet\Bundle\HrBundle\Entity\Requests;
use Timesheet\Bundle\HrBundle\Entity\Status;
use Timesheet\Bundle\HrBundle\Entity\SwapRequest;
use Timesheet\Bundle\HrBundle\Entity\Timing;
use Timesheet\Bundle\HrBundle\Entity\User;
use Timesheet\Bundle\HrBundle\Form\Type\HolidayRequestType;
use Timesheet\Bundle\HrBundle\Form\Type\PhotoNotesType;
use Timesheet\Bundle\HrBundle\Form\Type\SwapRequestType;
use Timesheet\Bundle\HrBundle\Form\Type\TimesheetCheckType;
use Symfony\Component\Validator\Validator;
use Timesheet\Bundle\HrBundle\TimesheetHrBundle;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation;
use \Symfony\Component\Validator\Constraints\DateTime;
// use Timesheet\Bundle\HrBundle\Entity\Timesheet\Bundle\HrBundle\Entity;
// use Timesheet\Bundle\HrBundle\Form\Type\Timesheet\Bundle\HrBundle\Form\Type;


class AjaxController extends Controller
{

    public function userinfoAction($id) {

		$request=$this->getRequest();
		if ($request->isXmlHttpRequest()) {
	    	$data=array(
	    		'content'=>''
	    	);

	    	$userManager = $this->container->get('fos_user.user_manager');
	    	$functions = $this->get('timesheet.hr.functions');
	    	
	    	$user=$userManager->findUserBy(array('id'=>$id));
	    	
	    	if ($user) {

	    		$data['content'].=$this->renderView('TimesheetHrBundle:Ajax:userinfo.html.twig', array(
	    			'user' 		=> $user,
	    			'roles'		=> $functions->getAvailableRoles(),
	    			'groups'	=> $functions->getGroups(),
	    			'locations'	=> $functions->getLocation(),
	    			'titles'	=> $functions->getTitles(),
	    			'countries' => Intl::getRegionBundle()->getCountryNames()
	    		));
	    	}
	    		    	
	    	return new JsonResponse($data);
		} else {
			error_log('not ajax request...');
				
			return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
		}
    }


    public function userlistAction() {
error_log('userlistAction');
		$request=$this->getRequest();
		if ($request->isXmlHttpRequest()) {
	    	$data=array(
	    		'content'=>''
	    	);

    		$functions=$this->get('timesheet.hr.functions');
    		$params=$request->request->all();
    			    
    		$securityContext = $this->container->get('security.context');
    		$sysadmin=false;
    		if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    			// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
    			if (TRUE === $securityContext->isGranted('ROLE_SYSADMIN')) {
    				// we don't need to check domain is sysadmin logged in
    				$sysadmin=true;
    			}
    		}
    		if ($sysadmin) {
    			$domainId=null;
    		} else {
    			$domainId=$functions->getDomainId($request->getHttpHost());
    		}
    		
    		$name=$params['name'];
    		$group=((isset($params['group']))?($params['group']):(''));
    		$domain=((isset($params['domain']))?($params['domain']):(''));
// error_log('domain:'.$domain);
    		$qualification=((isset($params['qualification']))?($params['qualification']):(''));
    		$base=$params['base'];
    		$listType=$params['listType'];
    		
    		$users=$functions->getUsersList(null, (strlen($name)?$name:null), false, (strlen($group)?$group:null), (strlen($qualification)?$qualification:null), null, true, (($domain)?($domain):($domainId)));
    		if (isset($users[-1]['found'])) {
    			$found=$users[-1]['found'];
    			unset($users[-1]);
    		} else {
    			$found=count($users);
    		}
    		$session=$this->get('session');
   			$session->set('userSearch', $name);
   			$session->set('groupSearch', $group);
   			$session->set('qualificationSearch', $qualification);
   			$calendar=array(
   				'usersearch'=>$name,
   				'groupsearch'=>$group,
   				'qualificationsearch'=>$qualification
   			);
   			$session->set('calendar', $calendar);

    		$template=null;
    		
    		switch ($listType) {
    			case 1 : {
    				$template='TimesheetHrBundle:Internal:usersList.html.twig';
    				break;
    			}
    			case 2 : {
    				$template='TimesheetHrBundle:Internal:usersListSchedule.html.twig';
    				break;
    			}
    			default : {
    				error_log('wrong list type');
    				break;
    			}
    		}
    		
    		if ($template) {
    			
    			$data['content'].=$this->renderView($template, array(
					'base'	=> $base,
    				'users'	=> $users,
    				'found' => $found,
    				'AHE'	=> $functions->getConfig('ahe', $domainId),
    				'AHEW'	=> $functions->getConfig('ahew', $domainId),
    				'holidaycalculations'	=> $functions->getHolidayCalculations($domainId),
    				'hct'	=> $functions->getConfig('hct', $domainId),
    				'lunchtime'	=> $functions->getConfig('lunchtime', $domainId),
    				'lunchtimeUnpaid'	=> $functions->getConfig('lunchtimeUnpaid', $domainId),
   					'domainId'		=> $domainId,
   					'domains'		=> (($domainId)?(null):($functions->getDomains()))
    						
	    		));
    		}
	    	
	    	return new JsonResponse($data);
		} else {
			error_log('not ajax request...');
				
			return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
		}
    }
    
    
    function scheduleAction() {

    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$data=array(
    			'error'=>'',
    			'content'=>'',
    			'location'=>'',
    			'js'=>'',
    			'showhide'=>''
    		);
    	
    		$functions=$this->get('timesheet.hr.functions');
    		$params=$request->request->all();
    	
    		$action=$params['action'];
    		switch ($action) {
    			case 'add' : {
		    		$date=substr($params['date'], 0, 4).'-'.substr($params['date'], 4, 2).'-'.substr($params['date'], 6, 2);
		    		$locationId=$params['locationId'];
		    		$shiftId=$params['shiftId'];
		    		$userId=$params['userId'];
		    		
		    		$data['error']=$functions->allocateUserToSchedule($date, $locationId, $shiftId, $userId);
		    		$data['content']=$functions->getAllocationList($date, $locationId, $shiftId);
		    		$data['location']=$functions->getAllocationForLocation($locationId, $date);
		    		$data['showhide']='rs_showhide|rq_showhide';
		    		$data['dayId']=date('w', strtotime($date));
		    		$data['dayProblem']=$functions->isDailyScheduleProblem($locationId, $date);
    				break;
    			}
    			case 'remove' : {
		    		$date=substr($params['date'], 0, 4).'-'.substr($params['date'], 4, 2).'-'.substr($params['date'], 6, 2);
		    		$locationId=$params['locationId'];
		    		$shiftId=$params['shiftId'];
		    		$userId=$params['userId'];
		    		
		    		$data['error']=$functions->removeUserFromSchedule($date, $locationId, $shiftId, $userId);
		    		$data['content']=$functions->getAllocationList($date, $locationId, $shiftId);
		    		$data['location']=$functions->getAllocationForLocation($locationId, $date);
		    		$data['showhide']='rs_showhide|rq_showhide';
		    		$data['dayId']=date('w', strtotime($date));
		    		$data['dayProblem']=$functions->isDailyScheduleProblem($locationId, $date);
		    		break;
    			}
    		    case 'clean' : {
		    		$locationId=$params['locationId'];
		    		$timestamp=$params['timestamp'];
    		
		    		$data['error']=$functions->cleanSchedule($locationId, $timestamp);
	   				break;
    			}
				case 'copy' : {
		    		$locationId=$params['locationId'];
		    		$timestamp=$params['timestamp'];
    		
		    		$data['error']=$functions->copySchedule($locationId, $timestamp, '-7');
	   				break;
    			}
    			case 'copyback' : {
		    		$locationId=$params['locationId'];
		    		$timestamp=$params['timestamp'];
    		
		    		$data['error']=$functions->copySchedule($locationId, $timestamp, '+7');
	   				break;
    			}
    			case 'fill' : {
		    		$locationId=$params['locationId'];
		    		$timestamp=$params['timestamp'];
    		
		    		$data['error']=$functions->fillSchedule($locationId, $timestamp);
	   				break;
    			}
    		    case 'publish' : {
		    		$locationId=$params['locationId'];
		    		$timestamp=$params['timestamp'];
    		
		    		$data['error']=$functions->publishSchedule($locationId, $timestamp, 1);
	   				break;
    			}
    		    case 'unpublish' : {
		    		$locationId=$params['locationId'];
		    		$timestamp=$params['timestamp'];
    		
		    		$data['error']=$functions->publishSchedule($locationId, $timestamp, 0);
	   				break;
    			}
				case 'report1' : {
		    		$locationId=$params['locationId'];
		    		$timestamp=$params['timestamp'];
    		
		    		$data['js']='self';
		    		$data['url']=$this->generateUrl('timesheet_hr_weeklylocationreport', array('timestamp'=>$timestamp, 'location'=>$locationId, 'type'=>''));
		    		$data['error']='';
	   				break;
    			}
    			case 'report2' : {
		    		$locationId=$params['locationId'];
		    		$timestamp=$params['timestamp'];
    		
		    		$data['js']='self';
		    		$data['url']=$this->generateUrl('timesheet_hr_weeklylocationreport', array('timestamp'=>$timestamp, 'location'=>$locationId, 'type'=>'Default'));
		    		$data['error']='';
	   				break;
    			}
    		}

    		return new JsonResponse($data);;
    		 
   		} else {
			error_log('not ajax request...');
				
			return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
		}
    }
    
    
    function holidayAction() {

    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$data=array(
    			'error'=>'',
    			'content'=>'',
    			'location'=>''
    		);
    	
//    		$functions=$this->get('timesheet.hr.functions');
    		$params=$request->request->all();
// error_log('params:'.print_r($params, true));
    		$action=$params['action'];
    		switch ($action) {
    			case 'add' : {
		    		$data['error']=''; // add new holiday request';
		    		$data['content']='';
		    		$data['js']='addrequest';
		    		$data['url']=$this->generateUrl('timesheet_ajax_addrequest');
		    		$data['date']=date('Y-m-d', $params['timestamp']);
		    		$data['action']=$params['action'];
		    		$data['base']=((isset($params['base']))?($params['base']):(''));
		    		$data['location']='';
    				break;
    			}
				case 'swap' : {
		    		$data['error']=''; // swap shifts';
		    		$data['content']='';
		    		$data['js']='swaprequest';
		    		$data['url']=$this->generateUrl('timesheet_ajax_swaprequest');
		    		$data['date']=date('Y-m-d', $params['timestamp']);
		    		$data['action']=$params['action'];
		    		$data['base']=((isset($params['base']))?($params['base']):(''));
		    		$data['location']='';
    				break;
    			}
    		}

    		return new JsonResponse($data);;
    		 
   		} else {
			error_log('not ajax request...');
				
			return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
		}
    }
    
    
    function allocationAction($date, $locationId, $shiftId) {

    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
// no need now
    		$data=array();
    		
    		return new JsonResponse($data); 
    	} else {
    		
    		if (strpos($date, '-')===false) {
    			$date=substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6, 2);
    		}
    		$functions=$this->get('timesheet.hr.functions');
    		
    		$allocated=$functions->getAllocationList($date, $locationId, $shiftId);
   		
    		
    		return new Response($allocated, 200);
    	}
    }

    
    public function showrequestsAction() {
    
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$data=array(
   				'content'=>'',
   				'title'=>'List of Requests'
    		);
    
    		$params=$request->request->all();
    
    		$base=$params['base'];
    		$userId=((isset($params['userid']))?($params['userid']):(''));
    
    		$functions = $this->get('timesheet.hr.functions');

    		$list=$functions->getFutureRequests($userId);
// error_log('list:'.print_r($list, true));    		
    	    $data['content']=$this->renderView('TimesheetHrBundle:Ajax:requestlist.html.twig', array(
    			'base'	=> $base,
    	    	'list'	=> $list
    		));

    	    return new JsonResponse($data);
    	} else {
    		error_log('not ajax request...');
    
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
    	}
    }
    
    
    public function addrequestAction() {
error_log('addrequestAction');    
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$data=array(
   				'content'=>'',
    			'title'=>'Request'
    		);

    		$params=$request->request->all();
// error_log('params:'.print_r($params, true));
    		$base=$params['base'];
    		$date=$params['date'];
    		$refresh=((isset($params['refresh']))?($params['refresh']):(null));
    		$action=$params['action'];
    		$id=((isset($params['id']))?($params['id']):(''));
    		$functions = $this->get('timesheet.hr.functions');
    		$domainId=$functions->getDomainId($request->getHttpHost());
error_log('domainId:'.$domainId);
    		
    		    		
    		switch ($action) {
    			case 'add' : {
error_log('add');    				
		    		$holiday=new Requests();
		    		
		    		$holiday->setStart(new \DateTime($date.' 00:00:00'));
		    		$holiday->setFinish(new \DateTime($date.' 23:59:59'));
		
		    		$usernames=array();
		    		$admin=false;
		    		$groupId=null;
		    		$qualificationId=null;
		    		$locationId=null;
		    		
    			    $securityContext = $this->container->get('security.context');
    	
    				if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    					// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
    					if (TRUE === $securityContext->isGranted('ROLE_ADMIN')) {
							$admin=true;
    					} elseif (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
    						$currentUser=$this->getUser();
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
		    		
		    		if ($admin) {
error_log('admin');
						$users=$functions->getUsersList(null, null, true, $groupId, $qualificationId, $locationId, false, $domainId);
			    		if ($users) {
			    			foreach ($users as $k=>$v) {
								if ($k>=0) {
			    					$usernames[$v['id']]=trim($v['firstName'].' '.$v['lastName']).' ('.$v['username'].')';
								}
			    			}
			    		}
		    		} else {
		    			$currentUser=$this->getUser();
		    			$data['title'].=' for '.trim($currentUser->getTitle().' '.$currentUser->getFirstName().' '.$currentUser->getLastName());
		    		}
		    		
		    		$holidayForm=$this->createForm(new HolidayRequestType($holiday, $functions->getRequestTypes(), $usernames, $this->generateUrl('timesheet_ajax_handleholidayrequest'))); 
		    		
		    		$holidayForm->handleRequest($request);
		    		
		    		if ($holidayForm->isValid()) {
// error_log('valid');		    			
		    			if ($holidayForm->isSubmitted()) {
// error_log('submitted');
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    		}
		    
		   			$data['content']=$this->renderView('TimesheetHrBundle:Ajax:holidayrequest.html.twig', array(
						'holidayrequestform' => $holidayForm->createView(),
		   				'holidayTypes'=> $functions->getRequestTypes()
		    		));
		   			break;
		    	}

    		    case 'edit' : {
		    		$holiday=$this->getDoctrine()
		    			->getRepository('TimesheetHrBundle:Requests')
		    			->findOneBy(array('id'=>$id));
		    		
		    		$usernames=array();
		    		$admin=false;
		    		$groupId=null;
		    		$qualificationId=null;
		    		$locationId=null;
		    		
    			    $securityContext = $this->container->get('security.context');
    			    $currentUser=$this->getUser();
    			    
    				if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    					// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
    					if (TRUE === $securityContext->isGranted('ROLE_ADMIN')) {
							$admin=true;
    					} elseif (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
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
		    		
		    		if ($admin) {
			    		$users=$functions->getUsersList(null, null, true, $groupId, $qualificationId, $locationId, false, $functions->getDomainId($request->getHttpHost()));
			    		if ($users) {
			    			foreach ($users as $k=>$v) {
								if ($k>=1) {
			    					$usernames[$v['id']]=trim($v['firstName'].' '.$v['lastName']).' ('.$v['username'].')';
								}
			    			}
			    		}
		    		}
		    		
		    		$holidayForm=$this->createForm(new HolidayRequestType($holiday, $functions->getRequestTypes(), $usernames, $this->generateUrl('timesheet_ajax_handleholidayrequest'))); 
		    		
		    		$holidayForm->handleRequest($request);
		    		
		    		if ($holidayForm->isValid()) {
		    			if ($holidayForm->isSubmitted()) {
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    		}
		    
		   			$data['content']=$this->renderView('TimesheetHrBundle:Ajax:holidayrequest.html.twig', array(
						'holidayrequestform' => $holidayForm->createView(),
		   				'holidayTypes'=> $functions->getRequestTypes()
		    		));
		   			break;
    			}

    			case 'confirm' :
    			case 'confirmnew' : {
// error_log('confirm/new');
    				$data['title']='List of requests';
    				$requests=array();
    				$accepted=array();

    				$admin=false;
    				$groupId=null;
    				$locationId=null;
    				$securityContext = $this->container->get('security.context');
    				$currentUser=$this->getUser();
    				    	
    				if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    					// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
    					if (TRUE === $securityContext->isGranted('ROLE_ADMIN')) {
							$admin=true;
    					} elseif (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
    						
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
    				$em=$this->getDoctrine()->getManager();
    				
    				$qb=$em->createQueryBuilder();
    				$qb->select('r.id')
    					->addSelect('r.userId')
    					->addSelect('r.typeId')
    					->addSelect('r.start')
    					->addSelect('r.finish')
    					->addSelect('r.comment')
    					->addSelect('r.createdBy')
    					->addSelect('r.createdOn')
    					->addSelect('r.accepted')
    					->addSelect('r.acceptedOn')
    					->addSelect('r.acceptedBy')
    					->addSelect('r.acceptedComment')

    					->addSelect('rt.name as typeName')
    					->addSelect('rt.fullday')
    					->addSelect('rt.paid')
    					->addSelect('rt.textColor')
    					->addSelect('rt.backgroundColor')
    					->addSelect('rt.borderColor')
    					->addSelect('u.firstName')
    					->addSelect('u.lastName')
    					->addSelect('u.username')
    					->addSelect('u.groupAdmin')
    					->addSelect('u.groupId')
    					->addSelect('u.locationAdmin')
    					->addSelect('u.locationId')
    						
    					->from('TimesheetHrBundle:Requests', 'r')
    					->join('TimesheetHrBundle:RequestType', 'rt', 'WITH', 'r.typeId=rt.id')
    					->join('TimesheetHrBundle:User', 'u', 'WITH', 'r.userId=u.id')
    					->where('u.domainId=:dId')
    					->orderBy('r.createdBy', 'ASC')
    					->setParameter('dId', $domainId);
    				
    				if ($date) {
    					$qb->andWhere(':date BETWEEN DATE_FORMAT(r.start, \'%Y-%m-%d\') AND DATE_FORMAT(r.finish, \'%Y-%m-%d\')')
    						->setParameter('date', $date);
    				}
    				if (!$admin) {
    					$qb->andWhere('r.userId=:uId')
    						->setParameter('uId', $currentUser->getId());
    				}
    				if ($groupId) {
    					$qb->andWhere('u.groupId=:gId')
    						->setParameter('gId', $groupId);
    				}
    				if ($locationId) {
    					$qb->andWhere('u.locationId=:lId')
    						->setParameter('lId', $locationId);    					
    				}
    				if ($action == 'confirmnew') {
    					$qb->andWhere('r.accepted=0');
    				}

    				$results=$qb->getQuery()->getArrayResult();
    				if ($results) {
    					if (!$admin) {
    						$data['title'].=' for '.trim($currentUser->getFirstName().' '.$currentUser->getLastName());
    					}
    					foreach ($results as $result) {
    						$result['createdByName']=$functions->getUserFullNameById($result['createdBy']);
    						$result['acceptedByName']=$functions->getUserFullNameById($result['acceptedBy']);
    						
    						if ($result['accepted'] != 0) {
    							$accepted[]=$result;
    						} else {
    							if ($admin || ($currentUser->getId()!=$result['userId'] && ($result['groupId']==null || $result['groupId']==0 || $result['groupId']==$groupId || $result['locationId']==null || $result['locationId']==0 || $result['locationId']==$locationId))) {
    								$requests[]=$result;
    							} else {
    								$accepted[]=$result;
    							}
    						}
    					}
    				}
// error_log('results:'.print_r($results, true));    				
    				$data['content']=$this->renderView('TimesheetHrBundle:Ajax:holidayapproval.html.twig', array(
    					'base'		=> $base,
    					'refresh'	=> $refresh,
   						'requests'	=> $requests,
    					'accepted'	=> $accepted,
    				));
    				break;
    					
    			}	
    		}
// error_log('json return');
			return new JsonResponse($data);
    	} else {
    		error_log('not ajax request...');
    
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
    	}
    }
    
    
    public function swaprequestAction() {
    
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$data=array(
   				'content'=>''
    		);

    		$params=$request->request->all();
    		
    		$base=$params['base'];
    		$date=$params['date'];
    		
    		$functions = $this->get('timesheet.hr.functions');
    		    		
			$usernames=array();
		    $admin=false;
		    		
    		$securityContext = $this->container->get('security.context');
    	
    		if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    			// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
    			if (TRUE === $securityContext->isGranted('ROLE_ADMIN')) {
					$admin=true;
    			} elseif (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
    				$currentUser=$this->getUser();
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
		    		
		    if ($admin) {
// error_log('admin');
		    	$users=$functions->getUsersFutureShifts(null, null, null, null, $functions->getDomainId($request->getHttpHost()));
    		} else {
// error_log('non admin');
    			$user=$this->getUser();
// error_log('user:'.print_r($user, true));
    			$users=$functions->getUsersFutureShifts($user->getId(), null, null, null, $functions->getDomainId($request->getHttpHost()));
    		}
// error_log('users:'.print_r($users, true));
    		if ($users && count($users)) {
    			foreach ($users as $v) {
    				$usernames[$v['id']]=trim($v['firstName'].' '.$v['lastName']).' ('.$v['username'].')';
    			}
    		}
    		
    		$swaprequestForm=$this->createForm(new SwapRequestType($usernames, array(), array(), array(), $date, $this->generateUrl('timesheet_ajax_handleswaprequest'), $this->generateUrl('timesheet_ajax_usershift'))); 
    		$swaprequestForm->handleRequest($request);
    		if ($swaprequestForm->isValid()) {
    			if ($swaprequestForm->isSubmitted()) {
    				return $this->redirect($this->generateUrl($base));
    			}
    		}
    		$data['title']='Swap request';
   			$data['content']=$this->renderView('TimesheetHrBundle:Ajax:swaprequest.html.twig', array(
				'swaprequestform' => $swaprequestForm->createView()
    		));
// error_log('json return');
			return new JsonResponse($data);
    	} else {
    		error_log('not ajax request...');
    
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
    	}
    }

    
    public function handleswaprequestAction() {
    	// error_log('handleholidayrequestAction');
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$data=array(
    			'content'=>'',
    			'error'=>'',
    			'refresh'=>'holidayDiv'
    		);
    		$params=$request->request->all();
// error_log('params:'.print_r($params, true));
    		$userId1=((isset($params['swaprequest']['userId1']))?($params['swaprequest']['userId1']):(null));
    		$userId2=((isset($params['swaprequest']['userId2']))?($params['swaprequest']['userId2']):(null));
    		$tmp1=((isset($params['swaprequest']['shiftId1']))?(explode('_', $params['swaprequest']['shiftId1'])):(array()));
    		if (count($tmp1) == 3) {
    			$userId1=$tmp1[0];
    			$shiftId1=$tmp1[1];
    			$date1=$tmp1[2];
    		}
    		$tmp2=((isset($params['swaprequest']['shiftId2']))?(explode('_', $params['swaprequest']['shiftId2'])):(array()));
// error_log('tmp2 ('.count($tmp2).'):'.print_r($tmp2, true));
    		if (count($tmp2) == 6) {
    			$userId1=$tmp2[0];
    			$shiftId1=$tmp2[1];
    			$date1=$tmp2[2];
    			$userId2=$tmp2[3];
    			$shiftId2=$tmp2[4];
    			$date2=$tmp2[5];
    		}
    		$comment=((isset($params['swaprequest']['comment']))?($params['swaprequest']['comment']):(null));
    		if ($userId1 && $userId2 && $shiftId1 && $shiftId2 && $date1 && $date2) {
// error_log('Parameters OK');

    			$admin=false;
    			$securityContext = $this->container->get('security.context');
    			$currentUser=$this->getUser();
    			
    			if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    				// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
    				if (TRUE === $securityContext->isGranted('ROLE_ADMIN')) {
    					$admin=true;
    				} elseif (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
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

    			$em=$this->getDoctrine()->getManager();
    			$user=$this->getUser();
    			
    			$swaprequest=new SwapRequest();
    			$swaprequest->setComment($comment);
    			$swaprequest->setCreatedBy($user->getId());
    			$swaprequest->setCreatedOn(new \DateTime('now'));
    			$swaprequest->setDate1(new \DateTime($date1));
    			$swaprequest->setDate2(new \DateTime($date2));
    			$swaprequest->setShiftId1($shiftId1);
    			$swaprequest->setShiftId2($shiftId2);
    			$swaprequest->setUserId1($userId1);
    			$swaprequest->setUserId2($userId2);

    			$accepted=false;
    			if ($admin) {
    				$swaprequest->setAccepted(true);
    				$swaprequest->setAcceptedBy($currentUser->getId());
    				$swaprequest->setAcceptedComment('');
    				$swaprequest->setAcceptedOn(new \DateTime('now'));
    				$accepted=true;
    			} else {
    				$swaprequest->setAccepted(null);
    				$swaprequest->setAcceptedBy(null);
    				$swaprequest->setAcceptedComment(null);
    				$swaprequest->setAcceptedOn(null);
    			}
    			$em->persist($swaprequest);
    			$em->flush($swaprequest);
    			
    			$error=0;
    			if ($accepted) {
					$search=array(
						'userId'=>$userId1,
						'shiftId'=>$shiftId1,
						'date'=>new \DateTime($date1)
					);
	    			try {
	    				$shft1=$this->getDoctrine()
	    					->getRepository('TimesheetHrBundle:Allocation')
	    					->findOneBy($search);
	    				if ($shft1) {
	    					$shft1->setUserId($userId2);
	    				} else {
error_log('no shft1');
	    				}
					} catch (\Exception $e) {
						$error++;
						if (strpos($e->getMessage(), '1062') === false) {
error_log('Database error:'.$e->getMessage());
						}
					}
	
					$search=array(
						'userId'=>$userId2,
						'shiftId'=>$shiftId2,
						'date'=>new \DateTime($date2)
					);
					try {
	    				$shft2=$this->getDoctrine()
	    					->getRepository('TimesheetHrBundle:Allocation')
	    					->findOneBy($search);
	    				if ($shft2) {
							$shft2->setUserId($userId1);
	    				} else {
error_log('no shft2');
	    				}
					} catch (\Exception $e) {
						$error++;
						if (strpos($e->getMessage(), '1062') === false) {
error_log('Database error:'.$e->getMessage());
						}
					}
										
					$em->flush();
    			}
    			
				if ($error == 0 && $swaprequest->getId()) {
					if ($accepted) {
						$data['message']='Swap successfully made.';
					} else {
						$data['message']='Swap request made. Please wait for the other person acceptance';
					}
				} else {
					$data['message']='Swap error';
				}
				
//    		} else {
// error_log('Parameter missing...');
//				$data['message']='Parameter missing';
    		}
    		
    		return new JsonResponse($data);
	    } else {
	    	error_log('not ajax request...');
	    	
	    	return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
	    }
    }
    
    public function handleholidayrequestAction() {
// error_log('handleholidayrequestAction');
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$params=$request->request->all();
// error_log('params:'.print_r($params, true));
    		$userId=((isset($params['holidayrequest']['userId']))?($params['holidayrequest']['userId']):(null));    		 
			$functions = $this->get('timesheet.hr.functions');
	
			$admin=false;
			$securityContext = $this->container->get('security.context');
			
			if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
				// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
				if (TRUE === $securityContext->isGranted('ROLE_ADMIN')) {
					$admin=true;
				} elseif (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
					$currentUser=$this->getUser();
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

	   		$holiday=new Requests();
	    		
	   		$holiday->setStart(new \DateTime('now'));
	   		$holiday->setFinish(new \DateTime('now'));
    	    $users=$functions->getUsersList($userId, null, true, null, null, null, false, $functions->getDomainId($request->getHttpHost()));
    		$usernames=array();
    		if ($users) {
    			foreach ($users as $v) {
   					$usernames[$v['id']]=trim($v['firstName'].' '.$v['lastName']).' ('.$v['username'].')';
    			}
    		}
    		$holidayrequestform=$this->createForm(new HolidayRequestType($holiday, $functions->getRequestTypes(), $usernames, $this->generateUrl('timesheet_ajax_handleholidayrequest'))); 
	    	if ($request->isMethod('POST')) {

	    		$holidayrequestform->bind( $request );
	    		$response=array();
	    		if ($holidayrequestform->isValid()) {
	    	
	    			$data = $holidayrequestform->getData();
	    			if ($data['startDate']->format('Y-m-d') > $data['finishDate']->format('Y-m-d')) {
						// if the start date is latest then finish date, show error message
	    				error_log('Finish date should be later than start date');
	    				$response['success'] = false;
	    				$response['cause'] = 'Finish date should be later than start date';
	    				return new JsonResponse($response);
	    			}
	    			
	    			if ($data['startDate']->format('Y-m-d') < date('Y-m-d') && !$admin) {
						// if the start date is past and not admin, show error message
	    				error_log('Not allowed to request this date');
	    				$response['success'] = false;
	    				$response['cause'] = 'Not allowed to request this date';
	    				return new JsonResponse($response);
	    			}
	    			
	    			$em=$this->getDoctrine()->getManager();
		    		if ($data['id']) {
		    			// if we have ID, will edit that request
		    			// not used right now
		    			$holiday=$this->getDoctrine()
		    				->getRepository('TimesheetHrBundle:Requests')
		    				->findOneBy(array('id'=>$data['id']));
		    		}
		    		$rType=$functions->getRequestTypes($data['typeId']);
	    			$user=$this->getUser();

	    			if ($rType->getFullDay() == 1) {
		    			$tmp=$this->getDoctrine()
		    				->getRepository('TimesheetHrBundle:Requests')
		    				->findOneBy(
		    					array(
//		    						'typeId'=>$data['typeId'],
		    						'userId'=>((isset($data['userId']))?($data['userId']):($user->getId())),
		    						'start'=>new \DateTime($data['startDate']->format('Y-m-d').' 00:00:00')
		    					)
		    				);
		    			if ($tmp && count($tmp)) {
		    				error_log('Already requested to this day');
		    				$response['success'] = false;
		    				$response['cause'] = 'Already requested to this day';
		    				return new JsonResponse($response);
		    			}
	    			}
		    			 
	    			$holiday->setAccepted(false);
	    			$holiday->setAcceptedBy(null);
	    			$holiday->setAcceptedComment('');
	    			$holiday->setAcceptedOn(null);
	    			$holiday->setComment(''.$data['comment']);
	    			$holiday->setCreatedBy($user->getId());
	    			$holiday->setCreatedOn(new \DateTime('now'));
	    			$holiday->setTypeId($data['typeId']);
	    			$holiday->setUserId(((isset($data['userId']))?($data['userId']):($user->getId())));
					if (isset($rType) && $rType->getFullDay() == 1) {
						$holiday->setStart(new \DateTime($data['startDate']->format('Y-m-d').' 00:00:00'));
						$holiday->setFinish(new \DateTime($data['finishDate']->format('Y-m-d').' 23:59:59'));
					} else {
						if ($rType->getBothtime() > 0) {
							$holiday->setStart(new \DateTime($data['startDate']->format('Y-m-d').' '.$data['startTime']->format('H:i:s')));
							$holiday->setFinish(new \DateTime($data['startDate']->format('Y-m-d').' '.$data['finishTime']->format('H:i:s')));
						} elseif ($rType->getBothtime() < 0) {
							$holiday->setStart(new \DateTime($data['finishDate']->format('Y-m-d').' 00:00:00'));
							$holiday->setFinish(new \DateTime($data['finishDate']->format('Y-m-d').' '.$data['finishTime']->format('H:i:s')));
						} else {
							$holiday->setStart(new \DateTime($data['startDate']->format('Y-m-d').' '.$data['startTime']->format('H:i:s')));
							$holiday->setFinish(new \DateTime($data['startDate']->format('Y-m-d').' 23:59:59'));
						}
					}
	    			$holiday->setStart(new \DateTime($data['startDate']->format('Y-m-d').' '.$data['startTime']->format('H:i:s')));
	    			$holiday->setFinish(new \DateTime($data['finishDate']->format('Y-m-d').' '.$data['finishTime']->format('H:i:s')));
	    			if (!$data['id']) {
	    				$em->persist($holiday);
	    			}
	    			$em->flush($holiday);
	
	    			if ($holiday->getId()) {
	    				$response['message'] = 'Your request sent, please wait for approval';
		    			$response['success'] = true;
		    			$response['data'] = $data;
//		    			$response['redirect']=$this->generateUrl('timesheet_hr_timesheet');
						$response['refresh'] = 'holidayDiv';
	
	    			} else {
	    				error_log('Holiday request saving error');
	    				$response['success'] = false;
	    				$response['cause'] = 'Holiday request saving error';
	    			}

	    		}else{
	    	
	    			$response['success'] = false;
	    			$response['cause'] = 'Invalid form';
	    	
	    		}
// error_log('response ready');	    	
	    		return new JsonResponse($response);
	    	}
	    	
	    	return array(
	    		'holidayrequestform' => $holidayrequestform->createView()
	    	);
	
	    } else {
	    	error_log('not ajax request...');
	    	
	    	return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
	    }
    }

    
    public function approvedenyAction() {
error_log('approvedenyAction');    
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		 
    		$params=$request->request->all();
// error_log('params:'.print_r($params, true));    		
    		$id=$params['id'];
    		$comment=$params['comment'];
    		$action=$params['action'];
    		$refresh=((isset($params['refresh']))?($params['refresh']):(null));
    		
    		$holiday=$this->getDoctrine()
    			->getRepository('TimesheetHrBundle:Requests')
    			->findOneBy(array('id'=>$id));
    		 
    		$user=$this->getUser();
    
			$holiday->setAccepted(($action=='approve')?1:-1);
			$holiday->setAcceptedBy($user->getId());
    		$holiday->setAcceptedComment(''.$comment);
    		$holiday->setAcceptedOn(new \DateTime('now'));
    
			$em=$this->getDoctrine()->getManager();
			$em->flush($holiday);
// error_log('approved:'.print_r($holiday, true));
			
			$response=array();
			if ($holiday->getId()) {
				$response['success']=true;
				$response['id']=$holiday->getId();
				$response['refresh']=$refresh;
			} else {
				$response['success']=false;
				$response['error']='Saving failed';
			}
    
   			return new JsonResponse($response);
    
    	} else {
    		error_log('not ajax request...');
    
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
    	}
    }
    
    
    public function swapapprovedenyAction() {
error_log('swapapprovedenyAction');
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		 
    		$params=$request->request->all();
// error_log('params:'.print_r($params, true));
    		$id=$params['id'];
    		$action=$params['action'];
    		
    		
    		$swap=$this->getDoctrine()
    			->getRepository('TimesheetHrBundle:SwapRequest')
    			->findOneBy(array('id'=>$id));
error_log('swap:'.print_r($swap, true));    		 
    		$user=$this->getUser();
    
			$swap->setAccepted(($action=='approve')?1:0);
			$swap->setAcceptedBy($user->getId());
    		$swap->setAcceptedComment('');
    		$swap->setAcceptedOn(new \DateTime('now'));
    
			$em=$this->getDoctrine()->getManager();
			$em->flush($swap);
// error_log('approved:'.print_r($holiday, true));
			
			$response=array();
			if ($swap->getId()) {
				$error=0;
				$search=array(
					'userId'=>$swap->getUserId1(),
					'shiftId'=>$swap->getShiftId1(),
					'date'=>$swap->getDate1()
				);
				try {
					$shft1=$this->getDoctrine()
						->getRepository('TimesheetHrBundle:Allocation')
						->findOneBy($search);
					if ($shft1) {
						$shft1->setUserId($swap->getUserId2());
					} else {
						error_log('no shft1');
					}
				} catch (\Exception $e) {
					$error++;
					if (strpos($e->getMessage(), '1062') === false) {
						error_log('Database error:'.$e->getMessage());
					}
				}
				
				$search=array(
					'userId'=>$swap->getUserId2(),
					'shiftId'=>$swap->getShiftId2(),
					'date'=>$swap->getDate2()
				);
				try {
					$shft2=$this->getDoctrine()
						->getRepository('TimesheetHrBundle:Allocation')
						->findOneBy($search);
					if ($shft2) {
						$shft2->setUserId($swap->getUserId1());
					} else {
						error_log('no shft2');
					}
				} catch (\Exception $e) {
					$error++;
					if (strpos($e->getMessage(), '1062') === false) {
						error_log('Database error:'.$e->getMessage());
					}
				}
				
				if ($error == 0) {
					$response['success']=true;
					$response['id']=$swap->getId();
				} else {
					$response['success']=false;
					$response['error']='Swap not found';
				}
			} else {
				$response['success']=false;
				$response['error']='Swap Saving failed';
			}
    
   			return new JsonResponse($response);
    
    	} else {
    		error_log('not ajax request...');
    
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
    	}
    }
    
    
    public function holidayListAction($userId='0', $timestamp='0', $func='') {
    	
    	$request=$this->getRequest();

    	$base=$this->getRequest()->attributes->get('_route');
    	$functions=$this->get('timesheet.hr.functions');

    	$session=$this->get('session');
    	$user=$this->getUser();
    	$userId=$user->getId();
    	if ($session->get('calendar')) {
    		$calendar=$session->get('calendar');
    		if (isset($calendar['userId'])) {
    			$userId=$calendar['userId'];
    		}
    		if (isset($calendar['timestamp'])) {
    			$timestamp=$calendar['timestamp'];
    		}
    		$params=$request->request->all();

    		if (isset($params['func'])) {
	    		switch ($params['func']) {
	    			case 'next' : {
						$timestamp=mktime(0, 0, 0, date('m', $timestamp)+1, 1, date('Y', $timestamp));
	    				break;
	    			}
	    			case 'prev' : {
						$timestamp=mktime(0, 0, 0, date('m', $timestamp)-1, 1, date('Y', $timestamp));
	    				break;
	    			}
	    		}
	    		$calendar['timestamp']=$timestamp;
	    		$session->set('calendar', $calendar);
    		}
    	} else {
    		$calendar['timestamp']=$timestamp=mktime(0, 0, 0, date('m'), 1, date('Y'));
    		$calendar['userId']=$userId;
	    	$session->set('calendar', $calendar);
    	}
    	$domainId=$functions->getDomainId($request->getHttpHost());
    	$holidays=$functions->getHolidays($userId, $timestamp, $domainId);

    	$content=$this->renderView('TimesheetHrBundle:Internal:holidayList.html.twig', array(
	    		'base'		=> $base,
	   			'holidays'	=> $holidays,
    			'admin'		=> $functions->isAdmin()
	    	));
    	
    	if ($request->isXmlHttpRequest()) {

    		$ret=array(
    			'content'=>$content,
    			'refresh'=>'holidayDiv',
    			'error'=>''
    		);
// error_log('holidayList ajax return');
    		return new JsonResponse($ret);
    		
    	} else {
    		
    		
	    	return new Response($content);
    	}
    	 
    }
    

    public function timesheetListAction($userId='0', $timestamp='0', $func='', $usersearch='', $selectedUserId='') {
// error_log('ajax timesheetList');    	
    	$request=$this->getRequest();

    	$base=$request->attributes->get('_route');
    	$functions=$this->get('timesheet.hr.functions');

    	$session=$this->get('session');
    	if ($session->get('timesheet')) {
    		$calendar=$session->get('timesheet');
    		if (isset($calendar['userId'])) {
    			$userId=$calendar['userId'];
    		} else {
    			$user=$this->getUser();
    			$userId=$user->getId();
    		}
    		if (isset($calendar['timestamp'])) {
    			$timestamp=$calendar['timestamp'];
    		}
    	    if (isset($calendar['usersearch'])) {
    			$usersearch=$calendar['usersearch'];
    		}
    		if (isset($calendar['selectedUserId'])) {
    			$selectedUserId=$calendar['selectedUserId'];
    		}
    		$params=$request->request->all();

    		if (isset($params['func'])) {
	    		switch ($params['func']) {
	    			case 'next' : {
						$timestamp=mktime(0, 0, 0, date('m', $timestamp)+1, date('d', $timestamp), date('Y', $timestamp));
	    				break;
	    			}
	    			case 'prev' : {
						$timestamp=mktime(0, 0, 0, date('m', $timestamp)-1, date('d', $timestamp), date('Y', $timestamp));
	    				break;
	    			}
	    		}
	    		$calendar['timestamp']=$timestamp;
	    		
    		}
    		if (isset($params['usersearch']) && $params['usersearch']) {
    			$usersearch=$params['usersearch'];
    			$calendar['usersearch']=$usersearch;
    		}
    		if (isset($params['selectedUserId']) && $params['selectedUserId']) {
    			$selectedUserId=$params['selectedUserId'];
    		}
    		if (isset($params['selectedUserId']) && $params['selectedUserId']=='0') {
    			$selectedUserId=0;
    		}
    		$calendar['selectedUserId']=$selectedUserId;
    		
    		$session->set('timesheet', $calendar);
    	} else {
// error_log('session exists');
    		$calendar['timestamp']=mktime(0, 0, 0, date('m'), 1, date('Y'));
	    	$session->set('timesheet', $calendar);
    	}

    	$domainId=$functions->getDomainId($request->getHttpHost());
// error_log('selectedUserId:'.$selectedUserId);
		$users=$functions->getUsersForManager($this->getUser());
// error_log('4 memory:'.memory_get_usage());
		$timesheet=$functions->getTimesheet($userId, $timestamp, $usersearch, $session, $domainId, $selectedUserId, $functions->getUsersForManager($this->getUser(), (($selectedUserId>0)?(0):(10))));
// error_log('5 memory:'.memory_get_usage());
    	$content=$this->renderView('TimesheetHrBundle:Internal:timesheetList.html.twig', array(
	    		'base'		=> $base,
				'currentMonth'=> date('F Y', $timestamp),
    			'isManager'	=> $functions->isManager(),
    			'users'		=> $users,
    			'selectedUserId'	=> $selectedUserId,
    			'timestamp'	=> $timestamp,
	   			'timesheet'	=> $timesheet
	    	));
    	
    	if ($request->isXmlHttpRequest()) {

    		$ret=array(
    			'content'=>$content,
    			'error'=>'',
    			'refresh'=>'timesheetDiv'
    		);
// error_log('holidayList ajax return');
    		return new JsonResponse($ret);
    		
    	} else {
    		
    		
	    	return new Response($content);
    	}
    	 
    }
    

    public function scheduleListAction($locationId='0', $timestamp='0', $func='', $usersearch='', $groupsearch='', $qualificationsearch='', $base='') {
error_log('scheduleListAction');

    	$week=array();
		$locationsUrl=array();
    	$request=$this->getRequest();

//    	$base=$request->attributes->get('_route');
    	$functions=$this->get('timesheet.hr.functions');

    	$domainId=$functions->getDomainId($request->getHttpHost());
    	
    	$session=$this->get('session');
		if ($session->get('userSearch')) {
    		$usersearch=$session->get('userSearch');
    	}
        if ($session->get('groupSearch')) {
    		$groupsearch=$session->get('groupSearch');
    	}
        if ($session->get('qualificationSearch')) {
    		$qualificationsearch=$session->get('qualificationSearch');
    	}
    	if ($session->get('schedule')) {
    		$calendar=$session->get('schedule');
	    	if (isset($calendar['locationId'])) {
	    		$locationId=$calendar['locationId'];
	    	} else {
	    		$locationId=0;
	    	}
	    	if (isset($calendar['timestamp'])) {
	    		$timestamp=$calendar['timestamp'];
	    	}
	    	$params=$request->request->all();
	    	if (isset($params['func'])) {
		   		switch ($params['func']) {
		   			case 'next' : {
						$timestamp=mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp)+7, date('Y', $timestamp));
		   				break;
		   			}
		   			case 'prev' : {
		   				$timestamp=mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp)-7, date('Y', $timestamp));
		   				break;
		   			}
		   		}
		   		$calendar['timestamp']=$timestamp;
		    		
	    	}
	    	if (isset($params['usersearch']) && $params['usersearch']) {
	    		$usersearch=$params['usersearch'];
	    	}
	    	$calendar['usersearch']=$usersearch;
	    	if (isset($params['groupsearch']) && $params['groupsearch']) {
	    		$groupsearch=$params['groupsearch'];
	    	}
	    	$calendar['groupsearch']=$groupsearch;
	    	if (isset($params['qualificationsearch']) && $params['qualificationsearch']) {
	    		$qualificationsearch=$params['qualificationsearch'];
	    	}
	    	$calendar['qualigicationsearch']=$qualificationsearch;
	    		 
    		$session->set('schedule', $calendar);
    		$session->set('userSearch', $usersearch);
    		$session->set('groupSearch', $groupsearch);
    		$session->set('qualificationSearch', $qualificationsearch);
    	} else {
// error_log('session exists');
    		$calendar['timestamp']=mktime(0, 0, 0, date('m'), 1, date('Y'));
    		$calendar['locationId']=0;
    		$calendar['usersearch']=null;
    		$calendar['groupsearch']=null;
    		$calendar['qualigicationsearch']=null;
	    	$session->set('schedule', $calendar);
    	}
    	    	
    	if (!$timestamp) {
    		$timestamp=time();
    	}
    	
    	$thisMonday=mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp));
    	for ($i=0; $i<7; $i++) {
    		$d=$thisMonday+24*60*60*$i;
    		$week[date('Ymd', $d)]=array(
    			'dayOfWeek'=>date('w', $d),
    			'day'=>date('l', $d),
    			'date'=>date('jS M', $d),
    			'fulldate'=>date('Y-m-d', $d),
    			'problem'=> $functions->isDailyScheduleProblem($locationId, date('Y-m-d', $d))
    		);
    	}
    	$weekNo=sprintf('Week %d, %d', date('W', $thisMonday), date('Y', $thisMonday));
    	$dateRange=date('jS M', $thisMonday).' - '.date('jS M', $thisMonday+24*60*60*6);
    	
    	$users=$functions->getUsersList(null, (strlen($usersearch)?$usersearch:null), false, (strlen($groupsearch)?$groupsearch:null), (strlen($qualificationsearch)?$qualificationsearch:null), null, true, $functions->getDomainId($request->getHttpHost()));
    	if (isset($users['-1']['found'])) {
    		$found=$users['-1']['found'];
    		unset($users['-1']);
    	} else {
    		$found=count($users);
    	}

    	$allocationDivs=$functions->getAllAllocationDivs($locationId, $thisMonday);
    	$locationDivs=$functions->getAllLocationDivs($locationId, $thisMonday);
    	$locations=$functions->getLocation($locationId, true, $domainId);

//    	$locationId=null;
    	$securityContext = $this->container->get('security.context');
    	$currentUser=$this->getUser();
    	if (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
// error_log('manager');
    		if ($currentUser->getLocationAdmin()) {
// error_log('location manager');
    			$locationId=$currentUser->getLocationId();
    		} else {
    			$locationId=-1;
    		}
    	}
    	if ($locationId != -1) {
    		$locations=$functions->getLocation($locationId, true, $domainId);
    	
    		foreach ($locations as $k=>$v) {
    			$locationsUrl[$this->generateUrl('timesheet_hr_schedule', array('locationId'=>$k))]=$v;
    		}
    	}
    	 
    	$content=$this->renderView('TimesheetHrBundle:Internal:scheduleList.html.twig', array(
			'base'					=> $base,
    		'locationId'			=> $locationId,
    		'timestamp'				=> $timestamp,
    		'usersearch'			=> $usersearch,
    		'groupsearch'			=> $groupsearch,
    		'groups'				=> $functions->getGroups($domainId),
    		'qualificationsearch'	=> $qualificationsearch,
    		'qualifications'		=> $functions->getQualifications(null, false, $domainId),
    		'week'					=> $week,
    		'locations'				=> $locations,
    		'locationsUrl'			=> $locationsUrl,
    		'shifts'				=> $functions->getShifts(),
			'users'					=> $users,
    		'allocationDivs'		=> $allocationDivs,
    		'locationDivs'			=> $locationDivs,
    		'found'					=> $found,
    		'weekNo'				=> $weekNo,
    		'dateRange'				=> $dateRange,
    		'isManager'				=> $functions->isManager()
    	));
    	
    	if ($request->isXmlHttpRequest()) {

    		$ret=array(
    			'content'=>$content,
    			'error'=>'',
    			'refresh'=>'scheduleDiv'
    		);
// error_log('holidayList ajax return');
    		return new JsonResponse($ret);
    		
    	} else {
    		
    		
	    	return new Response($content);
    	}
    	 
    }

    
    public function addpunchAction() {
		$request=$this->getRequest();
		if ($request->isXmlHttpRequest()) {
    	
			$params=$request->request->all();
			
			$session=$this->get('session');
			$timezone=$session->get('timezone');
			$date=$params['date'].' '.$params['time'].':00';
			$dt=new \DateTime($date, new \DateTimeZone($timezone));
			$dt->setTimezone(new \DateTimeZone('UTC'));
			
			$user=$this->getDoctrine()
    			->getRepository('TimesheetHrBundle:User')
    			->findOneBy(array('id'=>$params['userId']));
			
			$info=new Info();
			
			$info->setUserId($params['userId']);
			$info->setDeleted(false);
			$info->setIpAddress($request->getClientIp());
			$info->setStatusId($params['typeId']);
			$info->setTimestamp($dt);
			$info->setComment($params['comment']);
			$info->setCreatedOn(new \DateTime('now'));
			$info->setCreatedBy($user->getId());
			
			$em=$this->getDoctrine()->getManager();
			
			$em->persist($info);
			$em->flush($info);
			
			if ($info->getId()) {
				$ret=array(
					'error'=>'',
					'content'=>''// .print_r($params, true)
				);
			} else {
				$ret=array(
					'error'=>'Saving error....',
					'content'=>''// .print_r($params, true)
				);
			}
			
    		return new JsonResponse($ret);
		} else {
			error_log('not ajax request...');
			
			return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
		}
    }
    
    
    function shiftdayAction($dayid) {
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
			$params=$request->request->all();
			
			$functions=$this->get('timesheet.hr.functions');
			$domainId=$functions->getDomainId($request->getHttpHost());
    		
			$data=array();

			$em=$this->getDoctrine()->getManager();
			$qb=$em
				->createQueryBuilder()
				->select('s.id')
				->addSelect('s.startTime')
				->addSelect('s.finishTime')
				->addSelect('s.title')
				->addSelect('l.name')
				->from('TimesheetHrBundle:Shifts', 's')
				->join('TimesheetHrBundle:Location', 'l', 'WITH', 'l.id=s.locationId')
				->join('TimesheetHrBundle:ShiftDays', 'sd', 'WITH', 'sd.shiftId=s.id')
				->where('sd.dayId=:dayId')
				->andWhere('l.domainId=:dId')
				->orderBy('l.name', 'ASC')
				->addOrderBy('s.startTime', 'ASC')
				->setParameter('dayId', $params['dayId'])
				->setParameter('dId', $domainId);
			
			$query=$qb->getQuery();
			
			$results=$query->getArrayResult();
				
			if ($results && count($results)) {
				foreach ($results as $result) {
					$data[]=array(
						'id'=>$result['id'],
						'title'=>$result['name'].' '.$result['startTime']->format('H:i').'-'.$result['finishTime']->format('H:i')
					);
				}
			}
    		return new JsonResponse($data);
    	} else {
    		error_log('not ajax request...');
    			
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
    	}
    }
    

    function usershiftAction($userid) {
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$data=array();
			$params=$request->request->all();
			if (isset($params['dClass'])) {
				$functions = $this->get('timesheet.hr.functions');
				
				switch ($params['dClass']) {
					case 'swapUser1':
					case 'swapUser2': {
						$tmp=explode('_', $params['dSelected']);
						if (count($tmp) == 4) {
							$s1Id=$tmp[1];
							$date=$tmp[2];
							$u2Id=$tmp[3];
							$uId=null;
						} else {
							$s1Id=null;
							$date=null;
							$u2Id=null;
							$uId=$params['dSelected'];
						}
						$conn=$this->getDoctrine()->getConnection();
	
						$query='SELECT DISTINCT'.
							' `a`.`date`,'.
							' `s`.`id`,'.
							' `s`.`title`,'.
							' `s`.`startTime`,'.
							' `s`.`finishTime`,'.
							' `l`.`name`'.
							' FROM `Shifts` `s`'.
								' JOIN `Allocation` `a` ON `s`.`id`=`a`.`shiftId`'.
								' JOIN `Location` `l` ON `s`.`locationId`=`l`.`id`'.
								' JOIN `Users` `u` ON `a`.`userId`=`u`.`id`'.
							' WHERE `a`.`published`'.
								' AND `a`.`date`>DATE(NOW())'.
								' AND `u`.`domainId`=:dId'.
								(($uId)?(' AND `a`.`userId`=:uId'):(' AND `a`.`date`!=:date AND `a`.`shiftId`!=:sId AND `a`.`userId`=:uId')).
							' ORDER BY `a`.`date`, `l`.`name`, `s`.`startTime`';
// error_log($query);
						$stmt=$conn->prepare($query);


						if ($uId) {
							$stmt->bindValue('uId', $params['dSelected']);
// error_log('uId:'.$params['dSelected']);
						} else {
							$stmt->bindValue('uId', $u2Id);
							$stmt->bindValue('date', $date);
							$stmt->bindValue('sId', $s1Id);
// error_log('uId:'.$u2Id.', date:'.$date.', sId:'.$s1Id);
						}
						$dId=$functions->getDomainId($request->getHttpHost());
// error_log('dId:'.$dId);
						$stmt->bindValue('dId', $dId);
						$stmt->execute();
			    				
						$results=$stmt->fetchAll();
						
						if ($results && count($results)) {
							foreach ($results as $result) {
								$data[]=array(
									'id'=>$params['dSelected'].'_'.$result['id'].'_'.$result['date'],
//									'title'=>$result['name'].' '.substr($result['startTime'], 0, 5).'-'.substr($result['finishTime'], 0, 5).' on '.date('d/m/Y' , strtotime($result['date']))
									'title'=>$result['title'].' on '.date('d/m/Y' , strtotime($result['date'])).' at '.$result['name']
								);
							}
						}
						break;
					}
					case 'swapShift1': {
						$tmp=explode('_', $params['dSelected']);
						if (count($tmp) == 3) {
							$uId=$tmp[0];
							$sId=$tmp[1];
							$date=$tmp[2];
						
							$results=$functions->getUsersFutureShifts(null, $sId, $date, $uId, $functions->getDomainId($request->getHttpHost()));
						} else {
							$results=array();
						}
						if ($results && count($results)) {
							foreach ($results as $result) {
								$data[]=array(
									'id'=>$params['dSelected'].'_'.$result['id'],
									'title'=>trim($result['firstName'].' '.$result['lastName']).' ('.$result['username'].')'
								);
							}
						}
						break;
					}
				}
			}
    		return new JsonResponse($data);
    	} else {
    		error_log('not ajax request...');
    			
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
    	}
    }
    
    public function getmessageAction() {
error_log('getmessageAction');
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$data=array(
    			'error'=>'',
    			'content'=>''
    		);
    		$params=$request->request->all();
// error_log('params:'.print_r($params, true));
    		if (isset($params['id']) && $params['id']) {
    			$folder=$params['folder'];
    			$message=$this->getDoctrine()
    				->getRepository('TimesheetHrBundle:Messages')
    				->findOneBy(array('id'=>$params['id']));
    			
    			if ($message && count($message)) {
    				if ($message->getReadOn() == null) {
    					$securityContext = $this->container->get('security.context');
    					 
    					if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    						// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
   							$currentUser=$this->getUser();
   							if ($currentUser->getId() == $message->getRecipient()) {
   								error_log('Recipient read the message. Id:'.$message->getId());
   								$em=$this->getDoctrine()->getManager();
   								$message->setReadOn(new \DateTime('now'));
   								$em->flush($message);
   							}
    					}
    				}

    				$functions = $this->get('timesheet.hr.functions');
    				$data['content']=$functions->createMessageView($message, $folder, true);
    			} else {
    				$data['error']='No message found';
    			}
    		} else {
    			$data['error']='No message ID';
    		}
    		 
    		return new JsonResponse($data);
    	} else {
    		error_log('not ajax request...');
    			
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
    	}
    }

    
    public function sysadminAction() {
error_log('sysadminAction');
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$data=array(
    			'error'=>''
    		);
    		$params=$request->request->all();
    		// error_log('params:'.print_r($params, true));
    		if (isset($params['name']) && strlen($params['name']) && isset($params['value']) && strlen($params['value'])) {

    			$config=$this->getDoctrine()
    				->getRepository('TimesheetHrBundle:Config')
    				->findOneBy(array('name'=>$params['name']));
    			 
    			if ($config && count($config)) {
    				$config->setValue($params['value']);
    				$em=$this->getDoctrine()->getManager();
    				$em->flush($config);
    				if (!$config->getId()) {
    					$data['error']='Could not save';
    				}    	
    			} else {
    				$data['error']='No config found';
    			}
    		} else {
    			$data['error']='Not correct parameters';
    		}
    		 
    		return new JsonResponse($data);
    	} else {
    		error_log('not ajax request...');
    		 
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
    	}
    	 
    }
    
    public function timesheetcheckAction() {
error_log('timesheetcheckAction');
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$data=array(
    			'error'=>'',
    			'comment'=>'',
    			'content'=>''
    		);
			$functions = $this->get('timesheet.hr.functions');
    		$params=$request->request->all();
    		$action=((isset($params['action']))?($params['action']):(''));;
			$date=((isset($params['date']))?(new \DateTime($params['date'])):(null));
			$userId=((isset($params['userid']))?($params['userid']):(''));
			$comment=((isset($params['comment']))?($params['comment']):(''));
			
			switch ($action) {
				case 'form' : {
					$checked=$functions->getTimesheetChecked($userId, $date->format('Y-m-d'));
		    		
					$comment=((isset($checked['comment']))?($checked['comment']):(''));
					if ($userId) {
						$userManager = $this->container->get('fos_user.user_manager');
						$user=$userManager->findUserBy(array('id'=>$userId));						
						$username=trim($user->getTitle().' '.$user->getFirstName().' '.$user->getLastName());
					} else {
						$username='Unknown';
					}

					$form=$this->createForm(new TimesheetCheckType($username, $date, $comment)); 
		    		$form->handleRequest($request);
					// No need to submit, should not happens
//		    		if ($form->isValid()) {
//		    			if ($form->isSubmitted()) {
//		    				return $this->redirect($this->generateUrl($base));
//		    			}
//		    		}
		    		$data['title']='Timesheet Check';
					$data['content']=$this->renderView('TimesheetHrBundle:Ajax:timesheetcheck.html.twig', array(
						'form' => $form->createView()
		    		));
					break;
				}
				case 'save' : {
					$currentUser=$this->getUser();
					$checkedBy=$currentUser->getId();
					$saved=$functions->setTimesheetChecked($userId, $date, $checkedBy, $comment);
					
					if ($saved) {
// error_log('saved');						
					} else {
						$data['error']='Could not save';
					}
					break;
				}
				default : {
					error_log('wrong action');
					break;
				}
			}
    		
       		return new JsonResponse($data);
    	} else {
    		error_log('not ajax request...');
    		 
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
    	}
    }


    public function photoAction() {
error_log('photoAction');
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$data=array(
    			'error'=>'',
    			'title'=>'Photo',
    			'content'=>''
    		);
			$functions = $this->get('timesheet.hr.functions');
			$domainId=$functions->getDomainId($request->getHttpHost());
			
    		$params=$request->request->all();
error_log('params:'.print_r($params, true));
    		$photoId=((isset($params['photoid']))?($params['photoid']):(''));
    		$func=((isset($params['func']))?($params['func']):(''));
    		switch ($func) {
    			case 'user' : {
    				$title='User photo';
					$userId=((isset($params['userid']))?($params['userid']):(''));
		
					$photos=$functions->getUserPhotos($userId, $domainId, false, true, 500, $photoId);
    				break;
    			}
    			case 'resident' : {
    				$title='Resident photo';
    				$residentId=((isset($params['userid']))?($params['userid']):(''));
    				
    				$photos=$functions->getResidentPhotos($residentId, $domainId, false, true, 500, $photoId);
    				break;
    			}
    		}
    		if ($photos && count($photos)) {
error_log('no of photos:'.count($photos));			
				$photo=reset($photos);
// error_log('photo:'.print_r($photo, true));
				$data['content']=$this->renderView('TimesheetHrBundle:Ajax:photo.html.twig', array(
					'alt' 	=> $title,
					'photo'	=> $photo['photo'],
					'type'	=> $photo['type'],
					'width'	=> $photo['width'],
					'height'=> $photo['height']
			    ));
    		}
			
       		return new JsonResponse($data);
    	} else {
    		error_log('not ajax request...');
    		 
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
    	}
    }

    public function userphotoAction() {
error_log('userphotoAction');
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$data=array(
    			'error'=>'',
    			'title'=>'Photo',
    			'content'=>'',
    			'js'=>'editphoto'
    		);
			$functions = $this->get('timesheet.hr.functions');
			$domainId=$functions->getDomainId($request->getHttpHost());
//			$currentUser=$this->getUser();
						
    		$params=$request->request->all();
    		$photoId=(isset($params['locationId'])?($params['locationId']):(null)); // locationId == photoId
// error_log('params:'.print_r($params, true));

			$action=((isset($params['action']))?($params['action']):(''));
			if (!$action) {
				// if the form submitted, the action forwarded in the form
				if (isset($params['photonotes']['action'])) {
					$action=$params['photonotes']['action'];
				}
			}
			switch ($action) {
				case 'delete' : {
// error_log('delete');
					$photo=$this->getDoctrine()
						->getRepository('TimesheetHrBundle:UserPhotos')
						->findOneBy(array('id'=>$photoId));
					
					$em=$this->getDoctrine()->getManager();
					$em->remove($photo);
					$em->flush($photo);
					$data['js']='redirect';
					$data['url']=$this->generateUrl('timesheet_hr_userphotos');
					break;
				}
				case 'edit' : {
// error_log('edit');
					$formSubmit=false;
					if (!$photoId) {
						if (isset($params['photonotes']['photoid'])) {
							$photoId=$params['photonotes']['photoid'];
							$formSubmit=true;
						}	
					}
		
					if ($photoId) {
						$photos=$functions->getUserPhotos(null, $domainId, false, true, 200, $photoId);
						if ($photos && count($photos)) {
							$photo=reset($photos);
			
							$form=$this->createForm(new PhotoNotesType($photo['id'], $photo['notes'], $photo['createdOn'], $action, $this->generateUrl('timesheet_ajax_userphoto')));
							if ($formSubmit) {
// error_log('submit');
								$form->handleRequest($request);
					    		if ($form->isValid()) {
// error_log('valid');
									if ($form->isSubmitted()) {
// error_log('submitted');
										$notes=$params['photonotes']['notes'];
								
										$photo=$this->getDoctrine()
											->getRepository('TimesheetHrBundle:UserPhotos')
											->findOneBy(array('id'=>$photoId));
								
										$photo->setNotes(''.$notes);
										$em=$this->getDoctrine()->getManager();
										$em->flush($photo);
										$data['redirect']=$this->generateUrl('timesheet_hr_userphotos');
									} else {
										$formSubmit=false;
									}
					    		} else {
					    			$formSubmit=false;
					    		}
							}

							if (!$formSubmit) {
								$data['content']=$this->renderView('TimesheetHrBundle:Ajax:userphoto.html.twig', array(
									'title'	=>'Edit Photo',
									'form'	=> $form->createView(),
									'alt'	=>'Photo '.$params['locationId'],
									'photo'	=>$photo,
								));
							}
						}
					} else {
error_log('no photo id');
					}
					break;
				}
				default : {
					$data['error']='Wrong action';
					break;
				}
			}
			
       		return new JsonResponse($data);
    	} else {
    		error_log('not ajax request...');
    		 
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
    	}
    }


    public function residentphotoAction() {
error_log('residentphotoAction');
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$data=array(
    			'error'=>'',
    			'title'=>'Photo',
    			'content'=>'',
    			'js'=>'editphoto'
    		);
			$functions = $this->get('timesheet.hr.functions');
			$domainId=$functions->getDomainId($request->getHttpHost());
//			$currentUser=$this->getUser();
						
    		$params=$request->request->all();
    		$photoId=(isset($params['locationId'])?($params['locationId']):(null)); // locationId == photoId
// error_log('params:'.print_r($params, true));

			$action=((isset($params['action']))?($params['action']):(''));
			if (!$action) {
				// if the form submitted, the action forwarded in the form
				if (isset($params['photonotes']['action'])) {
					$action=$params['photonotes']['action'];
				}
			}
			switch ($action) {
				case 'delete' : {
// error_log('delete');
					$photo=$this->getDoctrine()
						->getRepository('TimesheetHrBundle:ResidentPhotos')
						->findOneBy(array('id'=>$photoId));
					
					$em=$this->getDoctrine()->getManager();
					$em->remove($photo);
					$em->flush($photo);
					$data['js']='redirect';
					$data['url']=$this->generateUrl('residents_hr_residentphotos');
					break;
				}
				case 'edit' : {
// error_log('edit');
					$formSubmit=false;
					if (!$photoId) {
						if (isset($params['photonotes']['photoid'])) {
							$photoId=$params['photonotes']['photoid'];
							$formSubmit=true;
						}	
					}
		
					if ($photoId) {
						$photos=$functions->getResidentPhotos(null, $domainId, false, true, 200, $photoId);
						if ($photos && count($photos)) {
							$photo=reset($photos);
			
							$form=$this->createForm(new PhotoNotesType($photo['id'], $photo['notes'], $photo['createdOn'], $action, $this->generateUrl('timesheet_ajax_residentphoto')));
							if ($formSubmit) {
// error_log('submit');
								$form->handleRequest($request);
					    		if ($form->isValid()) {
// error_log('valid');
									if ($form->isSubmitted()) {
// error_log('submitted');
										$notes=$params['photonotes']['notes'];
								
										$photo=$this->getDoctrine()
											->getRepository('TimesheetHrBundle:ResidentPhotos')
											->findOneBy(array('id'=>$photoId));
								
										$photo->setNotes(''.$notes);
										$em=$this->getDoctrine()->getManager();
										$em->flush($photo);
										$data['redirect']=$this->generateUrl('residents_hr_residentphotos');
									} else {
										$formSubmit=false;
									}
					    		} else {
					    			$formSubmit=false;
					    		}
							}

							if (!$formSubmit) {
								$data['content']=$this->renderView('TimesheetHrBundle:Ajax:userphoto.html.twig', array(
									'title'	=>'Edit Resident Photo',
									'form'	=> $form->createView(),
									'alt'	=>'Photo '.$params['locationId'],
									'photo'	=>$photo,
								));
							}
						}
					} else {
error_log('no photo id');
					}
					break;
				}
				default : {
					$data['error']='Wrong action';
					break;
				}
			}
			
       		return new JsonResponse($data);
    	} else {
    		error_log('not ajax request...');
    		 
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
    	}
    }


    public function residenthistoryAction() {
error_log('residenthistoryAction');
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$data=array(
    			'error'=>'',
    			'title'=>'Resident Room History',
    			'content'=>''
    		);
			$functions = $this->get('timesheet.hr.functions');
			$domainId=$functions->getDomainId($request->getHttpHost());
						
    		$params=$request->request->all();
    		$residentId=(isset($params['id'])?($params['id']):(null));

			if ($residentId) {
				$resident=$this->getDoctrine()
					->getRepository('TimesheetHrBundle:Residents')
					->findOneBy(array('id'=>$residentId, 'domainId'=>$domainId));
				if ($resident && count($resident)) {
					$history=$functions->getResidentHistory($residentId, $domainId);
					$data['content']=$this->renderView('TimesheetHrBundle:Ajax:residenthistory.html.twig', array(
						'resident'	=> $resident,
						'history'	=> $history
					));
				}
			}
			
       		return new JsonResponse($data);
    	} else {
    		error_log('not ajax request...');
    		 
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
    	}
    }


    public function showdailyproblemsAction() {
error_log('showdailyproblemsAction');
    	$request=$this->getRequest();
    	if ($request->isXmlHttpRequest()) {
    		$data=array(
    			'error'=>'',
    			'title'=>'Requirements',
    			'content'=>''
    		);
			$functions = $this->get('timesheet.hr.functions');
			$domainId=$functions->getDomainId($request->getHttpHost());
						
    		$params=$request->request->all();
    		$date=(isset($params['date'])?(new \DateTime($params['date'])):(null));
    		$locationTmp=(isset($params['location'])?($params['location']):(null));

			if ($locationTmp) {
				$locations=explode('|', $locationTmp);
				$usersRepo=$this->getDoctrine()
					->getRepository('TimesheetHrBundle:User');
				if (count($locations)) {
					foreach ($locations as $locationId) {
						$location=$this->getDoctrine()
							->getRepository('TimesheetHrBundle:Location')
							->findOneBy(array('domainId'=>$domainId, 'id'=>$locationId));
						
						$required=$functions->getCurrentlyRequiredStaff($locationId, $date->format('Y-m-d'));
						$allocated=$functions->getCurrentlyAllocatedStaff($locationId, $date->format('Y-m-d'));
						$requiredQualifications=$functions->getCurrentlyRequiredQualifications($locationId, $date->format('Y-m-d'));
						$allocatedQualifications=$functions->getCurrentlyAllocatedQualifications($locationId, $date->format('Y-m-d'));
						$notrequired=array();
						$users=array();
						$tmpGroups=array();
						if ($allocated && count($allocated)) {
							foreach ($allocated as $a) {
								unset($tmpGroups);
								$tmpGroups=array();
								$tmpShiftId=$a['shiftId'];
								foreach ($required as $r) {
									if ($tmpShiftId==$r['shiftId']) {
										$tmpGroups[$r['groupId']]=$r['groupId'];
									}
								}
								if (!in_array($a['groupId'], $tmpGroups)) {
									$notrequired[]=$a;
								}
								if (!isset($users[$a['userId']])) {
									$users[$a['userId']]=$usersRepo->findOneBy(array('id'=>$a['userId']));
								}
							}
						}

						$data['content'].=$this->renderView('TimesheetHrBundle:Ajax:showdailyproblems.html.twig', array(
							'location'	=> $location,
							'shifts'	=> $functions->getShifts(null, null, $locationId, $domainId),
							'date'		=> $date,
							'required'	=> $required,
							'notrequired' => $notrequired,
							'allocated'	=> $allocated,
							'requiredQualifications'=>$requiredQualifications,
							'allocatedQualifications'=>$allocatedQualifications,
							'users'		=> $users
						));
						$data['title'].=' at '.$location->getName().' on '.$date->format('l jS M Y');
					}
				}
			}
			
       		return new JsonResponse($data);
    	} else {
    		error_log('not ajax request...');
    		 
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'), 302);
    	}
    }
}
