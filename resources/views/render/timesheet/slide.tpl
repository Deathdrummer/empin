<div class="timesheetcard" ddr-class="{'timesheetcard_today': isToday}" timesheetcard>
	<div class="timesheetcard__top">
		<h3 ddr-class="{'color-red': isWeekEnd}" class="timesheetcard__title">{{humanDate}}</h3>
		<p class="timesheetcard__subtitle">{{weekDay}}</p>
	</div>
	
	<div class="timesheetcard__content">
		<div class="timesheetcard__teams scrollblock scrollblock-hidescroll" teams>
			<div ddr-for="team in teams" class="timesheetcard__team" team>
				<div class="timesheetcard__search" search>
					<div class="input normal-input normal-input-search normal-input_iconed w100" style="box-shadow: 0 0 8px 0 #0000000a;">	
						<input type="search" value="" placeholder="Поиск..." autocomplete="off" inpgroup="normal" searchinput ddr-input="teamSearchContracts">
						<div class="postfix_icon">
							<i class="fa-solid fa-magnifying-glass"></i>
						</div>
						<div class="normal-input__errorlabel noselect" errorlabel=""></div>
						<div class="timesheetcard__searchresutls searchresutls"searchresutls></div>
					</div>
				</div>
				
				<div class="row justify-content-between align-items-center h3rem timesheetcard__panel">
					<div class="col-auto">
						<strong class="timesheetcard__master">{{team.master.full_name}}</strong>
					</div>
					<div class="col-auto">
						<button class="timesheetcard__btn" ddr-click="teamOpenSearch:team.id,day" noswipe title="Добавить договор">
							<i class="fa-solid fa-plus"></i>
						</button>
					</div>
				</div>
				
				<ul ddr-if="team.contracts" class="timesheetcard__contracts" contractslist>
					<li ddr-for="contract in team.contracts" contract>
						<div class="table timesheetcard__table">
							<table>
								<tr>
									<td><strong class="fz10px">{{contract.object_number}}</strong></td>
									<td><p class="fz10px">{{contract.title}}</p></td>
									<td>
										<div class="scrollblock scrollblock-light h4rem fz10px">{{contract.titul}}</div>
									</td>
								</tr>
							</table>
						</div>
						<div class="timesheetcard__chatblock tchat">
							<ul class="tchat__list" tchat>
								<li ddr-for="mess in contract.chat" class="tchat__message tmessage" ddr-class="{'tmessage_self': mess.self}">
									<div class="row justify-content-between mb2px">
										<div class="col-auto"><strong class="tmessage__name">{{mess.from.fname}} {{mess.from.sname}}</strong></div>
										<div class="col-auto"><small class="tmessage__date">{{mess.created_at}}</small></div>
									</div>
									<p class="tmessage__message format">{{mess.message}}</p>
								</li>
							</ul>
							
							<div class="tchat__form" noswipe>
								<div class="tchat__input" contenteditable placeholder="Сообщение..." tchatmess ddr-action="setBtnStat|setToEnter:{{contract.timesheet_contract_id}}"></div>
								<button class="tchat__btn" ddr-click="teamAddComment:contract.timesheet_contract_id" noswipe title="Отправить" ddr-action="sendMess" disabled><i class="fa fa-paper-plane"></i></button>
							</div>
						</div>	
					</li>
				</ul>
			</div>
		</div>
	</div>
		
	<div class="timesheetcard__bottom">
		<div class="button small-button button-light">
			<button class="pointer" ddr-click="getTeamList:day" noswipe>Добавить бригаду</button>
		</div>
	</div>
	
	<div class="timesheetcard__staffwrap" staffwrap>
		<p class="timesheetcard__stafftitle">Выберите Бригадира <i class="fa fa-close" title="Отмена"></i></p>
		<div stafflist></div>
	</div>
</div>