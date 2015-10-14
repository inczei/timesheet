<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/QuaqlificationType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class QualificationType extends AbstractType
{
	
	private $qualification;
	private $domains;
	
	
	public function __construct($qualification, $domains=null)
	{
		$this->qualification = $qualification;
		$this->domains = $domains;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
			->add('id', 'hidden', array(
    			'label'=>'Id:',
    			'data'=>((isset($this->qualification))?($this->qualification->getId()):(''))
			))
        	->add('title', 'text', array(
    			'label'=>'Title:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>100))
    			),
    			'data'=>((isset($this->qualification))?($this->qualification->getTitle()):(''))
    		))
    		->add('comments', 'text', array(
    			'label'=>'Comments:',
    			'required'=>false,
    			'data'=>((isset($this->qualification))?($this->qualification->getComments()):(''))
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
   			if (isset($this->domains) && count($this->domains)) {
   				$builder
	   				->add('domainId', 'choice', array(
						'label'=>'Company:',
  						'choices'=>$this->domains,
   						'data'=>((isset($this->qualification))?($this->qualification->getDomainId()):(''))
   				));
   			}
    }

    public function getName()
    {
        return 'qualification';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

 }