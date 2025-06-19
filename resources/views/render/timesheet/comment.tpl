<li 
	class="tchat__message tmessage tmessage_self"
	onpointerdown="longPressStart(event)"
	onpointermove="longPressMove(event)"
    onpointerup="longPressCancel(event)"
    onpointerleave="longPressCancel(event)"
    data-onhold="tsRemoveMessage:{{id}}"
    data-duration="600"
    contextmenu="tsRemoveMessageContext:{{id}}"
    message
	>
	
	<div class="timesheetcard__remove h100" removemessageblock>
		<p class="tsremove" removemessage><i class="fa-solid fa-trash"></i> Удалить</p>
		<button class="tsclose" closeremovemessage><i class="fa-solid fa-xmark"></i></button>
	</div>
	
	<div class="row justify-content-between mb2px">
		<div class="col-auto"><strong class="tmessage__name">{{from.fname}} {{from.sname}}</strong></div>
		<div class="col-auto"><small class="tmessage__date">{{created_at}}</small></div>
	</div>
	<p class="tmessage__message format">{{message}}</p>
</li>