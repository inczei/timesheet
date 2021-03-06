<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/ShiftType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class ShiftType extends AbstractType
{
	
	private $locations;
	private $shift;
	private $days;
	
	
	public function __construct($shift, $locations, $days)
	{
		$this->locations = $locations;
		$this->shift = $shift;
		$this->days = $days;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('id', 'hidden', array(
    			'data'=>((isset($this->status))?($this->status['id']):(''))
    		))
        	->add('title', 'text', array(
    			'label'=>'Title:',
        		'required'=>true,
    			'data'=>((isset($this->shift))?($this->shift->getTitle()):('')),
        		'attr'=>array(
        			'style'=>'width: 99%'
        		)
    		))
    		->add('locationId', 'choice', array(
        		'choices'=>$this->locations,
    			'label'=>'Location:',
        		'required'=>true,
    			'data'=>((isset($this->shift))?($this->shift->getLocationId()):(''))
    		))
    		->add('startTime', 'time', array(
    			'label'=>'Start time:',
    			'widget'=>'choice',
    			'minutes'=>array(0,15,30,45),
    			'required'=>true,
    			'attr'=>array(
    				'class'=>'timeEntry'	
    			),
    			'data'=>((isset($this->shift))?($this->shift->getStartTime()):(''))
    		))
    		->add('strictBreak', 'checkbox', array(
    			'label'=>'Strict break time:',
        		'required'=>false,
    			'data'=>((isset($this->shift))?($this->shift->getStrictBreak()):(''))
    		))
    		->add('startBreak', 'time', array(
    			'label'=>'Start break:',
    			'widget'=>'choice',
    			'minutes'=>array(0,15,30,45),
    			'required'=>false,
    			'attr'=>array(
    				'class'=>'timeEntry'	
    			),
    			'data'=>((isset($this->shift))?($this->shift->getStartBreak()):(''))
    		))
    		->add('finishBreak', 'time', array(
    			'label'=>'Finish break:',
    			'widget'=>'choice',
    			'minutes'=>array(0,15,30,45),
    			'required'=>false,
    			'data'=>((isset($this->shift))?($this->shift->getFinishBreak()):(''))
    		))
    		->add('finishTime', 'time', array(
    			'label'=>'Finish time:',
    			'widget'=>'choice',
    			'minutes'=>array(0,15,30,45),
    			'required'=>true,
    			'data'=>((isset($this->shift))?($this->shift->getFinishTime()):(''))
    		))
    		->add('fpStartTime', 'time', array(
    			'label'=>'FP Start time:',
    			'widget'=>'choice',
    			'minutes'=>array(0,15,30,45),
    			'required'=>false,
    			'empty_value'=>'Please select',
    			'attr'=>array(
    				'class'=>'timeEntry'	
    			),
    			'data'=>((isset($this->shift))?($this->shift->getFpStartTime()):(''))
    		))
    		->add('fpFinishTime', 'time', array(
    			'label'=>'FP Finish time:',
    			'widget'=>'choice',
    			'minutes'=>array(0,15,30,45),
    			'required'=>false,
    			'empty_value'=>'Please select',
    			'data'=>((isset($this->shift))?($this->shift->getFpFinishTime()):(''))
    		))
    		->add('fpStartBreak', 'time', array(
    			'label'=>'FP Start Break time:',
    			'widget'=>'choice',
    			'minutes'=>array(0,15,30,45),
    			'required'=>false,
    			'empty_value'=>'Please select',
    			'attr'=>array(
    				'class'=>'timeEntry'	
    			),
    			'data'=>((isset($this->shift))?($this->shift->getFpStartBreak()):(''))
    		))
    		->add('fpFinishBreak', 'time', array(
    			'label'=>'FP Finish Break time:',
    			'widget'=>'choice',
    			'minutes'=>array(0,15,30,45),
    			'required'=>false,
    			'empty_value'=>'Please select',
    			'data'=>((isset($this->shift))?($this->shift->getFpFinishBreak()):(''))
    		))
    		->add('days', 'hidden', array(
    			'label'=>'Days:',
        		'required'=>false
    		))
        	->add('dayMon', 'checkbox', array(
    			'label'=>'Mon:',
        		'required'=>false,
        		'data'=>((isset($this->days[1]))?($this->days[1]):(false))
    		))
        	->add('dayTue', 'checkbox', array(
    			'label'=>'Tue:',
        		'required'=>false,
        		'data'=>((isset($this->days[2]))?($this->days[2]):(false))
    		))
        	->add('dayWed', 'checkbox', array(
    			'label'=>'Wed:',
        		'required'=>false,
        		'data'=>((isset($this->days[3]))?($this->days[3]):(false))
    		))
        	->add('dayThu', 'checkbox', array(
    			'label'=>'Thu:',
        		'required'=>false,
        		'data'=>((isset($this->days[4]))?($this->days[4]):(false))
    		))
        	->add('dayFri', 'checkbox', array(
    			'label'=>'Fri:',
        		'required'=>false,
        		'data'=>((isset($this->days[5]))?($this->days[5]):(false))
    		))
        	->add('daySat', 'checkbox', array(
    			'label'=>'Sat:',
        		'required'=>false,
        		'data'=>((isset($this->days[6]))?($this->days[6]):(false))
    		))
    		->add('daySun', 'checkbox', array(
    			'label'=>'Sun:',
        		'required'=>false,
        		'data'=>((isset($this->days[0]))?($this->days[0]):(false))
    		))
    		->add('minWorkTime', 'number', array(
    			'label'=>'Minimum Working Time (minutes):',
        		'required'=>false,
    			'data'=>((isset($this->shift))?($this->shift->getMinWorkTime()):(''))
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
        return 'shift';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
    
 }