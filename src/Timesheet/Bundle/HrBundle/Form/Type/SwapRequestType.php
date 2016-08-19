<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/SwapRequestType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class SwapRequestType extends AbstractType
{
	
	private $actionUrl;
	private $userUrl;
	private $usernames1;
	private $usernames2;
	private $shifts1;
	private $shifts2;
	private $date;
	
	
	public function __construct($usernames1, $usernames2, $shifts1, $shifts2, $date, $actionUrl, $userUrl)
	{
		$this->actionUrl = $actionUrl;
		$this->userUrl = $userUrl;
		$this->usernames1 = $usernames1;
		$this->usernames2 = $usernames2;
		$this->shifts1 = $shifts1;
		$this->shifts2 = $shifts2;
		$this->date = $date;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        	->add('userId1', 'choice', array(
   				'choices'=>$this->usernames1,
   				'label'=>'User:',
   				'required'=>true,
   				'empty_value'=>'Please select',
   				'data'=>'',
        		'attr'=>array(
        			'class'=>'swapUser1',
        			'data-action'=>$this->userUrl
        		)   					
   			))
   			->add('userId2', 'choice', array(
   				'choices'=>$this->usernames2,
   				'label'=>'User:',
   				'required'=>true,
   				'empty_value'=>'Please select',
   				'data'=>'',
   				'attr'=>array(
   					'class'=>'swapUser2',
   					'data-action'=>$this->userUrl
   				)
   					
   			))
   			->add('shiftId1', 'choice', array(
   				'choices'=>$this->shifts1,
   				'label'=>'Shift:',
   				'required'=>true,
   				'empty_value'=>'Please select',
   				'data'=>'',
   				'attr'=>array(
   					'class'=>'swapShift1',
   					'data-action'=>$this->userUrl
   				)
   			))
   			->add('shiftId2', 'choice', array(
   				'choices'=>$this->shifts2,
   				'label'=>'Shift:',
   				'required'=>true,
   				'empty_value'=>'Please select',
   				'data'=>'',		
        		'attr'=>array(
        			'class'=>'swapShift2',
        			'data-action'=>$this->userUrl
        		)   					
   			))
   			->add('comment', 'textarea', array(
    			'label'=>'Comment:',
    			'required'=>false,
    			'attr'=>array(
    				'rows'=>3,
    				'cols'=>50
    			),
    			'data'=>''
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
   			
   		if ($this->actionUrl) {
   			$builder->setAction($this->actionUrl);
   		}
    }

    public function getName()
    {
        return 'swaprequest';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
    
 }