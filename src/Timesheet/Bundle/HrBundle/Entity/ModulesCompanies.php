<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ModulesCompanies
 *
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="moddom_idx", columns={"moduleId", "domainId"})})
 * @ORM\Entity */
class ModulesCompanies
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
     * @ORM\Column(name="moduleId", type="integer")
     */
    private $moduleId;

    /**
     * @var integer
     *
     * @ORM\Column(name="domainId", type="integer")
     */
    private $domainId;


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
     * Set moduleId
     *
     * @param integer $moduleId
     * @return ModulesCompanies
     */
    public function setModuleId($moduleId)
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    /**
     * Get moduleId
     *
     * @return integer 
     */
    public function getModuleId()
    {
        return $this->moduleId;
    }

    /**
     * Set domainId
     *
     * @param integer $domainId
     * @return ModulesCompanies
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
}
