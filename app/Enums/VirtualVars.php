<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

final class VirtualVars extends Enum {
	//#[Description('{"ru":"№ заказа","en":"№ заказа"}')]
	//const order		= 1;
	
	const count_ks2_diapason 		= 'Количество актов КС-2 в диапазоне';
	
	const price_avvr_words			= 'Сумма АВВР прописью без НДС';
	const price_avvr_nds_words		= 'Сумма АВВР прописью с НДС';
	const avvr_nds_only_words		= 'Сумма АВВР прописью НДС';
	
	const price_pir_words			= 'Сумма ПИР прописью без НДС';
	const price_pir_nds_words		= 'Сумма ПИР прописью с НДС';
	const pir_nds_only_words		= 'Сумма ПИР прописью НДС';
	
	
	const nds						= 'Процент НДС';
	const invoice_number			= 'Номер накладной';
	
	
	
	
	const date_start_fyear			= 'Дата подписания договора (чч.мм.гг)';
	const date_end_fyear 			= 'Дата окончания работ по договору (чч.мм.гг)';
	const date_gen_start_fyear		= 'Дата подписания генподрядного договора (чч.мм.гг)';
	const date_gen_end_fyear 		= 'Дата окончания работ по генподрядному договору (чч.мм.гг)';
	const date_sub_start_fyear		= 'Дата подписания субподрядного договора (чч.мм.гг)';
	const date_sub_end_fyear 		= 'Дата окончания работ по субподрядному договору (чч.мм.гг)';
	const date_report_from_fyear	= 'Отчетный период с (чч.мм.гг)';
	const date_buy_fyear 	 		= 'Дата закупки (чч.мм.гг)';
	const date_close_fyear 	 		= 'Дата закрытия договора (чч.мм.гг)';
	
	
	const date_start_human			= 'Дата подписания договора (чч месяц гггг)';
	const date_end_human 			= 'Дата окончания работ по договору (чч месяц гггг)';
	const date_gen_start_human		= 'Дата подписания генподрядного договора (чч месяц гггг)';
	const date_gen_end_human 		= 'Дата окончания работ по генподрядному договору (чч месяц гггг)';
	const date_sub_start_human		= 'Дата подписания субподрядного договора (чч месяц гггг)';
	const date_sub_end_human 		= 'Дата окончания работ по субподрядному договору (чч месяц гггг)';
	const date_report_from_human	= 'Отчетный период с (чч месяц гггг)';
	const date_buy_human 	 		= 'Дата закупки (чч месяц гггг)';
	const date_close_human 	 		= 'Дата закрытия договора (чч месяц гггг)';
		
}



