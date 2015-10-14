<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ResidentPlacements
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class ResidentPlacements
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
     * @ORM\Column(name="roomId", type="integer")
     */
    private $roomId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="moveIn", type="date", nullable=true)
     */
    private $moveIn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="moveOut", type="date", nullable=true)
     */
    private $moveOut;
    
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
     * @ORM\Column(name="modifiedOn", type="datetime", nullable=true)
     */
    private $modifiedOn;

    /**
     * @var integer
     *
     * @ORM\Column(name="modifiedBy", type="integer", nullable=true)
     */
    private $modifiedBy;
    
    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text")
     */
    private $notes;

    /**
     * @var string
     *
     * @ORM\Column(name="moveOutNotes", type="text", nullable=true)
     */
    private $moveOutNotes;
    

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
     * @return ResidentPlacements
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
     * Set roomId
     *
     * @param integer $roomId
     * @return ResidentPlacements
     */
    public function setRoomId($roomId)
    {
        $this->roomId = $roomId;

        return $this;
    }

    /**
     * Get roomId
     *
     * @return integer 
     */
    public function getRoomId()
    {
        return $this->roomId;
    }

    /**
     * Set moveIn
     *
     * @param \DateTime $moveIn
     * @return ResidentPlacements
     */
    public function setMoveIn($moveIn)
    {
        $this->moveIn = $moveIn;

        return $this;
    }

    /**
     * Get moveIn
     *
     * @return \DateTime 
     */
    public function getMoveIn()
    {
        return $this->moveIn;
    }
    
    /**
     * Set moveOut
     *
     * @param \DateTime $moveOut
     * @return ResidentPlacements
     */
    public function setMoveOut($moveOut)
    {
        $this->moveOut = $moveOut;

        return $this;
    }

    /**
     * Get moveOut
     *
     * @return \DateTime 
     */
    public function getMoveOut()
    {
        return $this->moveOut;
    }

    /**
     * Set createdOn
     *
     * @param \DateTime $createdOn
     * @return ResidentPlacements
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
     * @return ResidentPlacements
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
     * Set modifiedOn
     *
     * @param \DateTime $modifiedOn
     * @return ResidentPlacements
     */
    public function setModifiedOn($modifiedOn)
    {
        $this->modifiedOn = $modifiedOn;

        return $this;
    }

    /**
     * Get modifiedOn
     *
     * @return \DateTime 
     */
    public function getModifiedOn()
    {
        return $this->modifiedOn;
    }
    
    /**
     * Set modifiedBy
     *
     * @param integer $modifiedBy
     * @return ResidentPlacements
     */
    public function setModifiedBy($modifiedBy)
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    /**
     * Get modifiedBy
     *
     * @return integer 
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }

    /**
     * Set notes
     *
     * @param string $notes
     * @return ResidentPlacements
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
    
    /**
     * Set moveOutNotes
     *
     * @param string $moveOutNotes
     * @return ResidentPlacements
     */
    public function setMoveOutNotes($moveOutNotes)
    {
        $this->moveOutNotes = $moveOutNotes;

        return $this;
    }

    /**
     * Get moveOutNotes
     *
     * @return string 
     */
    public function getMoveOutNotes()
    {
        return $this->moveOutNotes;
    }
}
