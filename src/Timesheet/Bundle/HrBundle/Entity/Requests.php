<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Requests
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Requests
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
     * @var \DateTime
     *
     * @ORM\Column(name="start", type="datetime")
     */
    private $start;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="finish", type="datetime")
     */
    private $finish;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=255)
     */
    private $comment;

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
     * @var boolean
     *
     * @ORM\Column(name="accepted", type="integer")
     */
    private $accepted = 0;

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
     * @ORM\Column(name="acceptedComment", type="string", length=255)
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
     * Set userId
     *
     * @param integer $userId
     * @return Requests
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
     * @return Requests
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
     * Set start
     *
     * @param \DateTime $start
     * @return Requests
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get start
     *
     * @return \DateTime 
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set finish
     *
     * @param \DateTime $finish
     * @return Requests
     */
    public function setFinish($finish)
    {
        $this->finish = $finish;

        return $this;
    }

    /**
     * Get finish
     *
     * @return \DateTime 
     */
    public function getFinish()
    {
        return $this->finish;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return Requests
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
     * Set createdBy
     *
     * @param integer $createdBy
     * @return Requests
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
     * @return Requests
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
     * Set accepted
     *
     * @param boolean $accepted
     * @return Requests
     */
    public function setAccepted($accepted)
    {
        $this->accepted = $accepted;

        return $this;
    }

    /**
     * Get accepted
     *
     * @return boolean 
     */
    public function getAccepted()
    {
        return $this->accepted;
    }

    /**
     * Set acceptedBy
     *
     * @param integer $acceptedBy
     * @return Requests
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
     * @return Requests
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
     * @return Requests
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
