<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/IdentifyConfirmType.php
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

class IdentifyConfirmType extends AbstractType
{
	
	private $ma = '';
	
	public function __construct($ma)
	{
		$this->ma = $ma;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        	->add('deviceId', 'hidden', array(
        		'data'=>$this->ma->getDeviceId()
        	))
   			->add('code', 'text', array(
   				'label'=>'Activation code'
   			))
   			->add('submit', 'submit');
    }


    public function getName()
    {
        return 'identifyconfirm';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

}