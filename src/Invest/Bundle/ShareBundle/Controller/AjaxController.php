<?php

/*
 * Author: Imre Incze
 * Todo:
 * - Trade : Fix yield calculation if the currency is not GBP 
 * - Dividend : Fix yield calculation if the currency is not GBP
 * 
 *  13/11/2014 - can we have the option to include predicted dates?
 */

namespace Invest\Bundle\ShareBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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

class AjaxController extends Controller
{
	protected $currencyNeeded=array();
	
	protected $defaultCurrencies=array('EUR', 'USD', 'AUD', 'HUF', 'PHP');

	

    public function currencyAction($currency) {

    	$data=array();
    	$search=array();
    	
    	$currencies=explode(',', $currency);

    	if (count($currencies)) {
    		
    		$this->currencyNeeded=$this->getCurrencyList();
    		
    		foreach ($currencies as $k=>$v) {
    			if (!in_array($v, $this->currencyNeeded)) {
    				unset($currencies[$k]);
    			}
    		}
    		$search=array('currency'=>$currencies);
    	}

    	$i=0;
    	foreach ($currencies as $curr) {
	    	$results=$this->getDoctrine()
	    		->getRepository('InvestShareBundle:Currency')
	    		->findBy($search, array('updated'=>'ASC'));
	    	
	    	$data[$i]['name']=$curr;
	    	$data[$i]['tooltip']['valueDecimals']=3;
	    	if ($results && count($results)) {
	    		foreach ($results as $result) {
	    			if ($result->getCurrency() == $curr) {
	    				$data[$i]['data'][]=array($result->getUpdated()->getTimestamp()*1000, $result->getRate());
	    			}
	    		}
	    	}
	    	$i++;
    	}
    	
    	return new JsonResponse($data);
    }
    
    
    public function pricesAction($company, $min, $max) {
$time=time();    
    	$data=array();
    	$min_date=null;
    	$max_date=null;
    	$rangeDate=array('min'=>0, 'max'=>date('Y-m-d H:i:s'));
		if ($min) {
			$rangeDate['min']=date('Y-m-d H:i:s', $min/1000);
		}
		if ($max) {
			$rangeDate['max']=date('Y-m-d H:i:s', $max/1000);
		}
/*
		$range=4;
    	
    	switch ($range) {
    		case 1 : {
    			$rangeDate['min']=date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s')+1, date('n')-3, date('j'), date('Y')));
    			break;
    		}
    	    case 2 : {
    			$rangeDate['min']=date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s')+1, date('n')-6, date('j'), date('Y')));
    			break;
    		}
    	    case 3 : {
    			$rangeDate['min']=date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s')+1, date('n'), date('j'), date('Y')-1));
    			break;
    		}
    	    case 4 : {
    			$rangeDate['min']=date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, 2000));
    			break;
    		}
    		default : {
    			$rangeDate['min']=date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s')+1, date('n')-1, date('j'), date('Y')));
    			break;
    		}
    	}
*/
error_log('min date:'.$rangeDate['min'].', max date:'.$rangeDate['max']);

    	$functions=$this->get('invest.share.functions');
    	
        if ($company) {
    		$selectedCompanies=explode(',', $company);
// error_log('selected companies:'.print_r($selectedCompanies, true));    		
    		
    		$prices=$this->getDoctrine()
	    		->getRepository('InvestShareBundle:StockPrices')
	    		->findBy(
	   				array(
						'code'=>$selectedCompanies
		    		),
	    			array(
	    				'date'=>'ASC'
	    			)
	   			);

   	    	if (count($prices)) {
   	    		
/*
 * create timescale list
 */

   	    		foreach ($selectedCompanies as $k=>$v) {
   	    			$data[$k]=array(
   	    				'name'=>$v,
   	    				'id'=>'data_'.$k,
   	    				'type'=>'line',
   	    				'gapsize'=>5,
   	    				'treshold'=>'null',
			    		'tooltip'=>array('valueDecimals'=>2)
   	    			);
   	    		}
			    
   	    		foreach ($prices as $pr1) {

   	    			$prDate=$pr1->getDate()->format('Y-m-d H:i:s');
   	    			if ($prDate >= $rangeDate['min'] && $prDate <= $rangeDate['max']) {
   	    				
	   	    			$i=array_search($pr1->getCode(), $selectedCompanies);
		    			$data[$i]['data'][]=array($pr1->getDate()->getTimestamp()*1000, $pr1->getPrice());
	
		    			if ($min_date == null || $min_date > $pr1->getDate()->format('Y-m-d H:i:s')) {
		    				$min_date=$pr1->getDate()->format('Y-m-d H:i:s');
		    			}
		    			if ($max_date == null || $max_date < $pr1->getDate()->format('Y-m-d H:i:s')) {
		    				$max_date=$pr1->getDate()->format('Y-m-d H:i:s');
		    			}
		    			
   	    			}
	    		}
// error_log('min_date:'.$min_date);
// error_log('max_date:'.$max_date);

/*
 * Create dividends points into the graph
*/
	    		$i=count($selectedCompanies);

	    		foreach ($selectedCompanies as $k=>$v) {
		    		$divs=$functions->getDividendsForCompany($v, true);
	
		    		if (count($divs)) {
// error_log('divs:'.count($divs));
						$i_decl=array();
						$i_exdiv=array();
						$i_pay=array();	
		    			foreach ($divs as $div) {

		    				$amount=(($div['Special'])?('Special '):('')).(($div['Currency']=='USD')?('$ '):('')).(($div['Currency']=='EUR')?('€ '):('')).$div['Amount'].(($div['Currency']=='GBP')?('p'):(''));
	    		
		    				if (date('Y-m-d H:i:s', strtotime($div['DeclDate'])) < $max_date && strtotime($div['DeclDate']) > 0 && date('Y-m-d H:i:s', strtotime($div['DeclDate'])) > $min_date) {

		    					if (!isset($i_decl[$k])) {
		    						$i_decl[$k]=$i++;
		    					}

		    					if (!isset($data[$i_decl[$k]]['data'])) {
		    						$data[$i_decl[$k]]=array(
		    							'type'=>'flags',
		    							'shape'=>'squarepin',
		    							'onSeries'=>'data_'.$k,
		    							'name'=>'Decl.Date ('.$v.')',
		    							'data'=>array()
		    						);
		    					}
		    					$data[$i_decl[$k]]['data'][]=array(
		    						'x'=>strtotime($div['DeclDate'])*1000,
		    						'title'=>$amount,
		    						'text'=>'ExDividend Declaration Date (<b>'.$v.'</b>)',
		    					);
		    				}
		    		
		    				if (date('Y-m-d H:i:s', strtotime($div['ExDivDate'])) < $max_date && strtotime($div['ExDivDate']) > 0 && date('Y-m-d H:i:s', strtotime($div['ExDivDate'])) > $min_date) {

		    					if (!isset($i_exdiv[$k])) {
		    						$i_exdiv[$k]=$i++;
		    					}

		    					if (!isset($data[$i_exdiv[$k]]['data'])) {
		    						$data[$i_exdiv[$k]]=array(
		    							'type'=>'flags',
		    							'shape'=>'squarepin',
		    							'onSeries'=>'data_'.$k,
		    							'name'=>'ExDiv.Date ('.$v.')',
		    							'data'=>array()
		    						);
		    					}
		    					$data[$i_exdiv[$k]]['data'][]=array(
		    						'x'=>strtotime($div['ExDivDate'])*1000,
		    						'title'=>$amount,
		    						'text'=>'ExDividend Date (<b>'.$v.'</b>)',
		    					);
		    				}
		    		
		    				if (date('Y-m-d H:i:s', strtotime($div['PaymentDate'])) < $max_date && strtotime($div['PaymentDate']) > 0 && date('Y-m-d H:i:s', strtotime($div['PaymentDate'])) > $min_date) {

		    					if (!isset($i_pay[$k])) {
		    						$i_pay[$k]=$i++;
		    					}

		    					if (!isset($data[$i_pay[$k]]['data'])) {
		    						$data[$i_pay[$k]]=array(
		    							'type'=>'flags',
		    							'shape'=>'squarepin',
		    							'onSeries'=>'data_'.$k,
		    							'name'=>'Payment.Date ('.$v.')',
		    							'data'=>array()
		    						);
		    					}
		    					$data[$i_pay[$k]]['data'][]=array(
		    						'x'=>strtotime($div['PaymentDate'])*1000,
		    						'title'=>$amount,
		    						'text'=>'Payment Date (<b>'.$v.'</b>)'
		    					);
		    				}
		    			}
		    		}
	    			
		    		$ddeals=$functions->getDirectorsDealsForCompany($v);
	
		    		if (count($ddeals)) {
// error_log('ddeals:'.count($ddeals));	
		    			foreach ($ddeals as $d) {
		    				
		    				if (date('Y-m-d H:i:s', strtotime($d['DealDate'])) < $max_date && date('Y-m-d H:i:s', strtotime($d['DealDate'])) > $min_date) {
		    					if (!isset($data[$i]['data'])) {
		    						$data[$i]=array(
		    							'type'=>'flags',
		    							'shape'=>'squarepin',
		    							'onSeries'=>'data_'.$k,
	    								'name'=>'Directors Deal ('.$v.')',
	    								'data'=>array()
		    						);
		    					}
		    					$data[$i]['data'][]=array(
		    						'x'=>strtotime($d['DealDate'])*1000,
		    						'title'=>'DD',
		    						'text'=>'<b>Directors Deal ('.$v.')</b><br>Name: '.$d['Name'].'<br>Position:'.$d['Position'].'<br>Type:'.$d['Type'].'<br>Price:'.$d['Price'].'<br>Value:'.$d['Value']
		    					);
	    					}
	    				}
	    				$i++;
	    			}
		    		 
	   	    		$diary=$functions->getFinancialDiaryForCompany($v, true);
		
		    		if (count($diary)) {
// error_log('diary:'.count($diary));
			    		foreach ($diary as $d) {
			    				
			    			if (date('Y-m-d H:i:s', strtotime($d['Date'])) < $max_date && date('Y-m-d H:i:s', strtotime($d['Date'])) > $min_date) {
			    				if (!isset($data[$i]['data'])) {
			    					$data[$i]=array(
			    						'type'=>'flags',
			    						'shape'=>'circlepin',
		    							'name'=>'Financial Diary ('.$v.')',
		    							'data'=>array()
			    					);
			    				}
			    				$data[$i]['data'][]=array(
			    					'x'=>strtotime($d['Date'])*1000,
			    					'title'=>'FD',
			    					'text'=>'<b>Financial Diary ('.$v.')</b><br>'.$d['Type']
			    				);
			    			}
			    		}
			    		$i++;
		    		}
		   	    }
   	    	}
	    }
// error_log('i:'.$i);
// error_log('ajax Prices ('.$company.'): '.(time() - $time).'s, range:['.$rangeDate['min'].'-'.$rangeDate['max'].']');
    	return new JsonResponse($data);
    }
    

    private function getCurrencyList() {
    	
    	$ret=array();
    	$query='SELECT `Currency`'.
    		' FROM `Currency`'.
    		' GROUP BY `Currency`';
    	
    	$em=$this->getDoctrine()->getManager();
    	$connection=$em->getConnection();
    	
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
