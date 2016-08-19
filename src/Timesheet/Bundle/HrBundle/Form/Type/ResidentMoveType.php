<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/ResidentMoveType.php
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

class ResidentMoveType extends AbstractType
{
	
	private $resident;
	private $rooms;
	private $currentLocation;
	
	public function __construct($resident, $rooms, $currentLocation)
	{
		$this->resident = $resident;
		$this->rooms = $rooms;
		$this->currentLocation = $currentLocation;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        	->add('residentId', 'hidden', array(
        		'data'=>((isset($this->resident))?($this->resident->getId()):(''))
        	))
        	->add('currentRoomId', 'hidden', array(
    			'data'=>((isset($this->currentLocation))?($this->currentLocation['id']):(''))
    		))
        	->add('notes', 'textarea', array(
    			'label'=>'Notes:',
    			'required'=>false,
        		'attr'=>array(
        			'rows'=>3,
        			'cols'=>70
        		),
    			'data'=>''
    		))
        	->add('residentName', 'text', array(
    			'label'=>'Resident Name:',
        		'read_only'=>true,
       			'required'=>false,
    			'data'=>((isset($this->resident))?(trim($this->resident->getTitle().' '.$this->resident->getFirstName().' '.$this->resident->getLastName())):(''))
    		))
        	->add('currentLocation', 'text', array(
    			'label'=>'Current Location:',
        		'read_only'=>true,
       			'required'=>false,
    			'data'=>((isset($this->currentLocation))?($this->currentLocation['roomNumber'].' - '.$this->currentLocation['name']):(''))
    		))
    		->add('roomId', 'choice', array(
    			'choices'=>array(0=>'Move out')+$this->rooms,
    			'label'=>'Where to move:',
    			'required'=>true,
    			'data'=>null,
    			'empty_value'=>' - Please select - ',
    		))
    		->add('date', 'date', array(
    			'widget'=>'single_text',
    			'label'=>'Date:',
    			'required'=>true,
    			'format'=>'dd/MM/yyyy',
    			'attr'=>array(
    				'class'=>'dateInput'
    			),
    			'data'=>new \DateTime()
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
    		));
    }

    public function getName()
    {
        return 'residentmove';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

}