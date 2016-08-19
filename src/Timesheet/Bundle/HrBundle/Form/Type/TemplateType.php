<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/TemplateType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class TemplateType extends AbstractType
{
	
	private $template;
	private $pages;
	private $formats;
	
	public function __construct($template, $pages, $formats)
	{
		$this->template = $template;
		$this->pages = $pages;
		$this->formats = $formats;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('id', 'hidden', array(
    			'data'=>((isset($this->template))?($this->template->getId()):(''))
    		))
    		->add('domainId', 'hidden', array(
    			'label'=>'',
    			'required'=>false,
    			'data'=>((isset($this->template))?($this->template->getDomainId()):(''))
    		))
    		->add('pageId', 'choice', array(
    			'choices'=>$this->pages,
    			'label'=>'Page:',
    			'required'=>true,
    			'data'=>((isset($this->template))?($this->template->getPageId()):(''))
    		))
    		->add('name', 'text', array(
    			'label'=>'Template Name:',
        		'required'=>true,
    			'data'=>((isset($this->template))?($this->template->getName()):(''))
    		))
    		->add('comment', 'text', array(
    			'label'=>'Comment:',
        		'required'=>false,
    			'data'=>((isset($this->template))?($this->template->getComment()):(''))
    		))
    		->add('format', 'choice', array(
    			'choices'=>$this->formats,
    			'label'=>'Format:',
        		'required'=>true,
    			'empty_value'=>' - Please select - ',
    			'data'=>((isset($this->template))?($this->template->getFormat()):(''))
    		))
    		->add('heading', 'choice', array(
    			'choices'=>array('0'=>'No', '1'=>'Yes'),
    			'label'=>'Heading:',
        		'required'=>true,
    			'empty_value'=>' - Please select - ',
    			'data'=>((isset($this->template))?($this->template->getHeading()):(''))
    		))
    		->add('available', 'choice', array(
    			'choices'=>array('0'=>'Hidden', '1'=>'Available'),
    			'label'=>'Status:',
        		'required'=>true,
    			'empty_value'=>' - Please select - ',
    			'data'=>((isset($this->template))?($this->template->getAvailable()):(''))
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
        return 'template';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
    
 }