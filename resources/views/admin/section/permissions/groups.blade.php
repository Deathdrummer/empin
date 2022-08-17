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
						fieldset="ID группы:w5rem|number|id,Название группы:w30rem|text|name,Группа пользователей:w30rem|select|group"
						options="group;admin:Администраторы,site:Сотрудники"
						group="small"
					 />
				</x-card>
			</div>
		</div>
	</x-settings>
</section>