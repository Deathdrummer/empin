<x-input-group group="normal">
	@if($permissions)
		<div class="ddrtabs">
			<div class="ddrtabs__nav">
				<ul class="ddrtabsnav" ddrtabsnav>
					@foreach($permissions as $group => $items)
						<li
							@class([
								'ddrtabsnav__item',
								'ddrtabsnav__item_active' => $loop->first
							])
							ddrtabsitem="permissionsTab{{$group}}"
							>{{$data['permissions_groups'][$group]}}</li>
					@endforeach
				</ul>
			</div>
			
			<ul class="ddrtabs__content ddrtabscontent" ddrtabscontent>
				@foreach($permissions as $group => $items)
					<li
						@class([
							'ddrtabscontent__item',
							'ddrtabscontent__item_visible' => $loop->first
						])
						ddrtabscontentitem="permissionsTab{{$group}}"
						>
						@forelse($items as $item)
							<div class="mb10px pl2rem">
								<x-checkbox
									label="{{$item['title']}}"
									checked="{{isset($role_permissions[$item['id']])}}"
									action="setPermissionToRole:{{$item['id']}}" />
							</div>
						@empty
							<p>Список пуст</p>
						@endforelse
					</li>
				@endforeach
			</ul>
		</div>
	@endif
</x-input-group>