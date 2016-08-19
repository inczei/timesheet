<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ExportTemplateFieldFormats
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class ExportTemplateFieldFormats
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
     * @ORM\Column(name="formatName", type="string", length=50)
     */
    private $formatName;

    /**
     * @var string
     *
     * @ORM\Column(name="formatExt", type="string", length=8)
     */
    private $formatExt;


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
     * Set formatName
     *
     * @param string $formatName
     * @return ExportTemplateFieldFormats
     */
    public function setFormatName($formatName)
    {
        $this->formatName = $formatName;

        return $this;
    }

    /**
     * Get formatName
     *
     * @return string 
     */
    public function getFormatName()
    {
        return $this->formatName;
    }

    /**
     * Set formatExt
     *
     * @param string $formatExt
     * @return ExportTemplateFieldFormats
     */
    public function setFormatExt($formatExt)
    {
        $this->formatExt = $formatExt;

        return $this;
    }

    /**
     * Get formatExt
     *
     * @return string 
     */
    public function getFormatExt()
    {
        return $this->formatExt;
    }
}
