export function selectionsList(selection, editSelection, _clearCounts, getList, canEditSelection) {
	ddrPopup({
		title: 'Подборки договоров',
		width: 1000,
		buttons: ['Закрыть', {action: 'selectionAdd', title: 'Создать подборку'}],
		disabledButtons: true,
		winClass: 'ddrpopup_white'
	}).then(async ({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, query, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
		wait();
		const viewsPath = 'site.section.contracts.render.selections';
		
		let init = await axiosQuery('get', 'site/selections/init', {views: viewsPath});
		
		if (init.error) {
			console.log(init.error);
			wait(false);
			return false;
		}
		
		setHtml(init.data, () => {
			$('#selectionsList').ddrWait({
				iconHeight: '26px',
				bgColor: '#ffffffd6'
			});
			
			$.ddrCRUD({
				container: '#selectionsList',
				itemToIndex: '[ddrtabletr]',
				route: 'site/selections',
				//params: {
				//	list: {department_id: deptId},
				//	store: {department_id: deptId},
				//},
				viewsPath,
			}).then(({error, list, changeInputs, create, store, storeWithShow, update, destroy, remove, query, getParams}) => {
				
				$('#selectionsTable').blockTable('buildTable');
				
				$("#selectionsList").sortable({
					axis: 'y',
					placeholder: 'sortable-placeholder h6rem',
					classes: {
						'ui-sortable-placeholder': 'ddrtable__tr',
					},
					async stop(event, ui) {
						const sortedData = {};
						$("#selectionsList").find('[selection]').each((k, item) => {
							let id = $(item).attr('selection');
							sortedData[id] = k+1;
						});
						
						const {data, error, status, headers} = await axiosQuery('put', 'site/selections/sort', {items: sortedData});
					},
					//cancel: "[nohandle]"
					handle: '[handle]'
				});
				
				
				
				wait(false);
				enableButtons(true);
				changeInputs({'[save], [update]': 'enable'});
				
				$('#selectionsList').find('[ddrtabletr][archive="1"]').setAttrib('hidden');
				
				$.changeSelectionsList = (btn, isActive, id) => {
					if (id == 'archive') {
						$('#selectionsList').find('[ddrtabletr][archive]:not([hidden])').setAttrib('hidden');
						$('#selectionsList').find('[ddrtabletr][archive="1"]').removeAttrib('hidden');
					} else if (id == 'active') {
						$('#selectionsList').find('[ddrtabletr][archive]:not([hidden])').setAttrib('hidden');
						$('#selectionsList').find('[ddrtabletr][archive="0"]').removeAttrib('hidden');
					}
				}
				
				
				
				$.selectionAdd = (btn) => {
					let selectionAddBtnWait = $(btn).ddrWait({
						iconHeight: '18px',
						bgColor: '#ffffff91'
					});
					
					create((data, container, {error}) => {
						selectionAddBtnWait.destroy();
						if (error) $.notify(error.message, 'error');
						if (data) $(container).append(data);
						$('#selectionsTable').blockTable('buildTable');
					});
				}
				
				
				
				$.selectionSave = (btn) => {
					let row = $(btn).closest('[ddrtabletr]');
				
					let selectionSaveWait = $(row).ddrWait({
						iconHeight: '26px',
						bgColor: '#ffffffd6'
					});
					
					storeWithShow(row, (data, container, {error}) => {
						if (data) {
							$(row).replaceWith(data);
							$('#selectionsTable').blockTable('buildTable');
							$.notify('Запись успешно сохранена!');
						}
						
						if (error) {
							selectionSaveWait.destroy();
							$.notify(error.message, 'error');
						} 
						
						if (error.errors) {
							$.each(error.errors, function(field, errors) {
								$(row).find('[name="'+field+'"]').ddrInputs('error', errors[0]);
							});
						}
					});
				}
				
				
				
				
				
				$.selectionUpdate = (btn, id) => {
					let row = $(btn).closest('[ddrtabletr]');
					
					let updateSelectionWait = $(row).ddrWait({
						iconHeight: '15px',
						bgColor: '#ffffff91'
					});
				
					update(id, row, (data, container, {error}) => {
						if (data) {
							$(row).find('[update]').ddrInputs('disable');
							$(row).find('input, select, textarea').ddrInputs('state', 'clear');
							$.notify('Запись успешно обновлена!');
						}
						
						if (error) $.notify(error.message, 'error');
						
						if (error.errors) {
							$.each(error.errors, function(field, errors) {
								$(row).find('[name="'+field+'"]').ddrInputs('error', errors[0]);
							});
						}
						
						updateSelectionWait.destroy();
					});
				}
				
				
				
				$.selectionRemove = (btn, id) => {
					let row = $(btn).closest('[ddrtabletr]');
					
					if (!id) {
						remove(row);
					} else {
						dialog('Удалить подборку?', {
							buttons: {
								'Удалить|red': function({closeDialog}) {
									closeDialog();
									
									let removeSelectionWait = $(row).ddrWait({
										iconHeight: '15px',
										bgColor: '#ffffff91'
									});
									
									destroy(id, function(stat) {
										if (stat) {
											remove(row);
											$.notify('Запись успешно удалена!');
										} else {
											$.notify('Ошибка удаления записи!', 'error');
										} 
										
										removeSelectionWait.destroy();
									});
								},
								'Отмена|light': function({closeDialog}) {
									closeDialog();
								}
							}
						});
					}
				}
				
				
				
				$.selectionUnsubscribe = (btn, id) => {
					if (!id) {
						$.notify('Ошибка! Не удалось отписаться!', 'error');
						return;
					}
					let row = $(btn).closest('[ddrtabletr]');
					
					dialog('Отписаться от подборки?', {
						buttons: {
							'Отписаться|red': function({closeDialog}) {
								closeDialog();
								
								let unsubscribeSelectionWait = $(row).ddrWait({
									iconHeight: '15px',
									bgColor: '#ffffff91'
								});
								
								query({
									method: 'post',
									route: 'unsubscribe',
									data: {id}, // это ID записи
									responseType: 'json'
								}, (data, container, {error, status, headers}) => {
									if (!error) {
										remove(row);
										$.notify('Отписка успешно выполнена!');
									} else {
										$.notify('Ошибка отписки от подборки!', 'error');
									}
									unsubscribeSelectionWait.destroy();
								});
							},
							'Отмена|light': function({closeDialog}) {
								closeDialog();
							}
						}
					});
				}
				
				
				
				
				
				//--------------------------------- Отправить сообщение в чаты с договорами данной подборки
				$.selectionSendMessages = (sendMessDialogBtn, selectionId) => {
					$(sendMessDialogBtn).ddrInputs('disable');
					
					let sendMessAbortCtrl = new AbortController();
					
					let html = '<strong class="d-block mb5px">Сообщение:</strong>\
								<div class="textarea normal-textarea w40rem" id="sendMessDialogField">\
									<textarea name="" rows="5" class="w100"></textarea>\
								</div>'
					
					dialog(html, {
						buttons: {
							'Отправить': ({closeDialog}) => {
								let sendMessDialogWait = $('#sendMessDialogField').ddrWait();
								$('#ddrpopupDialogBtn0').ddrInputs('disable');
								
								let message = $('#sendMessDialogField').find('textarea').val();
								
								axiosQuery('put', 'site/contracts/chats', {selectionId, message}, 'json', sendMessAbortCtrl)
									.then(({data, error, status, headers}) => {
										if (error) {
											$.notify('Ошибка отправки сообщений!', 'error');
											return;
										}
										
										if (data) {
											if (data == -1) {
												$.notify('Подборка не содержит в себе ни одного договора! Сообщение не было разослано!', 'info');
											} else {
												$.notify('Сообщение успешно отправлено во все чаты договоров!');
											}
											
											closeDialog();
											$(sendMessDialogBtn).ddrInputs('enable');
										
										} else {
											$.notify('Не удалось отправить сообщение в чаты договоров!', 'error');
											sendMessDialogWait.destroy();
										}
										
									}).catch((e) => {
										console.log(e);
									});
							},
							'Отмена|red': ({closeDialog}) => {
								$(sendMessDialogBtn).ddrInputs('enable');
								closeDialog();
								sendMessAbortCtrl.abort();
							}
						},
						callback() {
							$('#ddrpopupDialogBtn0').ddrInputs('disable');
							
							let isEmpty = true;	
							$('#sendMessDialogField').ddrInputs('change', (textarea) => {
								if ($(textarea).val() && isEmpty) {
									$('#ddrpopupDialogBtn0').ddrInputs('enable');
									isEmpty = false;
								} else if (!$(textarea).val() && !isEmpty) {
									$('#ddrpopupDialogBtn0').ddrInputs('disable');
									isEmpty = true;
								}
							});
						}
					});
					
				}
				
				
				
				
				
				
				//--------------------------------- Поделиться подборкой с другими сотрудниками
				let statusesTooltip, destroyTooltip, sharePopper;
				$.selectionShare = (btn, selection_id, subscribed = false) => {
					
					statusesTooltip = $(btn).ddrTooltip({
						cls: 'w30rem',
						placement: 'left-start',
						offset: [-5, 5],
						tag: 'noscroll',
						minWidth: '360px',
						minHeight: '200px',
						maxHeight: '400px',
						duration: [200, 200],
						trigger: 'click',
						wait: {
							iconHeight: '40px'
						},
						onShow: function({reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps}) {
							sharePopper = popper;
							destroyTooltip = destroy;
							
							query({method: 'get', route: 'users_to_share', data: {views: viewsPath, selection_id, subscribed}, responseType: 'text'}, (data, container, {error, status, headers}) => {
								if (error) {
									$.notify(error?.message, 'error');
									console.log(error?.errors);
								}
								setData(data);
								waitDetroy();
							});
						}
					});
					
					// clone-user subscribe-user clone-user-department subscribe-user-department
					// тип ID подборки ID отдела или участника
					$.shareSelection = (type, selectionId, unitId, permission) => {
						//destroyTooltip();
						
						let shareSelectionWait = $(sharePopper).ddrWait({
							iconHeight: '25px'
						});
						
						query({
							method: 'post',
							route: 'share',
							data: {
								type,
								unitId,
								selectionId,
								permission
							}
						}, (data, container, {error, status, headers}) => {
							if (error) {
								$.notify('Ошибка отправки подборки!', 'error');
								shareSelectionWait.destroy();
								return;
							}
							
							if (['clone-user', 'clone-user-department'].indexOf(type) !== -1) {
								$.notify('Подборка успешно отправлена!');
							} else {
								$.notify('Подписка успешно оформлена!');
							}
							
							shareSelectionWait.destroy();
						});
					}
					
				}
				
				
				
				
				
				
				//--------------------------------- Действия со списками
				
				$.selectionBuildList = (btn, id, canEdit = 0) => {
					$('[selectionsbtn]').ddrInputs('disable');
					close();
					selection.value = id;
					canEditSelection.value = canEdit;
					editSelection = null;
					
					let selectionTitle = $(btn).closest('[ddrtabletr]').find('input[name="title"]').val() || $(btn).closest('[ddrtabletr]').find('p').text();
					
					_clearCounts();
					getList({
						//canEditSelection: canEdit,
						withCounts: true,
						callback: function() {
							$('#currentSelectionTitle').text(selectionTitle);
							$('[selectionsbtn]').ddrInputs('enable');
							$('#currentSelection').removeAttrib('hidden');
						}
					});
				}
				
				
				$.selectionBuildToEdit = (btn, id) => {
					$('[selectionsbtn]').ddrInputs('disable');
					close();
					selection.value = id;
					editSelection = true;
					
					let selectionTitle = $(btn).closest('[ddrtabletr]').find('input[name="title"]').val();
					
					_clearCounts();
					getList({
						withCounts: true,
						callback: function() {
							$('#currentSelectionTitle').text(selectionTitle);
							$('[selectionsbtn]').ddrInputs('enable');
							$('#currentSelection').removeAttrib('hidden');
						}
					});
				}
				
				
				
				
				
				
				$.selectionExport = async (btn, selectionId) => {
					let row = $(btn).closest('[ddrtabletr]');
					
					$(btn).setAttrib('disabled');
				
					let selectionExportBtnWait = $(btn).ddrWait({
						iconHeight: '16px',
						bgColor: '#d2fafb99'
					});
					
					const winHeight = $('[ddrpopupdata]').outerHeight() - 100;
					
					const {data, error, status, headers} = await axiosQuery('get', 'site/contracts/to_export', {
						height: (winHeight < 300 ? 300 : winHeight)+'px',
					});
					
					
					const exportDialogHtml = '<div class="w38rem text-start">'+
						'<h6 class="fz16px color-blue mb1rem text-center" style="color:#487c91;">Экспорт данных в Excel</h6>'+
						data+
					'</div>';
					
					dialog(exportDialogHtml, {
						buttons: {
							'Отмена|light': function({closeDialog}) {
								closeDialog();
								selectionExportBtnWait.destroy();
							},
							'Экспорт|blue': async function({closeDialog}) {
								let selectionExportWinWait = $('[ddrpopupdialogwin]').ddrWait({
									iconHeight: '25px',
									//bgColor: '#d2fafb99'
								});
								
								
								const colums = [];
								$('[ddrpopupdialogwin]').find('[columtoxeport]:checked').each((k, item) => {
									let field = $(item).attr('columtoxeport');
									colums.push(field);
								});
								
								const sort = ddrStore('site-contracts-sortfield') || 'id',
									order =  ddrStore('site-contracts-sortorder') || 'ASC';
								
								const {data, error, status, headers} = await axiosQuery('post', 'site/contracts/to_export', {
									selection_id: selectionId,
									colums,
									sort,
									order
								}, 'blob');
								
								
								if (headers['content-type'] != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
									$.notify('Ошибка экспорта данных', 'error');
									selectionExportWinWait.off();
									return;
								}
								
								const d = ddrDateBuilder();
								
								exportFile({data, headers, filename: 'Договоры_подборки_'+d.day.zero+'.'+d.month.zero+'.'+d.year.full}, () => {
									closeDialog();
									selectionExportBtnWait.destroy();
								});
							},
						}
					}, () => {
						
						let selectAllChecksStat = false;
						$.selectAllChecks = () => {
							$('#excelColumsList').find('[columtoxeport]').ddrInputs('checked', !selectAllChecksStat ? true : false);
							selectAllChecksStat = !selectAllChecksStat;
							checkCountChecked();
						};
						
						
						$('#excelColumsList').find('[columtoxeport]').on('change', function() {
							checkCountChecked()
						});
						
						
						function checkCountChecked() {
							let countChecked = $('#excelColumsList').find('[columtoxeport]:checked').length;
							if (countChecked) {
								$('#ddrpopupDialogBtn1').ddrInputs('enable');
							} else {
								$('#ddrpopupDialogBtn1').ddrInputs('disable');
							}
						}
					});
				}
				
				
				
				
				
				
				$.selectionToArchive = (btn, id) => {
					let selectionToArchiveBtn = $(btn).ddrWait({
						iconHeight: '15px',
						bgColor: '#ffffff91'
					});
					
					$(btn).ddrInputs('disable');
					
					
					const isArchive = $(btn).closest('[ddrtabletr]').attr('archive') == 1;
					const command = isArchive ? 'from' : 'to';
					const mess = isArchive ? 'Вернуть подборку в активные?' : 'Отправить подборку в архив?';
					const actionBtn = isArchive ? 'Вернуть' : 'Отправить';
					const success = isArchive ? 'Подборка успешно возвращена в активные!' : 'Подборка успешно отправлена в архив!';
					
					dialog(mess, {
						buttons: {
							[actionBtn]: async ({closeDialog}) => {
								const {data, error, status, headers, abort} = await axiosQuery('put', 'site/selections/archive', {id, command}, 'json');
					
								if (error) {
									$.notify('Ошибка отправки подборки в архив!', 'error');
									console.log(error?.message, error?.errors);
								} 
								
								if (data) {
									$(btn).closest('[ddrtabletr]').setAttrib('hidden');
									$(btn).closest('[ddrtabletr]').attr('archive', isArchive ? '0' : '1');
									
									if (isArchive) {
										$(btn).parent('.button').removeClass('button-light').addClass('button-purple');
										$(btn).html('<i class="fa-solid fa-fw fa-box-archive"></i>');
									} else {
										$(btn).parent('.button').removeClass('button-purple').addClass('button-light');
										$(btn).html('<i class="fa-solid fa-fw fa-arrow-rotate-left"></i>');
									}
									
									closeDialog();
									$.notify(success);
									selectionToArchiveBtn.destroy();
									$(btn).ddrInputs('enable');
								}
							},
							'Отмена|red': ({closeDialog}) => {
								selectionToArchiveBtn.destroy();
								$(btn).ddrInputs('enable');
								closeDialog();
							} 
					}});
				}
				
			});
		});
	});
}