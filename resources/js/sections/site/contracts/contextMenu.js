export function contextMenu(
	haSContextMenu,
	selectedContracts,
	removeContractsRows,
	sendMessStat,
	lastChoosedRow,
	canEditCell,
	canCreateCheckbox,
	canRemoveCheckbox,
	getCounts) {
	
	let commentsTooltip, cellEditTooltip;
	$.contractContextMenu = (
		{target, closeOnScroll, onContextMenu, onCloseContextMenu, changeAttrData, buildTitle, setStyle, setCloseSubNavTOut},
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
		canReturnToWork, // вернуть договор в работу из архива
		isPinned // закреплен ли договор
		) => {
		
		
		
		const isCommon = !!$(target.pointer).closest('[ddrtabletd]').hasAttr('commonlist') || false;
		const isDeptCheckbox = !!$(target.pointer).closest('[ddrtabletd]').hasAttr('deptcheck') || false;
		const hasCheckbox = !!$(target.pointer).closest('[ddrtabletd]').children().length;
		const contextEdited = !!$(target.pointer).closest('[ddrtabletd]').hasAttr('contextedit');
		const disableEditCell = !$(target.pointer).closest('[ddrtabletd]').attr('contextedit');
		const selectedTextCell = !!$(target.pointer).closest('[ddrtabletd]').find('[edittedplace]').hasClass('select-text') || !!$(target.pointer).closest('[ddrtabletd]').find('[edittedblock]').length || (!!$('#contractsTable').find('[ddrtabletd].selected').length && $(target.pointer).closest('[ddrtabletd]').hasClass('selected'));
		
		let calcPrices;
		let allPinned;
		let pinnedInSelected = {};
		
		
		
		// Если это оин пункт "копировать"
		if ((selectedTextCell || $(target.pointer).closest('[ddrtabletd]').hasClass('selected')) || !!$(target.pointer).closest('[ddrtabletd]').find('[edittedplace]').hasClass('select-text')) {
			setStyle({
				mainMinHeight: '30px',
			});
		}
		
		
		let enableEditPriceCell = true;
		if ($(target.pointer).closest('[ddrtabletd]').find('[calcprice]').length) {
			const edittedCellData = pregSplit($(target.pointer).closest('[ddrtabletd]').attr('contextedit')),
				[,, subContracting, genContracting] = pregSplit($(target.pointer).closest('[ddrtabletd]').find('[calcprice]').attr('calcprice') || null);
		
			if (['price_gen', 'price_gen_nds'].indexOf(edittedCellData[1]) !== -1 && genContracting) {
				enableEditPriceCell = false;
			} else if (['price_sub', 'price_sub_nds'].indexOf(edittedCellData[1]) !== -1 && subContracting) {
				enableEditPriceCell = false;
			} else if (['price', 'price_nds', 'gen_percent'].indexOf(edittedCellData[1]) === -1 && !subContracting && !genContracting) {
				enableEditPriceCell = false;
			}
		}
		
		
		
		onContextMenu(() => {
			haSContextMenu.value = true;
			
			if (!selectedTextCell) {
				$('#contractsList').find('[editted]').each(function(k, cell) {
					unEditCell(cell);
				});
			}
			
			
			if ($(target.pointer).closest('[ddrtabletd]').hasClass('selected') == false) {
				$('#contractsList').find('[ddrtabletd].selected').removeClass('selected');
				
			}
			
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
			
			if (!selectedTextCell) {
				removeSelection();
				$('#contractsTable').find('[edittedplace].select-text').removeClass('select-text');	
			}
			
			
			// Закрепленные договоры среди выделенных [ID: true/false]
			if ($('#contractsTable').find('[contractselected]').length) {
				$('#contractsTable').find('[contractselected]').each((k, item) => {
					pinnedInSelected[$(item).attr('contractid')] = !!$(item).find('[pinnedicon]').children('i').length;
				});
				
			} else {
				pinnedInSelected[$(target.selector).attr('contractid')] = !!$(target.selector).find('[pinnedicon]').children('i').length;
			}
			
			allPinned = Object.values(pinnedInSelected).every(elem => elem === true) ? 1 : (Object.values(pinnedInSelected).every(elem => elem === false) ? -1 : 0);
			
			console.log('onContextMenu', selectedContracts.items);
			
			if (commentsTooltip?.destroy != undefined) commentsTooltip.destroy();
			if (cellEditTooltip?.destroy != undefined) cellEditTooltip.destroy();
		});
		
		
		onCloseContextMenu(() => {
			haSContextMenu.value = false;
		});
		
		
		const countSelected = selectedContracts.items?.length || 0;
		
		function unEditCell(cell = null) {
			if (_.isNull(cell)) return;
			/*if ($(cell).find('#edittedCellData').tagName()?.toLowerCase() == 'input') {
				$(cell).find('[edittedplace]').number(true, 2, '.', ' ');
				$(cell).find('[hiddenplace]').removeAttrib('hidden');
			}*/
			$(cell).removeClass('editted');
			$(cell).find('[edittedwait]').remove();
			$(cell).find('[edittedplacer]').remove();
			$(cell).find('[edittedblock]').remove();
			$(cell).removeAttrib('editted');
			
			$('#contractsTable').find('[replacer]').setAttrib('replacer', '');
			$('#contractsTable').find('[replacer]:contains("-")').siblings('strong:not(:hidden)').setAttrib('hidden');
			$('#contractsTable').find('[replacer]:empty').siblings('strong:not(:hidden)').setAttrib('hidden');
		} 
		
		
		
		
		closeOnScroll('#contractsList');
		
		
		return [
			{
				name: buildTitle(countSelected, 'Чат договора', 'Cообщение в чаты'),
				countLeft: countSelected > 1 ? countSelected : null,
				countRight: countSelected == 1 ? messagesCount : null,
				countOnArrow: true,
				visible: isCommon && canChat && !selectedTextCell,
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
				visible: isCommon && (canToArchive && !isArchive) && !selectedTextCell,
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
				visible: isCommon && canReturnToWork && isArchive && !selectedTextCell,
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
				visible: isCommon && !selectedTextCell,
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
							async onClick(selector) {
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
								
								const {data, error, status, headers} = await axiosQuery('put', 'site/selections/'+method, params);
								
								if (error) {
									procNotif.error({message: 'Ошибка добавления в подборку!'});
									console.log(error?.message, error.errors);
									return;
								} 
								
								if (data) {
									let contractTitle = countSelected == 1 ? ' '+objectNumber+' '+title : '';
									procNotif.done({message: buildTitle(countSelected, '%'+contractTitle+' успешно добавлен в подборку!', '# % успешно добавлены в подборку!', ['Договор', 'договора', 'договоров'])});
								}
							}
						};
					}
				},
			}, {
				name: 'Удалить из подборки',
				visible: isCommon && selectionId && !selectedTextCell,
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
				visible: isCommon && !selectedTextCell,
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
				enabled: countSelected > 1 || (!!hasDepsToSend && ((canSending && departmentId) || (canSendingAll && !departmentId))),
				hidden: isArchive || !isCommon || selectedTextCell,
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
				visible: isCommon && canHiding && departmentId && !isArchive && !selectedTextCell,
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
				visible: isDeptCheckbox && !isArchive && ((!hasCheckbox && canCreateCheckbox) || (hasCheckbox && canRemoveCheckbox)) && !selectedTextCell,
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
				visible: hasCheckbox && isDeptCheckbox && !selectedTextCell,
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
				visible: countSelected == 1 && isCommon && canEditCell && contextEdited && !selectedTextCell,/* && !isArchive*/ // добавить !isArchive - если не нужно редактировать в архиве 
				disabled: ($(target.pointer).closest('[ddrtabletd]').hasAttr('editted') || disableEditCell) || !enableEditPriceCell,
				sort: 7,
				async onClick() {	
					const cell = $(target.pointer).closest('[ddrtabletd]');
					const attrData = $(cell).attr('contextedit');
					const [contractId = null, column = null, type = null] = pregSplit(attrData);
					
					
					$('#contractsList').find('[editted]').each(function(k, cell) {
						unEditCell(cell);
					});
					
					
					
					$('.app').on(tapEvent+'.unEditCell', function(e) {
						if ($(e.target).closest('[ddrtabletd]').hasAttr('editted') && [3,4].indexOf(type) === -1) return;
						unEditCell(cell);
						$('.app').off('.unEditCell');
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
						
						if (error) {
							$.notify('Ошибка редактирования ячейки!', 'error');
							cellWait.destroy();
							console.log(error?.message, error.errors);
							return;
						}
						
						const percentNds = headers['price_nds'] || 0;
						
						$(cell).append(data);
						
						
						
						$(cell).find('#edittedCellData').focus();
						
						const edittedBlock = $(cell).find('#edittedCellData');
						
						edittedBlock[0].selectionStart = edittedBlock[0].selectionEnd = edittedBlock[0].value.length;
						
						if (type == 2) {
							$(cell).find('#edittedCellData').number(true, 2, '.', ' ');
							
							//------------------------------------------------------------- Калькулятор
							const primarySelector = $(cell).find('#edittedCellData'),
								row = $(primarySelector).closest('[ddrtabletr]'),
								[, contractingPercent, subContracting, genContracting] = pregSplit($(cell).find('[calcprice]').attr('calcprice'));
							
							const _setValueToSelector = (field = null, value = '') => {
								if (_.isNull(field)) return false;
								const replacer = $(row).find('[calcprice^="'+field+'|"]');
								$(replacer).setAttrib('replacer', value);
								$(replacer).siblings('strong:hidden').removeAttrib('hidden');
							}
							
							
							if (!subContracting && !genContracting) {
								if (column == 'price') {
									calcPrices = $(primarySelector).ddrCalc([{
										selector: value => _setValueToSelector('price_nds', value),
										method: 'nds',
										numberFormat: [2, '.', ' '],
										percent: percentNds,
									}]);
									
								} else if (column == 'price_nds') {
									calcPrices = $(primarySelector).ddrCalc([{
										selector: value => _setValueToSelector('price', value),
										method: 'nds',
										reverse: true,
										numberFormat: [2, '.', ' '],
										percent: percentNds,
									}]);
								}
								
							} else if (subContracting) {
								if (column == 'price') {
									calcPrices = $(primarySelector).ddrCalc([{
										selector: value => _setValueToSelector('price_nds', value),
										method: 'nds',
										numberFormat: [2, '.', ' '],
										percent: percentNds,
									}, {
										selector: value => _setValueToSelector('price_gen', value),
										method: 'percent',
										numberFormat: [2, '.', ' '],
										percent: contractingPercent,
									}, {
										selector: value => _setValueToSelector('price_gen_nds', value),
										method: 'percent',
										percent: contractingPercent,
										numberFormat: [2, '.', ' '],
										middleware: [(value, calc) => calc('nds', value, percentNds), false],
									}]);
									
								} else if (column == 'price_nds') {
									calcPrices = $(primarySelector).ddrCalc([{
										selector: value => _setValueToSelector('price', value),
										method: 'nds',
										reverse: true,
										numberFormat: [2, '.', ' '],
										percent: percentNds,
									}, {
										selector: value => _setValueToSelector('price_gen', value),
										method: 'percent',
										numberFormat: [2, '.', ' '],
										percent: contractingPercent,
										middleware: [(value, calc) => calc('nds', value, percentNds, true), false],
									}, {
										selector: value => _setValueToSelector('price_gen_nds', value),
										method: 'percent',
										percent: contractingPercent,
										numberFormat: [2, '.', ' '],
									}]);
									
								} else if (column == 'price_gen') {
									calcPrices = $(primarySelector).ddrCalc([{
										selector: value => _setValueToSelector('price', value),
										method: 'percent',
										reverse: true,
										numberFormat: [2, '.', ' '],
										percent: contractingPercent,
									}, {
										selector: value => _setValueToSelector('price_nds', value),
										method: 'percent',
										reverse: true,
										numberFormat: [2, '.', ' '],
										percent: contractingPercent,
										middleware: [(value, calc) => calc('nds', value, percentNds), false],
									}, {
										selector: value => _setValueToSelector('price_gen_nds', value),
										method: 'nds',
										percent: percentNds,
										numberFormat: [2, '.', ' '],
									}]);
									
								} else if (column == 'price_gen_nds') {
									calcPrices = $(primarySelector).ddrCalc([{
										selector: value => _setValueToSelector('price', value),
										method: 'percent',
										reverse: true,
										numberFormat: [2, '.', ' '],
										percent: contractingPercent,
										middleware: [(value, calc) => calc('nds', value, percentNds, true), false],
									}, {
										selector: value => _setValueToSelector('price_nds', value),
										method: 'percent',
										reverse: true,
										numberFormat: [2, '.', ' '],
										percent: contractingPercent,
									}, {
										selector: value => _setValueToSelector('price_gen', value),
										method: 'nds',
										reverse: true,
										percent: percentNds,
										numberFormat: [2, '.', ' '],
									}]);
								}
								
							} else if (genContracting) {
								if (column == 'price') {
									calcPrices = $(primarySelector).ddrCalc([{
										selector: value => _setValueToSelector('price_nds', value),
										method: 'nds',
										numberFormat: [2, '.', ' '],
										percent: percentNds,
									}, {
										selector: value => _setValueToSelector('price_sub', value),
										method: 'percent',
										reverse: true,
										numberFormat: [2, '.', ' '],
										percent: contractingPercent,
									}, {
										selector: value => _setValueToSelector('price_sub_nds', value),
										method: 'percent',
										reverse: true,
										percent: contractingPercent,
										numberFormat: [2, '.', ' '],
										middleware: [(value, calc) => calc('nds', value, percentNds), false],
									}]);
									
								} else if (column == 'price_nds') {
									calcPrices = $(primarySelector).ddrCalc([{
										selector: value => _setValueToSelector('price', value),
										method: 'nds',
										reverse: true,
										numberFormat: [2, '.', ' '],
										percent: percentNds,
									}, {
										selector: value => _setValueToSelector('price_sub', value),
										method: 'percent',
										reverse: true,
										numberFormat: [2, '.', ' '],
										percent: contractingPercent,
										middleware: [(value, calc) => calc('nds', value, percentNds, true), false],
									}, {
										selector: value => _setValueToSelector('price_sub_nds', value),
										method: 'percent',
										reverse: true,
										percent: contractingPercent,
										numberFormat: [2, '.', ' '],
									}]);
									
								} else if (column == 'price_sub') {
									calcPrices = $(primarySelector).ddrCalc([{
										selector: value => _setValueToSelector('price', value),
										method: 'percent',
										numberFormat: [2, '.', ' '],
										percent: contractingPercent,
									}, {
										selector: value => _setValueToSelector('price_nds', value),
										method: 'percent',
										numberFormat: [2, '.', ' '],
										percent: contractingPercent,
										middleware: [(value, calc) => calc('nds', value, percentNds), false],
									}, {
										selector: value => _setValueToSelector('price_sub_nds', value),
										method: 'nds',
										percent: percentNds,
										numberFormat: [2, '.', ' '],
									}]);
									
								} else if (column == 'price_sub_nds') {
									calcPrices = $(primarySelector).ddrCalc([{
										selector: value => _setValueToSelector('price', value),
										method: 'percent',
										numberFormat: [2, '.', ' '],
										percent: contractingPercent,
										middleware: [(value, calc) => calc('nds', value, percentNds, true), false],
									}, {
										selector: value => _setValueToSelector('price_nds', value),
										method: 'percent',
										numberFormat: [2, '.', ' '],
										percent: contractingPercent,
									}, {
										selector: value => _setValueToSelector('price_sub', value),
										method: 'nds',
										reverse: true,
										percent: percentNds,
										numberFormat: [2, '.', ' '],
									}]);
								}
							}
						} 
						
						
						
						//------------------------------------------------------------- Сохранить по Enter
						$(cell).find('#edittedCellData').on('keypress.saveedittedcell', (e) => {
							if (e.keyCode == 13 && !e.shiftKey) {
								$(cell).find('[savecelldata]').trigger(tapEvent); 
								$(cell).find('#edittedCellData').off('.saveedittedcell');
							}
						});
						
						
						$(cell).off('.savecelldata');
						
						//------------------------------------------------------------- Сохранить
						$(cell).one(tapEvent+'.savecelldata', '[savecelldata]', async function() {
							const row = $(cell).closest('[ddrtabletr]'),
								prices = $(row).find('[calcprice]:not([replacer=""])'),
								addictColums = {};
							
							if (prices.length) {
								$.each(prices, (k, pr) => {
									let cp = pregSplit($(pr).attr('calcprice'));
									addictColums[cp[0]] = Number($(pr).attr('replacer').replaceAll(' ', ''));
									$(pr).siblings('strong:hidden').removeAttrib('hidden');
								});
								
								$.each(addictColums, (col, price) => {
									$(row).find('[calcprice^="'+col+'|"]').html($.number(price, 2, '.', ' '));
								});
							}
							
							
							$(this).hide();
							
							cellWait.on();
							
							const priceRegex = new RegExp('price\_?');
							const cellVal = $(cell).find('#edittedCellData').val();
							
							const cellData = priceRegex.test(column) ? Number(cellVal) : cellVal;
							const emptyVal = $(cell).find('[edittedplace]').attr('edittedplace');
							
							const {data, error, status, headers} = await axiosQuery('post', 'site/contracts/cell_edit', {
								contract_id: contractId, 
								column,
								type,
								data: cellData,
								addict_colums: addictColums,
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
								if (type == 2) {
									$(cell).find('[edittedplace]').number(true, 2, '.', ' ');
									$(cell).find('[edittedplace]').siblings('strong:hidden').removeAttrib('hidden');
								}
								unEditCell(cell);
								if (calcPrices?.destroy != undefined) calcPrices.destroy();
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
							minHeight: type == 3 ? '176px' : '50px',
							duration: [200, 200],
							trigger: 'click',
							wait: {
								iconHeight: '40px'
							},
							onShow: async function({reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps}) {
								
								if (type == 3) {
									const calendarBlock = 	'<div ondblclick="event.stopPropagation();">'+
																'<div><div id="editCellCalendar"></div></div>'+
																'<div class="mt5px text-end">'+
																	'<div class="button verysmall-button button-light">'+
																		'<button title="Очистить" id="editCellCalendarClear">Очистить</button>'+
																	'</div>'+
																'</div>'+
															'</div>';
															
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
												addict_colums: {},
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
									
									
									
									$('#editCellCalendarClear').one(tapEvent, async function() {
										const cellDateWait = $(reference).ddrWait({
											iconHeight: '30px',
											tag: 'noscroll noopen edittedwait'
										});
										
										const emptyVal = $(cell).find('[edittedplace]').attr('edittedplace');
										
										const {data, error} = await axiosQuery('post', 'site/contracts/cell_edit', {
											contract_id: contractId,
											column,
											type,
											data: null,
											addict_colums: {},
										}, 'json');
										
										if (error) {
											cellDateWait.off();
											$.notify('Ошибка сохранения ячейки!', 'error');
											console.log(error?.message, error.errors);
										}
										
										if (data) {
											$.notify('Сохранено!');
											$(cell).find('[edittedplace]').removeAttrib('date');
											$(cell).find('[edittedplace]').text(emptyVal);
											cellDateWait.destroy();
											unEditCell(cell);
											cellEditTooltip?.destroy();
										}
									});
									
									
									
									$(datePicker.el).siblings('.qs-datepicker-container').addClass('qs-datepicker-container-noshadow qs-datepicker-container-relative ');
									
								} else {
									const {data, error, status, headers} = await axiosQuery('get', 'site/contracts/cell_edit', {
										contract_id: contractId, 
										column,
										type,
										addict_colums: {},
									}, 'json');
									
									await setData(data);
								}
								
								waitDetroy();
								
								$('#contractsList').one('scroll', function() {
									// При скролле списка скрыть тултип комментариев
									if (cellEditTooltip?.destroy != undefined) cellEditTooltip.destroy();
								});
								
								
								$(popper).find('[edittedlistvalue]').one(tapEvent, async function() {
									cellWait.on();
									
									let value = $(this).attr('edittedlistvalue');
									const emptyVal = $(cell).find('[edittedplace]').attr('edittedplace');
									const {data: savedRes, error: savedErr} = await axiosQuery('post', 'site/contracts/cell_edit', {
										contract_id: contractId,
										column,
										type,
										data: value,
										addict_colums: {},
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
			}, {
				name: 'Экспорт в Excel',
				visible: countSelected && !selectedTextCell,
				countLeft: countSelected > 1 ? countSelected : null,
				sort: 8,
				async onClick() {
					let contractsIds = selectedContracts.items;
					
					const {
						state, // isClosed
						popper,
						wait,
						setTitle,
						setButtons,
						loadData,
						setHtml,
						setLHtml,
						dialog,
						close,
						query,
						onScroll,
						disableButtons,
						enableButtons,
						setWidth
					} = await ddrPopup({
						title: 'Экспорт данных в Excel', // заголовок
						width: 400, // ширина окна
						buttons: ['ui.cancel', {title: 'Экспорт', variant: 'blue', action: 'exportContractsData', id: 'exportContractsData'}], // массив кнопок
					});
					
					wait();
					
					const {data, error, status, headers} = await axiosQuery('get', 'site/contracts/to_export');
					
					
					if (error) {
						$.notify('Ошибка! Не удалось открыть окно настроек экспорта!', 'error');
						console.log(error?.message, error.errors);
					}
					
					await setHtml(data);
					
					wait(false);
					
					
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
							$('#exportContractsData').ddrInputs('enable');
						} else {
							$('#exportContractsData').ddrInputs('disable');
						}
					}
					
					
					
					
					$.exportContractsData = async () => {
						wait();
						const colums = [];
						$(popper).find('[columtoxeport]:checked').each((k, item) => {
							let field = $(item).attr('columtoxeport');
							colums.push(field);
						});
						
						const sort = ddrStore('site-contracts-sortfield') || 'id',
							order =  ddrStore('site-contracts-sortorder') || 'ASC';
						
						const {data, error, status, headers} = await axiosQuery('post', 'site/contracts/to_export', {
							contracts_ids: contractsIds,
							colums,
							sort,
							order,
						}, 'blob');
						
						
						if (headers['content-type'] != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
							$.notify('Ошибка экспорта данных', 'error');
							wait(false);
							return;
						}
						
						const d = ddrDateBuilder();
						
						exportFile({data, headers, filename: 'Договоры_'+d.day.zero+'.'+d.month.zero+'.'+d.year.full}, () => {
							close();
						});
					}
				}
			}, {
				name: 'Копировать',
				visible: selectedTextCell,
				disabled: !!$(target.pointer).closest('[ddrtabletd]').find('[edittedblock]').length && !getSelectionStr(),
				sort: 1,
				async onClick() {
					if (getSelectionStr()) {
						copyStringToClipboard(getSelectionStr());
						$.notify('Скопировано!', {autoHideDelay: 2000});
						
					} else {
						let row = null, allData = '';
						$('#contractsTable').find('[ddrtabletd][copied]').each((k, item) => {
							if (k == 0) row = $(item).closest('[ddrtabletr]')[0];
							
							if (k > 0 && row !== $(item).closest('[ddrtabletr]')[0]) {
								row = $(item).closest('[ddrtabletr]')[0];
								allData += "\n";
							} else if (k > 0) {
								allData += "\t";
							}
							
							allData += $(item).find('[edittedplace]').text();
						});
						
						let copiedData = allData.trim();
			
						if (copiedData) {
							copyStringToClipboard(copiedData);
							$.notify('Скопировано!', {autoHideDelay: 2000});
						}
					}
					
					//$('#contractsTable').find('[edittedplace].select-text').removeClass('select-text');	
					//$('#contractsList').find('[ddrtabletd].selected').removeClass('selected');
				}
			}, {
				name() {
					if (countSelected == 1) return isPinned ? 'Открепить договор' : 'Закрепить договор';
					return [-1, 0].indexOf(allPinned) !== -1 ? 'Закрепить договоры' : 'Открепить договоры';
				},
				countLeft: countSelected > 1 ? countSelected : null,
				//visible: countSelected,
				//hidden: countSelected > 1,
				sort: 3,
				async onClick() {
					let titlePin = isPinned ? 'Открепление' : 'Закрепление';
					let titlePinDone = isPinned ? 'открепления ' : 'закрепления ';
					
					let pinProcNotif = processNotify(buildTitle(countSelected, titlePin+' # %...', ['договора', 'договоров', 'договоров']));
					
					const {data, error, status, headers} = await axiosQuery('put', 'site/contracts/pin', {contracts_ids: pinnedInSelected, stat: allPinned}, 'json')
					
					if (error) {
						//$.notify('Ошибка закрепления договора!', 'error');
						console.log(error?.message, error.errors);
						pinProcNotif.error({message: 'Ошибка '+titlePinDone+buildTitle(countSelected, titlePin+' # %...', ['договора', 'договоров', 'договоров'])});
					}
					
					if (data) {
						const pinnedIconHtml = '<i '+
								'class="fz10px fa-solid fa-thumbtack fa-rotate-by color-gray-600" '+
								'style="--fa-rotate-angle: -40deg;" '+
								'noscroll '+
								'title="Закрепить договор"> '+
							'</i>';
						
						if (countSelected > 1) {
							$.each(Object.keys(pinnedInSelected), (k, item) => {
								let contractRow = $('#contractsTable').find('[contractid="'+item+'"]');
								
								if (isPinned) $(contractRow).find('[pinnedicon]').empty();
								else $(contractRow).find('[pinnedicon]').html(pinnedIconHtml);
								
								
								changeAttrData(contractRow, 18, isPinned == 1 ? '0' : '1');
							});
							
						} else {
							if (isPinned) $(target.selector).find('[pinnedicon]').empty();
							else $(target.selector).find('[pinnedicon]').html(pinnedIconHtml);
							changeAttrData(18, isPinned == 1 ? '0' : '1');
						}
						
						pinProcNotif.done({message: 'Готово!'});		
					}
				}
			}, {
				name: 'Выделить цветом',
				countLeft: countSelected > 1 ? countSelected : null,
				sort: 4,
				load: {
					url: 'site/contracts/colorselections',
					method: 'get',
					map: (item) => {
						return {
							name: item.name,
							//faIcon: 'fa-solid fa-angles-right',
							visible: true,
							async onClick(selector) {
								const {data, error, status, headers} = await axiosQuery('post', 'site/contracts/colorselections', {contractIds: selectedContracts.items, colorId: item.id}, 'json');
								
								if (error) {
									$.notify('Ошибка применеия цвета!', 'error');
									console.log(error?.message, error.errors);
								}
								
								if (data) {
									if (countSelected > 1) {
										$.each(selectedContracts.items, (k, item) => {
											$('#contractsTable').find('[contractid="'+item+'"]').find('[ddrtabletd][commonlist]').css('background-color', data.color || '');;
										});
										$.notify('Цвет выделенных договоров успешно применен!');
									
									} else {
										$(target.selector).find('[ddrtabletd][commonlist]').css('background-color', data.color || '');
										$.notify('Цвет договора успешно применен!');
									}	
								}
							}
						};
					}
				},
			},
		];
		
	}
}