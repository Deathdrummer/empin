<?php

use App\Enums\ContractColums;
use App\Enums\VirtualVars;
use App\Http\Controllers\admin\Acts;
use App\Http\Controllers\ContractsFilesController;
use App\Http\Controllers\crud\Admins;
use App\Http\Controllers\crud\Contracts;
use App\Http\Controllers\crud\Departments;
use App\Http\Controllers\crud\Permissions;
use App\Http\Controllers\crud\Roles;
use App\Http\Controllers\crud\Staff;
use App\Http\Controllers\crud\Steps;
use App\Http\Controllers\crud\StepsPatterns;
use App\Http\Controllers\crud\UsersNew;
use App\Http\Controllers\SiteParserController;
use App\Http\Controllers\TabsController;
use App\Http\Controllers\UploadFilesController;
use App\Models\User;
use App\Services\Business\Department;
use App\Services\Business\User as BusinessUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['isajax:admin', 'lang'])->post('/popup', function(Request $request) {
	return view('admin.popup.index', $request->all());
});


Route::middleware(['isajax:admin', 'lang'])->post('/langline', function(Request $request) {
	$transChoise = $request->input('trans');
	$line = $request->input('line');
	if ($transChoise) return trans_choice($line, $request->input('count'));
	return __($line);
});



// табы
Route::controller(TabsController::class)->middleware(['lang'])->group(function() {
	Route::post('/tabs', 'index');
});




Route::middleware(['isajax:admin', 'lang'])->post('/simplelist', function(Request $request) {
	
	['id' => $id, 'row' => $row, 'fields' => $fieldsRaw, 'options' => $rawOptions, 'setting' => $setting, 'group' => $group] = $request->validate([
		'id'		=> 'required|string',
		'row'		=> 'required|numeric',
		'fields'	=> 'required|string',
		'options' 	=> 'string|nullable',
		'setting'	=> 'required|string',
		'group'		=> 'required|string',
	]);
	
	
	$options = [];
	if ($rawOptions) {
		$optionsString = str_replace('::', ',', htmlspecialchars_decode($rawOptions));	
			
		$opsData = splitString($optionsString, '|');
		
		foreach ($opsData as $ops) {
			[$name, $values] = splitString($ops, ';');
			$opsValues = splitString($values, ',');
			
			foreach ($opsValues as $optVal) {
				//[$val, $title] = splitString($optVal, ':');
				$o = splitString($optVal, ':');
				
				$options[$name][$o[0]] = $o[1] ?? $o[0];
			}
		}
	}
	
	
	
	
	$fieldsData = splitString($fieldsRaw, '|');

	$fields = [];
	foreach ($fieldsData as $field) {
		[$type, $name] = splitString($field, ':');
		
		$fields[] = [
			'type' => $type, 
			'name' => $name
		];
	}
	
	return view('components.simplelist.item', compact('id', 'fields', 'setting', 'row', 'group', 'options'));
});











//-------------------------------------------------------------------------------------------------- CRUD



// Адинистраторы
Route::post('/admins/permissions', [Admins::class, 'permissions']);
Route::put('/admins/permissions', [Admins::class, 'set_permissions']);
Route::middleware('lang')->post('/admins/send_email', [Admins::class, 'send_email']);
Route::post('/admins/store_show', [Admins::class, 'store_show']);
Route::resource('admins', Admins::class);



// Персонал
Route::post('/staff/permissions', [Staff::class, 'permissions']);
Route::put('/staff/permissions', [Staff::class, 'set_permissions']);
Route::middleware('lang')->post('/staff/send_email', [Staff::class, 'send_email']);
Route::post('/staff/store_show', [Staff::class, 'store_show']);
Route::resource('staff', Staff::class);


// Сотрудники нов.
Route::post('/users_new/permissions', [UsersNew::class, 'permissions']);
Route::put('/users_new/permissions', [UsersNew::class, 'set_permissions']);

Route::get('users_new/reg_staff_to_user', [UsersNew::class, 'get_staff_to_user_form']);
Route::post('users_new/reg_staff_to_user', [UsersNew::class, 'reg_staff_to_user']);
Route::delete('users_new/reg_staff_to_user', [UsersNew::class, 'unreg_user_from_staff']);

Route::get('users_new/change_user_email', [UsersNew::class, 'get_user_email']);
Route::post('users_new/change_user_email', [UsersNew::class, 'change_user_email']);
Route::post('/users_new/send_email', [UsersNew::class, 'send_email']);

Route::post('/users_new/set_role', [UsersNew::class, 'set_role']);
Route::post('/users_new/set_department', [UsersNew::class, 'set_department']);

Route::post('/users_new/list_user', [UsersNew::class, 'add_to_list_user']);
Route::delete('/users_new/list_user', [UsersNew::class, 'remove_from_list_user']);

Route::put('/users_new/set_show_in_selection', [UsersNew::class, 'set_show_in_selection']);
Route::put('/users_new/set_working', [UsersNew::class, 'set_working']);


Route::post('/users_new/store_show', [UsersNew::class, 'store_show']);
Route::resource('users_new', UsersNew::class);






// Роли
Route::post('/roles/permissions', [Roles::class, 'permissions']);
Route::put('/roles/permissions', [Roles::class, 'permissions_save']);
Route::post('/roles/store_show', [Roles::class, 'store_show']);
Route::resource('roles', Roles::class);





// Разрешения
Route::post('/permissions/sections', [Permissions::class, 'sections']);
Route::put('/permissions/sections', [Permissions::class, 'section_save']);
Route::delete('/permissions/sections', [Permissions::class, 'section_remove']);
Route::post('/permissions/store_show', [Permissions::class, 'store_show']);
Route::resource('permissions', Permissions::class);






// Отделы
Route::post('/departments/store_show', [Departments::class, 'store_show']);
Route::resource('departments', Departments::class);





// Этапы
Route::post('/steps/init', [Steps::class, 'init']);
Route::post('/steps/store_show', [Steps::class, 'store_show']);
Route::resource('steps', Steps::class);





// Шаблоны этапов
Route::get('/steps_patterns/steps', [StepsPatterns::class, 'steps']);
Route::post('/steps_patterns/steps', [StepsPatterns::class, 'steps_save']);
Route::delete('/steps_patterns/steps', [StepsPatterns::class, 'steps_remove']);
Route::post('/steps_patterns/store_show', [StepsPatterns::class, 'store_show']);
Route::resource('steps_patterns', StepsPatterns::class);





Route::get('/contracts_files_list', [UploadFilesController::class, 'list']);
Route::get('/get_values_to_filter', [UploadFilesController::class, 'get_values_to_filter']);
Route::post('/upload_file', [UploadFilesController::class, 'upload']);
Route::delete('/upload_file', [UploadFilesController::class, 'remove']);



# Файлы договоров
Route::post('/contracts_files', [ContractsFilesController::class, 'upload']);
Route::get('/contracts_files', [ContractsFilesController::class, 'download']);
Route::delete('/contracts_files', [ContractsFilesController::class, 'remove']);




Route::get('/get_export_cheatsheet', function (Request $request) {
	$data = ContractColums::asArray();
	$virtVars = VirtualVars::asArray();
	
	return response()->json(['cols' => $data, 'virtVars' => $virtVars]);
});

Route::get('/get_export_hints', function (Request $request) {
	return response()->view('admin/section/contracts/render/templates-to-export/hints');
});







// Договоры
Route::get('/contracts/to_archive/{id}', [Contracts::class, 'to_archive']);
Route::get('/contracts/set_customer_rules', [Contracts::class, 'set_customer_rules']);
Route::get('/contracts/get_deps_hidden_statuses', [Contracts::class, 'get_deps_hidden_statuses']);
Route::put('/contracts/set_dept_hidden_status', [Contracts::class, 'set_dept_hidden_status']);
Route::post('/contracts/store_show', [Contracts::class, 'store_show']);
Route::get('/contracts/check_exists_contracts', [Contracts::class, 'check_exists_contracts']);
Route::resource('contracts', Contracts::class);







Route::get('/get_employee_list', function (Request $request, Department $departmentService) {
	$deptId = $request->input('dept_id', 0);
	$stepId = $request->input('step_id', 0);
	
	$depsUsers = $departmentService->getUsersToAssign([$deptId], ['id', 'full_name']);
	//$test = $usersService->get(fields: ['department_id', 'full_name', 'working'], registred: true, departments: [$deptId]);
	
	
	return $depsUsers[$deptId][$stepId];
	
    //return User::select('id', 'pseudoname')->where('department_id', $deptId)->get();
});










// Парсер сайтовы
Route::get('/siteparser/list', [SiteParserController::class, 'list']);
Route::get('/siteparser/get_subjects', [SiteParserController::class, 'get_subjects']);
Route::get('/siteparser/import_form', [SiteParserController::class, 'import_form']);
Route::post('/siteparser/import_form', [SiteParserController::class, 'import_data']);
Route::post('/siteparser/set_stat', [SiteParserController::class, 'set_stat']);