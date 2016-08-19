<?php
// src/Timesheet/Bundle/HrBundle/Form/Type/FPRUserAllocationType.php
namespace Timesheet\Bundle\HrBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class FPRUserAllocationType extends AbstractType
{
	
	private $fpreaders;
	private $fpreaderNames;
	private $readerUsers;
	private $localId;
	private $actionUrl;
	private $fpuser = array();
	
	public function __construct($fpreaders, $readerUsers, $fpuser, $fpreaderNames, $localId, $actionUrl)
	{
		$this->fpreaders = $fpreaders;
		$this->fpreaderNames = $fpreaderNames;
		$this->readerUsers = $readerUsers;
		if (isset($fpuser) && is_array($fpuser) && count($fpuser)) {
			foreach ($fpuser as $fpu) {
				$this->fpuser[$fpu['id']]=$fpu['readerUserId'];
			}
		}
		$this->localId = $localId;
		$this->actionUrl = $actionUrl;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
    		->add('localId', 'hidden', array(
    			'data'=>$this->localId
    		))
        	->add('submit', 'submit', array(
    			'label'=>'Save',
    			'attr'=>array('class'=>'submitButton')
    		));
   		if (isset($this->fpreaders) && is_array($this->fpreaders) && count($this->fpreaders)) {
   			foreach ($this->fpreaders as $fpr) {
	   			$builder
	    			->add('user'.$fpr['id'], 'choice', array(
		    			'choices'=>((isset($this->readerUsers[$fpr['id']]))?($this->readerUsers[$fpr['id']]):(array())),
		    			'label'=>'User at '.((isset($this->fpreaderNames[$fpr['id']]))?($this->fpreaderNames[$fpr['id']]):('reader '.$fpr['id'])),
		    			'required'=>false,
		    			'empty_value'=>' - Please select - ',
		    			'data'=>((isset($this->fpuser[$fpr['id']]))?($this->fpuser[$fpr['id']]):(null))
	    		));
   			}
   		}
   		if ($this->actionUrl) {
   			$builder->setAction($this->actionUrl);
   		}
    }

    public function getName()
    {
        return 'fpruserallocation';
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
    
 }