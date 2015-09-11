<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Shifts
 *
 * @ORM\Table()
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
}
