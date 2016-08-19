<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ExportTemplates
 *
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="et_idx", columns={"name", "domainId"})})
 * @ORM\Entity
 */
class ExportTemplates
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
     * @ORM\Column(name="domainId", type="integer")
     */
    private $domainId;

    /**
     * @var integer
     *
     * @ORM\Column(name="pageId", type="integer")
     */
    private $pageId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     */
    private $name;

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
     * @ORM\Column(name="comment", type="string", length=255)
     */
    private $comment = '';

    /**
     * @var string
     *
     * @ORM\Column(name="format", type="string", length=4)
     */
    private $format = 'PDF';

    /**
     * @var boolean
     *
     * @ORM\Column(name="available", type="boolean")
     */
    private $available = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="heading", type="boolean")
     */
    private $heading = 1;
    
    
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
     * Set domainId
     *
     * @param integer $domainId
     * @return ExportTemplates
     */
    public function setDomainId($domainId)
    {
        $this->domainId = $domainId;

        return $this;
    }

    /**
     * Get domainId
     *
     * @return integer 
     */
    public function getDomainId()
    {
        return $this->domainId;
    }

    /**
     * Set pageId
     *
     * @param integer $pageId
     * @return ExportTemplates
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;

        return $this;
    }

    /**
     * Get pageId
     *
     * @return integer 
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return ExportTemplates
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
     * Set createdOn
     *
     * @param \DateTime $createdOn
     * @return ExportTemplates
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
     * @return ExportTemplates
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
     * Set comment
     *
     * @param string $comment
     * @return ExportTemplates
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
     * Set format
     *
     * @param string $format
     * @return ExportTemplates
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get format
     *
     * @return string 
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set available
     *
     * @param boolean $available
     * @return ExportTemplates
     */
    public function setAvailable($available)
    {
        $this->available = $available;

        return $this;
    }

    /**
     * Get available
     *
     * @return boolean 
     */
    public function getAvailable()
    {
        return $this->available;
    }
    
    /**
     * Set heading
     *
     * @param boolean $heading
     * @return ExportTemplates
     */
    public function setHeading($heading)
    {
        $this->heading = $heading;

        return $this;
    }

    /**
     * Get heading
     *
     * @return boolean 
     */
    public function getHeading()
    {
        return $this->heading;
    }
}
