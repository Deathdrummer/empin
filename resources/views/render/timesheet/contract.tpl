<li contract>
	<div class="table timesheetcard__table">
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