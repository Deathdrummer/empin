<section>
	
	<div teleport="#headerTeleport">
		<x-button
			group="normal"
			variant="purple"
			action="openSetColumsWin"
			title="Отображение столбцов"
			><i class="fa-solid fa-filter"></i> Фильтры</x-button>
	</div>
	
	<x-card id="usersNewCard" loading>
		<div class="table">
			<table class="w100">
				<thead>
					<tr>
						<td class="w40rem"><strong>Имя</strong></td>
						<td class="w40rem"><strong>Должность</strong></td>
						<td></td>
						<td class="w9rem center"><strong>Действия</strong></td>
					</tr>
				</thead>
				<tbody id="usersNewList"></tbody>
				<tfoot>
					<tr>
						<td colspan="9" class="right">
							<x-button id="usersNewAddBtn" variant="blue" group="normal" px="15" disabled>Добавить сотрудника</x-button>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
	</x-card>
</section>




<script type="module">
	
	$.ddrCRUD({
		container: '#usersNewList',
		itemToIndex: 'tr',
		route: 'ajax/users_new',
		viewsPath: 'admin.section.personal.render.users_new',
		//onInit(container) {},
	}).then(({error, list, changeInputs, create, show, store, storeWithShow, update, edit, destroy, remove, query}) => {
		$('#usersNewCard').card('ready');
		
		if (error) {
			$.notify(error.message, 'error');
			return false;
		}  
		
		$('#usersNewAddBtn').ddrInputs('enable');
		
		changeInputs({'[save], [update]': 'enable'});
		
		
		let usersNewPopup, usersNewEditPopup;
		$('#usersNewCard').on(tapEvent, '#usersNewAddBtn', function() {
			let usersNewAddBtnWait = $(this).ddrWait({
				iconHeight: '26px',
				bgColor: '#ffffff91'
			});
			
			create(async (data, container, {error}) => {
				usersNewAddBtnWait.destroy();
				usersNewPopup= await ddrPopup({
					title: 'Новый сотрудник', // заголовок
					width: '700px',
					html: data, // контент
					buttons: ['Отмена', {title: 'Сохранить', variant: 'blue', action: 'usersNewSave', tag: 'save', id: 'AddNewStaff'}],
				});
				
				if (error) $.notify(error.message, 'error');
			});
		});
		
		/*const {
					state,
					popper,
					wait,
					setTitle,
					setButtons,
					loadData,
					setHtml,
					setLHtml,
					dialog,
					close,
					onClose,
					onScroll,
					disableButtons,
					enableButtons,
					setWidth,
				} */
		
		
		$.usersNewSave = (btn) => {
			let formStat = true;
			
			$('#staffForm').find('[required]').each((_, input) => {
				if ($(input).val() == '') {
					$(input).ddrInputs('error', 'Ошибка');
					formStat = false;
				} 
			});
			
			if (!formStat) return false;
			
			usersNewPopup.wait();
			storeWithShow('#staffForm', (data, container, {error}) => {
				if (error) {
					usersNewPopup.wait(false);
					$.notify(error.message, 'error');
				} 
				
				if (error.errors) {
					$.each(error.errors, function(field, errors) {
						$('#staffForm').find('[name="'+field+'"]').ddrInputs('error', errors[0]);
					});
					usersNewPopup.wait(false);
				}
				
				if (data) {
					$('#usersNewList').append(data);
					$.notify('Запись успешно сохранена!');
					usersNewPopup.close();
				}
			});
		}
		
		
		
		$.usersNewDismiss = (btn) => {
			let id = $(btn).attr('remove'),
				row = $(btn).closest('tr');
			
			if (!id) {
				remove(row);
			} else {
				let removeUsersNewPopup = ddrPopup({
					width: 400, // ширина окна
					html: 'Вы действительно хотите уволить сотрудника?', // контент
					buttons: ['ui.cancel', {title: 'Уволить', variant: 'red', action: 'usersNewRemoveAction:'+id}],
					centerMode: true,
					winClass: 'ddrpopup_dialog color-red'
				});
				
				removeUsersNewPopup.then(({close, wait}) => {
					$.usersNewRemoveAction = (btn) => {
						wait();
						destroy(id, function(stat) {
							if (stat) {
								remove(row);
								$.notify('Сотрудник уволен!');
							} else {
								$.notify('Ошибка увольнения сотрудника!', 'error');
							} 
							close();
						});
					}
				});	
			}
		}
		
		
		
		
		$.openUserCard = (selector, userId) => {
			let noClick = event.target.closest('button') || ['P', 'A', 'STRONG', 'SMALL'].includes(event.target.tagName)
			let hasOpened = $('#usersNewCard').hasAttr('cardopened');
			if (noClick || hasOpened) return false;
			$('#usersNewCard').setAttrib('cardopened');
			
			$('#usersNewCard').card('setData', '<div style="height:calc(100vh - 162px);"></div>');
			const {destroyWait} = $('#usersNewCard').card('wait');
			$('#usersNewCard').card('setWidth', '800px', () => {
				
				show(userId, (data, container, {error, status, headers}) => {
					$('#usersNewCard').card('setData', data);
					destroyWait();
				});
				
				$('[cardback]').removeAttrib('hidden');
			}, 200);
		}
		
		
		$.usersNewCardEdit = async (btn) => {
			let userId = $(btn).attr('edit');
			
			usersNewEditPopup = await ddrPopup({
				title: 'Данные сотрудника', // заголовок
				width: '700px',
				//html: data, // контент
				buttons: ['Отмена', {title: 'Обновить', variant: 'blue', action: 'usersNewUpdate', disabled: 1, tag: 'save', id: 'editNewStaff'}],
			});
			
			usersNewEditPopup.wait();
			
			edit(userId, (data, container, {error, status, headers}) => {
				usersNewEditPopup.setHtml(data, () => {
					usersNewEditPopup.wait(false);
					$('#staffForm').changeInputs(() => {
						$('#editNewStaff').ddrInputs('enable');
					});
				});
			});
			
			$.usersNewUpdate = async (btn) => {
				usersNewEditPopup.wait();
				update(userId, '#staffForm', (data, container, {error}) => {
					
					if (error) {
						usersNewSaveWait.destroy();
						$.notify(error.message, 'error');
						usersNewEditPopup.wait(false);
					} 
					
					if (error.errors) {
						$.each(error.errors, function(field, errors) {
							$('#staffForm').find('[name="'+field+'"]').ddrInputs('error', errors[0]);
						});
					}
					
					if (data) {
						$.notify('Запись успешно обновлена!');
						show(userId, (data, container, {error: showError, status: showStatus, headers: showHeaders}) => {
							$('#usersNewCard').card('setData', data);
							usersNewEditPopup.close();
						});
					}
				});
			}
		};
		
		
		
		
		
		
		
		
		
		$.setStaffToUser = async (toggle, userId) => {
			let email,
				toggleStat = $(toggle).is(':checked');
			
			if (toggleStat === false) {
				
				let usersNewCardWait = $('#usersNewCard').ddrWait({
					iconHeight: '26px',
					bgColor: '#ffffff91'
				});
				
				query({
					method: 'delete',
					route: 'reg_staff_to_user',
					data: {user_id: userId},
					responseType: 'json'
				}, (data, container, {error, status, headers}) => {
					if (error) {
						wait(false);
						$.notify(error.message, 'error');
					} 
					
					if (error.errors) {
						$.each(error.errors, function(field, errors) {
							$('#staffForm').find('[name="'+field+'"]').ddrInputs('error', errors[0]);
						});
						wait(false);
					}
					
					if (data) {
						$.notify('Сотрудник успешно аннулирован!');
						show(userId, (data, container, {error: showError, status: showStatus, headers: showHeaders}) => {
							$('#usersNewCard').card('setData', data);
							usersNewCardWait.destroy();
						});
					}
				});
				
				return;
			}
			
			
			const {
				wait,
				setHtml,
				close,
				onCancel,
			} = await ddrPopup({
				title: 'Регистрация сотрудника', // заголовок
				width: '400px',
				//html: data, // контент
				buttons: ['Отмена', {title: 'Зарегистрировать', variant: 'green', action: 'setStaffToUserAction', disabled: 1, id: 'setStaffToUserBtn'}],
			});
			
			onCancel(() => {
				$('#staffToUserToggle').removeAttrib('checked');
			});
			
			
			query({
				method: 'get',
				route: 'reg_staff_to_user',
				data: {user_id: userId},
				responseType: 'text'
			}, (data, container, {error, status, headers}) => {
				if (error) {
					usersNewSaveWait.destroy();
					$.notify(error.message, 'error');
				} 
				
				if (error.errors) {
					$.each(error.errors, function(field, errors) {
						$('#staffForm').find('[name="'+field+'"]').ddrInputs('error', errors[0]);
					});
				}
				
				if (data) {
					const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
					setHtml(data, () => {
						$('#setStaffToUserEmail').on('input', (e) => {
							if (emailRegex.test(e.target.value)) {
						         $('#setStaffToUserBtn').ddrInputs('enable');
						         email = e.target.value;
						    } else {
						         $('#setStaffToUserBtn').ddrInputs('disable');
						    }
						});
					});
				}
			});
			
			
			
			$.setStaffToUserAction = () => {
				wait();
				
				let usersNewCardWait = $('#usersNewCard').ddrWait({
					iconHeight: '26px',
					bgColor: '#ffffff91'
				});
				
				query({
					method: 'post',
					route: 'reg_staff_to_user',
					data: {user_id: userId, email},
					responseType: 'json'
				}, (data, container, {error, status, headers}) => {
					if (error) {
						wait(false);
						$.notify(error.message, 'error');
					} 
					
					if (error.errors) {
						$.each(error.errors, function(field, errors) {
							$('#staffForm').find('[name="'+field+'"]').ddrInputs('error', errors[0]);
						});
						wait(false);
					}
					
					if (data) {
						$.notify('Сотрудник успешно зарегистрирован!');
						show(userId, (data, container, {error: showError, status: showStatus, headers: showHeaders}) => {
							$('#usersNewCard').card('setData', data);
							usersNewCardWait.destroy();
							close();
						});
					}
				});
			}
		}
		
		
		
		
		
		
		$.usersNewEditEmail = async (btn, userId) => {
			let changedEmail;
			const {
				wait,
				setHtml,
				close,
				onCancel,
			} = await ddrPopup({
				title: 'Регистрация сотрудника', // заголовок
				width: '400px',
				//html: data, // контент
				buttons: ['Отмена', {title: 'Зарегистрировать', variant: 'green', action: 'updateUserEmailAction', disabled: 1, id: 'updateUserEmailBtn'}],
			});
			
			wait();
			
			query({
				method: 'get',
				route: 'change_user_email',
				data: {user_id: userId},
				responseType: 'text'
			}, (data, container, {error, status, headers}) => {
				
				const userData = JSON.parse(headers['x-user']);
				
				if (error) {
					usersNewSaveWait.destroy();
					$.notify(error.message, 'error');
				} 
				
				if (error.errors) {
					$.each(error.errors, function(field, errors) {
						$('#staffForm').find('[name="'+field+'"]').ddrInputs('error', errors[0]);
					});
				}
				
				if (data) {
					const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
					setHtml(data, () => {
						$('#updateStaffToUserEmail').on('input', (e) => {
							if (emailRegex.test(e.target.value) && userData?.email != e.target.value) {
						         $('#updateUserEmailBtn').ddrInputs('enable');
						         changedEmail = e.target.value;
						    } else {
						         $('#updateUserEmailBtn').ddrInputs('disable');
						    }
						});
					});
				}
				
				
				$.updateUserEmailAction = (btn) => {
					query({
						method: 'post',
						route: 'change_user_email',
						data: {user_id: userId, email: changedEmail},
					}, (data, container, {error, status, headers}) => {
						if (error) {
							wait(false);
							$.notify(error.message, 'error');
						} 
						
						if (error.errors) {
							$.each(error.errors, function(field, errors) {
								$('#staffForm').find('[name="'+field+'"]').ddrInputs('error', errors[0]);
							});
							wait(false);
						}
						
						if (data) {
							$.notify('E-mail успешно изменен!');
							show(userId, (data, container, {error: showError, status: showStatus, headers: showHeaders}) => {
								$('#usersNewCard').card('setData', data);
								close();
							});
						}
					});
				}
				
			});
		}
		
		
		
		
		
		
		
		$.usersNewSendEmail = (btn, userId) => {
			let usersNewCardWait = $('#usersNewCard').ddrWait({
				iconHeight: '26px',
				bgColor: '#ffffff91'
			});
			
			query({
				method: 'post',
				route: 'send_email',
				data: {user_id: userId},
			}, (data, container, {error, status, headers}) => {
				if (error) {
					wait(false);
					$.notify(error.message, 'error');
				} 
				
				if (error.errors) {
					$.each(error.errors, function(field, errors) {
						$('#staffForm').find('[name="'+field+'"]').ddrInputs('error', errors[0]);
					});
					wait(false);
				}
				
				if (data) {
					$.notify('Письмо сотруднику отправлено!');
					$(btn).removeClass('color-green').addClass('color-gray-600');
					$(btn).setAttrib('title', 'Выслать доступ повторно');
					usersNewCardWait.destroy();
				}
				
			});
		}
		
		
		
		
		
		
		
		
		$.setRoleAction = (select, userId) => {
			const roleId = $(select).val();
			
			$(select).ddrInputs('disable');
			
			query({
				method: 'post',
				route: 'set_role',
				data: {user_id: userId, role_id: roleId},
			}, (data, container, {error, status, headers}) => {
				if (error) {
					wait(false);
					$.notify(error.message, 'error');
					console.log(error.errors);
				} 
				
				if (data) {
					$.notify('Роль успешно присвоена!');
					
				}
				
				$(select).ddrInputs('enable');
			});
		}
		
		
		
		
		$.setDepartmentAction = (select, userId) => {
			const depId = $(select).val() || null;
			
			$(select).ddrInputs('disable');
			
			let usersNewCardWait = $('#usersNewCard').ddrWait({
				iconHeight: '26px',
				bgColor: '#ffffff91'
			});
			
			query({
				method: 'post',
				route: 'set_department',
				data: {user_id: userId, department_id: depId},
			}, (data, container, {error, status, headers}) => {
				if (error) {
					wait(false);
					$.notify(error.message, 'error');
					console.log(error.errors);
				} 
				
				if (data) {
					show(userId, (data, container, {error: showError, status: showStatus, headers: showHeaders}) => {
						$('#usersNewCard').card('setData', data);
						$.notify('Отдел успешно присвоен!');
						usersNewCardWait.destroy();
					});
				}
				
				$(select).ddrInputs('enable');
			});
		}
		
		
		
		
		
		
		
		
		
		$.setRulesAction = async (rowBtn, userId, pseudoname) => {
			let row = $(rowBtn).closest('tr');
			
			const {
				wait,
				setHtml,
				close,
				onCancel,
			} = await ddrPopup({
				title: 'Права доступа <br><span class="fz13px color-gray" style="position: absolute; transform: translateX(-50%);">'+pseudoname+'</span> ', // заголовок,
				url: 'ajax/users_new/permissions',
				params: {row, view: 'admin.section.personal.render.permissions', user: userId, guard: 'site'},
				width: 1000, // ширина окна
				buttons: ['ui.close']
			});
			
			
			$.setPermissionToUser = (btn, userId, permissionId) => {
				let checkStat = $(btn).is(':checked') ? 1 : 0;
				query({
					method: 'put',
					route: 'permissions',
					data: {user: userId, permission: permissionId, stat: checkStat}
				}, (stat, container, {error, status, headers}) => {
					if (error) {
						$.notify(error?.message, 'error');
						return false;
					}
					 
					let select = $(row).find('[name="role"]');
					
					if (!stat?.was_role && !stat?.has_permissions) {
						$(select).ddrInputs('setOptions', {
								title: 'Роль не выбрана',
								defaultSelected: true,
								selected: true,
								disabled: true
							}, 'option[disabled]', 'replace');
					} else {
						let title;
						if ((stat?.was_role && stat?.has_permissions ) || !stat?.was_role && stat?.has_permissions) {
							title = 'Кастомная роль';
						} else if (stat?.was_role && !stat?.has_permissions) {
							title = 'Роль не выбрана';
						}
						
						let optionSelector = $(select).children('option[disabled]').length ? 'option[disabled]' : 'before';
						$(select).ddrInputs('setOptions', {
							title,
							defaultSelected: true,
							selected: true,
							disabled: true
						}, optionSelector, 'replace');
					}
				});
			}
		}
		
		
		
		
		
		
		
		
		$.toDropdownListAction = (btn, staffId, listId) => {
			let method = $(btn).is(':checked') ? 'post' : 'delete';
			$(btn).ddrInputs('disable');
			
			query({
				method: method,
				route: 'list_user',
				data: {staff_id: staffId, list_id: listId}
			}, (stat, container, {error, status, headers}) => {
				if (error) {
					$.notify(error?.message, 'error');
					console.log(error);	
				}
				$(btn).ddrInputs('enable');
			});
		}
		
		
		
		
		
		
		
		
		$.setShowInSelectionAction = (btn, staffId) => {
			$(btn).ddrInputs('disable');
			
			let checkStat = $(btn).is(':checked') ? 1 : 0;
			
			query({
				method: 'put',
				route: 'set_show_in_selection',
				data: {staff_id: staffId, stat: checkStat}
			}, (stat, container, {error, status, headers}) => {
				if (error) {
					$.notify(error?.message, 'error');
					console.log(error);	
				}
				$(btn).ddrInputs('enable');
			});
		}
		
		
		
		
		$.setWorkingAction = (btn, staffId) => {
			$(btn).ddrInputs('disable');
			
			let checkStat = $(btn).is(':checked') ? 0 : 1;
			
			query({
				method: 'put',
				route: 'set_working',
				data: {staff_id: staffId, stat: checkStat}
			}, (stat, container, {error, status, headers}) => {
				if (error) {
					$.notify(error?.message, 'error');
					console.log(error);	
				}
				$(btn).ddrInputs('enable');
			});
		}
		
		
		
		
		
			
		
		
		
		
		
		
		$.closeUserCard = (selector, userId) => {
			$('#usersNewCard').removeAttrib('cardopened');
			const {destroyWait} = $('#usersNewCard').card('wait');
			$('#usersNewCard').card('setWidth', false, async () => {
				$('[cardback]').setAttrib('hidden');
				list({}, (stat, data) => {
					const htmlData = `<div class="table">\
						<table class="w100">\
							<thead>\
								<tr>\
									<td class="w40rem"><strong>Имя</strong></td>\
									<td class="w40rem"><strong>Должность</strong></td>\
									<td></td>\
									<td class="w9rem center"><strong>Действия</strong></td>\
								</tr>\
							</thead>\
							<tbody id="usersNewList">${data}</tbody>\
							<tfoot>\
								<tr>\
									<td colspan="9" class="right">\
										<x-button id="usersNewAddBtn" variant="blue" group="normal" px="15" disabled>Добавить сотрудника</x-button>\
									</td>\
								</tr>\
							</tfoot>\
						</table>\
					</div>`;
					
					$('#usersNewCard').card('setData', htmlData);
					$('#usersNewAddBtn').ddrInputs('enable');
					changeInputs({'[save], [update]': 'enable'});
					destroyWait();
				});
				
				
			}, 200);
		}
		
		
	});
	
	
		
	
	
</script>