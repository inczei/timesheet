<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserVisas
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class UserVisas
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
     * @ORM\Column(name="visaId", type="integer")
     */
    private $visaId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startDate", type="date")
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endDate", type="date", nullable=true)
     */
    private $endDate;

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
     * @var boolean
     *
     * @ORM\Column(name="notExpire", type="boolean")
     */
    private $notExpire;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text")
     */
    private $notes;


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
     * @return UserVisas
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
     * Set visaId
     *
     * @param integer $visaId
     * @return UserVisas
     */
    public function setVisaId($visaId)
    {
        $this->visaId = $visaId;

        return $this;
    }

    /**
     * Get visaId
     *
     * @return integer 
     */
    public function getVisaId()
    {
        return $this->visaId;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return UserVisas
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return UserVisas
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set createdOn
     *
     * @param \DateTime $createdOn
     * @return UserVisas
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
     * @return UserVisas
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
     * Set notExpire
     *
     * @param boolean $notExpire
     * @return UserVisas
     */
    public function setNotExpire($notExpire)
    {
        $this->notExpire = $notExpire;

        return $this;
    }

    /**
     * Get notExpire
     *
     * @return boolean 
     */
    public function getNotExpire()
    {
        return $this->notExpire;
    }

    /**
     * Set notes
     *
     * @param string $notes
     * @return UserVisas
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes
     *
     * @return string 
     */
    public function getNotes()
    {
        return $this->notes;
    }
}
