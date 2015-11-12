<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AWWH
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class AWWH
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
     * @ORM\Column(name="userId", type="integer")
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="year", type="integer")
     */
    private $year;

    /**
     * @var integer
     *
     * @ORM\Column(name="week", type="integer")
     */
    private $week;

    /**
     * @var float
     *
     * @ORM\Column(name="days", type="float")
     */
    private $days;
    
    /**
     * @var float
     *
     * @ORM\Column(name="average", type="float")
     */
    private $average;


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
     * Set userId
     *
     * @param integer $userId
     * @return AWWH
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
     * Set year
     *
     * @param integer $year
     * @return AWWH
     */
    public function setYear($year)
    {
        $this->year = $year;

        return $this;
    }

    /**
     * Get year
     *
     * @return integer 
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set week
     *
     * @param integer $week
     * @return AWWH
     */
    public function setWeek($week)
    {
        $this->week = $week;

        return $this;
    }

    /**
     * Get week
     *
     * @return integer 
     */
    public function getWeek()
    {
        return $this->week;
    }

    /**
     * Set days
     *
     * @param float $days
     * @return AWWH
     */
    public function setDays($days)
    {
        $this->days = $days;

        return $this;
    }

    /**
     * Get days
     *
     * @return float 
     */
    public function getDays()
    {
        return $this->days;
    }
    
    /**
     * Set average
     *
     * @param float $average
     * @return AWWH
     */
    public function setAverage($average)
    {
        $this->average = $average;

        return $this;
    }

    /**
     * Get average
     *
     * @return float 
     */
    public function getAverage()
    {
        return $this->average;
    }
}
