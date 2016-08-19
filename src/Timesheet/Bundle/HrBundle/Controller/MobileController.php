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
use Timesheet\Bundle\HrBundle\Form\Type\IdentifyType;
use Timesheet\Bundle\HrBundle\Form\Type\IdentifyConfirmType;
use Timesheet\Bundle\HrBundle\Form\Type\MobilePunchType;
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
use Timesheet\Bundle\HrBundle\Entity\MobileAuth;
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
use Detection\MobileDetect;


class MobileController extends Controller
{
	public function indexAction() {
error_log('mobileAction');
		$functions=$this->get('timesheet.hr.functions');
		return $this->render('TimesheetHrBundle:Mobile:index.html.twig', array(
			'title'		=> $functions->getPageTitle('Mobile Home')
		));
	}

	public function manifestAction() {
error_log('manifestAction');
		$functions=$this->get('timesheet.hr.functions');
		return $this->render('TimesheetHrBundle:Mobile:manifest.json.twig', array(
			'title'			=> $functions->getPageTitle('Mobile'),
			'description'	=> 'Punch in/out onto Timesheet'
		));
	}
	
	public function unauthAction() {
error_log('unauthAction');
 		$functions=$this->get('timesheet.hr.functions');
 		$domainId=$functions->getDomainId($this->getRequest()->getHttpHost());
 		
 		if (isset($_COOKIE['thr'.$domainId])) {
 			$tmp=$_COOKIE['thr'.$domainId];
error_log('decrypted cookie:'.$tmp.', pos:'.((strpos($tmp, '|')==false)?'false':strpos($tmp, '|')));
 			if (strpos($tmp, '|') != false) {
	 			$uId=base64_decode(substr($tmp, 0, strpos($tmp, '|')));
	 			$deviceId=substr($tmp, strpos($tmp, '|')+1);
error_log('userId:'.$uId.', deviceId:'.$deviceId);					
				unset($_COOKIE['thr'.$domainId]);
				setcookie('thr'.$domainId, '', 1, '/');
					
				$results=$this->getDoctrine()
					->getRepository('TimesheetHrBundle:MobileAuth')
					->findBy(array('userId'=>$uId));
//					->findBy(array('deviceId'=>$deviceId, 'userId'=>$uId));
					
				if ($results && count($results)) {
					$em=$this->getDoctrine()->getManager();
						
					foreach ($results as $result) {
error_log('remove:'.print_r($result, true));
						$em->remove($result);
						$em->flush();
					}
				}
 			} else {
error_log('wrong cookie format');
				unset($_COOKIE['thr'.$domainId]);
				setcookie('thr'.$domainId, '', 1, '/');
 			}
 		} else {
error_log('no cookie defined');
 		}
		
		return $this->redirect($this->generateUrl('timesheet_mobile_punch'));
	}
	
	public function punchAction($auth) {
error_log('mobilepunchAction');
 		$request=$this->getRequest();
 		$userManager = $this->container->get('fos_user.user_manager');
 		$functions=$this->get('timesheet.hr.functions');
 		$domainId=$functions->getDomainId($request->getHttpHost());
 		
 		$user=$this->getUser();
 		$message='';
 		$result='';
 		$repeat=true;
 		if ($user) {
error_log('logged in as '.$user->getUsername().', cannot use mobile punch');
 			$message='Logged in as '.$user->getUsername().', cannot use mobile punch';
 		} else {
 			$em=$this->getDoctrine()->getManager();
 			$detect=new \Mobile_Detect();
 			$tmp1=sprintf('%s%s%s%s%s%s%s', $detect->isMobile()?'M':'N', $detect->isTablet()?'T':'N', $detect->isIphone()?'I':'N', $detect->isAndroidOS()?'A':'N', $detect->isiOS()?'I':'N', $detect->mobileGrade(), $this->getOS());
 			$deviceId=base64_encode($tmp1);
error_log('deviceId:'.print_r($deviceId, true));
 			
 			if (isset($_COOKIE['thr'.$domainId]) && strpos($_COOKIE['thr'.$domainId], '|')!=false && substr($_COOKIE['thr'.$domainId], strpos($_COOKIE['thr'.$domainId], '|')+1) == $deviceId) {
error_log('authenticated');
				$message='Your current status in unknown';
				$userId=base64_decode(substr($_COOKIE['thr'.$domainId], 0, strpos($_COOKIE['thr'.$domainId], '|')));
error_log('userId:'.$userId);				

				$qb=$em
					->createQueryBuilder()
					->select('s.name, s.id')
					->from('TimesheetHrBundle:Status', 's')
					->join('TimesheetHrBundle:Info', 'i', 'WITH', 's.id=i.statusId')
					->where('i.userId=:uId')
					->orderBy('i.timestamp', 'DESC')
					->setMaxResults(1)
					->setParameter('uId', $userId);
				
				$query=$qb->getQuery();
				$results=$query->useResultCache(true)->getArrayResult();
				
				$tmp=array(1);
				$available=array();
				
				if ($results && count($results)) {
					$status=reset($results);
					$currentStatus=$status['id'];
 					$message='Your current status is '.$status['name'];
 					
 					switch ($currentStatus) {
 						case 1 : {
 							// Signed in
 							$tmp=array(3,5,2);
 							break;
 						}
 						case 2 : {
 							// Signed out
 							$tmp=array(1);
 							break;
 						}
 						case 3 : {
 							// Break started
 							$tmp=array(4);
 							break;
 						}
 						case 4 : {
 							// Break finished
 							$tmp=array(5,3,2);
 							break;
 						}
						case 5 : {
 							// Lunch started
							$tmp=array(6);
 							break;
 						}
 						case 6 : {
 							// Lunch finished
 							$tmp=array(3,2);
 							break;
 						}
 					}
				}
 				if ($tmp && count($tmp)) {
					$qb=$em
						->createQueryBuilder()
						->select('s.name, s.id, s.color')
						->from('TimesheetHrBundle:Status', 's')
						->where('s.id IN (:ids)')
						->setParameter('ids', $tmp);
					
					$query=$qb->getQuery();
					$results=$query->useResultCache(true)->getArrayResult();
					if ($results && count($results)) {
						foreach ($results as $result) {
							$available[$result['id']]=array('name'=>$result['name'], 'color'=>$result['color']);
						}
					}
 				}

				$result=''; // 'Available statuses:'.print_r($available, true);
					
				$mobilePunchForm=$this->createForm(new MobilePunchType($available));
				$mobilePunchForm->handleRequest($request);
				if ($mobilePunchForm->isSubmitted() && $mobilePunchForm->isValid()) {
error_log('mobilePunch form submitted');
					$selectedStatus=null;
					$data=$mobilePunchForm->getData();
					$comment=''.$data['comment'];
					$ref=array('latitude'=>$data['latitude'], 'longitude'=>$data['longitude']);
					foreach (array_keys($available) as $k) {
						if ($mobilePunchForm->get('status_'.$k)->isClicked()) {
							$selectedStatus=$k;
						}
					}
error_log('selected:'.print_r($available[$selectedStatus], true));
					if ($selectedStatus != null) {
						$ret=$functions->savePunchStatus($userId, $selectedStatus, $comment, $ref);
						if ($ret == '') {
							$message='Your status has changed to '.$available[$selectedStatus]['name'];
							$available=null;
							$result='<a href="'.$this->generateUrl('timesheet_mobile_punch').'">Refresh</a>';
							unset($mobilePunchForm);
						} else {
							$message='Could not change status';
						}
					}
				}
 			} elseif (isset($_COOKIE['thr'.$domainId]) && $_COOKIE['thr'.$domainId] != $deviceId) {
error_log('no cookie or different device id, need to de-authorise');
				if (isset($_COOKIE['thr'.$domainId])) {
					unset($_COOKIE['thr'.$domainId]);
					setcookie('thr'.$domainId, '', 1, '/');
				}
				return $this->redirect($this->generateUrl('timesheet_mobile_punch', array('auth'=>$auth)));

 			} else {
error_log('not yet authorised');
 				if ($auth) {
error_log('auth code received');
 					$ma=$this->getDoctrine()
 						->getRepository('TimesheetHrBundle:MobileAuth')
 						->findOneBy(array('id'=>base64_decode($auth)));
 					
 					if ($ma) {
	 					$identifyConfirmForm=$this->createForm(new IdentifyConfirmType($ma));
	 					$identifyConfirmForm->handleRequest($request);
						if ($identifyConfirmForm->isSubmitted() && $identifyConfirmForm->isValid()) {
error_log('2nd form submitted');
							$data=$identifyConfirmForm->getData();
							if ($data['deviceId'] == $ma->getDeviceId()) {
error_log('device id match');
			 					if (isset($data['code']) && strlen($data['code'])) {
error_log('code entered');
			 						$ma->setCompletedOn(new \DateTime());
			 						$em->flush($ma);
			 						error_log('codes: '.$ma->getCode().' == '.$data['code']);
			 						if ($ma->getCode() == $data['code']) {
error_log('AUTHENTICATED');
			 							$message='Your device is now authenticated.';
			 							setcookie('thr'.$domainId, base64_encode($ma->getUserId()).'|'.$deviceId, 0, '/');
			 							
			 							return $this->redirect($this->generateUrl('timesheet_mobile_punch'));
			 						} else {
error_log('wrong code entered');
			 							$message='Wrong code entered';
			 						}
			 					} else {
error_log('no code entered');
			 						$message='No code entered';
			 					}
							} else {
error_log('wrong device id');
								$message='Wrong device ID';
							}
						} else {
error_log('not submitted, show the 2nd form');
							$repeat=false;
						}
 					} else {
error_log('no data found');
						$message='No data found';
 					}
 					if ($repeat) {
 						return $this->redirect($this->generateUrl('timesheet_mobile_punch', array('auth'=>$auth)));
 					}
 					
 				} else {
	 				$message='Please identify your device and yourself by enter username and password. Then we will send you a unique code to your registered e-mail address, which needs to enter on the next page.';
	 			
		 			$identifyForm=$this->createForm(new IdentifyType('init'));
					$identifyForm->handleRequest($request);
					if ($identifyForm->isSubmitted() && $identifyForm->isValid()) {
					
						$data=$identifyForm->getData();
error_log('identifyForm data:'.print_r($data, true));
						if (isset($data['uname']) && isset($data['upass']) && strlen($data['uname']) && strlen($data['upass'])) {
error_log('uname and upass filled up');
							$user=$userManager->findUserBy(array('username'=>$data['uname'], 'domainId'=>$domainId));
							if ($user && $user->getDomainId() == $functions->getDomainId($request->getHttpHost())) {
								$encoder_service = $this->get('security.encoder_factory');
								$encoder = $encoder_service->getEncoder($user);
						
								if ($encoder->isPasswordValid($user->getPassword(), trim($data['upass']), $user->getSalt())) {
error_log('password ok');
									$result='Username and password are accepted';
									$fullname=trim($user->getFullname());
									$email=$user->getEmail();
							
error_log('Full name:'.$fullname.', e-mail:'.$email.', deviceId:'.$deviceId);
								
									$ma=$this->getDoctrine()
										->getRepository('TimesheetHrBundle:MobileAuth')
										->findOneBy(array('userId'=>$user->getId()));
		
									$new=false;
									if (!$ma) {
										$new=true;
										$ma=new MobileAuth();
										
										$ma->setCreatedOn(new \DateTime());
										$ma->setUserId($user->getId());
										$ma->setCode(rand(1000000000, 9999999999));
										$ma->setDeviceId($deviceId);
error_log('create new code:'.$ma->getCode());
									}
		
									if ($new) {
										$em->persist($ma);
										
										$companyname=$functions->getConfig('companyname', $domainId);
										$email=\Swift_Message::newInstance()
											->setSubject('Activation code')
											->setFrom('info@skillfill.co.uk', $companyname)
											->setTo($user->getEmail(), $fullname)
											->setContentType('text/html')
											->setBody($this->renderView('TimesheetHrBundle:Emails:activationcode.html.twig',
												array(
													'name'=>$fullname,
													'companyname'=>$companyname,
													'code'=>$ma->getCode()
												),
												'text/html'));
										
										$sent=$this->get('mailer')->send($email);
										
										if ($sent) {
											error_log('E-mail sent to '.$user->getEmail());
										} else {
											error_log('E-mail not sent');
										}
										
									}
									$em->flush($ma);
									$message='We have sent a message to your registered e-mail address with the activation code. Please check your e-mail and enter the code below.';
error_log('ready to redirect, ID:'.$ma->getId());
									return $this->redirect($this->generateUrl('timesheet_mobile_punch', array('auth'=>base64_encode($ma->getId()))));
								
								} else {
error_log('wrong password');
									$result='Wrong password!';
								}
							
							} else {
								$result='Wrong username';
							}
						}
					}
				}
			}
 		}
 		
 		
		return $this->render('TimesheetHrBundle:Mobile:punch.html.twig', array(
			'title'			=> $functions->getPageTitle('Punch'),
			'domainId'		=> $domainId,
			'message'		=> $message,
			'result'		=> $result,
			'identifyForm'	=> ((isset($identifyForm))?($identifyForm->createView()):(((isset($identifyConfirmForm))?($identifyConfirmForm->createView()):(null)))),
			'punchForm'		=> ((isset($mobilePunchForm))?($mobilePunchForm->createView()):(null))
		));
	}
	

	function getOS(){
		if ( isset( $_SERVER ) ) {
			$agent = $_SERVER['HTTP_USER_AGENT'] ;
		}
		else {
			global $HTTP_SERVER_VARS ;
			if ( isset( $HTTP_SERVER_VARS ) ) {
				$agent = $HTTP_SERVER_VARS['HTTP_USER_AGENT'] ;
			}
			else {
				global $HTTP_USER_AGENT ;
				$agent = $HTTP_USER_AGENT ;
			}
		}
		$ros=array();
		$name=array();
		
		$ros[] = array('Windows XP', 'Windows XP');
		$ros[] = array('Windows NT 5.1|Windows NT5.1', 'Windows XP');
		$ros[] = array('Windows 2000', 'Windows 2000');
		$ros[] = array('Windows NT 5.0', 'Windows 2000');
		$ros[] = array('Windows NT 4.0|WinNT4.0', 'Windows NT');
		$ros[] = array('Windows NT 5.2', 'Windows Server 2003');
		$ros[] = array('Windows NT 6.0', 'Windows Vista');
		$ros[] = array('Windows NT 7.0', 'Windows 7');
		$ros[] = array('Windows CE', 'Windows CE');
		$ros[] = array('(media center pc).([0-9]{1,2}\.[0-9]{1,2})', 'Windows Media Center');
		$ros[] = array('(win)([0-9]{1,2}\.[0-9x]{1,2})', 'Windows');
		$ros[] = array('(win)([0-9]{2})', 'Windows');
		$ros[] = array('(windows)([0-9x]{2})', 'Windows');
		// Doesn't seem like these are necessary...not totally sure though..
		// $ros[] = array('(winnt)([0-9]{1,2}\.[0-9]{1,2}){0,1}', 'Windows NT');
		// $ros[] = array('(windows nt)(([0-9]{1,2}\.[0-9]{1,2}){0,1})', 'Windows NT'); // fix by bg
		$ros[] = array('Windows ME', 'Windows ME');
		$ros[] = array('Win 9x 4.90', 'Windows ME');
		$ros[] = array('Windows 98|Win98', 'Windows 98');
		$ros[] = array('Windows 95', 'Windows 95');
		$ros[] = array('(windows)([0-9]{1,2}\.[0-9]{1,2})', 'Windows');
		$ros[] = array('win32', 'Windows');
		$ros[] = array('(java)([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,2})', 'Java');
		$ros[] = array('(Solaris)([0-9]{1,2}\.[0-9x]{1,2}){0,1}', 'Solaris');
		$ros[] = array('dos x86', 'DOS');
		$ros[] = array('unix', 'Unix');
		$ros[] = array('Mac OS X', 'Mac OS X');
		$ros[] = array('Mac_PowerPC', 'Macintosh PowerPC');
		$ros[] = array('(mac|Macintosh)', 'Mac OS');
		$ros[] = array('(sunos)([0-9]{1,2}\.[0-9]{1,2}){0,1}', 'SunOS');
		$ros[] = array('(beos)([0-9]{1,2}\.[0-9]{1,2}){0,1}', 'BeOS');
		$ros[] = array('(risc os)([0-9]{1,2}\.[0-9]{1,2})', 'RISC OS');
		$ros[] = array('os\/2', 'OS/2');
		$ros[] = array('freebsd', 'FreeBSD');
		$ros[] = array('openbsd', 'OpenBSD');
		$ros[] = array('netbsd', 'NetBSD');
		$ros[] = array('irix', 'IRIX');
		$ros[] = array('plan9', 'Plan9');
		$ros[] = array('osf', 'OSF');
		$ros[] = array('aix', 'AIX');
		$ros[] = array('GNU Hurd', 'GNU Hurd');
		$ros[] = array('(fedora)', 'Linux - Fedora');
		$ros[] = array('(kubuntu)', 'Linux - Kubuntu');
		$ros[] = array('(ubuntu)', 'Linux - Ubuntu');
		$ros[] = array('(debian)', 'Linux - Debian');
		$ros[] = array('(CentOS)', 'Linux - CentOS');
		$ros[] = array('(Mandriva).([0-9]{1,3}(\.[0-9]{1,3})?(\.[0-9]{1,3})?)', 'Linux - Mandriva');
		$ros[] = array('(SUSE).([0-9]{1,3}(\.[0-9]{1,3})?(\.[0-9]{1,3})?)', 'Linux - SUSE');
		$ros[] = array('(Dropline)', 'Linux - Slackware (Dropline GNOME)');
		$ros[] = array('(ASPLinux)', 'Linux - ASPLinux');
		$ros[] = array('(Red Hat)', 'Linux - Red Hat');
		// Loads of Linux machines will be detected as unix.
		// Actually, all of the linux machines I've checked have the 'X11' in the User Agent.
		// $ros[] = array('X11', 'Unix');
		$ros[] = array('(linux)', 'Linux');
		$ros[] = array('(amigaos)([0-9]{1,2}\.[0-9]{1,2})', 'AmigaOS');
		$ros[] = array('amiga-aweb', 'AmigaOS');
		$ros[] = array('amiga', 'Amiga');
		$ros[] = array('AvantGo', 'PalmOS');
		// $ros[] = array('(Linux)([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3}(rel\.[0-9]{1,2}){0,1}-([0-9]{1,2}) i([0-9]{1})86){1}', 'Linux');
		// $ros[] = array('(Linux)([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3}(rel\.[0-9]{1,2}){0,1} i([0-9]{1}86)){1}', 'Linux');
		// $ros[] = array('(Linux)([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3}(rel\.[0-9]{1,2}){0,1})', 'Linux');
		$ros[] = array('[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3})', 'Linux');
		$ros[] = array('(webtv)/([0-9]{1,2}\.[0-9]{1,2})', 'WebTV');
		$ros[] = array('Dreamcast', 'Dreamcast OS');
		$ros[] = array('GetRight', 'Windows');
		$ros[] = array('go!zilla', 'Windows');
		$ros[] = array('gozilla', 'Windows');
		$ros[] = array('gulliver', 'Windows');
		$ros[] = array('ia archiver', 'Windows');
		$ros[] = array('NetPositive', 'Windows');
		$ros[] = array('mass downloader', 'Windows');
		$ros[] = array('microsoft', 'Windows');
		$ros[] = array('offline explorer', 'Windows');
		$ros[] = array('teleport', 'Windows');
		$ros[] = array('web downloader', 'Windows');
		$ros[] = array('webcapture', 'Windows');
		$ros[] = array('webcollage', 'Windows');
		$ros[] = array('webcopier', 'Windows');
		$ros[] = array('webstripper', 'Windows');
		$ros[] = array('webzip', 'Windows');
		$ros[] = array('wget', 'Windows');
		$ros[] = array('Java', 'Unknown');
		$ros[] = array('flashget', 'Windows');
		// delete next line if the script show not the right OS
		// $ros[] = array('(PHP)/([0-9]{1,2}.[0-9]{1,2})', 'PHP');
		$ros[] = array('MS FrontPage', 'Windows');
		$ros[] = array('(msproxy)/([0-9]{1,2}.[0-9]{1,2})', 'Windows');
		$ros[] = array('(msie)([0-9]{1,2}.[0-9]{1,2})', 'Windows');
		$ros[] = array('libwww-perl', 'Unix');
		$ros[] = array('UP.Browser', 'Windows CE');
		$ros[] = array('NetAnts', 'Windows');
		$file = count($ros);
		$os = '';
		
		for ($n=0; $n<$file; $n++ ){
			if ( preg_match('/'.$ros[$n][0].'/i' , $agent, $name)){
				$os = @$ros[$n][1].' '.@$name[2];
				break;
			}
		}
		return trim($os);
	}
	
}
