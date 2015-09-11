<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/CompanyType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class CompanyType extends AbstractType
{
	
	private $company;
	private $timezones;
	private $holidaycalculations;
	
	public function __construct($company, $timezones, $holidaycalculations)
	{
		$this->company = $company;
		$this->timezones = $timezones;
		$this->holidaycalculations = $holidaycalculations;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('id', 'hidden', array(
    			'data'=>((isset($this->company))?($this->company->getId()):(''))
    		))
        	->add('companyname', 'text', array(
    			'label'=>'Company Name:',
        		'required'=>true,
    			'data'=>((isset($this->company))?($this->company->getCompanyname()):(''))
    		))
    		->add('domain', 'text', array(
    			'label'=>'Domain:',
    			'required'=>true,
    			'data'=>((isset($this->company))?($this->company->getDomain()):(''))
    		))
    		->add('timezone', 'choice', array(
        		'choices'=>$this->timezones,
    			'label'=>'Timezone:',
        		'required'=>true,
    			'empty_value'=>' - Please select - ',
    			'data'=>((isset($this->company))?($this->company->getTimezone()):(''))
    		))
    		->add('yearstart', 'date', array(
    			'label'=>'Year start (Year not used):',
    			'widget'=>'single_text',
    			'format'=>'dd/MM/y',
    			'required'=>true,
    			'attr'=>array(
    				'class'=>'dateInput'
    			),
    			'data'=>((isset($this->company))?($this->company->getYearstart()):(''))
    		))
        	->add('ahe', 'number', array(
    			'label'=>'Annual Holidays (days):',
        		'required'=>false,
        		'data'=>((isset($this->company))?($this->company->getAHE()):(''))
    		))
        	->add('ahew', 'number', array(
    			'label'=>'Annual Holidays (weeks):',
        		'required'=>false,
        		'data'=>((isset($this->company))?($this->company->getAHEW()):(''))
    		))
        	->add('hct', 'choice', array(
    			'label'=>'Holiday Calculation Type:',
        		'choices'=>$this->holidaycalculations,
        		'required'=>false,
        		'empty_value'=>' - Please select - ',
        		'data'=>((isset($this->company))?($this->company->getHCT()):(''))
    		))
        	->add('lunchtime', 'number', array(
    			'label'=>'Paid Lunchtime (minutes):',
        		'required'=>false,
        		'data'=>((isset($this->company))?($this->company->getLunchtime()):(''))
    		))
        	->add('lunchtimeUnpaid', 'number', array(
    			'label'=>'Unpaid Lunchtime (minutes):',
        		'required'=>false,
        		'data'=>((isset($this->company))?($this->company->getLunchtimeUnpaid()):(''))
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
        return 'company';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
    
 }