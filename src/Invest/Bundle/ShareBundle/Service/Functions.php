<?php

/*
 * Author: Imre Incze
 * 
 */

namespace Invest\Bundle\ShareBundle\Service;

// use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Invest\Bundle\ShareBundle\Entity\Config;
use Invest\Bundle\ShareBundle\Entity\Company;
use Invest\Bundle\ShareBundle\Entity\Diary;
use Invest\Bundle\ShareBundle\Entity\Trade;
use Invest\Bundle\ShareBundle\Entity\TradeTransactions;
use Invest\Bundle\ShareBundle\Entity\DirectorsDeals;
use Invest\Bundle\ShareBundle\Entity\Dividend;
use Invest\Bundle\ShareBundle\Entity\Portfolio;
use Invest\Bundle\ShareBundle\Entity\PortfolioTransaction;
use Invest\Bundle\ShareBundle\Entity\Transaction;
use Invest\Bundle\ShareBundle\Entity\StockPrices;
use Invest\Bundle\ShareBundle\Entity\StockPricesWrong;
use Invest\Bundle\ShareBundle\Entity\Summary;
use Invest\Bundle\ShareBundle\Entity\Currency;
use Symfony\Component\Validator\Validator;
use Invest\Bundle\ShareBundle\InvestShareBundle;
// use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Ps\PdfBundle\Annotation\Pdf;
use Symfony\Component\DependencyInjection\ContainerAware;
use Doctrine\ORM\EntityManager;

class Functions extends ContainerAware
{
	
	protected $em;
	
	protected $doctrine;
	
	protected $startTaxYear = '04-06';
	
	protected $startTaxYearMonth = 4;
	
	protected $startTaxYearDay = 6;
	
	protected $dealsLimit = 60000;
	
	protected $dividendWarningDays = 7;
	
	protected $currencyNeeded=array();
	
	protected $defaultCurrencies=array('EUR', 'USD', 'AUD', 'HUF', 'PHP');

	protected $pager = 20;
	

    
    
/*
 * Private functions
 * - usort functions
 * - align data
 * - get data
 */
    
	public function __construct($doctrine) {
		$this->doctrine = $doctrine; 
		$this->em = $doctrine->getManager();
	}
	

    public function getConfig($key) {
/*
 * read the config table by name
 */  	
    	$result=$this->doctrine
    		->getRepository('InvestShareBundle:Config')
    		->findBy(
    			array(
    				'name'=>$key,
    			),
    			array(),
    			1
    		);

   		return ((count($result))?($result[0]->getValue()):(''));
    }
    
    
    public function updateSummary() {
    	
    	$currencyRates=$this->getCurrencyRates();
/*
 * update the summary table, only when we show that
 */     
    	
    	$portfolios=$this->doctrine
    		->getRepository('InvestShareBundle:Portfolio')
    		->findAll();
    	
    	if ($portfolios && count($portfolios)) {
/*
 * get config values
 */ 
    		
    		
    		$CgtAllowance=$this->getConfig('cgt_allowance_2014_2015');
    		$BasicRate=$this->getConfig('basic_rate_treshold_2014_2015');
    		
    		
    		foreach ($portfolios as $portfolio) {
    			$pId=$portfolio->getId();
    			$summary=$this->doctrine
    				->getRepository('InvestShareBundle:Summary')
    				->findOneBy(
    					array(
    						'portfolioId' => $pId
    					),
    					array(),
    					1);
    			
    			if ($summary) {
    				$new=false;
    			} else {
    				$summary=new Summary();
    				$new=true;
    				$summary->setPortfolioId($pId);
    			}
/*
 * complex sql query for update
 * "id", "name" and "startAmount" of portfolio
 * all the added transactions, debit(+)/credit(-) as "Debit"
 * summary of the bought prices*quantity as "Investment"
 * summary of the amounts of dividends between buy and sell date as "Dividend"
 * if sold, calculate the "Profit" based on the sold price and buy price
 * calculate the "CurrentStock"
 * calculate the "PaidDividend"
 */

    			$pData=$this->getTradesData($pId, null, null, null);
    			
				$data=array(
					'CurrentDividend'=>0,
					'DividendPaid'=>0,
					'Investment'=>0,
					'CurrentValue'=>0,
					'CurrentValueBySector'=>array(),
					'CashIn'=>0,
					'ActualDividendIncome'=>0,
					'Profit'=>0,
					'CgtProfitsRealised'=>0,
					'Family'=>$portfolio->getFamily()
				);

    			if ($pData) {
    				foreach ($pData as $p) {
						if ($p['reference2'] == '') {
/*
 * Unsold
 */
    						$data['Investment']+=$p['quantity1']*$p['unitPrice1']/100+$p['cost1'];
							$data['CurrentValue']+=$p['quantity1']*$p['lastPrice']/100;
							if (!isset($data['CurrentValueBySector'][$p['sector']])) {
								$data['CurrentValueBySector'][$p['portfolioName']][$p['sector']]=0;
							}
							$data['CurrentValueBySector'][$p['portfolioName']][$p['sector']]+=$p['quantity1']*$p['lastPrice']/100;
							$data['Profit']+=(($p['quantity1']*$p['lastPrice']/100)-($p['quantity1']*$p['unitPrice1']/100+$p['cost1']));
								
						} else {
/*
 * Sold
 */							
							$data['Profit']+=(($p['quantity2']*$p['unitPrice2']/100-$p['cost2'])-($p['quantity1']*$p['unitPrice1']/100+$p['cost1']));
							$data['CgtProfitsRealised']+=(($p['quantity2']*$p['unitPrice2']/100-$p['cost2'])-($p['quantity1']*$p['unitPrice1']/100+$p['cost1']));
						}

						$rate=(($p['Currency']=='GBP')?(1):($currencyRates[$p['Currency']]/100));

						if (isset($p['DividendRate']) && $p['DividendRate']) {
							$data['CurrentDividend']+=$p['Dividend']/$p['DividendRate']*100;
							$data['DividendPaid']+=$p['DividendPaid']/$p['DividendRate']*100;
						} else {					
							$data['CurrentDividend']+=$p['Dividend']/$rate;
							$data['DividendPaid']+=$p['DividendPaid']/$rate;
						}

						$data['ActualDividendIncome']+=$p['DividendPaid']/$rate;
    				}
    			}
    			
    			$pt=$this->doctrine
    				->getRepository('InvestShareBundle:PortfolioTransaction')
    				->findBy(
    					array(
    						'PortfolioId'=>$pId
    					)
    				);
    			
    			if ($pt) {
    				foreach ($pt as $pt1) {
    					$data['CashIn']+=$pt1->getAmount();
    				}
    			}

    			
    			$summary->setCurrentDividend($data['CurrentDividend']);
    			$summary->setInvestment($data['Investment']);
    			$summary->setCurrentValue($data['CurrentValue']);
    			$summary->setCurrentValueBySector(json_encode($data['CurrentValueBySector']));
    			$summary->setProfit($data['Profit']);
    			$summary->setDividendPaid($data['DividendPaid']);
    			$summary->setRealisedProfit($data['CgtProfitsRealised']+$data['CurrentDividend']);
    			$summary->setDividendYield((($data['Investment']!=0)?($data['CurrentDividend']/$data['Investment']):(0)));
    			$summary->setCurrentROI(($data['Investment']!=0)?(($data['CurrentValue']-$data['Investment']+$data['ActualDividendIncome']+$data['CgtProfitsRealised'])/$data['Investment']):(0));
    			$summary->setCashIn($data['CashIn']);
    			$summary->setUnusedCash($data['CashIn']+$data['CgtProfitsRealised']-$data['Investment']);
    			$summary->setActualDividendIncome($data['ActualDividendIncome']);
    			$summary->setCgtProfitsRealised($data['CgtProfitsRealised']);
    			$summary->setUnusedCgtAllowance(($CgtAllowance/$data['Family'])-$data['CgtProfitsRealised']);
    			$summary->setUnusedBasicRateBand(($BasicRate/$data['Family'])-$data['ActualDividendIncome']);
    			$summary->setFamily($data['Family']);
    			 
    			$summary->setUpdatedOn(new \Datetime('now'));
    			
    			if ($new) {
    				$this->em->persist($summary);
    			}
    			$this->em->flush();
    			
    			if (!$summary->getId()) {
    				error_log('Summary update error');
    			}
    			
    		}
    	} else {
/*
 * No portfolio, no update
 */
			$summary=$this->doctrine
    			->getRepository('InvestShareBundle:Summary')
    			->findAll();
    			
    		if ($summary) {
    			foreach ($summary as $sum) {
    				$this->em->remove($sum);
    				$this->em->flush();
    			}
    		}
    			
    		return false;
    	}

    	return true;
    }
    
    
    public static function typeSort($a, $b) {
    	if ($a['type'] == $b['type']) {
    		if ($a['settleDate'] == $b['settleDate']) {
    			if ($a['reference'] == $b['reference']) {

    				return 0;
    			}
    			
    			return ($a['reference'] > $b['reference'])?1:-1;
    		}
    		
    		return ($a['settleDate'] > $b['settleDate'])?1:-1;
    	}
    	
    	return ($a['type'] > $b['type'])?1:-1;
    }
    
    
    public static function buySort($a, $b) {
    	if ($a['tradeId'] == $b['tradeId']) {
    		if ($a['settleDate1'] == $b['settleDate1']) {
    			if ($a['reference1'] == $b['reference1']) {
	    			if (strlen($a['reference2']) == strlen($b['reference2'])) {

	    				return 0;
	    			}
	    			
	    			return (strlen($a['reference2']) < strlen($b['reference2']))?1:-1;
    			}
    			
    			return ($a['reference1'] > $b['reference1'])?1:-1;
    		}
    		
    		return ($a['settleDate1'] > $b['settleDate1'])?1:-1;
    	}
    	
    	return ($a['tradeId'] > $b['tradeId'])?1:-1;
    }
    
    
    public static function divSort($a, $b) {
    	if ($a['Name'] == $b['Name']) {
    		if ($a['ExDivDate'] == $b['ExDivDate']) {
    			
				return 0;
    		}
    		
    		return ($a['ExDivDate'] > $b['ExDivDate'])?1:-1;
    	}
    	
    	return ($a['Name'] > $b['Name'])?1:-1;
    }
    
    
    public static function divDateSort($a, $b) {
   		if ($a['ExDivDate'] == $b['ExDivDate']) {
    		if ($a['Name'] == $b['Name']) {

    			return 0;
    		}
    		
    		return ($a['Name'] > $b['Name'])?1:-1;
    	}
    	
    	return ($a['ExDivDate'] > $b['ExDivDate'])?1:-1;
    }
    	
	
	public function alignSellTrades($t, $tmpBuyTrades, &$combined) {
		
		$connection=$this->em->getConnection();
		
		$query3='SELECT * FROM `TradeTransactions` WHERE `type`=1 AND `tradeId`=:tId ORDER BY `tradeDate`, `reference`';
		$stmt=$connection->prepare($query3);
		$stmt->bindValue('tId', $t['tradeId']);
		$stmt->execute();
		$tmpSellTrades=$stmt->fetchAll();

		foreach ($tmpBuyTrades as $bt) {
			$combined[]=array(
				'type'=>0,
				'portfolioId'=>$t['PortfolioId'],
				'portfolioName'=>$t['PortfolioName'],
				'companyId'=>$t['CompanyId'],
				'companyCode'=>$t['CompanyCode'],
				'companyName'=>$t['CompanyName'],
				'sector'=>$t['Sector'],
				'lastPrice'=>$t['lastPrice'],
				'clientNumber'=>$t['clientNumber'],
				'tradeId'=>$t['tradeId'],
				'PeRatio'=>$t['PE_Ratio'],
				'reference1'=>$bt['reference'],
				'settleDate1'=>$bt['settleDate'],
				'tradeDate1'=>$bt['tradeDate'],
				'quantity1'=>$bt['quantity'],
				'unitPrice1'=>$bt['unitPrice'],
				'cost1'=>$bt['cost'],
						
				'reference2'=>'',
				'settleDate2'=>'',
				'tradeDate2'=>'',
				'quantity2'=>'',
				'unitPrice2'=>'',
				'cost2'=>'',
						
				'noOfDaysInvested'=>0,
				'rows'=>1,
				'Currency'=>$t['Currency'],
				'comment'=>''
			);
			
		}
		
		$usedSellTrades=array();
		$quantity=array();
		foreach ($tmpSellTrades as $st) {
			$quantity[$st['tradeId']]=0;
		}
		
		for ($i=0; $i<count($tmpSellTrades); $i++) {

			$st=$tmpSellTrades[$i];
			$ok=false;
			foreach ($combined as $k=>$c) {
				if (!in_array($c['reference2'], $usedSellTrades)) {
					if (!$ok && $c['reference2']!=$st['reference']) {
						if ($st['tradeId'] == $c['tradeId']) {
							if ($st['tradeDate'] > $c['tradeDate1']) {
								$quantity[$st['tradeId']]+=$c['quantity1'];
								if ($quantity[$st['tradeId']] >= $st['quantity']) {

									$ok=true;
									$combined[$k]['reference2']=$st['reference'];
									$combined[$k]['tradeDate2']=$st['tradeDate'];
									$combined[$k]['settleDate2']=$st['settleDate'];
									$combined[$k]['reference2']=$st['reference'];
									
									$combined[$k]['quantity2']=$st['quantity'];
									// we need the remaining quantity only
									$quantity[$st['tradeId']]-=$st['quantity'];

									$combined[$k]['unitPrice2']=$st['unitPrice'];
									$combined[$k]['cost2']=$st['cost'];
		
									$days=(strtotime($st['tradeDate'])-strtotime($c['tradeDate1']))/(24*60*60);
		
									$combined[$k]['noOfDaysInvested']=$days;
									$usedSellTrades[]=$st['reference'];
									$st['tradeDate']-=$c['tradeDate1'];

								} else {

									$ok=true;
									$combined[$k]['reference2']=$st['reference'];
									$combined[$k]['tradeDate2']=$st['tradeDate'];
									$combined[$k]['settleDate2']=$st['settleDate'];
									$combined[$k]['reference2']=$st['reference'];
									
									$combined[$k]['quantity2']=$quantity[$st['tradeId']];

									if ($quantity[$st['tradeId']] > $quantity[$st['tradeId']]) {
										$i++;
									}
									
									$combined[$k]['unitPrice2']=$st['unitPrice'];
									$combined[$k]['cost2']=$st['cost'];
									$st['quantity']-=$quantity[$st['tradeId']];
// decrease the summary of the quantity to remove all the remainig amount and duplicates
									$tmpSellTrades[$i]['quantity']-=$quantity[$st['tradeId']];
									
									$quantity[$st['tradeId']]-=$c['quantity1'];
										
									$days=(strtotime($st['tradeDate'])-strtotime($c['tradeDate1']))/(24*60*60);
									
									$combined[$k]['noOfDaysInvested']=$days;
									$usedSellTrades[]=$st['reference'];
									$st['tradeDate']-=$c['tradeDate1'];
									$i--;

								}
							}
						}
					}
				}
			}
		}
		return $combined;		
	}
	
	
    public function getTradesData($searchPortfolio, $searchCompany, $searchSector, $searchSold) {

    	$combined=array();
    	$connection=$this->em->getConnection();
    	
    	$query1='SELECT'.
    			' `tt`.`tradeId`,'.
    			' `t`.`CompanyId`,'.
    			' `c`.`Name` `CompanyName`,'.
    			' `c`.`Code` `CompanyCode`,'.
    			' `c`.`Currency`,'.
    			' `c`.`lastPrice`,'.
    			' `c`.`Sector`,'.
    			' `p`.`clientNumber`,'.
    			' `t`.`PortfolioId`,'.
    			' `t`.`PE_Ratio`,'.
    			' `p`.`Name` `PortfolioName`'.
    			' FROM `Trade` `t`'.
    			' JOIN `TradeTransactions` `tt` ON `t`.`id`=`tt`.`tradeId`'.
    			' JOIN `Company` `c` ON `t`.`CompanyId`=`c`.`id`'.
    			' JOIN `Portfolio` `p` ON `t`.`PortfolioId`=`p`.`id`'.
    			' WHERE 1'.
    			(($searchSector)?(' AND `c`.`Sector`="'.$searchSector.'"'):('')).
    			(($searchCompany)?(' AND `c`.`id`="'.$searchCompany.'"'):('')).
    			(($searchPortfolio)?(' AND `p`.`id`="'.$searchPortfolio.'"'):('')).

    	' GROUP BY `tt`.`tradeId`'.
    	' ORDER BY `tt`.`tradeId`';

    	$stmt=$connection->prepare($query1);
    	$stmt->execute();
    	$tmpTrades=$stmt->fetchAll();
    	
    	if ($tmpTrades) {
    		foreach ($tmpTrades as $t) {
    			$query2='SELECT * FROM `TradeTransactions` WHERE `type`=0 AND `tradeId`=:tId ORDER BY `tradeDate`, `reference`';
    			$stmt=$connection->prepare($query2);
    			$stmt->bindValue('tId', $t['tradeId']);
    			$stmt->execute();
    			$tmpBuyTrades=$stmt->fetchAll();

    			$this->alignSellTrades($t, $tmpBuyTrades, $combined);
    	
    		}
    	}
    	$ok=false;
    	
    	while (!$ok) {
    		$additional=array();
    		if (count($combined)) {
    			foreach ($combined as $k=>$c) {
    				if ($c['reference2'] != '' && $c['quantity1']>$c['quantity2']) {
    					$rate=($c['quantity2'] / $c['quantity1']);
    					$add = $c;
    					$add['type'] = 0;
    					$add['quantity1'] = $c['quantity1']-$c['quantity2'];
    					$add['cost1'] = $c['cost1']*(1-$rate);
    					$add['reference2'] = '';
    					$add['settleDate2'] = null;
    					$add['tradeDate2'] = null;
    					$add['quantity2'] = 0;
    					$add['unitPrice2'] = 0;
    					$add['cost2'] = 0;
    					$add['noOfDaysInvested'] = 0;
    					$add['rows'] = 1;
    					$add['tradeId'] = $c['tradeId'];
    					$add['Currency'] = $c['Currency'];
    						
    					$combined[$k]['quantity1'] = $c['quantity2'];
    					$combined[$k]['cost1'] = $c['cost1']*$rate;

    					$additional[] = $add;
    				}
    			}
    		}
    			
    	
    		if (count($additional)) {
    			$combined=array_merge($combined, $additional);
    			usort($combined, 'self::buySort');
    	
    		} else {
    			$ok=true;
    		}

    		if (count($additional)) {
    	
    			$tmpCombined=array();
    			foreach ($tmpTrades as $t) {
    				unset($tmpBuyTrades);
    				$tmpBuyTrades=array();
    				foreach ($combined as $c) {
    					if ($c['tradeId'] == $t['tradeId']) {

    						$tmpBuyTrades[]=array(
    								'tradeId'=>$c['tradeId'],
    								'type'=>0,
    								'settleDate'=>$c['settleDate1'],
    								'tradeDate'=>$c['tradeDate1'],
    								'reference'=>$c['reference1'],
    								'description'=>'',
    								'unitPrice'=>$c['unitPrice1'],
    								'quantity'=>$c['quantity1'],
    								'cost'=>$c['cost1']
    						);
    					}
    				}

    				$this->alignSellTrades($t, $tmpBuyTrades, $tmpCombined);
    			}
    			$combined=$tmpCombined;
    	
    		}
    	
    		if (count($combined) && $searchSold) {
    			foreach ($combined as $k=>$v) {
    				switch ($searchSold) {
    					case 1 : {
    						// Unsold
    						if ($v['reference2'] != '') {
    							unset($combined[$k]);
    						}
    						break;
    					}
    					case 2 : {
    						// Sold
    						if ($v['reference2'] == '') {
    							unset($combined[$k]);
    						}
    						break;
    					}
    				}
    			}
    		}
    	}

    	if (count($combined)) {
    		$repo=$this->doctrine
    			->getRepository('InvestShareBundle:Dividend');
    		foreach ($combined as $k=>$c) {
    			$combined[$k]['Dividend']=0;
    			$combined[$k]['DividendPaid']=0;

    			$dividends=$repo->findBy(
    				array(
    					'companyId'=>$c['companyId']
    				)
    			);
    			
    			if ($dividends && count($dividends)) {
    				foreach ($dividends as $div) {
    					if ($div->getExDivDate()->format('Y-m-d') <= date('Y-m-d') && $div->getExDivDate()->format('Y-m-d H:i:s') > $c['tradeDate1'] && ($div->getExDivDate()->format('Y-m-d H:i:s') <= $c['tradeDate2'] || $c['reference2'] == '')) {
    						$combined[$k]['Dividend']+=($c['quantity1']*$div->getAmount()/100);
    						$combined[$k]['DividendRate']=$div->getPaymentRate();
    						if ($div->getPaymentDate()->format('Y-m-d H:i:s') < date('Y-m-d H:i:s')) {
    							$combined[$k]['DividendPaid']+=$c['quantity1']*$div->getAmount()/100;
    						}
    					}
    				}
    			}
    		}
    	}
    	
    	return $combined;
    }

    
    public function getDividendsForCompany($code, $predict = null, $special = null) {
// $time=time();
    	$dividends=array();
    	$d1=0;
    	$d2=0;
    	
    	$company=$this->doctrine
			->getRepository('InvestShareBundle:Company')
			->findOneBy(
				array(
					'code'=>$code
				)
	    	);

    	if ($company) {
    		$cId=$company->getId();

    		$d1=$company->getFrequency();
	    	$d2=0;
	    	 
	    	$connection=$this->em->getConnection();
	    	
	    	$query1='SELECT `d`.*, `c`.`Currency`'.
	    		' FROM `Dividend` `d`'.
	    			' JOIN `Company` `c` ON `d`.`CompanyId`=`c`.`id`'.
	    		' WHERE `d`.`CompanyId`=:cId'.
	    		' ORDER BY `d`.`ExDivDate`';
	
	    	$stmt=$connection->prepare($query1);
	    	$stmt->bindValue('cId', $cId);
	    	$stmt->execute();
	    	$dividends=$stmt->fetchAll();
    	}
    	
    	$q=array();
    	if ($predict) {
// print '<hr>predict<br>'.print_r($dividends, true).'<br>';

    		if ($dividends) {
	    		$d2=count($dividends);
	    		if ($d2) {
	    			$d=$dividends[0];
	    			foreach ($dividends as $div) {
	    				if (!$div['Special']) {
	    					$d=$div;
// Save quarterly/half year data
							switch ($d1) {
								case 4 : {
	    							$q[$this->quarterYear($div['ExDivDate'])]=$div;
	    							break;
								}
								case 2 : {
									$q[$this->halfYear($div['ExDivDate'])]=$div;
									break;
								}
								case 1 : {
									$q[1]=$div;
									break;
								}
							}
	    				}
	    				if ($div['Special'] || $div['ExDivDate'] < ((date('m-d')>$this->startTaxYear)?(date('Y')):(date('Y')-1)).'-'.$this->startTaxYear.' 00:00:00') {
	    					$d2--;
	    				}
	    			}
	    		}
	    	}

	    	if ($d1>0 && ($d2>0 || (isset($dividends) && count($dividends)))) {
	    		$diff=$d1-$d2;

	    		$d['Predicted']=1;
	    		$d['PaymentRate']=null;
	    		$date1=strtotime($d['ExDivDate']);
	    		$date2=strtotime($d['PaymentDate']);
	    		
	    		$endOfPrediction=mktime(0, 0, 0, $this->startTaxYearMonth, 1+$this->startTaxYearDay, (date('m-d')>=$this->startTaxYear)?((date('Y')+2)):(date('Y')+1));
	    		
	    		switch ($d1) {
	    			case 1 : {

	    				for ($i=0; $i<(2*$d1-$diff); $i++) {

	    					$ExDivDate=date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $date1)+12*($i+1), date('d', $date1), date('Y', $date1)));
	    					$PaymentDate=date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $date2)+12*($i+1), date('d', $date2), date('Y', $date2)));
	    					if ($ExDivDate < date('Y-m-d H:i:s', $endOfPrediction)) {
	    						$d['DeclDate']=null;
	    						$d['ExDivDate']=$ExDivDate;
	    						$d['PaymentDate']=$PaymentDate;
	    						$dividends[]=$d;
	    					}
	    				}
	    				break;
	    			}
	    			case 2 : {

	    				for ($i=0; $i<(4*$d1-$diff); $i++) {
   							$ExDivDate=date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $date1)+6*($i+1), date('d', $date1), date('Y', $date1)));
	    					$PaymentDate=date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $date2)+6*($i+1), date('d', $date2), date('Y', $date2)));
	    					if ($ExDivDate < date('Y-m-d H:i:s', $endOfPrediction)) {
	    						$d['DeclDate']=null;
	    						$d['ExDivDate']=$ExDivDate;
	    						$d['PaymentDate']=$PaymentDate;
	    						
	    						if (isset($q[$this->halfYear(date('Y-m-d', strtotime($ExDivDate)))])) {
	    							$d['Amount']=$q[$this->halfYear(date('Y-m-d', strtotime($ExDivDate)))]['Amount'];
	    						}
	    						$dividends[]=$d;
	    					}
	    				}
	    				break;
	    			}
	    			case 4 : {

	    				for ($i=0; $i<(8*$d1-$diff); $i++) {
	    					$ExDivDate=date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $date1)+3*($i+1), date('d', $date1), date('Y', $date1)));
	    					$PaymentDate=date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $date2)+3*($i+1), date('d', $date2), date('Y', $date2)));
	    					if ($ExDivDate < date('Y-m-d H:i:s', $endOfPrediction)) {
	    						$d['DeclDate']=null;
	    						$d['ExDivDate']=$ExDivDate;
	    						$d['PaymentDate']=$PaymentDate;
	    						if (isset($q[$this->quarterYear(date('Y-m-d', strtotime($ExDivDate)))])) {
	    							$d['Amount']=$q[$this->quarterYear(date('Y-m-d', strtotime($ExDivDate)))]['Amount'];
	    						}
	    						$dividends[]=$d;
	    					}
	    				}
	    				break;
	    			}
	    		}
	    	}
    	}
    	if (count($dividends)) {
    		foreach ($dividends as $k=>$v) {
    			$dividends[$k]['TaxYear']=$this->getTaxYear($v['PaymentDate']);
    		}
    	}

// error_log('ajax, getDividendsForCompany ('.$code.') : '.$time.' - '.time());
		return $dividends;
    }

    
    public function getDirectorsDealsForCompany($code) {
// $time=time();
    	$connection=$this->em->getConnection();
    	 
    	$query='SELECT * FROM `DirectorsDeals` WHERE `Code`=:code ORDER BY `DealDate`';
    	$stmt=$connection->prepare($query);
    	$stmt->bindValue('code', $code);
    	$stmt->execute();
    	$deals=$stmt->fetchAll();
// error_log('ajax, getDirectorsDealsForCompany ('.$code.') : '.$time.' - '.time());
    	return $deals;
    	
    }
    
    
    public function getFinancialDiaryForCompany($code) {
// $time=time();
    	$connection=$this->em->getConnection();
    	
    	$query='SELECT * FROM `Diary` WHERE `Code`=:code ORDER BY `Date`';
    	$stmt=$connection->prepare($query);
    	$stmt->bindValue('code', $code);
    	$stmt->execute();
    	$diary=$stmt->fetchAll();
// error_log('ajax, getFinancialDiaryForCompany ('.$code.') : '.$time.' - '.time());
    	return $diary;

    }

    public function getCurrencyRates() {
    	
    	$connection=$this->em->getConnection();    	
    	
    	$currencyRates=array();
    	$query='SELECT `c`.`Currency`, (SELECT `Rate` FROM `Currency` WHERE `Currency`=`c`.`Currency` ORDER BY `Updated` DESC LIMIT 1) `Rate` FROM `Currency` `c` GROUP BY `c`.`Currency`';
    	$stmt=$connection->prepare($query);
    	$stmt->execute();
    	$results=$stmt->fetchAll();
    	if ($results && count($results)) {
    		foreach ($results as $result) {
    			$currencyRates[$result['Currency']]=$result['Rate'];
    		}
    	}

    	return $currencyRates;
    }
    
    
    public function quarterYear($dateStr) {
    	$ret=1;
    	$m=date('m', strtotime($dateStr));
    	switch ($m) {
    		case 1 :
    		case 2 :
    		case 3 : {
    			$ret=1;
    			break;
    		}
    		case 4 :
    		case 5 :
    		case 6 : {
    			$ret=2;
    			break;
    		}
    	    case 7 :
    		case 8 :
    		case 9 : {
    			$ret=3;
    			break;
    		}
    	    case 10 :
    		case 11 :
    		case 12 : {
    			$ret=4;
    			break;
    		}
    	} 
    	 
    	return $ret;
    }
    
    
    public function halfYear($dateStr) {
    	$ret=1;
    	$m=date('m', strtotime($dateStr));
    	switch ($m) {
    		case 1 :
    		case 2 :
    		case 3 :
    		case 4 :
    		case 5 :
    		case 6 : {
    			$ret=0;
    			break;
    		}
    		case 7 :
    		case 8 :
    		case 9 :
    		case 10 :
    		case 11 :
    		case 12 : {
    			$ret=1;
    			break;
    		}
    	}
    
    	return $ret;
    }
    
    
    public function isCurrentTaxYear($date) {
    	
    	$taxYearFrom = date('Y-m-d', mktime(0, 0, 0, $this->startTaxYearMonth, $this->startTaxYearDay, date('Y')));
    	$taxYearTo = date('Y-m-d', mktime(0, 0, 0, $this->startTaxYearMonth, $this->startTaxYearDay-1, date('Y')+1));
// print '<br>'.date('Y-m-d', strtotime($date)).' between '.$taxYearFrom.' and '.$taxYearTo;    	    	
    	if (date('Y-m-d', strtotime($date)) >= $taxYearFrom && date('Y-m-d', strtotime($date)) <= $taxYearTo) {
    		return true;
    	}
    	return false;
    	
    }
    
    
    public function getTaxYear($dateStr) {
    	
    	$date=strtotime($dateStr);
    	if (date('m-d', $date) > '04-05') {
    		$current=date('y', $date);
    	} else {
    		$current=date('y', $date)-1;
    	}
    	
    	return sprintf('%02d%02d', $current, $current+1);
    	 
    }
    
    
    public function addDirectorsDeals($data) {
    	$ret=false;

    	$dd=$this->doctrine
    		->getRepository('InvestShareBundle:DirectorsDeals')
    		->findOneBy(
    			array(
    				'declDate'	=>$data['DeclDate'],
    				'dealDate'	=>$data['DealDate'],
    				'type'		=>$data['Type'],
    				'code'		=>$data['Code'],
    				'shares'	=>$data['Shares']
    			)
    		);

    	if (!$dd) {
    		$dd=new DirectorsDeals();
    		
    		$dd->setCreatedOn(new \DateTime('now'));
    		$dd->setCode($data['Code']);
    		$dd->setName($data['Name']);
    		$dd->setDeclDate($data['DeclDate']);
    		$dd->setDealDate($data['DealDate']);
    		$dd->setType($data['Type']);
    		$dd->setPosition($data['Position']);
    		$dd->setShares($data['Shares']);
    		$dd->setPrice($data['Price']);
    		$dd->setValue($data['Value']);
    		
    		$this->em->persist($dd);
    		$this->em->flush();
    		
    		if ($dd->getId()) {
    			$ret=true;
    		}
    	}
    	
    	return $ret;
    }

    
    public function addFinancialDiary($data) {
    	$ret=false;
    
    	if ($this->isFTSE($data['Code'])) {
	    	$fd=$this->doctrine
	    		->getRepository('InvestShareBundle:Diary')
	    		->findOneBy(
	    			array(
	    				'date'	=>$data['Date'],
	    				'name'	=>$data['Name'],
	    				'type'	=>$data['Type'],
	    				'code'	=>$data['Code']
	    			)
	    		);
	    
	    	if (!$fd) {
	    		$fd=new Diary();
	    
	    		$fd->setCreatedOn(new \DateTime('now'));
	    		$fd->setCode($data['Code']);
	    		$fd->setName($data['Name']);
	    		$fd->setDate($data['Date']);
	    		$fd->setType($data['Type']);
	    
	    		$this->em->persist($fd);
	    		$this->em->flush();
	    
	    		if ($fd->getId()) {
	    			$ret=true;
	    		}
	    	}
    	}
    	 
    	return $ret;
    }
    
    
    public function isFTSE($code) {
    	
    	$connection=$this->em->getConnection();
    	
    	$query='SELECT `id` FROM `Company` WHERE `Code`="'.$code.'"';
    	$stmt=$connection->prepare($query);
    	$stmt->execute();
    	$results=$stmt->fetchAll();
    	
    	if (count($results)) {
			return true;
    	}
    	return false;
    }
    

    public function getCompanyNames($current) {
    	
    	$companies=array();
    	
    	if ($current) {
// error_log('current');
    		$trades=$this->getTradesData(null, null, null, null);
    	 
	    	if (count($trades)) {
	    		foreach ($trades as $t) {
	    			if ($t['reference2'] == '') {
	    				$companies[$t['companyCode']]=$t['companyName'];
    				}
    			}
	    	}
    	} else {
// error_log('all');

    		$connection=$this->em->getConnection();
    		
    		$query='SELECT `Code`, `Name` FROM `Company` ORDER BY `Code`';
    		$stmt=$connection->prepare($query);
    		$stmt->execute();
    		$results=$stmt->fetchAll();
    		
    		if (count($results)) {
    			foreach ($results as $result) {
    				$companies[$result['Code']]=$result['Name'];
    			}
    		}
    	}

// error_log('no of companies:'.count($companies));
    	return $companies;
    }
    

    public function getCurrencyList() {
    	
    	$ret=array();
    	$query='SELECT `Currency`'.
    			' FROM `Currency`'.
    			' GROUP BY `Currency`';
    	
    	$connection=$this->em->getConnection();
    	
    	$stmt=$connection->prepare($query);
    	$stmt->execute();
    	$results=$stmt->fetchAll();
    	
    	if ($results) {
    		foreach ($results as $result) {
    			$ret[]=$result['Currency'];
    		}
    	}
    	
    	return $ret;
    }
    
}
