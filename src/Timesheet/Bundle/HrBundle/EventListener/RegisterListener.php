<?php
// src/Timesheet/Bundle/HrBundle/EventListener/RegisterListener.php

namespace Timesheet\Bundle\HrBundle\EventListener;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Listener responsible to change the redirection at the end of the password resetting
 */
class RegisterListener implements EventSubscriberInterface
{
    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::REGISTER_SUCCESS => 'onRegisterSuccess',
            FOSUserEvents::REGISTER_FAILED => 'onRegisterFailed',
        );
    }

    public function onRegisterSuccess(FormEvent $event)
    {
error_log('register success...redirecting');
        $url = $this->router->generate('timesheet_hr_homepage');

        $event->setResponse(new RedirectResponse($url));
    }

    public function onRegisterFailed(FormEvent $event)
    {
error_log('register failed...try again');
        $url = $this->router->generate('timesheet_hr_register');

        $event->setResponse(new RedirectResponse($url));
    }
}
