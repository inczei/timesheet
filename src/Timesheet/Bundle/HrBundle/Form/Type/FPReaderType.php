<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/FPReaderType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class FPReaderType extends AbstractType
{
	
	private $fpreader;
	private $locations;
	private $companies;
	
	public function __construct($fpreader, $locations, $companies)
	{
		$this->fpreader = $fpreader;
		$this->locations = $locations;
		$this->companies = $companies;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('id', 'hidden', array(
    			'data'=>((isset($this->fpreader))?($this->fpreader->getId()):(''))
    		))
        	->add('ipAddress', 'text', array(
    			'label'=>'IP Address:',
        		'required'=>true,
    			'data'=>((isset($this->fpreader))?($this->fpreader->getIpAddress()):(''))
    		))
        	->add('port', 'text', array(
    			'label'=>'Port:',
        		'required'=>true,
    			'data'=>((isset($this->fpreader))?($this->fpreader->getPort()):(''))
    		))
        	->add('deviceId', 'text', array(
    			'label'=>'Device ID:',
        		'required'=>true,
    			'data'=>((isset($this->fpreader))?($this->fpreader->getDeviceId()):(''))
    		))
        	->add('password', 'text', array(
    			'label'=>'Password:',
        		'required'=>false,
    			'data'=>((isset($this->fpreader))?($this->fpreader->getPassword()):(''))
    		))
    		->add('deviceName', 'text', array(
    			'label'=>'Device Name:',
        		'required'=>false,
    			'data'=>((isset($this->fpreader))?($this->fpreader->getDeviceName()):(''))
    		))
        	->add('status', 'choice', array(
        		'choices'=>array('0'=>'Inactive', '1'=>'Active'),
    			'label'=>'Status:',
        		'required'=>true,
        		'empty_value'=>' - Please select - ',
    			'data'=>((isset($this->fpreader))?($this->fpreader->getStatus()):(''))
    		))
    		->add('locationId', 'choice', array(
	    			'choices'=>$this->locations,
	    			'label'=>'Location:',
	    			'required'=>true,
	    			'empty_value'=>' - Please select - ',
	    			'data'=>((isset($this->fpreader))?($this->fpreader->getLocationId()):(''))
    		))
    		->add('comment', 'text', array(
    			'label'=>'Comment:',
        		'required'=>false,
    			'data'=>((isset($this->fpreader))?($this->fpreader->getComment()):(''))
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
   		if (isset($this->companies)) {
   			$builder
    			->add('domainId', 'choice', array(
	    			'choices'=>$this->companies,
	    			'label'=>'Domain:',
	    			'required'=>true,
	    			'empty_value'=>' - Please select - ',
	    			'data'=>((isset($this->fpreader))?($this->fpreader->getDomainId()):(''))
    		));
   		}
    }

    public function getName()
    {
        return 'fpreader';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
    
 }