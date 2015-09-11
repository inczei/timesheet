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
use Timesheet\Bundle\HrBundle\Form\Type\QualificationType;
use Timesheet\Bundle\HrBundle\Form\Type\ResetPasswordType;
use Timesheet\Bundle\HrBundle\Form\Type\ShiftType;
use Timesheet\Bundle\HrBundle\Form\Type\StaffRequirementsType;
use Timesheet\Bundle\HrBundle\Form\Type\QualRequirementsType;
use Timesheet\Bundle\HrBundle\Form\Type\StatusType;
use Timesheet\Bundle\HrBundle\Form\Type\TimingType;
use Timesheet\Bundle\HrBundle\Form\Type\UserQualificationType;
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
use Timesheet\Bundle\HrBundle\Entity\StaffRequirements;
use Timesheet\Bundle\HrBundle\Entity\Shifts;
use Timesheet\Bundle\HrBundle\Entity\ShiftDays;
use Timesheet\Bundle\HrBundle\Entity\Status;
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



class DefaultController extends Controller
{

	
	const MENU_HOMEPAGE = 1;
	const MENU_LOGIN = 2;
	const MENU_REGISTER = 3;
	const MENU_STATUS = 4;
	const MENU_TIMESHEET = 5;
	const MENU_SCHEDULE = 6;
	const MENU_HOLIDAY = 7;
	const MENU_ADMIN = 8;
	const MENU_CONFIG = 9;
	const MENU_MESSAGES = 10;
	const MENU_SYSADMIN = 11;
	
	const MENU_ITEMS = 11;

	private $adminActions=array(
		'edituser',
		'editlocation',
		'editcontract',
		'edittiming',
		'editgroup',
		'editqualification',
		'edituserqualification',
		'editstatus',
		'editshift',
		'editsreq',
		'editqreq'
	);
	

    public function indexAction() {

    	$message='';
    	$error=true;
    	
    	$session=$this->get('session');
    	
   		$session->set('menu', self::MENU_HOMEPAGE);
   		$request=$this->getRequest();
   		$functions=$this->get('timesheet.hr.functions');
   		$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
   		$functions->setTimezoneSession($session, $request);

   		$form=$this->createForm(new PunchType($this->getStatus()));
    	$form->handleRequest($request);

		if ($request->isMethod('POST') && $form->isSubmitted()) {
    	 	
	    	if ($form->isValid()) {
	   			
	   			$data=$form->getData();
	   			
		    	$userManager = $this->container->get('fos_user.user_manager');
	    		
	    		$user=$userManager->findUserByUsername($data['uname']);
	    		
	    		if ($user) {
	    			
	    			if ($functions->isLoginRequired($user->getUsername(), $domainId)) {
	    			
			    		$encoder_service = $this->get('security.encoder_factory');
			    		$encoder = $encoder_service->getEncoder($user);
			    		
			    		if ($encoder->isPasswordValid($user->getPassword(), $data['upass'], $user->getSalt())) {
	
			    			$newStatus=$this->getStatus($data['status'], false);
			    			if ($newStatus) {
			    				if ($this->getStatusAcceptable($user, $newStatus)) {
				    				$error=false;
				    				$message='Password accepted<br>Your new status : '.$newStatus['name'];
			    				} else {
			    					if ($user->getLastStatus()) {
			    						$currentStatus=$this->getStatus($user->getLastStatus());
			    						$cStatus=$currentStatus[$user->getLastStatus()];
			    					} else {
			    						$cStatus='None';
			    					}
			    					$message='Please select another status. Your current status : '.$cStatus;
			    				}
			    			} else {
			    				$message='Invalid status';
			    			}
			    		} else {
			    			$message='Invalid username or password';
			    		}
	    			} else {
	    				$message='You are not required to change status';
	    			}
	    		} else {
	    			error_log('Invalid username:'.$data['uname']);
	    			$message.='Invalid username'.$data['uname'];
	    		}
	    		
	    		if (!$error) {
	    			$message.=$this->savePunchStatus($user->getId(), $data['status'], $data['comment']);	    			
	    			
					$session->getFlashBag()->set('login', $message);
				
					error_log($message);
					return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
	    		}
	    	}

	    	$session->getFlashBag()->set('login', $message);
				
			error_log($message);
			return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
		}
        return $this->render('TimesheetHrBundle:Default:index.html.twig', array(
        	'form'		=> $form->createView(),
        	'message'	=> $message,
        	'title'		=> $this->getPageTitle('Home')
        ));
    }
    

    public function loginAction() {
    	
    	$message='';
    	$session = $this->get('session');
    	$session->set('menu', self::MENU_LOGIN);
    	$request=$this->getRequest();
    	
    	$form=$this->createForm(new LoginType());
    	$form->handleRequest($request);

    	if ($form->isSubmitted() && $form->isValid()) {
    		$data=$form->getData();
    		
			$functions=$this->get('timesheet.hr.functions');
			$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
			
    		$userManager = $this->container->get('fos_user.user_manager');
    		$user=$userManager->findUserBy(array('username'=>$data['uname'], 'domainId'=>$domainId));
    		
    		if ($user && $user->getDomainId() == $functions->getDomainId($request->getHttpHost())) {
	    		$encoder_service = $this->get('security.encoder_factory');
	    		$encoder = $encoder_service->getEncoder($user);
    		
	    		if ($encoder->isPasswordValid($user->getPassword(), trim($data['upass']), $user->getSalt())) {
	
	    			$providerKey = $this->container->getParameter('fos_user.firewall_name');
	    			$token = new UsernamePasswordToken($user, $data['upass'], $providerKey, $user->getRoles());
	    			$this->get("security.context")->setToken($token);
	    			
	    			// Fire the login event
	    			$event = new InteractiveLoginEvent($this->getRequest(), $token);
	    			$this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
	    			
	    			$message.='Password accepted';
	
	    			$session->set('timezone', $functions->getTimezone($request->getHttpHost()));
//	    			$message.=', timezone:'.$session->get('timezone');
	    			$session->getFlashBag()->set('login', $message);
	    			
	    			error_log($message);
	    			return $this->redirect($this->generateUrl('timesheet_hr_status'));
	    			
	    		} else {
	    			$message.='Wrong password for '.$data['uname'];
	    		}
    		} else {
    			$message.='Wrong username:'.$data['uname'];
    		}
    		error_log($message);
    	}
    	
    	return $this->render('TimesheetHrBundle:Default:login.html.twig', array(
    		'form'	=> $form->createView(),
    		'message'=> $message
    	));
    	 
    }

    
    public function resetpasswordAction($link) {
error_log('resetpasswordAction');
error_log('link:'.$link);
		$request=$this->getRequest();
		$session = $this->get('session');
		$functions=$this->get('timesheet.hr.functions');
		if ($link && strlen($link) == $functions->link_length) {
		    $passwordReset=$this->getDoctrine()
		    	->getRepository('TimesheetHrBundle:PasswordReset')
		    	->findOneBy(
		    		array(
		    			'valid'=>true,
		    			'link'=>$link
		    		));
		    		
		    if ($passwordReset && count($passwordReset)) {
		    	$session->set('resetpassword', $link);
		    	return $this->redirect($this->generateUrl('timesheet_hr_resetpassword'));
		    }
		} else {
			if ($session->has('resetpassword')) {
				$link=$session->get('resetpassword');
error_log('session link:'.$link);

				$passwordReset=$this->getDoctrine()
					->getRepository('TimesheetHrBundle:PasswordReset')
					->findOneBy(
						array(
							'valid'=>true,
							'link'=>$link
					));

				$form=$this->createForm(new ResetPasswordType($link));
				$form->handleRequest($request);

				if ($form->isSubmitted() && $form->isValid()) {

					$data=$form->getData();
					$userManager = $this->container->get('fos_user.user_manager');

    				$user=$userManager->findUserBy(array('id'=>$passwordReset->getUserId()));
    				
    				if ($data['username'] == $user->getUsername()) {
    					$user->setPlainPassword($data['password']);
    					$userManager->updateUser($user);
    					$session->remove('resetpassword');
    				
    					$em=$this->getDoctrine()->getManager();
    					$passwordReset->setValid(false);
    					$em->flush($passwordReset);
    					$session->getFlashBag()->set('notice', 'Your password has changed. Please login now');
    					
    					return $this->redirect($this->generateUrl('timesheet_hr_login'));
    				} else {
    					$session->getFlashBag()->set('notice', 'Wrong details entered. Please try again.');    					
    				}
				}
				
				return $this->render('TimesheetHrBundle:Security:resetpassword.html.twig', array(
					'form'	=> $form->createView(),
					'title'	=> 'Reset Password'
				));
				
			}
		}
		
		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));    	
    }
    
    
    public function forgotpasswordAction() {
error_log('forgotpasswordAction');

    	$message='';
    	$link=null;
    	$session = $this->get('session');
    	$request=$this->getRequest();
    	
    	$form=$this->createForm(new ForgotPasswordType());
    	$form->handleRequest($request);

    	if ($form->isSubmitted() && $form->isValid()) {
    		$data=$form->getData();
// error_log('data:'.print_r($data, true));    		
			$functions=$this->get('timesheet.hr.functions');
			$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
			
    		$userManager = $this->container->get('fos_user.user_manager');

    		$user=$userManager->findUserByUsername($data['username']);
    		
    		if ($form->get('cancel')->isClicked()) {
    			return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    		}
    		if ($user && $user->getDomainId() == $domainId && $user->getEmail() == $data['email']) {
   			
	    		$message.='Details accepted, but message not yet sent';
	    		$canSend=false;
	    		$new=false;
	    		
	    		$em=$this->getDoctrine()->getManager();

	    		// Valid only 1 day
	    		$qb=$em->createQueryBuilder();
	    		$qb->update('TimesheetHrBundle:PasswordReset', 'pr')
	    			->set('pr.valid', ':v1')
	    			->Where('pr.createdOn <= DATE_SUB(CURRENT_DATE(), 1, \'day\')')
	    			->andWhere($qb->expr()->eq('pr.valid', ':v2'))
	    			->setParameter('v1', false)
	    			->setParameter('v2', false);
		    	
		    	$qb->getQuery()->execute();

	    		
	    		$passwordReset=$this->getDoctrine()
	    			->getRepository('TimesheetHrBundle:PasswordReset')
	    			->findOneBy(
	    				array(
	    					'valid'=>true,
	    					'userId'=>$user->getId(),
	    					'email'=>$user->getEmail()
	    				));
	    		
	    		if ($passwordReset && count($passwordReset)) {
	    			$link=$passwordReset->getLink();
error_log('old link:'.$link);
					$dt1=$passwordReset->getLastSent();
					$interval=$dt1->diff(new \DateTime('now'));
					$minutes=60*24*$interval->format('%d')+60*$interval->format('%h')+$interval->format('%i');
error_log('minutes:'.$minutes);
					if ($minutes <= 10) {
						// if less than 10 minutes ago requested a link, only show a message to wait...
						$canSend=false;
						$message='You requested a password reset link recently. Please allow 10 minutes to arrive.';
					} else {
						// anyway send a reminder
						$canSend=true;
						$new=false;
					}
	    			
	    		} else {
					// if we have no request in the last day, create a new link and send
	    			$canSend=true;
	    			$new=true;
	    		}
	    		
	    		if ($canSend) {
	    			if ($new) {
	    				$link=$functions->createUniqueId();
error_log('new link:'.$link);
	    				$passwordReset=new PasswordReset();
		    			 
		    			$passwordReset->setCreatedOn(new \DateTime('now'));
		    			$passwordReset->setLastSent(new \DateTime('now'));
		    			$passwordReset->setEmail($user->getEmail());
		    			$passwordReset->setLink($link);
		    			$passwordReset->setUserId($user->getId());
		    			$passwordReset->setValid(true);
		    			 
		    			$em->persist($passwordReset);
	    			} else {
	    				$passwordReset->setLastSent(new \DateTime('now'));
	    			}
	    			$em->flush($passwordReset);
	    				    			 
		    		$name=trim($user->getFirstName().' '.$user->getLastName());
		    		$companyname=$functions->getConfig('companyname', $domainId);
		    		
		    		$email=\Swift_Message::newInstance()
		    			->setSubject('Reset Password'.(($new)?(''):(' reminder')))
		    			->setFrom('info@skillfill.co.uk', $companyname)
		    			->setTo($user->getEmail(), $name)
		    			->setContentType('text/html')
		    			->setBody($this->renderView('TimesheetHrBundle:Emails:forgotpassword.html.twig', 
		    				array(
		    					'name'=>$name,
		    					'companyname'=>$companyname,
		    					'link'=>$link
		    				),
		    				'text/html'));
		    		
		    		$sent=$this->get('mailer')->send($email);
		    		
		    		if ($sent) {
		    			$message=(($new)?(''):('Reminder ')).'E-mail sent to your '.$user->getEmail().' e-mail address. Please allow 10 minutes to arrive.';
		    		}
	    		}
	
    		} else {
    			$message.='Wrong details';
    		}

    		error_log($message);
    		$session->getFlashBag()->set('notice', $message);
    		$message='';
    		
    	}
    	
    	return $this->render('TimesheetHrBundle:Security:forgotpassword.html.twig', array(
    		'form'	=> $form->createView(),
    		'title'	=> 'Forgot Password',
    		'message'=> $message
    	));
    	 
    }
    
    
    public function statusAction() {

    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	 
    	$message='';
    	$session=$this->get('session');
    	$session->set('menu', self::MENU_STATUS);

    	return $this->render('TimesheetHrBundle:Default:status.html.twig', array(
    			'message'	=> $message,
    			'title'		=> $this->getPageTitle('Status')
    	));
    	 
    }

    
    public function configAction() {
    	 
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') || (TRUE !== $securityContext->isGranted('ROLE_ADMIN') && TRUE !== $securityContext->isGranted('ROLE_MANAGER'))) {
    		error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	$base=$this->getRequest()->attributes->get('_route');
    
    	$session=$this->get('session');
    	$session->set('menu', self::MENU_CONFIG);

    	$request=$this->getRequest();
    	
    	$functions=$this->get('timesheet.hr.functions');
    	
    	$hct=$functions->getHolidayCalculations($functions->getDomainId($this->getRequest()->getHttpHost()));
    	if (count($hct)) {
    		$hct[0]='System default ('.$hct[$functions->getConfig('hct')].')';
    	}
    	$dId=$functions->getDomainId($request->getHttpHost());
    	$result=$this->getDoctrine()
    		->getRepository('TimesheetHrBundle:Companies')
    		->findOneBy(array('id'=>$dId));

    	$conf=array(
    		'companyname'=>$result->getCompanyname(),
    		'domain'=>$result->getDomain(),
    		'timezone'=>$result->getTimezone(),
    		'hct'=>$result->getHCT(),
    		'yearstart'=>$result->getYearstart(),
    		'ahew'=>$result->getAHEW(),
    		'lunchtime'=>$result->getLunchtime(),
    		'lunchtimeUnpaid'=>$result->getLunchtimeUnpaid()
    	);
    	 
    	$form=$this->createForm(new ConfigType($conf, $functions->getTimezone(), $hct));
    	$form->handleRequest($request);
    	if ($form->isValid()) {
    	
    		$message='Valid';
    	
    		$data=$form->getData();
    		
    		$result->setCompanyname($data['companyname']);
    		$result->setDomain($data['domain']);
    		$result->setTimezone($data['timezone']);
    		$result->setHCT($data['hct']);
    		$result->setAHEW($data['ahew']);
    		$result->setLunchtime($data['lunchtime']);
    		$result->setLunchtimeUnpaid($data['lunchtimeUnpaid']);
    		$result->setYearstart($data['yearstart']);
    		
    		$em=$this->getDoctrine()->getManager();
    		
    		$em->persist($result);
			$em->flush($result);
    		
			if ($result->getId()) {
				$message='Config settings updated';
				$session->getFlashBag()->set('notice', $message);
				
				error_log($message);
				return $this->redirect($this->generateUrl('timesheet_hr_config'));
			}
    		 
    	}
    	return $this->render('TimesheetHrBundle:Default:config.html.twig', array(
    			'base'		=> $base,
    			'form'		=> $form->createView(),
    			'title'		=> $this->getPageTitle('Config'),
    	));
    }

    
    public function messagesAction($action, $page) {
    	 
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    		error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}

    	$user=$this->getUser();
    	
    	if (!$user) {
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}

    	$base=$this->getRequest()->attributes->get('_route');
    	$user=$this->getUser();
    	$session=$this->get('session');
    	$session->set('menu', self::MENU_MESSAGES);

    	$folders=array('Inbox', 'Draft', 'Sent');
    	$actions=array('New', 'Reply', 'Forward', 'Delete');
    	$headers=array();
    	if (!in_array($action, $folders) && !in_array($action, $actions)) {
    		if (!$page) {
    			$page=0;
    		}
    		return $this->redirect($this->generateUrl('timesheet_hr_messages', array('action'=>$folders[0], 'page'=>$page)));
    	}
    	$functions=$this->get('timesheet.hr.functions');
    	$request=$this->getRequest();
    	$em=$this->getDoctrine()->getManager();
    	
    	switch ($action) {
    		case 'Inbox' : {
    			$headers=array('From', 'Subject', 'Date');
    			break;
    		}
    		case 'Draft' :
    		case 'Sent' : {
    			$headers=array('To', 'Subject', 'Date');
    			break;
    		}
    		case 'New' :
    		case 'Reply' :
    		case 'Forward' :
    		case 'Delete' : {
    	    	if ($page) {
    	    		// Edit draft message
    	    		
    	    		switch ($action) {
    	    			case ' New' : {
    	    				$search=array('id'=>$page, 'status'=>null);
    	    				break;
    	    			}
    	    			default : {
    	    				$search=array('id'=>$page);
    	    				break;
    	    			}
    	    		}
    	    		$newMessage=$this->getDoctrine()
    	    			->getRepository('TimesheetHrBundle:Messages')
    	    			->findOneBy($search);
    	    		
    	    		if ($newMessage) {
    	    			if ($action=='Delete') {
    	    				
    	    				$newMessage->setDeleted(true);
    	    				$em->flush($newMessage);
    	    				
    	    				$session->getFlashBag()->set('notice', 'Message deleted');
    	    				return $this->redirect($this->generateUrl('timesheet_hr_messages', array('action'=>$folders[0], 'page'=>null)));
    	    			}
    	    		} else {    	    			
    		   			$session->getFlashBag()->set('notice', 'Wrong message ID');
    		    		return $this->redirect($this->generateUrl('timesheet_hr_messages', array('action'=>$folders[0], 'page'=>null)));    		    			
    		    	}
    		    	switch ($action) {
    		    		case 'New' : {
    		   				$message=array(
    		   					'id'=>$newMessage->getId(),
			   					'sender'=>trim($user->getFirstName().' '.$user->getLastName()),
			   					'recipient'=>$newMessage->getRecipient(),
			   					'subject'=>$newMessage->getSubject(),
			   					'content'=>$newMessage->getContent()
			   				);
    		    			break;
    		    		}
    		    		case 'Reply' : {
    		    			$message=array(
   		    					'id'=>null,
   		    					'sender'=>trim($user->getFirstName().' '.$user->getLastName()),
   		    					'recipient'=>$newMessage->getCreatedBy(),
   		    					'subject'=>'Re:'.$newMessage->getSubject(),
   		    					'content'=>PHP_EOL.PHP_EOL.'>>>'.PHP_EOL.$functions->createMessageView($newMessage, null, true)
    		    			);
    		    			$newMessage=new Messages();
    		    			break;
    		    		}
    		    		case 'Forward' : {
    		    			$message=array(
    		    				'id'=>null,
    		    				'sender'=>trim($user->getFirstName().' '.$user->getLastName()),
    		    				'recipient'=>null,
    		    				'subject'=>'Fwd:'.$newMessage->getSubject(),
    		    				'content'=>PHP_EOL.PHP_EOL.'>>>'.PHP_EOL.$functions->createMessageView($newMessage, null, true)
    		    			);
    		    			$newMessage=new Messages();
    		    			break;
    		    		}
    		    	}
    		    		
    		   	} else {
    		   		// Create a new empty message
    		   		$message=array(
    					'id'=>0,
    					'sender'=>trim($user->getFirstName().' '.$user->getLastName()),
    					'recipient'=>'',
    					'subject'=>'',
    					'content'=>''
    		   		);
    		   		$newMessage=new Messages();
    		    		
    		   	}
			
			   	$form=$this->createForm(new MessageType($message, $functions->getRecipients($user->getDomainId(), $user->getId())));
			   	$form->handleRequest($request);
			   	if ($form->isValid()) {
			   		if ($form->get('cancel')->isClicked()) {
			   			return $this->redirect($this->generateUrl('timesheet_hr_messages'));
			   		} else { // if ($userForm->get('submit')->isClicked()) {

			   			$draft=($form->get('saveasdraft')->isClicked());
			    			
			   			$message='Valid';
			   			$data=$form->getData();
			    			
			   			$newMessage->setCreatedOn(new \DateTime('now'));
			   			$newMessage->setCreatedBy($user->getId());
			   			$newMessage->setRecipient($data['recipient']);
			   			$newMessage->setSubject($data['subject']);
			   			$newMessage->setContent($data['content']);
			   			$newMessage->setStatus(($draft)?(null):(false));
			   			$newMessage->setReplyId(null);

			   			if (!$data['id']) {
			   				$em->persist($newMessage);
			   			}
			   			$em->flush($newMessage);
			    			
			   			if ($newMessage->getId()) {
			   				if ($draft) {
			   					$session->getFlashBag()->set('notice', 'Message saved as draft');
			   				} else {

			   					$message='Message sent';
			   					$recipient=$this->getDoctrine()
			   						->getRepository('TimesheetHrBundle:User')
			   						->find($data['recipient']);
			   					
			   					$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());			   					
			   					$companyname=$functions->getConfig('companyname', $domainId);
			   					$name=trim($recipient->getTitle().' '.$recipient->getFirstName().' '.$recipient->getLastName());
			   					
			   					if ($recipient->getExEmail()) {
				   					$email=\Swift_Message::newInstance()
					   					->setSubject($data['subject'].' [do not reply]')
					   					->setFrom('info@skillfill.co.uk', $companyname)
					   					->setReplyTo('noreply@skillfill.co.uk')
					   					->setTo($recipient->getEmail(), $name)
					   					->setContentType('text/html')
					   					->setBody($this->renderView('TimesheetHrBundle:Emails:message.html.twig',
				   							array(
			   									'name'=>$name,
				   								'sender'=>trim($user->getFirstName().' '.$user->getLastName()),
				   								'subject'=>$data['subject'],
			   									'content'=>$data['content'],
				   								'companyname'=>$companyname
				   							),
				   							'text/html'));
			   					
				   					$sent=$this->get('mailer')->send($email);
			   					
				   					if ($sent) {
				   						$message='Message and e-mail sent.';
				   					}
			   					}
			   					 
			   					$session->getFlashBag()->set('notice', $message);
			   				}
				
							return $this->redirect($this->generateUrl('timesheet_hr_messages'));
			   			} else {
			   				$session->getFlashBag()->set('notice', 'Message sending problem');
			   			}
			   		}
			   	}
    			break;
    		}
    	}

    	if (isset($form)) {
    		$msg=null;
    		$total=0;
    		$pages=0;
    		$current=0;
    	} else {
    		$tmp=$functions->getMessageHeaders($user->getId(), $action, $page);
       		$msg=$tmp['headers'];
    		$total=$tmp['total'];
    		$pages=$tmp['pages'];
    		$current=$tmp['current'];
    		
    		if (count($msg) == 0 && $total > 0) {
    			// Wrong page number
    			return $this->redirect($this->generateUrl('timesheet_hr_messages', array('action'=>$action, 'page'=>0)));
    		}
    	}
    	
        return $this->render('TimesheetHrBundle:Default:messages.html.twig', array(
    		'base'		=> $base,
    		'title'		=> $this->getPageTitle('Messages'),
        	'form'		=> ((isset($form))?($form->createView()):(null)),
        	'folder'	=> $action,
        	'folders'	=> $folders,
        	'headers'	=> $headers,
        	'total'		=> $total,
        	'pages'		=> $pages,
        	'current'	=> $current,
        	'messages'	=> $msg
    	));
    }
    
    
    public function scheduleAction($locationId, $timestamp) {
    	
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') || (TRUE !== $securityContext->isGranted('ROLE_ADMIN') && TRUE !== $securityContext->isGranted('ROLE_MANAGER'))) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	
    	$base=$this->getRequest()->attributes->get('_route');
    	$session=$this->get('session');
    	$session->set('menu', self::MENU_SCHEDULE);

    	if (strlen($locationId) || $timestamp) {
    		if ($session->get('schedule')) {
    			$calendar=$session->get('schedule');
    		} else {
    			$calendar=array();
    		}
    		if (strlen($locationId)) {
    			$calendar['locationId']=$locationId;
    		}
    		if ($timestamp) {
    			$calendar['timestamp']=$timestamp;
    		}
    		$session->set('schedule', $calendar);
    	
    		return $this->redirect($this->generateUrl('timesheet_hr_schedule'));
    	} else {
    		if ($session->get('schedule')) {
    			$calendar=$session->get('schedule');

    			if (isset($calendar['locationId']) && $calendar['locationId'] > 0) {
    				$locationId=$calendar['locationId'];
    			} else {
    				$locationId=0;
    			}
    			$calendar['locationId']=$locationId;
    			if (isset($calendar['timestamp']) && $calendar['timestamp']) {
    				$timestamp=$calendar['timestamp'];
    			} else {
    				$timestamp=time();
    			}
    			$calendar['timestamp']=$timestamp;
    			$session->set('schedule', $calendar);
    		} else {
    			$timestamp=time();
    			$calendar=array('locationId'=>0, 'timestamp'=>$timestamp);
    			$session->set('schedule', $calendar);
    			return $this->redirect($this->generateUrl('timesheet_hr_schedule'));
    		}
    	}
    	if ($session->get('userSearch')) {
    		$userSearch=$session->get('userSearch');
    	} else {
    		$userSearch='';
    	}
    	if ($session->get('groupSearch')) {
    		$groupSearch=$session->get('groupSearch');
    	} else {
    		$groupSearch='';
    	}
    	if ($session->get('qualificationSearch')) {
    		$qualificationSearch=$session->get('qualificationSearch');
    	} else {
    		$qualificationSearch='';
    	}
    	 
    	return $this->render('TimesheetHrBundle:Default:schedule.html.twig', array(
    		'base'		=> $base,
    		'locationId'=> $locationId,
    		'timestamp'	=> $timestamp,
    		'usersearch'=> $userSearch,
    		'groupsearch'=> $groupSearch,
    		'qualificationsearch'=> $qualificationSearch,
    		'title'		=> $this->getPageTitle('Schedule'),
    	));
    }
    
    
    public function registrationAction() {
    	 
    	$session=$this->get('session');
    	
    	$base=$this->getRequest()->attributes->get('_route');
   		$session->set('menu', self::MENU_REGISTER);
    	
   		$message='';
    	 
    	$request=$this->getRequest();
    	 
    	$functions=$this->get('timesheet.hr.functions');
    	$userManager = $this->container->get('fos_user.user_manager');

		$currentUser=$this->getUser();
		$user=new User();
		$new=true;
    	$form=$this->createForm(new RegisterType($functions->getGroups(), $functions->getLocation(), $functions->getAvailableRoles($currentUser->getRoles()), $functions->getTitles(), $user, $new, $functions->getCompanies()));
    	$form->handleRequest($request);
    	 
    	if ($form->isValid()) {
    
    		$message='Valid';
    
    		if ($form->get('cancel')->isClicked()) {
    			$session->remove('admin');
    			return $this->redirect($this->generateUrl($base));
    		}
    		
    		$data=$form->getData();
    		if ($data['username']) {
    			$username=$data['username'];
    		} else {
    			$username=$this->generateUsername($data['firstName'], $data['lastName']);			    
   				$message.='<br>new username:'.$username;
    		}
    		
			$user=$userManager->createUser();
				
			$user->setUsername($username);

			if ($data['upass']) {
				$user->setPlainPassword($data['upass']);
			}
			$user->setEmail($data['email']);
			$user->setBirthday($data['birthday']);
			$user->setNationality($data['nationality']);
			$user->setNI($data['ni']);
			$user->setPhoneLandline($data['phoneLandline']);
			$user->setPhoneMobile($data['phoneMobile']);
			$user->setFirstName(''.$data['firstName']);
			$user->setLastName(''.$data['lastName']);
			$user->setNokName($data['nokName']);
			$user->setNokPhone($data['nokPhone']);
			$user->setNokRelation($data['nokRelation']);
			$user->setAddressLine1(''.$data['addressLine1']);
			$user->setAddressLine2(''.$data['addressLine2']);
			$user->setAddressCity(''.$data['addressCity']);
			$user->setAddressCounty(''.$data['addressCounty']);
			$user->setAddressCountry(''.$data['addressCountry']);
			$user->setAddressPostcode(''.$data['addressPostcode']);
			$user->setGroupId($data['group']);
			$user->setLocationId($data['location']);
			$user->setPayrolCode(''.$data['payrolCode']);
			$user->setLoginRequired($data['loginRequired']);
			$user->setNotes($data['notes']);
			$user->setTitle($data['title']);
			$user->setIsActive($data['isActive']);
			$user->setEnabled(true);
			$user->setRoles(array($data['role']));
			$user->setDomainId($data['domainId']);
			switch ($data['role']) {
				case 'ROLE_ADMIN' : {
					$user->setGroupAdmin(true);
					$user->setLocationAdmin(true);
					break;
				}
				case 'ROLE_USER' : {
					$user->setGroupAdmin(false);
					$user->setLocationAdmin(false);
					break;
				}
				default : {
					$user->setGroupAdmin((isset($data['groupAdmin']) && $data['groupAdmin'])?true:false);
					$user->setLocationAdmin((isset($data['locationAdmin']) && $data['locationAdmin'])?true:false);
					break;
				}
			}
			$userManager->updateUser($user);
			if ($user->getId()) {
				$session->getFlashBag()->set('notice', 'New user ('.$username.') registered');
				return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
			}
    	}
    	 
    	 
    	 
    	return $this->render('TimesheetHrBundle:Default:register.html.twig', array(
   			'form'	=> $form->createView(),
   			'message'=> $message
    	));
    
    }


    public function timesheetAction($userId='0', $timestamp='0', $usersearch='') {
    
        $securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	
    	$message='';
    	$session=$this->get('session');
    	$base=$this->getRequest()->attributes->get('_route');
    	$user=$this->getUser();
    	
    	$session->set('menu', self::MENU_TIMESHEET);
    	if ($userId || $timestamp || $usersearch) {
    		$session->set('timesheet', array('userId'=>$userId, 'timestamp'=>$timestamp, 'usersearch'=>$usersearch));
    		
    		return $this->redirect($this->generateUrl('timesheet_hr_timesheet'));
		} else {
    		if ($session->get('timesheet')) {

    			$calendar=$session->get('timesheet');
    			if (isset($calendar['userId'])) {
    				$userId=$calendar['userId'];
    			} else {
    				$userId=$user->getId();
    				$calendar['userId']=$userId;
    			}
    			if (isset($calendar['timestamp'])) {
    				$timestamp=$calendar['timestamp'];
    			} else {
    				$timestamp=mktime(0, 0, 0, date('n'), 1, date('Y'));
    				$calendar['timestamp']=$timestamp;
    			}
    		    if (isset($calendar['usersearch'])) {
    				$usersearch=$calendar['usersearch'];
    			} else {
    				$usersearch='';
    				$calendar['usersearch']=$usersearch;
    			}
    			$session->set('timesheet', $calendar);
    			
    		} else {
				$userId=$user->getId();
    			$timestamp=time();
    			$calendar['userId']=$userId;
    			$calendar['timestamp']=$timestamp;
    			$calendar['usersearch']=$usersearch;
    			$session->set('timesheet', $calendar);
    		}
    	}    	

    	return $this->render('TimesheetHrBundle:Default:timesheet.html.twig', array(
    		'base'		=> $base,
    		'userId'	=> $userId,
    		'title'		=> $this->getPageTitle('Timesheet'),
    		'timestamp'	=> $timestamp,
    		'usersearch'=> $usersearch,
   			'message'	=> $message
    	));
    	
    }
    
    	 
    public function holidayAction($userId='0', $timestamp='0') {
    
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	 
    	$message='';
    	$session=$this->get('session');
    	 
    	$session->set('menu', self::MENU_HOLIDAY);

    	$message='';
    	$base=$this->getRequest()->attributes->get('_route');
    	$user=$this->getUser();

    	if (!$user) {
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	if ($userId || $timestamp) {
    		$session->set('calendar', array('userId'=>$userId, 'timestamp'=>$timestamp));
    		
    		return $this->redirect($this->generateUrl('timesheet_hr_holiday'));
    	} else {
    		if ($session->get('calendar')) {
    			$calendar=$session->get('calendar');
    			if (isset($calendar['userId'])) {
    				$userId=$calendar['userId'];
    			} else {
    				$userId=$user->getId();
    				$calendar['userId']=$userId;
    			}
    			if (isset($calendar['timestamp'])) {
    				$timestamp=$calendar['timestamp'];
    			} else {
    				$timestamp=mktime(0, 0, 0, date('n'), 1, date('Y'));
    				$calendar['timestamp']=$timestamp;
    			}
    			$session->set('calendar', $calendar);
    		} else {
    			if ($user) {
    				$calendar['userId']=$user->getId();
    			}
    			$calendar['timestamp']=mktime(0, 0, 0, date('n'), 1, date('Y'));
    			$session->set('calendar', $calendar);
    		}
    	}    	
    	
    	return $this->render('TimesheetHrBundle:Default:holiday.html.twig', array(
    		'base'		=> $base,
    		'userId'	=> $userId,
    		'timestamp'	=> $timestamp,
			'title'		=> $this->getPageTitle('Holiday'),
   			'message'	=> $message
    	));
    }
    
    
    public function resetAction() {
    	
    	$session=$this->get('session');
    	$session->remove('admin');
    	$session->remove('calendar');
    	return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    }

    
    public function usermenuAction($base, $domainId) {
    	
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    		error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}

    	$functions=$this->get('timesheet.hr.functions');
    	
    	$session=$this->get('session');
    	if ($session->get('userSearch')) {
    		$userSearch=$session->get('userSearch');
    		// error_log('user search loaded : '.$userSearch);
    	} else {
    		$userSearch='';
    	}
    	 
		$users=$functions->getUsersList(null, ((strlen($userSearch))?($userSearch):(null)), false, null, null, null, true, $domainId, $this->getRequest());
		if (isset($users[-1]['found'])) {
			$found=$users[-1]['found'];
			unset($users[-1]);
		} else {
			$found=count($users);
		}
		
		$dId=$functions->getDomainId($this->getRequest()->getHttpHost());
		
    	return $this->render('TimesheetHrBundle:Internal:usermenu.html.twig', array(
    		'base'			=> $base,
    		'users'			=> $users,
    		'found'			=> $found,
    		'userSearch'	=> $userSearch,
    		'AHE'			=> $functions->getConfig('ahe', $dId),
    		'AHEW'			=> $functions->getConfig('ahew', $dId),
    		'holidaycalculations'			=> $functions->getHolidayCalculations($dId),
			'hct'			=> $functions->getConfig('hct', $dId),
   			'lunchtime'		=> $functions->getConfig('lunchtime', $dId),
   			'lunchtimeUnpaid'	=> $functions->getConfig('lunchtimeUnpaid', $dId),
			'domainId'		=> $domainId,
    		'domains'		=> (($domainId)?(null):($functions->getDomains()))
    	));
    }

    
    public function locationmenuAction($base) {

    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    		error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}

    	$functions=$this->get('timesheet.hr.functions');
    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	
    	return $this->render('TimesheetHrBundle:Internal:locationmenu.html.twig', array(
    		'base'		=> $base,
    		'locations'	=> $functions->getLocation(null, false, $domainId)
    	));
    	
    }
    

    public function groupmenuAction($domainId, $base) {
    	 
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	 
    	$conn=$this->getDoctrine()->getConnection();
    	 
	    $query='SELECT'.
	    	' `g`.*'.
	    	' FROM `Groups` `g`'.
	    	(($domainId)?(' WHERE `g`.`domainId`=:dId'):('')).
	    	' ORDER BY `g`.`Name`';
	    	
	    $stmt=$conn->prepare($query);
	    if ($domainId) {
	    	$stmt->bindValue('dId', $domainId);
	    }
	    $stmt->execute();
	    	 
	    $groups=$stmt->fetchAll();
	    $functions=$this->get('timesheet.hr.functions');
	    
    	return $this->render('TimesheetHrBundle:Internal:groupmenu.html.twig', array(
    		'groups'	=> $groups,
    		'domainId'	=> $domainId,
    		'domains'	=> (($domainId)?(null):($functions->getDomains())),
    		'base'		=> $base
    	));
    }
    

    public function qualificationmenuAction($domainId, $base) {
    	 
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	 
    	$conn=$this->getDoctrine()->getConnection();
    	 
	    $query='SELECT'.
	    	' `q`.*'.
	    	' FROM `Qualifications` `q`'.
	    	(($domainId)?(' WHERE `q`.`domainId`=:dId'):('')).
	    	' ORDER BY `q`.`Title`';
	    	
	    $stmt=$conn->prepare($query);
	    if ($domainId) {
	    	$stmt->bindValue('dId', $domainId);
	    }
	    $stmt->execute();
	    	 
	    $qualifications=$stmt->fetchAll();
	    $functions=$this->get('timesheet.hr.functions');
    	    	 
    	return $this->render('TimesheetHrBundle:Internal:qualificationmenu.html.twig', array(
    		'qualifications'	=> $qualifications,
    		'domainId'	=> $domainId,
    		'domains'	=> (($domainId)?(null):($functions->getDomains())),
    		'base'				=> $base
    	));
    }
    
    
    public function statusmenuAction($base) {
    	
        $securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	
    	return $this->render('TimesheetHrBundle:Internal:statusmenu.html.twig', array(
    		'statuses'	=> $this->getStatuses(),
    		'base'		=> $base
    	));
    }
    	 
    
    public function shiftmenuAction($base) {
    	
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	 
    	$functions=$this->get('timesheet.hr.functions');
    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	$shifts=$functions->getShift(null, $domainId);
error_log('shifts:'.print_r($shifts, true));
    	return $this->render('TimesheetHrBundle:Internal:shiftmenu.html.twig', array(
    		'base'		=> $base,
    		'locations'	=> $functions->getLocation(null, true, $domainId),
    		'shifts'	=> $shifts
    	));
    }
    
    
    public function usersAction($action, $param1, $param2) {

    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') || (TRUE !== $securityContext->isGranted('ROLE_ADMIN') && TRUE !== $securityContext->isGranted('ROLE_SYSADMIN'))) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	
    	$sysadmin=(TRUE === $securityContext->isGranted('ROLE_SYSADMIN'));
    	$functions=$this->get('timesheet.hr.functions');
    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	$base=$this->getRequest()->attributes->get('_route');
    	$session=$this->get('session');
    	$session->set('menu', self::MENU_ADMIN);
    	$message='';

    	if (in_array($action, $this->adminActions) && $param1!=null) {
    	
    		$session->set('admin', array(
    			'action'=>$action,
    			'param1'=>$param1,
    			'param2'=>$param2
    		));
    	
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
    		if (isset($admin['param2'])) {
    			$new2=($admin['param2'] == 0);
    		} else {
    			$new2=false;
    		}
    		
			$em=$this->getDoctrine()->getManager();

    		switch ($admin['action']) {
    	
    			case 'edituser' : {
    	
    				if ($new) {
    					$user=new User();
    				} else {
    					$user=$this->getDoctrine()
    					->getRepository('TimesheetHrBundle:User')
    					->findOneBy(array('id'=>$admin['param1']));
    				}

    				$currentUser=$this->getUser();
    				$userForm=$this->createForm(new RegisterType($functions->getGroups($domainId), $functions->getLocation(), $functions->getAvailableRoles($currentUser->getRoles()), $functions->getTitles(), $user, $new, (($sysadmin)?($functions->getCompanies()):(null))));

    				$userForm->handleRequest($this->getRequest());
    	
    				if ($userForm->isValid()) {
    					if ($userForm->get('cancel')->isClicked()) {
    						$session->remove('admin');
    						return $this->redirect($this->generateUrl($base));
    					} else { // if ($userForm->get('submit')->isClicked()) {
	    					$message='Valid';
	    					$generatedUsername='';
	    					$data=$userForm->getData();
	    					$userManager = $this->container->get('fos_user.user_manager');
	    					if ($new && $domainId && strlen($data['username'])<3) {
// error_log('generate username');
	    						$generatedUsername=$this->generateUsername($data['firstName'], $data['lastName']);
	    						$username=$generatedUsername;
	    						$user->setUsername($username);
	    					} else {
// error_log('use entered username');
	    						$username=$data['username'];
	    						$user->setUsername($username);
	    					}
	    					if ($data['upass']) {
	    						$user->setPlainPassword($data['upass']);
	    					}
	    					$user->setEmail($data['email']);
	    					$user->setBirthday($data['birthday']);
			    			$user->setNationality($data['nationality']);
			    			$user->setNI(''.$data['ni']);
	    					$user->setPhoneLandline(''.$data['phoneLandline']);
	    					$user->setPhoneMobile(''.$data['phoneMobile']);
	    					$user->setFirstName(''.$data['firstName']);
	    					$user->setLastName(''.$data['lastName']);
	    					$user->setNokName(''.$data['nokName']);
	    					$user->setNokPhone(''.$data['nokPhone']);
	    					$user->setNokRelation(''.$data['nokRelation']);
	    					$user->setAddressLine1(''.$data['addressLine1']);
	    					$user->setAddressLine2(''.$data['addressLine2']);
	    					$user->setAddressCity(''.$data['addressCity']);
	    					$user->setAddressCounty(''.$data['addressCounty']);
	    					$user->setAddressCountry(''.$data['addressCountry']);
	    					$user->setAddressPostcode(''.$data['addressPostcode']);
	    					$user->setGroupId($data['group']);
	    					$user->setLocationId($data['location']);
	    					$user->setPayrolCode(''.$data['payrolCode']);
	    					$user->setLoginRequired($data['loginRequired']);
	    					$user->setNotes(''.$data['notes']);
	    					$user->setTitle(''.$data['title']);
	    					$user->setIsActive($data['isActive']);
	    					$user->setExEmail($data['exEmail']);
	    					$user->setEnabled(true);
	    					$user->setRoles(array($data['role']));
	    					if (isset($data['domainId'])) {
	    						$user->setDomainId($data['domainId']);
	    					}
	    					switch ($data['role']) {
	    						case 'ROLE_ADMIN' : {
	    							$user->setGroupAdmin(true);
	    							$user->setLocationAdmin(true);
	    							break;
	    						}
	    						case 'ROLE_USER' : {
	    							$user->setGroupAdmin(false);
	    							$user->setLocationAdmin(false);
	    							break;
	    						}
	    						default : {
	    							$user->setGroupAdmin((isset($data['groupAdmin']) && $data['groupAdmin'])?true:false);
	    							$user->setLocationAdmin((isset($data['locationAdmin']) && $data['locationAdmin'])?true:false);
	    							break;
	    						}
	    					}
	    					try {
	    						$userManager->updateUser($user);
							} catch (\Exception $e) {
								if (strpos($e->getMessage(), '1062') === false) {
									error_log('Database error:'.$e->getMessage());
								} else {
									$message='Username already exists, please try another username';
								}
							}
	    					if ($user->getId()) {
	    						$session->remove('admin');
	    						$session->getFlashBag()->set('notice', 'User ('.$user->getUsername().') '.(($new)?('saved'):('updated')).(($generatedUsername!='')?(', new username: '.$generatedUsername):('')));
	    						return $this->redirect($this->generateUrl($base));
	    					}
    					}
    				}
    				break;
    			}

    			case 'edittiming' : {

    				if ($new) {
		    			$session->remove('admin');
		    			return $this->redirect($this->generateUrl('timesheet_hr_admin'));
    				} else {
    					$user=$this->getDoctrine()
			    			->getRepository('TimesheetHrBundle:User')
			    			->findOneBy(array('id'=>$admin['param1']));

	    				if ($new2) {
	    					$timing=new Timing();
	    				} else {
		    				$timing=$this->getDoctrine()
				    			->getRepository('TimesheetHrBundle:Timing')
				    			->findOneBy(array(
				    				'userId'=>$admin['param1'], 
				    				'id'=>$admin['param2']
				    			));
	    				}
    				}

    				$timingForm=$this->createForm(new TimingType($timing, $user, $functions->getShiftsWithDetails(), $this->generateUrl('timesheet_ajax_shiftday'), $this->getDoctrine()->getManager()));

		    		$timingForm->handleRequest($this->getRequest());
		
		    		if ($timingForm->isValid()) {
		
		    			if ($timingForm->get('cancel')->isClicked()) {
		    				$session->remove('admin');
		    				return $this->redirect($this->generateUrl($base));
						} elseif ($timingForm->has('delete') && $timingForm->get('delete')->isClicked()) {
// error_log('preferred timing delete...');
							/*
							 * delete selected preferred timings from user
							 */
							$data=$timingForm->getData();
							/*
							 * store all the checked checkboxes' ids
							 */
							$tIds=array();
							foreach ($data as $k=>$v) {
								if (substr($k, 0, 7)=='delete_' && (int)substr($k, 7, strlen($k)) > 0 && $v) {
									$tIds[]=(int)substr($k, 7, strlen($k));
								}	
							}
							if (count($tIds)) {
								/*
								 * if checked any, delete
								 */
								$uId=$data['userId'];
								$results=$this->getDoctrine()
									->getRepository('TimesheetHrBundle:Timing')
									->findBy(array('userId'=>$uId, 'id'=>$tIds));
								if ($results && count($results)) {
									foreach ($results as $r) {
										$em->remove($r);
										$em->flush($r);
									}
									
									$session->getFlashBag()->set('notice', 'Timings for '.$user->getUsername().' is deleted');
									
								}
							} else {
								$session->getFlashBag()->set('notice', 'Nothing to delete');
							}
								
	    					return $this->redirect($this->generateUrl('timesheet_hr_users'));
		    			} elseif ($timingForm->get('submit')->isClicked()) {
		    				/*
		    				 * add preferred timing to a user
		    				 */
    		
			    			$data=$timingForm->getData();

			    			try {
				    			$timing->setDayId($data['dayId']);
				    			$timing->setShiftId($data['shiftId']);
								$timing->setUserId($data['userId']);
								$em->persist($timing);
								$em->flush($timing);
							} catch (\Exception $e) {
								if (strpos($e->getMessage(), '1062') === false) {
									error_log('Database error:'.$e->getMessage());
								}
							}
							if ($timing->getId()) {
				    			$session->getFlashBag()->set('notice', 'Timings for '.$user->getUsername().' added');
				    		}
				    		return $this->redirect($this->generateUrl('timesheet_hr_users'));
		    			}
		    		}
    				break;
    			}

				case 'editcontract' : {

    				if ($new) {
		    			$session->remove('admin');
		    			return $this->redirect($this->generateUrl($base));
    				} else {
	    				$user=$this->getDoctrine()
			    			->getRepository('TimesheetHrBundle:User')
			    			->findOneBy(array('id'=>$admin['param1']));

	    				if ($new2) {
	    					$contract=new Contract();
	    				} else {
		    				$contract=$this->getDoctrine()
				    			->getRepository('TimesheetHrBundle:Contract')
				    			->findOneBy(array('id'=>$admin['param2']));
	    				}
    				}
		    		
		    		$contractForm=$this->createForm(new ContractType($contract, $user, $functions->getHolidayCalculations($domainId)));
		    		
		    		$contractForm->handleRequest($this->getRequest());
		
		    		if ($contractForm->isValid()) {
		
		    			if ($contractForm->get('cancel')->isClicked()) {
		    				$session->remove('admin');
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    			 
		    			$message='Valid';
		    		
		    			$data=$contractForm->getData();

						$contract->setCSD($data['csd']);
						$contract->setCED($data['ced']);
						$contract->setAWH($data['awh']);
						$contract->setAHE($data['ahe']);
						$contract->setAHEW($data['ahew']);
						$contract->setLunchtime($data['lunchtime']);
						$contract->setLunchtimeUnpaid($data['lunchtimeUnpaid']);
						$contract->setHCT($data['hct']);
						$contract->setWDpW($data['wdpw']);
						$contract->setProbation($data['probation']?true:false);
						$contract->setAHEonYS($data['AHEonYS']?true:false);
						$contract->setInitHolidays($data['initHolidays']);
						
						if ($new2) {
							$contract->setUserId($data['userId']);
							
							$em->persist($contract);
						}
						$em->flush($contract);
		    		
		    			if ($contract->getId()) {
		    				
		    				$session->remove('admin');
		    				$session->getFlashBag()->set('notice', 'Contract for '.$user->getUsername().' '.(($new2)?('saved'):('updated')));
		    		
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    		}
    				break;
    			}

    			case 'edituserqualification' : {

    				if ($new) {
		    			$session->remove('admin');
		    			return $this->redirect($this->generateUrl($base));
    				} else {
	    				$user=$this->getDoctrine()
			    			->getRepository('TimesheetHrBundle:User')
			    			->findOneBy(array('id'=>$admin['param1']));
    				}

    				$title=trim($user->getFirstName().' '.$user->getLastName().' ('.$user->getUsername().')');
    				$list=$functions->getQualifications($admin['param1'], true, $domainId);
		    		$userqualificationForm=$this->createForm(new UserQualificationType($functions->getQualifications(null, false, $domainId), $user, $list));
		    		$userqualificationForm->handleRequest($this->getRequest());
		    		if ($userqualificationForm->isValid()) {
		
		    			if ($userqualificationForm->get('cancel')->isClicked()) {
// error_log('qualificatoin cancel...');
		    				$session->remove('admin');
		    				return $this->redirect($this->generateUrl($base));
		    			} elseif ($userqualificationForm->get('submit')->isClicked()) {
// error_log('qualificatoin submit...');
							/*
							 * add new qualification to user
							 */

							
			    			$data=$userqualificationForm->getData();
			    			try {
				    			$userqualification=new UserQualifications();
				    			$userqualification->setUserId($admin['param1']);
				    			$userqualification->setQualificationId($data['qualificationId']);
								$userqualification->setComments((($data['comments'])?($data['comments']):('')));
								$userqualification->setAchievementDate($data['achievementDate']);
								$userqualification->setExpiryDate($data['expiryDate']);
								$userqualification->setCreatedBy($this->getUser()->getId());
								$userqualification->setCreatedOn(new \DateTime());
								$em->persist($userqualification);
								$em->flush($userqualification);
			    		
							} catch (\Exception $e) {
								if (strpos($e->getMessage(), '1062') === false) {
									error_log('Database error:'.$e->getMessage());
								}
							}
							
			    			if ($userqualification->getId()) {
			    				$session->getFlashBag()->set('notice', 'Qualification for '.$user->getUsername().' is '.(($new2)?('added'):('updated')));
			    				return $this->redirect($this->generateUrl('timesheet_hr_users'));
			    			}
		    			} elseif ($userqualificationForm->has('delete') && $userqualificationForm->get('delete')->isClicked()) {
// error_log('qualificatoin delete...');
							$data=$userqualificationForm->getData();
							$qIds=array();
							foreach ($data as $k=>$v) {
								if (substr($k, 0, 7)=='delete_' && (int)substr($k, 7, strlen($k)) > 0 && $v) {
									$qIds[]=(int)substr($k, 7, strlen($k));
								}	
							}
							if (count($qIds)) {
								$uId=$data['userId'];
								$results=$this->getDoctrine()
									->getRepository('TimesheetHrBundle:UserQualifications')
									->findBy(array('userId'=>$uId, 'id'=>$qIds));
								if ($results && count($results)) {
									foreach ($results as $r) {
										$em->remove($r);
										$em->flush($r);
									}
									
									$session->getFlashBag()->set('notice', 'Qualification for '.$user->getUsername().' is deleted');
									
								}
							} else {
								$session->getFlashBag()->set('notice', 'Nothing to delete');
							}
								
	    					return $this->redirect($this->generateUrl('timesheet_hr_users'));
		    			}
		    		}
    				break;
    			}
    		}
    	}
    	
    	return $this->render('TimesheetHrBundle:Default:users.html.twig', array(
    		'base'			=> $base,
    		'message'		=> $message,
    		'title'			=> $this->getPageTitle('Users'),
    		'userForm'		=> ((isset($userForm))?($userForm->createView()):(null)),
    		'contractForm'	=> ((isset($contractForm))?($contractForm->createView()):(null)),
    		'timingForm'	=> ((isset($timingForm))?($timingForm->createView()):(null)),
    		'timingList'	=> ((isset($timingForm))?($functions->getTimings($admin['param1'], $domainId)):(null)),
    		'userqualificationForm'	=> ((isset($userqualificationForm))?($userqualificationForm->createView()):(null)),
    		'qualifications' => ((isset($userqualificationForm))?($functions->getQualifications($admin['param1'], true, $domainId)):(null)),
			'title' 		=> ((isset($userqualificationForm))?($title):(null)),
    		'domainId'		=> (($sysadmin)?(null):($domainId))
    	));
    }
    
    
    public function locationsAction($action, $param1, $param2) {
    	
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}

    	$base=$this->getRequest()->attributes->get('_route');
		$functions = $this->get('timesheet.hr.functions');
		$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	$session=$this->get('session');
    	$session->set('menu', self::MENU_ADMIN);
    	$message='';
    	$ipaddresses=array();

    	if (in_array($action, $this->adminActions) && $param1!=null) {
    	
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
    	
    			case 'editlocation' : {

    				if ($new) {
    					$location=new Location();
    					$location->setDomainId($domainId);
    				} else {
	    				$location=$this->getDoctrine()
			    			->getRepository('TimesheetHrBundle:Location')
			    			->findOneBy(array('id'=>$admin['param1']));
	    				
	    				$result=$this->getDoctrine()
			    			->getRepository('TimesheetHrBundle:LocationIpAddress')
			    			->findBy(array('locationId'=>$admin['param1']));
	    				
	    				if ($result) {
	    					foreach ($result as $ip) {
	    						$ipaddresses[]=$ip->getIpAddress();
	    					}
	    				}
    				}
		    		
		    		$locationForm=$this->createForm(new LocationType($location, ((count($ipaddresses))?(implode(PHP_EOL, $ipaddresses)):('')), $new, $functions->getGroups()));
		    		
		    		$locationForm->handleRequest($this->getRequest());
		
		    		if ($locationForm->isValid()) {
		
		    			if ($locationForm->get('cancel')->isClicked()) {
		    				$session->remove('admin');
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    			 
		    			$message='Valid';
		    		
		    			$data=$locationForm->getData();

		    			$location->setName(''.$data['name']);
		    			$location->setPhoneLandline(''.$data['phoneLandline']);
		    			$location->setPhoneMobile(''.$data['phoneMobile']);
		    			$location->setPhoneFax(''.$data['phoneFax']);
		    			$location->setAddressLine1(''.$data['addressLine1']);
		    			$location->setAddressLine2(''.$data['addressLine2']);
		    			$location->setAddressCity(''.$data['addressCity']);
		    			$location->setAddressCounty(''.$data['addressCounty']);
		    			$location->setAddressCountry(''.$data['addressCountry']);
		    			$location->setAddressPostcode(''.$data['addressPostcode']);
		    			$location->setActive($data['active']==1);
		    			$location->setFixedIpAddress($data['fixedipaddress']==1);

		    			if ($new) {
		    				$em->persist($location);
		    			}
		    			$em->flush($location);
		    				
		    			if ($location->getId()) {
		    				
		    				$new_ip=array();
		    				$wrong_ip=array();
		    				if (strlen($data['ipaddress'])) {
		    					$tmp=explode(PHP_EOL, $data['ipaddress']);
		    					if (count($tmp)) {
		    						foreach ($tmp as $v) {
		    							$v=preg_replace('/[^0-9\.]/', '', trim($v));
		    							if (filter_var($v, FILTER_VALIDATE_IP)) {
		    								$new_ip[$v]=$v;
		    							} else {
		    								$wrong_ip[]=$v;
		    							}
		    						}
		    					}
		    					
		    					if (count($new_ip)) {
		    						$tmp_ip=$new_ip;
		    						
		    						$tmp=$this->getDoctrine()
		    							->getRepository('TimesheetHrBundle:LocationIpAddress')
		    							->findBy(array('locationId'=>$location->getId()));
		    								    						
		    						foreach ($tmp as $t) {
		    							if (in_array($t->getIpAddress(), $new_ip)) {
		    								unset($tmp_ip[$t->getIpAddress()]);
		    							} else {
		    								$em->remove($t);
		    								$em->flush();
		    							}
		    						}
		    						if (count($tmp_ip)) {
		    							foreach ($tmp_ip as $t) {
		    								$lIp=new LocationIpAddress();
		    								
		    								$lIp->setLocationId($location->getId());
		    								$lIp->setIpAddress($t);
		    								$lIp->setStartTime(new \DateTime('now'));
		    								$lIp->setEndTime(null);
		    								
		    								$em->persist($lIp);
		    								$em->flush();
		    							}
		    						}
		    					}

		    				}
		    				$msg='Location ('.$location->getName().') updated';
		    				if (count($new_ip) || count($wrong_ip)) {
		    					$msg.=', with ';
		    					if (count($new_ip)) {
		    						$msg.=count($new_ip).' correct ';
		    					}
		    					if (count($wrong_ip)) {
		    						$msg.=count($wrong_ip).' incorrect ';
		    					}
		    					 
		    					$msg.='IP address';
		    				}
		    				$session->remove('admin');
		    				$session->getFlashBag()->set('notice', $msg);
		    				
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    		}
    				break;
    			}
    		}
    	} 
    	
    	return $this->render('TimesheetHrBundle:Default:locations.html.twig', array(
    		'base'			=> $base,
    		'message'		=> $message,
    		'title'			=> $this->getPageTitle('Locations'),
			'locationForm'	=> ((isset($locationForm))?($locationForm->createView()):(null)),
    		'locations'		=> $functions->getLocation(null, true, $domainId),
    		'members'		=> ((isset($admin['param1']) && $admin['param1'])?($functions->getMembers($admin['param1'])):(null))
    	));
    }
    
    
    public function adminAction($action, $param1, $param2) {
error_log('adminAction');
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') || (TRUE !== $securityContext->isGranted('ROLE_ADMIN'))) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	
    	$admin=array();
    	$functions=$this->get('timesheet.hr.functions');
    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	$base=$this->getRequest()->attributes->get('_route');
    	    	
    	$message='';
    	$session=$this->get('session');

    	$session->set('menu', self::MENU_ADMIN);
    	
    	if (in_array($action, $this->adminActions) && $param1!=null) {

    		$session->set('admin', array('action'=>$action, 'param1'=>$param1, 'param2'=>$param2));
    		
    		return $this->redirect($this->generateUrl($base));
    	}
    	
    	if ($session->get('admin')) {
    		$admin=$session->get('admin');
    	}

    	$em=$this->getDoctrine()->getManager();

    	if ($admin) {
			if (isset($admin['param1'])) {
    			$new=($admin['param1'] == 0);
    		} else {
    			$new=false;
    		}
    		if (isset($admin['param2'])) {
    			$new2=($admin['param2'] == 0);
    		} else {
    			$new2=false;
    		}
    		
    		switch ($admin['action']) {

				case 'editgroup' : {

    				if ($new) {
    					$group=new Groups();
    				} else {
	    				$group=$this->getDoctrine()
			    			->getRepository('TimesheetHrBundle:Groups')
			    			->findOneBy(array('id'=>$admin['param1']));
	    				
    				}
		    		
		    		$groupForm=$this->createForm(new GroupType($group, null));
		    		
		    		$groupForm->handleRequest($this->getRequest());
		
		    		if ($groupForm->isValid()) {
		
		    			if ($groupForm->get('cancel')->isClicked()) {
		    				$session->remove('admin');
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    			 
		    			$message='Valid';
		    		
		    			$data=$groupForm->getData();

		    			$group->setName(''.$data['name']);
	    				$group->setDomainId($domainId);
		    			
		    			if ($new) {
		    				$em->persist($group);
		    			}
		    			$em->flush($group);
		    				
		    			if ($group->getId()) {
		    				
		    				$session->remove('admin');
		    				$session->getFlashBag()->set('notice', 'Group "'.$group->getName().'" '.(($new2)?('saved'):('updated')));
		    		
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    		}
    				break;
    			}

    			case 'editqualification' : {

    				if ($new) {
    					$qualification=new Qualifications();
    				} else {
	    				$qualification=$this->getDoctrine()
			    			->getRepository('TimesheetHrBundle:Qualifications')
			    			->findOneBy(array('id'=>$admin['param1']));
	    				
    				}
		    		
		    		$qualificationForm=$this->createForm(new QualificationType($qualification));
		    		
		    		$qualificationForm->handleRequest($this->getRequest());
		
		    		if ($qualificationForm->isValid()) {
		
		    			if ($qualificationForm->get('cancel')->isClicked()) {
		    				$session->remove('admin');
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    			 
		    			$message='Valid';
		    		
		    			$data=$qualificationForm->getData();

		    			$qualification->setTitle(''.$data['title']);
		    			$qualification->setComments(''.$data['comments']);
		    			
		    			if ($new) {
		    				$qualification->setCreatedOn(new \DateTime('now'));
		    				$qualification->setCreatedBy($this->getUser()->getId());
		    				$em->persist($qualification);
		    			}
		    			$em->flush($qualification);
		    				
		    			if ($qualification->getId()) {
		    				
		    				$session->remove('admin');
		    				$session->getFlashBag()->set('notice', 'Qualification "'.$qualification->getTitle().'" '.(($new2)?('saved'):('updated')));
		    		
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    		}
    				break;
    			}

    		}
    	} else {
    		$problems=$functions->getProblems($domainId);
    	}
    	    	
    	return $this->render('TimesheetHrBundle:Default:admin.html.twig', array(
    		'base'			=> $base,
    		'groupForm'		=> ((isset($groupForm))?($groupForm->createView()):(null)),
    		'qualificationForm'	=> ((isset($qualificationForm))?($qualificationForm->createView()):(null)),
    		'message'		=> $message,
    		'title'			=> $this->getPageTitle('Administration'),
    		'problems'		=> ((isset($problems))?($problems):(null)),
    		'domainId'		=> $domainId
    	));
    	
    }
    
    
    public function shiftsAction($action, $param1, $param2) {

    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') || (TRUE !== $securityContext->isGranted('ROLE_ADMIN'))) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	
    	$admin=array();
    	$list=array();
    	$shiftTitle='';
    	
    	$base=$this->getRequest()->attributes->get('_route');
    	$functions = $this->get('timesheet.hr.functions');
    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	
    	$message='';
    	$session=$this->get('session');

    	$session->set('menu', self::MENU_ADMIN);
    	
    	if (in_array($action, $this->adminActions) && $param1!=null) {

    		$session->set('admin', array('action'=>$action, 'param1'=>$param1, 'param2'=>$param2));
    		
    		return $this->redirect($this->generateUrl($base));
    	}
    	
    	if ($session->get('admin')) {
    		$admin=$session->get('admin');
    	}

    	$em=$this->getDoctrine()->getManager();

    	if ($admin) {
			if (isset($admin['param1'])) {
    			$new=($admin['param1'] == 0);
    		} else {
    			$new=false;
    		}
    		if (isset($admin['param2'])) {
    			$new2=($admin['param2'] == 0);
    		} else {
    			$new2=false;
    		}
    		switch ($admin['action']) {

    			case 'editshift' : {
    				$days=array();
    				if ($new) {
    					$shift=new Shifts();
    				} else {
	    				$shift=$this->getDoctrine()
			    			->getRepository('TimesheetHrBundle:Shifts')
			    			->findOneBy(array('id'=>$admin['param1']));
	    				
	    				$results=$this->getDoctrine()
			    			->getRepository('TimesheetHrBundle:ShiftDays')
			    			->findBy(array('shiftId'=>$admin['param1']));
	    				
	    				if ($results && count($results)) {
	    					foreach ($results as $result) {
	    						$days[$result->getDayId()]=true;
	    					}
	    				}
    				}
		    		
		    		$shiftForm=$this->createForm(new ShiftType($shift, $functions->getLocation(null, true, $domainId), $days));
		    		$shiftForm->handleRequest($this->getRequest());
		    		if ($shiftForm->isValid()) {
		    			if ($shiftForm->get('cancel')->isClicked()) {
		    				$session->remove('admin');
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    			 
		    			$message='Valid';
		    			$data=$shiftForm->getData();

						$shift->setTitle($data['title']);
						$shift->setLocationId($data['locationId']);
						$shift->setStartTime($data['startTime']);
						$shift->setFinishTime($data['finishTime']);
						
		    			if ($new) {
		    				$em->persist($shift);
		    			}
		    			$em->flush($shift);
		    			
		    			if ($shift->getId()) {
		    				
		    				$results=$this->getDoctrine()
		    					->getRepository('TimesheetHrBundle:ShiftDays')
		    					->findBy(array('shiftId'=>$shift->getId()));
		    				
		    				$newDays=array();
		    				if ($data['dayMon']) {
		    					$newDays[1]=true;
		    				}
		    				if ($data['dayTue']) {
		    					$newDays[2]=true;
		    				}
		    				if ($data['dayWed']) {
		    					$newDays[3]=true;
		    				}
		    				if ($data['dayThu']) {
		    					$newDays[4]=true;
		    				}
		    				if ($data['dayFri']) {
		    					$newDays[5]=true;
		    				}
		    				if ($data['daySat']) {
		    					$newDays[6]=true;
		    				}
		    				if ($data['daySun']) {
		    					$newDays[0]=true;
		    				}
		    				
		    				if ($results && count($results)) {
		    					if ($days != $newDays) {
		    						for ($i=0; $i<7; $i++) {
		    							
		    							if (isset($newDays[$i]) && $newDays[$i]) {
		    								
		    								if ((isset($days[$i]) && $days[$i] != $newDays[$i]) || (!isset($days[$i]))) {
				    							$shiftDays=new ShiftDays();
				    						
				    							$shiftDays->setShiftId($shift->getId());
				    							$shiftDays->setDayId($i);
				    							
				    							$em->persist($shiftDays);
				    							$em->flush($shiftDays);
		    								}
		    								unset($newDays[$i]);
		    							} else {

		    								if (isset($days[$i]) && $days[$i]) {
		    									$result=$this->getDoctrine()
		    										->getRepository('TimesheetHrBundle:ShiftDays')
		    										->findOneBy(array('shiftId'=>$shift->getId(), 'dayId'=>$i));
		    								
		    									$em->remove($result);
		    									$em->flush();
		    								} else {
		    									unset($newDays[$i]);
		    								}
		    							}
		    						}		    						
		    					} else {
		    						unset($newDays);
		    					}
		    				}
		    				
		    				if (isset($newDays) && count($newDays)) {
		    					foreach ($newDays as $k=>$nd) {
		    						if ($nd) {
		    							$shiftDays=new ShiftDays();
		    						
		    							$shiftDays->setShiftId($shift->getId());
		    							$shiftDays->setDayId($k);
		    							
		    							$em->persist($shiftDays);
		    							$em->flush($shiftDays);
		    						}
		    					}
		    				}
		    				
//		    				$locations=$functions->getLocation();
		    				$session->remove('admin');
		    				$session->getFlashBag()->set('notice', 'Shift "'.$data['title'].' '.(($new2)?('saved'):('updated')));
		    		
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    			
		    		}
    				break;
    			}
    			
    			case 'editsreq' : {
//					$locations=$functions->getLocation();
					$list=$functions->getRequirementsForShift($admin['param1']);
					$groups=$functions->getGroups($domainId);
					foreach ($list as $l) {
						if (isset($groups[$l['groupId']])) {
							unset($groups[$l['groupId']]);
						}
					}
					
    				$shifts_tmp=$functions->getShift();
    				if (count($shifts_tmp)) {
    					$shiftTitle=$shifts_tmp[$admin['param1']]['locationName'].' '.$shifts_tmp[$admin['param1']]['startTime']->format('H:i').'-'.$shifts_tmp[$admin['param1']]['finishTime']->format('H:i');
    					if (count($shifts_tmp[$admin['param1']]['days'])) {
    						$d=array();
    						for ($i=1; $i<8; $i++) {
    							if (isset($shifts_tmp[$admin['param1']]['days'][(($i==7)?(0):($i))])) {
    								$ts=mktime(0, 0, 0, date('m'), date('d')-date('N')+(($i==7)?(0):($i)), date('Y'));
    								$d[]=date('D', $ts);
    							}
    						}
    						if (count($d)) {
    							$shiftTitle.=' ('.implode(',', $d).')';
    						}
    					}
    					
    				}
   					$staffReq=new StaffRequirements();
    				$staffReqForm=$this->createForm(new StaffRequirementsType($staffReq, $groups, $list, $admin['param1']));
    				$staffReqForm->handleRequest($this->getRequest());
    				if ($staffReqForm->isValid()) {
    					if ($staffReqForm->get('cancel')->isClicked()) {
    						$session->remove('admin');
    						return $this->redirect($this->generateUrl($base));
    					} elseif ($staffReqForm->get('submit')->isClicked()) {
	    					$message='Valid';
	    					$data=$staffReqForm->getData();
	    			
	    					$staffReq->setShiftId($data['shiftId']);
	    					$staffReq->setGroupId($data['groupId']);
	    					$staffReq->setNumberOfStaff($data['numberOfStaff']);
    						$em->persist($staffReq);
	    					$em->flush($staffReq);
	    					if ($staffReq->getId()) {
	    						$session->getFlashBag()->set('notice', 'Staff Requirements '.(($new2)?('saved'):('updated')));
	    						return $this->redirect($this->generateUrl('timesheet_hr_shifts'));
	    					}
	    				} elseif ($staffReqForm->get('delete')->isClicked()) {
// error_log('staff requirement delete...');
							$gIds=array();
							$data=$staffReqForm->getData();
								
	    					foreach ($data as $k=>$v) {
								if (substr($k, 0, 7)=='delete_' && (int)substr($k, 7, strlen($k)) > 0 && $v) {
									$gIds[]=(int)substr($k, 7, strlen($k));
								}	
							}
							$sId=$data['shiftId'];
								
							$results=$this->getDoctrine()
								->getRepository('TimesheetHrBundle:StaffRequirements')
								->findBy(array('shiftId'=>$sId, 'groupId'=>$gIds));
							if ($results && count($results)) {
								foreach ($results as $r) {
									$em->remove($r);
									$em->flush($r);
								}
								$session->getFlashBag()->set('notice', 'Staff Requirements deleted');
							}			
	    			
	    					return $this->redirect($this->generateUrl('timesheet_hr_shifts'));
    					}
    					 
    				}
    				break;
    			}
    			 
				case 'editqreq' : {
//					$locations=$functions->getLocation();
					$list=$functions->getQualRequirementsForShift($admin['param1']);
					$qualifications=$functions->getQualifications(null, false, $domainId);
					foreach ($list as $l) {
						if (isset($qualifications[$l['qualificationId']])) {
							unset($qualifications[$l['qualificationId']]);
						}
					}
					
    				$shifts_tmp=$functions->getShift();
    				if (count($shifts_tmp)) {
    					$shiftTitle=$shifts_tmp[$admin['param1']]['locationName'].' '.$shifts_tmp[$admin['param1']]['startTime']->format('H:i').'-'.$shifts_tmp[$admin['param1']]['finishTime']->format('H:i');
    					if (count($shifts_tmp[$admin['param1']]['days'])) {
    						$d=array();
    						for ($i=1; $i<8; $i++) {
    							if (isset($shifts_tmp[$admin['param1']]['days'][(($i==7)?(0):($i))])) {
    								$ts=mktime(0, 0, 0, date('m'), date('d')-date('N')+(($i==7)?(0):($i)), date('Y'));
    								$d[]=date('D', $ts);
    							}
    						}
    						if (count($d)) {
    							$shiftTitle.=' ('.implode(',', $d).')';
    						}
    					}
    					
    				}
   					$qualReq=new QualRequirements();
    				$qualReqForm=$this->createForm(new QualRequirementsType($qualReq, $qualifications, $list, $admin['param1']));
    				$qualReqForm->handleRequest($this->getRequest());
    				if ($qualReqForm->isValid()) {
    					if ($qualReqForm->get('cancel')->isClicked()) {
    						$session->remove('admin');
    						return $this->redirect($this->generateUrl($base));
    					} elseif ($qualReqForm->get('submit')->isClicked()) {
	    					$message='Valid';
	    					$data=$qualReqForm->getData();
	    			
	    					$qualReq->setShiftId($data['shiftId']);
	    					$qualReq->setQualificationId($data['qualificationId']);
	    					$qualReq->setNumberOfStaff($data['numberOfStaff']);
    						$em->persist($qualReq);
	    					$em->flush($qualReq);
	    					if ($qualReq->getId()) {
	    						$session->getFlashBag()->set('notice', 'Qualification Requirements '.(($new2)?('saved'):('updated')));
	    						return $this->redirect($this->generateUrl('timesheet_hr_shifts'));
	    					}
	    				} elseif ($qualReqForm->get('delete')->isClicked()) {
// error_log('qualification requirement delete...');
							$qIds=array();
							$data=$qualReqForm->getData();
								
	    					foreach ($data as $k=>$v) {
								if (substr($k, 0, 7)=='delete_' && (int)substr($k, 7, strlen($k)) > 0 && $v) {
									$qIds[]=(int)substr($k, 7, strlen($k));
								}	
							}
							$sId=$data['shiftId'];
								
							$results=$this->getDoctrine()
								->getRepository('TimesheetHrBundle:QualRequirements')
								->findBy(array('shiftId'=>$sId, 'qualificationId'=>$qIds));
							if ($results && count($results)) {
								foreach ($results as $r) {
									$em->remove($r);
									$em->flush($r);
								}
								$session->getFlashBag()->set('notice', 'Qualification Requirements deleted');
							}			
	    			
	    					return $this->redirect($this->generateUrl('timesheet_hr_shifts'));
    					}
    					 
    				}
    				break;
    			}
    		
    		}
    	}

    	return $this->render('TimesheetHrBundle:Default:shifts.html.twig', array(
    		'base'			=> $base,
    		'shiftForm'		=> ((isset($shiftForm))?($shiftForm->createView()):(null)),
    		'staffReqForm'	=> ((isset($staffReqForm))?($staffReqForm->createView()):(null)),
    		'qualReqForm'	=> ((isset($qualReqForm))?($qualReqForm->createView()):(null)),
    		'list'			=> $list,
    		'shiftTitle'	=> $shiftTitle,
    		'message'		=> $message,
    		'title'			=> $this->getPageTitle('Shifts')
    	));
    	
    }
    
    
    public function menuAction() {

    	$links=array();
    	$submenu=array();
    	
    	$session=$this->get('session');
    	$securityContext = $this->container->get('security.context');

   		$active=$session->get('menu');
   		if ($active < 1 || $active > self::MENU_ITEMS) {
   			$active = 1;
   		}
    	
    	$links[]=array('url'=>$this->generateUrl('timesheet_hr_homepage'), 'name'=>'Home', 'active'=>($active == self::MENU_HOMEPAGE));

    	if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    		// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
    		$currentUser=$this->getUser();
    		$functions=$this->get('timesheet.hr.functions');
    		$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    		$unread=$functions->getNumberOfUnreadMessages($currentUser->getId());
// error_log('unread:'.print_r($unread, true));
    		if (TRUE === $securityContext->isGranted('ROLE_USER') && TRUE !== $securityContext->isGranted('ROLE_SYSADMIN')) {
    			$links[]=array('url'=>$this->generateUrl('timesheet_hr_status'), 'name'=>'Status', 'active'=>($active == self::MENU_STATUS));
    			$links[]=array('url'=>$this->generateUrl('timesheet_hr_messages'), 'name'=>'Messages'.(($unread)?(' ('.$unread.')'):('')), 'active'=>($active == self::MENU_MESSAGES));
    			$links[]=array('url'=>$this->generateUrl('timesheet_hr_timesheet'), 'name'=>'Timesheet', 'active'=>($active == self::MENU_TIMESHEET));
	    		$links[]=array('url'=>$this->generateUrl('timesheet_hr_holiday'), 'name'=>'Holiday', 'active'=>($active == self::MENU_HOLIDAY));
    		} else {
//    			$links[]=array('url'=>$this->generateUrl('timesheet_hr_sysadmin'), 'name'=>'Sysadmin', 'active'=>false);
    			$submenu[]=array('url'=>$this->generateUrl('timesheet_hr_users'), 'name'=>'Users', 'active'=>false);
    			$submenu[]=array('url'=>$this->generateUrl('timesheet_hr_reset'), 'name'=>'Reset', 'active'=>false);
				$links[]=array('sub'=>$submenu, 'url'=>$this->generateUrl('timesheet_hr_sysadmin'), 'name'=>'Sysadmin', 'active'=>($active == self::MENU_SYSADMIN));    		}
//				unset($submenu);
    		if (TRUE === $securityContext->isGranted('ROLE_ADMIN') || TRUE === $securityContext->isGranted('ROLE_MANAGER')) {

    			$locationId=null;
    			if (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
    				if ($currentUser->getLocationAdmin()) {
    					$locationId=$currentUser->getLocationId();
    				} else {
    					$locationId=-1;
    				}
    			}
				if ($locationId != -1) {
    				$locations=$functions->getLocation($locationId, true, $domainId);

    				$locationSubmenu=array();
    				foreach ($locations as $k=>$l) {
						$locationSubmenu[]=array('url'=>$this->generateUrl('timesheet_hr_schedule', array('locationId'=>$k)), 'name'=>$l, 'active'=>false);
    				}
					
    				if (count($locationSubmenu)) {
    					$links[]=array('sub'=>$locationSubmenu, 'url'=>$this->generateUrl('timesheet_hr_schedule', array('locationId'=>0)), 'name'=>'Schedule', 'active'=>($active == self::MENU_SCHEDULE));
    				}
				}
    		}
	    	
	    	if (TRUE === $securityContext->isGranted('ROLE_ADMIN')) {
			// only for ADMIN
//    			$links[]=array('url'=>$this->generateUrl('timesheet_hr_registration'), 'name'=>'Registration', 'active'=>($active == self::MENU_REGISTER));

    			$submenu[]=array('url'=>$this->generateUrl('timesheet_hr_users'), 'name'=>'Users', 'active'=>false);
    			$submenu[]=array('url'=>$this->generateUrl('timesheet_hr_locations'), 'name'=>'Locations', 'active'=>false);
    			$submenu[]=array('url'=>$this->generateUrl('timesheet_hr_shifts'), 'name'=>'Shifts', 'active'=>false);
				$submenu[]=array('url'=>$this->generateUrl('timesheet_hr_config'), 'name'=>'Config', 'active'=>false);
	    		$links[]=array('sub'=>$submenu, 'url'=>$this->generateUrl('timesheet_hr_admin'), 'name'=>'Administration', 'active'=>($active == self::MENU_ADMIN));
			} 
			$links[]=array('url'=>$this->generateUrl('fos_user_security_logout'), 'name'=>'Logout', 'active'=>0);
    	} else {
    		$links[]=array('url'=>$this->generateUrl('fos_user_security_login'), 'name'=>'Login', 'active'=>($active== self::MENU_LOGIN));
    	}
    	 
    	return $this->render('TimesheetHrBundle:Default:menu.html.twig', array(
    		'links'	=> $links
    	));
    }

    
    public function sysadminAction($action, $param1, $param2) {
    	
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') || (TRUE !== $securityContext->isGranted('ROLE_SYSADMIN'))) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}

    	$em=$this->getDoctrine()->getManager();
    	$session=$this->getRequest()->getSession();
    	$base=$this->getRequest()->attributes->get('_route');
    	$functions=$this->get('timesheet.hr.functions');
    	$hct=$functions->getHolidayCalculations(null);
    	
		$new=false;
    	switch ($action) {
    		case 'New' :
    			$company=new Companies();
    			$new=true;
    		case 'Edit' : {
    			if ($param1) {
    				$company=$this->getDoctrine()
    					->getRepository('TimesheetHrBundle:Companies')
    					->findOneBy(array('id'=>$param1));
    				
    				if (!$company || count($company)<1) {
    					return $this->redirect($this->generateUrl('timesheet_hr_sysadmin'));
    				}
    			}
    			$companyForm=$this->createForm(new CompanyType($company, $functions->getTimezone(), $hct));
    			
    			$companyForm->handleRequest($this->getRequest());
    			if ($companyForm->isValid()) {
    				if ($companyForm->get('cancel')->isClicked()) {
//    					$session->remove('admin');
    					return $this->redirect($this->generateUrl($base));
    				} else {
    					$data=$companyForm->getData();
    			
    					$company->setCompanyname($data['companyname']);
    					$company->setDomain($data['domain']);
    					$company->setAHE($data['ahe']);
    					$company->setAHEW($data['ahew']);
    					$company->setHCT($data['hct']);
    					$company->setLunchtime($data['lunchtime']);
    					$company->setLunchtimeUnpaid($data['lunchtimeUnpaid']);
    					$company->setTimezone($data['timezone']);
    					$company->setYearstart($data['yearstart']);
    					if (!$data['id']) {
    						$em->persist($company);
    					}
    					$em->flush($company);
    					if ($company->getId()) {
    						$session->getFlashBag()->set('notice', 'Company '.(($new)?('saved'):('updated')));
    						return $this->redirect($this->generateUrl('timesheet_hr_sysadmin'));
    					}
    				}
    			}		 
    			break;
    		}
			case 'editgroup' : {

   				if ($param1) {
   					$group=$this->getDoctrine()
		    			->getRepository('TimesheetHrBundle:Groups')
		    			->findOneBy(array('id'=>$param1));
   				} else {
   					$group=new Groups();
   				}
		    		
	    		$groupForm=$this->createForm(new GroupType($group, $functions->getDomains()));
		    		
	    		$groupForm->handleRequest($this->getRequest());
		
	    		if ($groupForm->isValid()) {
	
	    			if ($groupForm->get('cancel')->isClicked()) {
	    				$session->remove('admin');
	    				return $this->redirect($this->generateUrl($base));
	    			}
		    			 
	    			$data=$groupForm->getData();

	    			$group->setName(''.$data['name']);
    				$group->setDomainId($data['domainId']);
	    			
	    			if (!$param1) {
	    				$em->persist($group);
	    			}
	    			$em->flush($group);
		    				
	    			if ($group->getId()) {
		    				
	    				$session->remove('admin');
	    				$session->getFlashBag()->set('notice', 'Group "'.$group->getName().'" '.(($param1)?('updated'):('saved')));
	    		
	    				return $this->redirect($this->generateUrl($base));
	    			}
	    		}
   				break;
   			}

    	    case 'editqualification' : {

    			if ($param1) {
	    			$qualification=$this->getDoctrine()
			   			->getRepository('TimesheetHrBundle:Qualifications')
			   			->findOneBy(array('id'=>$param1));
    			} else {
    				$qualification=new Qualifications();
    			}
		    		
		    	$qualificationForm=$this->createForm(new QualificationType($qualification, $functions->getDomains()));
		    	$qualificationForm->handleRequest($this->getRequest());
		
	    		if ($qualificationForm->isValid()) {
		
	    			if ($qualificationForm->get('cancel')->isClicked()) {
	    				$session->remove('admin');
	    				return $this->redirect($this->generateUrl($base));
	    			}
		    			 
	    			$data=$qualificationForm->getData();

	    			$qualification->setTitle(''.$data['title']);
	    			$qualification->setComments(''.$data['comments']);
	    			$qualification->setDomainId($data['domainId']);
		    			
	    			if (!$param1) {
	    				$qualification->setCreatedOn(new \DateTime('now'));
	    				$qualification->setCreatedBy($this->getUser()->getId());
	    				$em->persist($qualification);
	    			}
	    			$em->flush($qualification);
		    				
	    			if ($qualification->getId()) {
		    				
	    				$session->remove('admin');
	    				$session->getFlashBag()->set('notice', 'Qualification "'.$qualification->getTitle().'" '.(($param1)?('updated'):('saved')));
	    		
	    				return $this->redirect($this->generateUrl($base));
	    			}
	    		}
   				break;
   			}
   			
			case 'editstatus' : {
    			if ($param1) {
    				$status=$this->getStatuses($param1);    				
    			} else {
    				$status=array(
    					'id'=>null,
    					'nameStart'=>null,
    					'nameFinish'=>null,
    					'level'=>null,
    					'multi'=>0,
    					'color'=>null,
    					'active'=>null
    				);
    			}
		    		
		    	$statusForm=$this->createForm(new StatusType($status, $this->getStatusColors(), $this->getStatusLevels()));
	    		$statusForm->handleRequest($this->getRequest());
		
	    		if ($statusForm->isValid()) {
		
	    			if ($statusForm->get('cancel')->isClicked()) {
	    				$session->remove('admin');
	    				return $this->redirect($this->generateUrl($base));
	    			}
		    			 
	    			$data=$statusForm->getData();
		    			
					if ($data['id']) {
							
						$status1=$this->getDoctrine()
							->getRepository('TimesheetHrBundle:Status')
							->findOneBy(array('id'=>$data['id']));

						$status2=$this->getDoctrine()
							->getRepository('TimesheetHrBundle:Status')
							->findOneBy(array('pair'=>$data['id']));
								
						$status1->setName($data['nameStart']);
						$status1->setStart(true);
						$status1->setActive($data['active']?true:false);
						$status1->setLevel($data['level']);
						$status1->setMulti($data['multi']);
						$status1->setColor($data['color']);
							
						$status2->setName($data['nameFinish']);
						$status2->setStart(false);
						$status2->setActive($data['active']?true:false);
						$status2->setLevel($data['level']);
						$status2->setMulti($data['multi']);
						$status2->setColor($data['color']);
							
						$em->flush($status1);
						$em->flush($status2);
							
					} else {
							
						$status1=new Status();
						$status2=new Status();
							
						$status1->setName($data['nameStart']);
						$status1->setStart(true);
						$status1->setActive($data['active']?true:false);
						$status1->setLevel($data['level']);
						$status1->setMulti($data['multi']);
						$status1->setColor($data['color']);
						$status1->setPair('99');
							
						$em->persist($status1);
						$em->flush($status1);
							
						$status2->setName($data['nameFinish']);
						$status2->setStart(false);
						$status2->setActive($data['active']?true:false);
						$status2->setLevel($data['level']);
						$status2->setMulti($data['multi']);
						$status2->setColor($data['color']);
						$status2->setPair($status1->getId());
							
						$em->persist($status2);
						$em->flush($status2);
							
						$status1->setPair($status2->getId());
							
						$em->flush($status1);
							
					}		    			

	    			if ($status1->getId()) {
		    				
	    				$session->remove('admin');
	    				$session->getFlashBag()->set('notice', 'Status "'.$status1->getName().' / '.$status2->getName().'" '.(($data['id'])?('updated'):('saved')));
		    		
	    				return $this->redirect($this->generateUrl($base));
	    			}

	    		}
   				break;
   			}
   			default : {
		    	$config=$this->getDoctrine()
		    		->getRepository('TimesheetHrBundle:Config')
		    		->findBy(array(), array('name'=>'ASC'));
		    	
		    	$sites=$this->getDoctrine()
		    		->getRepository('TimesheetHrBundle:Companies')
		    		->findBy(array(), array('companyname'=>'ASC'));
    			break;
    		}
    	}
    	
    	
    	return $this->render('TimesheetHrBundle:Default:sysadmin.html.twig', array(
    		'base'			=> 'timesheet_hr_sysadmin',
    		'title'			=>'Sysadmin',
    		'config'		=> ((isset($config))?($config):(null)),
    		'sites'			=> ((isset($sites))?($sites):(null)),
    		'hct'			=> $hct,
    		'groupForm'		=> ((isset($groupForm))?($groupForm->createView()):(null)),
    		'qualificationForm'		=> ((isset($qualificationForm))?($qualificationForm->createView()):(null)),
    		'statusForm'	=> ((isset($statusForm))?($statusForm->createView()):(null)),
    		'companyForm'	=> ((isset($companyForm))?($companyForm->createView()):(null)),
    		'domainId'		=> null
    	));
    }
    

    public function usersummaryAction() {
// error_log('usersummary');

    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    		error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	 
    	$user=$this->getUser();

    	if ($user) {
// error_log('user exists');
// error_log('userId:'.$user->getId());
    		$base=$this->getRequest()->attributes->get('_route');
    		
	    	$functions = $this->get('timesheet.hr.functions');
	    	$holidays=$functions->getHolidayEntitlement($user->getId(), $this->getRequest());
	    	$workinghours=$functions->getWorkingHours($user->getId(), time());
	    	
	    	$data=array(
	    		'status'=>$functions->getCurrentStatus($user->getId()),
	    		'lastweek'=>$workinghours['weekly']['last']['whr'],
	    		'thisweek'=>$workinghours['weekly']['current']['whr'],
	    		'nextweek'=>$workinghours['weekly']['next']['whr'],
	    		'lastmonth'=>$workinghours['monthly']['last']['whr'],
	    		'thismonth'=>$workinghours['monthly']['current']['whr'],
	    		'nextmonth'=>$workinghours['monthly']['next']['whr'],
	    		'holidays'=>$holidays['untilToday'],
	    		'timestamp'=>time(),
	    		'timestamplast'=>$workinghours['weekly']['last']['first'],
	    		'timestampnext'=>$workinghours['weekly']['next']['first'],
	    		'timestamplastmonth'=>$workinghours['monthly']['last']['first'],
	    		'timestampnextmonth'=>$workinghours['monthly']['next']['first'],
	    		'swaprequests'=>$functions->getFutureSwapRequests($user->getId()),
	    		'todayshift'=>$functions->getTodayShift($user->getId()),
	    		'nextshift'=>$functions->getNextShift($user->getId())
	    	);
	    	
	    	return $this->render('TimesheetHrBundle:Internal:usersummary.html.twig', array(
	    		'data'		=> $data,
	    		'userId'	=> $user->getId(),
	    		'base'		=> $base
	    	));
    	} else {
// error_log('user not exists');
    		return new Response('');
    	}
    }
    
    /*
     * @Pdf()
     */
    public function weeklyreportAction($timestamp, $user) {
error_log('weeklyreportAction');    	

		$securityContext = $this->container->get('security.context');
		if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
			return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
		}

		$monday=mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp));
		$sunday=mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+7, date('Y', $timestamp));

		$facade = $this->get('ps_pdf.facade');
		$response = new Response();

		$functions = $this->get('timesheet.hr.functions');
		
		$user=$this->getDoctrine()
			->getRepository('TimesheetHrBundle:User')
			->findOneBy(array('id'=>$user));
		
		$this->render(sprintf('TimesheetHrBundle:Pdf:WeeklyReport.%s.twig', 'pdf'), array(
			'name' => trim($user->getFirstName().' '.$user->getLastName()),
			'username' => $user->getUsername(),
			'date1'=>date('Y-m-d', $monday),
			'date2'=>date('Y-m-d', $sunday),
			'week'=> date('W', $monday),
			'report'=>$functions->getWeeklySchedule($user->getId(), $monday),
			'footer' => 'Created on '.date('d/m/Y H:i:s'),
			), $response);

		$filename='WeeklyReport.pdf';
		$xml=$response->getContent();
		
    	$content = $facade->render($xml);
    	
    	return new Response($content, 200, array
    		('content-type' => 'application/pdf',
    		'Content-Disposition' => 'attachment; filename=' . $filename
    		));
    }
    
    /*
     * @Pdf()
     */
    public function monthlyreportAction($timestamp, $user) {
error_log('monthlyreportAction');
// error_log('timestamp:'.$timestamp.' = '.date('Y-m-d', $timestamp));
		$securityContext = $this->container->get('security.context');
		if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
			return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
		}

		$first=mktime(0, 0, 0, date('n', $timestamp), 1, date('Y', $timestamp));
		$last=mktime(0, 0, 0, date('n', $timestamp), date('t', $timestamp), date('Y', $timestamp));
// error_log('date:'.date('Y-m-d', $first).'-'.date('Y-m-d', $last));
		$facade = $this->get('ps_pdf.facade');
		$response = new Response();

		$functions = $this->get('timesheet.hr.functions');
		
		$user=$this->getDoctrine()
			->getRepository('TimesheetHrBundle:User')
			->findOneBy(array('id'=>$user));
		
		$this->render('TimesheetHrBundle:Pdf:MonthlyReport.pdf.twig', array(
			'name' => trim($user->getFirstName().' '.$user->getLastName()),
			'username' => $user->getUsername(),
			'date1'=>date('Y-m-d', $first),
			'date2'=>date('Y-m-d', $last),
			'month'=> date('F', $first),
			'report'=>$functions->getMonthlySchedule($user->getId(), $first),
			'footer' => 'Created on '.date('d/m/Y H:i:s'),
			), $response);

		$filename='MonthlyReport.pdf';
		$xml=$response->getContent();
		
    	$content = $facade->render($xml);
    	
    	return new Response($content, 200, array
    		('content-type' => 'application/pdf',
    		'Content-Disposition' => 'attachment; filename=' . $filename
    		));
    }

    
    public function weeklylocationreportAction($timestamp, $location, $type='') {
error_log('weeklylocationreportAction');
error_log('type:'.$type);

		$securityContext = $this->container->get('security.context');
		if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			error_log('not allowed...redirect to homepage');
			return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
		}

    
    	$monday=mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+1, date('Y', $timestamp));
    	$sunday=mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)-date('N', $timestamp)+7, date('Y', $timestamp));
    
    	$facade = $this->get('ps_pdf.facade');
    	$response = new Response();
    
    	$functions = $this->get('timesheet.hr.functions');
    
    	$locationData=$functions->getLocation($location, false);
    	
		switch ($type) {
			case 'Default' : {
				$report2=$functions->getWeeklyLocationSchedule2($location, $monday);
error_log('Default report:'.print_r($report2, true));
				$shifts=array();
				$users=array();

				if ($report2 && count($report2)) {
					foreach ($report2 as $rep) {
						if (isset($rep['shifts'])) {
							foreach ($rep['shifts'] as $sId=>$shft) {
								if ($shft && count($shft)) {
									foreach ($shft as $s)
									$users[$s['userId']]=trim($s['firstName'].' '.$s['lastName']).' ('.$s['username'].')';
									$shifts[$sId]=array(
										'title'=>$s['title'],
										'times'=>$s['startTime']->format('H:i').'-'.$s['finishTime']->format('H:i')
									);
								}
							}
						}
					}
				}
				if (count($users)) {
					asort($users);
				}
				
				$this->render('TimesheetHrBundle:Pdf:WeeklyLocationReportDefault.pdf.twig', array(
						'name' => trim($locationData['name']),
						'date1'=>date('Y-m-d', $monday),
						'date2'=>date('Y-m-d', $sunday),
						'week'=> date('W', $monday),
						'report'=>$report2,
						'shifts'=>$shifts,
						'users'=>$users,
						'footer' => 'Created on '.date('d/m/Y H:i:s'),
				), $response);
				
				break;
			}
			default : {
				$report=$functions->getWeeklyLocationSchedule($location, $monday);
				$timings=array();
				if ($report && count($report)) {
					foreach ($report as $rep) {
						if (isset($rep['timings'])) {
							foreach ($rep['timings'] as $r) {
								$timings[$r['shiftId']]=array(
									'title'=>$r['title'],
									'times'=>$r['startTime']->format('H:i').'-'.$r['finishTime']->format('H:i')
								);
							}
						}
					}
				}
				
				$this->render('TimesheetHrBundle:Pdf:WeeklyLocationReport.pdf.twig', array(
					'name' => trim($locationData['name']),
					'date1'=>date('Y-m-d', $monday),
					'date2'=>date('Y-m-d', $sunday),
					'week'=> date('W', $monday),
					'report'=>$report,
					'timings'=>$timings,
					'footer' => 'Created on '.date('d/m/Y H:i:s'),
				), $response);
				
				break;
			}
		}
    
    	$filename='WeeklyLocationReport'.$type.'.pdf';
    	$xml=$response->getContent();
    
    	$content = $facade->render($xml);
    	 
    	return new Response($content, 200, array
   			('content-type' => 'application/pdf',
				'Content-Disposition' => 'attachment; filename=' . $filename
   			));
    }
    
/*
 * Private functions
 * - usort functions
 * - align data
 * - get data
 */

    
    
    private function getStatus($id=null, $nameOnly=true) {
    	/*
    	 * read the status table by name
    	 */
    	$results=$this->getDoctrine()
    		->getRepository('TimesheetHrBundle:Status')
    		->findBy(
    			(($id)?(array('id'=>$id)):(array('active'=>1))),
    			array('start'=>'DESC', 'pair'=>'ASC')
    		);
    	 
    	$arr=array();
    	if ($results) {
    		foreach ($results as $result) {
    			if ($nameOnly) {
    				$arr[$result->getId()]=$result->getName();
    			} else {
    				$arr=array(
    					'id'=>$result->getId(),
    					'name'=>$result->getName(),
    					'level'=>$result->getLevel(),
    					'start'=>$result->getStart(),
    					'pair'=>$result->getPair()
    				);
    			}
    		}
    	}
    
    	return $arr;
    }
    
    
    private function getStatusAcceptable($user, $status) {

    	if ($user->getLastStatus() == null && $status['start'] && $status['level'] == 0) {
    		// accept if this is the 1st status and new status is starting in the 1st level
// error_log('first sign in');
    		return true;
    	}
		if ($user->getLastStatus() != null) {
			$currentStatus=$this->getStatus($user->getLastStatus(), false);
			
			if ($status['id'] != $currentStatus['id']) {

				if ($status['start'] && !$currentStatus['start'] && $currentStatus['level'] == $status['level'] && $status['level'] == 0) {
					// accept if currently signed out and now sign in
// error_log('new sign in');
					return true;
				}
			
				if (!$status['start'] && !$currentStatus['start'] && $status['level'] == 0) {
					// accept if currently signed in or finished highest level task and now sign out
// error_log('new sign out');
					return true;
				}
				
				if ($status['start'] && ($status['level'] > $currentStatus['level'] || ($status['level'] == $currentStatus['level'] && !$currentStatus['start']))) {
					// accept if change status to highest level
// error_log('start highest level status');
					return true;
				}

				if (!$status['start'] && $currentStatus['level'] == $status['level'] && $currentStatus['pair'] == $status['id']) {
					// accept if end of the currently selected status
// error_log('end highest level status');
					return true;
				}
				
				if (!$status['start'] && $status['id'] != $currentStatus['id'] && $currentStatus['level'] <= $status['level'] && $currentStatus['pair'] == $status['pair']) {
					error_log('test:, current pair:'.$currentStatus['pair'].', status:'.print_r($status, true));
					return true;
				}
				
			}
		}
    	
// error_log('status not accepted');    	
    	return false;
    }
    
    
    private function savePunchStatus($userId, $statusId, $comment) {
    	
//    	$functions=$this->get('timesheet.hr.functions');
//    	$userTZ=$functions->getTimezone($this->getRequest()->getHttpHost());
//error_log('timezone:'.$userTZ);
//    	$now=new \DateTime(date('Y-m-d H:i:s'), new \DateTimeZone($userTZ));
//    	$now->setTimeZone(new \DateTimeZone('UTC'));
    	// 'data_timezone' => 'UTC',
    	// 'user_timezone' => $options['user_timezone']
    	$now=new \DateTime('now');
    	$now->setTimeZone(new \DateTimeZone('UTC'));
    	
    	$em=$this->getDoctrine()->getManager();
    	
    	$user=$this->getDoctrine()
    		->getRepository('TimesheetHrBundle:User')
    		->findOneBy(array('id'=>$userId));
    	 
    	if ($user) {
	    	$punch=new Info();
	    	
	    	$punch->setUserId($userId);
	    	$punch->setStatusId($statusId);
	    	$punch->setTimestamp($now);
	    	$punch->setComment($comment?$comment:'');
	    	$punch->setIpAddress($this->container->get('request')->getClientIp());
	    	$punch->setCreatedOn($now);
	    	$punch->setCreatedBy($userId);
	    	
	    	$em->persist($punch);
	    	$em->flush($punch);
	    	
	    	if (!$punch->getId()) {
	    		error_log('Failed to write punch information');
	    		
	    		return '[write error:info]';
	    	}
    	
    		$user->setLastStatus($statusId);
    		$user->setLastTime($now);
    		
    		$em->flush($user);
    		
    	} else {
    		error_log('Failed to write user information');
    		
    		return '[write error: user]';
    	}
    	
    	return '';
    	
    }
    
    private function generateUsername($firstname, $lastname) {
    	
    	$fname=preg_replace("/[^a-zA-Z0-9]+/", '', strtolower(trim($firstname)));
    	$lname=preg_replace("/[^a-zA-Z0-9]+/", '', strtolower(trim($lastname)));
    	$uname=$fname.'.'.substr($lname, 0, 1);
    	
    	$i=0;
    	$ok=false;
    		
    	while (!$ok) {
    		$username=$uname.(($i)?($i):(''));
    		error_log('username check:'.$username);
    		$u=$this->getDoctrine()
    		->getRepository('TimesheetHrBundle:User')
    		->findOneBy(array('username'=>$username));
    		 
    		if (!$u) {
    			$ok=true;
    		}
    		$i++;
    	}
    	
    	return $username;
    }


    
    private function getStatusColors() {
    	 
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
    
    private function getStatusLevels() {
    	 
    	$arr=array();
    	$arr[0]='Punch in/out';
    	$arr[1]='Other';
    	 
    	return $arr;
    }

    private function getStatuses($id=null) {
    	
    	$statuses=array();
    	$first=null;
    	
		$em=$this->getDoctrine()->getManager();
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
    
    
    public function getPageTitle($title) {

    	$request=$this->getRequest();
    	$functions=$this->get('timesheet.hr.functions');
    	$result=$this->getDoctrine()
    		->getRepository('TimesheetHrBundle:Companies')
    		->findOneBy(array('id'=>$functions->getDomainId($request->getHttpHost())));
    	
    	return $title.(($result && count($result))?(' - '.$result->getCompanyname()):(''));
    }    
}
