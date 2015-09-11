<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/ContractType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Timesheet\Bundle\HrBundle\Entity\Shifts;
use Timesheet\Bundle\HrBundle\Entity\ShiftDays;
use \DateTime;

class TimingType extends AbstractType
{
	private $timing;
	private $user;
	private $shifts;
	private $days = array('1'=>'Monday', '2'=>'Tuesday', '3'=>'Wednesday', '4'=>'Thursday', '5'=>'Friday', '6'=>'Saturday', '0'=>'Sunday');
	private $ajaxAction;
	protected $em;
		
	
	public function __construct($timing, $user, $shifts, $ajaxAction, EntityManager $em)
	{
		$this->timing = $timing;
		$this->user = $user;
		$this->shifts = $shifts;
		$this->ajaxAction = $ajaxAction;
		$this->em = $em;
		 
// error_log('ajax action:'.$this->ajaxAction);
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('userId', 'hidden', array(
    			'required'=>true,
    			'read_only'=>true,
    			'constraints'=>array(
   					new NotBlank()
    			),
    			'data'=>((isset($this->user))?($this->user->getId()):(''))
    		))
    		->add('username', 'text', array(
    			'label'=>'Username:',
    			'required'=>true,
    			'read_only'=>true,
    			'data'=>((isset($this->user))?($this->user->getUsername()):(''))
    		))
    		->add('payrolCode', 'text', array(
    			'label'=>'Payrol Code:',
    			'required'=>false,
    			'read_only'=>true,
    			'data'=>((isset($this->user))?($this->user->getPayrolCode()):(''))
    		))
    		->add('firstName', 'text', array(
    			'label'=>'First Name:',
    			'read_only'=>true,
    			'data'=>((isset($this->user))?($this->user->getFirstName()):(''))
    		))
    		->add('lastName', 'text', array(
    			'label'=>'Last Name:',
    			'read_only'=>true,
    			'data'=>((isset($this->user))?($this->user->getLastName()):(''))
    		))
    		->add('dayId', 'choice', array(
    			'choices'=>$this->days,
    			'label'=>'Day:',
    			'required'=>true,
    			'empty_value' => ' - Please select - ',
    			'attr'=>array(
    				'class'=>'timing_type_days',
    				'data-action'=>$this->ajaxAction
    			),
    			'data'=>((isset($this->timing))?($this->timing->getDayId()):(null))
    		))
    		->add('shiftId', 'choice', array(
    			'choices'=>array(), // $this->shifts,
    			'label'=>'Shift:',
    			'required'=>true,
    			'empty_value' => ' - Please select - ',
    			'attr'=>array(
    				'class'=>'timing_type_shift'
    			),
    			'data'=>((isset($this->timing))?($this->timing->getShiftId()):(null))
    		))
    		->add('submit', 'submit', array(
    			'label'=>'Add',
    			'attr'=>array('class'=>'submitButton')
    		))
   			->add('cancel', 'submit', array(
   				'label'=>'Ready',
   				'attr'=>array('formnovalidate'=>true),
    			'validation_groups'=>false
    		));
   		$list=$this->getTiminglist($this->user->getId());   			
       	if (count($list)) {
   			foreach ($list as $l) {
   				$builder->add('delete_'.$l['id'], 'checkbox', array(
   					'required'=>false,
   					'attr'=>array(
   						'sid'=>$l['id']
   					)
   				));
   			}
   			$builder->add('delete', 'submit', array(
   					'label'=>'Delete selected',
   					'attr'=>array(
   						'formnovalidate'=>true
   					),
   					'validation_groups'=>false
   			));
   		}
   			$builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
   			$builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
   			
    }

    
    protected function addElements(FormInterface $form, ShiftDays $sd = null) {
// error_log('addElements');
    	$submit = $form->get('submit');
    	$form->remove('submit');
// error_log('add dayId');
    	$form->add('dayId', 'choice', array(
    			'choices'=>$this->days,
    			'label'=>'Day:',
    			'required'=>true,
    			'empty_value' => ' - Please select - ',
    			'attr'=>array(
    				'class'=>'timing_type_days',
    				'data-action'=>$this->ajaxAction
    			),
    			'data'=>$sd->getDayId()
    	));
    	
    	$shifts=array();
    	if ($sd) {
    		
    		$conn=$this->em->getConnection();
    		
    		$stmt=$conn->prepare('SELECT'.
    				' `s`.`id`,'.
    				' `s`.`startTime`,'.
    				' `s`.`finishTime`,'.
    				' `l`.`name`'.
    				' FROM `Shifts` `s`'.
    					' JOIN `Location` `l` ON `s`.`locationId`=`l`.`id`'.
    					' JOIN `ShiftDays` `sd` ON `s`.`id`=`sd`.`shiftId`'.
    				' WHERE `sd`.`dayId`=:dayId'.
    				' ORDER BY `l`.`name`, `s`.`startTime`');
    		
			$stmt->bindValue('dayId', $sd->getDayId());
			$stmt->execute();
			
			$results=$stmt->fetchAll();
			if ($results && count($results)) {
				foreach ($results as $result) {
					$shifts[$result['id']]=$result['name'].' '.substr($result['startTime'], 0, 5).'-'.substr($result['finishTime'], 0, 5);
				}
			}
// error_log('shifts:'.print_r($shifts, true));
    	}
// error_log('add shiftId');    	
    	$form->add('shiftId', 'choice', array(
    			'choices'=>$shifts,
    			'label'=>'Shift:',
    			'required'=>true,
    			'empty_value' => ' - Please select - ',
    			'attr'=>array(
    				'class'=>'timing_type_shift'
    			),
    			'data'=>'' // ((isset($this->timing))?($this->timing->getShiftId()):(null))
    		));
// error_log('done');
    	$form->add($submit);
	}
	
	function onPreSubmit(FormEvent $event) {
// error_log('onPreSubmit');

		$form=$event->getForm();
		$data=$event->getData();
// error_log('data:'.print_r($data, true));		
		$sd=new ShiftDays();
		$sd->setDayId($data['dayId']);
// error_log('sd:'.print_r($sd, true));
		$this->addElements($form, $sd);

	}
	
	function onPreSetData(FormEvent $event) {
// error_log('onPreSetData');		
	}

    private function getTimingList($userId) {
    	
    	$shifts=array();
    	$conn=$this->em->getConnection();
    	$stmt=$conn->prepare('SELECT'.
    		' `t`.`id`,'.
    		' `s`.`startTime`,'.
    		' `s`.`finishTime`,'.
    		' `l`.`name`'.
    		' FROM `Timing` `t`'.
    			' JOIN `Shifts` `s` ON `t`.`shiftId`=`s`.`id`'.
    			' JOIN `Location` `l` ON `s`.`locationId`=`l`.`id`'.
    			' JOIN `ShiftDays` `sd` ON `sd`.`shiftId`=`s`.`id` AND `sd`.`dayId`=`t`.`dayId`'.
    		' WHERE `t`.`userId`=:uId'.
    			' ORDER BY `l`.`name`, `s`.`startTime`');
    	
    	$stmt->bindValue('uId', $userId);
    	$stmt->execute();
    		
    	$results=$stmt->fetchAll();
    	if ($results && count($results)) {
    		foreach ($results as $result) {
    			$shifts[]=array('id'=>$result['id'], 'title'=>$result['name'].' '.substr($result['startTime'], 0, 5).'-'.substr($result['finishTime'], 0, 5));
    		}
    	}
    	
    	return $shifts;
    	 
    }
    
    
    public function getName()
    {
        return 'timing';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

 }