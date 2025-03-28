<x-input-group group="normal">
	<div class="row row-cols-2 gx-30">
		<div class="col">
			<div class="ddrlist">
				<div class="ddrlist__item mb3rem">
					<p class="fz14px mb10px">Отображать сотудников:</p>
					<x-chooser class="h24px" variant="neutral" px="20">
						<x-chooser.item
							action="setWorkingStat"
							:active="is_null($working)"><span class="fz14px">Всех</span></x-chooser.item>
						
						<x-chooser.item
							action="setWorkingStat:1"
							:active="$working === 1"><span class="fz14px">Работающих</span></x-chooser.item>
						
						<x-chooser.item
							action="setWorkingStat:0"
							:active="$working === 0"><span class="fz14px">Уволеных</span></x-chooser.item>
					</x-chooser>
				</div>
				
				<div class="ddrlist__item mb3rem">
					<p class="fz14px mb10px">Пользователи Empin-Pro:</p>
					<x-toggle
						group="large"
						action="setRegStat"
						:checked="$registred" />
				</div>
				
				<div class="ddrlist__item mb3rem">
					<p class="fz14px mb10px">Отображать сотрудников отделов:</p>
					<div class="ddrlist">
						@foreach($departmentsList as $dept)
							<div class="ddrlist__item mb1rem">
								<x-checkbox
									:label="$dept['name']"
									:checked="$dept['active']"
									action="setDepartment:{{$dept['id']}}"
									/>
							</div>
						@endforeach
					</div>
				</div>
			</div>
		</div>
		<div class="col"></div>
	</div>
	
	
			
	
</x-input-group>