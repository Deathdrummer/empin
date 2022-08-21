<section>
	<x-settings>
		<x-card
			loading
			ready
			title="Дедлайны"
			desc="Условия для формирования дедлайнов"
			>
			<x-simplelist
				setting="contracts-deadlines"
				fieldset="Цвет:w6rem|color|color,Название:w20rem|input|name,Мин (условие):w20rem|select|min_sign,Мин (кол-во):w10rem|number|min_count,Мин (единица даты):w15rem|select|min_datetype,Макс (условие):w20rem|select|max_sign,Макс (кол-во):w10rem|number|max_count,Макс (единица даты):w15rem|select|max_datetype,Группа:w15rem|select|group"
				options="min_sign;>:больше,=:равно,>=:больше или равно|max_sign;<:меньше,<=:меньше или равно|max_datetype;h:часов,d:дней,w:недель,m:месяцев,y:лет|min_datetype;h:часов,d:дней,w:недель,m:месяцев,y:лет|group;contracts:Контракты,steps:Этапы"
				group="small"
			 />
		</x-card>
	</x-settings>
</section>

