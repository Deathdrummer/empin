import "./index.css";

// задать конструкцию:
// <div ddrhscroll="0|200">
// 	<div>
// 		Здесь ожно размещать элементы, которые будут так же на виду, но не прокручиваться
// 		<div ddrhscrolltrack>
// 			// содержание
// 		</div>
// 	</div>
// </div>

// - ddrhscroll="0|0|md"
//		- дополнительная прокрукта сверху
//		- дополнительная прокрукта снизу
//		- минимальная разрешенная ширина для включения плагани (можно в пикселях, можно брейкпоинт)
//		- Сохранение положения скролла
// - Чтобы задать отступ от краев, достаточно просто задать паддинги для [ddrhscrolltrack] 


let scrPos,
	scrW,
	scrH,
	winW,
	winH,
	blockH,
	pl,
	pr,
	scrTop,
	scrResTOut;


function initScrollValues(scrElem, scrTrack, scrollExtraTop, scrollExtraBottom) {
	$(scrElem).addClass('enabled');
	
	scrPos = $(scrElem).offset().top,
	scrW =  $(scrTrack)[0].scrollWidth,
	scrH = $(scrTrack).outerHeight(),
	blockH = $(scrElem).children().outerHeight(),
	winW = $(window).width(),
	winH = $(window).height(),
	scrTop = $(scrElem).scrollTop();
	
	$(scrElem).height(scrW - winW - (winH - blockH) + winH + scrollExtraTop + scrollExtraBottom).addClass('visible');
}
	
	
function setScrollPos(scrTrack, scrollExtraBottom, resized = false) {
	let p = scrTop >= scrPos - scrollExtraBottom ? scrTop - scrPos - scrollExtraBottom : 0; 
	if (scrTop <= scrPos + scrollExtraBottom + scrW - winW) {
		
		if (scrTop <= scrPos + scrollExtraBottom && scrTop >= scrPos) {
			$(scrTrack).css('transform', 'translateX(0px)');
		} else {
			$(scrTrack).css('transform', 'translateX(-'+p+'px)');
		}
	} else {
		if (!resized) $(scrTrack).css('transform', 'translateX(-'+(scrW - winW)+'px)');
	}
}


		


function destroyDdrHScroll(scrElem, scrTrack) {
	$(scrTrack).css('transform', 'none');
	$(scrElem).css('height', 'auto');
	$(scrElem).removeClass('enabled');
}



function isIntBrPoint(n) {
	if (n == undefined || typeof n == 'undefined') return false;
	if (typeof n != 'string') return Number(n) === n && n % 1 === 0;
	return Number(n)+'' === n;
};






function getEnableWiidth(w) {
	if (['xs', 'sm', 'md', 'lg', 'xl', 'xxl'].indexOf(w) !== -1) {
		return breakpoints[w];
	}
	return parseInt(w);
}




$(document).ready(function() {
	if ($(document).find('[ddrhscroll]').length) {
		$(document).find('[ddrhscroll]').each(function() {
			let scrElem = this,
				scrTrack = $(this).find('[ddrhscrolltrack]'),
				d = $(scrElem).attr('ddrhscroll').split('|'),
				scrollExtraTop = parseInt(d[0]) || 0,
				scrollExtraBottom = parseInt(d[1]) || 0,
				enableWidth = getEnableWiidth(d[2]) || 0;
				
			
			if ($(window).width() >= enableWidth) {
				initScrollValues(scrElem, scrTrack, scrollExtraBottom, scrollExtraTop);
				setScrollPos(scrTrack, scrollExtraTop);
			} else {
				$(scrElem).addClass('visible');
			}
			
			
			$(window).resize(function() {
				clearTimeout(scrResTOut);
				scrResTOut = setTimeout(() => {
					if ($(window).width() >= enableWidth) {
						initScrollValues(scrElem, scrTrack, scrollExtraBottom, scrollExtraTop);
						setScrollPos(scrTrack, scrollExtraTop, true);
					} else {
						destroyDdrHScroll(scrElem, scrTrack);
					}
				}, 50);
			});
			
			
			
			/*$(document).on("scrollstop",function() {
				scrTop = $(this).scrollTop();
				localStorage.setItem('ddrHScroll:scrollPos', scrTop);
			});*/
			
				
						
			$(window).scroll(function() {
				if ($(window).width() >= enableWidth) {
					scrTop = $(this).scrollTop();
					setScrollPos(scrTrack, scrollExtraTop);
				}
			});
		});
		
		
		/*setTimeout(() => {
			let savedScrPos = localStorage.getItem('ddrHScroll:scrollPos');
			if (savedScrPos) $(window).scrollTop(savedScrPos);
		});*/
		
	}
});