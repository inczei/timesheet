<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserDBSCheck
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class UserDBSCheck
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
     * @ORM\Column(name="typeId", type="integer")
     */
    private $typeId;

    /**
     * @var string
     *
     * @ORM\Column(name="disclosureNo", type="string", length=20)
     */
    private $disclosureNo;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="issueDate", type="date")
     */
    private $issueDate;

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
     *
     * @return UserDBSCheck
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
     * Set typeId
     *
     * @param integer $typeId
     *
     * @return UserDBSCheck
     */
    public function setTypeId($typeId)
    {
        $this->typeId = $typeId;

        return $this;
    }

    /**
     * Get typeId
     *
     * @return integer
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * Set disclosureNo
     *
     * @param string $disclosureNo
     *
     * @return UserDBSCheck
     */
    public function setDisclosureNo($disclosureNo)
    {
        $this->disclosureNo = $disclosureNo;

        return $this;
    }

    /**
     * Get disclosureNo
     *
     * @return string
     */
    public function getDisclosureNo()
    {
        return $this->disclosureNo;
    }

    /**
     * Set issueDate
     *
     * @param \DateTime $issueDate
     *
     * @return UserDBSCheck
     */
    public function setIssueDate($issueDate)
    {
    	$this->issueDate = $issueDate;
    
    	return $this;
    }
    
    /**
     * Get issueDate
     *
     * @return \DateTime
     */
    public function getIssueDate()
    {
    	return $this->issueDate;
    }
    
    /**
     * Set createdOn
     *
     * @param \DateTime $createdOn
     *
     * @return UserDBSCheck
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
     *
     * @return UserDBSCheck
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
     * Set notes
     *
     * @param string $notes
     *
     * @return UserDBSCheck
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

