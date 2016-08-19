<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/RegisterType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Doctrine\ORM\EntityRepository;
use Timesheet\Bundle\HrBundle\Entity\Status;
use Doctrine\ORM\Mapping\Entity;

class RegisterType extends AbstractType
{
	
	private $grouplist;
	private $locationlist;
	private $user;
	private $new;
	private $roles;
	private $titles;
	private $domains;
	private $ethnics;
	
	public function __construct($grouplist, $locationlist, $roles, $titles, $ethnics, $maritalStatuses, $user = null, $new = true, $fpReader = false, $domains = null)
	{
		$this->grouplist = $grouplist;
		$this->locationlist = $locationlist;
		$this->user = $user;
		$this->new = $new; // if new, password is required and can enrol user in fp reader automatically
		$this->roles = $roles;
		$this->titles = $titles;
		$this->domains = $domains;
		$this->ethnics = $ethnics;
		$this->maritalStatuses = $maritalStatuses;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        	->add('notes', 'textarea', array(
    			'label'=>'Notes:',
    			'required'=>false,
        		'attr'=>array(
        			'rows'=>3,
        			'cols'=>70
        		),
    			'data'=>((isset($this->user))?($this->user->getNotes()):(''))
    		))
        	->add('title', 'choice', array(
        		'choices'=>$this->titles,
    			'label'=>'Title:',
       			'required'=>false,
    			'data'=>((isset($this->user))?($this->user->getTitle()):(''))
    		))
        	->add('firstName', 'text', array(
    			'label'=>'First Name:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>100))
    			),
    			'data'=>((isset($this->user))?($this->user->getFirstName()):(''))
    		))
    		->add('lastName', 'text', array(
    			'label'=>'Last Name:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>100))
    			),
    			'data'=>((isset($this->user))?($this->user->getLastName()):(''))
    		))
    		->add('email', 'email', array(
    			'label'=>'E-mail:',
    			'required'=>false,
    			'constraints'=>array(
   					new NotBlank(),
    				new Email(),
   					new Length(array('min'=>5, 'max'=>100))
    			),
    			'data'=>((isset($this->user))?($this->user->getEmail()):(''))
    		))
    		->add('phoneLandline', 'text', array(
    			'label'=>'Phone Landline:',
    			'required'=>false,
    			'data'=>((isset($this->user))?($this->user->getPhoneLandline()):(''))
    		))
    		->add('phoneMobile', 'text', array(
    			'label'=>'Phone Mobile:',
    			'required'=>false,
    			'data'=>((isset($this->user))?($this->user->getPhoneMobile()):(''))
    		))
    		->add('nokName', 'text', array(
    			'label'=>'Next of kin:',
    			'required'=>false,
    			'data'=>((isset($this->user))?($this->user->getNokName()):(''))
    		))
    		->add('nokRelation', 'text', array(
    			'label'=>'Next of kin Relation:',
    			'required'=>false,
    			'data'=>((isset($this->user))?($this->user->getNokRelation()):(''))
    		))
    		->add('nokPhone', 'text', array(
    			'label'=>'Next of kin Phone:',
    			'required'=>false,
    			'data'=>((isset($this->user))?($this->user->getNokPhone()):(''))
    		))
    		->add('birthday', 'birthday', array(
    			'widget'=>'single_text',
    			'label'=>'Date of Birth:',
    			'required'=>false,
    			'format'=>'dd/MM/yyyy',
    			'attr'=>array(
    				'class'=>'dateInput'
    			),
    			'data'=>((isset($this->user))?($this->user->getBirthday()):(null))
    		))
    		->add('nationality', 'country', array(
    			'label'=>'Nationality:',
    			'preferred_choices'=>array('GB'),
    			'required'=>true,
    			'data'=>((isset($this->user))?($this->user->getNationality()):('')),
//    			'empty_value'=>' - Please select - ',
    		))
    		->add('ethnic', 'choice', array(
    			'choices'=>$this->ethnics,
    			'label'=>'Ethnic:',
    			'required'=>false,
    			'data'=>((isset($this->user))?($this->user->getEthnic()):('')),
    			'empty_value'=>'Not disclosed',
    		))
    		->add('maritalStatus', 'choice', array(
    			'choices'=>$this->maritalStatuses,
    			'label'=>'Marital Status:',
    			'required'=>true,
    			'data'=>((isset($this->user))?($this->user->getMaritalStatus()):('')),
//    			'empty_value'=>' - Please select - ',
    		))
    		->add('addressLine1', 'text', array(
    			'label'=>'Address:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>100))
    			),
    			'data'=>((isset($this->user))?($this->user->getAddressLine1()):(''))
    		))
    		->add('addressLine2', 'text', array(
    			'label'=>' ',
    			'required'=>false,
    			'data'=>((isset($this->user))?($this->user->getAddressLine2()):(''))
    		))
    		->add('addressCity', 'text', array(
    			'label'=>'City/Town:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>100))
    			),
    			'data'=>((isset($this->user))?($this->user->getAddressCity()):(''))
    		))
    		->add('addressCounty', 'text', array(
    			'label'=>'County:',
    			'required'=>false,
    			'data'=>((isset($this->user))?($this->user->getAddressCounty()):(''))
    		))
    		->add('addressCountry', 'country', array(
    			'label'=>'Country:',
				'preferred_choices'=>array('GB'),
  				'required'=>true,
    			'data'=>((isset($this->user))?($this->user->getAddressCountry()):('')),
    		))
    		->add('loginRequired', 'choice', array(
    			'choices'=>array(0=>'No', 1=>'Yes'),
    			'label'=>'Login Required:',
    			'required'=>true,
    			'data'=>((isset($this->user))?($this->user->getLoginRequired()):(''))
    		))
    		->add('isActive', 'choice', array(
    			'choices'=>array(0=>'No', 1=>'Yes'),
    			'label'=>'Active:',
    			'required'=>true,
    			'data'=>((isset($this->user))?($this->user->getIsActive()):(''))
    		))
    		->add('addressPostcode', 'text', array(
    			'label'=>'Postcode:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>10))
    			),
    			'data'=>((isset($this->user))?($this->user->getAddressPostcode()):(''))
    		))
    		->add('payrolCode', 'text', array(
    			'label'=>'Payrol Code:',
    			'required'=>false,
    			'data'=>((isset($this->user))?($this->user->getPayrolCode()):(''))
    		))
    		->add('ni', 'text', array(
    			'label'=>'National Insurance:',
    			'required'=>false,
    			'data'=>((isset($this->user))?($this->user->getNI()):(''))
    		))
    		->add('group', 'choice', array(
    			'choices'=>array('0'=>'Not specified')+$this->grouplist,
    			'label'=>'Group:',
    			'required'=>true,
    			'data'=>((isset($this->user))?($this->user->getGroupId()):(''))
    		))
    		->add('location', 'choice', array(
    			'choices'=>array('0'=>'Not specified')+$this->locationlist,
    			'label'=>'Location:',
    			'required'=>true,
    			'data'=>((isset($this->user))?($this->user->getLocationId()):(''))
    		))
    		->add('role', 'choice', array(
    			'choices'=>$this->roles,
    			'label'=>'Role:',
    			'required'=>true,
    			'data'=>((isset($this->user))?($this->getRoles($this->user->getRoles())):(''))
    		))
    		->add('groupAdmin', 'choice', array(
    			'choices'=>array('0'=>'No', '1'=>'Yes'),
    			'label'=>'Group Admin:',
    			'required'=>true,
//   				'disabled'=>(((isset($this->user))?(($this->getRoles($this->user->getRoles()) == 'ROLE_MANAGER')?false:true):(false))),
    			'data'=>((isset($this->user))?($this->user->getGroupAdmin()):(''))
    		))
    		->add('locationAdmin', 'choice', array(
    			'choices'=>array('0'=>'No', '1'=>'Yes'),
    			'label'=>'Location Admin:',
    			'required'=>true,
//   				'disabled'=>(((isset($this->user))?(($this->getRoles($this->user->getRoles()) == 'ROLE_MANAGER')?false:true):(false))),
    			'data'=>((isset($this->user))?($this->user->getLocationAdmin()):(''))
    		))
    		->add('exEmail', 'choice', array(
    			'choices'=>array(0=>'No', 1=>'Yes'),
    			'label'=>'Send messages to e-mail:',
    			'required'=>false,
    			'data'=>((isset($this->user))?($this->user->getExEmail()):(''))
    		))
    		->add('submit', 'submit', array(
    			'label'=>'Submit',
    			'attr'=>array(
    				'class'=>'submitButton'
    			)
    		))
   			->add('cancel', 'button', array(
   				'label'=>'Cancel',
   				'attr'=>array('formnovalidate'=>true),
//    			'validation_groups'=>false
    		))
   			->addEventListener(
   				FormEvents::SUBMIT,
   				array($this, 'onSubmit')
   			)
   			->addEventListener(
   				FormEvents::POST_SUBMIT,
   				function (FormEvent $event) {
        			$event->stopPropagation();
   			}, 900);
// error_log('user:'.print_r($this->user, true));
// $roles=$this->user->getRoles();
// error_log('roles:'.print_r($roles, true));
// $role=$this->user->getRoles($roles);
// error_log('role:'.print_r($role, true));
   		if ($this->domains) {
   			$builder
   				->add('domainId', 'choice', array(
   					'choices'=>$this->domains,
    				'label'=>'Company:',
    				'required'=>true,
    				'data'=>((isset($this->user))?($this->user->getDomainId()):(''))
   				));	
   		}
   		
    	if ($this->new) {
    		$builder
    			->add('fpEnrol', 'checkbox', array(
    				'label'=>'Enrol in FP Reader?',
    				'required'=>false
    			))
    		  	->add('upass', 'repeated', array(
		    		'type'=>'password',
	    			'constraints'=>array(
	   					new NotBlank(),
	   					new Length(array('min'=>4, 'max'=>20))
	    			),
		    		'required' => true,
				    'first_options'  => array('label' => 'Password'),
				    'second_options' => array('label' => 'Repeat Password'),
		    	))
    		  	->add('username', 'text', array(
    		  		'label'=>'Username:',
    		  		'read_only'=>((isset($this->domains) && count($this->domains))?(false):(true)),
    		  		'required'=>false,
    		  		'data'=>((isset($this->user))?($this->user->getUsername()):(''))
    		  	));
    		  		
    	} else {
    		$builder
	    		->add('upass', 'repeated', array(
	    			'type'=>'password',
	    			'constraints'=>array(
	   					new Length(array('min'=>4, 'max'=>20))
	    			),
	    			'required' => false,
	    			'first_options'  => array('label' => 'Password'),
	    			'second_options' => array('label' => 'Repeat Password'),
	    		))
	    		->add('username', 'text', array(
	    			'label'=>'Username:',
	    			'read_only'=>((isset($this->domains) && count($this->domains))?(false):(true)),
	    			'required'=>true,
	    			'constraints'=>array(
	    				new NotBlank(),
	    				new Length(array('min'=>2, 'max'=>100))
	    			),
	    			'data'=>((isset($this->user))?($this->user->getUsername()):(''))
	    		));
	    		 
    	}
    }

    private function getRoles($roles) {

    	foreach ($roles as $r) {
    		if (isset($this->roles[$r])) {
    			return $r;
    		}
    	}
    	return 'ROLE_USER';
    }
    
    public function onSubmit(FormEvent $event) {
//    	$form=$event->getForm();
    	$data=$event->getData();
// error_log('onSubmit');
// error_log('form:'.print_r($form, true));
// error_log('form data:'.print_r($data, true));
		switch ($data['role']) {
			case 'ROLE_ADMIN': {
				$data['groupAdmin']=1;
				$data['locationAdmin']=1;
				$event->setData($data);
				break;
			}
			case 'ROLE_USER': {
				$data['groupAdmin']=0;
				$data['locationAdmin']=0;
				$event->setData($data);
				break;
			}
		}
    }

    public function getName()
    {
        return 'register';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

}