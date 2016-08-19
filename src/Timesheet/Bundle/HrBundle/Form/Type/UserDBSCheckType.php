<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/UserDBSCheckType.php
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

class UserDBSCheckType extends AbstractType
{
	private $userdbscheck;
	private $user;
	
	public function __construct($userdbscheck, $user, $dbschecktypes)
	{
		$this->userdbscheck = $userdbscheck;
		$this->user = $user;
		$this->dbschecktypes = $dbschecktypes;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('id', 'hidden', array(
    			'required'=>false,
    			'data'=>((isset($this->userdbscheck))?($this->userdbscheck->getId()):(''))
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
    		->add('disclosureNo', 'text', array(
    			'label'=>'Disclosure Number:',
    			'required'=>true,
    			'data'=>((isset($this->userdbscheck))?($this->userdbscheck->getDisclosureNo()):(''))
    		))
    		->add('issueDate', 'date', array(
    			'label'=>'Issue Date:',
    			'widget'=>'single_text',
   				'format'=>'dd/MM/yyyy',
    			'required'=>true,
    			'attr'=>array(
    				'class'=>'dateInput'
    			),
    			'data'=>((isset($this->userdbscheck))?($this->userdbscheck->getIssueDate()):(new \Datetime()))
    		))
    		->add('typeId', 'choice', array(
    			'label'=>'DBS Check Type:',
    			'choices'=>$this->dbschecktypes,
    			'required'=>true,
    			'empty_value'=>' - Please select - ',
    			'data'=>((isset($this->userdbscheck))?($this->userdbscheck->getTypeId()):(''))
    		))
    		->add('notes', 'textarea', array(
    			'label'=>'Notes:',
    			'required'=>false,
        		'attr'=>array(
        			'rows'=>3,
        			'cols'=>70
        		),
    			'data'=>((isset($this->userdbscheck))?($this->userdbscheck->getNotes()):(''))
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
        return 'userdbscheck';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

 }