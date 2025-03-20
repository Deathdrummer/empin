<h4 class="fz16px mb1rem text-center">Поделиться подборкой</h4>

@if($depsUsers)
	<div class="scrollblock minh20rem maxh25rem pr8px">
		@foreach($depsUsers as $department => $users)
			
			<div class="d-flex align-items-center justify-content-between border-bottom border-gray-300">
				<strong
					class="d-block mb4px"
					title="Отправить всем сотрудникам отдела."
					>{{$departments[$department]['name'] ?? 'Сотрудники без отдела'}}</strong>
				
				<div class="row align-items-center gx-6">
					<div class="col">
						<i
							class="fa-regular fa-clone pointer color-gray-500 color-blue-hovered"
							title="Отправить копию подборки всем сотрудникам отдела.&#013;Изменения вносимые получателями в подборку, не будут отражаться на вашей подборке."
							onclick="$.shareSelection('clone-user-department', {{$selectionId}}, {{$department ?: '-1'}})"
							></i>
					</div>
					<div class="col">
						<i
							class="fa-solid fa-share pointer color-green color-green-hovered"
							title="Отправить подборку всем сотрудникам отдела и сделать всех получателей ПРОСМАТРИВАЮЩИМИ.&#013;У получателей не будет возможности вносить изменения в подборку."
							onclick="$.shareSelection('subscribe-user-department', {{$selectionId}}, {{$department ?: '-1'}}, 'read')"
							></i>
					</div>
					<div class="col">
						<i
							@class([
								'fa-solid',
								'fa-share',
								'pointer' => !$subscribed,
								'color-orange' => !$subscribed,
								'color-gray-200' => $subscribed
							])
							@if($subscribed)
								title="Запрещено."
							@else
								title="Отправить подборку всем сотрудникам отдела и сделать всех получателей РЕДАКТОРАМИ.&#013;Изменения вносимые получателями в подборку, будут синхронизированы у всех пользователей у кого есть данная подборка."
							@endif
							
							@if(!$subscribed)
								onclick="$.shareSelection('subscribe-user-department', {{$selectionId}}, {{$department ?: '-1'}}, 'write')"
							@endif
							></i>
					</div>
				</div>
			</div>
				<x-list space="4px" class="mb14px">
					@forelse($users as $user)
						<x-list.item>
							<div class="d-flex align-items-center justify-content-between border-bottom border-gray-300">
								<p>{{$user['full_name']}}</p>
								
								<div class="row align-items-center gx-6">
									<div class="col">
										<i
											class="fa-regular fa-clone pointer color-gray-500 color-blue-hovered"
											title="Отправить копию подборки.&#013;Изменения вносимые получателем в подборку, не будут отражаться на вашей подборке."
											onclick="$.shareSelection('clone-user', {{$selectionId}}, {{$user['id']}})"
											></i>
									</div>
									<div class="col">
										<i
											class="fa-solid fa-share pointer color-green color-green-hovered"
											title="Отправить подборку и сделать получателя ПРОСМАТРИВАЮЩИМ.&#013;У получателя не будет возможности вносить изменения в подборку."
											onclick="$.shareSelection('subscribe-user', {{$selectionId}}, {{$user['id']}}, 'read')"
											></i>
									</div>
									<div class="col">
										<i
											@class([
												'fa-solid',
												'fa-share',
												'pointer' => !$subscribed,
												'color-orange' => !$subscribed,
												'color-gray-200' => $subscribed
											])
											@if($subscribed)
												title="Запрещено."
											@else
												title="Отправить подборку и сделать получателя РЕДАКТОРОМ.&#013;Изменения вносимые получателем в подборку, будут синхронизированы у всех пользователей у кого есть данная подборка."
											@endif
											
											@if(!$subscribed)
												onclick="$.shareSelection('subscribe-user', {{$selectionId}}, {{$user['id']}}, 'write')"
											@endif
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