<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/ModuleType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class ModuleType extends AbstractType
{
	
	private $module;
	private $companies;
	private $selectedCompanies;
	
	
	public function __construct($module, $companies, $selectedCompanies)
	{
		$this->module = $module;
		$this->companies = $companies;
		$this->selectedCompanies = $selectedCompanies;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('id', 'hidden', array(
    			'data'=>((isset($this->module))?($this->module->getId()):(''))
    		))
        	->add('name', 'text', array(
    			'label'=>'Name:',
    			'constraints'=>array(
   					new NotBlank(),
   					new Length(array('min'=>2, 'max'=>50))
    			),
    			'data'=>((isset($this->module))?($this->module->getName()):(''))
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
   			
   		if (isset($this->companies) && count($this->companies)) {
			$builder->add('companies', 'choice', array(
				'choices'=>$this->companies,
				'label'=>'Companies:',
				'multiple'=>true,
				'expanded'=>true,
				'required'=>false,
				'data'=>$this->selectedCompanies
			));
   		}
    }

    public function getName()
    {
        return 'module';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
    
 }