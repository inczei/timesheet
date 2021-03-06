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
use Timesheet\Bundle\HrBundle\Form\Type\FPReaderType;
use Timesheet\Bundle\HrBundle\Form\Type\PunchType;
use Timesheet\Bundle\HrBundle\Form\Type\RegisterType;
use Timesheet\Bundle\HrBundle\Form\Type\LoginType;
use Timesheet\Bundle\HrBundle\Form\Type\LocationType;
use Timesheet\Bundle\HrBundle\Form\Type\ChangeStatusType;
use Timesheet\Bundle\HrBundle\Form\Type\ContractType;
use Timesheet\Bundle\HrBundle\Form\Type\GroupType;
use Timesheet\Bundle\HrBundle\Form\Type\HolidayRequestType;
use Timesheet\Bundle\HrBundle\Form\Type\JobTitleType;
use Timesheet\Bundle\HrBundle\Form\Type\MessageType;
use Timesheet\Bundle\HrBundle\Form\Type\ModuleType;
use Timesheet\Bundle\HrBundle\Form\Type\PhotoType;
use Timesheet\Bundle\HrBundle\Form\Type\QualificationType;
use Timesheet\Bundle\HrBundle\Form\Type\ResetPasswordType;
use Timesheet\Bundle\HrBundle\Form\Type\SageImportType;
use Timesheet\Bundle\HrBundle\Form\Type\ShiftType;
use Timesheet\Bundle\HrBundle\Form\Type\StaffRequirementsType;
use Timesheet\Bundle\HrBundle\Form\Type\QualRequirementsType;
use Timesheet\Bundle\HrBundle\Form\Type\StatusType;
use Timesheet\Bundle\HrBundle\Form\Type\TemplateType;
use Timesheet\Bundle\HrBundle\Form\Type\TimingType;
use Timesheet\Bundle\HrBundle\Form\Type\UserDBSCheckType;
use Timesheet\Bundle\HrBundle\Form\Type\UserQualificationType;
use Timesheet\Bundle\HrBundle\Form\Type\UserVisaType;
use Timesheet\Bundle\HrBundle\Entity\Constants;
use Timesheet\Bundle\HrBundle\Entity\Allocation;
use Timesheet\Bundle\HrBundle\Entity\Companies;
use Timesheet\Bundle\HrBundle\Entity\Config;
use Timesheet\Bundle\HrBundle\Entity\Contract;
use Timesheet\Bundle\HrBundle\Entity\Groups;
use Timesheet\Bundle\HrBundle\Entity\Info;
use Timesheet\Bundle\HrBundle\Entity\JobTitles;
use Timesheet\Bundle\HrBundle\Entity\Location;
use Timesheet\Bundle\HrBundle\Entity\LocationIpAddress;
use Timesheet\Bundle\HrBundle\Entity\Messages;
use Timesheet\Bundle\HrBundle\Entity\Modules;
use Timesheet\Bundle\HrBundle\Entity\PasswordReset;
use Timesheet\Bundle\HrBundle\Entity\Qualifications;
use Timesheet\Bundle\HrBundle\Entity\QualRequirements;
use Timesheet\Bundle\HrBundle\Entity\StaffRequirements;
use Timesheet\Bundle\HrBundle\Entity\Shifts;
use Timesheet\Bundle\HrBundle\Entity\ShiftDays;
use Timesheet\Bundle\HrBundle\Entity\Status;
use Timesheet\Bundle\HrBundle\Entity\StatusToDomain;
use Timesheet\Bundle\HrBundle\Entity\Timing;
use Timesheet\Bundle\HrBundle\Entity\User;
use Timesheet\Bundle\HrBundle\Entity\UserQualifications;
use Timesheet\Bundle\HrBundle\Entity\UserVisas;
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
use Symfony\Component\Validator\Constraints\True;
use Symfony\Component\HttpFoundation\Cookie;
use Timesheet\Bundle\HrBundle\Entity\ExportTemplates;
use Timesheet\Bundle\HrBundle\Entity\ModulesCompanies;
use PHPPdf\Exception\DomainException;
use Timesheet\Bundle\HrBundle\Entity\FPReaders;
use Timesheet\Bundle\HrBundle\Classes\TAD;
use Timesheet\Bundle\HrBundle\Classes\TADFactory;
use Timesheet\Bundle\HrBundle\Entity\UserDBSCheck;
use Assetic\Exception\Exception;


class DefaultController extends Controller
{

	public $autoLogout=2;
	
    public function indexAction() {

    	$message='';
    	$error=true;
    	
    	$session=$this->get('session');
    	
   		$session->set('menu', Constants::MENU_HOMEPAGE);
   		$request=$this->getRequest();
   		$functions=$this->get('timesheet.hr.functions');
   		$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
   		$functions->setTimezoneSession($session, $request);

   		$form=$this->createForm(new PunchType($this->getStatus(null, true, $domainId)));
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
	    			$message.=$functions->savePunchStatus($user->getId(), $data['status'], $data['comment']);	    			
	    			
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
        	'title'		=> $functions->getPageTitle('Home')
        ));
    }
    
    public function loginAction() {
    	
    	if ($this->get("security.context")->isGranted('ROLE_USER')) {
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}

    	$message='';
    	$session = $this->get('session');
    	$session->set('menu', Constants::MENU_LOGIN);
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
	    			return $this->redirect($this->generateUrl('timesheet_hr_dashboard'));
	    			
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
// error_log('link:'.$link);
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
// error_log('session link:'.$link);

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
    		
    		if (!$form->get('submit')->isClicked()) {
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
// error_log('old link:'.$link);
					$dt1=$passwordReset->getLastSent();
					$interval=$dt1->diff(new \DateTime('now'));
					$minutes=60*24*$interval->format('%d')+60*$interval->format('%h')+$interval->format('%i');
// error_log('minutes:'.$minutes);
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
// error_log('new link:'.$link);
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
    
    
    public function dashboardAction() {

    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	 
    	$message='';
    	$autologout=array();
    	$session=$this->get('session');
    	$session->set('menu', Constants::MENU_DASHBOARD);
    	$functions=$this->get('timesheet.hr.functions');
    	$currentUser=$this->getUser();
    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	if (TRUE !== $securityContext->isGranted('ROLE_SYSADMIN') && (TRUE === $securityContext->isGranted('ROLE_ADMIN') || TRUE === $securityContext->isGranted('ROLE_MANAGER'))) {
    		// for managers and admin allowed to set auto logout on or off
    		$cookies=$this->getRequest()->cookies;
    		if ($cookies->has('ts_alo')) {
    			// if setted on, all the users will be logged out after a certain amount of time inactivity
    			$autologout[]=array('url'=>$this->generateUrl('timesheet_hr_autologout'), 'name'=>'AutoLogout off');
    		} else {
    			$autologout[]=array('url'=>$this->generateUrl('timesheet_hr_autologout', array('minutes'=>$functions->getConfig('autologout', $domainId))), 'name'=>'AutoLogout on');
    		}
    	}
    	 
    	return $this->render('TimesheetHrBundle:Default:status.html.twig', array(
    		'message'	=> $message,
    		'title'		=> $functions->getPageTitle('Dashboard'),
    		'autologout'=> $autologout,
    		'problems'	=> $functions->getProblems($domainId, $currentUser->getId())
    	));
    	 
    }

    
    public function templatesAction($action, $param1, $param2) {
error_log('templatesAction');
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') || (TRUE !== $securityContext->isGranted('ROLE_ADMIN') && TRUE !== $securityContext->isGranted('ROLE_MANAGER'))) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	$request=$this->getRequest();
    	$session=$request->getSession();
    	$base=$request->attributes->get('_route');
    	$functions=$this->get('timesheet.hr.functions');
    	$domainId=$functions->getDomainId($request->getHttpHost());
    	$availablePages=$functions->getAvailablePages();
    	$availableFormats=$functions->getAvailableFormats();
    	
    	$templates=array();
    	$new=false;
    	switch ($action) {
    		case 'new': {
    			$new=true;
    			// no break
    		}
    		case 'edit': {
    			if ($new) {
    				$template=new ExportTemplates();
    			} else {
    				$template=$this->getDoctrine()
    					->getRepository('TimesheetHrBundle:ExportTemplates')
    					->findOneBy(array('id'=>$param1));    				
    			}
    			$form=$this->createForm(new TemplateType($template, $availablePages, $availableFormats));
    			$form->handleRequest($request);
    			
    			if ($form->isSubmitted() && $form->isValid()) {
    				if (!$form->get('submit')->isClicked()) {
    					return $this->redirect($this->generateUrl('timesheet_hr_templates'));
    				}
    				
    				$data=$form->getData();
    					
				    $currentUser=$this->getUser();
				    					
    				$template->setCreatedOn(new \DateTime());
				    $template->setCreatedBy($currentUser->getId());
				    $template->setDomainId(((isset($data['domainId']))?($data['domainId']):($domainId)));
				    $template->setPageId($data['pageId']);
    				$template->setName($data['name']);
				    $template->setFormat($data['format']);
				    $template->setComment(''.$data['comment']);
				    $template->setHeading($data['heading']);
				    $template->setAvailable($data['available']);
				    					
				    $em=$this->getDoctrine()->getManager();
				    try {
					   	if ($new) {
					   		$em->persist($template);
					   	}
					   	$em->flush($template);
    				} catch (\Exception $e) {
    					error_log('Database error:'.$e->getMessage());
    					$message='Database error, could not save';
    					$session->getFlashBag()->set('notice', $message);
    				}
    				return $this->redirect($this->generateUrl('timesheet_hr_templates'));    					
    			}
    			break;
    		}
    		default: {
    			$templates=$functions->getExportTemplateNames($domainId);
    			break;
    		}
    	}
    	
    	
    	return $this->render('TimesheetHrBundle:Default:templates.html.twig', array(
    		'message'	=> '',
    		'base'		=> $base,
    		'title'		=> $functions->getPageTitle('Templates'),
    		'pages'		=> $availablePages,
    		'formats'	=> $availableFormats,
    		'templates'	=> $templates,
    		'templateForm'	=> ((isset($form))?($form->createView()):(null))
    	));
    }
    
    
    public function configurationAction() {
error_log('configAction');
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') || (TRUE !== $securityContext->isGranted('ROLE_ADMIN') && TRUE !== $securityContext->isGranted('ROLE_MANAGER'))) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	$base=$this->getRequest()->attributes->get('_route');
    
    	$session=$this->get('session');
    	$session->set('menu', Constants::MENU_CONFIG);

    	$request=$this->getRequest();
    	
    	$functions=$this->get('timesheet.hr.functions');
    	
    	$hct=$functions->getHolidayCalculations($functions->getDomainId($request->getHttpHost()));
    	if (count($hct)) {
    		$hct[0]='System default ('.$hct[$functions->getConfig('hct')].')';
    	}
    	$dId=$functions->getDomainId($request->getHttpHost());
    	if (!$dId) {
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
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
    		'minhoursforlunch'=>$result->getMinHoursForLunch(),
    		'lunchtime'=>$result->getLunchtime(),
    		'lunchtimeUnpaid'=>$result->getLunchtimeUnpaid(),
    		'autologout'=>$result->getAutologout(),
    		'rounding'=>$result->getRounding(),
    		'grace'=>$result->getGrace()
    	);
    	$defaults=array(
    		'hct'=>$functions->getConfig('hct'),
    		'yearstart'=>$functions->changeDateFormat($functions->getConfig('yearstart'), 'dd/mm'),
    		'ahew'=>$functions->getConfig('ahew'),
    		'minhoursforlunch'=>$functions->getConfig('minhoursforlunch'),
    		'lunchtime'=>$functions->getConfig('lunchtime'),
    		'lunchtimeUnpaid'=>$functions->getConfig('lunchtimeUnpaid'),
    		'autologout'=>$functions->getConfig('autologout'),
    		'rounding'=>$functions->getConfig('rounding'),
    		'grace'=>$functions->getConfig('grace')
    	);
    	 
    	$form=$this->createForm(new ConfigType($conf, $functions->getTimezone(), $hct, $defaults));
    	$form->handleRequest($request);
    	if ($form->isSubmitted() && $form->isValid()) {
    		$message='Valid';
    		if (!$form->get('submit')->isClicked()) {
    			return $this->redirect($this->generateUrl('timesheet_hr_admin'));
    		}
	    	
    		$data=$form->getData();
	    		
	    	$result->setCompanyname($data['companyname']);
	    	$result->setDomain($data['domain']);
	    	$result->setTimezone($data['timezone']);
	    	$result->setHCT($data['hct']);
	    	$result->setAHEW($data['ahew']);
	    	$result->setMinHoursForLunch($data['minhoursforlunch']);
	    	$result->setLunchtime($data['lunchtime']);
	    	$result->setLunchtimeUnpaid($data['lunchtimeUnpaid']);
	    	$result->setYearstart($data['yearstart']);
	    	$result->setAutologout($data['autologout']);
	    	$result->setGrace($data['grace']);
	    	$result->setRounding($data['rounding']);
	    		
	    	$em=$this->getDoctrine()->getManager();
	    	error_log('try to save');
	    	try {
		    	$em->persist($result);
				$em->flush($result);
	    	} catch (\Exception $e) {
	    		error_log('cannot save config');
	    	}
	    		
			if ($result->getId()) {
				$message='Config settings updated';
				$session->getFlashBag()->set('notice', $message);
				
				error_log($message);
				return $this->redirect($this->generateUrl('timesheet_hr_configuration'));
			}
    	}
    	return $this->render('TimesheetHrBundle:Default:config.html.twig', array(
    			'base'		=> $base,
    			'form'		=> $form->createView(),
    			'title'		=> $functions->getPageTitle('Config'),
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
    	$session->set('menu', Constants::MENU_MESSAGES);

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
			   		}
			   		
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
    		'title'		=> $functions->getPageTitle('Messages'),
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
    	$session->set('menu', Constants::MENU_SCHEDULE);

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
// error_log('calendar:'.print_r($calendar, true));
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
// error_log('locationId in session:'.$locationId);
    		} else {
// error_log('locationId without session');
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
    	$functions=$this->get('timesheet.hr.functions');
    	
    	return $this->render('TimesheetHrBundle:Default:schedule.html.twig', array(
    		'base'		=> $base,
    		'locationId'=> $locationId,
    		'timestamp'	=> $timestamp,
    		'usersearch'=> $userSearch,
    		'groupsearch'=> $groupSearch,
    		'qualificationsearch'=> $qualificationSearch,
    		'title'		=> $functions->getPageTitle('Schedule'),
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
    	
    	$session->set('menu', Constants::MENU_TIMESHEET);
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
    	$functions=$this->get('timesheet.hr.functions');
    	
    	return $this->render('TimesheetHrBundle:Default:timesheet.html.twig', array(
    		'base'		=> $base,
    		'userId'	=> $userId,
    		'title'		=> $functions->getPageTitle('Timesheet'),
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
    	 
    	$session->set('menu', Constants::MENU_HOLIDAY);

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
    	$functions=$this->get('timesheet.hr.functions');
    	
    	return $this->render('TimesheetHrBundle:Default:holiday.html.twig', array(
    		'base'		=> $base,
    		'userId'	=> $userId,
    		'timestamp'	=> $timestamp,
			'title'		=> $functions->getPageTitle('Holiday'),
   			'message'	=> $message
    	));
    }


    public function resetAction() {
    	
    	$session=$this->get('session');
    	$session->remove('admin');
    	$session->remove('calendar');
    	return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    }


    public function userphotosAction($action, $id) {
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	
    	$session=$this->get('session');
        if ($id) {
    		$session->set('userPhotos', $id);
    		return $this->redirect($this->generateUrl('timesheet_hr_userphotos', array('action'=>$action)));
    	}
    	if ($session->get('userPhotos')) {
    		$userId=$session->get('userPhotos');
    	} else {
    		$userId=null;
    	}

    	if ($userId) {
    		$functions=$this->get('timesheet.hr.functions');
    		$request=$this->getRequest();
			$domainId=$functions->getDomainId($request->getHttpHost());
		
			$userManager = $this->container->get('fos_user.user_manager');
			$user=$userManager->findUserBy(array('id'=>$userId));
			$photos=array();
			$images=array();
			
			switch ($action) {
				case 'new': {
			    	$form=$this->createForm(new PhotoType($user->getId(), trim($user->getTitle().' '.$user->getFirstName().' '.$user->getLastName()), ''));
			    	
			    	if ($request->getMethod() == 'POST') {
//			    	$form->bindRequest($request);
			    	$form->handleRequest($request);
	    				if ($form->isValid()) {
	    					if (!$form->get('submit')->isClicked()) {
	    						$session->remove('admin');
	    						return $this->redirect($this->generateUrl('timesheet_hr_userphotos'));
	    					} else
	    					{ // if ($userForm->get('submit')->isClicked()) {
	
		    					$data=$form->getData();
		    					
								$filepath=$data['file']->getPathname();
	
// error_log('data:'.print_r($data, true));								
							
								$phototype=$data['file']->getMimeType();
// error_log('phototype:'.$phototype);
								$photosize=$data['file']->getClientSize();
// error_log('file size:'.$photosize);							
								if ($photosize>1024*1024) {
									$session->getFlashBag()->set('notice', 'Please upload smallest file. The limit is 1M');
									return $this->redirect($this->generateUrl('timesheet_hr_userphotos'));
								}
	
								if (false === array_search($phototype, array('image/jpeg', 'image/png', 'image/gif'))) {
									$session->getFlashBag()->set('notice', 'Wrong file type');
									return $this->redirect($this->generateUrl('timesheet_hr_userphotos'));
								} else {
									$phototype=str_replace('image/', '', $phototype);
								}
								
								if (file_exists($filepath) && is_readable($filepath)) {
									
									
									$photodata=file_get_contents($filepath);
	
									unlink($filepath);
									$notes=$data['notes'];
									try {
				    					$currentUser=$this->getUser();
				    					
				    					$userPhotos=new UserPhotos();
				    					$userPhotos->setCreatedOn(new \DateTime());
				    					$userPhotos->setCreatedBy($currentUser->getId());
				    					$userPhotos->setUserId($userId);
				    					$userPhotos->setPhoto($photodata);
				    					$userPhotos->setType($phototype);
				    					$userPhotos->setNotes(''.$notes);
				    					$em=$this->getDoctrine()->getManager();
				    					$em->persist($userPhotos);
				    					$em->flush($userPhotos);
			    					} catch (\Exception $e) {
			    						error_log('Database error:'.$e->getMessage());
			    						$message='Database error, could not save';
			    						$session->getFlashBag()->set('notice', $message);
			    						return $this->redirect($this->generateUrl('timesheet_hr_userphotos'));
			    					}
error_log('6');			    					
				    				if ($userPhotos->getId()) {
	
				    					$tmp=$functions->getUserPhotos($userId, $domainId, false, true, 600, $userPhotos->getId());
				    					$photodata=reset($tmp);
// error_log('photodata:'.print_r($photodata, true));
// error_log('orig dim: '.$photodata['origWidth'].'x'.$photodata['origHeight']);
// error_log('dim: '.$photodata['width'].'x'.$photodata['height']);
										$tmpPhoto=$this->getDoctrine()
											->getRepository('TimesheetHrBundle:UserPhotos')
											->findOneBy(array('id'=>$userPhotos->getId()));
										
				    					$tmpPhoto->setPhoto(base64_decode($photodata['photo']));
				    					$em->flush($tmpPhoto);
				    					
				    					$message='Photo uploaded';
				    				} else {
				    					$message='Error occured';
				    				}
			    					
			    					$session->getFlashBag()->set('notice', $message);
			    					return $this->redirect($this->generateUrl('timesheet_hr_userphotos'));
			    					 
								}
	    					}
    					}
    				}
			    	break;
				}
				default: {
					$photos=$functions->getUserPhotos($userId, $domainId, true, true, 200);
					if ($photos && count($photos)) {
						foreach ($photos as $k=>$photo) {
							$images[$k]=$photo;
						}
					}
					break;
				}
			}
			
    		return $this->render('TimesheetHrBundle:Default:userPhotos.html.twig', array(
    			'title'		=> $functions->getPageTitle('Photos'),
    			'form'		=> ((isset($form))?($form->createView()):(null)),
    			'user'		=> $user,
    			'photos'	=> $photos,
    			'images'	=> $images
    		));

    		
    	} else {
    		error_log('no userId...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	 
    	 
    }
    
    
    public function usermenuAction($base, $domainId) {
    	
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}

    	$isAdmin=(TRUE === $securityContext->isGranted('ROLE_ADMIN'));
    	$sysadmin=(TRUE === $securityContext->isGranted('ROLE_SYSADMIN'));
    	$functions=$this->get('timesheet.hr.functions');
    	
    	$session=$this->get('session');
    	if ($session->get('userSearch')) {
    		$userSearch=$session->get('userSearch');
    		// error_log('user search loaded : '.$userSearch);
    	} else {
    		$userSearch='';
    	}

    	if ($sysadmin) {
    		$domainSearch=$functions->getCompanies();
    	}
		$users=$functions->getUsersList(null, ((strlen($userSearch))?($userSearch):(null)), false, null, null, null, true, $domainId);
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
    		'domainSearch'	=> ((isset($domainSearch))?($domainSearch):(null)),
    		'AHE'			=> $functions->getConfig('ahe', $dId),
    		'AHEW'			=> $functions->getConfig('ahew', $dId),
    		'holidaycalculations'	=> $functions->getHolidayCalculations($dId),
			'hct'			=> $functions->getConfig('hct', $dId),
   			'lunchtime'		=> $functions->getConfig('lunchtime', $dId),
   			'lunchtimeUnpaid'	=> $functions->getConfig('lunchtimeUnpaid', $dId),
			'domainId'		=> $domainId,
    		'domains'		=> (($domainId)?(null):($functions->getDomains())),
    		'jobtitles'		=> $functions->getJobTitles(),
    		'isAdmin'		=> $isAdmin
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
		$em=$this->getDoctrine()->getManager();
		
		$qb=$em
			->createQueryBuilder()
			->select('g')
			->from('TimesheetHrBundle:Groups', 'g')
			->orderBy('g.name', 'ASC');

		if ($domainId) {
			$qb->where('g.domainId=:dId')
				->setParameter('dId', $domainId);
		}
		
		
		$query=$qb->getQuery();
		$groups=$query->useResultCache(true)->getArrayResult();
	    $functions=$this->get('timesheet.hr.functions');
	    
    	return $this->render('TimesheetHrBundle:Internal:groupmenu.html.twig', array(
    		'groups'	=> $groups,
    		'domainId'	=> $domainId,
    		'domains'	=> (($domainId)?(null):($functions->getCompanies())),
    		'base'		=> $base
    	));
    }
    

    public function qualificationmenuAction($domainId, $base) {
    	 
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	$em=$this->getDoctrine()->getManager();
    	
    	$qb=$em
    		->createQueryBuilder()
    		->select('q')
    		->from('TimesheetHrBundle:Qualifications', 'q')
    		->orderBy('q.title', 'ASC');
    	
    	if ($domainId) {
    		$qb->where('q.domainId=:dId')
    			->setParameter('dId', $domainId);
    	}
    	
    	
    	$query=$qb->getQuery();
    	$qualifications=$query->useResultCache(true)->getArrayResult();
    	 
	    $functions=$this->get('timesheet.hr.functions');
    	    	 
    	return $this->render('TimesheetHrBundle:Internal:qualificationmenu.html.twig', array(
    		'qualifications'	=> $qualifications,
    		'domainId'			=> $domainId,
    		'domains'			=> (($domainId)?(null):($functions->getCompanies())),
    		'base'				=> $base
    	));
    }
    
    
    public function jobtitlemenuAction($domainId, $base) {
    	 
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	$em=$this->getDoctrine()->getManager();
    	
    	$qb=$em
    		->createQueryBuilder()
    		->select('jt')
    		->from('TimesheetHrBundle:JobTitles', 'jt')
    		->orderBy('jt.title', 'ASC');
    	
    	if ($domainId) {
    		$qb->where('jt.domainId=:dId')
    			->setParameter('dId', $domainId);
    	}
    	
    	
    	$query=$qb->getQuery();
    	$jobtitles=$query->useResultCache(true)->getArrayResult();
    	 
	    $functions=$this->get('timesheet.hr.functions');
    	    	 
    	return $this->render('TimesheetHrBundle:Internal:jobtitlesmenu.html.twig', array(
    		'jobtitles'	=> $jobtitles,
    		'domainId'	=> $domainId,
    		'domains'	=> (($domainId)?(null):($functions->getCompanies())),
    		'base'		=> $base
    	));
    }
    
    
    public function statusmenuAction($domainId, $base) {
    	
        $securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	$functions=$this->get('timesheet.hr.functions');
    	
    	return $this->render('TimesheetHrBundle:Internal:statusmenu.html.twig', array(
    		'statuses'	=> $functions->getStatuses(),
    		'base'		=> $base,
    		'companies'	=> (($domainId)?(null):($functions->getCompanies())),
    		'domainId'	=> $domainId
    	));
    }
    
    
    public function fpreadermenuAction($domainId, $base) {
    	
        $securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	$functions=$this->get('timesheet.hr.functions');
    	
    	return $this->render('TimesheetHrBundle:Internal:fpreadermenu.html.twig', array(
    		'fpreaders'	=> $functions->getFPReaders($domainId),
    		'base'		=> $base,
    		'companies'	=> (($domainId)?(null):($functions->getCompanies())),
    		'locations'	=> $functions->getLocation(null, true, (($domainId)?($domainId):(null))),
    		'domainId'	=> $domainId
    	));
    }
    
    
    public function modulesmenuAction($domainId, $base) {
    	
        $securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	$functions=$this->get('timesheet.hr.functions');
    	
    	return $this->render('TimesheetHrBundle:Internal:modulesmenu.html.twig', array(
    		'modules'	=> $functions->getModules(),
    		'base'		=> $base,
    		'companies'	=> (($domainId)?(null):($functions->getCompanies())),
    		'domainId'	=> $domainId
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

    	return $this->render('TimesheetHrBundle:Internal:shiftmenu.html.twig', array(
    		'base'		=> $base,
    		'locations'	=> $functions->getLocation(null, true, $domainId),
    		'shifts'	=> $shifts
    	));
    }
    
    
    public function usersAction($action, $param1, $param2) {
error_log('usersAction');
    	if (True !== $this->get("security.context")->isGranted('ROLE_MANAGER')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	
    	}
    	$sysadmin=(TRUE === $this->get('security.context')->isGranted('ROLE_SYSADMIN'));
    	$functions=$this->get('timesheet.hr.functions');
    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	if (!$domainId) {
error_log('no domain id');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	$base=$this->getRequest()->attributes->get('_route');
    	$session=$this->get('session');
    	$session->set('menu', Constants::MENU_ADMIN);
    	$message='';
    	
    	if ($action == 'clean') {
    		$session->remove('admin');
    		return $this->redirect($this->generateUrl('timesheet_hr_users'));
    	}

    	if (in_array($action, Constants::userActions) && $param1!=null) {
    	
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
error_log('existing user:'.print_r($user, true));
    				}

    				$currentUser=$this->getUser();
    				if ($sysadmin) {
    					$fpReader=false;
    				} else {
    					$fpReader=($functions->getAvailableFPReaders($domainId) > 0);
    				}
    				$userForm=$this->createForm(new RegisterType($functions->getGroups($sysadmin?null:$domainId), $functions->getLocation(null, true, $sysadmin?null:$domainId), $functions->getAvailableRoles($currentUser->getRoles(), $user->getRoles()), $functions->getTitles(), $functions->getEthnics(), Constants::maritalStatuses, $user, $new, $fpReader, (($sysadmin)?($functions->getCompanies()):(null))));

    				$userForm->handleRequest($this->getRequest());
    	
    				if ($userForm->isValid()) {
    					if (!$userForm->get('submit')->isClicked()) {
    						$session->remove('admin');
    						return $this->redirect($this->generateUrl($base));
    					} else { // if ($userForm->get('submit')->isClicked()) {
	    					$message='Valid';
	    					$generatedUsername='';
	    					$data=$userForm->getData();
	    					$userManager = $this->container->get('fos_user.user_manager');
	    					if ($new && $domainId && strlen($data['username'])<3) {
// error_log('generate username');
	    						$generatedUsername=$functions->generateUsername($data['firstName'], $data['lastName']);
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
			    			$user->setEthnic($data['ethnic']);
			    			$user->setMaritalStatus($data['maritalStatus']);
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
	    					} else {
	    						$user->setDomainId($domainId);
	    					}
	    					$gAdmin=false;
	    					$lAdmin=false;
	    					switch ($data['role']) {
	    						case 'ROLE_ADMIN' : {
	    							$gAdmin=true;
	    							$lAdmin=true;
	    							break;
	    						}
	    						case 'ROLE_USER' : {
	    							break;
	    						}
	    						default : {
	    							$gAdmin=((isset($data['groupAdmin']) && $data['groupAdmin']));
	    							$lAdmin=((isset($data['locationAdmin']) && $data['locationAdmin']));
	    							break;
	    						}
	    					}
	    					$user->setGroupAdmin($gAdmin);
	    					$user->setLocationAdmin($lAdmin);
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
	    						error_log('updated user:'.print_r($user, true));
								$message='User '.trim($user->getTitle().' '.$user->getFirstName().' '.$user->getLastName()).' ('.$user->getUsername().') '.(($new)?('saved'):('updated')).(($generatedUsername!='')?(', new username: '.$generatedUsername):(''));
	    						if ($new && $functions->fpEnrol($user)) {
	    							$message.=', enrolled in FP Reader, ID:'.$user->getId();
	    						}
	    						
	    						$session->remove('admin');
	    						$session->getFlashBag()->set('notice', $message);
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
									
									$session->getFlashBag()->set('notice', 'Timings for '.trim($user->getTitle().' '.$user->getFirstName().' '.$user->getLastName()).' ('.$user->getUsername().') is deleted');
									
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
				    			$session->getFlashBag()->set('notice', 'Timings for '.trim($user->getTitle().' '.$user->getFirstName().' '.$user->getLastName()).' ('.$user->getUsername().') added');
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
		    		
		    		$contractForm=$this->createForm(new ContractType($contract, $user, $functions->getHolidayCalculations($domainId), $functions->getJobTitles($domainId)));
		    		
		    		$contractForm->handleRequest($this->getRequest());
		
		    		if ($contractForm->isValid()) {
		
		    			if (!$contractForm->get('submit')->isClicked()) {
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
						$contract->setContractType($data['contractType']);
						$contract->setAHEonYS($data['AHEonYS']?true:false);
						$contract->setInitHolidays($data['initHolidays']);
						$contract->setJobTitleId($data['jobTitleId']);
						$contract->setJobDescription($data['jobDescription']);
						
						if ($new2) {
							$contract->setUserId($data['userId']);
							
							$em->persist($contract);
						}
						$em->flush($contract);
		    		
		    			if ($contract->getId()) {
		    				
		    				$session->remove('admin');
		    				$session->getFlashBag()->set('notice', 'Contract for '.trim($user->getTitle().' '.$user->getFirstName().' '.$user->getLastName()).' ('.$user->getUsername().') '.(($new2)?('saved'):('updated')));
		    		
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    		}
    				break;
    			}

    			case 'editvisa' : {

    				if ($new) {
		    			$session->remove('admin');
		    			return $this->redirect($this->generateUrl($base));
    				} else {
	    				$user=$this->getDoctrine()
			    			->getRepository('TimesheetHrBundle:User')
			    			->findOneBy(array('id'=>$admin['param1']));

	    				if ($new2) {
	    					$uservisa=new UserVisas();
	    				} else {
		    				$uservisa=$this->getDoctrine()
				    			->getRepository('TimesheetHrBundle:UserVisas')
				    			->findOneBy(array('id'=>$admin['param2']));
	    				}
    				}
		    		
		    		$uservisaForm=$this->createForm(new UserVisaType($uservisa, $user, $functions->getVisaList($domainId)));
		    		
		    		$uservisaForm->handleRequest($this->getRequest());
		
		    		if ($uservisaForm->isValid()) {
		
		    			if (!$uservisaForm->get('submit')->isClicked()) {
		    				$session->remove('admin');
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    			 
		    			$message='Valid';
		    		
		    			$data=$uservisaForm->getData();

						$uservisa->setUserId($data['userId']);
						$uservisa->setVisaId($data['visaId']);
						$uservisa->setStartDate($data['startDate']);
						$uservisa->setEndDate($data['endDate']);
						$uservisa->setNotExpire($data['notExpire']);
						$uservisa->setNotes(''.$data['notes']);
						
						if ($new2) {
							$currentUser=$this->getUser();
							$uservisa->setCreatedBy($currentUser->getId());
							$uservisa->setCreatedOn(new \DateTime('now'));
							
							$em->persist($uservisa);
						}
						$em->flush($uservisa);
		    		
		    			if ($uservisa->getId()) {
		    				
		    				$session->remove('admin');
		    				$session->getFlashBag()->set('notice', 'Visa for '.trim($user->getTitle().' '.$user->getFirstName().' '.$user->getLastName()).' ('.$user->getUsername().') '.(($new2)?('saved'):('updated')));
		    		
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    		}
    				break;
    			}
    			
    		    case 'editdbs' : {

    				if ($new) {
		    			$session->remove('admin');
		    			return $this->redirect($this->generateUrl($base));
    				} else {
	    				$user=$this->getDoctrine()
			    			->getRepository('TimesheetHrBundle:User')
			    			->findOneBy(array('id'=>$admin['param1']));

	    				if ($new2) {
	    					$userdbscheck=new UserDBSCheck();
	    				} else {
		    				$userdbscheck=$this->getDoctrine()
				    			->getRepository('TimesheetHrBundle:UserDBSCheck')
				    			->findOneBy(array('id'=>$admin['param2']));
	    				}
    				}
		    		
		    		$userdbscheckForm=$this->createForm(new UserDBSCheckType($userdbscheck, $user, $functions->getDBSTypeList($domainId)));
		    		
		    		$userdbscheckForm->handleRequest($this->getRequest());
		
		    		if ($userdbscheckForm->isValid()) {
		
		    			if (!$userdbscheckForm->get('submit')->isClicked()) {
		    				$session->remove('admin');
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    			 
		    			$message='Valid';
		    		
		    			$data=$userdbscheckForm->getData();

						$userdbscheck->setUserId($data['userId']);
						$userdbscheck->setTypeId($data['typeId']);
						$userdbscheck->setDisclosureNo(''.$data['disclosureNo']);
						$userdbscheck->setIssueDate($data['issueDate']);
						$userdbscheck->setNotes(''.$data['notes']);
						
						if ($new2) {
							$currentUser=$this->getUser();
							$userdbscheck->setCreatedBy($currentUser->getId());
							$userdbscheck->setCreatedOn(new \DateTime('now'));
							
							$em->persist($userdbscheck);
						}
						$em->flush($userdbscheck);
		    		
		    			if ($userdbscheck->getId()) {
		    				
		    				$session->remove('admin');
		    				$session->getFlashBag()->set('notice', 'DBS Check for '.trim($user->getTitle().' '.$user->getFirstName().' '.$user->getLastName()).' ('.$user->getUsername().') '.(($new2)?('saved'):('updated')));
		    		
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

    				$fullname=trim($user->getFirstName().' '.$user->getLastName().' ('.$user->getUsername().')');
    				$list=$functions->getQualifications($admin['param1'], true, $domainId);
    				$levels=$functions->getQualificationLevels();
		    		$userqualificationForm=$this->createForm(new UserQualificationType($functions->getQualifications(null, false, $domainId), $levels, $user, $list));
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
				    			$userqualification->setLevelId($data['levelId']);
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
			    				$session->getFlashBag()->set('notice', 'Qualification for '.trim($user->getTitle().' '.$user->getFirstName().' '.$user->getLastName()).' ('.$user->getUsername().') is '.(($new2)?('added'):('updated')));
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
									
									$session->getFlashBag()->set('notice', 'Qualification for '.trim($user->getTitle().' '.$user->getFirstName().' '.$user->getLastName()).' ('.$user->getUsername().') is deleted');
									
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
    		'title'			=> $functions->getPageTitle('Users'),
    		'userForm'		=> ((isset($userForm))?($userForm->createView()):(null)),
    		'contractForm'	=> ((isset($contractForm))?($contractForm->createView()):(null)),
    		'timingForm'	=> ((isset($timingForm))?($timingForm->createView()):(null)),
    		'timingList'	=> ((isset($timingForm))?($functions->getTimings($admin['param1'], $domainId)):(null)),
    		'userqualificationForm'	=> ((isset($userqualificationForm))?($userqualificationForm->createView()):(null)),
    		'userdbscheckForm'	=> ((isset($userdbscheckForm))?($userdbscheckForm->createView()):(null)),
    		'uservisaForm'	=> ((isset($uservisaForm))?($uservisaForm->createView()):(null)),
    		'qualifications' => ((isset($userqualificationForm))?($functions->getQualifications($admin['param1'], true, $domainId)):(null)),
    		'levels'		=> ((isset($levels))?($levels):(null)),
			'fullname' 		=> ((isset($userqualificationForm))?($fullname):(null)),
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
    	$session->set('menu', Constants::MENU_ADMIN);
    	$message='';
    	$ipaddresses=array();

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
		    		if ($locationForm->isSubmitted()) {
		    			if (!$locationForm->get('submit')->isClicked()) {
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
		    			$location->setLatitude($data['latitude']);
		    			$location->setLongitude($data['longitude']);
		    			$location->setRadius($data['radius']);
		    			 
		    			try {
			    			if ($new) {
			    				$em->persist($location);
			    			}
			    			$em->flush($location);
						} catch (\Exception $e) {
    						if (strpos($e->getMessage(), '1062') === false) {
    							error_log('Database error:'.$e->getMessage());
    						} else {
    							$session->getFlashBag()->set('notice', 'Sorry, this location already exists');
    						}
    					}
			    			
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
    		'domainId'		=> $domainId,
    		'message'		=> $message,
    		'title'			=> $functions->getPageTitle('Locations'),
			'locationForm'	=> ((isset($locationForm))?($locationForm->createView()):(null)),
    		'locations'		=> $functions->getLocation(null, true, $domainId),
    		'members'		=> ((isset($admin['param1']) && $admin['param1'])?($functions->getMembers($admin['param1'])):(null))
    	));
    }
    
    
    public function adminAction($action, $param1, $param2) {
    	if (!$this->get("security.context")->isGranted('ROLE_ADMIN')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	
    	}
    	 
error_log('adminAction');
    	$admin=array();
    	$functions=$this->get('timesheet.hr.functions');
    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	$base=$this->getRequest()->attributes->get('_route');
       	    	
    	$message='';
    	$session=$this->get('session');

    	$session->set('menu', Constants::MENU_ADMIN);
    	
    	if (in_array($action, Constants::adminActions) && $param1!=null) {

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

	    	    case 'newfpreader' :
	    			$fpreader=new FPReaders();
	    		case 'editfpreader' : {
	    			if ($admin['param1']) {
	    				$fpreader=$this->getDoctrine()
	    					->getRepository('TimesheetHrBundle:FPReaders')
	    					->findOneBy(array('id'=>$admin['param1']));
	    				
	    				if (!$fpreader || count($fpreader)<1) {
	    					return $this->redirect($this->generateUrl($base));
	    				}
	    			}
	    			$fpreaderForm=$this->createForm(new FPReaderType($fpreader, $functions->getLocation(null, true, $domainId), null));
	    			$fpreaderForm->handleRequest($this->getRequest());
	    			if ($fpreaderForm->isValid()) {
	    				if (!$fpreaderForm->get('submit')->isClicked()) {
	    					$session->remove('admin');
	    					return $this->redirect($this->generateUrl($base));
	    				}
	    				
	    				$data=$fpreaderForm->getData();
	    			
	    				$fpreader->setDeviceId($data['deviceId']);
	    				$fpreader->setDeviceName(''.$data['deviceName']);
	    				$fpreader->setIpAddress($data['ipAddress']);
	    				$fpreader->setPort($data['port']);
	    				$fpreader->setStatus($data['status']);
	    				$fpreader->setComment(''.$data['comment']);
	    				$fpreader->setPassword(''.$data['password']);
	    				$fpreader->setLocationId($data['locationId']);
	    					
	    				if (!$data['id']) {
	    					$fpreader->setVersion('');
	    					$fpreader->setSerialnumber('');
	    					$fpreader->setPlatform('');
	    				}
	    				if (isset($data['domainId'])) {
	    					$fpreader->setDomainId($data['domainId']);
	    				} else {
	    					$fpreader->setDomainId($domainId);
	    				}
	    					
	    				try {
		    				if (!$data['id']) {
		    					$em->persist($fpreader);
		    				}
		    				$em->flush($fpreader);
	    				} catch (\Exception $e) {
	    					if (strpos($e->getMessage(), '1062') === false) {
	    						error_log('Database error:'.$e->getMessage());
	    					} else {
	    						$session->getFlashBag()->set('notice', 'Sorry, this reader already exists');
	    					}
	    				}
	
	    				if ($fpreader->getId()) {
	    					$session->remove('admin');
	    					$session->getFlashBag()->set('notice', 'Fingerprint Reader '.(($new)?('saved'):('updated')));
	    					return $this->redirect($this->generateUrl($base));
	    				}
	    			}		 
	    			break;
	    		}
    			
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
		    		if ($groupForm->isSubmitted()) {
		    			if (!$groupForm->get('submit')->isClicked()) {
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
		    		if ($qualificationForm->isSubmitted()) {
		    			if (!$qualificationForm->get('submit')->isClicked()) {
		    				$session->remove('admin');
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    			 
		    			$message='Valid';
		    			$data=$qualificationForm->getData();

		    			$qualification->setTitle(''.$data['title']);
		    			$qualification->setComments(''.$data['comments']);
		    			$qualification->setDomainId($domainId);
		    			
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

    			case 'editjobtitle' : {

    				if ($new) {
    					$jobTitle=new JobTitles();
    				} else {
	    				$jobTitle=$this->getDoctrine()
			    			->getRepository('TimesheetHrBundle:JobTitles')
			    			->findOneBy(array('id'=>$admin['param1']));
	    				
    				}
		    		$jobTitleForm=$this->createForm(new JobTitleType($jobTitle));
		    		$jobTitleForm->handleRequest($this->getRequest());
		    		if ($jobTitleForm->isValid()) {
		    			if (!$jobTitleForm->get('submit')->isClicked()) {
		    				$session->remove('admin');
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    			$message='Valid';
		    			$data=$jobTitleForm->getData();

		    			$jobTitle->setTitle(''.$data['title']);
		    			$jobTitle->setActive($data['status']);
	    				$jobTitle->setDomainId($domainId);
		    			
	    				try {
			    			if ($new) {
			    				$em->persist($jobTitle);
			    			}
			    			$em->flush($jobTitle);
	    				} catch (\Exception $e) {
	    					if (strpos($e->getMessage(), '1062') === false) {
	    						error_log('Database error:'.$e->getMessage());
	    					} else {
	    						$session->getFlashBag()->set('notice', 'Sorry, this job title already exists');
	    					}
	    				}
		    				
		    			if ($jobTitle->getId()) {
		    				
		    				$session->remove('admin');
		    				$session->getFlashBag()->set('notice', 'Job title "'.$jobTitle->getTitle().'" '.(($new2)?('saved'):('updated')));
		    		
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
    		'fpreaderForm'	=> ((isset($fpreaderForm))?($fpreaderForm->createView()):(null)),
    		'jobTitleForm'	=> ((isset($jobTitleForm))?($jobTitleForm->createView()):(null)),
    		'message'		=> $message,
    		'title'			=> $functions->getPageTitle('Administration'),
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

    	$session->set('menu', Constants::MENU_ADMIN);
    	if ($action == 'clean') {
    		$session->remove('admin');
    		return $this->redirect($this->generateUrl('timesheet_hr_shifts'));
    	}

    	if (in_array($action, Constants::adminActions) && $param1!=null) {

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
		    			if (!$shiftForm->get('submit')->isClicked()) {
		    				$session->remove('admin');
		    				return $this->redirect($this->generateUrl($base));
		    			}
		    			 
		    			$message='Valid';
		    			$data=$shiftForm->getData();

						$shift->setTitle($data['title']);
						$shift->setLocationId($data['locationId']);
						$shift->setStartTime($data['startTime']);
						$shift->setFinishTime($data['finishTime']);
						$shift->setStartBreak($data['startBreak']);
						$shift->setFinishBreak($data['finishBreak']);
						$shift->setStrictBreak($data['strictBreak']);
						$shift->setMinWorkTime($data['minWorkTime']);
						$shift->setFpStartTime($data['fpStartTime']);
						$shift->setFpFinishTime($data['fpFinishTime']);
						$shift->setFpStartBreak($data['fpStartBreak']);
						$shift->setFpFinishBreak($data['fpFinishBreak']);
						
						if ($data['startTime'] > $data['finishTime']) {
							$data['finishTime']->modify('+1 day');
						}
		    			if (isset($data['fpFinishTime']) && $data['fpStartTime'] > $data['fpFinishTime']) {
							$data['fpFinishTime']->modify('+1 day');
						}
						if ($data['startBreak'] && $data['startBreak'] < $data['startTime']) {
							$data['startBreak']->modify('+1 day');
						}
						if ($data['finishBreak'] && $data['finishBreak'] < $data['startTime']) {
							$data['finishBreak']->modify('+1 day');
						}

						$error=false;
						if ($data['startTime'] == $data['finishTime']) {
							$message='Start time equal finish time';
							$error=true;
						}
						if ($data['startTime'] > $data['finishTime']) {
							$message='Start time earlier than finish time';
							$error=true;
						}
						if (!(isset($data['fpStartTime']) && isset($data['fpFinishTime']) && isset($data['fpStartBreak']) && isset($data['fpFinishBreak']))
								&& (!(isset($data['fpStartTime']) || isset($data['fpFinishTime']) || isset($data['fpStartBreak']) || isset($data['fpFinishBreak'])))) {
							$message='Some of FP time defined. Please select all or none ';
							$error=true;
						}
						
						if (isset($data['fpStartTime'])) {
			    			if ($data['fpStartTime'] > $data['startTime']) {
								$message='Start time earlier than FP Start time';
								$error=true;
							}
						}
						if (isset($data['fpFinishTime'])) {
			    			if ($data['fpFinishTime'] < $data['finishTime']) {
								$message='Finish time later than FP Finish time';
								$error=true;
							}
						}
						if (isset($data['fpStartBreak'])) {
			    			if ($data['fpStartBreak'] < $data['startTime']) {
								$message='Start time later than FP Start Break time';
								$error=true;
							}
						}
						if (isset($data['fpFinishBreak'])) {
			    			if ($data['fpFinishBreak'] > $data['finishTime']) {
								$message='Finish time earlier than FP Finish Break time';
								$error=true;
							}
						}
						if ($data['startBreak'] && $data['startTime'] > $data['startBreak']) {
							$message='Break time start earlier than Start time';
							$error=true;
						}
						if ($data['finishBreak'] && $data['finishBreak'] > $data['finishTime']) {
							$message='Break time finish later then Finish time';
							$error=true;
						}
						if ($data['minWorkTime'] && $data['minWorkTime']>(($data['finishTime']->getTimestamp()-$data['startTime']->getTimestamp())/60)) {
							$message='Minimum Work Time too much';
							$error=true;
						}
						
						if (!$error) {
							
							try {
				    			if ($new) {
				    				$em->persist($shift);
				    			}
				    			$em->flush($shift);
				    		} catch (\Exception $e) {
				    			if (strpos($e->getMessage(), '1062') === false) {
				    				error_log('Database error:'.$e->getMessage());
				    			} else {
				    				$session->getFlashBag()->set('notice', 'Sorry, this shift already exists');
				    			}
				    		}
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
			    				
//			    				$locations=$functions->getLocation();
			    				$session->remove('admin');
			    				$session->getFlashBag()->set('notice', 'Shift "'.$data['title'].'" '.(($new2)?('saved'):('updated')));
			    		
			    				return $this->redirect($this->generateUrl($base));
			    			}
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
	    				} elseif ($staffReqForm->has('delete') && $staffReqForm->get('delete')->isClicked()) {
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
					$levels=$functions->getQualificationLevels();
					// remove all already selected qualifications to show only which can be added
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
    				$qualReqForm=$this->createForm(new QualRequirementsType($qualReq, $qualifications, $levels, $list, $admin['param1']));
    				$qualReqForm->handleRequest($this->getRequest());
//    				$validator = $this->get('validator');
//    				$errors = $validator->validate($qualReqForm);
					if ($qualReqForm->isSubmitted() && $qualReqForm->isValid()) {
						$data=$qualReqForm->getData();
    					if ($qualReqForm->get('cancel')->isClicked()) {
    						$session->remove('admin');
    						return $this->redirect($this->generateUrl($base));
	    				} elseif ($qualReqForm->has('delete') && $qualReqForm->get('delete')->isClicked()) {
							$qIds=array();
							
								
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
	    				} else {
error_log('else');
	    					$message='Valid';
	    					$data=$qualReqForm->getData();
	    			
	    					$qualReq->setShiftId($data['shiftId']);
	    					$qualReq->setQualificationId($data['qualificationId']);
	    					$qualReq->setLevelId($data['levelId']);
	    					$qualReq->setNumberOfStaff($data['numberOfStaff']);
    						$em->persist($qualReq);
	    					$em->flush($qualReq);
	    					if ($qualReq->getId()) {
	    						$session->getFlashBag()->set('notice', 'Qualification Requirements '.(($new2)?('saved'):('updated')));
	    						return $this->redirect($this->generateUrl('timesheet_hr_shifts'));
	    					}
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
    		'title'			=> $functions->getPageTitle('Shifts')
    	));
    	
    }
    
    
    public function menuAction() {

    	$links=array();
    	$adminSubmenu=array();
    	$sysadminSubmenu=array();
    	$residentsSubmenu=array();
    	
    	$session=$this->get('session');
    	$securityContext = $this->container->get('security.context');

   		$active=$session->get('menu');
   		if ($active < 1 || $active > Constants::MENU_ITEMS) {
   			$active = 1;
   		}
    	
    	if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    		// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
    		$currentUser=$this->getUser();
    		$functions=$this->get('timesheet.hr.functions');
    		$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
			$links[]=array('url'=>$this->generateUrl('timesheet_hr_dashboard'), 'name'=>'Dashboard', 'active'=>($active == Constants::MENU_DASHBOARD));
    		if (TRUE === $securityContext->isGranted('ROLE_USER') && TRUE !== $securityContext->isGranted('ROLE_SYSADMIN')) {
    			$links[]=array('url'=>$this->generateUrl('timesheet_hr_timesheet'), 'name'=>'Timesheet', 'active'=>($active == Constants::MENU_TIMESHEET));
	    		$links[]=array('url'=>$this->generateUrl('timesheet_hr_holiday'), 'name'=>'Holiday', 'active'=>($active == Constants::MENU_HOLIDAY));
    			if ($functions->isModuleAvailable(1, $domainId)) {
	    			$residentsSubmenu[]=array('url'=>$this->generateUrl('residents_hr_list'), 'name'=>'Residents List', 'active'=>false);
	    			$links[]=array('sub'=>$residentsSubmenu, 'url'=>$this->generateUrl('residents_hr_dashboard'), 'name'=>'Residents', 'active'=>($active == Constants::MENU_RESIDENTS));
    			}
    		}
    		if (TRUE === $securityContext->isGranted('ROLE_SYSADMIN')) {
    			$sysadminSubmenu[]=array('url'=>$this->generateUrl('timesheet_hr_users', array('action'=>'clean')), 'name'=>'Users', 'active'=>false);
//    			$sysadminSubmenu[]=array('url'=>$this->generateUrl('residents_hr_list', array('action'=>'clean')), 'name'=>'Residents', 'active'=>false);
//    			$sysadminSubmenu[]=array('url'=>$this->generateUrl('timesheet_hr_reset'), 'name'=>'Reset', 'active'=>false);
    			$links[]=array('sub'=>$sysadminSubmenu, 'url'=>$this->generateUrl('timesheet_hr_sysadmin'), 'name'=>'Sysadmin', 'active'=>($active == Constants::MENU_SYSADMIN));
    		}
    		if (TRUE !== $securityContext->isGranted('ROLE_SYSADMIN') && (TRUE === $securityContext->isGranted('ROLE_ADMIN') || TRUE === $securityContext->isGranted('ROLE_MANAGER'))) {
				// only for ADMIN
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
    					$links[]=array('sub'=>$locationSubmenu, 'url'=>$this->generateUrl('timesheet_hr_schedule', array('locationId'=>0)), 'name'=>'Schedule', 'active'=>($active == Constants::MENU_SCHEDULE));
    				}
				}
    			$adminSubmenu[]=array('url'=>$this->generateUrl('timesheet_hr_users', array('action'=>'clean')), 'name'=>'Users', 'active'=>false);
    			$adminSubmenu[]=array('url'=>$this->generateUrl('timesheet_hr_locations', array('action'=>'clean')), 'name'=>'Locations', 'active'=>false);
    			if ($functions->isModuleAvailable(1, $domainId)) {
    				$adminSubmenu[]=array('url'=>$this->generateUrl('residents_hr_rooms', array('action'=>'clean')), 'name'=>'Rooms', 'active'=>false);
    			}
    			$adminSubmenu[]=array('url'=>$this->generateUrl('timesheet_hr_shifts', array('action'=>'clean')), 'name'=>'Shifts', 'active'=>false);
				$adminSubmenu[]=array('url'=>$this->generateUrl('timesheet_hr_configuration'), 'name'=>'Configuration', 'active'=>false);
				if ($functions->isModuleAvailable(2, $domainId)) {
					$adminSubmenu[]=array('url'=>$this->generateUrl('timesheet_hr_templates'), 'name'=>'Templates', 'active'=>false);
				}
				$links[]=array('sub'=>$adminSubmenu, 'url'=>$this->generateUrl('timesheet_hr_admin'), 'name'=>'Administration', 'active'=>($active == Constants::MENU_ADMIN));
			} 
    	} else {
    		$links[]=array('url'=>$this->generateUrl('timesheet_hr_homepage'), 'name'=>'Home', 'active'=>($active == Constants::MENU_HOMEPAGE));
    		$links[]=array('url'=>$this->generateUrl('fos_user_security_login'), 'name'=>'Login', 'active'=>($active== Constants::MENU_LOGIN));
    	}
    	 
    	return $this->render('TimesheetHrBundle:Default:menu.html.twig', array(
    		'links'	=> $links
    	));
    }

    
    public function sysadminAction($action, $param1, $param2) {
    	
    	if (!$this->get("security.context")->isGranted('ROLE_SYSADMIN')) {
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
    				if (!$companyForm->get('submit')->isClicked()) {
//    					$session->remove('admin');
    					return $this->redirect($this->generateUrl($base));
    				}
    				
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
    				$company->setAutologout($data['autologout']);
    				$company->setGrace($data['grace']);
    				$company->setRounding($data['rounding']);
    					
    				try {
	    				if (!$data['id']) {
	    					$em->persist($company);
	    				}
	    				$em->flush($company);
    				} catch (\Exception $e) {
    					if (strpos($e->getMessage(), '1062') === false) {
    						error_log('Database error:'.$e->getMessage());
    					} else {
    						$session->getFlashBag()->set('notice', 'Sorry, this company already exists');
    					}
    				}

    				if ($company->getId()) {
    					$msg='Company '.(($new)?('saved'):('updated'));
    					if ($new) {
    						$msg.=$functions->addNewAdmin($data['adminUsername'], $data['adminPassword'], $data['adminEmail'], $company->getId());
    					}
    					$session->getFlashBag()->set('notice', $msg);
    					return $this->redirect($this->generateUrl('timesheet_hr_sysadmin'));
    				}
    			}		 
    			break;
    		}
    		
    	    case 'newfpreader' :
    			$fpreader=new FPReaders();
    			$new=true;
    		case 'editfpreader' : {
    			if ($param1) {
    				$fpreader=$this->getDoctrine()
    					->getRepository('TimesheetHrBundle:FPReaders')
    					->findOneBy(array('id'=>$param1));
    				
    				if (!$fpreader || count($fpreader)<1) {
    					return $this->redirect($this->generateUrl('timesheet_hr_sysadmin'));
    				}
    			}
//    			$fpreaderForm=$this->createForm(new FPReaderType($fpreader, $functions->getCompanies()));
    			$fpreaderForm=$this->createForm(new FPReaderType($fpreader, $functions->getLocation(null, true, null), $functions->getCompanies()));
    			 
    			$fpreaderForm->handleRequest($this->getRequest());
    			if ($fpreaderForm->isValid()) {
    				if (!$fpreaderForm->get('submit')->isClicked()) {
    					return $this->redirect($this->generateUrl($base));
    				}
    				
    				$data=$fpreaderForm->getData();
    			
    				$fpreader->setDeviceId($data['deviceId']);
    				$fpreader->setDeviceName(''.$data['deviceName']);
    				$fpreader->setIpAddress($data['ipAddress']);
    				$fpreader->setPort($data['port']);
    				$fpreader->setStatus($data['status']);
    				$fpreader->setComment(''.$data['comment']);
    				if ($new) {
    					$fpreader->setVersion('');
    					$fpreader->setSerialnumber('');
    					$fpreader->setPlatform('');
    				}
   					$fpreader->setDomainId($data['domainId']);
   					$fpreader->setLocationId($data['locationId']);
   							
    				try {
	    				if (!$data['id']) {
	    					$em->persist($fpreader);
	    				}
	    				$em->flush($fpreader);
    				} catch (\Exception $e) {
    					if (strpos($e->getMessage(), '1062') === false) {
    						error_log('Database error:'.$e->getMessage());
    					} else {
    						$session->getFlashBag()->set('notice', 'Sorry, this reader already exists');
    					}
    				}

    				if ($fpreader->getId()) {
    					$msg='Fingerprint Reader '.(($new)?('saved'):('updated'));

    					$session->getFlashBag()->set('notice', $msg);
    					return $this->redirect($this->generateUrl('timesheet_hr_sysadmin'));
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
		    		
	    		$groupForm=$this->createForm(new GroupType($group, $functions->getCompanies()));
		    		
	    		$groupForm->handleRequest($this->getRequest());
		
	    		if ($groupForm->isValid()) {
	
	    			if (!$groupForm->get('submit')->isClicked()) {
	    				$session->remove('admin');
	    				return $this->redirect($this->generateUrl($base));
	    			}
		    			 
	    			$data=$groupForm->getData();

	    			$group->setName(''.$data['name']);
    				$group->setDomainId($data['domainId']);

    				try {
		    			if (!$param1) {
		    				$em->persist($group);
		    			}
		    			$em->flush($group);
		    		} catch (\Exception $e) {
		    			if (strpos($e->getMessage(), '1062') === false) {
		    				error_log('Database error:'.$e->getMessage());
		    			} else {
		    				$session->getFlashBag()->set('notice', 'Sorry, this group already exists');
		    			}
		    		}
		    			 
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
		    		
		    	$qualificationForm=$this->createForm(new QualificationType($qualification, $functions->getCompanies()));
		    	$qualificationForm->handleRequest($this->getRequest());
		
	    		if ($qualificationForm->isValid()) {
		
	    			if (!$qualificationForm->get('submit')->isClicked()) {
	    				$session->remove('admin');
	    				return $this->redirect($this->generateUrl($base));
	    			}
		    			 
	    			$data=$qualificationForm->getData();

	    			$qualification->setTitle(''.$data['title']);
	    			$qualification->setComments(''.$data['comments']);
	    			$qualification->setDomainId($data['domainId']);
		
	    			try {
		    			if (!$param1) {
		    				$qualification->setCreatedOn(new \DateTime('now'));
		    				$qualification->setCreatedBy($this->getUser()->getId());
		    				$em->persist($qualification);
		    			}
		    			$em->flush($qualification);
		    		} catch (\Exception $e) {
		    			if (strpos($e->getMessage(), '1062') === false) {
		    				error_log('Database error:'.$e->getMessage());
		    			} else {
		    				$session->getFlashBag()->set('notice', 'Sorry, this qualification already exists');
		    			}
		    		}
		    			 
		    				
	    			if ($qualification->getId()) {
		    				
	    				$session->remove('admin');
	    				$session->getFlashBag()->set('notice', 'Qualification "'.$qualification->getTitle().'" '.(($param1)?('updated'):('saved')));
	    		
	    				return $this->redirect($this->generateUrl($base));
	    			}
	    		}
   				break;
   			}
   			
    	    case 'editjobtitle' : {

    			if ($param1) {
	    			$jobTitle=$this->getDoctrine()
			   			->getRepository('TimesheetHrBundle:JobTitles')
			   			->findOneBy(array('id'=>$param1));
    			} else {
			   		$jobTitle=new JobTitles();
    			}

    			$jobTitleForm=$this->createForm(new JobTitleType($jobTitle, $functions->getCompanies()));
		    	$jobTitleForm->handleRequest($this->getRequest());
		    	if ($jobTitleForm->isValid()) {
		    		if (!$jobTitleForm->get('submit')->isClicked()) {
		    			$session->remove('admin');
		    			return $this->redirect($this->generateUrl($base));
		    		}

		    		$data=$jobTitleForm->getData();

		    		$jobTitle->setTitle(''.$data['title']);
		    		$jobTitle->setActive($data['status']);
	    			$jobTitle->setDomainId($data['domainId']);
		    			
	    			try {
			    		if ($new) {
			    			$em->persist($jobTitle);
			    		}
			    		$em->flush($jobTitle);
	    			} catch (\Exception $e) {
	    				if (strpos($e->getMessage(), '1062') === false) {
	    					error_log('Database error:'.$e->getMessage());
	    				} else {
	    					$session->getFlashBag()->set('notice', 'Sorry, this job title already exists');
	    				}
	    			}
		    				
		    		if ($jobTitle->getId()) {
		    				
		    			$session->remove('admin');
		    			$session->getFlashBag()->set('notice', 'Job title "'.$jobTitle->getTitle().'" '.(($param2)?('updated'):('saved')));
		    		
		    			return $this->redirect($this->generateUrl($base));
		    		}
		    	}
    			break;
    		}
   			
    	   	case 'editmodule' : {
    			if ($param1) {
    				$module=$em->getRepository('TimesheetHrBundle:Modules')
    					->findOneBy(array('id'=>$param1));
    				$selectedCompanies=$functions->getSelectedModules($param1);
    			} else {
    				return $this->redirect($this->generateUrl($base));
    			}
		    		
		    	$moduleForm=$this->createForm(new ModuleType($module, $functions->getCompanies(), $selectedCompanies));
	    		$moduleForm->handleRequest($this->getRequest());
		
	    		if ($moduleForm->isValid()) {
		
	    			if (!$moduleForm->get('submit')->isClicked()) {
	    				$session->remove('admin');
	    				return $this->redirect($this->generateUrl($base));
	    			}
		    			 
	    			$data=$moduleForm->getData();
	    			$companies=((isset($data['companies']))?($data['companies']):(array()));
	    			$em=$this->getDoctrine()->getManager();
	    			
	    			$qb=$em->createQueryBuilder();
	    			$qb->delete('TimesheetHrBundle:ModulesCompanies', 'mc')
	    				->where($qb->expr()->in('mc.moduleId', $param1));
	    			if (count($companies)) {
	    				$qb->andWhere($qb->expr()->notIn('mc.domainId', $companies));
	    			}
	    			$qb->getQuery()->execute();
	    			if (count($companies)) {
	    				$domains=$functions->getSelectedModules($param1);
	    				foreach ($companies as $c1) {
	    					if (!in_array($c1, $domains)) {
	    						try {
	    							$mc=new ModulesCompanies();
	    							$mc->setDomainId($c1);
	    							$mc->setModuleId($param1);
	    							$em->persist($mc);
		    						$em->flush();
		    					} catch (\Exception $e) {
		    						if (strpos($e->getMessage(), '1062') === false) {
		    							if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
		    								if (!$em->isOpen()) {
	    										$em = $em->create($em->getConnection(), $em->getConfiguration());
	    									}
	    									if ($em->isOpen()) {
	//												error_log('Entity manager is reopened');
		   									} else {
		   										error_log('Entity manager is closed');
		   									}
		   								} else {
		   									error_log('Database error:'.$e->getMessage());
		   								}
		   							} else {
		   								error_log('already in the database, not inserted');
		   							}
		    					}
	    					}
	    				}
	    			}
	    			
	    			$session->remove('admin');
					$session->getFlashBag()->set('notice', 'Module "'.$module->getName().'" updated');
					return $this->redirect($this->generateUrl($base));
	    			
	    		}
   				break;
   			}
    		
    		case 'editstatus' : {
    			if ($param1) {
    				$status=$functions->getStatuses($param1);
    				$selectedCompanies=$functions->getSelectedCompanies($param1);
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
    				$selectedCompanies=array();
    			}
		    		
		    	$statusForm=$this->createForm(new StatusType($status, $functions->getStatusColors(), $functions->getStatusLevels(), $functions->getCompanies(), $selectedCompanies));
	    		$statusForm->handleRequest($this->getRequest());
		
	    		if ($statusForm->isValid()) {
		
	    			if (!$statusForm->get('submit')->isClicked()) {
	    				$session->remove('admin');
	    				return $this->redirect($this->generateUrl($base));
	    			}
		    			 
	    			$data=$statusForm->getData();

	    			try {
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
					} catch (\Exception $e) {
						if (strpos($e->getMessage(), '1062') === false) {
							error_log('Database error:'.$e->getMessage());
						} else {
							$session->getFlashBag()->set('notice', 'Sorry, this status already exists');
						}
					}
						
	    			if ($status1->getId()) {
						$companies=((isset($data['companies']))?($data['companies']):(array()));
						$st=array($status1->getId(), $status2->getId());
						$em=$this->getDoctrine()->getManager();
								
						$qb=$em->createQueryBuilder();
						$qb->delete('TimesheetHrBundle:StatusToDomain', 'std')
							->where($qb->expr()->in('std.statusId', $st));
						if (count($companies)) {
							$qb->andWhere($qb->expr()->notIn('std.domainId', $companies));
						}
								
						$qb->getQuery()->execute();
						if (count($companies)) {
							foreach ($st as $st1) {
								$domains=$functions->getSelectedCompanies($st1);
								foreach ($companies as $c1) {
									if (!in_array($c1, $domains)) {
										try {
											$std=new StatusToDomain();
											$std->setDomainId($c1);
											$std->setStatusId($st1);
											$em->persist($std);
											$em->flush();
										} catch (\Exception $e) {
											if (strpos($e->getMessage(), '1062') === false) {
												if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
													if (!$em->isOpen()) {
//														error_log('Entity manager is closed');
														$em = $em->create($em->getConnection(), $em->getConfiguration());
													}
													if ($em->isOpen()) {
//														error_log('Entity manager is reopened');
													} else {
														error_log('Entity manager is closed');
													}
												} else {
													error_log('Database error:'.$e->getMessage());
												}
											} else {
error_log('already in the database, not inserted');
											}
										}
									}
								}
							}
						}
		    				
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

		    	$fpreaders=$functions->getFPReaders(null);
		    	
		    	break;
    		}
    	}
    	
    	
    	return $this->render('TimesheetHrBundle:Default:sysadmin.html.twig', array(
    		'base'			=> 'timesheet_hr_sysadmin',
    		'title'			=>'Sysadmin',
    		'config'		=> ((isset($config))?($config):(null)),
    		'sites'			=> ((isset($sites))?($sites):(null)),
    		'fpreaders'		=> ((isset($fpreaders))?($fpreaders):(null)),
    		'hct'			=> $hct,
    		'groupForm'		=> ((isset($groupForm))?($groupForm->createView()):(null)),
    		'qualificationForm'		=> ((isset($qualificationForm))?($qualificationForm->createView()):(null)),
    		'jobTitleForm'	=> ((isset($jobTitleForm))?($jobTitleForm->createView()):(null)),
    		'statusForm'	=> ((isset($statusForm))?($statusForm->createView()):(null)),
    		'moduleForm'	=> ((isset($moduleForm))?($moduleForm->createView()):(null)),
    		'companyForm'	=> ((isset($companyForm))?($companyForm->createView()):(null)),
    		'fpreaderForm'	=> ((isset($fpreaderForm))?($fpreaderForm->createView()):(null)),
    		'domainId'		=> null
    	));
    }

    
    public function autopunchAction($userId, $date) {
error_log('autopunchAction');    	
        	if (!$this->get("security.context")->isGranted('ROLE_ADMIN')) {
error_log('not allowed...redirect to homepage');
			return $this->redirect($this->generateUrl('timesheet_hr_homepage'));

    	}
    	
    	$maxDifference=30;
    	
    	$locationIp=array();
    	$userIds=array();

    	$currentDate=new \DateTime(($date)?($date):('now'));
		
    	$prevDate=clone $currentDate;
		$prevDate->modify('-1 day');
		$nextDate=clone $currentDate;
		$nextDate->modify('+1 day');
		
    	$dayId=$currentDate->format('w');
    	$em=$this->getDoctrine()->getManager();
    	$qb=$em->createQueryBuilder()
    		->select('a.userId')
    		->addSelect('s.startTime')
    		->addSelect('s.finishTime')
    		->addSelect('s.minWorkTime')
    		->addSelect('a.locationId')
    		->addSelect('l.fixedIpAddress')
    		->addSelect('l.name')
    		->addSelect('u.username')
    		->from('TimesheetHrBundle:User', 'u')
    		->join('TimesheetHrBundle:Allocation', 'a', 'WITH', 'a.userId=u.id')
    		->join('TimesheetHrBundle:Location', 'l', 'WITH', 'a.locationId=l.id')
    		->join('TimesheetHrBundle:Shifts', 's', 'WITH', 'a.shiftId=s.id')
    		->join('TimesheetHrBundle:ShiftDays', 'sd', 'WITH', 'sd.shiftId=s.id')
    		->where('u.isActive=1')
    		->andWhere('u.loginRequired=1')
    		->andWhere('a.date=:date')
    		->andWhere('sd.dayId=:day')
    		->groupBy('u.id')
    		->orderBy('u.username', 'ASC')
    		->setParameter('date', $currentDate->format('Y-m-d'))
    		->setParameter('day', $dayId);

    	if ($userId) {
    		$qb->andWhere('u.id=:uId')
    			->setParameter('uId', $userId);
    	}
    	$query=$qb->getQuery();
    	$results=$query->getArrayResult();
		$data=array();
		if ($results && count($results)) {
			foreach ($results as $r) {
				if (!isset($userIds[$r['userId']])) {
					$userIds[$r['userId']]=$r['userId'];
				}
				if ($r['fixedIpAddress']) {
					if (!isset($locationIp[$r['locationId']])) {
						$ips=$this->getDoctrine()->getRepository('TimesheetHrBundle:LocationIpAddress')
							->findOneBy(array('locationId'=>$r['locationId']));
						if ($ips && $ips->getIpAddress()) {
							$ip=$ips->getIpAddress();
						} else {
							$ip='127.0.0.1';
						}
						$locationIp[$r['locationId']]=$ip;
					} else {
						$ip=$locationIp[$r['locationId']];
					}
				} else {
					$ip=sprintf('%d.%d.%d.%d', 192, 168, rand(1,254), rand(1,254));
				}
				
				if ($r['minWorkTime']) {
					$startTime=new \DateTime($currentDate->format('Y-m-d').' '.$r['startTime']->format('H:i:s'));
					$startTime->modify(sprintf('%+d minute', rand(-60,300))); // -60 to +400 minutes from start time
					$finishTime=clone $startTime;
					$finishTime->modify('+'.$r['minWorkTime'].' minute');
				} else {
					$startTime=new \DateTime($currentDate->format('Y-m-d').' '.$r['startTime']->format('H:i:s'));
					$finishTime=new \DateTime($currentDate->format('Y-m-d').' '.$r['finishTime']->format('H:i:s'));
				}
				if ($r['startTime']->format('H:i:s') > $r['finishTime']->format('H:i:s')) {
					$finishTime->modify('+1 day');
				}
				$punchIn=clone $startTime;
				$punchOut=clone $finishTime;
				$deleteIn=clone $startTime;
				$deleteIn->modify('-'.$maxDifference.' minute');
				$deleteOut=clone $finishTime;
				$deleteOut->modify('+'.$maxDifference.' minute');
				$m1=$maxDifference/2; // 15
				$m2=$maxDifference/2; // 15
				if (rand(0,15) > 10) {
					$m1=$maxDifference; // 30
					$m2=$maxDifference/6; // 5
				}
				$pi=rand(-$m1, $m2);
				$po=rand(-$m2, $m1);
				if ($pi) {
					$punchIn->modify(sprintf('%+d minute', $pi));
				}
				if ($po) {
					$punchOut->modify(sprintf('%+d minute', $po));
				}
			
				$qb1=$em->createQueryBuilder();
				$qb1->delete('TimesheetHrBundle:Info', 'i')
					->where('i.userId=:uId')
					->andWhere('(i.statusId IN (1,2) AND i.timestamp BETWEEN :date1 AND :date2)')
					->setParameter('date1', $deleteIn->format('Y-m-d H:i:s'))
					->setParameter('date2', $deleteOut->format('Y-m-d H:i:s'));
				$qb1->setParameter('uId', $r['userId']);
				$qb1->getQuery()->execute();

				$pIn=new Info();
				$pIn->setStatusId(1);
				$pIn->setTimestamp($punchIn);
				$pIn->setUserId($r['userId']);
				$pIn->setDeleted(0);
				$pIn->setIpAddress($ip);
				$pIn->setCreatedBy($r['userId']);
				$pIn->setCreatedOn(new \DateTime());
				$pIn->setComment('');
				$em->persist($pIn);
//				$em->flush($pIn);
				
				$pOut=new Info();
				$pOut->setStatusId(2);
				$pOut->setTimestamp($punchOut);
				$pOut->setUserId($r['userId']);
				$pOut->setDeleted(0);
				$pOut->setIpAddress($ip);
				$pOut->setCreatedBy($r['userId']);
				$pOut->setCreatedOn(new \DateTime());
				$pOut->setComment('');
				$em->persist($pOut);
//				$em->flush($pOut);
				
				$data[]=array(
					'userId'=>$r['userId'],
					'username'=>$r['username'],
					'startTime'=>$startTime,
					'finishTime'=>$finishTime,
					'punchIn'=>$punchIn,
					'punchOut'=>$punchOut,
					'ip'=>$ip,
					'location'=>$r['name']
				);
			}
			$em->flush();
		}

		$qb=$em->createQueryBuilder()
			->select('i.userId')
			->addSelect('MAX(i.timestamp) as lastTime')
			->from('TimesheetHrBundle:Info', 'i')
			->where('i.userId IN (:uIds)')
			->setParameter('uIds', $userIds)
			->groupBy('i.userId');
		
		$query=$qb->getQuery();
		$results=$query->getArrayResult();

		if ($results && count($results)) {
			foreach ($results as $r) {
				$qb=$em->createQueryBuilder();
				$qb->update('TimesheetHrBundle:User', 'u')
					->set('u.lastTime', ':time')
					->set('u.lastStatus', ':status')
					->where('u.id=:uId')
					->setParameter('time', new \DateTime($r['lastTime']))
					->setParameter('status', 2)
					->setParameter('uId', $r['userId']);
				
				$query=$qb->getQuery();
				$changed=$query->execute();
			}
		}

		return $this->render('TimesheetHrBundle:Internal:autopunch.html.twig', array(
			'title'		=> 'AutoPunch',
	    	'data'		=> $data,
			'userId'	=> $userId,
			'prevDate'	=> $prevDate,
			'nextDate'	=> $nextDate
    	));
    }
    
    
    public function autologoutAction($minutes) {
    	$securityContext = $this->container->get('security.context');
    	$forcelogout=false;
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    		error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	} else {
    		// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
			if (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
				$cookies=$this->getRequest()->cookies;
				if ($minutes && (int)$minutes) {
    				$response=new Response();
    				$response->headers->setCookie(new Cookie('ts_alo', (int)$minutes, 0, '/', null, false, false));
					$response->sendHeaders();
					$forcelogout=true;
    			} else {
    				if ($cookies->has('ts_alo')) {
    					$response=new Response();
    					$response->headers->clearCookie('ts_alo');
    					$response->sendHeaders();
    					unset($cookies);
    				}
    			}
			}
		}
		if ($forcelogout) {
error_log('auto logout after:'.$cookies->get('ts_alo').' minutes');
			return $this->redirect($this->generateUrl('fos_user_security_logout'));
		}
   		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    }
    

    public function usersummaryAction() {
error_log('usersummary');

    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	} else {
    		$role='ROLE_USER';
    		if (TRUE === $securityContext->isGranted('ROLE_ADMIN')) {
    			$role='ROLE_ADMIN';
    		} elseif (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
    			$role='ROLE_MANAGER';
    		}
    	}
//    	$base=$this->getRequest()->attributes->get('_route');
    	$base=$this->generateUrl('timesheet_hr_dashboard');
    	$user=$this->getUser();

    	if ($user) {
    		
// error_log('base:'.$base);
    		
	    	$functions = $this->get('timesheet.hr.functions');
	    	$holidays=$functions->getHolidayEntitlement($user->getId());
	    	$workinghours=$functions->getWorkingHours($user->getId(), time());
	    	$userId=$user->getId();
	    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
	    	
	    	$data=array(
	    		'status'=>$functions->getCurrentStatus($userId),
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
	    		'swaprequests'=>$functions->getFutureSwapRequests($userId),
	    		'todayshift'=>$functions->getTodayShift($userId),
	    		'nextshift'=>$functions->getNextShift($userId, false),
	    		'unread'=>$functions->getNumberOfUnreadMessages($userId),
	    		'requests'=>$functions->getRequestsToAnswer($userId, $domainId, false),
	    		'role'=>$role
	    	);
	    	
	    	return $this->render('TimesheetHrBundle:Internal:usersummary.html.twig', array(
	    		'data'		=> $data,
	    		'userId'	=> $user->getId(),
	    		'base'		=> $base,
	    		'domainId'	=> $domainId
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
		
		$this->render(sprintf('TimesheetHrBundle:Export:WeeklyReport.%s.twig', 'pdf'), array(
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
    
    	$this->render('TimesheetHrBundle:Export:MonthlyReport.pdf.twig', array(
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
// error_log('Default report:'.print_r($report2, true));
				$shifts=array();
				$users=array();

				if ($report2 && count($report2)) {
					foreach ($report2 as $rep) {
						if (isset($rep['shifts'])) {
							foreach ($rep['shifts'] as $sId=>$shft) {
								if ($shft && count($shft)) {
									foreach ($shft as $s)
									$users[$s['userId']]=trim($s['userTitle'].' '.$s['firstName'].' '.$s['lastName']); // .' ('.$s['username'].')';
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
				
				$this->render('TimesheetHrBundle:Export:WeeklyLocationReportDefault.pdf.twig', array(
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
				
				$this->render('TimesheetHrBundle:Export:WeeklyLocationReport.pdf.twig', array(
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
     * @Pdf()
     */
    public function timesheetreportAction($timestamp, $userid) {
error_log('timesheetreportAction');
    
    	$role='ROLE_USER';
		$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	} else {
    		// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
    		if (TRUE === $securityContext->isGranted('ROLE_ADMIN')) {
    			$role='ROLE_ADMIN';
    		} elseif (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
    			$role='ROLE_MANAGER';
    		}
    	}
    	$functions = $this->get('timesheet.hr.functions');
    	
    	$session=$this->get('session');
    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	$currentUser=$this->getUser();
    	
    	$first=mktime(0, 0, 0, date('n', $timestamp), 1, date('Y', $timestamp));
    
    	$facade = $this->get('ps_pdf.facade');
    	$response = new Response();
//error_log('userId:'.$userid);    
    	$user=$this->getDoctrine()
    		->getRepository('TimesheetHrBundle:User')
    		->findOneBy(array('id'=>$userid));
    	if (!$user) {
    		$user=$this->getDoctrine()
    			->getRepository('TimesheetHrBundle:User')
    			->findOneBy(array('username'=>$userid));    		
    	}
//error_log('user:'.print_r($user, true));    

    	$this->render(sprintf('TimesheetHrBundle:Export:TimesheetReport.%s.twig', 'pdf'), array(
    		'name' => $user->getFullName(), // trim($user->getTitle().' '.$user->getFirstName().' '.$user->getLastName()),
    		'username' => $user->getUsername(),
    		'date'=>date('F Y', $first),
    		'report'=>$functions->getTimesheet($currentUser->getId(), $first, '', $session, $domainId, $user->getId(), $functions->getUsersForManager($this->getUser(), null, 0, $role)),
    		'footer' => 'Created on '.date('d/m/Y H:i:s'),
    	), $response);
    
    	$filename='Timesheet_'.date('F_Y', $timestamp).'.pdf';
    	$xml=$response->getContent();    
    	$content = $facade->render($xml);
    	 
    	return new Response($content, 200, array
    			('content-type' => 'application/pdf',
    					'Content-Disposition' => 'attachment; filename=' . $filename
    			));
    }
    


    public function timesheetreportsummaryAction($timestamp, $userid, $filetype) {
error_log('timesheetreportsummaryAction');
error_log('userid:'.$userid.', filetype:'.$filetype);
    	$role='ROLE_USER';
		$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	} else {
    		// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
    		if (TRUE === $securityContext->isGranted('ROLE_ADMIN')) {
    			$role='ROLE_ADMIN';
    		} elseif (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
    			$role='ROLE_MANAGER';
    		}
    	}
    	$functions = $this->get('timesheet.hr.functions');
    	$selectedUserId=$userid;
    	
    	$session=$this->get('session');
    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	$users=$functions->getUsersForManager($this->getUser(), null, 0, $role);

    	if (isset($users) && is_array($users) && count($users)==1) {
    		// if we have only 1 user, that is the selected user, show only those details, no list of users
    		$tmp=array_keys($users);
    		if (is_array($tmp)) {
    			$selectedUserId=reset($tmp);
    		}
    	}

    	$first=mktime(0, 0, 0, date('n', $timestamp), 1, date('Y', $timestamp));
    
    	$response = new Response();
    	$users2=$functions->getUsersForManager($this->getUser(), null, (($selectedUserId>0)?(0):((($selectedUserId == -1 )?(10):(0)))), $role);
		$timesheet=$functions->getTimesheet($this->getUser()->getId(), $timestamp, '', $session, $domainId, $selectedUserId, $users2);
		$summary=$functions->createTimesheetSummary($timesheet);
    	$dates=array();
    	$d=1;
    	$d1=mktime(0, 0, 0, date('m', $timestamp), $d, date('Y', $timestamp));
    	$d_last=date('t', $timestamp);
    	while ($d <= $d_last) {
    		$dates[date('Y-m-d', $d1)]=date('D j', $d1);
    		$d1=mktime(0, 0, 0, date('m', $timestamp), ++$d, date('Y', $timestamp));
    	}
    	 
    	$this->render(sprintf('TimesheetHrBundle:Export:TimesheetSummaryReport.%s.twig', $filetype), array(
    		'date'		=>date('F Y', $first),
    		'report'	=>$summary,
    		'dates'		=> $dates,
    		'users'		=> $users,
    		'gracePeriod'	=> $functions->getConfig('grace', $domainId),
    		'footer' => 'Created on '.date('d/m/Y H:i:s'),
    	), $response);

    	$filename='Summary_'.date('F_Y', $timestamp).'.'.$filetype;

    	switch ($filetype) {
    		case 'csv' : {
		    	$response->headers->set('Content-Type', 'text/csv');
		    	$response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
		    	return $response;
    			break;
    		}
    		case 'pdf' : {
    			$facade = $this->get('ps_pdf.facade');
    			$xml=$response->getContent();
		    	$content = $facade->render($xml);
		    	 
		    	return new Response($content, 200, array
		    		('content-type' => 'application/pdf',
		    			'Content-Disposition' => 'attachment; filename=' . $filename
		    		));
    			break;
    		}
    	}
    	
    }


    public function latenessreportAction($timestamp, $userid, $filetype, $holidays) {
error_log('latenessreportAction');
error_log('userid:'.$userid.', filetype:'.$filetype);
    	$role='ROLE_USER';
		$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	} else {
    		// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
    		if (TRUE === $securityContext->isGranted('ROLE_ADMIN')) {
    			$role='ROLE_ADMIN';
    		} elseif (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
    			$role='ROLE_MANAGER';
    		}
    	}
    	$functions = $this->get('timesheet.hr.functions');
    	$selectedUserId=$userid;
    	
    	$session=$this->get('session');
    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	$users=$functions->getUsersForManager($this->getUser(), 0, $role);

    	if (isset($users) && is_array($users) && count($users)==1) {
    		// if we have only 1 user, that is the selected user, show only those details, no list of users
    		$tmp=array_keys($users);
    		if (is_array($tmp)) {
    			$selectedUserId=reset($tmp);
    		}
    	}

    	$first=mktime(0, 0, 0, date('n', $timestamp), 1, date('Y', $timestamp));
    	
    	$response = new Response();
    	$users2=$functions->getUsersForManager($this->getUser(), (($selectedUserId>0)?(0):((($selectedUserId == -1 )?(10):(0)))), $role);
		$timesheet=$functions->getTimesheet($this->getUser()->getId(), $timestamp, '', $session, $domainId, $selectedUserId, $users2);
		$lateness=$functions->createLatenessReport($timesheet);
    	$dates=array();
    	$d=1;
    	$d1=mktime(0, 0, 0, date('m', $timestamp), $d, date('Y', $timestamp));
    	$d_last=date('t', $timestamp);
    	while ($d <= $d_last) {
    		$dates[date('Y-m-d', $d1)]=date('D j', $d1);
    		$d1=mktime(0, 0, 0, date('m', $timestamp), ++$d, date('Y', $timestamp));
    	}

    	$this->render(sprintf('TimesheetHrBundle:Export:LatenessReport.%s.twig', $filetype), array(
    		'date'		=>date('F Y', $first),
    		'report'	=>$lateness,
    		'dates'		=> $dates,
    		'users'		=> $users,
    		'holidays'	=> (isset($holidays) && $holidays),
    		'gracePeriod'	=> $functions->getConfig('grace', $domainId),
    		'footer' => 'Created on '.date('d/m/Y H:i:s'),
    	), $response);

    	$filename='Lateness_'.date('F_Y', $timestamp).'.'.$filetype;

    	switch ($filetype) {
    		case 'csv' : {
		    	$response->headers->set('Content-Type', 'text/csv');
		    	$response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
		    	return $response;
    			break;
    		}
    		case 'pdf' : {
    			$facade = $this->get('ps_pdf.facade');
		    	$xml=$response->getContent();
		    	$content = $facade->render($xml);
		    	 
		    	return new Response($content, 200, array
		    		('content-type' => 'application/pdf',
		    			'Content-Disposition' => 'attachment; filename=' . $filename
		    		));
    			break;
    		}
    	}
    	
    }

    
    public function sageimportAction() {
 error_log('sageimportAction');
 		$message='';
    	$role='ROLE_USER';
    	$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
    		error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	} else {
    		// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
    		if (TRUE === $securityContext->isGranted('ROLE_ADMIN')) {
    			$role='ROLE_ADMIN';
    		} elseif (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
    			$role='ROLE_MANAGER';
    		}
    	}
    	if ($role=='ROLE_USER') {
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	
    	$session=$this->get('session');
    	$functions = $this->get('timesheet.hr.functions');
	
    	$user=$this->getUser();
    	$importForm=$this->createForm(new SageImportType($user->getId()));
    	$importForm->handleRequest($this->getRequest());
    	
    	if ($importForm->isSubmitted() && $importForm->isValid()) {
    		$data=$importForm->getData();
    		
error_log('data:'.print_r($data, true));

			$filepath=$data['file']->getPathname();
			
			// error_log('data:'.print_r($data, true));
				
			$filetype=$data['file']->getMimeType();
error_log('file type:'.$filetype);
			$filesize=$data['file']->getClientSize();
error_log('file size:'.$filesize);
			if ($filesize>2024*1024) {
				$session->getFlashBag()->set('notice', 'Please upload smallest file. The limit is 2 MB');
				return $this->redirect($this->generateUrl('timesheet_hr_sageimport'));
			}
			
			if (false === array_search($filetype, array('application/xml'))) {
				$session->getFlashBag()->set('notice', 'Wrong file type');
				return $this->redirect($this->generateUrl('timesheet_hr_sageimport'));
			} else {
				$filetype=str_replace('application/', '', $filetype);
			}
			
			if (file_exists($filepath) && is_readable($filepath)) {
					
					
				$filedata=file_get_contents($filepath);
			
				unlink($filepath);
error_log('file uploaded');
				$error=false;
				try {
					libxml_use_internal_errors(true);
					$xml=simplexml_load_string($filedata);
					if ($xml === false) {
						$error=true;
						foreach (libxml_get_errors() as $err) {
							if (strlen($message)) {
								$message.=', ';
							}
							$message.=$err->message;
						}	
					}
// error_log('XML content:'.print_r($xml, true));
				} catch (Exception $e) {
					$error=true;
					error_log('Cannot recognise XML file');
				}
				
				if (!$error) {
					$message=$functions->importSageXML($xml);
					unset($importForm);
				}
			}
    	}	 
    	
	    return $this->render('TimesheetHrBundle:Default:sageimport.html.twig', array(
	    	'title'			=> 'Import Sage XML file',
	    	'importForm'	=> ((isset($importForm))?($importForm->createView()):('')),
	    	'message'		=> $message
	    ));
    }
    
    
    public function sageexportAction($filetype) {
error_log('sageexportAction');
error_log('filetype:'.$filetype);
    	$role='ROLE_USER';
		$securityContext = $this->container->get('security.context');
    	if (!$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
error_log('not allowed...redirect to homepage');
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	} else {
    		// authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
    		if (TRUE === $securityContext->isGranted('ROLE_ADMIN')) {
    			$role='ROLE_ADMIN';
    		} elseif (TRUE === $securityContext->isGranted('ROLE_MANAGER')) {
    			$role='ROLE_MANAGER';
    		}
    	}
    	if ($role=='ROLE_USER') {
    		return $this->redirect($this->generateUrl('timesheet_hr_homepage'));
    	}
    	
//    	$sysadmin=(TRUE === $securityContext->isGranted('ROLE_SYSADMIN'));
    	
    	$functions = $this->get('timesheet.hr.functions');
    	
//    	$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
    	
    	$session=$this->get('session');
    	if ($session->get('userSearch')) {
    		$userSearch=$session->get('userSearch');
    		// error_log('user search loaded : '.$userSearch);
    	} else {
    		$userSearch='';
    	}
    	
//    	if ($sysadmin) {
//    		$domainSearch=$functions->getCompanies();
//    	}
//    	$users=$functions->getUsersList(null, ((strlen($userSearch))?($userSearch):(null)), false, null, null, null, true, $domainId);
    	 
    	$users=$functions->getUsersForManager($this->getUser(), ((strlen($userSearch))?($userSearch):(null)), 0, $role);

    	$response = new Response();
		$sage=$functions->createSageReport($users);
// error_log('sage:'.print_r($sage, true));
    	$this->render(sprintf('TimesheetHrBundle:Export:SageReport.%s.twig', $filetype), array(
    		'date'		=> date('d/m/Y'),
    		'report'	=> $sage,
    		'users'		=> $users,
    		'footer'	=> 'Created on '.date('d/m/Y H:i:s'),
    	), $response);

    	$filename='sage_export_'.date('dmY').'.'.$filetype;

    	switch ($filetype) {
    		case 'csv' : {
		    	$response->headers->set('Content-Type', 'text/csv');
		    	$response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
    			break;
    		}
    	}
    	return $response;
    	
    }

/*
 * Private functions
 * - usort functions
 * - align data
 * - get data
 */

    
    
    private function getStatus($statusId=null, $nameOnly=true, $domainId=null) {
    	/*
    	 * read the status table by name
    	 */
		$em=$this->getDoctrine()->getManager();
		$qb=$em
			->createQueryBuilder()
			->select('s.id')
			->addSelect('s.name')
			->from('TimesheetHrBundle:Status', 's')
			->where('s.id>0')
			->orderBy('s.start', 'DESC')
			->addOrderBy('s.pair', 'ASC');
		
		if ($statusId) {
			$qb->andWhere('s.id=:sId')
				->setParameter('sId', $statusId);
		} else {
			$qb->andWhere('s.active=1');
		}
		if ($domainId) {
			$qb->join('TimesheetHrBundle:StatusToDomain', 'std', 'WITH', 's.id=std.statusId')
				->andWhere('std.domainId=:dId')
				->setParameter('dId', $domainId);
		}
		if (!$nameOnly) {
			$qb->addSelect('s.level')
				->addSelect('s.start')
				->addSelect('s.pair');
		
		}
		$query=$qb->getQuery();
		$results=$query->useResultCache(true)->getArrayResult();

		$ret=array();
    	if ($results) {
    		foreach ($results as $result) {
    			if ($nameOnly) {
    				$ret[$result['id']]=$result['name'];
    			} else {
    				$ret=array(
    					'id'=>$result['id'],
    					'name'=>$result['name'],
    					'level'=>$result['level'],
    					'start'=>$result['start'],
    					'pair'=>$result['pair']
    				);
    			}
    		}
    	}
    
    	return $ret;
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
    
    
    public function updateattendanceAction() {

    	$data=array();
    	$functions = $this->get('timesheet.hr.functions');
		$data=$functions->updateAttendanceFromReader();

		return $this->render('TimesheetHrBundle:Internal:updateattendance.html.twig', array(
			'title'		=> 'Update Attendance Records',
	    	'data'		=> $data
    	));
    }
        
    
}
