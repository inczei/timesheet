<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
/**
 * @ORM\Entity
 * @ORM\Table(name="Users", uniqueConstraints={
 * @ORM\UniqueConstraint(name="username_domain_idx", columns={"username", "domainId"}),
 * @ORM\UniqueConstraint(name="fullname_domain_idx", columns={"title", "firstName", "lastName", "domainId"})})
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(name="usernameCanonical", column=@ORM\Column(type="string", name="username_canonical", length=255, unique=false, nullable=false)),
 *      @ORM\AttributeOverride(name="email", column=@ORM\Column(type="string", name="email", length=255, unique=false, nullable=true)),
 *      @ORM\AttributeOverride(name="emailCanonical", column=@ORM\Column(type="string", name="email_canonical", length=255, unique=false, nullable=true))
 * })
 */
class User extends BaseUser
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $lastStatus;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastTime;

    /**
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    protected $lastIpAddress;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $lastComment;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $groupId = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $groupAdmin = false;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $locationId = 0;
    
    /**
     * @ORM\Column(type="boolean")
     */
    protected $locationAdmin = false;
    
    /**
     * @ORM\Column(type="boolean")
     */
    protected $isActive = true;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $loginRequired = true;
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $firstName;
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $lastName;
    
    /**
     * @ORM\Column(type="string", length=10)
     */
    protected $payrolCode;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $addressLine1;
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $addressLine2 = '';
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $addressCity;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $addressCounty = '';
    
    /**
     * @ORM\Column(type="string", length=5)
     */
    protected $addressCountry = '';
    
    /**
     * @ORM\Column(type="string", length=10)
     */
    protected $addressPostcode;
    
    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $phoneMobile = '';
    
    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $phoneLandline = '';
    
    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $birthday;
    
    /**
     * @ORM\Column(type="string", length=5)
     */
    protected $nationality = '';
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $nokName = '';
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $nokRelation = '';
    
    /**
     * @ORM\Column(type="string", length=9)
     */
    protected $ni = '';
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $nokPhone = '';
    
    /**
     * @ORM\Column(type="string", length=8)
     */
    protected $title = '';
    
    /**
     * @ORM\Column(type="string", length=250)
     */
    protected $notes = '';
    
    /**
     * @ORM\Column(type="boolean")
     */
    private $exEmail = false;
    
    /**
     * @ORM\Column(name="domainId", type="integer")
     */
    protected $domainId = 1;
    
    /**
     * @ORM\Column(name="taxCode", type="string", length=10)
     */
    protected $taxCode = '';
    
    /**
     * @ORM\Column(name="niCategory", type="string", length=2)
     */
    protected $NICategory = '';
    
    /**
     * @ORM\Column(name="paymentFrequency", type="string", length=10)
     */
    protected $paymentFrequency = '';
    
    /**
     * @ORM\Column(name="maritalStatus", type="string", length=15)
     */
    protected $maritalStatus = '';
    
    /**
     * @ORM\Column(name="ethnic", type="string", length=15)
     */
    protected $ethnic = '';
    
    
    
    
    public function __construct()
    {
    	parent::__construct();
        $this->isActive = true;
        $this->loginRequired = true;
        $this->groupId = 0;
        $this->locationId = 0;
        $this->taxCode = '';
        $this->NICategory = '';
        $this->paymentFrequency = '';
        $this->maritalStatus = '';
    }

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
     * Set isActive
     *
     * @param boolean $isActive
     * @return User
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set loginRequired
     *
     * @param boolean $loginRequired
     * @return User
     */
    public function setLoginRequired($loginRequired)
    {
        $this->loginRequired = $loginRequired;

        return $this;
    }

    /**
     * Get loginRequired
     *
     * @return boolean 
     */
    public function getLoginRequired()
    {
        return $this->loginRequired;
    }
    
    /**
     * Set lastStatus
     *
     * @param integer $lastStatus
     * @return User
     */
    public function setLastStatus($lastStatus)
    {
        $this->lastStatus = $lastStatus;

        return $this;
    }

    /**
     * Get lastStatus
     *
     * @return integer 
     */
    public function getLastStatus()
    {
        return $this->lastStatus;
    }
    
    /**
     * Set lastTime
     *
     * @param datetime $lastTime
     * @return User
     */
    public function setLastTime($lastTime)
    {
        $this->lastTime = $lastTime;

        return $this;
    }

    /**
     * Get lastTime
     *
     * @return datetime
     */
    public function getLastTime()
    {
        return $this->lastTime;
    }

    /**
     * Set lastIpAddress
     *
     * @param string $lastIpAddress
     * @return User
     */
    public function setLastIpAddress($lastIpAddress)
    {
        $this->lastIpAddress = $lastIpAddress;

        return $this;
    }

    /**
     * Get lastIpAddress
     *
     * @return string
     */
    public function getLastIpAddress()
    {
        return $this->lastIpAddress;
    }
    
    /**
     * Set lastComment
     *
     * @param string $lastComment
     * @return User
     */
    public function setLastComment($lastComment)
    {
        $this->lastComment = $lastComment;

        return $this;
    }

    /**
     * Get lastComment
     *
     * @return string
     */
    public function getLastComment()
    {
        return $this->lastComment;
    }
    
    /**
     * Set groupId
     *
     * @param integer $groupId
     * @return User
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId
     *
     * @return integer
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set locationId
     *
     * @param integer $locationId
     * @return User
     */
    public function setLocationId($locationId)
    {
        $this->locationId = $locationId;

        return $this;
    }

    /**
     * Get locationId
     *
     * @return integer
     */
    public function getLocationId()
    {
        return $this->locationId;
    }
    
    /**
     * Set firstName
     *
     * @param string $firstName
     * @return User
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
     * @return User
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
     * Set payrolCode
     *
     * @param string $payrolCode
     * @return User
     */
    public function setPayrolCode($payrolCode)
    {
        $this->payrolCode = $payrolCode;

        return $this;
    }

    /**
     * Get payrolCode
     *
     * @return string 
     */
    public function getPayrolCode()
    {
        return $this->payrolCode;
    }

    /**
     * Set NI
     *
     * @param string $ni
     * @return User
     */
    public function setNI($ni)
    {
        $this->ni = $ni;

        return $this;
    }

    /**
     * Get NI
     *
     * @return string 
     */
    public function getNI()
    {
        return $this->ni;
    }
    
    /**
     * Set nationality
     *
     * @param string $nationality
     * @return User
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
     * Set addressLine1
     *
     * @param string $addressLine1
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * Set phoneMobile
     *
     * @param string $phoneMobile
     * @return User
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
     * Set birthday
     *
     * @param date $birthday
     * @return User
     */

    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday
     *
     * @return date
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set groupAdmin
     *
     * @param boolean $groupAdmin
     * @return User
     */
    public function setGroupAdmin($groupAdmin)
    {
        $this->groupAdmin = $groupAdmin;

        return $this;
    }

    /**
     * Get groupAdmin
     *
     * @return boolean 
     */
    public function getGroupAdmin()
    {
        return $this->groupAdmin;
    }
    
    /**
     * Set locationAdmin
     *
     * @param boolean $locationAdmin
     * @return User
     */
    public function setLocationAdmin($locationAdmin)
    {
        $this->locationAdmin = $locationAdmin;

        return $this;
    }

    /**
     * Get locationAdmin
     *
     * @return boolean 
     */
    public function getLocationAdmin()
    {
        return $this->locationAdmin;
    }

    /**
     * Set nokName
     *
     * @param string $nokName
     * @return User
     */
    public function setNokName($nokName)
    {
        $this->nokName = $nokName;

        return $this;
    }

    /**
     * Get nokName
     *
     * @return string 
     */
    public function getNokName()
    {
        return $this->nokName;
    }
    
    /**
     * Set nokRelation
     *
     * @param string $nokRelation
     * @return User
     */
    public function setNokRelation($nokRelation)
    {
        $this->nokRelation = $nokRelation;

        return $this;
    }

    /**
     * Get nokRelation
     *
     * @return string 
     */
    public function getNokRelation()
    {
        return $this->nokRelation;
    }
    
    /**
     * Set nokPhone
     *
     * @param string $nokPhone
     * @return User
     */
    public function setNokPhone($nokPhone)
    {
        $this->nokPhone = $nokPhone;

        return $this;
    }

    /**
     * Get nokPhone
     *
     * @return string 
     */
    public function getNokPhone()
    {
        return $this->nokPhone;
    }

    /**
     * Set notes
     *
     * @param string $notes
     * @return User
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
     * Set title
     *
     * @param string $title
     * @return User
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
     * Set exEmail
     *
     * @param boolean $exEmail
     * @return User
     */
    public function setExEmail($exEmail)
    {
    	$this->exEmail = $exEmail;
    
    	return $this;
    }
    
    /**
     * Get exEmail
     *
     * @return boolean
     */
    public function getExEmail()
    {
    	return $this->exEmail;
    }
    
    /**
     * Set domainId
     *
     * @param integer $domainId
     * @return User
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
     * Set taxCode
     *
     * @param string $taxCode
     * @return User
     */
    public function setTaxCode($taxCode)
    {
    	$this->taxCode = $taxCode;
    
    	return $this;
    }
    
    /**
     * Get taxCode
     *
     * @return string
     */
    public function getTaxCode()
    {
    	return $this->taxCode;
    }
    
    /**
     * Set niCategory
     *
     * @param string $niCategory
     * @return User
     */
    public function setNICategory($niCategory)
    {
    	$this->NICategory = $niCategory;
    
    	return $this;
    }
    
    /**
     * Get niCategory
     *
     * @return string
     */
    public function getNICategory()
    {
    	return $this->NICategory;
    }
    
    /**
     * Set paymentFrequency
     *
     * @param string $paymentFrequency
     * @return User
     */
    public function setPaymentFrequency($paymentFrequency)
    {
    	$this->paymentFrequency = $paymentFrequency;
    
    	return $this;
    }
    
    /**
     * Get paymentFrequency
     *
     * @return string
     */
    public function getPaymentFrequency()
    {
    	return $this->paymentFrequency;
    }
    
    /**
     * Set maritalStatus
     *
     * @param string $maritalStatus
     * @return User
     */
    public function setMaritalStatus($maritalStatus)
    {
    	$this->maritalStatus = $maritalStatus;
    
    	return $this;
    }
    
    /**
     * Get maritalStatus
     *
     * @return string
     */
    public function getMaritalStatus()
    {
    	return $this->maritalStatus;
    }
    
    /**
     * Set ethnic
     *
     * @param string $ethnic
     * @return User
     */
    public function setEthnic($ethnic)
    {
    	$this->ethnic = $ethnic;
    
    	return $this;
    }
    
    /**
     * Get ethnic
     *
     * @return string
     */
    public function getEthnic()
    {
    	return $this->ethnic;
    }
    
    
    /**
     * Get full name
     *
     * @return string 
     */
    public function getFullName()
    {
        return trim($this->title.' '.$this->firstName.' '.$this->lastName);
    }
}
