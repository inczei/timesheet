<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FPReaderAttendance
 *
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="fpratt_idx", columns={"readerId", "userId", "timestamp"})})
 * @ORM\Entity
 */
class FPReaderAttendance
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
     * @ORM\Column(name="readerId", type="integer")
     */
    private $readerId;

    /**
     * @var integer
     *
     * @ORM\Column(name="userId", type="integer")
     */
    private $userId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="verified", type="integer")
     */
    private $verified;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime")
     */
    private $timestamp;


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
     * Set readerId
     *
     * @param integer $readerId
     * @return FPReaderAttendance
     */
    public function setReaderId($readerId)
    {
        $this->readerId = $readerId;

        return $this;
    }

    /**
     * Get readerId
     *
     * @return integer 
     */
    public function getReaderId()
    {
        return $this->readerId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return FPReaderAttendance
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
     * Set status
     *
     * @param integer $status
     * @return FPReaderAttendance
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
     * Set verified
     *
     * @param integer $verified
     * @return FPReaderAttendance
     */
    public function setVerified($verified)
    {
        $this->verified = $verified;

        return $this;
    }

    /**
     * Get verified
     *
     * @return integer
     */
    public function getVerified()
    {
        return $this->verified;
    }

    /**
     * Set timestamp
     *
     * @param \DateTime $timestamp
     * @return FPReaderAttendance
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
}
