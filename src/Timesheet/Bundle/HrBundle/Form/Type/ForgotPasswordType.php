<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/ForgotPasswordType.php
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

class ForgotPasswordType extends AbstractType
{
	
	public function __construct()
	{
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('username', 'text', array(
    			'label'=>'Username:',
    			'constraints'=>array(
    				new NotBlank(),
    				new Length(array('min'=>4))
    			)
    		))
    		->add('email', 'email', array(
    			'label'=>'E-mail:',
    			'constraints'=>array(
    				new NotBlank(),
    				new Length(array('min'=>5))
    			)
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
        return 'forgotpassword';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

}