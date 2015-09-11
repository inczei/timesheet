<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/TimesheetCheckType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class TimesheetCheckType extends AbstractType
{
	
	private $username;
	private $date;
	private $comment;
	
	
	public function __construct($username, $date, $comment)
	{
		$this->username = $username;
		$this->date = $date;
		$this->comment = $comment;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        	->add('username', 'text', array(
   				'label'=>'User:',
   				'required'=>true,
        		'read_only'=>true,
   				'data'=>$this->username,
   			))
   			->add('date', 'date', array(
   				'label'=>'Date:',
    			'widget'=>'single_text',
    			'format'=>'dd/MM/yyyy',
   				'required'=>true,
   				'read_only'=>true,
   				'data'=>$this->date,		
   			))
   			->add('comment', 'textarea', array(
    			'label'=>'Comment:',
    			'required'=>false,
    			'attr'=>array(
    				'rows'=>3,
    				'cols'=>50
    			),
    			'data'=>$this->comment
    		));
    }

    public function getName()
    {
        return 'timesheetcheck';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
    
 }