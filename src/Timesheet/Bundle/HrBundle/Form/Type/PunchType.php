<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/PunchType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;
use Timesheet\Bundle\HrBundle\Entity\Status;

class PunchType extends AbstractType
{
	
	private $statuslist;
	
	public function __construct($statuslist)
	{
		$this->statuslist = $statuslist;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('status', 'choice', array(
    			'choices'=>array(''=>'Please select')+$this->statuslist,
    			'label'=>'Status:',
    			'data'=>''
    		))
   			->add('uname', 'text', array(
    			'label'=>'Username:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2))
    			),
   				'data'=>'',
   				'max_length'=>50,
   				'attr'=>array('autocomplete'=>'off')
    		))
	    	->add('upass', 'password', array(
    			'label'=>'Password:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>4))
    			),
	    		'data'=>'',
	    		'max_length'=>50,
   				'attr'=>array('autocomplete'=>'off')
	    	))
   			->add('comment', 'text', array(
    			'label'=>'Comment:',
   				'required'=>false,
    			'constraints'=>array(
   					new Length(array('max'=>100))
    			),
   				'data'=>'',
   				'max_length'=>100,
   				'attr'=>array('autocomplete'=>'off')
    		))
	    	->add('submit', 'submit', array(
	    		'label'=>'Submit',
	    		'attr'=>array('class'=>'submitButton')
	    	));

    }

    public function getName()
    {
        return 'punch';
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

}