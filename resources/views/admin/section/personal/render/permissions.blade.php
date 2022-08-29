<x-input-group group="normal">
	@if($permissions)
		<div class="ddrtabs">
			<div class="ddrtabs__nav">
				<ul class="ddrtabsnav" ddrtabsnav>
					@foreach($permissions as $group => $items)
						<li
							@class([
								'fz14px',
								'ddrtabsnav__item',
								'ddrtabsnav__item_active' => $loop->first
							])
							ddrtabsitem="permissionsTab{{$group}}"
							>
							@isset($data['permissions_groups'][$group])
								<span>{{$data['permissions_groups'][$group]}}</span>
							@else
								<span class="color-red">Группа удалена</span>
							@endisset
						</li>
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
						<div class="row row-cols-2 gx-20">
							@forelse($items as $item)
								<div class="col mb10px">
									<x-checkbox
										label="{{$item['title']}}"
										checked="{{isset($user_permissions[$item['id']])}}"
										action="setPermissionToUser:{{$user}},{{$item['id']}}" />
								</div>
							@empty
								<p>Список пуст</p>
							@endforelse
						</div>
							
					</li>
				@endforeach
			</ul>
		</div>
	@endif
</x-input-group>