<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * UserQualifications
 *
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="userqualification_idx", columns={"userId", "qualificationId", "achievementDate"})})
 * @ORM\Entity
 */
class UserQualifications
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
     * @ORM\Column(name="userId", type="integer")
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="qualificationId", type="integer")
     */
    private $qualificationId;

    /**
     * @var integer
     *
     * @ORM\Column(name="levelId", type="integer")
     */
    private $levelId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="comments", type="string", length=255)
     */
    private $comments;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="achievementDate", type="date")
     */
    private $achievementDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiryDate", type="date", nullable=true)
     */
    private $expiryDate;

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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return UserQualifications
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

    /**
     * Set qualificationId
     *
     * @param integer $qualificationId
     * @return UserQualifications
     */
    public function setQualificationId($qualificationId)
    {
        $this->qualificationId = $qualificationId;

        return $this;
    }

    /**
     * Get qualificationId
     *
     * @return integer 
     */
    public function getQualificationId()
    {
        return $this->qualificationId;
    }
    
    /**
     * Set levelId
     *
     * @param integer $levelId
     * @return UserQualifications
     */
    public function setLevelId($levelId)
    {
        $this->levelId = $levelId;

        return $this;
    }

    /**
     * Get levelId
     *
     * @return integer 
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * Set comments
     *
     * @param string $comments
     * @return UserQualifications
     */
    public function setComments($comments)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * Get comments
     *
     * @return string 
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set achievementDate
     *
     * @param \DateTime $achievementDate
     * @return UserQualifications
     */
    public function setAchievementDate($achievementDate)
    {
        $this->achievementDate = $achievementDate;

        return $this;
    }

    /**
     * Get achievementDate
     *
     * @return \DateTime 
     */
    public function getAchievementDate()
    {
        return $this->achievementDate;
    }

    /**
     * Set expiryDate
     *
     * @param \DateTime $expiryDate
     * @return UserQualifications
     */
    public function setExpiryDate($expiryDate)
    {
        $this->expiryDate = $expiryDate;

        return $this;
    }

    /**
     * Get expiryDate
     *
     * @return \DateTime 
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * Set createdOn
     *
     * @param \DateTime $createdOn
     * @return UserQualifications
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
     * @return UserQualifications
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
     * @Assert\True(message="Achievement date cannot be in the future")
     * @return boolean
     */
    public function isAchievementDateValid() {
error_log('isAchievementDateValid');
    	return ($this->achievementDate < date('Y-m-d H:i:s'));
    }

    /**
     * @Assert\True(message="Expiry date cannot be before Achievement date")
     * @return boolean
     */
    public function isExpiryDateValid() {
error_log('isExpiryDateValid');
    	return (isset($this->expiryDate) && $this->expiryDate!=null && $this->expiryDate>$this->achievementDate);
    }
}
