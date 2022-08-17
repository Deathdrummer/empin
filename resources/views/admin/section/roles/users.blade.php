<section>
	<x-card id="rolesCard" loading>
		<div class="table">
			<table class="w100">
				<thead>
					<tr>
						<td class="w30rem"><strong>Название</strong></td>
						<td></td>
						<td class="w7rem center"><strong>Права</strong></td>
						<td class="w10rem center"><strong>Действия</strong></td>
					</tr>
				</thead>
				<tbody id="rolesUsersList"></tbody>
				<tfoot>
					<tr>
						<td colspan="6" class="right">
							<x-button id="rolesAddBtn" variant="blue" group="normal" px="15" action="rolesAddBtn" disabled>Добавить</x-button>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
	</x-card>
</section>


<script type="module">
	
	$.ddrCRUD({
		container: '#rolesUsersList',
		itemToIndex: 'tr',
		route: 'ajax/roles',
		globalParams: {guard: 'site'},
		viewsPath: 'admin.section.roles.render.users',
		sortField: 'sort'
	}).then(({error, list, changeInputs, create, store, storeWithShow, update, destroy, remove, query, viewsPath}) => {
		
		$('#rolesCard').card('ready');
		
		if (error) {
			$.notify(error.message, 'error');
			return false;
		} 
		
		$('#rolesAddBtn').ddrInputs('enable');
		
		changeInputs({'[save], [update]': 'enable'});
		
		
		$.rolesAddBtn = () => {
			let rolesAddBtnWait = $(this).ddrWait({
				iconHeight: '26px',
				bgColor: '#ffffff91'
			});
			
			create((data, container, {error}) => {
				rolesAddBtnWait.destroy();
				if (data) $(container).append(data);
				if (error) $.notify(error.message, 'error');
			});
		}
		
		
		$.roleSave = (btn) => {
			let row = $(btn).closest('tr');
			
			let roleSaveWait = $(row).ddrWait({
				iconHeight: '26px',
				bgColor: '#ffffffd6'
			});
			
			storeWithShow(row, (data, container, {error}) => {
				if (data) {
					$(row).replaceWith(data);
					$.notify('Запись успешно сохранена!');
				}
				
				if (error) {
					roleSaveWait.destroy();
					$.notify(error.message, 'error');
				} 
				
				if (error.errors) {
					$.each(error.errors, function(field, errors) {
						$(row).find('[name="'+field+'"]').ddrInputs('error', errors[0]);
					});
				}
			});
		}
		
		
		
		$.roleUpdate = (btn, id) => {
			let row = $(btn).closest('tr');
			
			update(id, row, (data, container, {error}) => {
				if (data) {
					$(btn).ddrInputs('disable');
					$(row).find('input, select, textarea').ddrInputs('state', 'clear');
					$.notify('Запись успешно обновлена!');
				}
				
				if (error) $.notify(error.message, 'error');
				
				if (error.errors) {
					$.each(error.errors, function(field, errors) {
						$(row).find('[name="'+field+'"]').ddrInputs('error', errors[0]);
					});
				}
			});
		}
		
		
		
		$.roleRemove = (btn, id = null) => {
			let row = $(btn).closest('tr');
			
			if (!id) {
				remove(row);
			} else {
				let removeRolePopup = ddrPopup({
					width: 400, // ширина окна
					lhtml: 'dialog.delete', // контент
					buttons: ['ui.cancel', {title: 'ui.delete', variant: 'red', action: 'roleRemoveAction'}],
					centerMode: true,
					winClass: 'ddrpopup_dialog color-red'
				});
				
				removeRolePopup.then(({close, wait}) => {
					$.roleRemoveAction = (btn) => {
						wait();
						destroy(id, function(stat) {
							if (stat) {
								remove(row);
								$.notify('Запись успешно удалена!');
							} else {
								$.notify('Ошибка удаления записи!', 'error');
							} 
							close();
						});
					}
				});	
			}
		}
		
		
		
		
		
		$.roleGetPermissions = (btn, roleId) => {
			let roleTitle = $(btn).closest('tr').find('[name="title"]').val();
			
			ddrPopup({
				url: '/ajax/roles/permissions',
				params: {view: 'admin.section.roles.render.permissions', guard: 'site', role: roleId},
				title: '<span class="fz13px color-gray">Права для роли:</span> '+roleTitle,
				width: 800, // ширина окна
				//html: '<p>Привет я html!</p>', // контент
				buttons: ['ui.close'],
				//buttonsAlign, // выравнивание вправо
				//disabledButtons: true,
				//closeByBackdrop: false, // Закрывать окно только по кнопкам [ddrpopupclose]
				//changeWidthAnimationDuration, // ms
				//buttonsGroup: 'verysmall', // группа для кнопок
				//winClass, // добавить класс к модальному окну
				//centerMode, // контент по центру
				//topClose: false // верхняя кнопка закрыть
			}).then(({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
				
				$.setPermissionToRole = (btn, permissionId) => {
					$(btn).ddrInputs('disable');
					let checkStat = $(btn).is(':checked') ? 1 : 0;
					let setPermissionToRole =  axiosQuery('put', 'ajax/roles/permissions', {
						role: parseInt(roleId),
						permission: parseInt(permissionId),
						stat: checkStat
					}, 'json');
					setPermissionToRole.then(({data, error}) => {
						if (error) $.notify(error.message, 'error');
						$(btn).ddrInputs('enable');
					});
				};
				
				
				
				
			});
		}
		
		
		$.setDialogNo = ({closeDialog}) => {
			closeDialog();
		}

	});
	

</script>