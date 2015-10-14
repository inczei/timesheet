<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Rooms
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Rooms
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
     * @ORM\Column(name="roomNumber", type="string", length=8)
     */
    private $roomNumber;

    /**
     * @var integer
     *
     * @ORM\Column(name="locationId", type="integer")
     */
    private $locationId;

    /**
     * @var integer
     *
     * @ORM\Column(name="places", type="integer")
     */
    private $places;

    /**
     * @var integer
     *
     * @ORM\Column(name="extraPlaces", type="integer")
     */
    private $extraPlaces;

    /**
     * @var boolean
     *
     * @ORM\Column(name="open", type="boolean")
     */
    private $open;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text")
     */
    private $notes;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;


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
     * Set roomNumber
     *
     * @param string $roomNumber
     * @return Rooms
     */
    public function setRoomNumber($roomNumber)
    {
        $this->roomNumber = $roomNumber;

        return $this;
    }

    /**
     * Get roomNumber
     *
     * @return string 
     */
    public function getRoomNumber()
    {
        return $this->roomNumber;
    }

    /**
     * Set locationId
     *
     * @param integer $locationId
     * @return Rooms
     */
    public function setLocationId($locationId)
    {
        $this->locationId = $locationId;

        return $this;
    }

    /**
     * Get locationId
     *
     * @return integer 
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * Set places
     *
     * @param integer $places
     * @return Rooms
     */
    public function setPlaces($places)
    {
        $this->places = $places;

        return $this;
    }

    /**
     * Get places
     *
     * @return integer 
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * Set extraPlaces
     *
     * @param integer $extraPlaces
     * @return Rooms
     */
    public function setExtraPlaces($extraPlaces)
    {
        $this->extraPlaces = $extraPlaces;

        return $this;
    }

    /**
     * Get extraPlaces
     *
     * @return integer 
     */
    public function getExtraPlaces()
    {
        return $this->extraPlaces;
    }

    /**
     * Set open
     *
     * @param boolean $open
     * @return Rooms
     */
    public function setOpen($open)
    {
        $this->open = $open;

        return $this;
    }

    /**
     * Get open
     *
     * @return boolean 
     */
    public function getOpen()
    {
        return $this->open;
    }

    /**
     * Set notes
     *
     * @param string $notes
     * @return Rooms
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
     * Set active
     *
     * @param boolean $active
     * @return Rooms
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }
}
