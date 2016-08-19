<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/GroupType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class GroupType extends AbstractType
{
	
	private $group;
	private $domains;
	
	
	public function __construct($group, $domains)
	{
		$this->group = $group;
		$this->domains = $domains;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('id', 'hidden', array(
    			'label'=>'ID:',
    			'data'=>((isset($this->group))?($this->group->getId()):(''))
    		))
        	->add('name', 'text', array(
    			'label'=>'Name:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>50))
    			),
    			'data'=>((isset($this->group))?($this->group->getName()):(''))
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
   					'data'=>((isset($this->group))?($this->group->getDomainId()):(''))
   				));
   		}
    }

    public function getName()
    {
        return 'group';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

 }