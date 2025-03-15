<div class="staffToUserForm">
	<p class="fz15px mb5px color-gray">E-mail сотрудника:</p>
	<x-input type="email" group="large" class="w100" :value="$email ?? null" id="{{isset($email) ? 'updateStaffToUserEmail' : 'setStaffToUserEmail'}}" />
</div>