<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RequestType
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class RequestType
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
     * @ORM\Column(name="name", type="string", length=20)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=255)
     */
    private $comment;

    /**
     * @var integer
     *
     * @ORM\Column(name="fullday", type="integer")
     */
    private $fullday = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="bothtime", type="integer")
     */
    private $bothtime = 1;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="paid", type="boolean")
     */
    private $paid;

    /**
     * @var boolean
     *
     * @ORM\Column(name="adminOnly", type="boolean")
     */
    private $adminOnly;

    /**
     * @var string
     *
     * @ORM\Column(name="textColor", type="string", length=6)
     */
    private $textColor;
    
    /**
     * @var string
     *
     * @ORM\Column(name="backgroundColor", type="string", length=6)
     */
    private $backgroundColor;
    
    /**
     * @var string
     *
     * @ORM\Column(name="borderColor", type="string", length=6)
     */
    private $borderColor;
    
    /**
     * @var string
     *
     * @ORM\Column(name="initial", type="string", length=3)
     */
    private $initial;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="entitlement", type="integer")
     */
    private $entitlement = 0;
    
    
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
     * @return RequestType
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
     * Set comment
     *
     * @param string $comment
     * @return RequestType
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
     * Set fullday
     *
     * @param integer $fullday
     * @return RequestType
     */
    public function setFullday($fullday)
    {
        $this->fullday = $fullday;

        return $this;
    }

    /**
     * Get fullday
     *
     * @return integer 
     */
    public function getFullday()
    {
        return $this->fullday;
    }
    
    /**
     * Set bothtime
     *
     * @param integer $bothtime
     * @return RequestType
     */
    public function setBothtime($bothtime)
    {
        $this->bothtime = $bothtime;

        return $this;
    }

    /**
     * Get bothtime
     *
     * @return integer 
     */
    public function getBothtime()
    {
        return $this->bothtime;
    }

    /**
     * Set paid
     *
     * @param boolean $paid
     * @return RequestType
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;

        return $this;
    }

    /**
     * Get paid
     *
     * @return boolean 
     */
    public function getPaid()
    {
        return $this->paid;
    }

    /**
     * Set adminOnly
     *
     * @param boolean $adminOnly
     * @return RequestType
     */
    public function setAdminOnly($adminOnly)
    {
        $this->adminOnly = $adminOnly;

        return $this;
    }

    /**
     * Get adminOnly
     *
     * @return boolean 
     */
    public function getAdminOnly()
    {
        return $this->adminOnly;
    }

    /**
     * Set textColor
     *
     * @param string $textColor
     * @return RequestType
     */
    public function setTextColor($textColor)
    {
        $this->textColor = $textColor;

        return $this;
    }

    /**
     * Get textColor
     *
     * @return string 
     */
    public function getTextColor()
    {
        return $this->textColor;
    }
    
    /**
     * Set backgroundColor
     *
     * @param string $backgroundColor
     * @return RequestType
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    /**
     * Get backgroundColor
     *
     * @return string 
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }
    
    /**
     * Set borderColor
     *
     * @param string $borderColor
     * @return RequestType
     */
    public function setBorderColor($borderColor)
    {
        $this->borderColor = $borderColor;

        return $this;
    }

    /**
     * Get borderColor
     *
     * @return string 
     */
    public function getBorderColor()
    {
        return $this->borderColor;
    }
    
    /**
     * Set initial
     *
     * @param string $initial
     * @return RequestType
     */
    public function setInitial($initial)
    {
        $this->initial = $initial;

        return $this;
    }

    /**
     * Get initial
     *
     * @return string 
     */
    public function getInitial()
    {
        return $this->initial;
    }

    /**
     * Set entitlement
     *
     * @param integer $entitlement
     * @return RequestType
     */
    public function setEntitlement($entitlement)
    {
        $this->entitlement = $entitlement;

        return $this;
    }

    /**
     * Get entitlement
     *
     * @return integer 
     */
    public function getEntitlement()
    {
        return $this->entitlement;
    }
}
