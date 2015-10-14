<?php

namespace Timesheet\Bundle\HrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Contract
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Contract
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
     * @ORM\Column(name="UserId", type="integer")
     */
    private $userId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="CSD", type="date")
     */
    private $csd;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="CED", type="date", nullable=true)
     */
    private $ced;

    /**
     * @var float
     *
     * @ORM\Column(name="AWH", type="float")
     */
    private $awh;

    /**
     * @var float
     *
     * @ORM\Column(name="ahe", type="float", nullable=true, options={"comment":"Annual Holiday Entitlement in days"})
     */
    private $ahe;
    
    /**
     * @var float
     *
     * @ORM\Column(name="ahew", type="float", nullable=true, options={"comment":"Annual Holiday Entitlement in weeks"})
     */
    private $ahew;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="wdpw", type="integer", nullable=true, options={"comment":"Working days per week"})
     */
    private $wdpw;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="hct", type="integer", nullable=true, options={"comment":"Holiday Calculation Type"})
     */
    private $hct;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="lunchtime", type="integer", nullable=true, options={"comment":"Paid lunchtime in minutes"})
     */
    private $lunchtime;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="lunchtimeUnpaid", type="integer", nullable=true, options={"comment":"Unpaid lunch time in minutes"})
     */
    private $lunchtimeUnpaid;
    
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="contractType", type="integer")
     */
    private $contractType = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="AHEonYS", type="boolean")
     */
    private $AHEonYS = true;
    
    /**
     * @var float
     *
     * @ORM\Column(name="initHolidays", type="float")
     */
    private $initHolidays = 0;
   
    
    
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
     * @return Contract
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
     * Set csd
     *
     * @param \DateTime $csd
     * @return Contract
     */
    public function setCSD($csd)
    {
        $this->csd = $csd;

        return $this;
    }

    /**
     * Get csd
     *
     * @return \DateTime 
     */
    public function getCSD()
    {
        return $this->csd;
    }

    /**
     * Set ced
     *
     * @param \DateTime $ced
     * @return Contract
     */
    public function setCED($ced)
    {
        $this->ced = $ced;

        return $this;
    }

    /**
     * Get ced
     *
     * @return \DateTime 
     */
    public function getCED()
    {
        return $this->ced;
    }

    /**
     * Set awh
     *
     * @param float $awh
     * @return Contract
     */
    public function setAWH($awh)
    {
        $this->awh = $awh;

        return $this;
    }

    /**
     * Get awh
     *
     * @return float 
     */
    public function getAWH()
    {
        return $this->awh;
    }
    
    /**
     * Set ahe
     *
     * @param float $ahe
     * @return Contract
     */
    public function setAHE($ahe)
    {
        $this->ahe = $ahe;

        return $this;
    }

    /**
     * Get ahe
     *
     * @return float 
     */
    public function getAHE()
    {
        return $this->ahe;
    }

    /**
     * Set ahew
     *
     * @param float $ahew
     * @return Contract
     */
    public function setAHEW($ahew)
    {
    	$this->ahew = $ahew;
    
    	return $this;
    }
    
    /**
     * Get ahew
     *
     * @return float
     */
    public function getAHEW()
    {
    	return $this->ahew;
    }
    
    /**
     * Set hct
     *
     * @param integer $hct
     * @return Contract
     */
    public function setHCT($hct)
    {
        $this->hct = $hct;

        return $this;
    }

    /**
     * Get hct
     *
     * @return integer
     */
    public function getHCT()
    {
        return $this->hct;
    }

    /**
     * Set wdpw
     *
     * @param integer $wdpd
     * @return Contract
     */
    public function setWDpW($wdpw)
    {
    	$this->wdpw = $wdpw;
    
    	return $this;
    }
    
    /**
     * Get wdpd
     *
     * @return integer
     */
    public function getWDpW()
    {
    	return $this->wdpw;
    }
    
    /**
     * Set contractType
     *
     * @param integer $contractType
     * @return Contract
     */
    public function setContractType($contractType)
    {
        $this->contractType = $contractType;

        return $this;
    }

    /**
     * Get contractType
     *
     * @return integer
     */
    public function getContractType()
    {
        return $this->contractType;
    }
    
    /**
     * Set AHEonYS
     *
     * @param boolean $AHEonYS
     * @return Contract
     */
    public function setAHEonYS($AHEonYS)
    {
        $this->AHEonYS = $AHEonYS;

        return $this;
    }

    /**
     * Get AHEonYS
     *
     * @return boolean 
     */
    public function getAHEonYS()
    {
        return $this->AHEonYS;
    }

    /**
     * Set initHolidays
     *
     * @param float $initHolidays
     * @return Contract
     */
    public function setInitHolidays($initHolidays)
    {
        $this->initHolidays = $initHolidays;

        return $this;
    }

    /**
     * Get initHolidays
     *
     * @return float 
     */
    public function getInitHolidays()
    {
        return $this->initHolidays;
    }

    /**
     * Set lunchtime
     *
     * @param integer $lunchtime
     * @return Contract
     */
    public function setLunchtime($lunchtime)
    {
        $this->lunchtime = $lunchtime;

        return $this;
    }

    /**
     * Get lunchtime
     *
     * @return integer
     */
    public function getLunchtime()
    {
        return $this->lunchtime;
    }

    /**
     * Set lunchtimeUnpaid
     *
     * @param integer $lunchtimeUnpaid
     * @return Contract
     */
    public function setLunchtimeUnpaid($lunchtimeUnpaid)
    {
        $this->lunchtimeUnpaid = $lunchtimeUnpaid;

        return $this;
    }

    /**
     * Get lunchtimeUnpaid
     *
     * @return integer
     */
    public function getLunchtimeUnpaid()
    {
        return $this->lunchtimeUnpaid;
    }
}
