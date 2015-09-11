<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Timing
 *
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="timing_idx", columns={"userId", "dayId", "shiftId"})})
 * @ORM\Entity
 */
class Timing
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
     * @ORM\Column(name="dayId", type="integer")
     */
    private $dayId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="shiftId", type="integer")
     */
    private $shiftId;
    
    
    
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
     * @return Timing
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
     * Set dayId
     *
     * @param integer $dayId
     * @return Timing
     */
    public function setDayId($dayId)
    {
        $this->dayId = $dayId;

        return $this;
    }

    /**
     * Get dayId
     *
     * @return integer 
     */
    public function getDayId()
    {
        return $this->dayId;
    }

    /**
     * Set shiftId
     *
     * @param integer $shiftId
     * @return Timing
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
    
}
