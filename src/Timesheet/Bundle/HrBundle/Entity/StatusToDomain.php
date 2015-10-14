<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StatusToDomain
 *
 * @ORM\Entity
 * @ORM\Table(name="StatusToDomain", uniqueConstraints={
 * @ORM\UniqueConstraint(name="status_domain_idx", columns={"statusId", "domainId"})})
 */
class StatusToDomain
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
     * @ORM\Column(name="statusId", type="integer")
     */
    private $statusId;

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
     * Set statusId
     *
     * @param integer $statusId
     * @return StatusToDomain
     */
    public function setStatusId($statusId)
    {
        $this->statusId = $statusId;

        return $this;
    }

    /**
     * Get statusId
     *
     * @return integer 
     */
    public function getStatusId()
    {
        return $this->statusId;
    }

    /**
     * Set domainId
     *
     * @param integer $domainId
     * @return StatusToDomain
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
