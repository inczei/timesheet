<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/ContractType.php
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

class ContractType extends AbstractType
{
	private $contract;
	private $user;
	private $holidayCalculations;
	private $jobTitles;
	
	public function __construct($contract, $user, $holidayCalculations, $jobTitles)
	{
		$this->contract = $contract;
		$this->user = $user;
		$this->holidayCalculations = $holidayCalculations;
		$this->jobTitles = $jobTitles;
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
    			'required'=>false,
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
    			'required'=>false,
    			'read_only'=>true,
    			'data'=>((isset($this->user))?($this->user->getFirstName()):(''))
    		))
    		->add('lastName', 'text', array(
    			'label'=>'Last Name:',
    			'required'=>false,
    			'read_only'=>true,
    			'data'=>((isset($this->user))?($this->user->getLastName()):(''))
    		))
    		->add('jobTitleId', 'choice', array(
    			'choices'=>$this->jobTitles,
    			'label'=>'Job Title:',
    			'required'=>false,
    			'data'=>((isset($this->contract))?($this->contract->getJobTitleId()):(''))
    		))
    		->add('jobDescription', 'text', array(
    			'label'=>'Job Description:',
    			'required'=>false,
    			'data'=>((isset($this->contract))?($this->contract->getJobDescription()):(''))
    		))
    		->add('csd', 'date', array(
    			'label'=>'Contract Start Date:',
    			'widget'=>'single_text',
   				'format'=>'dd/MM/yyyy',
    			'required'=>true,
    			'attr'=>array(
    				'class'=>'dateInput'
    			),
    			'data'=>((isset($this->contract))?($this->contract->getCSD()):(new \Datetime()))
    		))
    		->add('ced', 'date', array(
    			'label'=>'Contract End Date:',
    			'widget'=>'single_text',
    				'format'=>'dd/MM/yyyy',
    			'required'=>false,
    			'attr'=>array(
    				'class'=>'dateInput'
    			),
    			'data'=>((isset($this->contract))?($this->contract->getCED()):(null))
    		))
    		->add('awh', 'number', array(
    			'label'=>'Agreed Weekly Hours:',
    			'required'=>true,
    			'data'=>((isset($this->contract))?($this->contract->getAWH()):(0))
    		))
    		->add('ahe', 'number', array(
    			'label'=>'Annual Holiday Entitlement:',
    			'required'=>false,
    			'data'=>((isset($this->contract))?($this->contract->getAHE()):(0))
    		))
    		->add('ahew', 'number', array(
    			'label'=>'Annual Holiday Entitlement (weeks):',
    			'required'=>false,
    			'data'=>((isset($this->contract))?($this->contract->getAHEW()):('5.6'))
    		))
    		->add('lunchtime', 'number', array(
    			'label'=>'Paid lunchtime (minutes):',
    			'required'=>false,
    			'data'=>((isset($this->contract))?($this->contract->getLunchtime()):(''))
    		))
    		->add('lunchtimeUnpaid', 'number', array(
    			'label'=>'Unpaid lunchtime (minutes):',
    			'required'=>false,
    			'data'=>((isset($this->contract))?($this->contract->getLunchtimeUnpaid()):(''))
    		))
    		->add('contractType', 'choice', array(
    			'label'=>'Contract Type:',
    			'choices'=>array('0'=>'Permanent', '1'=>'Probation', '2'=>'Temporary'),
    			'required'=>true,
    			'empty_value'=>' - Please select - ',
    			'data'=>((isset($this->contract))?($this->contract->getContractType()):(''))
    		))
    		->add('hct', 'choice', array(
    			'label'=>'Holiday Calculation:',
    			'choices'=>$this->holidayCalculations,
    			'required'=>true,
    			'empty_value'=>' - Please select - ',
    			'data'=>((isset($this->contract))?($this->contract->getHCT()):(''))
    		))
    		->add('wdpw', 'number', array(
    			'label'=>'Working Days per Week:',
    			'required'=>true,
    			'data'=>((isset($this->contract))?($this->contract->getWDpW()):(''))
    		))
    		->add('AHEonYS', 'choice', array(
    			'label'=>'AHE Start:',
    			'choices'=>array('0'=>'Contract Start Date', '1'=>'Year Start Date'),
    			'required'=>true,
    			'empty_value'=>' - Please select - ',
    			'data'=>((isset($this->contract))?($this->contract->getAHEonYS()):(''))
    		))
    		->add('initHolidays', 'number', array(
    			'label'=>'Holidays Carried Over:',
    			'required'=>false,
    			'data'=>((isset($this->contract))?($this->contract->getInitHolidays()):(0))
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
        return 'contract';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

 }