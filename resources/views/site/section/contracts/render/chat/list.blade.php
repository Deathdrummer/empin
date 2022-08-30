@if($messages)
	<div>
		<div class="chat" id="chatMessageList">
			@foreach($messages as $message)
				@include('site.section.contracts.render.chat.item', $message)
			@endforeach
		</div>
		
		<div class="mt2rem">
			<div class="row align-items-end">
				<div class="col">
					{{-- <x-textarea
						rows="5"
						group="normal"
						class="w100"
						/> --}}
					
					<div
						id="chatMessageBlock"
						class="color-gray-600 border-all border-gray-300 border-radius-5px minh4rem maxh15rem scrollblock p1rem"
						contenteditable
						>
						
					</div>
				</div>
				<div class="col-auto">
					<x-button
						id="chatSendMesageBtn"
						group="large"
						variant="blue"
						disabled
						title="Отправить сообщение"
						action="chatSendMesage:{{$contractId}}"
						><i class="fa-solid fa-paper-plane"></i></x-button>
				</div>
			</div>
		</div>
	</div>
@endif