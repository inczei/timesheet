<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FPReaderToUser
 *
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="fprtouser_idx", columns={"readerId", "readerUserId"})})
 * @ORM\Entity
 */
class FPReaderToUser
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
     * @ORM\Column(name="userId", type="integer")
     */
    private $userId;


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
     * @return FPReaderToUser
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
     * @return FPReaderToUser
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
     * Set userId
     *
     * @param integer $userId
     * @return FPReaderToUser
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }
}
