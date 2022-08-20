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
				fieldset="Цвет:w4rem|color|color,Название:w20rem|input|name,Меньше (условие):w20rem|select|less_condition,Меньше (кол-во):w10rem|number|less_count,Меньше (единица даты):w15rem|select|less_datetype,Больше (условие):w20rem|select|more_condition,Больше (кол-во):w10rem|number|more_count,Больше (единица даты):w15rem|select|more_datetype,Группа:w15rem|select|group"
				options="less_condition;<:меньше,=:равно,<=:меньше или равно|more_condition;>:больше,=:равно,>=:больше или равно|less_datetype;h:часов,d:дней,w:недель,m:месяцев,y:лет|more_datetype;h:часов,d:дней,w:недель,m:месяцев,y:лет|group;contracts:Контракты,steps:Этапы"
				group="small"
			 />
		</x-card>
	</x-settings>
</section>

