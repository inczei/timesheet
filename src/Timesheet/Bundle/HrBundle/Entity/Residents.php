<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Residents
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Residents
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
     * @ORM\Column(name="title", type="string", length=8)
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
     * @ORM\Column(name="nickName", type="string", length=50)
     */
    private $nickName;

    /**
     * @var string
     *
     * @ORM\Column(name="maidenName", type="string", length=50)
     */
    private $maidenName;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birthday", type="date")
     */
    private $birthday;

    /**
     * @var string
     *
     * @ORM\Column(name="nationality", type="string", length=5)
     */
    private $nationality;
    
    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=50)
     */
    private $email;

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
     * @ORM\Column(name="addressCountry", type="string", length=5)
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
     * @ORM\Column(name="phoneMobile", type="string", length=20)
     */
    private $phoneMobile;

    /**
     * @var string
     *
     * @ORM\Column(name="phoneLandline", type="string", length=20)
     */
    private $phoneLandline;

    /**
     * @var string
     *
     * @ORM\Column(name="ni", type="string", length=9)
     */
    private $ni;

    /**
     * @var integer
     *
     * @ORM\Column(name="maritalStatus", type="integer", nullable=true)
     */
    private $maritalStatus;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="religion", type="integer", nullable=true)
     */
    private $religion;

    /**
     * @var text
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    private $notes;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="domainId", type="integer")
     */
    private $domainId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="createdBy", type="integer")
     */
    private $createdBy;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdOn", type="datetime")
     */
    private $createdOn;
    
    /**
     * @var string
     *
     * @ORM\Column(name="nhs", type="string", length=15)
     */
    private $nhs;
    
    
    
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
     * Set firstName
     *
     * @param string $firstName
     * @return Residents
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
     * @return Residents
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
     * Set nickName
     *
     * @param string $nickName
     * @return Residents
     */
    public function setNickName($nickName)
    {
        $this->nickName = $nickName;

        return $this;
    }

    /**
     * Get nickName
     *
     * @return string 
     */
    public function getNickName()
    {
        return $this->nickName;
    }
    
    /**
     * Set maidenName
     *
     * @param string $maidenName
     * @return Residents
     */
    public function setMaidenName($maidenName)
    {
        $this->maidenName = $maidenName;

        return $this;
    }

    /**
     * Get maidenName
     *
     * @return string 
     */
    public function getMaidenName()
    {
        return $this->maidenName;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Residents
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
     * Set birthday
     *
     * @param \DateTime $birthday
     * @return Residents
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday
     *
     * @return \DateTime 
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set nationality
     *
     * @param string $nationality
     * @return Residents
     */
    public function setNationality($nationality)
    {
        $this->nationality = $nationality;

        return $this;
    }

    /**
     * Get nationality
     *
     * @return string 
     */
    public function getNationality()
    {
        return $this->nationality;
    }
    
    /**
     * Set email
     *
     * @param string $email
     * @return Residents
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
     * Set addressLine1
     *
     * @param string $addressLine1
     * @return Residents
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
     * @return Residents
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
     * @return Residents
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
     * @return Residents
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
     * @return Residents
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
     * @return Residents
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
     * @return Residents
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
     * @return Residents
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
     * Set ni
     *
     * @param string $ni
     * @return Residents
     */
    public function setNI($ni)
    {
        $this->ni = $ni;

        return $this;
    }

    /**
     * Get ni
     *
     * @return string 
     */
    public function getNI()
    {
        return $this->ni;
    }
    
    /**
     * Set nhs
     *
     * @param string $nhs
     * @return Residents
     */
    public function setNHS($nhs)
    {
        $this->nhs = $nhs;

        return $this;
    }

    /**
     * Get nhs
     *
     * @return string 
     */
    public function getNHS()
    {
        return $this->nhs;
    }
    
    /**
     * Set maritalStatus
     *
     * @param integer $maritalStatus
     * @return Residents
     */
    public function setMaritalStatus($maritalStatus)
    {
        $this->maritalStatus = $maritalStatus;

        return $this;
    }

    /**
     * Get maritalStatus
     *
     * @return integer
     */
    public function getMaritalStatus()
    {
        return $this->maritalStatus;
    }
    
    /**
     * Set religion
     *
     * @param integer $religion
     * @return Residents
     */
    public function setReligion($religion)
    {
        $this->religion = $religion;

        return $this;
    }

    /**
     * Get religion
     *
     * @return integer
     */
    public function getReligion()
    {
        return $this->religion;
    }
    
    /**
     * Set notes
     *
     * @param text $notes
     * @return Residents
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes
     *
     * @return text
     */
    public function getNotes()
    {
        return $this->notes;
    }
    
    /**
     * Set domainId
     *
     * @param integer $domainId
     * @return Residents
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
    
    /**
     * Set createdBy
     *
     * @param integer $createdBy
     * @return Residents
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
    
    /**
     * Set createdOn
     *
     * @param datetime $createdOn
     * @return Residents
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    /**
     * Get createdOn
     *
     * @return datetime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }
}
