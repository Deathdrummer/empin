<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

final class VirtualVars extends Enum {
	//#[Description('{"ru":"№ заказа","en":"№ заказа"}')]
	//const order		= 1;
	
	const count_ks2_diapason 	= 'Количество актов КС-2 в диапазоне';
	
	const price_avvr_words		= 'Сумма АВВР прописью без НДС';
	const price_avvr_nds_words	= 'Сумма АВВР прописью с НДС';
	const avvr_nds_only_words	= 'Сумма АВВР прописью НДС';
	
	const price_pir_words		= 'Сумма ПИР прописью без НДС';
	const price_pir_nds_words	= 'Сумма ПИР прописью с НДС';
	const pir_nds_only_words	= 'Сумма ПИР прописью НДС';
	
	
	const nds					= 'Процент НДС';
	const invoice_number		= 'Номер накладной';
	
	
		
}



