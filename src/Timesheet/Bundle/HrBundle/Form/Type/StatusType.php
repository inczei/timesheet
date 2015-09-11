<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/StatusType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class StatusType extends AbstractType
{
	
	private $status;
	private $colors;
	private $levels;
	
	
	public function __construct($status, $colors, $levels)
	{
		$this->status = $status;
		$this->colors = $colors;
		$this->levels = $levels;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('id', 'hidden', array(
    			'data'=>((isset($this->status))?($this->status['id']):(''))
    		))
        	->add('nameStart', 'text', array(
    			'label'=>'Name (start):',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>50))
    			),
    			'data'=>((isset($this->status))?($this->status['nameStart']):(''))
    		))
    		->add('nameFinish', 'text', array(
    			'label'=>'Name (finish):',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>50))
    			),
    			'data'=>((isset($this->status))?($this->status['nameFinish']):(''))
    		))
    		->add('active', 'choice', array(
    			'choices'=>array('0'=>'inactive', '1'=>'active'),
    			'label'=>'Status:',
    			'data'=>((isset($this->status))?($this->status['active']):(''))
    		))
    		->add('level', 'choice', array(
    			'choices'=>$this->levels,
    			'label'=>'Level:',
    			'data'=>((isset($this->status))?($this->status['level']):(''))
    		))
    		->add('multi', 'choice', array(
    			'choices'=>array(0=>'Single', 1=>'Multi'),
    			'label'=>'Multi:',
    			'data'=>((isset($this->status))?($this->status['multi']):(''))
    		))
    		->add('color', 'choice', array(
    			'choices'=>$this->colors,
    			'label'=>'Level:',
    			'data'=>((isset($this->status))?($this->status['color']):(''))
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
    }

    public function getName()
    {
        return 'status';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
    
 }