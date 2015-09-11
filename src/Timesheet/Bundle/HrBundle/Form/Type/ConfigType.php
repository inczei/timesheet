<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/ConfigType.php
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

class ConfigType extends AbstractType
{
	private $data;
	private $timezones;
	private $hct;
	
	public function __construct($data, $timezones, $hct)
	{
		$this->data = $data;
		$this->timezones = $timezones;
		$this->hct = $hct;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('companyname', 'text', array(
    			'label'=>'Company name:',
    			'constraints'=>array(
    				new NotBlank(),
    				new Length(array('min'=>4))
    			),
    			'data'=>$this->data['companyname']
    		))
        	->add('domain', 'text', array(
    			'label'=>'Domain:',
    			'constraints'=>array(
    				new NotBlank(),
    				new Length(array('min'=>4))
    			),
        		'data'=>$this->data['domain']
    		))
    		->add('timezone', 'choice', array(
    			'label'=>'Timezone:',
    			'choices'=>$this->timezones,
    			'data'=>$this->data['timezone']
    		))
        	->add('ahew', 'text', array(
    			'label'=>'Annual Holiday Entitlement (weeks):',
    			'constraints'=>array(
    				new NotBlank()
    			),
        		'data'=>$this->data['ahew']
    		))
        	->add('lunchtime', 'number', array(
    			'label'=>'Paid lunchtime (minutes):',
        		'data'=>$this->data['lunchtime']
    		))
        	->add('lunchtimeUnpaid', 'number', array(
    			'label'=>'Unpaid lunchtime (minutes):',
        		'data'=>$this->data['lunchtimeUnpaid']
    		))
    		->add('yearstart', 'date', array(
    			'label'=>'Year Start:',
    			'widget'=>'single_text',
   				'format'=>'dd/MM/yyyy',
    			'attr'=>array(
    				'class'=>'dateInput'
    			),
        		'data'=>$this->data['yearstart']
    		))
    		->add('hct', 'choice', array(
    			'label'=>'Holiday Calculation Type:',
    			'choices'=>$this->hct,
    			'required'=>true,
    			'empty_value'=>' - Please select - ',
    			'data'=>$this->data['hct']
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
        return 'config';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

}