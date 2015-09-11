<?php

namespace Timesheet\Bundle\HrBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;


/**
 * @author Matt Drollette <matt@drollette.com>
 */
class LocaleExtension extends \Twig_Extension
{
    protected $container;
    protected $timezone;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->timezone = $this->container->get('session')->get('timezone', 'UTC');
    }

    public function getFunctions()
    {
        return array();
    }

    public function getFilters()
    {
        return array(
            'localtime' => new \Twig_Filter_Method($this, 'formatDatetime', array('is_safe' => array('html'))),
        );
    }

    public function formatDatetime($date, $timezone = null)
    {
    	if (null === $timezone) {
// error_log('input timezone is null, saved timezone:'.$this->timezone);
            $timezone = $this->timezone;
        }
// error_log('timezone:'.$timezone);
        if (!$date instanceof \DateTime) {
            if (ctype_digit((string) $date)) {
                $date = new \DateTime('@'.$date, new \DateTimeZone('UTC'));
            } else {
                $date = new \DateTime($date, new \DateTimeZone('UTC'));
            }
        }

        if (!$timezone instanceof \DateTimeZone) {
// error_log('not DateTimeZone');
            $timezone = new \DateTimeZone($timezone);
        }
// error_log('destination timezone:'.print_r($timezone, true));
// error_log('date before:'.print_r($date, true));
        $date->setTimezone($timezone);
// error_log('date after:'.print_r($date, true));
        return $date;
    }

    public function getName()
    {
        return 'app_extension';
    }
}