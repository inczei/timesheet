<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/MessageType.php
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

class MessageType extends AbstractType
{
	private $recipients;
	private $message;
	
	public function __construct($message, $recipients)
	{
		$this->message = $message;
		$this->recipients = $recipients;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('id', 'hidden', array(
    			'label'=>'ID:',
    			'read_only'=>true,
    			'data'=>$this->message['id']
    		))
        	->add('sender', 'text', array(
    			'label'=>'Sender:',
    			'read_only'=>true,
    			'attr'=>array(
    				'style'=>'width: 400px'
    			),
    			'data'=>$this->message['sender']
    		))
    		->add('recipient', 'choice', array(
    			'label'=>'Recipient:',
    			'choices'=>$this->recipients,
    			'attr'=>array(
    				'style'=>'width: 400px'
    			),
    			'empty_value'=>' - Please select - ',
    			'data'=>$this->message['recipient']
    		))
    		->add('subject', 'text', array(
    			'label'=>'Subject:',
    			'constraints'=>array(
    				new NotBlank()
    			),
    			'attr'=>array(
    				'style'=>'width: 400px'
    			),
    			'data'=>$this->message['subject']
    		))
    		->add('content', 'ckeditor', array(
    			'label'=>'Content:',
    			'constraints'=>array(
    				new NotBlank()
    			),
    			'attr'=>array(
    				'style'=>'width: 400px',
    				'rows'=>5,
    				'cols'=>50
    			),
    			'data'=>$this->message['content']
    		))
    		->add('submit', 'submit', array(
    			'label'=>'Submit',
    			'attr'=>array('class'=>'submitButton')
    		))
   			->add('saveasdraft', 'submit', array(
   				'label'=>'Save as Draft',
   				'attr'=>array('formnovalidate'=>true),
    			'validation_groups'=>false
    		))
    		->add('cancel', 'submit', array(
   				'label'=>'Cancel',
   				'attr'=>array('formnovalidate'=>true),
    			'validation_groups'=>false
    		));
    		
    }


    public function getName()
    {
        return 'message';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

}