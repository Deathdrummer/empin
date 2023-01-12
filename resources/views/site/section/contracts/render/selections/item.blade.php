<tr class="h6rem">
	<td>
		@if($subscribed_read)
			<p class="fz12px ml6px">{{$title}}</p>
		@elseif(!$subscribed || $subscribed_write)
			<x-input
				name="title"
				group="small"
				:value="$title"
				class="w100"
				/>
		@endif	
	</td>
	<td class="center">
		@if(isset($contracts_count) && $contracts_count)
			<strong class="color-green fz14px">{{$contracts_count}}</strong>
		@else
			<p class="color-gray-500">-</p>
		@endif
	</td>
	<td class="center">
		<x-buttons-group group="small" gx="5">
			<x-button
				variant="green"
				action="selectionBuildList:{{$id}}"
				:enabled="$contracts_count ?? false"
				title="Сформировать список подборки"
				>Сформировать</x-button>
			
			<x-button
				variant="neutral"
				w="2rem-5px"
				action="selectionBuildToEdit:{{$id}}"
				:enabled="(($contracts_count ?? false) && (!$subscribed || $subscribed_write))"
				title="Редактировать список подборки"
				><i class="fa-solid fa-pen-to-square"></i></x-button>
		</x-buttons-group>
	</td>
	<td class="center">
		<x-button
			group="verysmall"
			variant="light"
			action="selectionSendMessages:{{$id}}"
			title="Отправить сообщения в чаты договоров"
			:enabled="$contracts_count ?? false"
			>
			<i class="fa-solid fa-comments"></i>
		</x-button>
	</td>
	<td class="center">
		<x-button
			group="verysmall"
			variant="yellow"
			action="selectionShare:{{$id}},{{$subscribed}}"
			title="Поделиться подборкой с другими сотрудниками"
			>
			<i class="fa-solid fa-share-nodes"></i>
		</x-button>
	</td>
	<td class="center">
		<x-buttons-group group="verysmall" w="2rem-5px" gx="5">
			<x-button variant="blue" action="selectionUpdate:{{$id}}" disabled update title="Обновить"><i class="fa-solid fa-save"></i></x-button>
			@if($subscribed)
				<x-button variant="orange" action="selectionUnsubscribe:{{$id}}" remove title="Отписаться"><i class="fa-solid fa-link-slash"></i></x-button>
			@else
				<x-button variant="red" action="selectionRemove:{{$id}}" remove title="Удалить"><i class="fa-solid fa-trash-can"></i></x-button>
			@endif
		</x-buttons-group>
	</td>
</tr>