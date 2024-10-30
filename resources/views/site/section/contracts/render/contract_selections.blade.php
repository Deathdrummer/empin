<div ondblclick="event.stopPropagation();" oncontextmenu="event.stopPropagation();event.preventDefault();">
	<h4 class="fz16px mb1rem">Этот договор в следующих подборках:</h4>
	<div class="scrollblock-light pr5px minh2rem" style="max-height: calc(100vh - 100px);">
		@if($selections)
			<ul>
				@foreach($selections as $selection)
					<li
						@class([
							'border-top border-light' => !$loop->first,
							'd-flex align-items-center h4rem',
						])
						>
						<div class="currentselection__label">
							<p id="currentSelectionTitle">{{$selection['title'] ?? '--'}}</p>
						</div>
							
						<x-button
							variant="red"
							group="verysmall"
							title="Отменить подборку"
							class="noselect w2rem-6px ml5px"
							action="removeContractFromSelection:{{$selection['id'] ?? null}}"
							style="border-radius:5px;border-color:transparent;height:26px;"
							:enabled="$selection['can_write']"
							>
							<i class="fa-solid fa-xmark"></i>
						</x-button>	
					</li>
				@endforeach
			</ul>
		@else
			<p class="color-gray fz14px">Нет подборок</p>
		@endif
	</div>
</div>


