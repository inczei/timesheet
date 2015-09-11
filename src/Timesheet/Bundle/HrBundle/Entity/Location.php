<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Location
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Location
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
     * @ORM\Column(name="Name", type="string", length=50)
     */
    private $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Active", type="boolean")
     */
    private $active = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="FixedIpAddress", type="boolean")
     */
    private $fixedIpAddress = true;

    /**
     * @var string
     *
     * @ORM\Column(name="AddressLine1", type="string", length=255)
     */
    private $addressLine1;

    /**
     * @var string
     *
     * @ORM\Column(name="AddressLine2", type="string", length=255)
     */
    private $addressLine2;

    /**
     * @var string
     *
     * @ORM\Column(name="AddressCity", type="string", length=255)
     */
    private $addressCity;

    /**
     * @var string
     *
     * @ORM\Column(name="AddressCounty", type="string", length=255)
     */
    private $addressCounty;

    /**
     * @var string
     *
     * @ORM\Column(name="AddressCountry", type="string", length=255)
     */
    private $addressCountry;

    /**
     * @var string
     *
     * @ORM\Column(name="AddressPostcode", type="string", length=10)
     */
    private $addressPostcode;

    /**
     * @var string
     *
     * @ORM\Column(name="PhoneLandline", type="string", length=20)
     */
    private $phoneLandline;

    /**
     * @var string
     *
     * @ORM\Column(name="PhoneFax", type="string", length=20)
     */
    private $phoneFax;

    /**
     * @var string
     *
     * @ORM\Column(name="PhoneMobile", type="string", length=20)
     */
    private $phoneMobile;

    /**
     * @var integer
     *
     * @ORM\Column(name="domainId", type="integer")
     */
    private $domainId;
    
    
    
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
     * Set name
     *
     * @param string $name
     * @return Location
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return Location
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set fixedIpAddress
     *
     * @param boolean $fixedIpAddress
     * @return Location
     */
    public function setFixedIpAddress($fixedIpAddress)
    {
        $this->fixedIpAddress = $fixedIpAddress;

        return $this;
    }

    /**
     * Get fixedIpAddress
     *
     * @return boolean 
     */
    public function getFixedIpAddress()
    {
        return $this->fixedIpAddress;
    }

    /**
     * Set addressLine1
     *
     * @param string $addressLine1
     * @return Location
     */
    public function setAddressLine1($addressLine1)
    {
        $this->addressLine1 = $addressLine1;

        return $this;
    }

    /**
     * Get addressLine1
     *
     * @return string 
     */
    public function getAddressLine1()
    {
        return $this->addressLine1;
    }

    /**
     * Set addressLine2
     *
     * @param string $addressLine2
     * @return Location
     */
    public function setAddressLine2($addressLine2)
    {
        $this->addressLine2 = $addressLine2;

        return $this;
    }

    /**
     * Get addressLine2
     *
     * @return string 
     */
    public function getAddressLine2()
    {
        return $this->addressLine2;
    }

    /**
     * Set addressCity
     *
     * @param string $addressCity
     * @return Location
     */
    public function setAddressCity($addressCity)
    {
        $this->addressCity = $addressCity;

        return $this;
    }

    /**
     * Get addressCity
     *
     * @return string 
     */
    public function getAddressCity()
    {
        return $this->addressCity;
    }

    /**
     * Set addressCounty
     *
     * @param string $addressCounty
     * @return Location
     */
    public function setAddressCounty($addressCounty)
    {
        $this->addressCounty = $addressCounty;

        return $this;
    }

    /**
     * Get addressCounty
     *
     * @return string 
     */
    public function getAddressCounty()
    {
        return $this->addressCounty;
    }

    /**
     * Set addressCountry
     *
     * @param string $addressCountry
     * @return Location
     */
    public function setAddressCountry($addressCountry)
    {
        $this->addressCountry = $addressCountry;

        return $this;
    }

    /**
     * Get addressCountry
     *
     * @return string 
     */
    public function getAddressCountry()
    {
        return $this->addressCountry;
    }

    /**
     * Set addressPostcode
     *
     * @param string $addressPostcode
     * @return Location
     */
    public function setAddressPostcode($addressPostcode)
    {
        $this->addressPostcode = $addressPostcode;

        return $this;
    }

    /**
     * Get addressPostcode
     *
     * @return string 
     */
    public function getAddressPostcode()
    {
        return $this->addressPostcode;
    }

    /**
     * Set phoneLandline
     *
     * @param string $phoneLandline
     * @return Location
     */
    public function setPhoneLandline($phoneLandline)
    {
        $this->phoneLandline = $phoneLandline;

        return $this;
    }

    /**
     * Get phoneLandline
     *
     * @return string 
     */
    public function getPhoneLandline()
    {
        return $this->phoneLandline;
    }

    /**
     * Set phoneFax
     *
     * @param string $phoneFax
     * @return Location
     */
    public function setPhoneFax($phoneFax)
    {
        $this->phoneFax = $phoneFax;

        return $this;
    }

    /**
     * Get phoneFax
     *
     * @return string 
     */
    public function getPhoneFax()
    {
        return $this->phoneFax;
    }

    /**
     * Set phoneMobile
     *
     * @param string $phoneMobile
     * @return Location
     */
    public function setPhoneMobile($phoneMobile)
    {
        $this->phoneMobile = $phoneMobile;

        return $this;
    }

    /**
     * Get phoneMobile
     *
     * @return string 
     */
    public function getPhoneMobile()
    {
        return $this->phoneMobile;
    }
    
    /**
     * Set domainId
     *
     * @param integer $domainId
     * @return Location
     */
    public function setDomainId($domainId)
    {
        $this->domainId = $domainId;

        return $this;
    }

    /**
     * Get domainId
     *
     * @return integer
     */
    public function getDomainId()
    {
        return $this->domainId;
    }
}
