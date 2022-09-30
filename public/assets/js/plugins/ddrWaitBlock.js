import {styleModule} from 'style-module';

$.fn.ddrWait = function(params = null) {
	let block = this,
		isBtn = block[0]?.tagName?.toLowerCase() == 'button',
		ddrwBId = 'ddrWaitBlock'+random(9,99999),
		{text, fontSize, fontColor, icon, iconHeight, iconColor, bgColor, position, tag} = _.assign({
			text: '',
			fontSize: '16px',
			fontColor: '#7a9699',
			icon: '',
			iconHeight: '50px',
			bgColor: '#fffe',
			iconColor: 'hue-rotate(333deg)',
			position: 'center',
			tag: null
		}, params),
		{ddrwaitwrapper, ddrwaitBlock, ddrwaitBlockVisible, ddrwaitContent, ddrwaitIcon, ddrwaitText} = styleModule({
			ddrwaitwrapper: {
				position: 'relative',
			},
			ddrwaitBlock: {
				position: 'absolute',
				top: 0,
				bottom: 0,
				left: 0,
				right: 0,
				width: 'revert',
				display: 'flex',
				alignItems: position == 'adaptive' ? 'flex-start' : position,
				justifyContent: 'center',
				backgroundColor: bgColor,
				opacity: 0,
				borderRadius: 'inherit',
				transition: 'opacity 0.16s',
				overflowY: 'hidden',
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
	$(block).append('<div class="'+ddrwaitBlock+' noselect" id="'+ddrwBId+'"'+(tag ? ' '+tag : '')+'><div class="'+ddrwaitContent+'" ddrwaitindicator>'+iconHtml+labelHtml+'</div></div>');
	if (isBtn) $(block).ddrInputs('disable');
	
	$('#'+ddrwBId).ready(() => {
		$('#'+ddrwBId).addClass(ddrwaitBlockVisible);
		
		if (position == 'adaptive') {
			let blockTop = $(block).offset().top,
				scrTop = $(document).scrollTop(),
				top = scrTop > blockTop ? (scrTop - blockTop) : blockTop;
			
			$('[ddrwaitindicator]').css('transform', 'translateY(calc(50vh - ('+iconHeight+' / 2) + '+top+'px))');
		} else if (position != 'center') {
			$('[ddrwaitindicator]').css('transform', 'translateY('+position+')');
		}
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