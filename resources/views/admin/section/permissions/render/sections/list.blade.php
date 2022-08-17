<div class="d-flex justify-content-end mb20px">
	<x-button
		id="refreshSectionsPermissions"
		group="normal"
		variant="green"
		action="permissionSectionReset:{{$guard}}"
		disabled
		px="20"
		>Обновить</x-button>
</div>

<form id="permissionsSectionsForm" class="table">
	<table class="w100">
		<thead>
			<tr>
				<td class="w30rem"><strong>Название разрешения</strong></td>
				<td class="w50rem"><strong>Раздел</strong></td>
				<td class="w30rem"><strong>Группировка</strong></td>
				<td></td>
				<td class="w6rem" title="Состоит в разрешениях"><strong>Разр.</strong></td>
			</tr>
		</thead>
		<tbody id="permissionsSectionsList">
			@forelse($list as $section => $item)
				@if(!isset($item['id']) && !isset($item['section']))
					@continue
				@endif
				<tr class="h5rem">
					<td>section-{{$section}}:{{$guard}}</td>
					<td>
						<x-input
							name="sections[{{$item['id']}}][title]"
							value="{{$item['perm_title'] ?? $item['title'] ?? null}}"
							group="normal"
							class="w100"
						 />
					</td>
					<td>
						<x-select
							name="sections[{{$item['id']}}][group]"
							group="normal"
							class="w100"
							:options="$data['permissions_groups']"
							value="{{$item['group'] ?? null}}"
							{{-- action="permissionsSectionSetGroup:{{$item['id']}},{{$guard}}-section-{{$section}},{{$item['title']}}" --}}
							/>
					</td>
					<td>
						<input type="hidden" name="sections[{{$item['id']}}][name]" value="section-{{$section}}:{{$guard}}">
						{{-- <input type="hidden" name="sections[{{$item['id']}}][title]" value="{{$item['title']}}"> --}}
					</td>
					<td class="center" index="{{$item['id']}}">
						@if(isset($item['has_permission']) && $item['has_permission'])
							<i class="fa-solid fa-check color-green fz20px"></i>
						@endif
					</td>
				</tr>
			@empty
			@endforelse
		</tbody>
	</table>
</form>				