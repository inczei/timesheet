<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SwapRequest
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class SwapRequest
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
     * @ORM\Column(name="createdBy", type="integer")
     */
    private $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdOn", type="datetime")
     */
    private $createdOn;

    /**
     * @var integer
     *
     * @ORM\Column(name="userId1", type="integer")
     */
    private $userId1;

    /**
     * @var integer
     *
     * @ORM\Column(name="shiftId1", type="integer")
     */
    private $shiftId1;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date1", type="date")
     */
    private $date1;

    /**
     * @var integer
     *
     * @ORM\Column(name="userId2", type="integer")
     */
    private $userId2;

    /**
     * @var integer
     *
     * @ORM\Column(name="shiftId2", type="integer")
     */
    private $shiftId2;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date2", type="date")
     */
    private $date2;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=255)
     */
    private $comment;

    /**
     * @var integer
     *
     * @ORM\Column(name="accepted", type="integer", nullable=true)
     */
    private $accepted;

    /**
     * @var integer
     *
     * @ORM\Column(name="acceptedBy", type="integer", nullable=true)
     */
    private $acceptedBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="acceptedOn", type="datetime", nullable=true)
     */
    private $acceptedOn;

    /**
     * @var string
     *
     * @ORM\Column(name="acceptedComment", type="string", length=255, nullable=true)
     */
    private $acceptedComment;


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
     * Set createdBy
     *
     * @param integer $createdBy
     * @return SwapRequest
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
     * Set createdOn
     *
     * @param \DateTime $createdOn
     * @return SwapRequest
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
     * Set userId1
     *
     * @param integer $userId1
     * @return SwapRequest
     */
    public function setUserId1($userId1)
    {
        $this->userId1 = $userId1;

        return $this;
    }

    /**
     * Get userId1
     *
     * @return integer 
     */
    public function getUserId1()
    {
        return $this->userId1;
    }

    /**
     * Set shiftId1
     *
     * @param integer $shiftId1
     * @return SwapRequest
     */
    public function setShiftId1($shiftId1)
    {
        $this->shiftId1 = $shiftId1;

        return $this;
    }

    /**
     * Get shiftId1
     *
     * @return integer 
     */
    public function getShiftId1()
    {
        return $this->shiftId1;
    }

    /**
     * Set date1
     *
     * @param \DateTime $date1
     * @return SwapRequest
     */
    public function setDate1($date1)
    {
        $this->date1 = $date1;

        return $this;
    }

    /**
     * Get date1
     *
     * @return \DateTime 
     */
    public function getDate1()
    {
        return $this->date1;
    }

    /**
     * Set userId2
     *
     * @param integer $userId2
     * @return SwapRequest
     */
    public function setUserId2($userId2)
    {
        $this->userId2 = $userId2;

        return $this;
    }

    /**
     * Get userId2
     *
     * @return integer 
     */
    public function getUserId2()
    {
        return $this->userId2;
    }

    /**
     * Set shiftId2
     *
     * @param integer $shiftId2
     * @return SwapRequest
     */
    public function setShiftId2($shiftId2)
    {
        $this->shiftId2 = $shiftId2;

        return $this;
    }

    /**
     * Get shiftId2
     *
     * @return integer 
     */
    public function getShiftId2()
    {
        return $this->shiftId2;
    }

    /**
     * Set date2
     *
     * @param \DateTime $date2
     * @return SwapRequest
     */
    public function setDate2($date2)
    {
        $this->date2 = $date2;

        return $this;
    }

    /**
     * Get date2
     *
     * @return \DateTime 
     */
    public function getDate2()
    {
        return $this->date2;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return SwapRequest
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

    /**
     * Set accepted
     *
     * @param integer $accepted
     * @return SwapRequest
     */
    public function setAccepted($accepted)
    {
        $this->accepted = $accepted;

        return $this;
    }

    /**
     * Get accepted
     *
     * @return integer 
     */
    public function getAccepted()
    {
        return $this->accepted;
    }

    /**
     * Set acceptedBy
     *
     * @param integer $acceptedBy
     * @return SwapRequest
     */
    public function setAcceptedBy($acceptedBy)
    {
        $this->acceptedBy = $acceptedBy;

        return $this;
    }

    /**
     * Get acceptedBy
     *
     * @return integer 
     */
    public function getAcceptedBy()
    {
        return $this->acceptedBy;
    }

    /**
     * Set acceptedOn
     *
     * @param \DateTime $acceptedOn
     * @return SwapRequest
     */
    public function setAcceptedOn($acceptedOn)
    {
        $this->acceptedOn = $acceptedOn;

        return $this;
    }

    /**
     * Get acceptedOn
     *
     * @return \DateTime 
     */
    public function getAcceptedOn()
    {
        return $this->acceptedOn;
    }

    /**
     * Set acceptedComment
     *
     * @param string $acceptedComment
     * @return SwapRequest
     */
    public function setAcceptedComment($acceptedComment)
    {
        $this->acceptedComment = $acceptedComment;

        return $this;
    }

    /**
     * Get acceptedComment
     *
     * @return string 
     */
    public function getAcceptedComment()
    {
        return $this->acceptedComment;
    }
}
