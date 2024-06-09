<?php namespace App\Services\Business;

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
		$id = $buildContractData['id'];
		return substr($id, -2);
	}
	
	
	
	
	
	
	
	
	
	
	
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