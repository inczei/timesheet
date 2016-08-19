<?php
namespace Timesheet\Bundle\HrBundle\EventListener;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ExceptionListener
{
	
	protected $router;
	
	public function __construct(UrlGeneratorInterface $router) {
		$this->router = $router;
	}
	
	
	public function onKernelException(GetResponseForExceptionEvent $event)
	{
		$exception = $event->getException();
	    if ($exception instanceof NotFoundHttpException) {
	    	$url=$this->router->generate('timesheet_hr_homepage');
			$response = new RedirectResponse($url);
			$event->setResponse($response);
	    }
	}
}