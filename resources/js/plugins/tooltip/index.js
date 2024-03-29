import 'tippy.js/dist/tippy.css';
window.tippy = require('tippy.js').default;

$.fn.ddrTooltip = function(params, callback) {
	const {
		cls,
		tag,
		placement, // top top-start top-end right right-start right-end bottom bottom-start bottom-end left left-start left-end auto auto-start auto-end
		appendTo, // selector body parent
		trigger,
		offset,
		interactive,
		duration,
		hideOnClick,
		wait,
		maxWidth,
		minWidth,
		minHeight,
		onCreate,
		onShow,
		onDestroy,
	} = _.assign({
		cls: null,
		tag: null,
		placement: 'right',
		trigger: 'mousedown',
		offset: [0, 0],
		interactive: true,
		duration: [300, 250],
		hideOnClick: true,
		wait: false,
		maxWidth: 'none',
		minWidth: false,
		minHeight: false,
		onCreate: null,
		onShow: null,
		appendTo: () => document.body, // 'parent', element
		onDestroy: () => {},
	}, params);
		
	let waitStat;
	
	
	let toolTipObj = tippy(this[0], {
		content: '<div'+(cls ? ' class="'+cls+'"' : '')+(tag ? ' '+tag : '')+' style="'+(minWidth ? ('min-width: '+minWidth+';') : '')+(minHeight ? (' min-height: '+minHeight+';') : '')+'"></div>',
		allowHTML: true,
		placement,
		trigger,
		interactive,
		duration,
		hideOnClick,
		maxWidth,
		offset,
		appendTo,
		onCreate(instance) { // {reference, popper, show, hide, destroy, setContent, setProps}
			if (wait) {
				waitStat = $(instance.popper).ddrWait(_.assign({
					iconHeight: '30px',
					tag: 'noscroll'
				}, wait));
			}
				
			instance.show();
			
			instance['waitDetroy'] = waitStat?.destroy || null;
			
			instance['setData'] = (data = '', setNew = false) => {
				instance.setContent('<div'+(cls ? ' class="'+cls+'"' : '')+(tag ? ' '+tag : '')+' style="'+(minWidth ? ('min-width: '+minWidth+';') : '')+(minHeight ? ('min-height: '+minHeight+';') : '')+'">'+data+'</div>');
			}
			
			if (onCreate && typeof onCreate == 'function') onCreate(instance);
		},
		onShow(instance) { // {reference, popper, show, hide, destroy, setContent, setProps}
			instance['waitDetroy'] = waitStat?.destroy || null;
			
			instance['setData'] = (data = '', setNew = false) => {
				instance.setContent('<div'+(cls ? ' class="'+cls+'"' : '')+(tag ? ' '+tag : '')+' style="'+(minWidth ? ('min-width: '+minWidth+';') : '')+(minHeight ? ('min-height: '+minHeight+';') : '')+'">'+data+'</div>');
			}
			
			if (onShow && typeof onShow == 'function') onShow(instance);
		},
		onHidden({reference, popper, show, hide, destroy, setContent, setProps}) {
			destroy();
		},
		onDestroy,
		//delay: [0,1000],
	});
	
	
	toolTipObj['wait'] = (setWait) => {
		$(toolTipObj.popper).ddrWait(_.assign({
			iconHeight: '30px',
			tag: 'noscroll'
		}, setWait || wait));
	}
	
	
	return toolTipObj;
}
