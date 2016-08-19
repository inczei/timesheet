<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/PhotoNotesType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class PhotoNotesType extends AbstractType
{
	
	private $photoid;
	private $notes;
	private $createdOn;
	private $actionUrl;
	private $action;
	
	
	public function __construct($photoid, $notes, $createdOn, $action, $actionUrl=null)
	{
		$this->photoid = $photoid;
		$this->notes = $notes;
		$this->createdOn = $createdOn;
		$this->actionUrl = $actionUrl;
		$this->action = $action;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('photoid', 'hidden', array(
    			'data'=>$this->photoid
    		))
    		->add('action', 'hidden', array(
    			'data'=>$this->action
    		))
    		->add('createdOn', 'datetime', array(
    			'label'=>'Uploaded',
    			'widget'=>'single_text',
				'format'=>'dd/MM/yyyy HH:mm',    				
    			'read_only'=>true,
    			'required'=>false,
    			'data'=>$this->createdOn
    		))    		
        	->add('notes', 'textarea', array(
    			'label'=>'Notes:',
    			'required'=>false,
        		'attr'=>array(
        			'rows'=>3,
        			'cols'=>70
        		),
    			'data'=>$this->notes
    		))
    		->add('submit', 'submit', array(
    			'label'=>'Update',
    			'attr'=>array('class'=>'submitButton')
    		))
    		->add('cancel', 'button', array(
   				'label'=>'Cancel',
   				'attr'=>array('formnovalidate'=>true),
//    			'validation_groups'=>false
    		));
    	if ($this->actionUrl) {
error_log('actionUrl:'.$this->actionUrl);
    		$builder->setAction($this->actionUrl);
    	}
    		
    }

    public function getName()
    {
        return 'photonotes';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
    
 }