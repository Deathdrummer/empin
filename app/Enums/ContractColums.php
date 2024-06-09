<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

final class ContractColums extends Enum {
	//#[Description('{"ru":"№ заказа","en":"№ заказа"}')]
	//const order		= 1;
	
	const object_number 	= 'Номер объекта';
	const title 			= 'Название';
	const titul 			= 'Титул';
	const customer 			= 'Заказчик';
	const contractor 		= 'Исполнтель';
	const type				= 'Тип договора';
	const contract 			= 'Номер договора';
	const applicant 		= 'Заявитель';
	const locality 			= 'Населенный пункт';
	const date_send_action 	= 'Дата подачи выполнения';
	const count_ks_2 		= 'Количество актов КС-2';
	const act_pir 			= 'Акт на ПИР';
	
	const date_start		= 'Дата подписания договора';
	const date_end 			= 'Дата окончания работ по договору';
	const date_gen_start	= 'Дата подписания генподрядного договора';
	const date_gen_end 		= 'Дата окончания работ по генподрядному договору';
	const date_sub_start	= 'Дата подписания субподрядного договора';
	const date_sub_end 		= 'Дата окончания работ по субподрядному договору';
	
	const price 			= 'Стоимость договора без НДС';
	const price_nds 		= 'Стоимость договора с НДС';
	const price_gen 		= 'Стоимость генподрядного договора без НДС';
	const price_gen_nds 	= 'Стоимость генподрядного договора с НДС';
	const price_sub 		= 'Стоимость субподрядного договора без НДС';
	const price_sub_nds 	= 'Стоимость субподрядного договора с НДС';
	
	const price_avvr 		= 'Сумма АВВР без НДС';
	const price_avvr_nds 	= 'Сумма АВВР с НДС';
	const avvr_nds_only 	= 'Сумма АВВР процента НДС';
	
	const price_pir 		= 'Сумма ПИР без НДС';
	const price_pir_nds 	= 'Сумма ПИР с НДС';
	const pir_nds_only 		= 'Сумма ПИР процента НДС';
	
	const price_smr 		= 'Сумма СМР без НДС';
	const price_pnr 		= 'Сумма ПНР без НДС';
	
	const date_report_from	= 'Отчетный период с';
	
	const buy_number 		= 'Номер закупки';
	const date_buy 	 		= 'Дата закупки';
	const hoz_method 		= 'Хоз способ';
	const subcontracting 	= 'Субподряд';
	const gencontracting 	= 'Генподряд';
	const gen_percent 		= 'Генподрядный процент';
	const date_close 	 	= 'Дата закрытия договора';
	const archive_dir 		= 'Архивная папка';
	const period 			= 'Срок исполнения договора';
	const archive 			= 'В архиве';
}



