<h4 class="fz16px mb1rem text-center">Поделиться подборкой</h4>

@if($depsUsers)
	<div class="scrollblock h20rem pr8px">
		@foreach($depsUsers as $department => $users)
			
			<div class="d-flex align-items-center justify-content-between border-bottom border-gray-300">
				<strong
					class="d-block mb4px"
					title="Отправить всем сотрудникам отдела"
					>{{$departments[$department]['name'] ?? 'Сотрудники без отдела'}}</strong>
				
				<div class="row align-items-center gx-6">
					<div class="col">
						<i
							class="fa-regular fa-clone pointer color-gray-500 color-blue-hovered"
							title="Клонировать"
							onclick="$.shareSelection('clone-user-department', {{$selectionId}}, {{$department ?: '-1'}})"
							></i>
					</div>
					<div class="col">
						<i
							class="fa-solid fa-share pointer color-gray-500 color-blue-hovered"
							title="Подписать всех сотрудников отдела"
							onclick="$.shareSelection('subscribe-user-department', {{$selectionId}}, {{$department ?: '-1'}})"
							></i>
					</div>
				</div>
			</div>
				<x-list space="4px" class="mb14px">
					@forelse($users as $user)
						<x-list.item>
							<div class="d-flex align-items-center justify-content-between border-bottom border-gray-300">
								<p>{{$user['pseudoname']}}</p>
								
								<div class="row align-items-center gx-6">
									<div class="col">
										<i
											class="fa-regular fa-clone pointer color-gray-500 color-blue-hovered"
											title="Клонировать"
											onclick="$.shareSelection('clone-user', {{$selectionId}}, {{$user['id']}})"
											></i>
									</div>
									<div class="col">
										<i
											class="fa-solid fa-share pointer color-gray-500 color-blue-hovered"
											title="Подписать сотрудника"
											onclick="$.shareSelection('subscribe-user', {{$selectionId}}, {{$user['id']}})"
											></i>
									</div>
								</div>
							</div>
						</x-list.item>
					@empty	
						<x-list.item>Нет сотрудников</x-list.item>
					@endif
				</x-list>
		@endforeach
	</div>
@endif