<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Allocation
 *
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="duls_idx", columns={"date", "userId", "locationId", "shiftId"})})
 * @ORM\Entity
 */
class Allocation
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
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="userId", type="integer")
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="locationId", type="integer")
     */
    private $locationId;

    /**
     * @var integer
     *
     * @ORM\Column(name="shiftId", type="integer")
     */
    private $shiftId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdOn", type="datetime")
     */
    private $createdOn;

    /**
     * @var boolean
     *
     * @ORM\Column(name="published", type="boolean")
     */
    private $published = false;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="publishedOn", type="datetime", nullable=true)
     */
    private $publishedOn;

    /**
     * @var integer
     *
     * @ORM\Column(name="publishedBy", type="integer", nullable=true)
     */
    private $publishedBy;
    
    

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
     * Set date
     *
     * @param \DateTime $date
     * @return Allocation
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return Allocation
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
     * Set locationId
     *
     * @param integer $locationId
     * @return Allocation
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
     * Set shiftId
     *
     * @param integer $shiftId
     * @return Allocation
     */
    public function setShiftId($shiftId)
    {
        $this->shiftId = $shiftId;

        return $this;
    }

    /**
     * Get shiftId
     *
     * @return integer 
     */
    public function getShiftId()
    {
        return $this->shiftId;
    }

    /**
     * Set createdOn
     *
     * @param \DateTime $createdOn
     * @return Allocation
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
     * Set published
     *
     * @param boolean $published
     * @return Allocation
     */
    public function setPublished($published)
    {
        $this->published = $published;

        return $this;
    }

    /**
     * Get published
     *
     * @return boolean 
     */
    public function getPublished()
    {
        return $this->published;
    }
    
    /**
     * Set publishedOn
     *
     * @param \DateTime $publishedOn
     * @return Allocation
     */
    public function setPublishedOn($publishedOn)
    {
        $this->publishedOn = $publishedOn;

        return $this;
    }

    /**
     * Get publishedOn
     *
     * @return \DateTime 
     */
    public function getPublishedOn()
    {
        return $this->publishedOn;
    }
    
    /**
     * Set publishedBy
     *
     * @param integer $publishedBy
     * @return Allocation
     */
    public function setPublishedBy($publishedBy)
    {
    	$this->publishedBy = $publishedBy;
    
    	return $this;
    }
    
    /**
     * Get publishedBy
     *
     * @return integer
     */
    public function getPublishedBy()
    {
    	return $this->publishedBy;
    }
    
}
