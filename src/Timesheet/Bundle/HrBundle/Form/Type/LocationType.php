<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/LocationType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class LocationType extends AbstractType
{
	
	private $location;
	private $ipaddress;
	
	
	public function __construct($location, $ipaddress='')
	{
		$this->location = $location;
		$this->ipaddress = $ipaddress;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('domainId', 'hidden', array(
    			'label'=>'Domain Id:',
    			'data'=>((isset($this->location))?($this->location->getDomainId()):(''))
    		))
        	->add('name', 'text', array(
    			'label'=>'Name:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>50))
    			),
    			'data'=>((isset($this->location))?($this->location->getName()):(''))
    		))
    		->add('phoneLandline', 'text', array(
    			'label'=>'Landline Number:',
    			'required'=>false,
    			'data'=>((isset($this->location))?($this->location->getPhoneLandline()):(''))
    		))
    		->add('phoneMobile', 'text', array(
    			'label'=>'Mobile Number:',
    			'required'=>false,
    			'data'=>((isset($this->location))?($this->location->getPhoneMobile()):(''))
    		))
    		->add('phoneFax', 'text', array(
    			'label'=>'Fax Number:',
    			'required'=>false,
    			'data'=>((isset($this->location))?($this->location->getPhoneFax()):(''))
    		))
    		->add('addressLine1', 'text', array(
    			'label'=>'Address:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>100))
    			),
    			'data'=>((isset($this->location))?($this->location->getAddressLine1()):(''))
    		))
    		->add('addressLine2', 'text', array(
    			'label'=>' ',
    			'required'=>false,
    			'data'=>((isset($this->location))?($this->location->getAddressLine2()):(''))
    		))
    		->add('addressCity', 'text', array(
    			'label'=>'City/Town:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>100))
    			),
    			'data'=>((isset($this->location))?($this->location->getAddressCity()):(''))
    		))
    		->add('addressCounty', 'text', array(
    			'label'=>'County:',
    			'required'=>false,
    			'data'=>((isset($this->location))?($this->location->getAddressCounty()):(''))
    		))
    		->add('addressCountry', 'country', array(
    			'label'=>'Country:',
    			'preferred_choices'=>array('GB'),
    			'required'=>true,
    			'data'=>((isset($this->location))?($this->location->getAddressCountry()):(''))
    		))
    		->add('addressPostcode', 'text', array(
    			'label'=>'Postcode:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>10))
    			),
    			'data'=>((isset($this->location))?($this->location->getAddressPostcode()):(''))
    		))
    		->add('latitude', 'number', array(
    			'label'=>'Latitude:',
    			'data'=>((isset($this->location))?($this->location->getLatitude()):(''))
    		))
    		->add('longitude', 'number', array(
    			'label'=>'Longitude:',
    			'data'=>((isset($this->location))?($this->location->getLongitude()):(''))
    		))
    		->add('radius', 'number', array(
    			'label'=>'Radius (m):',
    			'data'=>((isset($this->location))?($this->location->getRadius()):(''))
    		))
    		->add('active', 'choice', array(
    			'choices'=>array('1'=>'Active', '0'=>'Inactive'),
    			'label'=>'Status:',
    			'required'=>true,
    			'data'=>((isset($this->location))?($this->location->getActive()):(''))
    		))
    		->add('fixedipaddress', 'choice', array(
    			'choices'=>array('1'=>'Yes', '0'=>'No'),
    			'label'=>'Fixed IP Address:',
    			'required'=>true,
    			'data'=>((isset($this->location))?($this->location->getFixedIpAddress()):(''))
    		))
    		->add('ipaddress', 'textarea', array(
    			'label'=>'IP Address(es):',
    			'required'=>false,
    			'attr'=>array('rows'=>3, 'cols'=>60),
    			'data'=>$this->ipaddress
    		))
    		->add('submit', 'submit', array(
    			'label'=>'Submit',
    			'attr'=>array('class'=>'submitButton')
    		))
   			->add('cancel', 'button', array(
   				'label'=>'Cancel',
   				'attr'=>array('formnovalidate'=>true),
//    			'validation_groups'=>false
    		));
   			
    }

    public function getName()
    {
        return 'location';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

 }