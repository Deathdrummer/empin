<div class="table">
	<table>
		<thead>
			<tr>
				<td class="w4rem"></td>
				<td><strong class="fz12px">Название отдела</strong></td>
			</tr>
		</thead>
		<tbody id="contractDepsList">
			@forelse($sortDeps as $id => $name)
				<tr class="h4rem">
					<td class="center"><i class="fa-solid fa-fw fa-arrows-up-down color-gray fz12px"></i></td>
					<td sortdept="{{$id}}">
						<p>{{$name}}</p>
					</td>
				</tr>
			@empty
				<tr>
					<td colspan="2">
						<p class="color-gray-300">Нет данных</p>
					</td>
				</tr>
			@endforelse
		</tbody>
	</table>
</div>