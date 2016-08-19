<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/UserVisaType.php
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

class UserVisaType extends AbstractType
{
	private $contract;
	private $user;
	private $holidayCalculations;
	
	public function __construct($uservisa, $user, $visalist)
	{
		$this->uservisa = $uservisa;
		$this->user = $user;
		$this->visalist = $visalist;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('id', 'hidden', array(
    			'required'=>false,
    			'data'=>((isset($this->uservisa))?($this->uservisa->getId()):(''))
    		))
    		->add('userId', 'hidden', array(
    			'required'=>false,
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
    		->add('startDate', 'date', array(
    			'label'=>'Start Date:',
    			'widget'=>'single_text',
   				'format'=>'dd/MM/yyyy',
    			'required'=>true,
    			'attr'=>array(
    				'class'=>'dateInput'
    			),
    			'data'=>((isset($this->uservisa))?($this->uservisa->getStartDate()):(new \Datetime()))
    		))
    		->add('endDate', 'date', array(
    			'label'=>'Expiry Date:',
    			'widget'=>'single_text',
    				'format'=>'dd/MM/yyyy',
    			'required'=>false,
    			'attr'=>array(
    				'class'=>'dateInput'
    			),
    			'data'=>((isset($this->uservisa))?($this->uservisa->getEndDate()):(null))
    		))
    		->add('notExpire', 'choice', array(
    			'choices'=>array(0=>'Expire', 1=>'Not Expire'),
    			'label'=>'Expire?',
    			'required'=>true,
    			'data'=>((isset($this->uservisa))?($this->uservisa->getnotExpire()):(0))
    		))
    		->add('visaId', 'choice', array(
    			'label'=>'Visa Type:',
    			'choices'=>$this->visalist,
    			'required'=>true,
    			'empty_value'=>' - Please select - ',
    			'data'=>((isset($this->uservisa))?($this->uservisa->getVisaId()):(''))
    		))
    		->add('notes', 'textarea', array(
    			'label'=>'Notes:',
    			'required'=>false,
        		'attr'=>array(
        			'rows'=>3,
        			'cols'=>70
        		),
    			'data'=>((isset($this->uservisa))?($this->uservisa->getNotes()):(''))
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
        return 'uservisa';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

 }