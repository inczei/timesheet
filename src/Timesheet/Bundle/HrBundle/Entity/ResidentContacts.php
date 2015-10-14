<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ResidentContacts
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class ResidentContacts
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
     * @var integer
     *
     * @ORM\Column(name="residentId", type="integer")
     */
    private $residentId;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=20)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="firstName", type="string", length=50)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="lastName", type="string", length=50)
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(name="relation", type="string", length=50)
     */
    private $relation;

    /**
     * @var string
     *
     * @ORM\Column(name="addressLine1", type="string", length=50)
     */
    private $addressLine1;

    /**
     * @var string
     *
     * @ORM\Column(name="addressLine2", type="string", length=50)
     */
    private $addressLine2;

    /**
     * @var string
     *
     * @ORM\Column(name="addressCity", type="string", length=50)
     */
    private $addressCity;

    /**
     * @var string
     *
     * @ORM\Column(name="addressCounty", type="string", length=50)
     */
    private $addressCounty;

    /**
     * @var string
     *
     * @ORM\Column(name="addressCountry", type="string", length=4)
     */
    private $addressCountry;

    /**
     * @var string
     *
     * @ORM\Column(name="addressPostcode", type="string", length=10)
     */
    private $addressPostcode;

    /**
     * @var string
     *
     * @ORM\Column(name="phoneMobile", type="string", length=16)
     */
    private $phoneMobile;

    /**
     * @var string
     *
     * @ORM\Column(name="phoneLandline", type="string", length=16)
     */
    private $phoneLandline;

    /**
     * @var string
     *
     * @ORM\Column(name="phoneOther", type="string", length=16)
     */
    private $phoneOther;

    /**
     * @var string
     *
     * @ORM\Column(name="preferredPhone", type="string", length=16)
     */
    private $preferredPhone;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=50)
     */
    private $email;

    /**
     * @var boolean
     *
     * @ORM\Column(name="emergency", type="boolean")
     */
    private $emergency;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text")
     */
    private $notes;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdOn", type="datetime")
     */
    private $createdOn;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="createdBy", type="integer")
     */
    private $createdBy;
    
    
    
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
     * Set residentId
     *
     * @param integer $residentId
     * @return ResidentContacts
     */
    public function setResidentId($residentId)
    {
        $this->residentId = $residentId;

        return $this;
    }

    /**
     * Get residentId
     *
     * @return integer 
     */
    public function getResidentId()
    {
        return $this->residentId;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return ResidentContacts
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     * @return ResidentContacts
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     * @return ResidentContacts
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string 
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set relation
     *
     * @param string $relation
     * @return ResidentContacts
     */
    public function setRelation($relation)
    {
        $this->relation = $relation;

        return $this;
    }

    /**
     * Get relation
     *
     * @return string 
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * Set addressLine1
     *
     * @param string $addressLine1
     * @return ResidentContacts
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
     * @return ResidentContacts
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
     * @return ResidentContacts
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
     * @return ResidentContacts
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
     * @return ResidentContacts
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
     * @return ResidentContacts
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
     * Set phoneMobile
     *
     * @param string $phoneMobile
     * @return ResidentContacts
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
     * Set phoneLandline
     *
     * @param string $phoneLandline
     * @return ResidentContacts
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
     * Set phoneOther
     *
     * @param string $phoneOther
     * @return ResidentContacts
     */
    public function setPhoneOther($phoneOther)
    {
        $this->phoneOther = $phoneOther;

        return $this;
    }

    /**
     * Get phoneOther
     *
     * @return string 
     */
    public function getPhoneOther()
    {
        return $this->phoneOther;
    }

    /**
     * Set preferredPhone
     *
     * @param string $preferredPhone
     * @return ResidentContacts
     */
    public function setPreferredPhone($preferredPhone)
    {
        $this->preferredPhone = $preferredPhone;

        return $this;
    }

    /**
     * Get preferredPhone
     *
     * @return string 
     */
    public function getPreferredPhone()
    {
        return $this->preferredPhone;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return ResidentContacts
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set emergency
     *
     * @param boolean $emergency
     * @return ResidentContacts
     */
    public function setEmergency($emergency)
    {
        $this->emergency = $emergency;

        return $this;
    }

    /**
     * Get emergency
     *
     * @return boolean 
     */
    public function getEmergency()
    {
        return $this->emergency;
    }

    /**
     * Set notes
     *
     * @param string $notes
     * @return ResidentContacts
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes
     *
     * @return string 
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set createdOn
     *
     * @param \DateTime $createdOn
     * @return ResidentContacts
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    /**
     * Get createdOn
     *
     * @return \DateTime 
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * Set createdBy
     *
     * @param integer $createdBy
     * @return ResidentContacts
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return integer 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }
}
