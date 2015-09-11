<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShiftDays
 *
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="day_shift_idx", columns={"dayId", "shiftId"})})
 * @ORM\Entity
 */
class ShiftDays
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
     * Set dayId
     *
     * @param integer $dayId
     * @return ShiftDays
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
     * @return ShiftDays
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
