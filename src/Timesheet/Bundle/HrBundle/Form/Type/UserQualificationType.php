<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/UserQualificationType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class UserQualificationType extends AbstractType
{
	
	private $qualification;
	private $level;
	private $user;
	private $list;
	
	
	public function __construct($qualifications, $levels, $user, $list)
	{
		$this->qualifications = $qualifications;
		$this->levels = $levels;
		$this->user = $user;
		$this->list = $list;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('userId', 'hidden', array(
    			'data'=>((isset($this->user))?($this->user->getId()):(''))
    		))
    		->add('qualificationId', 'choice', array(
    			'choices'=>array(''=>' - Please select - ')+$this->qualifications,
    			'label'=>'Qualification:',
    			'required'=>true,
    			'data'=>''
    		))
    		->add('levelId', 'choice', array(
    			'choices'=>array(''=>' - Please select - ')+$this->levels,
    			'label'=>'Level:',
    			'required'=>true,
    			'data'=>''
    		))
    		->add('achievementDate', 'date', array(
    			'label'=>'Achievement Date:',
    			'widget'=>'single_text',
    			'format'=>'dd/MM/yyyy',
    			'required'=>true,
    			'attr'=>array(
    				'class'=>'dateInput'
    			),
    			'data'=>new \DateTime()
    		))
    		->add('expiryDate', 'date', array(
    			'label'=>'Expiry Date:',
    			'widget'=>'single_text',
    			'format'=>'dd/MM/yyyy',
    			'required'=>false,
    			'attr'=>array(
    				'class'=>'dateInput'
    			),
    			'data'=>new \DateTime()
    		))
    		->add('comments', 'text', array(
    			'label'=>'Comments:',
    			'required'=>false,
    			'data'=>''
    		))
    		->add('submit', 'submit', array(
    			'label'=>'Add',
    			'attr'=>array('class'=>'submitButton')
    		))
   			->add('cancel', 'submit', array(
   				'label'=>'Ready',
   				'attr'=>array('formnovalidate'=>true),
    			'validation_groups'=>false
    		));
// error_log('list:'.print_r($this->list, true));
       	if (count($this->list)) {
   			foreach ($this->list as $l) {
   				$builder->add('delete_'.$l['uqId'], 'checkbox', array(
   					'required'=>false,
   					'attr'=>array(
   						'qid'=>$l['uqId'],
   					)
   				));
   			}
   			$builder->add('delete', 'submit', array(
   					'label'=>'Delete selected',
   					'attr'=>array(
   						'formnovalidate'=>true
   					),
   					'validation_groups'=>false
   			));
   			
   		}
    }

    public function getName()
    {
        return 'userqualification';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

 }