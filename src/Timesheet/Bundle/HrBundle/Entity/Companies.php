<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Companies
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Companies
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="domain", type="string", length=50, unique=true)
     */
    private $domain;
    
    /**
     * @var string
     *
     * @ORM\Column(name="companyname", type="string", length=50, unique=true)
     */
    private $companyname;

    /**
     * @var string
     *
     * @ORM\Column(name="timezone", type="string", length=50)
     */
    private $timezone;

    /**
     * @var date
     *
     * @ORM\Column(name="yearstart", type="date", nullable=true, options={"comment":"Used without year"})
     */
    private $yearstart;

    /**
     * @var integer
     *
     * @ORM\Column(name="ahe", type="integer", nullable=true, options={"comment":"Annual Holiday Entitlement in days"})
     */
    private $ahe;
    
    /**
     * @var float
     *
     * @ORM\Column(name="ahew", type="float", nullable=true, options={"comment":"Annual Holiday Entitlement in weeks"})
     */
    private $ahew;
    
    
    /**
     * @var integer
     *
     * @ORM\Column(name="hct", type="integer", options={"comment":"Holiday Calculation Type"})
     */
    private $hct = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="minhoursforlunch", type="integer", nullable=true, options={"comment":"Minimum working hours for lunch time"})
     */
    private $minhoursforlunch = 6;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="lunchtime", type="integer", nullable=true, options={"comment":"Paid lunch time in minutes"})
     */
    private $lunchtime;

    /**
     * @var integer
     *
     * @ORM\Column(name="lunchtimeUnpaid", type="integer", nullable=true, options={"comment":"Unpaid lunch time in minutes"})
     */
    private $lunchtimeUnpaid;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="rounding", type="integer", nullable=true, options={"comment":"Rounding minutes"})
     */
    private $rounding = 15;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="grace", type="integer", nullable=true, options={"comment":"Grace period in minutes"})
     */
    private $grace;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="autologout", type="integer", nullable=true, options={"comment":"Auto logout time in minutes"})
     */
    private $autologout;
    
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set domain
     *
     * @param string $domain
     * @return Companies
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return string 
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set companyname
     *
     * @param string $companyname
     * @return Companies
     */
    public function setCompanyname($companyname)
    {
        $this->companyname = $companyname;

        return $this;
    }

    /**
     * Get companyname
     *
     * @return string 
     */
    public function getCompanyname()
    {
        return $this->companyname;
    }
    
    /**
     * Set timezone
     *
     * @param string $timezone
     * @return Companies
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Get timezone
     *
     * @return string 
     */
    public function getTimezone()
    {
        return $this->timezone;
    }
    
    /**
     * Set yearstart
     *
     * @param date $yearstart
     * @return Companies
     */
    public function setYearstart($yearstart)
    {
        $this->yearstart = $yearstart;

        return $this;
    }

    /**
     * Get yearstart
     *
     * @return date
     */
    public function getYearstart()
    {
        return $this->yearstart;
    }
    
    /**
     * Set ahe
     *
     * @param integer $ahe
     * @return Companies
     */
    public function setAHE($ahe)
    {
        $this->ahe = $ahe;

        return $this;
    }

    /**
     * Get ahe
     *
     * @return integer
     */
    public function getAHE()
    {
        return $this->ahe;
    }
    
    /**
     * Set ahew
     *
     * @param float $ahew
     * @return Companies
     */
    public function setAHEW($ahew)
    {
        $this->ahew = $ahew;

        return $this;
    }

    /**
     * Get ahew
     *
     * @return float
     */
    public function getAHEW()
    {
        return $this->ahew;
    }
    
    /**
     * Set hct
     *
     * @param integer $hct
     * @return Companies
     */
    public function setHCT($hct)
    {
        $this->hct = $hct;

        return $this;
    }

    /**
     * Get hct
     *
     * @return integer
     */
    public function getHCT()
    {
        return $this->hct;
    }

    /**
     * Set minhoursforlunch
     *
     * @param integer $minhoursforlunch
     * @return Companies
     */
    public function setMinHoursForLunch($minhoursforlunch)
    {
        $this->minhoursforlunch = $minhoursforlunch;

        return $this;
    }

    /**
     * Get minhoursforlunch
     *
     * @return integer
     */
    public function getMinHoursForLunch()
    {
        return $this->minhoursforlunch;
    }
    
    /**
     * Set lunchtime
     *
     * @param integer $lunchtime
     * @return Companies
     */
    public function setLunchtime($lunchtime)
    {
        $this->lunchtime = $lunchtime;

        return $this;
    }

    /**
     * Get lunchtime
     *
     * @return integer
     */
    public function getLunchtime()
    {
        return $this->lunchtime;
    }

    /**
     * Set lunchtimeUnpaid
     *
     * @param integer $lunchtimeUnpaid
     * @return Companies
     */
    public function setLunchtimeUnpaid($lunchtimeUnpaid)
    {
        $this->lunchtimeUnpaid = $lunchtimeUnpaid;

        return $this;
    }

    /**
     * Get lunchtimeUnpaid
     *
     * @return integer
     */
    public function getLunchtimeUnpaid()
    {
        return $this->lunchtimeUnpaid;
    }
    
    /**
     * Set rounding
     *
     * @param integer $rounding
     * @return Companies
     */
    public function setRounding($rounding)
    {
        $this->rounding = $rounding;

        return $this;
    }

    /**
     * Get rounding
     *
     * @return integer
     */
    public function getRounding()
    {
        return $this->rounding;
    }
    
    /**
     * Set grace
     *
     * @param integer $grace
     * @return Companies
     */
    public function setGrace($grace)
    {
        $this->grace = $grace;

        return $this;
    }

    /**
     * Get grace
     *
     * @return integer
     */
    public function getGrace()
    {
        return $this->grace;
    }
    
    /**
     * Set autologout
     *
     * @param integer $autologout
     * @return Companies
     */
    public function setAutologout($autologout)
    {
        $this->autologout = $autologout;

        return $this;
    }

    /**
     * Get autologout
     *
     * @return integer
     */
    public function getAutologout()
    {
        return $this->autologout;
    }
}
