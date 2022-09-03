@aware([
	'settings' 	=> null,
	'groupWrap'	=> null,
])

@props([
	'id' 			=> 'datepicker'.rand(0,9999999),
	'calendarid'	=> null,
	'disabled' 		=> false,
	'noedit' 		=> false,
	'date'			=> false,
	'onselect'		=> false,
	'setting' 		=> false,
	'group'			=> $groupWrap
])


<div {{$attributes->class([
		'input',
		$group.'-input' => $group,
		($group ? $group.'-' : '').'input-datepicker',
		($group ? $group.'-' : '').'input_noempty' => $value
	])}}>
	<input
		type="text"
		value="{{$setValue($value, $settings, $setting)}}"
		date
		id="{{$id}}"
		placeholder="{{$placeholder}}"
		autocomplete="off"
		{{$setInpGroup()}}
		@if($disabled)disabled @endif
		noedit
		readonly
		{{$tagParam ? $tag.'='.$tagParam.'' : $tag}}
		>
	<input
		type="hidden"
		name="{{$name}}"
		id="{{$id}}hidden"
		datepicker
		>
	<div class="icon">
		<i class="fa-solid fa-calendar-days"></i>
	</div>
	
	
	<div class="{{($group ? $group.'-' : '').'input__errorlabel'}}" errorlabel></div>
</div>



<script type="module">
	let cd,
		currentDate = '{{$date}}' || '{{$setValue($value, $settings, $setting)}}',
		selector = '#{{$id}}',
		hiddenSelector = selector+'hidden',
		currentLocate = '{{App::currentLocale()}}',
		weekDays = '{{__('ui.week_days')}}'.split('|'),
		monthesNames = '{{__('ui.monthes_names')}}'.split('|'),
		monthesNamesFull = '{{__('ui.monthes_names_full')}}'.split('|'),
		writeYear = '{{__('ui.write_year')}}',
		calendarId = '{{$calendarid}}' || false,
		onselectFn = '{{$onselect}}',
		setting = '{{$setting}}';
	
	
	let dateSelected = currentDate ? (new Date(currentDate.replace(/ /g,"T")) || new Date(currentDate.replace(/-/g, '/'))) : null;
	
	
	const ops = {
		//position: 'bl', // 'tr', 'tl', 'br', 'bl', 'c' Этот параметр позиционирует календарь относительно поля <input>
		startDay: 1,
		defaultView: 'calendar',
		overlayPlaceholder: writeYear,
		customDays: weekDays,
		customMonths: monthesNames,
		//alwaysShow: true,
		dateSelected: dateSelected,
		//maxDate: new Date(2099, 0, 1),
		//minDate: new Date(2018, 0, 1),
		//startDate: new Date(2022, 0, 1),
		//showAllDates: true,
		//disabler: date => date.getDay() === 2, // запретить определенные дни date.getMonth() === 9
		///* disabledDates: [
		//	new Date(2099, 0, 5),
		//	new Date(2099, 0, 6),
		//	new Date(2099, 0, 7),
		//], */
		//disableMobile: true, // запретить на мобильных, если будет использоваться нативный (input должен быть type="date") 
		//
		//
		//
		onSelect: (instance, date) => {
			if (!date) return false;
			
			$(selector).parent().find('[errorlabel]').empty();
			$(selector).parent().removeClass('input_error');
			
			$(hiddenSelector).val(addZero(date.getDate())+'-'+addZero(date.getMonth() + 1)+'-'+date.getFullYear());
			$(selector).ddrInputs('value', date.getDate()+' '+monthesNamesFull[date.getMonth()]+' '+date.getFullYear()+(currentLocate == 'ru' ? ' г.' : ''));
			$(selector).setAttrib('date', addZero(date.getDate())+'-'+addZero(date.getMonth() + 1)+'-'+date.getFullYear());
			
			let dateRange = calendarId ? datePicker.getRange() : null;
			
			if (onselectFn && typeof onselectFn == 'function') $[onselectFn](instance, date, dateRange);
			
			if (setting) {
				let saveDate;
				if (dateRange) {
					let dateStart = dateRange['start'],
						dateEnd = dateRange['end'];
					
					saveDate['start'] = dateStart.getFullYear()+'-'+addZero(dateStart.getMonth() + 1)+'-'+addZero(dateStart.getDate());
					saveDate['end'] = dateEnd.getFullYear()+'-'+addZero(dateEnd.getMonth() + 1)+'-'+addZero(dateEnd.getDate());
				} else {
					saveDate = date.getFullYear()+'-'+addZero(date.getMonth() + 1)+'-'+addZero(date.getDate());
				}
				
				// коллюэк при сохранении значения
				$.setSetting(setting, saveDate, function() {
					//console.log('datepicker callback');
				});
			}
			
			$(selector).trigger('datepicker'); 
			
			// Do stuff when a date is selected (or unselected) on the calendar.
			// You have access to the datepicker instance for convenience.
		},
		//onShow: instance => {
		//	// Do stuff when the calendar is shown.
		//	// You have access to the datepicker instance for convenience.
		//},
		//onHide: instance => {
		//	// Do stuff once the calendar goes away.
		//	// You have access to the datepicker instance for convenience.
		//},
		formatter: (input, cd, instance) => {
			if (currentDate) {
				$(hiddenSelector).val(addZero(cd.getDate())+'-'+addZero(cd.getMonth() + 1)+'-'+cd.getFullYear());
				input.value = cd.getDate()+' '+monthesNamesFull[cd.getMonth()]+' '+cd.getFullYear()+(currentLocate == 'ru' ? ' г.' : '')
				$(input).setAttrib('date', addZero(cd.getDate())+'-'+addZero(cd.getMonth() + 1)+'-'+cd.getFullYear());
			}
		}
	};
	
	
	if (calendarId) ops['id'] = calendarId; // для диапазона дат
	
	const datePicker = ddrDatepicker(selector, ops);
	
	
	
	
	
	
	
	
	
	// datePicker.disabled = false // true отключить или включить
	// picker.setDate(new Date(2099, 0, 1), true) 
	// picker.show()
	// picker1.hide() только если alwaysShow: false
	
	
	/* button.addEventListener('click', e => {
	// THIS!!! Prevent Datepicker's event handler from hiding the calendar.
	e.stopPropagation()

	// Toggle the calendar.
	const isHidden = picker.calendarContainer.classList.contains('qs-hidden')
	picker[isHidden ? 'show' : 'hide']()
	}) */
	
	/* 
	Этот метод доступен только для календарей с диапазоном дат. 
	Он возвращает объект со свойствами start и end, значениями которых являются объекты дат JavaScript, 
	представляющие то, что пользователь выбрал в обоих календарях.
	
	start.getRange() // { start: <JS date object>, end: <JS date object> }
	end.getRange() */
	
	
	//console.log(datePicker);
	
	

	/* $('body').on(tapEvent, function(e) {
		e.stopPropagation();
		
	}); */
	/* $(selector).on('blur', function(e) {
		e.stopPropagation()
		datePicker.hide();
		//picker[isHidden ? 'show' : 'hide']()
	}); */
	
	
	
</script>