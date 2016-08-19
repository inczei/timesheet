<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ExportTemplateFields
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class ExportTemplateFields
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
     * @ORM\Column(name="templateId", type="integer")
     */
    private $templateId;

    /**
     * @var integer
     *
     * @ORM\Column(name="fieldId", type="integer")
     */
    private $fieldId;

    /**
     * @var string
     *
     * @ORM\Column(name="fieldName", type="string", length=50)
     */
    private $fieldName;

    /**
     * @var integer
     *
     * @ORM\Column(name="fieldFormatId", type="integer")
     */
    private $fieldFormatId;

    /**
     * @var integer
     *
     * @ORM\Column(name="fieldPosition", type="integer")
     */
    private $fieldPosition;


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
     * Set templateId
     *
     * @param integer $templateId
     * @return ExportTemplateFields
     */
    public function setTemplateId($templateId)
    {
        $this->templateId = $templateId;

        return $this;
    }

    /**
     * Get templateId
     *
     * @return integer 
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }

    /**
     * Set fieldId
     *
     * @param integer $fieldId
     * @return ExportTemplateFields
     */
    public function setFieldId($fieldId)
    {
        $this->fieldId = $fieldId;

        return $this;
    }

    /**
     * Get fieldId
     *
     * @return integer 
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * Set fieldName
     *
     * @param string $fieldName
     * @return ExportTemplateFields
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    /**
     * Get fieldName
     *
     * @return string 
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Set fieldFormatId
     *
     * @param integer $fieldFormatId
     * @return ExportTemplateFields
     */
    public function setFieldFormatId($fieldFormatId)
    {
        $this->fieldFormatId = $fieldFormatId;

        return $this;
    }

    /**
     * Get fieldFormatId
     *
     * @return integer 
     */
    public function getFieldFormatId()
    {
        return $this->fieldFormatId;
    }

    /**
     * Set fieldPosition
     *
     * @param integer $fieldPosition
     * @return ExportTemplateFields
     */
    public function setFieldPosition($fieldPosition)
    {
        $this->fieldPosition = $fieldPosition;

        return $this;
    }

    /**
     * Get fieldPosition
     *
     * @return integer 
     */
    public function getFieldPosition()
    {
        return $this->fieldPosition;
    }
}
