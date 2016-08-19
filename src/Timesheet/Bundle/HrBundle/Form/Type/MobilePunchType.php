<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/MobilePunchType.php
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

class MobilePunchType extends AbstractType
{
	
	private $available = array();
	
	public function __construct($available)
	{
		$this->available = $available;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder
    		->add('latitude', 'hidden', array(
    			'data'=>'',
    			'required'=>false
    		))
    		->add('longitude', 'hidden', array(
    			'data'=>'',
    			'required'=>false
    		))
    		->add('comment', 'text', array(
    			'label'=>'Comment : ',
    			'required'=>false,
    			'attr'=>array(
    				'style'=>'font-size: x-large; width: 40%;'
    			)
    	));
    	if (isset($this->available) && is_array($this->available) && count($this->available)) {
    		foreach ($this->available as $k=>$v) {
    	    	$builder
   					->add('status_'.$k, 'submit', array(
   						'label'=>$v['name'],
   						'attr'=>array(
   							'style'=>'font-size: x-large; color: #'.$v['color'].'; width: 50%;'
   						)
   					));
    		}
    	}
    }


    public function getName()
    {
        return 'mobilepunch';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

}