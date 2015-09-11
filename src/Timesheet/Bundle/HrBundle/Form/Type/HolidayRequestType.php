<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/HolidayRequestType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class HolidayRequestType extends AbstractType
{
	
	private $holiday;
	private $types;
	private $actionUrl;
	private $usernames;

	
	public function __construct($holiday=null, $types=null, $usernames=null, $actionUrl=null)
	{
		$this->holiday = $holiday;
		$this->types = $types;
		$this->actionUrl = $actionUrl;
		$this->usernames = $usernames;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('id', 'hidden', array(
    			'data'=>((isset($this->holiday))?($this->holiday->getId()):(''))
    		))
        	->add('typeId', 'choice', array(
        		'choices'=>$this->getHolidayTypeNames(),
    			'label'=>'Type:',
        		'required'=>true,
        		'attr'=>array(
        			'onchange'=>'holidayTypeChanged($(this).val())'
        		),
    			'data'=>((isset($this->holiday))?($this->holiday->getTypeId()):('')),
        		'empty_value'=>'Please select'
    		))
    		->add('startDate', 'date', array(
    			'label'=>'Start date:',
    			'widget'=>'single_text',
    			'format'=>'dd/MM/yyyy',
    			'required'=>true,
    			'attr'=>array(
    				'class'=>'dateInput',
    				'size'=>10	
    			),
    			'data'=>((isset($this->holiday))?($this->holiday->getStart()):(new \DateTime('+1 day')))
    		))
    		->add('finishDate', 'date', array(
    			'label'=>'Finish date:',
    			'widget'=>'single_text',
    			'format'=>'dd/MM/yyyy',
    			'required'=>true,
    			'attr'=>array(
    				'class'=>'dateInput',
    				'size'=>10
    			),
    			'data'=>((isset($this->holiday))?($this->holiday->getFinish()):(new \DateTime('now')))
    		))
    		->add('startTime', 'time', array(
    			'label'=>'Start date/time:',
    			'required'=>true,
    			'attr'=>array(
    				'class'=>'timeEntry'	
    			),
    			'data'=>((isset($this->holiday))?($this->holiday->getStart()):(new \DateTime('+1 day')))
    		))
    		->add('finishTime', 'time', array(
    			'label'=>'Finish date/time:',
    			'required'=>true,
    			'attr'=>array(
    				'class'=>'timeEntry'	
    			),
    			'data'=>((isset($this->holiday))?($this->holiday->getFinish()):(new \DateTime('now')))
    		))
    		->add('comment', 'textarea', array(
    			'label'=>'Comment:',
    			'required'=>false,
    			'attr'=>array(
    				'rows'=>3,
    				'cols'=>50
    			),
    			'data'=>((isset($this->holiday))?($this->holiday->getComment()):(''))
    		))
    		->add('submit', 'submit', array(
    			'label'=>'Submit',
    			'attr'=>array('class'=>'submitButton')
    		));
//   			->add('cancel', 'submit', array(
//   				'label'=>'Cancel',
//   				'attr'=>array('formnovalidate'=>true),
//    			'validation_groups'=>false
//    		));
   			
   		if ($this->usernames) {
   			$builder->add('userId', 'choice', array(
   				'choices'=>$this->usernames,
   				'label'=>'User:',
   				'required'=>true,
   				'empty_value'=>'Please select',
   				'data'=>((isset($this->holiday))?($this->holiday->getUserId()):(''))   					
   			));
   		}
   		if ($this->actionUrl) {
   			$builder->setAction($this->actionUrl);
   		}
   		
    }

    public function getName()
    {
        return 'holidayrequest';
    }

    public function getHolidayTypeNames() {
    	$ret=array();
    	
    	if (count($this->types)) {
    		foreach ($this->types as $ht) {
    			$ret[$ht->getId()]=$ht->getName();
    		}
    	}
    	
    	return $ret;
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
    
 }