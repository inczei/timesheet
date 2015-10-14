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


class RoomType extends AbstractType
{
	
	private $room;
	private $locations;
	
	
	public function __construct($room, $locations)
	{
		$this->room = $room;
		$this->locations = $locations;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('id', 'hidden', array(
    			'data'=>((isset($this->room))?($this->room->getId()):(''))
    		))
        	->add('roomNumber', 'text', array(
    			'label'=>'Room Number:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>1, 'max'=>8))
    			),
    			'data'=>((isset($this->room))?($this->room->getRoomNumber()):(''))
    		))
    		->add('locationId', 'choice', array(
    			'choices'=>$this->locations,
    			'label'=>'Location:',
    			'required'=>true,
    			'data'=>((isset($this->room))?($this->room->getLocationId()):(''))
    		))
    		->add('places', 'text', array(
    			'label'=>'Places:',
    			'required'=>true,
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>1, 'max'=>8))
    			),
    			'data'=>((isset($this->room))?($this->room->getPlaces()):(''))
    		))
    		->add('extraPlaces', 'text', array(
    			'label'=>'Extra Places:',
    			'required'=>false,
    			'data'=>((isset($this->room))?($this->room->getExtraPlaces()):(''))
    		))
    		->add('open', 'choice', array(
    			'choices'=>array('0'=>'No', '1'=>'Yes'),
    			'label'=>'Open:',
    			'required'=>true,
    			'data'=>((isset($this->room))?($this->room->getOpen()):(''))
    		))
    		->add('active', 'choice', array(
    			'choices'=>array('0'=>'No', '1'=>'Yes'),
    			'label'=>'Active:',
    			'required'=>true,
    			'data'=>((isset($this->room))?($this->room->getActive()):(''))
    		))
    		->add('notes', 'textarea', array(
    			'label'=>'Notes:',
    			'required'=>false,
    			'attr'=>array('rows'=>3, 'cols'=>60),
    			'data'=>((isset($this->room))?($this->room->getNotes()):(''))
    		))
    		->add('submit', 'submit', array(
    			'label'=>'Submit',
    			'attr'=>array('class'=>'submitButton')
    		))
   			->add('cancel', 'submit', array(
   				'label'=>'Cancel',
   				'attr'=>array('formnovalidate'=>true),
    			'validation_groups'=>false
    		));
   			
    }

    public function getName()
    {
        return 'room';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

 }