<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/PhotoType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class PhotoType extends AbstractType
{
	
	private $userid;
	private $name;
	private $notes;
	
	
	public function __construct($userid, $name, $notes)
	{
		$this->userid = $userid;
		$this->name = $name;
		$this->notes = $notes;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        	->setAttribute('enctype', 'multipart/form-data')
    		->add('id', 'hidden', array(
    			'data'=>$this->userid
    		))
        	->add('name', 'text', array(
    			'label'=>'Name:',
        		'required'=>true,
    			'data'=>$this->name
    		))
        	->add('file', 'file', array(
    			'label'=>'Photo file:',
        		'required'=>true,
        		'data'=>null
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
    			'label'=>'Upload',
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
        return 'photo';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
    
 }