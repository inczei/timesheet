<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FPReaders
 *
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="fpreaders_idx", columns={"ipAddress", "port"})})
 * @ORM\Entity
 */
class FPReaders
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
     * @ORM\Column(name="ipAddress", type="string", length=50)
     */
    private $ipAddress;

    /**
     * @var integer
     *
     * @ORM\Column(name="port", type="integer")
     */
    private $port;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=50, nullable=true)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=50, nullable=true)
     */
    private $password;

    /**
     * @var integer
     *
     * @ORM\Column(name="deviceId", type="integer")
     */
    private $deviceId;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=50)
     */
    private $version;

    /**
     * @var string
     *
     * @ORM\Column(name="serialnumber", type="string", length=50)
     */
    private $serialnumber;

    /**
     * @var string
     *
     * @ORM\Column(name="deviceName", type="string", length=50)
     */
    private $deviceName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastAccess", type="datetime", nullable=true)
     */
    private $lastAccess;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="platform", type="string", length=50)
     */
    private $platform;

    /**
     * @var integer
     *
     * @ORM\Column(name="domainId", type="integer")
     */
    private $domainId;

    /**
     * @var integer
     *
     * @ORM\Column(name="locationId", type="integer")
     */
    private $locationId;
    
    /**
     * @var text
     *
     * @ORM\Column(name="comment", type="text")
     */
    private $comment;
    
    
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
     * Set ipAddress
     *
     * @param string $ipAddress
     * @return FPReaders
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return string 
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set port
     *
     * @param integer $port
     * @return FPReaders
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get port
     *
     * @return integer 
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return FPReaders
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return FPReaders
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set deviceId
     *
     * @param integer $deviceId
     * @return FPReaders
     */
    public function setDeviceId($deviceId)
    {
        $this->deviceId = $deviceId;

        return $this;
    }

    /**
     * Get deviceId
     *
     * @return integer 
     */
    public function getDeviceId()
    {
        return $this->deviceId;
    }

    /**
     * Set version
     *
     * @param string $version
     * @return FPReaders
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return string 
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set serialnumber
     *
     * @param string $serialnumber
     * @return FPReaders
     */
    public function setSerialnumber($serialnumber)
    {
        $this->serialnumber = $serialnumber;

        return $this;
    }

    /**
     * Get serialnumber
     *
     * @return string 
     */
    public function getSerialnumber()
    {
        return $this->serialnumber;
    }

    /**
     * Set deviceName
     *
     * @param string $deviceName
     * @return FPReaders
     */
    public function setDeviceName($deviceName)
    {
        $this->deviceName = $deviceName;

        return $this;
    }

    /**
     * Get deviceName
     *
     * @return string 
     */
    public function getDeviceName()
    {
        return $this->deviceName;
    }

    /**
     * Set lastAccess
     *
     * @param \DateTime $lastAccess
     * @return FPReaders
     */
    public function setLastAccess($lastAccess)
    {
        $this->lastAccess = $lastAccess;

        return $this;
    }

    /**
     * Get lastAccess
     *
     * @return \DateTime 
     */
    public function getLastAccess()
    {
        return $this->lastAccess;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return FPReaders
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set platform
     *
     * @param string $platform
     * @return FPReaders
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * Get platform
     *
     * @return string 
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Set domainId
     *
     * @param integer $domainId
     * @return FPReaders
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
     * Set locationId
     *
     * @param integer $locationId
     * @return FPReaders
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
     * Set comment
     *
     * @param string $comment
     * @return FPReaders
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }
}
