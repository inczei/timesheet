<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/ResidentRegisterType.php
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
use Symfony\Component\Intl\Intl;

class ResidentRegisterType extends AbstractType
{
	private $titles;
	private $resident;
	private $religions;
	private $maritalStatuses;
	private $domains;
	private $readOnly;
	private $countries;
	private $room;
	private $rooms;
	
	public function __construct($resident = null, $room = null, $titles = null, $religions = null, $maritalStatuses = null, $rooms = null, $domains = null, $readOnly = false)
	{
		$this->titles = $titles;
		$this->resident = $resident;
		$this->religions = $religions;
		$this->maritalStatuses = $maritalStatuses;
		$this->domains = $domains;
		$this->readOnly = $readOnly;
		$this->room = $room;
		$this->rooms = $rooms;
		$this->countries = Intl::getRegionBundle()->getCountryNames();
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        	->add('id', 'hidden', array(
    			'label'=>'Id:',
        		'read_only'=>$this->readOnly,
    			'required'=>false,
    			'data'=>((isset($this->resident))?($this->resident->getId()):(null))
    		))
    		->add('notes', 'textarea', array(
    			'label'=>'Notes:',
        		'read_only'=>$this->readOnly,
    			'required'=>false,
        		'attr'=>array(
        			'rows'=>3,
        			'cols'=>70
        		),
    			'data'=>((isset($this->resident))?($this->resident->getNotes()):(''))
    		))
        	->add('firstName', 'text', array(
    			'label'=>'First Name:',
    			'read_only'=>$this->readOnly,
        		'required'=>!$this->readOnly,
        		'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>50))
    			),
    			'data'=>((isset($this->resident))?($this->resident->getFirstName()):(''))
    		))
    		->add('lastName', 'text', array(
    			'label'=>'Last Name:',
    			'read_only'=>$this->readOnly,
    			'required'=>!$this->readOnly,
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>50))
    			),
    			'data'=>((isset($this->resident))?($this->resident->getLastName()):(''))
    		))
    		->add('nickName', 'text', array(
    			'label'=>'Nickname:',
    			'read_only'=>$this->readOnly,
    			'required'=>false,
    			'constraints'=>array(
  					new Length(array('max'=>50))
    			),
    			'data'=>((isset($this->resident))?($this->resident->getNickName()):(''))
    		))
    		->add('maidenName', 'text', array(
    			'label'=>'Maiden Name:',
    			'read_only'=>$this->readOnly,
    			'required'=>false,
    			'constraints'=>array(
  					new Length(array('max'=>50))
    			),
    			'data'=>((isset($this->resident))?($this->resident->getMaidenName()):(''))
    		))
    		->add('email', 'email', array(
    			'label'=>'E-mail:',
    			'read_only'=>$this->readOnly,
    			'required'=>false,
    			'constraints'=>array(
    				new Email(),
   					new Length(array('max'=>50))
    			),
    			'data'=>((isset($this->resident))?($this->resident->getEmail()):(''))
    		))
    		->add('phoneLandline', 'text', array(
    			'label'=>'Phone Landline:',
    			'read_only'=>$this->readOnly,
    			'required'=>false,
    			'data'=>((isset($this->resident))?($this->resident->getPhoneLandline()):(''))
    		))
    		->add('phoneMobile', 'text', array(
    			'label'=>'Phone Mobile:',
    			'read_only'=>$this->readOnly,
    			'required'=>false,
    			'data'=>((isset($this->resident))?($this->resident->getPhoneMobile()):(''))
    		))
    		->add('birthday', 'birthday', array(
    			'widget'=>'single_text',
    			'label'=>'Date of Birth:',
    			'read_only'=>$this->readOnly,
    			'required'=>false,
    			'format'=>'dd/MM/yyyy',
    			'attr'=>array(
    				'class'=>(($this->readOnly)?(''):('dateInput'))
    			),
    			'data'=>((isset($this->resident))?($this->resident->getBirthday()):(null))
    		))
    		->add('addressLine1', 'text', array(
    			'label'=>'Address:',
    			'read_only'=>$this->readOnly,
    			'required'=>!$this->readOnly,
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>50))
    			),
    			'data'=>((isset($this->resident))?($this->resident->getAddressLine1()):(''))
    		))
    		->add('addressLine2', 'text', array(
    			'label'=>' ',
    			'read_only'=>$this->readOnly,
    			'required'=>false,
    			'constraints'=>array(
   					new Length(array('max'=>50))
    			),
    			'data'=>((isset($this->resident))?($this->resident->getAddressLine2()):(''))
    		))
    		->add('addressCity', 'text', array(
    			'label'=>'City/Town:',
    			'read_only'=>$this->readOnly,
    			'required'=>!$this->readOnly,
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>50))
    			),
    			'data'=>((isset($this->resident))?($this->resident->getAddressCity()):(''))
    		))
    		->add('addressCounty', 'text', array(
    			'label'=>'County:',
    			'read_only'=>$this->readOnly,
    			'required'=>false,
    			'constraints'=>array(
					new Length(array('max'=>50))
   				),
    			'data'=>((isset($this->resident))?($this->resident->getAddressCounty()):(''))
    		))
    		->add('addressPostcode', 'text', array(
    			'label'=>'Postcode:',
    			'read_only'=>$this->readOnly,
    			'required'=>!$this->readOnly,
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>10))
    			),
    			'data'=>((isset($this->resident))?($this->resident->getAddressPostcode()):(''))
    		))
    		->add('ni', 'text', array(
    			'label'=>'National Insurance:',
    			'read_only'=>$this->readOnly,
    			'required'=>!$this->readOnly,
    			'data'=>((isset($this->resident))?($this->resident->getNI()):(''))
    		))
    		->add('nhs', 'text', array(
    			'label'=>'NHS Number:',
    			'read_only'=>$this->readOnly,
    			'required'=>!$this->readOnly,
    			'data'=>((isset($this->resident))?($this->resident->getNHS()):(''))
    		));

    	if (isset($this->rooms) && count($this->rooms)) {
    		$builder
	    		->add('roomId', 'choice', array(
	    			'choices'=>$this->rooms,
	    			'read_only'=>$this->readOnly,
	    			'label'=>'Room:',
	    			'required'=>false,
	    			'data'=>((isset($this->room) && isset($this->room['id']))?($this->room['id']):(null))
	    		))
	    		->add('roomMoveIn', 'date', array(
	    			'widget'=>'single_text',
	    			'label'=>'Move In Date:',
	    			'read_only'=>$this->readOnly,
	    			'required'=>false,
	    			'format'=>'dd/MM/yyyy',
	    			'attr'=>array(
	    				'class'=>(($this->readOnly)?(''):('dateInput'))
	    			),
	    			'data'=>((isset($this->room) && isset($this->room['moveIn']))?($this->room['moveIn']):(null))
	    		))
	    		->add('roomNotes', 'textarea', array(
	    			'label'=>'Room Notes:',
	        		'read_only'=>$this->readOnly,
	    			'required'=>false,
	        		'attr'=>array(
	        			'rows'=>3,
	        			'cols'=>70
	        		),
	    			'data'=>((isset($this->room) && isset($this->room['roomNotes']))?($this->room['roomNotes']):(null))
	    		));
    	}
    	if ($this->readOnly) {
    		$builder
   	 			->add('title', 'text', array(
    				'read_only'=>$this->readOnly,
    				'label'=>'Title:',
    				'required'=>false,
    				'data'=>((isset($this->resident) && isset($this->titles[$this->resident->getTitle()]))?($this->titles[$this->resident->getTitle()]):(''))
	    		))
	    		->add('religion', 'text', array(
	    			'label'=>'Religion:',
	    			'read_only'=>$this->readOnly,
	    			'required'=>false,
	    			'data'=>((isset($this->resident) && isset($this->religions[$this->resident->getReligion()]))?($this->religions[$this->resident->getReligion()]):(''))
	    		))
	    		->add('nationality', 'text', array(
	    			'label'=>'Nationality:',
	    			'read_only'=>$this->readOnly,
	    			'required'=>false,
	    			'data'=>((isset($this->resident) && isset($this->countries[$this->resident->getNationality()]))?($this->countries[$this->resident->getNationality()]):(''))
	    		))
	    		->add('addressCountry', 'text', array(
	    			'label'=>'Country:',
	    			'read_only'=>$this->readOnly,
	    			'required'=>false,
	    			'data'=>((isset($this->resident) && isset($this->countries[$this->resident->getAddressCountry()]))?($this->countries[$this->resident->getAddressCountry()]):(''))
	    		))
	    		->add('maritalStatus', 'text', array(
	    			'read_only'=>$this->readOnly,
	    			'label'=>'Marital Status:',
	    			'required'=>false,
	    			'data'=>((isset($this->resident) && isset($this->maritalStatuses[$this->resident->getMaritalStatus()]))?($this->maritalStatuses[$this->resident->getMaritalStatus()]):(''))
	    		));
//	    		->add('roomId', 'text', array(
//	    			'read_only'=>$this->readOnly,
//	    			'label'=>'Room:',
//	    			'required'=>false,
//	    			'data'=>((isset($this->room) && isset($this->room['roomNumber']))?($this->room['roomNumber']):(null))
//	    		));
    	} else {
    		$builder
        		->add('title', 'choice', array(
        			'choices'=>$this->titles,
    				'read_only'=>$this->readOnly,
        			'label'=>'Title:',
       				'required'=>false,
    				'data'=>((isset($this->resident))?($this->resident->getTitle()):(''))
    			))
	    		->add('religion', 'choice', array(
	    			'choices'=>$this->religions,
	    			'label'=>'Religion:',
	    			'read_only'=>$this->readOnly,
	    			'required'=>false,
	    			'data'=>((isset($this->resident))?($this->resident->getReligion()):('')),
	    			'empty_value'=>' - Please select - '
	    		))
	    		->add('addressCountry', 'country', array(
	    			'label'=>'Country:',
	    			'preferred_choices'=>array('GB'),
	    			'read_only'=>$this->readOnly,
	    			'required'=>true,
	    			'data'=>((isset($this->resident))?($this->resident->getAddressCountry()):('')),
//	    			'empty_value'=>' - Please select - ',
	    		))
	    		->add('nationality', 'country', array(
	    			'label'=>'Nationality:',
	    			'preferred_choices'=>array('GB'),
	    			'read_only'=>$this->readOnly,
	    			'required'=>true,
	    			'data'=>((isset($this->resident))?($this->resident->getNationality()):('')),
//	    			'empty_value'=>' - Please select - ',
	    		))
	    		->add('maritalStatus', 'choice', array(
	    			'choices'=>$this->maritalStatuses,
	    			'read_only'=>$this->readOnly,
	    			'label'=>'Marital Status:',
	    			'required'=>false,
	    			'data'=>((isset($this->resident))?($this->resident->getMaritalStatus()):(''))
	    		))
	    		->add('submit', 'submit', array(
    				'label'=>'Submit',
    				'attr'=>array(
    					'class'=>'submitButton'
    				)
    			))
   				->add('cancel', 'submit', array(
   					'label'=>'Cancel',
   					'attr'=>array(
   						'formnovalidate'=>true
   					),
    				'validation_groups'=>false
    			));
    	}

   		if ($this->domains) {
   			$builder
   				->add('domainId', 'choice', array(
   					'choices'=>$this->domains,
    				'label'=>'Company:',
   					'read_only'=>$this->readOnly,
    				'required'=>true,
    				'data'=>((isset($this->resident))?($this->resident->getDomainId()):(''))
   				));	
   		}
    }

    public function getName()
    {
        return 'residentregister';
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

}