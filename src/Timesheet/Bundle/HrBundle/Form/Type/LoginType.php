<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/LoginType.php
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

class LoginType extends AbstractType
{
	
	public function __construct()
	{
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('uname', 'text', array(
    			'label'=>'Username:',
    			'constraints'=>array(
    				new NotBlank(),
    				new Length(array('min'=>4))
    			)
    		))
    		->add('upass', 'password', array(
    			'label'=>'Password:',
    			'constraints'=>array(
    				new NotBlank(),
    				new Length(array('min'=>4))
    			)
    		))
    		->add('submit', 'submit');

    }


    public function getName()
    {
        return 'login';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

}