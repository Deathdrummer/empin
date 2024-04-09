<div id="editActsForm">
	<x-input-group group="normal">
		<div class="row row-cols-1 gy-10">
			<div class="col">
				<p class="color-gray-600 mb5px">Дата подачи выполнения</p>
				<x-datepicker name="date_send_action" :date="null" class="w30rem" />
			</div>
			<div class="col">
				<p class="color-gray-600 mb5px">Количество актов КС-2</p>
				<x-input type="number" name="count_ks_2" class="w7rem" showrows />
			</div>
			<div class="col">
				<p class="color-gray-600 mb5px">Акт на ПИР</p>
				<x-checkbox variant="normal" name="act_pir" />
			</div>
		</div>
	</x-input-group>	
</div>