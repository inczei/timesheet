<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Config\Definition\BooleanNode;

/**
 * Shifts
 *
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="rooms_idx", columns={"title", "locationId"})})
 * @ORM\Entity
 */
class Shifts
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
     * @ORM\Column(name="title", type="string", length=20)
     */
    private $title;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="locationId", type="integer")
     */
    private $locationId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startTime", type="time")
     */
    private $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="finishTime", type="time")
     */
    private $finishTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="FPstartTime", type="time", nullable=true, options={"comment":"Earliest time to recognise as Check in"})
     */
    private $fpStartTime = null;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="FPfinishTime", type="time", nullable=true, options={"comment":"Latest time to recognise as Check out"})
     */
    private $fpFinishTime = null;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="FPstartBreak", type="time", nullable=true, options={"comment":"Earliest time to recognise as Break start"})
     */
    private $fpStartBreak = null;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="FPfinishBreak", type="time", nullable=true, options={"comment":"Latest time to recognise as Break finish"})
     */
    private $fpFinishBreak = null;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startBreak", type="time", nullable=true)
     */
    private $startBreak = null;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="finishBreak", type="time", nullable=true)
     */
    private $finishBreak = null;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="strictBreak", type="boolean")
     */
    private $strictBreak = false;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="minWorkTime", type="integer", nullable=true)
     */
    private $minWorkTime = null;

    
    
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
     * Set title
     *
     * @param string $title
     * @return Shifts
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
     * Set locationId
     *
     * @param integer $locationId
     * @return Shifts
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
     * Set startTime
     *
     * @param \DateTime $startTime
     * @return Shifts
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return \DateTime 
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set finishTime
     *
     * @param \DateTime $finishTime
     * @return Shifts
     */
    public function setFinishTime($finishTime)
    {
        $this->finishTime = $finishTime;

        return $this;
    }

    /**
     * Get finishTime
     *
     * @return \DateTime 
     */
    public function getFinishTime()
    {
        return $this->finishTime;
    }
    
    /**
     * Set fpStartTime
     *
     * @param \DateTime $fpStartTime
     * @return Shifts
     */
    public function setFPStartTime($fpStartTime)
    {
        $this->fpStartTime = $fpStartTime;

        return $this;
    }

    /**
     * Get fpStartTime
     *
     * @return \DateTime 
     */
    public function getFPStartTime()
    {
        return $this->fpStartTime;
    }

    /**
     * Set fpFinishTime
     *
     * @param \DateTime $fpFinishTime
     * @return Shifts
     */
    public function setFPFinishTime($fpFinishTime)
    {
        $this->fpFinishTime = $fpFinishTime;

        return $this;
    }

    /**
     * Get fpFinishTime
     *
     * @return \DateTime 
     */
    public function getFPFinishTime()
    {
        return $this->fpFinishTime;
    }
    
    /**
     * Set startBreak
     *
     * @param \DateTime $startBreak
     * @return Shifts
     */
    public function setStartBreak($startBreak)
    {
        $this->startBreak = $startBreak;

        return $this;
    }

    /**
     * Get startBreak
     *
     * @return \DateTime 
     */
    public function getStartBreak()
    {
        return $this->startBreak;
    }

    /**
     * Set finishBreak
     *
     * @param \DateTime $finishBreak
     * @return Shifts
     */
    public function setFinishBreak($finishBreak)
    {
        $this->finishBreak = $finishBreak;

        return $this;
    }

    /**
     * Get finishBreak
     *
     * @return \DateTime 
     */
    public function getFinishBreak()
    {
        return $this->finishBreak;
    }

    /**
     * Set fpStartBreak
     *
     * @param \DateTime $fpStartBreak
     * @return Shifts
     */
    public function setFPStartBreak($fpStartBreak)
    {
        $this->fpStartBreak = $fpStartBreak;

        return $this;
    }

    /**
     * Get fpStartBreak
     *
     * @return \DateTime 
     */
    public function getFPStartBreak()
    {
        return $this->fpStartBreak;
    }

    /**
     * Set fpFinishBreak
     *
     * @param \DateTime $fpFinishBreak
     * @return Shifts
     */
    public function setFPFinishBreak($fpFinishBreak)
    {
        $this->fpFinishBreak = $fpFinishBreak;

        return $this;
    }

    /**
     * Get fpFinishBreak
     *
     * @return \DateTime 
     */
    public function getFPFinishBreak()
    {
        return $this->fpFinishBreak;
    }
    
    /**
     * Set strictBreak
     *
     * @param boolean $strictBreak
     * @return Shifts
     */
    public function setStrictBreak($strictBreak)
    {
        $this->strictBreak = $strictBreak;

        return $this;
    }

    /**
     * Get strictBreak
     *
     * @return boolean
     */
    public function getStrictBreak()
    {
        return $this->strictBreak;
    }

    /**
     * Set minWorkTime
     *
     * @param integer $minWorkTime
     * @return Shifts
     */
    public function setMinWorkTime($minWorkTime)
    {
    	$this->minWorkTime = $minWorkTime;
    
    	return $this;
    }
    
    /**
     * Get minWorkTime
     *
     * @return integer
     */
    public function getMinWorkTime()
    {
    	return $this->minWorkTime;
    }
}
