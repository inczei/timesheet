<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/ResidentContactType.php
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

class ResidentContactType extends AbstractType
{
	
	private $resident;
	private $residentContact;
	private $titles;
	
	public function __construct($resident, $residentContact, $titles)
	{
		$this->resident = $resident;
		$this->residentContact = $residentContact;
		$this->titles = $titles;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        	->add('id', 'hidden', array(
        		'data'=>((isset($this->residentContact))?($this->residentContact->getId()):(''))
        	))
        	->add('notes', 'textarea', array(
    			'label'=>'Notes:',
    			'required'=>false,
        		'attr'=>array(
        			'rows'=>3,
        			'cols'=>70
        		),
    			'data'=>((isset($this->residentContact))?($this->residentContact->getNotes()):(''))
    		))
        	->add('residentname', 'text', array(
    			'label'=>'Resident Name:',
        		'read_only'=>true,
       			'required'=>false,
    			'data'=>((isset($this->resident))?(trim($this->resident->getTitle().' '.$this->resident->getFirstName().' '.$this->resident->getLastName())):(''))
    		))
        	->add('title', 'choice', array(
        		'choices'=>$this->titles,
        		'label'=>'Title:',
       			'required'=>false,
    			'data'=>((isset($this->residentContact))?($this->residentContact->getTitle()):(''))
    		))
    		->add('firstName', 'text', array(
    			'label'=>'First Name:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>50))
    			),
    			'data'=>((isset($this->residentContact))?($this->residentContact->getFirstName()):(''))
    		))
    		->add('lastName', 'text', array(
    			'label'=>'Last Name:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>50))
    			),
    			'data'=>((isset($this->residentContact))?($this->residentContact->getLastName()):(''))
    		))
    		->add('email', 'email', array(
    			'label'=>'E-mail:',
    			'required'=>false,
    			'constraints'=>array(
   					new NotBlank(),
    				new Email(),
   					new Length(array('min'=>5, 'max'=>100))
    			),
    			'data'=>((isset($this->residentContact))?($this->residentContact->getEmail()):(''))
    		))
    		->add('phoneLandline', 'text', array(
    			'label'=>'Landline Phone number:',
    			'required'=>false,
    			'data'=>((isset($this->residentContact))?($this->residentContact->getPhoneLandline()):(''))
    		))
    		->add('phoneMobile', 'text', array(
    			'label'=>'Mobile Phone number:',
    			'required'=>false,
    			'data'=>((isset($this->residentContact))?($this->residentContact->getPhoneMobile()):(''))
    		))
    		->add('phoneOther', 'text', array(
    			'label'=>'Other Phone number:',
    			'required'=>false,
    			'data'=>((isset($this->residentContact))?($this->residentContact->getPhoneOther()):(''))
    		))
    		->add('preferredPhone', 'text', array(
    			'label'=>'Preferred Phone number:',
    			'required'=>true,
    			'data'=>((isset($this->residentContact))?($this->residentContact->getPreferredPhone()):(''))
    		))
    		->add('email', 'email', array(
    			'label'=>'E-mail:',
    			'required'=>false,
    			'data'=>((isset($this->residentContact))?($this->residentContact->getEmail()):(''))
    		))
    		->add('relation', 'text', array(
    			'label'=>'Relation:',
    			'required'=>true,
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>50))
    			),
    			'data'=>((isset($this->residentContact))?($this->residentContact->getRelation()):(''))
    		))
    		->add('addressLine1', 'text', array(
    			'label'=>'Address:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>50))
    			),
    			'data'=>((isset($this->residentContact))?($this->residentContact->getAddressLine1()):(''))
    		))
    		->add('addressLine2', 'text', array(
    			'label'=>' ',
    			'required'=>false,
    			'data'=>((isset($this->residentContact))?($this->residentContact->getAddressLine2()):(''))
    		))
    		->add('addressCity', 'text', array(
    			'label'=>'City/Town:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>100))
    			),
    			'data'=>((isset($this->residentContact))?($this->residentContact->getAddressCity()):(''))
    		))
    		->add('addressCounty', 'text', array(
    			'label'=>'County:',
    			'required'=>false,
    			'data'=>((isset($this->residentContact))?($this->residentContact->getAddressCounty()):(''))
    		))
    		->add('addressCountry', 'country', array(
				'preferred_choices'=>array('GB'),
	  			'label'=>'Country:',
    			'required'=>true,
    			'data'=>((isset($this->residentContact))?($this->residentContact->getAddressCountry()):('')),
//    			'empty_value'=>' - Please select - ',
    		))
    		->add('addressPostcode', 'text', array(
    			'label'=>'Postcode:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>10))
    			),
    			'data'=>((isset($this->residentContact))?($this->residentContact->getAddressPostcode()):(''))
    		))
    		->add('emergency', 'choice', array(
    			'choices'=>array(0=>'No', 1=>'Yes'),
    			'label'=>'Emergency contact:',
    			'required'=>true,
    			'data'=>((isset($this->residentContact))?($this->residentContact->getEmergency()):(''))
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

    public function getName()
    {
        return 'residentcontact';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

}