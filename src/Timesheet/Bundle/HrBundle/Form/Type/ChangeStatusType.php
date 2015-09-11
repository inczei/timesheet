<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/ChangeStatusType.php
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

class ChangeStatusType extends AbstractType
{

	protected $groups;
	
	public function __construct($groups)
	{
		$this->groups=$groups;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('all', 'choice', array(
    			'choices'=>array(0=>'Only present', 1=>'All'),
    			'label'=>'Status',
    			'required'=>true
    		))
    		->add('group', 'choice', array(
    			'choices'=>$this->groups,
    			'label'=>'Group',
    			'required'=>true
    		))
    		->add('search', 'submit');
        
    }


    public function getName()
    {
        return 'changestatus';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

}