<section>
	<x-settings>
		<x-card
			loading
			ready
			title="Дедлайны"
			desc="Условия для формирования дедлайнов"
			>
			
			<div class="ddrtabs">
				<div class="ddrtabs__nav">
					<ul class="ddrtabsnav" ddrtabsnav>
						<li class="ddrtabsnav__item ddrtabsnav__item_active" ddrtabsitem="tabContracts">Договоры</li>
						<li class="ddrtabsnav__item" ddrtabsitem="testSteps">Этапы</li>
					</ul>
				</div>
				
				<div class="ddrtabs__content ddrtabscontent" ddrtabscontent>
					<div class="ddrtabscontent__item ddrtabscontent__item_visible" ddrtabscontentitem="tabContracts">
						<x-simplelist
							setting="contracts-deadlines"
							fieldset="Цвет:w6rem|color|color,Название:w20rem|input|name,Мин (условие):w20rem|select|min_sign,Мин (кол-во):w10rem|number|min_count,Мин (единица даты):w15rem|select|min_datetype,Макс (условие):w20rem|select|max_sign,Макс (кол-во):w10rem|number|max_count,Макс (единица даты):w15rem|select|max_datetype"
							options="min_sign;>:больше,=:равно,>=:больше или равно|max_sign;<:меньше,<=:меньше или равно|max_datetype;h:часов,d:дней,w:недель,m:месяцев,y:лет|min_datetype;h:часов,d:дней,w:недель,m:месяцев,y:лет"
							group="small"
						 />
					</div>
					<div class="ddrtabscontent__item" ddrtabscontentitem="testSteps">
						<x-simplelist
							setting="steps-deadlines"
							fieldset="Цвет:w6rem|color|color,Название:w20rem|input|name,Мин (условие):w20rem|select|min_sign,Мин (кол-во):w10rem|number|min_count,Мин (единица даты):w15rem|select|min_datetype,Макс (условие):w20rem|select|max_sign,Макс (кол-во):w10rem|number|max_count,Макс (единица даты):w15rem|select|max_datetype"
							options="min_sign;>:больше,=:равно,>=:больше или равно|max_sign;<:меньше,<=:меньше или равно|max_datetype;h:часов,d:дней,w:недель,m:месяцев,y:лет|min_datetype;h:часов,d:дней,w:недель,m:месяцев,y:лет"
							group="small"
						 />
					</div>
				</div>
			</div>
			
			
			
			
			
			
			
			
						
		</x-card>
	</x-settings>
</section>

