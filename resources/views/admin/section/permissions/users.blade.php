<section>
	<x-card id="permissionsCard" loading>
		<div class="table">
			<table class="w100">
				<thead>
					<tr>
						<td class="w50rem"><strong>Системное название</strong></td>
						<td class="w50rem"><strong>Название</strong></td>
						<td class="w30rem"><strong>Группа прав</strong></td>
						<td></td>
						<td class="w10rem center"><strong>Действия</strong></td>
					</tr>
				</thead>
				<tbody id="permissionsUsersList"></tbody>
				<tfoot>
					<tr>
						<td colspan="6" class="right">
							<x-button id="permissionAddBtn" variant="blue" group="normal" px="15" action="permissionAddBtn" disabled>Добавить</x-button>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
	</x-card>
</section>


<script type="module">
	
	$.ddrCRUD({
		container: '#permissionsUsersList',
		itemToIndex: 'tr',
		route: 'ajax/permissions',
		globalParams: {guard: 'site'},
		viewsPath: 'admin.section.permissions.render.users',
		sortField: 'sort'
	}).then(({error, list, changeInputs, create, store, storeWithShow, update, destroy, remove, query, viewsPath}) => {
		
		$('#permissionsCard').card('ready');
		
		if (error) {
			$.notify(error.message, 'error');
			return false;
		} 
		
		$('#permissionAddBtn').ddrInputs('enable');
		
		changeInputs({'[save], [update]': 'enable'});
		
		
		
		$.setSystemPermissionName = (input) => {
			let lavue = $(input).val(),
				permissionSystem = $(input).closest('tr').find('[permissionsystem]'),
				sysVal = translit(lavue, {slug: true, lower: true});
			
			if (sysVal) {
				sysVal = sysVal+':site';
				$(permissionSystem).val(sysVal);
			} else {
				$(permissionSystem).val('');
			}
		}
		
		
		$.permissionAddBtn = () => {
			let permissionAddBtnWait = $(this).ddrWait({
				iconHeight: '26px',
				bgColor: '#ffffff91'
			});
			
			create((data, container, {error}) => {
				permissionAddBtnWait.destroy();
				if (data) $(container).append(data);
				if (error) $.notify(error.message, 'error');
			});
		}
		
		
		$.permissionSave = (btn) => {
			let row = $(btn).closest('tr');
			
			let permissionSaveWait = $(row).ddrWait({
				iconHeight: '26px',
				bgColor: '#ffffffd6'
			});
			
			storeWithShow(row, (data, container, {error}) => {
				if (data) {
					$(row).replaceWith(data);
					$.notify('Запись успешно сохранена!');
				}
				
				if (error) {
					permissionSaveWait.destroy();
					$.notify(error.message, 'error');
				} 
				
				if (error.errors) {
					$.each(error.errors, function(field, errors) {
						$(row).find('[name="'+field+'"]').ddrInputs('error', errors[0]);
					});
				}
			});
		}
		
		
		
		$.permissionUpdate = (btn, id) => {
			let row = $(btn).closest('tr');
			
			update(id, row, (stat, container, {error}) => {
				if (stat) {
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
		
		
		
		$.permissionRemove = (btn, id = null) => {
			let row = $(btn).closest('tr');
			
			if (!id) {
				remove(row);
			} else {
				let removePermissionPopup = ddrPopup({
					width: 400, // ширина окна
					lhtml: 'dialog.delete', // контент
					buttons: ['ui.cancel', {title: 'ui.delete', variant: 'red', action: 'permissionRemoveAction'}],
					centerMode: true,
					winClass: 'ddrpopup_dialog color-red'
				});
				
				removePermissionPopup.then(({close, wait}) => {
					$.permissionRemoveAction = (btn) => {
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
		
	

	});
	
	
	
	
	
</script>