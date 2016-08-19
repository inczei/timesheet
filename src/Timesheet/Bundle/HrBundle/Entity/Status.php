<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Status
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Status
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
     * @ORM\Column(name="Name", type="string", length=20, unique=true)
     */
    private $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Start", type="boolean")
     */
    private $start = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Multi", type="boolean")
     */
    private $multi = false;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="Pair", type="integer")
     */
    private $pair;

    /**
     * @var integer
     *
     * @ORM\Column(name="Level", type="integer")
     */
    private $level = 0;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="Color", type="string", length=6)
     */
    private $color = '000000';
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Active", type="boolean")
     */
    private $active = true;


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
     * Set name
     *
     * @param string $name
     * @return Status
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set start
     *
     * @param boolean $start
     * @return Status
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get start
     *
     * @return boolean 
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set multi
     *
     * @param boolean $multi
     * @return Status
     */
    public function setMulti($multi)
    {
        $this->multi = $multi;

        return $this;
    }

    /**
     * Get multi
     *
     * @return boolean 
     */
    public function getMulti()
    {
        return $this->multi;
    }
    
    /**
     * Set pair
     *
     * @param integer $pair
     * @return Status
     */
    public function setPair($pair)
    {
        $this->pair = $pair;

        return $this;
    }

    /**
     * Get pair
     *
     * @return integer 
     */
    public function getPair()
    {
        return $this->pair;
    }
    
    /**
     * Set level
     *
     * @param integer $level
     * @return Status
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return integer 
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set color
     *
     * @param string $color
     * @return Status
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }
    
    /**
     * Set active
     *
     * @param boolean $active
     * @return Status
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
