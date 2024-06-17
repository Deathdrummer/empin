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
						<li class="ddrtabsnav__item" ddrtabsitem="tabSteps">Этапы</li>
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
					<div class="ddrtabscontent__item" ddrtabscontentitem="tabSteps">
						
						<fieldset class="fieldset">
							<legend>Цвета дедлайнов этапов</legend>
							
							<x-input-group group="normal">
								<div class="row gx-30" gx="30">
									<div class="col-auto">
										<p class="mb6px">Перед дедлайном</p>
										<x-input type="color" variant="neutral" class="w15rem" setting="steps-deadlines.before" clearcolor />
									</div>
									<div class="col-auto">
										<p class="mb6px">День дедлайна</p>
										<x-input type="color" variant="neutral" class="w15rem" setting="steps-deadlines.current" clearcolor />
									</div>
									<div class="col-auto">
										<p class="mb6px">После дедлайна</p>
										<x-input type="color" variant="neutral" class="w15rem" setting="steps-deadlines.after" clearcolor />
									</div>
								</div>
							</x-input-group>
						</fieldset>
						
						{{-- 
						<x-simplelist
							setting="steps-deadlines"
							fieldset="Цвет:w6rem|color|color,Название:w20rem|input|name,Мин (условие):w20rem|select|min_sign,Мин (кол-во):w10rem|number|min_count,Мин (единица даты):w15rem|select|min_datetype,Макс (условие):w20rem|select|max_sign,Макс (кол-во):w10rem|number|max_count,Макс (единица даты):w15rem|select|max_datetype"
							options="min_sign;>:больше,=:равно,>=:больше или равно|max_sign;<:меньше,<=:меньше или равно|max_datetype;h:часов,d:дней,w:недель,m:месяцев,y:лет|min_datetype;h:часов,d:дней,w:недель,m:месяцев,y:лет"
							group="small"
						 /> --}}
					</div>
				</div>
			</div>
			
			
			
			
			
			
			
			
						
		</x-card>
	</x-settings>
</section>

