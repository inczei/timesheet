<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/QualRequirementsType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class QualRequirementsType extends AbstractType
{
	
	private $req;
	private $locations;
	private $qualifications;
	private $shiftId;
	private $list;
	
	
	public function __construct($req, $qualifications, $list, $shiftId=null)
	{
		$this->req = $req;
		$this->qualifications = $qualifications;
		$this->shiftId = $shiftId;
		$this->list = $list;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('id', 'hidden', array(
    			'data'=>((isset($this->req))?($this->req->getId()):(''))
    		))
    		->add('shiftId', 'hidden', array(
    			'data'=>$this->shiftId
    		))
    		->add('qualificationId', 'choice', array(
        		'choices'=>array('0'=>'Please select')+$this->qualifications,
    			'label'=>'Qualification:',
        		'required'=>true,
        		'read_only'=>true,
    			'data'=>((isset($this->req))?($this->req->getQualificationId()):(''))
    		))
    		->add('numberOfStaff', 'text', array(
    			'label'=>'Number of staff:',
    			'required'=>true,
    			'data'=>((isset($this->req))?($this->req->getNumberOfStaff()):(''))
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
   		if (count($this->list)) {
   			foreach ($this->list as $l) {
   				$builder->add('delete_'.$l['qualificationId'], 'checkbox', array(
   					'required'=>false,
   					'attr'=>array(
   						'qid'=>$l['qualificationId']
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
        return 'qualreqirements';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
    
 }