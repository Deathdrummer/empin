export function contextMenu(
	haSContextMenu,
	selectedContracts,
	removeContractsRows,
	sendMessStat,
	lastChoosedRow,
	canEditCell,
	canCreateCheckbox,
	canRemoveCheckbox) {
	
	let commentsTooltip, cellEditTooltip;
	$.contractContextMenu = (
		{target, closeOnScroll, onContextMenu, changeAttrData, buildTitle},
		contractId,
		departmentId,
		selectionId,
		objectNumber,
		title,
		
		hasDepsToSend,
		messagesCount,
		searched,
		selectionEdited,
		isArchive,
		
		canToArchive, // отправка договора в архив
		canSending, // отправка договора в другой отдел из отдела
		canSendingAll, // отправка договора в другой отдел из общего списка
		canHiding, // скрыть договор
		canChat, // просмотр чата
		canChatSending, // возможность отправлять сообщения в чате
		canReturnToWork // вернуть договор в работу из архива
		) => {
		
		
		const isCommon = !!$(target.pointer).closest('[ddrtabletd]').hasAttr('commonlist') || false;
		const isDeptCheckbox = !!$(target.pointer).closest('[ddrtabletd]').hasAttr('deptcheck') || false;
		const hasCheckbox = !!$(target.pointer).closest('[ddrtabletd]').children().length;
		const contextEdited = !!$(target.pointer).closest('[ddrtabletd]').hasAttr('contextedit');
		const disableEditCell = !$(target.pointer).closest('[ddrtabletd]').attr('contextedit');
		
		onContextMenu(() => {
			haSContextMenu.value = true;
			
			$('#contractsList').find('[editted]').each(function(k, cell) {
				unEditCell(cell);
			});
			
			// если кликнуть на НЕвыделенном договоре - то все выделенния отменятся и выделится текущий кликнутый договор
			if (isCommon && $(target.selector).hasAttr('contractselected') == false) {
				$('#contractsTable').find('[contractselected]').removeClass('ddrtable__tr-selected').removeAttrib('contractselected');
				lastChoosedRow.value = target.selector;
				//$(target.selector).addClass('ddrtable__tr-selected').setAttrib('contractselected');
				selectedContracts.add($(target.selector).attr('contractid'));
			}
			
			// Если клик НЕ на таблице общего перечня
			if (!isCommon) {
				$('#contractsTable').find('[contractselected]').removeClass('ddrtable__tr-selected').removeAttrib('contractselected');
				// lastChoosedRow.value = target.selector;
				// $(target.selector).addClass('ddrtable__tr-selected').setAttrib('contractselected');
				//selectedContracts.add($(target.selector).attr('contractid'));
				
			}
			console.log('onContextMenu', selectedContracts.items);
			
			if (commentsTooltip?.destroy != undefined) commentsTooltip.destroy();
			if (cellEditTooltip?.destroy != undefined) cellEditTooltip.destroy();
		});
		
		
		const countSelected = selectedContracts.items?.length || 0;
		
		function unEditCell(cell = null) {
			if (_.isNull(cell)) return;
			if ($(cell).find('#edittedCellData').tagName() == 'input') {
				$(cell).find('[edittedplace]').number(true, 2, '.', ' ');
				$(cell).find('[hiddenplace]').removeAttrib('hidden');
			}
			$(cell).removeClass('editted');
			$(cell).find('[edittedwait]').remove();
			$(cell).find('[edittedplacer]').remove();
			$(cell).find('[edittedblock]').remove();
			$(cell).removeAttrib('editted');
		} 
		
		
		
		
		closeOnScroll('#contractsList');
		
		
		return [
			{
				name: buildTitle(countSelected, 'Чат договора', 'Cообщение в чаты'),
				countLeft: countSelected > 1 ? countSelected : null,
				countRight: countSelected == 1 ? messagesCount : null,
				countOnArrow: true,
				visible: isCommon && canChat,
				sort: 1,
				onClick() {
					if (countSelected == 1) { // Если выделен 1 договор
						ddrPopup({
							title: '<small class="fz12px color-gray">Чат договора:</small> «'+title+'»',
							width: 800,
							buttons: ['Закрыть'],
							winClass: 'ddrpopup_chat'
						}).then(({state/* isClosed */, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => {
							wait();
							
							axiosQuery('get', 'site/contracts/chat', {contract_id: contractId}).then(({data, error, status, headers}) => {
								
								if (error) {
									$.notify('Не удалось загрузить чат!', 'error');
									console.log(error?.message, error?.errors);
									return;
								}
								
								setHtml(data, () => {
									sendMessStat.value = false;
									wait(false);
									
									$('.chat__message').tripleTap((elem) => {
										selectText(elem);
									});
									
									let chatVisibleHeight = $('#chatMessageList').outerHeight(),
										chatScrollHeight = $('#chatMessageList')[0].scrollHeight;
									$('#chatMessageList').scrollTop(chatScrollHeight - chatVisibleHeight);
									
									$('#chatMessageBlock').focus();
									
									$('#chatMessageList').find('.chat__post').mouseup(function (e) {
										const selObj = window.getSelection();
										const selectString =  selObj.toString();
										if (selectString.length) {
											copyStringToClipboard(selObj.toString());
											$.notify('Скопировано!');
										}
									});
															
									$('#chatMessageBlock').ddrInputs('change', () => {
										let mess = getContenteditable('#chatMessageBlock');
										
										if (mess && !sendMessStat.value) {
											sendMessStat.value = true;
											$('#chatSendMesageBtn').ddrInputs('enable');
										} else if (!mess && sendMessStat.value) {
											sendMessStat.value = false;
											$('#chatSendMesageBtn').ddrInputs('disable');
										}
									});
								});
								
							}).catch((e) => {
								console.log(e);
							});
							
						});
					
					} else { // Если выделено более 1 договора - отправить сообщение в чаты с выделеными договорами
						
						let html = '<p class="d-block mb5px fz14px color-darkgray">Сообщение:</p>' +
									'<div class="textarea normal-textarea w100" id="sendMessagesToManyContractsField">' +
										'<textarea name="" rows="10" class="w100"></textarea>' +
									'</div>';
						
						ddrPopup({
							title: 'Отправить сообщение в выбранные договоры',
							width: 500,
							html,
							buttons: ['Закрыть', {title: 'Отправить', variant: 'blue', action: 'sendMessagesToManyContracts', disabled: 1, id: 'sendMessagesToManyContractsBtn'}],
							winClass: 'ddrpopup_chat'
						}).then(({state/* isClosed */, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onClose, onScroll, disableButtons, enableButtons, setWidth}) => {							
							let isEmpty = true;	
							
							$('#sendMessagesToManyContractsField').find('textarea').focus();
							
							$('#sendMessagesToManyContractsField').ddrInputs('change', (textarea) => {
								if ($(textarea).val() && isEmpty) {
									$('#sendMessagesToManyContractsBtn').ddrInputs('enable');
									isEmpty = false;
								} else if (!$(textarea).val() && !isEmpty) {
									$('#sendMessagesToManyContractsBtn').ddrInputs('disable');
									isEmpty = true;
								}
							});
							
							
							$.sendMessagesToManyContracts = () => {
								wait();
								$('#sendMessagesToManyContractsBtn').ddrInputs('disable');
								
								let message = $('#sendMessagesToManyContractsField').find('textarea').val();
								let sendMessAbortCtrl = new AbortController();
								
								axiosQuery('put', 'site/contracts/chats', {contractIds: selectedContracts.items, message}, 'json', sendMessAbortCtrl)
								.then(({data, error, status, headers}) => {
									if (error) {
										$.notify('Ошибка отправки сообщения!', 'error');
										return;
									}
									
									if (data) {
										if (data == -1) {
											$.notify('Сообщение не было разослано!', 'info');
										} else {
											$.notify('Сообщение успешно отправлено во все чаты выбранных договоров!');
											close();
										}
										
									} else {
										$.notify('Не удалось отправить сообщение в чаты выбранных договоров!', 'error');
										wait(false);
									}
									
								}).catch((e) => {
									console.log(e);
								});
								
								
								onClose(() => {
									sendMessAbortCtrl.abort();
								});
							}
						});
					}
				}
			}, {
				name: 'Отправить в архив',
				visible: isCommon && (canToArchive && !isArchive),
				countLeft: countSelected > 1 ? countSelected : null,
				sort: 5,
				onClick: () => {
					let html = '';
					html += '<div>';
					if (countSelected == 1) {
						html += '<p class="fz14px color-darkgray text-start">Номер объекта: <span class="color-black">'+objectNumber+'</span></p>';
						html += '<p class="fz14px color-darkgray text-start">Название/заявитель: <span class="color-black">'+title+'</span></p>';
						html += '<p class="fz18px color-red mt15px">Вы действительно хотите отправить договор в архив?</p>';
					} else if (countSelected > 1) {
						html += '<p class="fz18px color-red mt15px">'+buildTitle(countSelected, 'Вы действительно хотите отправить # % в архив?', ['договор', 'договора', 'договоров'])+'</p>';
					}
					html += '</div>';
					
					ddrPopup({
						width: 400, // ширина окна
						html, // контент
						buttons: ['ui.cancel', {title: 'Отправить', variant: 'red', action: 'contractToArchiveAction'}],
						centerMode: true,
						winClass: 'ddrpopup_dialog'
					}).then(({close, wait}) => {
						$.contractToArchiveAction = (_) => {
							wait();
							axiosQuery('post', 'site/contracts/to_archive', {contractIds: selectedContracts.items}, 'json')
							.then(({data, error, status, headers}) => {
								let contractTitle = countSelected == 1 ? ' '+objectNumber+' '+title : '';
								if (data) {
									if (selectionId || searched) {
										getCounts(() => {
											removeContractsRows(target);
										});
									} else {
										removeContractsRows(target);
									}
									
									$.notify(buildTitle(countSelected, '%'+contractTitle+' успешно отправлен в архив!', '# % успешно отправлены в архив!', ['Договор', 'договора', 'договоров']));
								} else {
									$.notify(buildTitle(countSelected, 'Ошибка! %'+contractTitle+' не был отправлен в архив!', 'Ошибка! % не были отправлены в архив!', ['договор', 'договора', 'договоров']), 'error');
								}
								close();
							});
						}
					});
				},
			}, {
				name: 'Вернуть в работу',
				visible: isCommon && canReturnToWork && isArchive,
				countLeft: countSelected > 1 ? countSelected : null,
				sort: 5,
				onClick() {
					ddrPopup({
						width: 400, // ширина окна
						html: '<p class="fz18px color-green">'+buildTitle(countSelected, 'Вы действительно хотите вернуть # % в работу?', ['договор', 'договора', 'договоров'])+'</p>', // контент
						buttons: ['ui.cancel', {title: 'Вернуть', variant: 'blue', action: 'returnContractToWorkBtn'}],
						centerMode: true,
						winClass: 'ddrpopup_dialog'
					}).then(({close, wait}) => {
						$.returnContractToWorkBtn = (_) => {
							wait();
							axiosQuery('post', 'site/contracts/to_work', {contractIds: selectedContracts.items}, 'json')
							.then(({data, error, status, headers}) => {
								let contractTitle = countSelected == 1 ? ' '+objectNumber+' '+title : '';
								if (data) {
									if (selectionId || searched) {
										getCounts(() => {
											removeContractsRows(target);
										});
									} else {
										removeContractsRows(target);
									}
									
									$.notify(buildTitle(countSelected, '%'+contractTitle+' успешно возвращен в работу!', '# % успешно возвращены в работу!', ['Договор', 'договора', 'договоров']));
									//target.changeAttrData(15, '0');
								} else {
									$.notify(buildTitle(countSelected, 'Ошибка! %'+contractTitle+' не был возвращен в работу!', 'Ошибка! # % не были возвращены в работу!', ['Договор', 'договора', 'договоров']), 'error');
								}
								close();
							});
						}
					});
				}
			}, {
				name: 'Добавить в подборку',
				//hidden: selectionId,
				visible: isCommon,
				countLeft: countSelected > 1 ? countSelected : null,
				sort: 2,
				load: {
					url: 'site/contracts/selections_to_choose',
					params: {contractIds: selectedContracts.items},
					method: 'get',
					map: (item) => {
						return {
							name: item.title,
							//faIcon: 'fa-solid fa-clipboard-check',
							disabled: !!item.choosed,
							onClick(selector) {
								let selectionId = item.id;
								
								let procNotif = processNotify(buildTitle(countSelected, 'Добавление # % в подборку...', ['договора', 'договоров', 'договоров']));
								
								let contractIds = selectedContracts.items;
								
								let params, method;
								
								if (contractIds.length == 1) {
									params = {contractId: contractIds[0], selectionId};
									method = 'add_contract';
								} else {
									params = {contractIds, selectionId};
									method = 'add_contracts';
								}
								
								axiosQuery('put', 'site/selections/'+method, params)
								.then(({data, error, status, headers}) => {
									if (error) {
										procNotif.error({message: 'Ошибка добавления в подборку!'});
										console.log(error?.message, error.errors);
									} else {
										let contractTitle = countSelected == 1 ? ' '+objectNumber+' '+title : '';
										procNotif.done({message: buildTitle(countSelected, '%'+contractTitle+' успешно добавлен в подборку!', '# % успешно добавлены в подборку!', ['Договор', 'договора', 'договоров'])});
									}
								});
							}
						};
					}
				},
			}, {
				name: 'Удалить из подборки',
				visible: isCommon && selectionId,
				countLeft: countSelected > 1 ? countSelected : null,
				sort: 4,
				onClick() {		
					let procNotif = processNotify(buildTitle(countSelected, 'Удаление # % из подборки...', ['договора', 'договоров', 'договоров']));
					
					let contractIds = selectedContracts.items;
					
					let params, method;
					
					if (contractIds.length == 1) {
						params = {contractId: contractIds[0], selectionId};
						method = 'remove_contract';
					} else {
						params = {contractIds, selectionId};
						method = 'remove_contracts';
					}
					
					axiosQuery('put', 'site/selections/'+method, params)
					.then(({data, error, status, headers}) => {
						let contractTitle = countSelected == 1 ? ' '+objectNumber+' '+title : '';
						if (error) {
							//$.notify('Ошибка удаления из подборки!', 'error');
							procNotif.error({message: 'Ошибка удаления договора'+contractTitle+' из подборки!'});
							console.log(error?.message, error.errors);
						} else {
							
							procNotif.done({message: buildTitle(countSelected, '%'+contractTitle+' успешно удален из подборки!', '# % успешно удалены из подборки!', ['Договор', 'договора', 'договоров'])});
							//target.changeAttrData(7, '0');
							
							getCounts(() => {
								removeContractsRows(target);
								$('[selectionsbtn]').ddrInputs('enable');
							});
						}
					});
				}
			}, {
				name: 'Создать новую подборку',
				sort: 3,
				visible: isCommon,
				countLeft: countSelected > 1 ? countSelected : null,
				onClick() {
					let html = 	'<p class="d-block mb5px fz14px color-darkgray">Название подборки:</p>' +
								'<div class="input normal-input normal-input-text w100">' +
									'<input type="text" value="" id="selectionNameInput" placeholder="Введите текст" autocomplete="off" inpgroup="normal">' +
									'<div class="normal-input__errorlabel noselect" errorlabel=""></div>' +
								'</div>';
					
					
					ddrPopup({
						title: 'Создать подборку из выбранных договоров',
						width: 500,
						html,
						buttons: ['Закрыть', {title: 'Создать', variant: 'blue', action: 'createNewSelection', disabled: 1, id: 'createNewSelectionBtn'}],
						winClass: 'ddrpopup_chat'
					}).then(({state/* isClosed */, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onClose, onScroll, disableButtons, enableButtons, setWidth}) => {							
						
						let isEmpty = true;	
						$('#selectionNameInput').ddrInputs('change', (input) => {
							if ($(input).val() && isEmpty) {
								$('#createNewSelectionBtn').ddrInputs('enable');
								isEmpty = false;
							} else if (!$(input).val() && !isEmpty) {
								$('#createNewSelectionBtn').ddrInputs('disable');
								isEmpty = true;
							}
						});
						
						
						$.createNewSelection = () => {
							wait();
							
							let title = $('#selectionNameInput').val();
							let newSelectionAbortCtrl = new AbortController();
							
							axiosQuery('post', 'site/selections/add_selection_from_contextmenu', {title, contractIds: selectedContracts.items}, 'json', newSelectionAbortCtrl)
							.then(({data, error, status, headers}) => {
								if (error) {
									$.notify('Ошибка создания подборки!', 'error');
									return;
								}
								
								if (data) {
									if (data == -1) {
										$.notify('Подборка не была создана!', 'info');
										wait(false);
									} else {
										$.notify(buildTitle(countSelected, 'Поодборка с # % была успешна создана!', ['договором', 'договорами', 'договорами']));
										close();
									}
									
								} else {
									$.notify('Не удалось создать подборку!', 'error');
									wait(false);
								}
								
							}).catch((e) => {
								console.log(e);
							});
							
							onClose(() => {
								newSelectionAbortCtrl.abort();
							});
						}
						
					});
				}
			}, {
				name: 'Отправить в другой отдел',
				enabled: selectedContracts.items.length > 1 || (!!hasDepsToSend && ((canSending && departmentId) || (canSendingAll && !departmentId))),
				hidden: isArchive || !isCommon,
				countLeft: countSelected > 1 ? countSelected : null,
				sort: 4,
				load: {
					url: 'site/contracts/departments',
					params: {contractId: selectedContracts.items.length == 1 ? selectedContracts.items[0] : null},
					method: 'get',
					map: (item) => {
						return {
							name: item.name,
							//faIcon: 'fa-solid fa-angles-right',
							visible: true,
							onClick(selector) {
								let departmentName = selector.text(),
									itemsCount = selector.items().length;
								
								let procNotif = processNotify('Отправка договора в другой отдел...');
								
								axiosQuery('post', 'site/contracts/send', {contractIds: selectedContracts.items, departmentId: item.id}, 'json')
								.then(({data, error, status, headers}) => {
									if (data) {
										//$.notify('Договор успешно отправлен в '+departmentName+'!');
										
										if (selectionId || searched) {
											let params = {};
											getCounts(() => {
												//if (currentList > 0) removeContractsRows(target);
											});
										} else {
											//if (currentList > 0) removeContractsRows(target);
										}
										
										if (data.length) {
											let contractTitle = countSelected == 1 ? ' '+objectNumber+' '+title : '';
											let mess = buildTitle(data.length, '# %'+contractTitle+' успешно отправлен в '+departmentName+'!', '# % успешно отправлены в '+departmentName+'!', ['договор', 'договора', 'договоров']);
											procNotif.done({message: mess});
										} else {
											procNotif.error({message: 'Ни один договор не был отправлен!'});
										} 
										
										if (countSelected == 1 && itemsCount == 0) changeAttrData(6, '0');
									} else {
										//$.notify('Ошибка! Договор не был отправлен!', 'error');
										procNotif.error({message: 'Ошибка! Договор не был отправлен!'});
									}
								});
							}
						};
					}
				},
			}, {
				name: 'Скрыть',
				visible: isCommon && canHiding && departmentId && !isArchive,
				countLeft: countSelected > 1 ? countSelected : null,
				sort: 6,
				onClick() {	
					ddrPopup({
						width: 400, // ширина окна
						html: buildTitle(countSelected, '<p class="fz18px color-red">Вы действительно хотите скрыть # %?</p>', ['договор', 'договора', 'договоров']), // контент
						buttons: ['ui.cancel', {title: 'Скрыть', variant: 'red', action: 'contractHide'}],
						centerMode: true,
						winClass: 'ddrpopup_dialog'
					}).then(({close, wait}) => {
						$.contractHide = (_) => {
							wait();
							axiosQuery('post', 'site/contracts/hide', {contractIds: selectedContracts.items, departmentId}, 'json')
							.then(({data, error, status, headers}) => {
								let contractTitle = countSelected == 1 ? ' '+objectNumber+' '+title : '';
								if (data) {
									if (selectionId || searched) {
										getCounts(() => {
											removeContractsRows(target);
										});
									} else {
										removeContractsRows(target);
									}
									
									$.notify(buildTitle(countSelected, '%'+contractTitle+' успешно скрыт!', '# % успешно скрыты!', ['Договор', 'договора', 'договоров']));
									//target.changeAttrData(9, '0');
								} else {
									$.notify(buildTitle(countSelected, 'Ошибка! договор'+contractTitle+' не был скрыт!', 'Ошибка! договоры не были скрыты!'), 'error');
								}
								close();
							});
						}
					});
				}
			}, {
				name: hasCheckbox && canRemoveCheckbox ? 'Удалить чекбокс' : (!hasCheckbox && canCreateCheckbox ? 'Добавить чекбокс' : ''),
				visible: isDeptCheckbox && !isArchive && ((!hasCheckbox && canCreateCheckbox) || (hasCheckbox && canRemoveCheckbox)),
				sort: 1,
				async onClick() {	
					const cell = $(target.pointer).closest('[ddrtabletd]');
					const edited = !!$(cell).attr('edited');
					const attrData = $(cell).attr('deptcheck');
					const [contractId = null, departmentId = null, stepId = null] = pregSplit(attrData);
					
					const waitCell = $(cell).ddrWait({
						iconHeight: '30px',
						bgColor: '#efe9f9',
					});
					
					const {data, error, status, headers} = await axiosQuery('post', 'site/contracts/step_checkbox', {
						contractId, 
						departmentId,
						stepId,
						value: hasCheckbox
					}, 'json');
					
					
					if (error) {
						console.log(error);
						$.notify('Ошибка! Не удалось '+(hasCheckbox ? 'удалить' : 'добавить')+' чекбокс!', 'error');
						waitCell.destroy();
						return;
					}
					
					// canCreateCheckbox canRemoveCheckbox
					if (data) {
						if (!hasCheckbox) {
							if (edited) {
								const randId = generateCode('nnnnnnn');
								const editedCheckbox = '<div class="checkbox normal-checkbox">' +
									'<input type="checkbox" name="assigned_primary" id="checkbox'+randId+'" inpgroup="normal" oninput="$.contractSetData(this, '+contractId+','+departmentId+','+stepId+',1)">' +
									
										'<label class="noselect" for="checkbox'+randId+'"></label>' +
									
										'<label for="checkbox'+randId+'" class="checkbox__label lh90 d-inline-block normal-checkbox__label noselect"></label>' +
										'<div class="normal-checkbox__errorlabel" errorlabel=""></div>' +
									'</div>';
								$(cell).html(editedCheckbox);
							} else {
								$(cell).html('<div class="checkbox-empty checkbox-empty-normal border-gray-400"></div>');
							}
							$.notify('Чекбокс успешно добавлен!');
						} else {
							$(cell).empty();
							$.notify('Чекбокс успешно удален!');
						}
						waitCell.destroy();
					}
				}
			}, {
				name: 'Комментарии',
				visible: hasCheckbox && isDeptCheckbox,
				sort: 2,
				async onClick() {
					const cell = $(target.pointer).closest('[ddrtabletd]');
					const attrData = $(cell).attr('deptcheck');
					const [contractId = null, departmentId = null, stepId = null] = pregSplit(attrData);
					
					
					commentsTooltip = $(cell).ddrTooltip({
						//cls: 'w44rem',
						placement: 'bottom',
						tag: 'noscroll noopen',
						offset: [0 -5],
						minWidth: '200px',
						minHeight: '200px',
						duration: [200, 200],
						trigger: 'click',
						wait: {
							iconHeight: '40px'
						},
						onShow: async function({reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps}) {
							
							const {data, error, status, headers} = await axiosQuery('get', 'site/contracts/cell_comment', {
								contract_id: contractId, 
								department_id: departmentId,
								step_id: stepId,
							}, 'json');
							
							
							await setData(data);
							
							waitDetroy();
							
							const textarea = $(popper).find('#sendCellComment');
							
							$(textarea).focus();
							
							textarea[0].selectionStart = textarea[0].selectionEnd = textarea[0].value.length;
							
							$('#contractsList').one('scroll', function() {
								// При скролле списка скрыть тултип комментариев
								if (commentsTooltip?.destroy != undefined) commentsTooltip.destroy();
							});
							
							
							let inputCellCommentTOut;
							$(textarea).on('input', function() {
								clearTimeout(inputCellCommentTOut);
								inputCellCommentTOut = setTimeout(async () => {
									const comment = $(this).val();
									const {data: postRes, error: postErr, status, headers} = await axiosQuery('post', 'site/contracts/cell_comment', {
										contract_id: contractId, 
										department_id: departmentId,
										step_id: stepId,
										comment,
									}, 'json');
									
									if (postErr) {
										console.log(postErr);
										$.notify('Ошибка! Не удалось задать комментарий!', 'error');
										return;
									}
									
									if (postRes) {
										if (comment) $(reference).append('<div class="trangled trangled-top-right"></div>');
										else $(reference).find('.trangled').remove();
										
										//$.notify('Комментарий успешно сохранен!');
										//$(this).ddrInputs('change');
									}
									
								}, 500);
							});
						},
						onDestroy: function() {
							//$(cell).removeAttrib('tooltiped');
						}
					});
				}
			}, {
				name: 'Редактировать',
				visible: countSelected == 1 && isCommon && canEditCell && contextEdited/* && !isArchive*/, // добавить !isArchive - если не нужно редактировать в архиве 
				disabled: $(target.pointer).closest('[ddrtabletd]').hasAttr('editted') || disableEditCell,
				sort: 7,
				async onClick() {	
					const cell = $(target.pointer).closest('[ddrtabletd]');
					const attrData = $(cell).attr('contextedit');
					const [contractId = null, column = null, type = null] = pregSplit(attrData);
					
					
					$('#contractsList').find('[editted]').each(function(k, cell) {
						unEditCell(cell);
					});
					
					
					$('#contractsList').on(tapEvent+'.unEditCell', function(e) {
						if ($(e.target).closest('[ddrtabletd]').hasAttr('editted') && [3,4].indexOf(type) === -1) return;
						unEditCell(cell);
						$('#contractsList').off('.unEditCell');
					});
					
					
					$(cell).setAttrib('editted');
					
					const cellWait = $(cell).ddrWait({
						iconHeight: '30px',
						tag: 'noscroll noopen edittedwait'
					});
					
					
					
					if ([1,2].indexOf(type) !== -1) { // текст
						const {data, error, status, headers} = await axiosQuery('get', 'site/contracts/cell_edit', {
							contract_id: contractId, 
							column,
							type,
						});
						
						$(cell).append(data);
						
						if (type == 2) $(cell).find('#edittedCellData').number(true, 2, '.', ' ');
						
						$(cell).find('#edittedCellData').focus();
						
						$(cell).find('#edittedCellData').on('keypress', (e) => {
							if (e.keyCode == 13 && !e.shiftKey) {
								$(cell).find('[savecelldata]').trigger(tapEvent); 
							}
						});
						
						
						
						$(cell).one(tapEvent, '[savecelldata]', async function() {
							
							$(this).hide();
							
							cellWait.on();
						
							const cellData = $(cell).find('#edittedCellData').val();
							const emptyVal = $(cell).find('[edittedplace]').attr('edittedplace');
							
							const {data, error, status, headers} = await axiosQuery('post', 'site/contracts/cell_edit', {
								contract_id: contractId, 
								column,
								type,
								data: cellData,
							}, 'json');
							
							
							if (error) {
								cellWait.off();
								$.notify('Ошибка сохранения ячейки!', 'error');
								console.log(error?.message, error.errors);
							}
							
							if (data) {
								$.notify('Сохранено!');
								$(cell).find('[edittedplace]').text(cellData || emptyVal);
								cellWait.destroy();
								unEditCell(cell);
							}
						});
						
						
											
						
					} else if([3,4].indexOf(type) !== -1) { // 3 - дата 4 - вып. список
						$(cell).addClass('editted');
						
						cellEditTooltip = $(cell).ddrTooltip({
							//cls: 'w44rem',
							placement: 'bottom',
							tag: 'noscroll noopen nouneditted',
							offset: [0 -5],
							minWidth: type == 3 ? '202px' : '50px',
							minHeight: type == 3 ? '170px' : '50px',
							duration: [200, 200],
							trigger: 'click',
							wait: {
								iconHeight: '40px'
							},
							onShow: async function({reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps}) {
								
								if (type == 3) {
									const calendarBlock = '<div onclick="event.stopPropagation();" ondblclick="event.stopPropagation();" id="editCellCalendar"></div>';
									await setData(calendarBlock);
									
									const currentDate = $(cell).find('[edittedplace]').attr('date') || false;
									
									const datePicker = ddrDatepicker($(popper).find('#editCellCalendar')[0], {
										startDay: 1,
										defaultView: 'calendar',
										overlayPlaceholder: 'Введите год',
										customDays: ['пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс'],
										customMonths: ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'],
										alwaysShow: true,
										dateSelected: currentDate ? new Date(currentDate) : new Date(),
										onSelect: async ({el, destroy}, date) => {
											const rawDate = date.getFullYear()+'-'+addZero(date.getMonth() + 1)+'-'+addZero(date.getDate())+' 00:00:00';
											const toCellText = addZero(date.getDate())+'.'+addZero(date.getMonth() + 1)+'.'+date.getFullYear().toString().substr(-2);
											
											const emptyVal = $(cell).find('[edittedplace]').attr('edittedplace');
											
											const cellDateWait = $(reference).ddrWait({
												iconHeight: '30px',
												tag: 'noscroll noopen edittedwait'
											});
											
											
											
											const {data, error} = await axiosQuery('post', 'site/contracts/cell_edit', {
												contract_id: contractId,
												column,
												type,
												data: rawDate,
											}, 'json');
											
											if (error) {
												cellDateWait.off();
												$.notify('Ошибка сохранения ячейки!', 'error');
												console.log(error?.message, error.errors);
											}
											
											if (data) {
												$.notify('Сохранено!');
												$(cell).find('[edittedplace]').setAttrib('date', rawDate);
												$(cell).find('[edittedplace]').text(toCellText || emptyVal);
												cellDateWait.destroy();
												unEditCell(cell);
												cellEditTooltip?.destroy();
											}
										},
									});
									
									$(datePicker.el).siblings('.qs-datepicker-container').addClass('qs-datepicker-container-noshadow');
									
								} else {
									const {data, error, status, headers} = await axiosQuery('get', 'site/contracts/cell_edit', {
										contract_id: contractId, 
										column,
										type,
									}, 'json');
									
									await setData(data);
								}
								
								waitDetroy();
								
								$('#contractsList').one('scroll', function() {
									// При скролле списка скрыть тултип комментариев
									if (cellEditTooltip?.destroy != undefined) cellEditTooltip.destroy();
								});
								
								
								$(popper).find('[edittedlistvalue]').on(tapEvent, async function() {
									cellWait.on();
									
									let value = $(this).attr('edittedlistvalue');
									const emptyVal = $(cell).find('[edittedplace]').attr('edittedplace');
									const {data: savedRes, error: savedErr} = await axiosQuery('post', 'site/contracts/cell_edit', {
										contract_id: contractId,
										column,
										type,
										data: value,
									}, 'json');
									
									if (savedErr) {
										cellWait.off();
										$.notify('Ошибка сохранения ячейки!', 'error');
										console.log(savedErr?.message, savedErr.errors);
									}
									
									if (savedRes) {
										$.notify('Сохранено!');
										$(cell).find('[edittedplace]').text(savedRes || emptyVal);
										cellWait.destroy();
										unEditCell(cell);
										cellEditTooltip?.destroy();
									}
								});
								
							},
							onDestroy: function() {
								$(cell).removeAttrib('tooltiped');
							}
						});
						
					}
					
					
					
					cellWait.off();	
				}
			}
		];
		
	}
}