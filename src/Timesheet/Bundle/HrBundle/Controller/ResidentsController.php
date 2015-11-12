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
use Symfony\Component\Validator\Validator;
use Timesheet\Bundle\HrBundle\TimesheetHrBundle;
use Timesheet\Bundle\HrBundle\Form\Type\CompanyType;
use Timesheet\Bundle\HrBundle\Form\Type\ConfigType;
use Timesheet\Bundle\HrBundle\Form\Type\ForgotPasswordType;
use Timesheet\Bundle\HrBundle\Form\Type\PunchType;
use Timesheet\Bundle\HrBundle\Form\Type\RegisterType;
use Timesheet\Bundle\HrBundle\Form\Type\LoginType;
use Timesheet\Bundle\HrBundle\Form\Type\LocationType;
use Timesheet\Bundle\HrBundle\Form\Type\ChangeStatusType;
use Timesheet\Bundle\HrBundle\Form\Type\ContractType;
use Timesheet\Bundle\HrBundle\Form\Type\GroupType;
use Timesheet\Bundle\HrBundle\Form\Type\HolidayRequestType;
use Timesheet\Bundle\HrBundle\Form\Type\MessageType;
use Timesheet\Bundle\HrBundle\Form\Type\PhotoType;
use Timesheet\Bundle\HrBundle\Form\Type\QualificationType;
use Timesheet\Bundle\HrBundle\Form\Type\ResetPasswordType;
use Timesheet\Bundle\HrBundle\Form\Type\ResidentContactType;
use Timesheet\Bundle\HrBundle\Form\Type\ResidentRegisterType;
use Timesheet\Bundle\HrBundle\Form\Type\ResidentMoveType;
use Timesheet\Bundle\HrBundle\Form\Type\RoomType;
use Timesheet\Bundle\HrBundle\Form\Type\ShiftType;
use Timesheet\Bundle\HrBundle\Form\Type\StaffRequirementsType;
use Timesheet\Bundle\HrBundle\Form\Type\QualRequirementsType;
use Timesheet\Bundle\HrBundle\Form\Type\StatusType;
use Timesheet\Bundle\HrBundle\Form\Type\TimingType;
use Timesheet\Bundle\HrBundle\Form\Type\UserQualificationType;
use Timesheet\Bundle\HrBundle\Entity\Constants;
use Timesheet\Bundle\HrBundle\Entity\Allocation;
use Timesheet\Bundle\HrBundle\Entity\Companies;
use Timesheet\Bundle\HrBundle\Entity\Config;
use Timesheet\Bundle\HrBundle\Entity\Contract;
use Timesheet\Bundle\HrBundle\Entity\Groups;
use Timesheet\Bundle\HrBundle\Entity\Info;
use Timesheet\Bundle\HrBundle\Entity\Location;
use Timesheet\Bundle\HrBundle\Entity\LocationIpAddress;
use Timesheet\Bundle\HrBundle\Entity\Messages;
use Timesheet\Bundle\HrBundle\Entity\PasswordReset;
use Timesheet\Bundle\HrBundle\Entity\Qualifications;
use Timesheet\Bundle\HrBundle\Entity\QualRequirements;
use Timesheet\Bundle\HrBundle\Entity\Residents;
use Timesheet\Bundle\HrBundle\Entity\ResidentPhotos;
use Timesheet\Bundle\HrBundle\Entity\Rooms;
use Timesheet\Bundle\HrBundle\Entity\StaffRequirements;
use Timesheet\Bundle\HrBundle\Entity\Shifts;
use Timesheet\Bundle\HrBundle\Entity\ShiftDays;
use Timesheet\Bundle\HrBundle\Entity\Status;
use Timesheet\Bundle\HrBundle\Entity\StatusToDomain;
use Timesheet\Bundle\HrBundle\Entity\Timing;
use Timesheet\Bundle\HrBundle\Entity\User;
use Timesheet\Bundle\HrBundle\Entity\UserQualifications;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use FOS\UserBundle\Propel\Group;
// use FOS\UserBundle\Propel\FOS\UserBundle\Propel;
use Symfony\Component\Serializer\Tests\Normalizer\GetConstructorDummy;
use Symfony\Component\Intl\Intl;
use Timesheet\Bundle\HrBundle\Entity;
use Symfony\Component\HttpFoundation;
use Ps\PdfBundle\Annotation\Pdf;
use Zend\Math\Rand;
use Zend\Stdlib\Message;
use Timesheet\Bundle\HrBundle\Entity\UserPhotos;
use \DateTime;
use Timesheet\Bundle\HrBundle\Entity\ResidentContacts;

class ResidentsController extends Controller
{


    public function indexAction() {

    	$message='';
    	$session=$this->get('session');
    	
   		$session->set('menu', Constants::MENU_RESIDENTS);
//   		$request=$this->getRequest();
//   		$functions=$this->get('timesheet.hr.functions');
//   		$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());

//   		$defaultController=$this->get('timesheet.default.controller');
   		$functions=$this->get('timesheet.hr.functions');
   		
        return $this->render('TimesheetHrBundle:Residents:index.html.twig', array(
        	'message'	=> $message,
        	'title'		=> $functions->getPageTitle('Residents Dashboard')
        ));
    }
    

    public function residentslistAction() {
    	
    	$message='';
    	$session=$this->get('session');
    	$sysadmin=false;
    	 
    	$session->set('menu', Constants::MENU_RESIDENTS);

    	$functions=$this->get('timesheet.hr.functions');
    	$securityContext = $this->container->get('security.context');
    	if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    		// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
    		if (TRUE === $securityContext->isGranted('ROLE_SYSADMIN')) {
    			$sysadmin=true;
    		}
    	}
    	
    	
    	return $this->render('TimesheetHrBundle:Residents:residentslist.html.twig', array(
    		'message'	=> $message,
    		'title'		=> $functions->getPageTitle('Residents List'),
    		'residents'	=> $residents,
    		'admin'		=> $sysadmin,
    		'companies'	=> (($sysadmin)?($functions->getCompanies()):(null))
    	));
    }

    
    public function residentlistAction($action, $id) {
error_log('residentlistAction');
    	$message='';
    	$sysadmin=false;
    	$readOnly=false;
    	$session=$this->get('session');
    
    	$session->set('menu', Constants::MENU_RESIDENTS);
    
    	$functions=$this->get('timesheet.hr.functions');
    	$securityContext = $this->container->get('security.context');
    	if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    		// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
    		if (TRUE === $securityContext->isGranted('ROLE_SYSADMIN')) {
    			$sysadmin=true;
    		}
    	}
    	 
    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
//    	$base=$this->getRequest()->attributes->get('_route');

    	switch ($action) {
    		case 'new' :
    		case 'edit' :
    		case 'show' : {
    			$readOnly=($action=='show');
		    	$em=$this->getDoctrine()->getManager();
		        $currentUser=$this->getUser();
		        if (isset($id) && $id) {
			        $resident=$this->getDoctrine()
			        	->getRepository('TimesheetHrBundle:Residents')
			        	->findOneBy(array('id'=>$id));
			        $rooms=null;
		        } else {
		        	$resident=new Residents();
		        	$rooms=$functions->getRooms($domainId, false, true);
		        }

		       
		    	$residentForm=$this->createForm(
		    		new ResidentRegisterType(
		    			$resident,
		    			$functions->getLatestRoom($resident->getId()),
		    			$functions->getTitles(),
		    			$functions->getReligions(),
		    			$functions->getMaritalStatuses(),
		    			$rooms,
		    			(($sysadmin)?($functions->getCompanies()):(null)),
		    			$readOnly));
		
				$residentForm->handleRequest($this->getRequest());
		    	
				if ($residentForm->isValid() && !$readOnly) {
					if ($residentForm->get('cancel')->isClicked()) {
						$session->remove('resident');
						return $this->redirect($this->generateUrl('residents_hr_list'));
					} else {
						$new=false;
						$data=$residentForm->getData();
		
						$resident->setTitle(''.$data['title']);
						$resident->setFirstName(''.$data['firstName']);
						$resident->setLastName(''.$data['lastName']);
						$resident->setNickName(''.$data['nickName']);
						$resident->setMaidenName(''.$data['maidenName']);
						$resident->setEmail($data['email']);
						$resident->setPhoneLandline(''.$data['phoneLandline']);
						$resident->setPhoneMobile(''.$data['phoneMobile']);
						$resident->setBirthday($data['birthday']);
			   			$resident->setNationality($data['nationality']);
						$resident->setMaritalStatus($data['maritalStatus']);
						$resident->setReligion($data['religion']);
			   			$resident->setAddressLine1(''.$data['addressLine1']);
						$resident->setAddressLine2(''.$data['addressLine2']);
						$resident->setAddressCity(''.$data['addressCity']);
						$resident->setAddressCounty(''.$data['addressCounty']);
						$resident->setAddressCountry(''.$data['addressCountry']);
						$resident->setAddressPostcode(''.$data['addressPostcode']);
						$resident->setNI(''.$data['ni']);
						$resident->setNHS(''.$data['nhs']);
						$resident->setNotes(''.$data['notes']);
						$resident->setCreatedBy($currentUser->getId());
						$resident->setCreatedOn(new \DateTime());
						
						if (isset($data['domainId']) && $data['domainId']) {
							$resident->setDomainId($data['domainId']);
						} else {
							$resident->setDomainId($domainId);
						}
		
						try {
							if (!$data['id']) {
								$em->persist($resident);
								$new=true;
							}
							$em->flush($resident);
						} catch (\Exception $e) {
							if (strpos($e->getMessage(), '1062') === false) {
								error_log('Database error:'.$e->getMessage());
							} else {
								$message='Duplicate details, please try another username';
							}
						}
						if ($resident->getId()) {
							
							$message='Resident ('.trim($resident->getTitle().' '.$resident->getFirstName().' '.$resident->getLastName()).') details '.(($new)?('saved'):('updated'));
							
							if (isset($data['roomId']) && $data['roomId']) {
								if ($functions->setResidentMoveIn($resident->getId(), $data['roomId'], $data['roomMoveIn'], $data['roomNotes'], $currentUser->getId())) {
									$message.=' and placed into '.$rooms[$data['roomId']].' room';
								}
							}
							$session->remove('resident');
							$session->getFlashBag()->set('notice', $message);
							return $this->redirect($this->generateUrl('residents_hr_list'));
						}
					}
				}
				break;
    		}
    		default : {
    			$em=$this->getDoctrine()->getManager();
    			$qb=$em
	    			->createQueryBuilder()
	    			->select('r.id')
	    			->addSelect('r.title')
	    			->addSelect('r.firstName')
	    			->addSelect('r.lastName')
	    			->addSelect('r.nickName')
	    			->addSelect('r.notes')
	    			->addSelect('r.createdOn')
	    			->addSelect('r.domainId')
	    			->from('TimesheetHrBundle:Residents', 'r')
	    			->where('r.id>0')
	    			->orderBy('r.createdOn', 'DESC');
    				
    			if (!$sysadmin) {
    				$qb->andWhere('r.domainId=:dId')
	    				->setParameter('dId', $functions->getDomainId($this->getRequest()->getHttpHost()));
    			}
    			$query=$qb->getQuery();
    			
    			$residents=$query->getArrayResult();
    			$extra=true;
    			if ($residents && $extra) {
    				foreach ($residents as $k=>$v) {
    					$residents[$k]['photos']=$functions->getResidentPhotos($v['id'], $domainId, false, true, 50);
    					$residents[$k]['currentLocation']=$functions->getResidentLocation($v['id'], new \DateTime());
    					$residents[$k]['contacts']=$functions->getResidentContacts($v['id'], $domainId);
    				}
    			}

    			break;		
    		}
    	}
    	
    	return $this->render('TimesheetHrBundle:Residents:residentslist.html.twig', array(
    		'message'	=> $message,
    		'form'		=> ((isset($residentForm))?($residentForm->createView()):(null)),
    		'readOnly'	=> $readOnly,
    		'residentId'=> ((isset($resident))?($resident->getId()):(null)),
    		'title'		=> $functions->getPageTitle('Resident list'),
    		'residents'	=> ((isset($residents))?($residents):(null))
    	));
    }
    
    
    public function residentphotosAction($action, $id) {
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    		error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	 
    	$session=$this->get('session');
    	if ($id) {
    		$session->set('residentPhotos', $id);
    		return $this->redirect($this->generateUrl('residents_hr_residentphotos', array('action'=>$action)));
    	}
    	if ($session->get('residentPhotos')) {
    		$residentId=$session->get('residentPhotos');
    	} else {
    		$residentId=null;
    	}
    
    	if ($residentId) {
    		$functions=$this->get('timesheet.hr.functions');
    		$request=$this->getRequest();
    		$domainId=$functions->getDomainId($request->getHttpHost());
    
//    		$userManager = $this->container->get('fos_user.user_manager');
			
    		$resident=$this->getDoctrine()
    			->getRepository('TimesheetHrBundle:Residents')
    			->findOneBy(array('id'=>$residentId));
    		$photos=array();
    		$images=array();
    			
    		switch ($action) {
    			case 'new': {
    				$form=$this->createForm(new PhotoType($resident->getId(), trim($resident->getTitle().' '.$resident->getFirstName().' '.$resident->getLastName()), ''));
    
    				if ($request->getMethod() == 'POST') {
    					$form->handleRequest($request);
    					if ($form->isValid()) {
    						if ($form->get('cancel')->isClicked()) {
    							$session->remove('admin');
    							return $this->redirect($this->generateUrl('residents_hr_residentphotos'));
    						} else {
    
    							$data=$form->getData();
    							 
    							$filepath=$data['file']->getPathname();
    
    							// error_log('data:'.print_r($data, true));
    								
    							$phototype=$data['file']->getMimeType();
    							// error_log('phototype:'.$phototype);
    							$photosize=$data['file']->getClientSize();
    							// error_log('file size:'.$photosize);
    							if ($photosize>1024*1024) {
    								$session->getFlashBag()->set('notice', 'Please upload smallest file. The limit is 1M');
    								return $this->redirect($this->generateUrl('residents_hr_residentphotos'));
    							}
    
    							if (false === array_search($phototype, array('image/jpeg', 'image/png', 'image/gif'))) {
    								$session->getFlashBag()->set('notice', 'Wrong file type');
    								return $this->redirect($this->generateUrl('residents_hr_residentphotos'));
    							} else {
    								$phototype=str_replace('image/', '', $phototype);
    							}
    
    							if (file_exists($filepath) && is_readable($filepath)) {
    									
    									
    								$photodata=file_get_contents($filepath);
    
    								unlink($filepath);
    								$notes=$data['notes'];
    								try {
    									$currentUser=$this->getUser();
    									 
    									$residentPhotos=new ResidentPhotos();
    									$residentPhotos->setCreatedOn(new \DateTime());
    									$residentPhotos->setCreatedBy($currentUser->getId());
    									$residentPhotos->setResidentId($residentId);
    									$residentPhotos->setPhoto($photodata);
    									$residentPhotos->setType($phototype);
    									$residentPhotos->setNotes(''.$notes);
    									$em=$this->getDoctrine()->getManager();
    									$em->persist($residentPhotos);
    									$em->flush($residentPhotos);
    								} catch (\Exception $e) {
    									error_log('Database error:'.$e->getMessage());
    									$message='Database error, could not save';
    									$session->getFlashBag()->set('notice', $message);
    									return $this->redirect($this->generateUrl('residents_hr_residentphotos'));
    								}
    								error_log('6');
    								if ($residentPhotos->getId()) {
    
    									$tmp=$functions->getResidentPhotos($residentId, $domainId, false, true, 600, $residentPhotos->getId());
    									$photodata=reset($tmp);
    									// error_log('photodata:'.print_r($photodata, true));
    									// error_log('orig dim: '.$photodata['origWidth'].'x'.$photodata['origHeight']);
    									// error_log('dim: '.$photodata['width'].'x'.$photodata['height']);
    									$tmpPhoto=$this->getDoctrine()
    										->getRepository('TimesheetHrBundle:ResidentPhotos')
    										->findOneBy(array('id'=>$residentPhotos->getId()));
    
    									$tmpPhoto->setPhoto(base64_decode($photodata['photo']));
    									$em->flush($tmpPhoto);
    									 
    									$message='Photo uploaded';
    								} else {
    									$message='Error occured';
    								}
    
    								$session->getFlashBag()->set('notice', $message);
    								return $this->redirect($this->generateUrl('residents_hr_residentphotos'));
    								 
    							}
    						}
    					}
    				}
    				break;
    			}
    			default: {
    				$photos=$functions->getResidentPhotos($residentId, $domainId, true, true, 200);
    				if ($photos && count($photos)) {
    					foreach ($photos as $k=>$photo) {
    						$images[$k]=$photo;
    					}
    				}
    				break;
    			}
    		}
    			
    		return $this->render('TimesheetHrBundle:Residents:residentPhotos.html.twig', array(
    				'title'		=> $functions->getPageTitle('Photos'),
    				'form'		=> ((isset($form))?($form->createView()):(null)),
    				'resident'	=> $resident,
    				'photos'	=> $photos,
    				'images'	=> $images
    		));
    
    
    	} else {
    		error_log('no userId...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    
    
    }
    
    public function residentcontactsAction($action, $residentid, $contactid) {
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    		error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	 
    	$session=$this->get('session');
    	$sess=array('residentId'=>null, 'contactId'=>null);
    	if ($residentid || $contactid) {
    		if ($session->has('residentContacts')) {
    			$tmpSess=$session->get('residentContacts');
    			if (isset($tmpSess['residentId'])) {
    				$sess['residentId']=$tmpSess['residentId'];
    			}
    			if (isset($tmpSess['contactId'])) {
    				$sess['contactId']=$tmpSess['contactId'];
    			}
    		}
    		if ($residentid) {
    			$sess['residentId']=$residentid;
    		}
    		if ($contactid) {
    			$sess['contactId']=$contactid;
    		}
    		$session->set('residentContacts', $sess);
    		return $this->redirect($this->generateUrl('residents_hr_residentcontacts', array('action'=>$action)));
    	}
    	if ($session->has('residentContacts')) {
    		$sess=$session->get('residentContacts');
    		$residentId=((isset($sess['residentId']))?($sess['residentId']):(null));
    		$contactId=((isset($sess['contactId']))?($sess['contactId']):(null));
    	} else {
    		$residentId=null;
    		$contactId=null;
    	}
    
    	if ($residentId) {
    		$functions=$this->get('timesheet.hr.functions');
    		$request=$this->getRequest();
    		$domainId=$functions->getDomainId($request->getHttpHost());
    
    		$resident=$this->getDoctrine()
    			->getRepository('TimesheetHrBundle:Residents')
    			->findOneBy(array('id'=>$residentId));
    			
    		$residentContact=$this->getDoctrine()
    			->getRepository('TimesheetHrBundle:ResidentContacts')
    			->findOneBy(array('id'=>$contactId));
    		
    		switch ($action) {
    			case 'new':
    			case 'edit' : {
    				if ($action == 'new') {
    					$residentContact=new ResidentContacts();
    				}
    				$form=$this->createForm(new ResidentContactType($resident, $residentContact, $functions->getTitles()));
    
    				if ($request->getMethod() == 'POST') {
    					$form->handleRequest($request);
    					if ($form->isValid()) {
    						if ($form->get('cancel')->isClicked()) {
    							$session->remove('residentContacts');
    							return $this->redirect($this->generateUrl('residents_hr_residentcontacts', array('action'=>'show', 'residentid'=>$residentid)));
    						} else {
    
    							$data=$form->getData();
   							 
error_log('data:'.print_r($data, true));
    								
    
   								try {
   									$currentUser=$this->getUser();
    									 
   									$residentContact->setCreatedOn(new \DateTime());
    								$residentContact->setCreatedBy($currentUser->getId());
    								$residentContact->setResidentId($residentId);
    								$residentContact->setTitle($data['title']);
    								$residentContact->setFirstName(''.$data['firstName']);
    								$residentContact->setLastName(''.$data['lastName']);
    								$residentContact->setEmergency($data['emergency']);
    								$residentContact->setRelation(''.$data['relation']);
    								$residentContact->setEmail(''.$data['email']);
    								$residentContact->setPreferredPhone(''.$data['preferredPhone']);
    								$residentContact->setPhoneLandline(''.$data['phoneLandline']);
    								$residentContact->setPhoneMobile(''.$data['phoneMobile']);
    								$residentContact->setPhoneOther(''.$data['phoneOther']);
    								$residentContact->setAddressLine1(''.$data['addressLine1']);
    								$residentContact->setAddressLine2(''.$data['addressLine2']);
    								$residentContact->setAddressCity(''.$data['addressCity']);
    								$residentContact->setAddressCounty(''.$data['addressCounty']);
    								$residentContact->setAddressCountry(''.$data['addressCountry']);
    								$residentContact->setAddressPostcode(''.$data['addressPostcode']);
    								$residentContact->setNotes(''.$data['notes']);

    								$em=$this->getDoctrine()->getManager();
    								$em->persist($residentContact);
    								$em->flush($residentContact);
    							} catch (\Exception $e) {
    								error_log('Database error:'.$e->getMessage());
    								$message='Database error, could not save';
    								$session->getFlashBag()->set('notice', $message);
    								return $this->redirect($this->generateUrl('residents_hr_residentcontacts'));
    							}

   								if ($residentContact->getId()) {    
    								$session->getFlashBag()->set('notice', 'Contact'.(($contactId)?(' updated'):(' saved')));
    								return $this->redirect($this->generateUrl('residents_hr_residentcontacts'));
    								 
    							}
    						}
    					}
    				}
    				break;
    			}
    			default: {
    				$contacts=$functions->getResidentContacts($residentId, $domainId);
    				break;
    			}
    		}
    			
    		return $this->render('TimesheetHrBundle:Residents:residentContacts.html.twig', array(
    				'title'		=> $functions->getPageTitle('Resident Contacts'),
    				'form'		=> ((isset($form))?($form->createView()):(null)),
    				'resident'	=> $resident,
    				'contacts'	=> ((isset($form))?(null):($contacts))
    		));
    
    
    	} else {
    		error_log('no userId...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    
    
    }

    
    public function roomsAction($action, $param1, $param2) {
    	 
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    
    	$base=$this->getRequest()->attributes->get('_route');
    	$functions = $this->get('timesheet.hr.functions');
    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	$session=$this->get('session');
    	$session->set('menu', Constants::MENU_ADMIN);
    
    	if ($action == 'clean') {
    		$session->remove('admin');
    		return $this->redirect($this->generateUrl($base));
    	}
    	 
    	if (in_array($action, Constants::adminActions) && $param1!=null) {
    		 
    		$session->set('admin', array('action'=>$action, 'param1'=>$param1, 'param2'=>$param2));
    		 
    		return $this->redirect($this->generateUrl($base));
    	}
    	$admin=array();
    
    	if ($session->get('admin')) {
    		$admin=$session->get('admin');
    	}
    	 
    	if ($admin) {
    		if (isset($admin['param1'])) {
    			$new=($admin['param1'] == 0);
    		} else {
    			$new=false;
    		}
    
    		$em=$this->getDoctrine()->getManager();
    
    		switch ($admin['action']) {
    			 
    			case 'editroom' : {
    
    				if ($new) {
    					$room=new Rooms();
    				} else {
    					$room=$this->getDoctrine()
	    					->getRepository('TimesheetHrBundle:Rooms')
	    					->findOneBy(array('id'=>$admin['param1']));
    				}
    
    				$roomForm=$this->createForm(new RoomType($room, $functions->getLocation(null, true, $domainId)));
    
    				$roomForm->handleRequest($this->getRequest());
    
    				if ($roomForm->isValid()) {
    
    					if ($roomForm->get('cancel')->isClicked()) {
    						$session->remove('admin');
    						return $this->redirect($this->generateUrl($base));
    					}
    
    					$data=$roomForm->getData();
    
    					$room->setRoomNumber(''.$data['roomNumber']);
    					$room->setLocationId($data['locationId']);
    					$room->setPlaces($data['places']);
    					$room->setExtraPlaces(0+$data['extraPlaces']);
    					$room->setOpen($data['open']);
    					$room->setActive($data['active']);
    					$room->setNotes(''.$data['notes']);
    
    					if ($new) {
    						$em->persist($room);
    					}
    					$em->flush($room);
    
   						$msg='Room ('.$room->getRoomNumber().') saved';
   						$session->remove('admin');
   						$session->getFlashBag()->set('notice', $msg);
    
   						return $this->redirect($this->generateUrl($base));
    				}
    				break;
    			}
    		}
    	}
    	 
    	return $this->render('TimesheetHrBundle:Residents:rooms.html.twig', array(
    		'base'			=> $base,
    		'title'			=> $functions->getPageTitle('Rooms'),
    		'roomForm'		=> ((isset($roomForm))?($roomForm->createView()):(null)),
    		'locations'		=> $functions->getLocation(null, true, $domainId)
    	));
    }

    public function roommenuAction($base) {
    
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    
    	$functions=$this->get('timesheet.hr.functions');
    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	 
    	return $this->render('TimesheetHrBundle:Internal:roommenu.html.twig', array(
    		'base'		=> $base,
    		'rooms'		=> $functions->getRooms($domainId),
    		'locations' => $functions->getLocation()
    	));
    	 
    }
    
    
    public function residentmoveAction($id) {
error_log('residentmoveAction');
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	$base=$this->getRequest()->attributes->get('_route');
    	$functions=$this->get('timesheet.hr.functions');
    	$session=$this->get('session');
    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	
    	$currentLocation=$functions->getResidentLocation($id, new \DateTime());
//    	$rooms=$functions->getRooms($domainId);
    	$resident=$this->getDoctrine()
    		->getRepository('TimesheetHrBundle:Residents')
    		->findOneBy(array('id'=>$id));
    	if (!$resident) {
    		// if no resident id defined or wrong, redirect to start page
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}

		$moveForm=$this->createForm(new ResidentMoveType($resident, $functions->getRooms($domainId, false, true, $currentLocation['id']), $currentLocation));
    
		$moveForm->handleRequest($this->getRequest());
    
		if ($moveForm->isValid()) {
    
			if ($moveForm->get('cancel')->isClicked()) {
//				$session->remove('admin');
				return $this->redirect($this->generateUrl('residents_hr_list'));
			}
    
			$data=$moveForm->getData();
// error_log('data:'.print_r($data, true));
			$msg='';
			$error=false;
			$allRooms=$functions->getRooms($domainId, true, true);
			$currentUser=$this->getUser();
			if ($data['roomId'] == '0') {
				if ($functions->setResidentMoveOut($resident->getId(), $data['currentRoomId'], $data['date'], $data['notes'], $currentUser->getId())) {
					$msg='Moved out from room '.$allRooms[$data['currentRoomId']];
				} else {
					$msg='Error saving data';
					$error=true;
				}	
			} else {
				if ($functions->setResidentMoveIn($resident->getId(), $data['roomId'], $data['date'], $data['notes'], $currentUser->getId(), $data['currentRoomId'])) {
					$msg='Moved into '.$allRooms[$data['roomId']];
				} else {
					$msg='Error saving data';
					$error=true;
				}
			}
			$session->getFlashBag()->set('notice', $msg);
			if ($error) {
				return $this->redirect($this->generateUrl('residents_hr_move', array('id'=>$id)));
			} else {
				return $this->redirect($this->generateUrl('residents_hr_list'));
			}
		}
			
    	return $this->render('TimesheetHrBundle:Residents:residentMove.html.twig', array(
    		'title'				=> $functions->getPageTitle('Resident Move'),
    		'moveForm'			=> ((isset($moveForm))?($moveForm->createView()):(null)),
    		'resident'			=> $resident,
    		'currentLocation'	=> $currentLocation,
//    		'rooms'				=> $rooms,
    		'locations' 		=> $functions->getLocation()
	    ));
    }
    
}
