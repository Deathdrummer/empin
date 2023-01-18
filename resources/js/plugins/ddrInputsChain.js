window.ddrInputsChain = (data) => {
	data.forEach(({inputs, callbacks, trigger}) => {
		const [inp1, inp2] = inputs;
		const [cb1, cb2] = callbacks;
		
		//if (typeof cb2 !== 'function' && typeof cb2 !== 'function') return;
		
		let inp1ValBefore, inp2ValBefore;
		$(inp1).on('mousedown beforeinput input ddrInput1Chain', function(e) {
			if (['mousedown', 'beforeinput'].indexOf(e.type) !== -1) {
				inp1ValBefore = parseFloat($(inp1).val());
				inp2ValBefore = parseFloat($(inp2).val());
			} else if (['input', 'ddrInput1Chain'].indexOf(e.type) !== -1) {
				let inp1ValAfter = parseFloat($(inp1).val());
				let inp2ValAfter = parseFloat($(inp2).val());
				
				$(inp2).val(cb1({
					inp1: inp1ValAfter,
					inp2: inp2ValAfter,
					inp1Before: inp1ValBefore,
					inp2Before: inp2ValBefore
				}));
				
				$(inp2).trigger('ddrInput1Chain');
			}
		});
		
		if (cb2 && typeof cb2 == 'function') {
			$(inp2).on('mousedown beforeinput input ddrInput2Chain', function(e) {
				if (['mousedown', 'beforeinput'].indexOf(e.type) !== -1) {
					inp2ValBefore = parseFloat($(inp2).val());
					inp1ValBefore = parseFloat($(inp1).val());
				} else if (['input', 'ddrInput2Chain'].indexOf(e.type) !== -1) {
					let inp2ValAfter = parseFloat($(inp2).val());
					let inp1ValAfter = parseFloat($(inp1).val());

					$(inp1).val(cb2({
						inp2: inp2ValAfter,
						inp1: inp1ValAfter,
						inp2Before: inp2ValBefore,
						inp1Before: inp1ValBefore
					}));

					$(inp1).trigger('ddrInput2Chain');
				}
			});
		}	
	});
};