<div class="timesheetcard__team" team>	
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
	
	<div class="timesheetcard__remove-sticky">
		<div class="timesheetcard__remove timesheetcard__remove-team h3rem" removeteamblock>
			<p class="tsremove" removeteam><i class="fa-solid fa-trash"></i> Удалить</p>
			<button class="tsclose" closeremoveteam><i class="fa-solid fa-xmark"></i></button>
		</div>
	</div>
	
	<div class="row justify-content-between align-items-center h3rem timesheetcard__panel"
		onpointerdown="longPressStart(event)"
		onpointermove="longPressMove(event)"
        onpointerup="longPressCancel(event)"
        onpointerleave="longPressCancel(event)"
        data-onhold="tsRemoveTeam:{{teamId}}"
        data-duration="600"
        contextmenu="tsRemoveTeamContext:{{teamId}}"
        >
		<div class="col-auto">
			<strong class="timesheetcard__master">{{staffFullName}}</strong>
		</div>
		<div class="col-auto">
			<button class="timesheetcard__btn" ddr-click="teamOpenSearch:teamId,day" noswipe title="Добавить договор">
				<i class="fa-solid fa-plus"></i>
			</button>
		</div>
	</div>
	
	<ul class="timesheetcard__contracts" contractslist></ul>
</div>