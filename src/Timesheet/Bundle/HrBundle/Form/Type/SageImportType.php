<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/SageImportType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class SageImportType extends AbstractType
{
	
	private $userid;
	
	
	public function __construct($userid)
	{
		$this->userid = $userid;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        	->setAttribute('enctype', 'multipart/form-data')
    		->add('id', 'hidden', array(
    			'data'=>$this->userid
    		))
        	->add('file', 'file', array(
    			'label'=>'XML file:',
        		'required'=>true,
        		'data'=>null
    		))
    		->add('submit', 'submit', array(
    			'label'=>'Upload',
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
        return 'sageimport';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
    
 }