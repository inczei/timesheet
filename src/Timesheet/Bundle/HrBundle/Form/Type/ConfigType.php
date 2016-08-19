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
	private $defaults;
	
	public function __construct($data, $timezones, $hct, $defaults=null)
	{
		$this->data = $data;
		$this->timezones = $timezones;
		$this->hct = $hct;
		$this->defaults = $defaults;
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
    			'label'=>'Annual Holiday Entitlement (weeks):'.((isset($this->defaults) && $this->defaults['ahew'])?(' (def.:'.$this->defaults['ahew'].')'):('')),
    			'constraints'=>array(
    				new NotBlank()
    			),
        		'data'=>$this->data['ahew']
    		))
        	->add('minhoursforlunch', 'number', array(
    			'label'=>'Minimum working hours for lunch time (hours):'.((isset($this->defaults) && $this->defaults['minhoursforlunch'])?(' (def.:'.$this->defaults['minhoursforlunch'].')'):('')),
        		'data'=>$this->data['minhoursforlunch']
    		))
    		->add('lunchtime', 'number', array(
    			'label'=>'Paid lunchtime (minutes):'.((isset($this->defaults) && $this->defaults['lunchtime'])?(' (def.:'.$this->defaults['lunchtime'].')'):('')),
        		'data'=>$this->data['lunchtime']
    		))
        	->add('lunchtimeUnpaid', 'number', array(
    			'label'=>'Unpaid lunchtime (minutes):'.((isset($this->defaults) && $this->defaults['lunchtimeUnpaid'])?(' (def.:'.$this->defaults['lunchtimeUnpaid'].')'):('')),
        		'data'=>$this->data['lunchtimeUnpaid']
    		))
        	->add('autologout', 'number', array(
    			'label'=>'Auto logout (minutes):'.((isset($this->defaults) && $this->defaults['autologout'])?(' (def.:'.$this->defaults['autologout'].')'):('')),
        		'data'=>$this->data['autologout'],
        		'required'=>false
    		))
        	->add('grace', 'number', array(
    			'label'=>'Grace period (minutes):'.((isset($this->defaults) && $this->defaults['grace'])?(' (def.:'.$this->defaults['grace'].')'):('')),
        		'data'=>$this->data['grace'],
        		'required'=>false
    		))
        	->add('rounding', 'number', array(
    			'label'=>'Rounding (minutes):'.((isset($this->defaults) && $this->defaults['rounding'])?(' (def.:'.$this->defaults['rounding'].')'):('')),
        		'data'=>$this->data['rounding'],
        		'required'=>false
    		))
    		->add('yearstart', 'date', array(
    			'label'=>'Year Start:'.((isset($this->defaults) && $this->defaults['yearstart'])?(' (def.:'.$this->defaults['yearstart'].')'):('')),
    			'widget'=>'single_text',
   				'format'=>'dd/MM/yyyy',
    			'attr'=>array(
    				'class'=>'dateInput'
    			),
        		'data'=>$this->data['yearstart']
    		))
    		->add('hct', 'choice', array(
    			'label'=>'Holiday Calculation Type:'.((isset($this->defaults) && $this->defaults['hct'] && isset($this->hct[$this->defaults['hct']]))?(' (def.:'.$this->hct[$this->defaults['hct']].')'):('')),
    			'choices'=>$this->hct,
    			'required'=>true,
    			'empty_value'=>' - Please select - ',
    			'data'=>$this->data['hct']
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
    		
    }


    public function getName()
    {
        return 'config';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

}