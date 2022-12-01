<section>
	<x-settings>
		<div class="row g-10">
			<div class="col-12">
				<x-card
					loading
					ready
					title="Окно дополнительной информации о договоре"
					desc="Настройка полей модального окна"
					>
					<x-simplelist
						setting="contracts-common-info"
						fieldset="ID:w7rem|number|id|1,Название поля:w30rem|textarea|name,Тип поля:w20rem|select|type,Количество строк:w10rem|number|rows_count,Количество символов:w10rem|number|limit,Описание:w30rem|textarea|desc"
						options="type;input:Однострочное поле,textarea:Многострочное поле"
						group="small"
						onRemove="clearCommonInfo"
					 />
				</x-card>
			</div>
		</div>
	</x-settings>
</section>





<script type="module">
	$.clearCommonInfo = (tr, done) => {
		let fieldId = $(tr).find('[field="id"]').attr('value') || $(tr).find('[field="id"]').val();
		axiosQuery('delete', 'site/contracts/common_info', {field_id: parseInt(fieldId)}, 'json').then(({data, error, status, headers}) => {
			if (!data) {
				$.notify('Не удалось очистить информацию о договорах!', 'error');
				console.log(error?.message, error?.errors);
			}
			done();
		}).catch((e) => {
			console.log(e);
		});
	}
</script>