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
    	
    	$this->currencyNeeded=$this->getCurrencyList();
    	
    	if ($currency && in_array($currency, $this->currencyNeeded)) {
    		$search=array('currency'=>$currency);
    	}

    	$results=$this->getDoctrine()
    		->getRepository('InvestShareBundle:Currency')
    		->findBy($search, array('updated'=>'ASC'));
    	
    	$data[0]['name']=$currency;
    	$data[0]['tooltip']['valueDecimals']=3;
    	if ($results && count($results)) {
    		foreach ($results as $result) {
    			if (in_array($result->getCurrency(), $this->currencyNeeded)) {
    				$updated=$result->getUpdated()->getTimestamp();
    				$data[0]['data'][]=array($updated*1000, $result->getRate());
    			}
    		}
    	}
    	
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
