<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ResidentAllergies
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class ResidentAllergies
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
     * @ORM\Column(name="residentId", type="integer")
     */
    private $residentId;

    /**
     * @var integer
     *
     * @ORM\Column(name="allergyId", type="integer")
     */
    private $allergyId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdOn", type="datetime")
     */
    private $createdOn;

    /**
     * @var integer
     *
     * @ORM\Column(name="createdBy", type="integer")
     */
    private $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateStart", type="date")
     */
    private $dateStart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateEnd", type="date")
     */
    private $dateEnd;


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
     * Set residentId
     *
     * @param integer $residentId
     * @return ResidentAllergies
     */
    public function setResidentId($residentId)
    {
        $this->residentId = $residentId;

        return $this;
    }

    /**
     * Get residentId
     *
     * @return integer 
     */
    public function getResidentId()
    {
        return $this->residentId;
    }

    /**
     * Set allergyId
     *
     * @param integer $allergyId
     * @return ResidentAllergies
     */
    public function setAllergyId($allergyId)
    {
        $this->allergyId = $allergyId;

        return $this;
    }

    /**
     * Get allergyId
     *
     * @return integer 
     */
    public function getAllergyId()
    {
        return $this->allergyId;
    }

    /**
     * Set createdOn
     *
     * @param \DateTime $createdOn
     * @return ResidentAllergies
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
     * Set createdBy
     *
     * @param integer $createdBy
     * @return ResidentAllergies
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
     * Set dateStart
     *
     * @param \DateTime $dateStart
     * @return ResidentAllergies
     */
    public function setDateStart($dateStart)
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    /**
     * Get dateStart
     *
     * @return \DateTime 
     */
    public function getDateStart()
    {
        return $this->dateStart;
    }

    /**
     * Set dateEnd
     *
     * @param \DateTime $dateEnd
     * @return ResidentAllergies
     */
    public function setDateEnd($dateEnd)
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    /**
     * Get dateEnd
     *
     * @return \DateTime 
     */
    public function getDateEnd()
    {
        return $this->dateEnd;
    }
}
