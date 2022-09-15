<h4 class="fz16px mb1rem text-center">Поделиться подборкой</h4>

@if($depsUsers)
	<div class="scrollblock h20rem pr8px">
		@foreach($depsUsers as $department => $users)
			@if($department)
				<strong
					class="d-block mb4px pointer color-blue-hovered"
					title="Отправить всем сотрудникам отдела"
					onclick="$.shareSelectionDepartment(this, {{$selectionId}}, {{$department}})"
					>{{$departments[$department]['name']}}</strong>
				
				<x-list space="4px" class="mb14px">
					@forelse($users as $user)
						<x-list.item
							class="pointer color-blue-hovered"
							onclick="$.shareSelectionUser(this, {{$selectionId}}, {{$user['id']}})"
							>{{$user['pseudoname']}}</x-list.item>
					@empty	
						<x-list.item>Нет сотрудников</x-list.item>
					@endif
				</x-list>
			@else
				<strong
					class="d-block mb4px pointer color-blue-hovered"
					title="Отправить всем сотрудникам отдела"
					onclick="$.shareSelectionDepartment(this, {{$selectionId}}, '-1')"
					>Сотрудники без отдела</strong>
				
				<x-list space="4px" class="mb14px pointer color-blue-hovered">
					@forelse($users as $user)
						<x-list.item
							class="pointer color-blue-hovered"
							onclick="$.shareSelectionUser(this, {{$selectionId}}, {{$user['id']}})"
							>{{$user['pseudoname']}}</x-list.item>
					@empty	
						<x-list.item>Нет сотрудников</x-list.item>
					@endif
				</x-list>
			@endif
		@endforeach
	</div>
@endif