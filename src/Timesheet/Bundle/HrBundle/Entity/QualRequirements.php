<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * QualRequirements
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class QualRequirements
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
     * @var integer
     *
     * @ORM\Column(name="qualificationId", type="integer")
     */
    private $qualificationId;

    /**
     * @var integer
     *
     * @ORM\Column(name="levelId", type="integer", nullable=true)
     */
    private $levelId;
    
    
    
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
     * Set shiftId
     *
     * @param integer $shiftId
     * @return QualRequirements
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
     * @return QualRequirements
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

    /**
     * Set qualificationId
     *
     * @param integer $qualificationId
     * @return QualRequirements
     */
    public function setQualificationId($qualificationId)
    {
        $this->qualificationId = $qualificationId;

        return $this;
    }

    /**
     * Get qualificationId
     *
     * @return integer 
     */
    public function getQualificationId()
    {
        return $this->qualificationId;
    }
    
    /**
     * Set levelId
     *
     * @param integer $levelId
     * @return QualRequirements
     */
    public function setLevelId($levelId)
    {
        $this->levelId = $levelId;

        return $this;
    }

    /**
     * Get levelId
     *
     * @return integer 
     */
    public function getLevelId()
    {
        return $this->levelId;
    }
}
