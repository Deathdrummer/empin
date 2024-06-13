<?php namespace App\Services\Business;

use App\Helpers\DdrDateTime;
use Illuminate\Support\Str;

class VirtualVars {
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public static function run($virtVar, $buildContractData) {
		$varToCamel = Str::camel($virtVar);
		
		$instance = new self();
		
		if (!method_exists($instance, $varToCamel) || !is_callable([$instance, $varToCamel])) return null;
        
        return $instance->{$varToCamel}($buildContractData);
	}
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function countKs2Diapason($buildContractData) {
		return $buildContractData['count_ks_2'] > 1 ? '1-'.$buildContractData['count_ks_2'] : $buildContractData['count_ks_2'];
	}
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function priceAvvrWords($buildContractData) {
		return $this->_buildPriceToWords($buildContractData['price_avvr']);
	}
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function priceAvvrNdsWords($buildContractData) {
		return $this->_buildPriceToWords($buildContractData['price_avvr_nds']);
	}
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function avvrNdsOnlyWords($buildContractData) {
		return $this->_buildPriceToWords($buildContractData['avvr_nds_only']);
	}
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function pricePirWords($buildContractData) {
		return $this->_buildPriceToWords($buildContractData['price_pir']);
	}
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function pricePirNdsWords($buildContractData) {
		return $this->_buildPriceToWords($buildContractData['price_pir_nds']);
	}
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function pirNdsOnlyWords($buildContractData) {
		return $this->_buildPriceToWords($buildContractData['pir_nds_only']);
	}
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function nds() {
		return setting('price-nds');
	}
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function invoiceNumber($buildContractData) {
		return ((int)substr($buildContractData['id'], -2) + 1) ?: 99;
	}
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateStartFyear($buildContractData) {
		return DdrDateTime::convertDateFormat($buildContractData['date_start'] ?? null, 'd.m.Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateEndFyear($buildContractData) {
		return DdrDateTime::convertDateFormat($buildContractData['date_end'] ?? null, 'd.m.Y');
	}	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateGenStartFyear($buildContractData) {
		return DdrDateTime::convertDateFormat($buildContractData['date_gen_start'] ?? null, 'd.m.Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateGenEndFyear($buildContractData) {
		return DdrDateTime::convertDateFormat($buildContractData['date_gen_end'] ?? null, 'd.m.Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateSubStartFyear($buildContractData) {
		return DdrDateTime::convertDateFormat($buildContractData['date_sub_start'] ?? null, 'd.m.Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateSubEndFyear($buildContractData) {
		return DdrDateTime::convertDateFormat($buildContractData['date_sub_end'] ?? null, 'd.m.Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateReportFromFyear($buildContractData) {
		return DdrDateTime::convertDateFormat($buildContractData['date_report_from'] ?? null, 'd.m.Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateBuyFyear($buildContractData) {
		return DdrDateTime::convertDateFormat($buildContractData['date_buy'] ?? null, 'd.m.Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateCloseFyear($buildContractData) {
		return DdrDateTime::convertDateFormat($buildContractData['date_close'] ?? null, 'd.m.Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateSendActionFyear($buildContractData) {
		return DdrDateTime::convertDateFormat($buildContractData['date_send_action'] ?? null, 'd.m.Y');
	}
	
	
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateStartHuman($buildContractData) {
		return DdrDateTime::dateToHuman($buildContractData['date_start'] ?? null, '«j» F Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateEndHuman($buildContractData) {
		return DdrDateTime::dateToHuman($buildContractData['date_end'] ?? null, '«j» F Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateGenStartHuman($buildContractData) {
		return DdrDateTime::dateToHuman($buildContractData['date_gen_start'] ?? null, '«j» F Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateGenEndHuman($buildContractData) {
		return DdrDateTime::dateToHuman($buildContractData['date_gen_end'] ?? null, '«j» F Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateSubStartHuman($buildContractData) {
		return DdrDateTime::dateToHuman($buildContractData['date_sub_start'] ?? null, '«j» F Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateSubEndHuman($buildContractData) {
		return DdrDateTime::dateToHuman($buildContractData['date_sub_end'] ?? null, '«j» F Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateReportFromHuman($buildContractData) {
		return DdrDateTime::dateToHuman($buildContractData['date_report_from'] ?? null, '«j» F Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateBuyHuman($buildContractData) {
		return DdrDateTime::dateToHuman($buildContractData['date_buy'] ?? null, '«j» F Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateCloseHuman($buildContractData) {
		return DdrDateTime::dateToHuman($buildContractData['date_close'] ?? null, '«j» F Y');
	}
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function dateSendActionHuman($buildContractData) {
		return DdrDateTime::dateToHuman($buildContractData['date_send_action'] ?? null);
	}
	
	

	
	
	
	
	
	
	
	
	
	
	
	
	
	//------------------------------------------------------------------------------------------------------------------------------------
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	private function _buildPriceToWords($price = null) {
		if (!$price) return false;
		$price = str_replace(' ', '', $price);
		[$rub, $kop] = explode('.', $price);
		
		$kopsWords = getDeclension(($kop ?: '0'), ['копейка', 'копейки', 'копеек']);
		
		$rubsWords = numberToWords($rub, ['рубль', 'рубля', 'рублей']);
		
		return $rubsWords.' '.$kop.' '.$kopsWords;
	}
	
	
	// price_avvr_words		
	// price_avvr_nds_words	
	// avvr_nds_only_words	
	
	
}