<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TimesheetCheck
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class TimesheetCheck
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
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="checkedBy", type="integer")
     */
    private $checkedBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="checkedOn", type="datetime")
     */
    private $checkedOn;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text")
     */
    private $comment;


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
     * @return TimesheetCheck
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
     * Set date
     *
     * @param \DateTime $date
     * @return TimesheetCheck
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set checkedBy
     *
     * @param integer $checkedBy
     * @return TimesheetCheck
     */
    public function setCheckedBy($checkedBy)
    {
        $this->checkedBy = $checkedBy;

        return $this;
    }

    /**
     * Get checkedBy
     *
     * @return integer 
     */
    public function getCheckedBy()
    {
        return $this->checkedBy;
    }

    /**
     * Set checkedOn
     *
     * @param \DateTime $checkedOn
     * @return TimesheetCheck
     */
    public function setCheckedOn($checkedOn)
    {
        $this->checkedOn = $checkedOn;

        return $this;
    }

    /**
     * Get checkedOn
     *
     * @return \DateTime 
     */
    public function getCheckedOn()
    {
        return $this->checkedOn;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return TimesheetCheck
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }
}
