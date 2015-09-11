<?php
// src/Timesheet/Bundle/HrBundle/EventListener/LoginListener.php

namespace Timesheet\Bundle\HrBundle\EventListener;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Listener responsible to change the redirection at the end of the password resetting
 */
class LoginListener implements EventSubscriberInterface
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
            FOSUserEvents::LOGIN_SUCCESS => 'onLoginSuccess',
            FOSUserEvents::LOGIN_FAILED => 'onLoginFailed',
        );
    }

    public function onLoginSuccess(FormEvent $event)
    {
error_log('login success...redirecting');
        $url = $this->router->generate('timesheet_hr_homepage');

        $event->setResponse(new RedirectResponse($url));
    }

    public function onLoginFailed(FormEvent $event)
    {
error_log('login failed...try again');
        $url = $this->router->generate('timesheet_hr_login');

        $event->setResponse(new RedirectResponse($url));
    }
}
