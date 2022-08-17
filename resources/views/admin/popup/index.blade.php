@isset($title)
	<div class="ddrpopup__header" ddrpopupheader>
		<h4 class="ddrpopup__title ddrpopup__title_h1" ddrpopuptitle>{!!__($title)!!}</h4>
		@if($topClose)
			<div
				class="ddrpopup__close"
				{{isset($disabledButtons) && $disabledButtons ? 'disabled' :  ''}}
				title="{{__('ui.close')}}"
				ddrpopupclose
				></div>
		@endif
	</div>
@endisset



<div
	@class([
		'ddrpopup__data',
		'd-flex align-items-center justify-content-center text-center' => $centerMode,
		//$winClass
	])
	ddrpopupdata
	></div>



@isset($dialog)
	<div class="ddrpopup__dialog" ddrpopupdialog>
	 	<div class="popupdialog" ddrpopupdialogwin>
	 		<div class="popupdialog__message">{{__($dialog['text'])}}</div>
	 		<div class="popupdialog__buttons">
	 			@isset($dialog['buttons'])
	 				@foreach($dialog['buttons'] as $title)
						<x-button
						id="ddrpopupDialogBtn{{$loop->index}}"
						>{{__($title)}}</x-button>
					@endforeach
	 			@endisset
	 		</div>
	 	</div>
	 </div>
@endisset



@isset($buttons)
	<div class="ddrpopup__footer" ddrpopupfooter>
		<div
			@class([
				'ddrpopup__buttons',
				'justify-content-'.$buttonsAlign => $buttonsAlign
			])
			ddrpopupbuttons
			>
			<x-buttons-group
				group="{{$buttonsGroup ?? 'small'}}"
				px="15"
				disabled="{{$disabledButtons ?? false}}"
				>
				@foreach($buttons as $button)
					@if(is_array($button))
						<x-button
							variant="{{$button['variant'] ?? 'blue'}}"
							disabled="{{$button['disabled'] ?? $disabledButtons}}"
							action="{{$button['action'] ?? false}}"
							id="{{$button['id'] ?? false}}"
							>{{__($button['title'])}}</x-button>
					@else
						<x-button
							variant="light"
							ddrpopupclose
							title="{{__($button)}}"
							>{{__($button)}}</x-button>
					@endif
				@endforeach
			</x-buttons-group>
		</div>
	</div>
@endisset