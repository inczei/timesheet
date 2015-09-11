<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/ResetPasswordType.php
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

class ResetPasswordType extends AbstractType
{

	protected $link;
	
	public function __construct($link)
	{
		$this->link = $link;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('link', 'hidden', array(
    			'label'=>'Link:',
    			'constraints'=>array(
    				new NotBlank(),
    			),
    			'data'=>$this->link
    		))
        	->add('username', 'text', array(
    			'label'=>'Username:',
    			'constraints'=>array(
    				new NotBlank(),
    				new Length(array('min'=>4))
    			)
    		))
    		->add('password', 'repeated', array(
		    	'type'=>'password',
	    		'constraints'=>array(
	   				new NotBlank(),
	   				new Length(array('min'=>4, 'max'=>20))
	    		),
		    	'required' => true,
			    'first_options'  => array('label' => 'Password'),
			    'second_options' => array('label' => 'Repeat Password'),
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
        return 'resetpassword';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

}