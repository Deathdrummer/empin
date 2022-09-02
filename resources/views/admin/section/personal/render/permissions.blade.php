<x-input-group group="normal">
	@if($data['groups'])
		<div class="ddrtabs">
			<div class="ddrtabs__nav">
				<ul class="ddrtabsnav" ddrtabsnav>
					@foreach($data['groups'] as $groupId => $groupData)
						<li
							@class([
								'fz14px',
								'ddrtabsnav__item',
								'ddrtabsnav__item_active' => $loop->first
							])
							ddrtabsitem="permissionsTab{{$groupData['id'] ?? null}}"
							>
							<span>{{$groupData['name'] ?? 'Без названия'}}</span>
						</li>
					@endforeach
				</ul>
			</div>
			
			<ul class="ddrtabs__content ddrtabscontent" ddrtabscontent>
				@foreach($data['groups'] as $groupId => $groupData)
					<li
						@class([
							'ddrtabscontent__item',
							'ddrtabscontent__item_visible' => $loop->first
						])
						ddrtabscontentitem="permissionsTab{{$groupData['id'] ?? null}}"
						>
						@isset($permissions[$groupData['id']])
							<div class="row row-cols-2 gx-20">
								@foreach($permissions[$groupData['id']] as $item)
									<div class="col mb10px">
										<x-checkbox
											label="{{$item['title'] ?? 'Без названия'}}"
											:checked="in_array($item['id'], $user_permissions) ?? false"
											action="setPermissionToUser:{{$user ?? null}},{{$item['id'] ?? null}}"
											/>
									</div>
								@endforeach
							</div>
						@else
							<p class="text-center color-gray">Список пуст</p>
						@endisset
					</li>
				@endforeach
			</ul>
		</div>
	@endif
</x-input-group>