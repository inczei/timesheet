<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Info
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Info
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
     * @var \DateTime
     *
     * @ORM\Column(name="Timestamp", type="datetime")
     */
    private $timestamp;

    /**
     * @var integer
     *
     * @ORM\Column(name="StatusId", type="integer")
     */
    private $statusId;

    /**
     * @var integer
     *
     * @ORM\Column(name="UserId", type="integer")
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="IpAddress", type="string", length=15)
     */
    private $ipAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="Comment", type="string", length=255)
     */
    private $comment = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="CreatedBy", type="integer")
     */
    private $createdBy;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="CreatedOn", type="datetime")
     */
    private $createdOn;
    
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Deleted", type="boolean")
     */
    private $deleted = 0;
    
    
    
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
     * Set timestamp
     *
     * @param \DateTime $timestamp
     * @return Info
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp
     *
     * @return \DateTime 
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set statusId
     *
     * @param integer $statusId
     * @return Info
     */
    public function setStatusId($statusId)
    {
        $this->statusId = $statusId;

        return $this;
    }

    /**
     * Get statusId
     *
     * @return integer 
     */
    public function getStatusId()
    {
        return $this->statusId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return Info
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set ipAddress
     *
     * @param string $ipAddress
     * @return Info
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
     * Set comment
     *
     * @param string $comment
     * @return Info
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
    
    /**
     * Set deleted
     *
     * @param boolean $deleted
     * @return Info
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return boolean
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set createdBy
     *
     * @param integer $createdBy
     * @return Info
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
     * @param \DateTime $createdOn
     * @return Info
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

}
