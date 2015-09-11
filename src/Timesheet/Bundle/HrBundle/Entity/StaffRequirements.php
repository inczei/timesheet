<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StaffRequirements
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class StaffRequirements
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
     * @ORM\Column(name="groupId", type="integer")
     */
    private $groupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="shiftId", type="integer")
     */
    private $shiftId;

    /**
     * @var integer
     *
     * @ORM\Column(name="numberOfStaff", type="integer")
     */
    private $numberOfStaff;


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
     * Set groupId
     *
     * @param integer $groupId
     * @return StaffRequirements
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
     * Set shiftId
     *
     * @param integer $shiftId
     * @return StaffRequirements
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
     * Set numberOfStaff
     *
     * @param integer $numberOfStaff
     * @return StaffRequirements
     */
    public function setNumberOfStaff($numberOfStaff)
    {
        $this->numberOfStaff = $numberOfStaff;

        return $this;
    }

    /**
     * Get numberOfStaff
     *
     * @return integer 
     */
    public function getNumberOfStaff()
    {
        return $this->numberOfStaff;
    }
}
