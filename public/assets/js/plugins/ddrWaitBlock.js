import {styleModule} from 'style-module';

$.fn.ddrWait = function(params = null) {
	let block = this,
		isBtn = block[0]?.tagName?.toLowerCase() == 'button',
		ddrwBId = 'ddrWaitBlock'+random(9,99999),
		{text, fontSize, fontColor, icon, iconHeight, iconColor, bgColor} = _.assign({
			text: '',
			fontSize: '16px',
			fontColor: '#7a9699',
			icon: '',
			iconHeight: '50px',
			bgColor: '#fffe',
			iconColor: 'hue-rotate(333deg)'
		}, params),
		{ddrwaitwrapper, ddrwaitBlock, ddrwaitBlockVisible, ddrwaitContent, ddrwaitIcon, ddrwaitText} = styleModule({
			ddrwaitwrapper: {
				position: 'relative',
			},
			ddrwaitBlock: {
				position: 'absolute',
				top: 0,
				left: 0,
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
				width: '100%',
				height: '100%',
				backgroundColor: bgColor,
				opacity: 0,
				borderRadius: 'inherit',
				transition: 'opacity 0.16s',
				zIndex: 10
			},
			ddrwaitBlockVisible: {
				opacity: 1,
			},
			ddrwaitContent: {
				textAlign: 'center'
			},
			ddrwaitIcon: {
				maxHeight: iconHeight,
				height: '90%',
				filter: iconColor
			},
			ddrwaitText: {
				marginTop: '5px',
				color: fontColor,
				fontSize
			}
		});
	
		
	let labelHtml = text ?  '<p class="'+ddrwaitText+'">'+text+'</p>' : '',
		iconHtml = '<img src="/assets/images/loading.gif" ddrwaiticon class="'+ddrwaitIcon+'">';
	
	$(block).addClass(ddrwaitwrapper);
	$(block).append('<div class="'+ddrwaitBlock+' noselect" id="'+ddrwBId+'"><div class="'+ddrwaitContent+'">'+iconHtml+labelHtml+'</div></div>');
	if (isBtn) $(block).ddrInputs('disable');
	
	$('#'+ddrwBId).ready(() => {
		$('#'+ddrwBId).addClass(ddrwaitBlockVisible);
	});
	
	return {
		destroy() {
			if (isBtn) $(block).ddrInputs('enable');
			$(block).removeClass(ddrwaitwrapper);
			$(block).find('#'+ddrwBId).remove();
			//$(block).find('.'+ddrwaitwrapper+', .'+ddrwaitBlock+', .'+ddrwaitContent+', .'+ddrwaitIcon+', .'+ddrwaitText).removeClass('s_*');
		}
	};
	//console.log(ddrwaitwrapper, ddrwaitBlock, ddrwaitContent, ddrwaitIcon, ddrwaitText);
}