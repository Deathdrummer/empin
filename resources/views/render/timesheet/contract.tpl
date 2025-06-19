<li contract>
	
	<div 
		class="table timesheetcard__table"
		contracttable
		onpointerdown="longPressStart(event)"
		onpointermove="longPressMove(event)"
	    onpointerup="longPressCancel(event)"
	    onpointerleave="longPressCancel(event)"
	    data-onhold="tsRemoveContract:{{timesheet_contract_id}}"
	    data-duration="600"
	    contextmenu="tsRemoveContractContext:{{timesheet_contract_id}}"
		>
		
		<div class="timesheetcard__remove h4rem-4px" removecontractblock>
			<p class="tsremove" removecontract><i class="fa-solid fa-trash"></i> Удалить</p>
			<button class="tsclose" closeremovecontract><i class="fa-solid fa-xmark"></i></button>
		</div>
		
		<table>
			<tr>
				<td><strong class="fz10px">{{object_number}}</strong></td>
				<td><p class="fz10px">{{title}}</p></td>
				<td>
					<div class="scrollblock scrollblock-light h4rem fz10px">{{titul}}</div>
				</td>
			</tr>
		</table>
	</div>
	
	<div class="timesheetcard__chatblock tchat">
		<ul class="tchat__list" tchat></ul>
		
		<div class="tchat__form" noswipe>
			<div class="tchat__input" contenteditable placeholder="Сообщение..." tchatmess ddr-action="setBtnStat|setToEnter:{{timesheet_contract_id}}"></div>
			<button class="tchat__btn" ddr-click="teamAddComment:timesheet_contract_id" noswipe title="Отправить" ddr-action="sendMess" disabled><i class="fa fa-paper-plane"></i></button>
		</div>
	</div>
</li>