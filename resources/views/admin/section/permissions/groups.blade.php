<section>
	<x-settings>
		<div class="row g-10">
			<div class="col">
				<x-card
					loading
					ready
					{{-- title="Разделы разрешений"
					desc="Разделы разрешений для группировки списка разрешений" --}}
					>
					<x-simplelist
						setting="permissions_groups"
						fieldset="ID группы:w5rem|number|id|1,Название группы:w50rem|text|name,Группа пользователей:w30rem|select|group"
						options="group;admin:Администраторы,site:Сотрудники"
						group="small"
						onRemove="removePermissionpGroup"
					 />
				</x-card>
			</div>
		</div>
	</x-settings>
</section>




<script type="module">
	
	$.removePermissionpGroup = (row, done) => {
		let id = $(row).find('[field="id"]').attr('value');
		axiosQuery('delete', 'ajax/permissions/sections', {group: id}).then(({data, error, status, headers}) => {
			if (data) {
				done();
			}
		}).catch((e) => {
			console.log(e);
		});
	}
	
	
</script>