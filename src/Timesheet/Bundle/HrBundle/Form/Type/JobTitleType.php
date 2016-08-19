<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/JobTitleType.php
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

class JobTitleType extends AbstractType
{
	
	private $jobTitle;
	private $domains;

	public function __construct($jobTitle, $domains=null)
	{
		$this->jobTitle = $jobTitle;
		$this->domains = $domains;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('id', 'hidden', array(
    			'required'=>false,
    			'data'=>((isset($this->jobTitle))?($this->jobTitle->getId()):(''))
    		))
    		->add('title', 'text', array(
    			'label'=>'Title:',
    			'required'=>true,
    			'data'=>((isset($this->jobTitle))?($this->jobTitle->getTitle()):(''))
    		))
    		->add('status', 'choice', array(
    			'choices'=>array(1=>'Active', 0=>'Inactive'),
    			'label'=>'Status:',
    			'required'=>true,
    			'data'=>((isset($this->jobTitle))?($this->jobTitle->getActive()):(0))
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
   			if (isset($this->domains) && count($this->domains)) {
   				$builder
   				->add('domainId', 'choice', array(
   						'label'=>'Company:',
   						'choices'=>$this->domains,
   						'data'=>((isset($this->jobTitle))?($this->jobTitle->getDomainId()):(''))
   				));
   			}
   			
    }

    
    public function getName()
    {
        return 'jobtitle';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

 }