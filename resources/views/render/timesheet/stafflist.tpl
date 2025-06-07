<ul class="timesheetcard__stafflist scrollblock scrollblock-light">
	<li ddr-for="stf in staff" class="timesheetcard__staffitem" ddr-click="addTeam:stf.id,day,stf.full_name" staffitem title="Выбрать">
		<p>{{stf.full_name}}</p>
	</li>
	<p class="text-center color-gray-400" ddr-if="staff.length == 0">Нет сотрудников</p>
</ul>