<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FPReaderTemplates
 *
 * @ORM\Table(name="FPReaderTemplates", uniqueConstraints={
 * @ORM\UniqueConstraint(name="fpreader_templates_idx", columns={"readerId", "readerUserId", "fingerId", "version"})})
 * @ORM\Entity
 */
class FPReaderTemplates
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
     * @ORM\Column(name="readerId", type="integer")
     */
    private $readerId;

    /**
     * @var integer
     *
     * @ORM\Column(name="readerUserId", type="integer")
     */
    private $readerUserId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="fingerId", type="integer")
     */
    private $fingerId;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="text")
     */
    private $template;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=10)
     */
    private $version = 'v9';
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updatedOn", type="datetime")
     */
    private $updatedOn;


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
     * Set readerId
     *
     * @param integer $readerId
     * @return FPReaderTemplates
     */
    public function setReaderId($readerId)
    {
        $this->readerId = $readerId;

        return $this;
    }

    /**
     * Get readerId
     *
     * @return integer 
     */
    public function getReaderId()
    {
        return $this->readerId;
    }
    
    /**
     * Set readerUserId
     *
     * @param integer $readerUserId
     * @return FPReaderTemplates
     */
    public function setReaderUserId($readerUserId)
    {
        $this->readerUserId = $readerUserId;

        return $this;
    }

    /**
     * Get readerUserId
     *
     * @return integer 
     */
    public function getReaderUserId()
    {
        return $this->readerUserId;
    }

    /**
     * Set fingerId
     *
     * @param integer $fingerId
     * @return FPReaderTemplates
     */
    public function setFingerId($fingerId)
    {
        $this->fingerId = $fingerId;

        return $this;
    }

    /**
     * Get fingerId
     *
     * @return integer 
     */
    public function getFingerId()
    {
        return $this->fingerId;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return FPReaderTemplates
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return string 
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set version
     *
     * @param string $version
     * @return FPReaderTemplates
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return string 
     */
    public function getVersion()
    {
        return $this->version;
    }
    
    /**
     * Set updatedOn
     *
     * @param \DateTime $updatedOn
     * @return FPReaderTemplates
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get updatedOn
     *
     * @return \DateTime 
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }
}
