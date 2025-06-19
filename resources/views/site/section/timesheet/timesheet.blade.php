<section>
	<div id="ddrSwiper" class="ddrswiper" noselect></div>
</section>





<script type="module">
	
	$('#ddrSwiper').ddrSwiper({
		centerSlide: 'center',
		slidesPerView: 5,
		slidesCount: 21,
		loadNewOffset: 10,
		slideBlank: `<div class="card minh50rem h100 w100 rounded-5rem" placer></div>`,
		//template: 'timesheet.slide',
		render: {
			template: 'timesheet.slide',
			actions: {
			    setBtnStat: [
			    	{
			        	event: 'input',
			        	action: ddrDebounceWrap((selector, {root, args, e, map}) => {
			        		let message = getContenteditable(selector);
			        		if (message) $(selector).siblings('button').removeAttrib('disabled');
			        		else $(selector).siblings('button').setAttrib('disabled');
			        	}, 200, {leading: false, trailing: true, maxWait: 1000})
			    	},
			    ],
			    'setToEnter:keydown': (selector, {root, args, e, map}) => {
			    	if (e.keyCode === 13 && e.shiftKey == false) {
			    		e.preventDefault();
			    		$.teamAddComment($(selector).siblings('button')[0], ...args);
			    	}
			    }
			    //otherAction: (el, root, ...args) => { ... }
			},
		},
		loadSlidesData: async function(indexes, abortCtrl) {
			// Вернуть {data, error}
			return await axiosQuery('get', 'site/timesheet/slides', {indexes}, 'json', abortCtrl);
		},
		responsive: [
			{ breakpoint: 1500, slidesPerView: 3 },
			{ breakpoint: 1000, slidesPerView: 2 },
			{ breakpoint: 600, slidesPerView: 1 },
		]
	});
		
	
	

	
	
	// получить список сотрудников для добавления бригады
	$.getTeamList = ddrRenderWrap({
		template: 'timesheet.stafflist',
		map: [
			'day',
			{staffWrapSelector: (selector) => $(selector).closest('[timesheetcard]').find('[staffwrap]')},
			{staffListSelector: (selector) => $(selector).closest('[timesheetcard]').find('[stafflist]')},
		],
		middleware: async (selector, vars, abortCtrl) => {
			const {staffWrapSelector, staffListSelector, day} = vars;
			$(staffWrapSelector).addClass('timesheetcard__staffwrap-visible');

			const {data, error, status, headers} = await axiosQuery('get', 'site/timesheet/staff', {}, 'json', abortCtrl);
			
			if (error || !data) {
				console.error(error);
				$.notify(error?.message, 'error');
				return false;
			}
			
			vars.staff = data;
		},
		render: (selector, html, {staffWrapSelector, staffListSelector}) => {
			$(staffListSelector).html(html);
			
			$(staffWrapSelector).one('mousedown', ddrOneWrap(['[staffitem]'], (currentTarget) => {
				$(currentTarget).removeClass('timesheetcard__staffwrap-visible');
			}));
		},
		timeout: 500,
		leading: true,
		trailing: true,
		maxWait: 1000
	});
	
	
	
	
	
	
	// выбрать сотрудника для бригады
	$.addTeam = ddrRenderWrap({
		template: 'timesheet.team',
		map: [
			'teamId',
			'day',
			'staffFullName',
			{teamsSelector: (selector) => $(selector).closest('[timesheetcard]').find('[teams]')},
			{searchTeamsSelector: (selector) => $(selector).closest('[timesheetcard]').find('[staffwrap]')},
		],
		middleware: async (selector, vars, abortCtrl) => {
			const {teamsSelector, searchTeamsSelector, teamId, day, staffFullName} = vars;
			
			$(searchTeamsSelector).removeClass('timesheetcard__staffwrap-visible');
			
			const {data, error, status, headers} = await axiosQuery('post', 'site/timesheet/team', {
				staff_id: teamId, day: day
			}, 'json', abortCtrl);
			
			if (error || !data) {
				console.error(error);
				$.notify(error?.message, 'error');
				return false;
			}
			
			
			if (data) {
				$.notify('Бригада успешно добавлена!');
				vars.teamId = data;
			}
			
			return vars;
		},
		render: (selector, html, {teamsSelector}) => {
			$(teamsSelector).append(html);
		},
		timeout: 500,
		leading: true,
		trailing: true,
		maxWait: 1000
	});
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	// открыть поиск договора для бригады
	$.teamOpenSearch = ddrDebounceWrap((btn, teamId, dayDate) => {
		const teamSelector = $(btn).closest('[team]');
		$(teamSelector).addClass('timesheetcard__team-wait');
		$(teamSelector).find('[search]').addClass('timesheetcard__search-visible');
		$(teamSelector).setAttrib('noswipe');
		$(teamSelector).find('[searchinput]').setAttrib('meta', `${teamId},${dayDate}`);
		$(teamSelector).find('[searchinput]').focus();
		
		$(teamSelector).one('mousedown', ddrOneWrap(['.normal-input'], (currentTarget) => {
			$(currentTarget).removeClass('timesheetcard__team-wait');
			$(teamSelector).find('[search]').removeClass('timesheetcard__search-visible');
			$(teamSelector).find('[searchresutls]').empty();
			$(teamSelector).find('[searchinput]').val('');
			
			$(currentTarget).removeAttrib('noswipe');
		}));
	}, 500)
	
	
	
	
	
	
	// вывести список найденных договоров
	$.teamSearchContracts = ddrRenderWrap({
		template: 'timesheet.search',
		map: [
			{
				search: async (selector, args, abortCtrl) => {
					const {data, error, status, headers} = await axiosQuery('get', 'site/timesheet/contracts', {search: selector.value}, 'json', abortCtrl);
					return data;
				}
			},
		],
		middleware: (selector, mappingData, abortCtrl) => {
			const [teamId, dayDate] = $(selector).attr('meta').split(',');
			return {...mappingData, teamId, dayDate};
		},
		render: (selector, html, mappingData) => {
			$(selector).siblings('[searchresutls]').html(html);
		},
		timeout: 500,
		leading: false,
		trailing: true,
		maxWait: 1000
	});
	
	
	
	
	
	// добавить договор в бригаду
	$.chooseSearchedContract = ddrRenderWrap({
		template: 'timesheet.contract',
		map: [
			'...contract',
			'teamId',
			{teamSelector: (selector) => $(selector).closest('[team]')},
		],
		actions: {
		    setBtnStat: [
		    	{
		        	event: 'input',
		        	action: ddrDebounceWrap((selector, {root, args, e, map}) => {
		        		let message = getContenteditable(selector);
		        		if (message) $(selector).siblings('button').removeAttrib('disabled');
		        		else $(selector).siblings('button').setAttrib('disabled');
		        	}, 200, {leading: false, trailing: true, maxWait: 1000})
		    	},
		    ],
		    'setToEnter:keydown': (selector, {root, args, e, map}) => {
		    	if (e.keyCode === 13 && e.shiftKey == false) {
		    		e.preventDefault();
		    		$.teamAddComment($(selector).siblings('button')[0], ...args);
		    	}
		    }
		    //otherAction: (el, root, ...args) => { ... }
		},
		middleware: async (selector, vars, abortCtrl) => {
			
			const {teamSelector, id, teamId} = vars;
			
			$(teamSelector).find('[searchresutls]').empty();
			$(teamSelector).find('[searchinput]').val('');
			$(teamSelector).removeClass('timesheetcard__team-wait');
			$(teamSelector).find('[search]').removeClass('timesheetcard__search-visible');	
			
			const {destroy} = $(teamSelector).find('[contractslist]').ddrWait({
				bgColor: '#ffffffe6',
				iconHeight: '40px',
				position: 'calc(-100px + 42vh)',
				
			});
			
			const {data, error, status, headers} = await axiosQuery('post', 'site/timesheet/contract', {
				contract_id: id, team_id: Number(teamId)
			}, 'json', abortCtrl);
			
			destroy();
			
			if (error || !data) {
				console.error(error);
				$.notify(error?.message, 'error');
				return false;
			}
			
			vars.timesheet_contract_id = data;
			
			return vars;
		},
		render: (selector, html, {teamSelector}) => {
			$(teamSelector).find('[contractslist]').append(html);
		},
		timeout: 500,
		leading: true,
		trailing: true,
		maxWait: 1000
	});
	
	
	
	
	
	
	
	
	
	
	// добавить комментарий
	$.teamAddComment = ddrRenderWrap({
		template: 'timesheet.comment',
		map: [
			'tcId',
			{messSelector: (selector) => $(selector).siblings('[tchatmess]')},
			{chatSelector: (selector) => $(selector).closest('[contract]').find('[tchat]')},
		],
		middleware: async (selector, vars, abortCtrl) => {
			const {tcId, messSelector, chatSelector} = vars;
			
			const {data, error, status, headers} = await axiosQuery('post', 'site/timesheet/comment', {
				timesheet_contract_id: tcId,
				message: getContenteditable(messSelector)
			}, 'json', abortCtrl);
			
			
			if (error || !data) {
				console.error(error);
				$.notify(error?.message, 'error');
				return false;
			}
			
			return {chatSelector, messSelector, ...data?.data};
		},
		render: (selector, html, {chatSelector, messSelector}) => {
			$(chatSelector).append(html);
			$(messSelector).siblings('button').setAttrib('disabled');
		    $(messSelector).empty();
		    $.notify('Комментарий успешно добавлен!');
		},
		timeout: 500,
		leading: true,
		trailing: true,
		maxWait: 1000
	});
	
	
	
	
	
	
	
	
	
	
	
	// ---------------------------------------------------------------------------------------------------------------- Удаление бригады
	$.tsRemoveTeam = (item, id) => {
		const tsCard = $(item).closest('[timesheetcard]'),
			teamBlock = $(item).closest('[team]'),
			removeTeamBlock = $(teamBlock).find('[removeteamblock]');
		
		$(tsCard).find('.timesheetcard__remove-visible').removeClass('timesheetcard__remove-visible');
		
		$(removeTeamBlock).addClass('timesheetcard__remove-visible');
		
		$(removeTeamBlock).find('[closeremoveteam]').one(tapEvent, () => {
			$(removeTeamBlock).removeClass('timesheetcard__remove-visible');
			$(removeTeamBlock).find('[removeteam]').off(tapEvent);
		});
		
		$(removeTeamBlock).find('[removeteam]').one(tapEvent, async () => {
			const {destroy} = $(teamBlock).ddrWait({iconHeight: '30px'});
			const result = await removeTeam(id);
			if (result) {
				$(teamBlock).remove();
				$.notify('Бригада успешно удалена!');
				destroy();
			} 
		});
	}
	
	
	
	
	$.tsRemoveTeamContext = (item, id) => {
		if (event.pointerType === 'touch') return;
		return [
			{
				name: 'Удалить бригаду',
				faIcon: 'fa-solid fa-trash',
				onClick: async () => {
					const teamBlock = $(item.target.selector).closest('[team]');
					const {destroy} = $(teamBlock).ddrWait({iconHeight: '30px'});
					const result = await removeTeam(id);
					if (result) {
						$(teamBlock).remove();
						$.notify('Бригада успешно удалена!');
						destroy();
					} 
				}
			},
		];
	}
	
	
	
	let abortCtrlTeam;
	async function removeTeam(teamId) {
		if (abortCtrlTeam instanceof AbortController) abortCtrlTeam.abort();
		abortCtrlTeam = new AbortController();
		
		const {data, error, status, headers} = await axiosQuery('delete', 'site/timesheet/team', {
			id: Number(teamId)
		}, 'json', abortCtrlTeam);
		
		if (error || !data) {
			console.error(error);
			$.notify(error?.message, 'error');
			return false;
		}
		
		return true;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	// ---------------------------------------------------------------------------------------------------------------- Удаление договора
	$.tsRemoveContract = (item, tsContrId) => {
		const tsCard = $(item).closest('[timesheetcard]'),
			contract = $(item).closest('[contract]'),
			contractBlock = $(contract).find('[contracttable]'),
			removeContractBlock = $(contract).find('[removecontractblock]');
		
		$(tsCard).find('.timesheetcard__remove-visible').removeClass('timesheetcard__remove-visible');
		
		$(removeContractBlock).addClass('timesheetcard__remove-visible');
		
		$(removeContractBlock).find('[closeremovecontract]').one(tapEvent, () => {
			$(removeContractBlock).removeClass('timesheetcard__remove-visible');
			$(removeContractBlock).find('[removecontract]').off(tapEvent);
		});
		
		$(removeContractBlock).find('[removecontract]').one(tapEvent, async () => {
			const {destroy} = $(contract).ddrWait({iconHeight: '30px'});
			const result = await removeContract(tsContrId);
			if (result) {
				$(contract).remove();
				$.notify('Договор успешно удален!');
				destroy();
			} 
		});
	}
	
	
	
	
	$.tsRemoveContractContext = (item, tsContrId) => {
		if (event.pointerType === 'touch') return;
		return [
			{
				name: 'Удалить договор',
				faIcon: 'fa-solid fa-trash',
				onClick: async () => {
					const contract = $(item.target.selector).closest('[contract]'),
						contractBlock = $(contract).find('[contracttable]');
						
					const {destroy} = $(contractBlock).ddrWait({iconHeight: '30px'});
					const result = await removeContract(tsContrId);
					if (result) {
						$(contractBlock).remove();
						$.notify('Договор успешно удален!');
						destroy();
					} 
				}
			},
		];
	}
	
	
	
	
	let abortCtrlContract;
	async function removeContract(tsContrId) {
		if (abortCtrlContract instanceof AbortController) abortCtrlContract.abort();
		abortCtrlContract = new AbortController();
		
		const {data, error, status, headers} = await axiosQuery('delete', 'site/timesheet/contract', {
			timesheet_contract_id: Number(tsContrId)
		}, 'json', abortCtrlContract);
		
		if (error || !data) {
			console.error(error);
			$.notify(error?.message, 'error');
			return false;
		}
		
		return true;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	// ---------------------------------------------------------------------------------------------------------------- Удаление сообщения
	$.tsRemoveMessage = (item, tsMessId) => {
		const tsCard = $(item).closest('[timesheetcard]'),
			messageBlock = $(item).closest('[message]'),
			removeMessageBlock = $(messageBlock).find('[removemessageblock]');
		
		$(tsCard).find('.timesheetcard__remove-visible').removeClass('timesheetcard__remove-visible');
		
		$(removeMessageBlock).addClass('timesheetcard__remove-visible');
		
		$(removeMessageBlock).find('[closeremovemessage]').one(tapEvent, () => {
			$(removeMessageBlock).removeClass('timesheetcard__remove-visible');
			$(removeMessageBlock).find('[removemessage]').off(tapEvent);
		});
		
		$(removeMessageBlock).find('[removemessage]').one(tapEvent, async () => {
			const {destroy} = $(messageBlock).ddrWait({iconHeight: '30px'});
			const result = await removeMessage(tsMessId);
			if (result) {
				$(messageBlock).remove();
				$.notify('Сообщение успешно удалено!');
				destroy();
			} 
		});
	}
	
	
	
	
	$.tsRemoveMessageContext = (item, tsMessId) => {
		if (event.pointerType === 'touch') return;
		return [
			{
				name: 'Удалить сообщение',
				faIcon: 'fa-solid fa-trash',
				onClick: async () => {
					const messageBlock = $(item.target.selector).closest('[message]');
					const {destroy} = $(messageBlock).ddrWait({iconHeight: '30px'});
					const result = await removeMessage(tsMessId);
					if (result) {
						$(messageBlock).remove();
						$.notify('Сообщение успешно удалено!');
						destroy();
					} 
				}
			},
		];
	}
	
	
	
	
	let abortCtrlMessage;
	async function removeMessage(tsMessId) {
		if (abortCtrlMessage instanceof AbortController) abortCtrlMessage.abort();
		abortCtrlMessage = new AbortController();
		
		const {data, error, status, headers} = await axiosQuery('delete', 'site/timesheet/comment', {
			mess_id: Number(tsMessId)
		}, 'json', abortCtrlMessage);
		
		if (error || !data) {
			console.error(error);
			$.notify(error?.message, 'error');
			return false;
		}
		
		return true;
	}
	
	
	
</script>