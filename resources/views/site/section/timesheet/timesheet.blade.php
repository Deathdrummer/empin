<section>
	<div id="ddrSwiper" class="ddrswiper" noselect>
			{{-- <div class="ddrswiper__item"><div class="card minh50rem h100 w100 rounded-5rem"><p>0</p></div></div>
			<div class="ddrswiper__item"><div class="card minh50rem h100 w100 rounded-5rem"><p>1</p></div></div>
			<div class="ddrswiper__item"><div class="card minh50rem h100 w100 rounded-5rem"><p>2</p></div></div>
			<div class="ddrswiper__item"><div class="card minh50rem h100 w100 rounded-5rem"><p>3</p></div></div>
			<div class="ddrswiper__item"><div class="card minh50rem h100 w100 rounded-5rem"><p>4</p></div></div>
			<div class="ddrswiper__item"><div class="card minh50rem h100 w100 rounded-5rem"><p>5</p></div></div>
			<div class="ddrswiper__item"><div class="card minh50rem h100 w100 rounded-5rem"><p>6</p></div></div>
			<div class="ddrswiper__item"><div class="card minh50rem h100 w100 rounded-5rem"><p>7</p></div></div>
			<div class="ddrswiper__item"><div class="card minh50rem h100 w100 rounded-5rem"><p>8</p></div></div> --}}
	</div>
</section>


<style>
.ddrswiper {
	overflow-x: hidden;
	overflow-y: hidden;
	white-space: nowrap;
	font-size: 0px;
	height: 100%;
}

.ddrswiper__item-current .card {
	box-shadow: 0 0 0 4px #aac4cf inset;
	transition: box-shadow 0.2s;
}

.ddrswiper__item {
	display: inline-block;
	width: var(--slide-width);
	height: 100%;
	position: relative;
	padding: 15px;
}

.ddrswiper__item.test {
	background-color: #f00;
}

.ddrswiper__item-waiting > *:not(.ddrswiper__waiting) {
	opacity: 0.3;
}


.ddrswiper__item-waiting .ddrswiper__waiting {
	opacity: 1;
	pointer-events: auto;
}

.ddrswiper__spacer {
	display: inline-block;
	position: relative;
	height: 0;
	pointer-events: none;
}



.ddrswiper__item:active {
	cursor: grabbing;
}



.ddrswiper__waiting {
	position: absolute;
	inset: 0;
	display: flex;
	justify-content: center;
	align-items: center;
	border-radius: inherit;
	opacity: 0;
	pointer-events: none;
	z-index: 10;
}

.ddrswiper__waiting > div {
	text-align: center;
}

.ddrswiper__waiting img {
	width: 40px;
	filter: hue-rotate(189deg) grayscale(0.5);
	
}

.ddrswiper__waiting p {
	margin-top: 15px;
	color: #b8c3db;
}



/*.ddrswiper__item:before {
	content: '';
	display: block;
	width: 50%;
	height: 100%;
	background-color: #f00;
	opacity: 0.1;
	position: absolute;
	top: 0;
	left: 0;
	z-index: 10;
}

.ddrswiper__item:after {
	content: '';
	display: block;
	width: 50%;
	height: 100%;
	background-color: #0f0;
	opacity: 0.1;
	position: absolute;
	top: 0;
	left: 50%;
}*/


</style>


<script type="module">
	
	
	const centerSlide = 'center',
		slidesPerView = 5,
		slidesCount = 21,
		loadNewOffset = 10,
		slideBlank = `<div class="card minh50rem h100 w100 rounded-5rem">[placer]</div>`; // [placer] - это то место, куда будут загружены данные
	
	
	
	
	
	// это просто дополнительный коллбэк при получении новых данных для слайдов
	$('#ddrSwiper').on('ddrLoadSlidesData', (e, newIndexes) => {
		//console.log(123);
	});
	
	
	
	
	const swiperContainer = document.getElementById('ddrSwiper');
	
	let slideWidth = swiperContainer.offsetWidth / slidesPerView,  //$('#ddrSwiper').find('.ddrswiper__item').first().outerWidth(),
		swipeStat = false,
		countSlides = slidesCount,
		slidesShift = 0, // смещение слайдов при свайпе
		currentSlideSelector = null,
		currentSlide = centerSlide == 'center' ? Math.ceil(countSlides / 2) - 1 : (centerSlide >= 0 ? (centerSlide > countSlides ? countSlides : centerSlide) : 0),
		spaceWidth = slideWidth*Math.floor(slidesPerView / 2),
		containerWidth = swiperContainer.offsetWidth,
		observer = null, // Объявляем observer на верхнем уровне
		loadNewSlidesAbortCtrl;
	
	initSlides();
	
		
	//let accum = 0;
	
	$('#ddrSwiper').off('mousedown.ddrscroll touchstart.ddrscroll').on('mousedown.ddrscroll touchstart.ddrscroll', function(e) {
		swipeStat = true;
		
		countSlides = $('#ddrSwiper').find('.ddrswiper__item').length;
		
		$('#ddrSwiper').stop(true);
		
		let $item = $(this);
		let startX = getPageX(e);
		
		let initialLeft = parseInt($item.css('left')) || 0;
		
		let scrollLeft = $('#ddrSwiper').scrollLeft();
		
		let startSpaceWidth = $('#ddrSwiper').find('.ddrswiper__spacer-start').outerWidth();
		let endSpaceWidth = $('#ddrSwiper').find('.ddrswiper__spacer-end').outerWidth();
		
		let scrLeftEnd = $('#ddrSwiper').get(0).scrollWidth - swiperContainer.offsetWidth;
		
		let fixLeft = false, fixRight = false;
		function onMouseMove(e) {
			let pageX = getPageX(e);
			let delta = pageX - startX;
			let edgeShift;
			let scrLeft = $('#ddrSwiper').scrollLeft();
			
			if (scrLeft == 0) {
				if (fixLeft === false) fixLeft = pageX;
				edgeShift = pageX - fixLeft;
				$('#ddrSwiper').find('.ddrswiper__spacer-start').css('width', startSpaceWidth + (edgeShift / 6));
			}
			
			if (scrLeft >= scrLeftEnd) {
				if (fixRight === false) fixRight = pageX;
				edgeShift = fixRight - pageX; 
				$('#ddrSwiper').find('.ddrswiper__spacer-end').css('width', endSpaceWidth + (edgeShift / 6));
			}
			
			$('#ddrSwiper').scrollLeft(scrollLeft-(initialLeft + delta));
		}
		
		function onMouseUp(e) {
			$(document).off('mousemove.ddrscroll', onMouseMove);
			$(document).off('mouseup.ddrscroll', onMouseUp);
			
			$('#ddrSwiper').find('.ddrswiper__spacer-start').stop(true, false).animate({width: startSpaceWidth}, 100);
			$('#ddrSwiper').find('.ddrswiper__spacer-end').stop(true, false).animate({width: endSpaceWidth}, 100);
			
			let scrollLeft = $('#ddrSwiper').scrollLeft() + (slideWidth / 2),
				scrollSlides = Math.floor(scrollLeft / slideWidth);
			
			
			$('#ddrSwiper').animate({scrollLeft: slideWidth * scrollSlides}, {
				duration: 200,
				easing: 'customQuad',
				complete: () => {
					$('#ddrSwiper').trigger('ddrTransitionEnd', [slidesShift]);
					slidesShift = 0;
				}
			});
			
			swipeStat = false;
		}
		
		$(document).off('mousemove.ddrscroll touchmove.ddrscroll').on('mousemove.ddrscroll touchmove.ddrscroll', onMouseMove);
		$(document).off('mouseup.ddrscroll touchend.ddrscroll touchcancel.ddrscroll').on('mouseup.ddrscroll touchend.ddrscroll touchcancel.ddrscroll', onMouseUp);
		
		e.preventDefault();
	});
	
	
	
	
	
	
	$('#ddrSwiper').on('ddrTransitionEnd', (e, slidesShift) => {	
		$('#ddrSwiper').find('.ddrswiper__item.ddrswiper__item-current').removeClass('ddrswiper__item-current');
		$(currentSlideSelector).addClass('ddrswiper__item-current');
		
		if (slidesShift == 0) return;
		
		let offset = 0;
		
		if (slidesCount != countSlides) console.warn(`количество слайдо отличается от изначального! Было: ${slidesCount} Стало: ${countSlides}`);
		
		if (slidesShift > 0) {
			offset = loadNewOffset - (slidesCount - currentSlide - 1);	
		} else if (slidesShift < 0) {
			offset = -(loadNewOffset - currentSlide);	
		}
		
		if (offset != 0) {
			removeSlides(offset);
			addSlides(offset);
			setPosition(offset);
			initializeObserver();
		}
	});
	
	
	$('#ddrSwiper').on('ddrSetCurrentSlide', (e, index, slide, slideOffset) => {
		if (!swipeStat) {
			$('#ddrSwiper').find('.ddrswiper__item.ddrswiper__item-current').removeClass('ddrswiper__item-current');
			$(slide).addClass('ddrswiper__item-current');
		}
	});
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//------------------------------------------------------------------------- Вспомогательные функции
	
	// пересечение слайда через центр
	function initializeObserver() {
	  if (observer) {
		observer.disconnect(); // Прекращаем наблюдение за всеми элементами
	  }

	  const swiperItems = document.querySelectorAll('.ddrswiper__item');
	  containerWidth = swiperContainer.offsetWidth;

	  observer = new IntersectionObserver((entries) => {
		entries.forEach(entry => {
		  if (entry.isIntersecting) {
			const intersectingItem = entry.target;
			const itemIndex = Array.from(swiperItems).indexOf(intersectingItem);
			
			const slideStep = itemIndex - currentSlide;
			
			if (swipeStat) slidesShift += slideStep;
			
			$('#ddrSwiper').trigger('ddrSetCurrentSlide', [itemIndex, intersectingItem, slideStep]);
			currentSlide = itemIndex;
			currentSlideSelector = intersectingItem;
		  }
		});
	  }, {
		root: swiperContainer,
		rootMargin: `0px ${-containerWidth / 2}px 0px ${-containerWidth / 2}px`,
		threshold: 0
	  });

	  swiperItems.forEach(item => observer.observe(item));
	}
	
	
	
	
	
	function addSlides(count) {
		if (count == 0) return;
		const swiperContainer = $('#ddrSwiper');
		const firstSlide = swiperContainer.find('.ddrswiper__item').first();
		const lastSlide = swiperContainer.find('.ddrswiper__item').last();
		const currentItemCount = swiperContainer.find('.ddrswiper__item').length;
		
		const newSlides = [];
		const newIndexes = [];
		
		if (count < 0) {
			const lastIndex = Number(firstSlide.attr('ddrswiper-index'));
			
			for (let i = 1; i <= Math.abs(count); i++) {
				newSlides.push(buildSlide(lastIndex - i));
				newIndexes.push(lastIndex - i);
			}
			$(firstSlide).before(...newSlides.reverse());
		} else if (count > 0) {
			const lastIndex = Number(lastSlide.attr('ddrswiper-index'));
			
			for (let i = 1; i <= Math.abs(count); i++) {
				newSlides.push(buildSlide(lastIndex + i));
				newIndexes.push(lastIndex + i);
			}
			$(lastSlide).after(...newSlides);
		}
		
		getSlidesData(newIndexes);
		
		$('#ddrSwiper').trigger('ddrLoadSlidesData', [newIndexes]);
	}
		
	
	
	
	
	function removeSlides(count = 0) {
		if (count == 0) return;
		if (count > 0) $('#ddrSwiper').find('.ddrswiper__item').slice(0, count).remove();
		else if (count < 0) $('#ddrSwiper').find('.ddrswiper__item').slice(count).remove();
	}

	
	
	
	function setPosition(count) {
		if (count == 0) return;
		
		let scrollPos = $('#ddrSwiper').scrollLeft();
		
		if (count < 0) $('#ddrSwiper').scrollLeft(scrollPos - (slideWidth * count));
		else if (count > 0) $('#ddrSwiper').scrollLeft(scrollPos - (slideWidth * count));
	}
	
	
	
	
	
	function getPageX(e) {
		if (e.type.startsWith('touch')) {
			const touch = e.originalEvent.touches[0] || e.originalEvent.changedTouches[0];
			return touch.pageX;
		}
		
		return e.pageX;
	}
		
	
	
	
	function buildSlide(index = 0) {
		return`<div class="ddrswiper__item ddrswiper__item-waiting" ddrswiper-index="${index}" ddrswiperitem>
			<div class="ddrswiper__waiting" ddrswiperwaiting>
				<div>
					<img src="/assets/images/loading.gif" notouch />
					<p>Загрузка...</p>
				</div>
			</div>
			${slideBlank || ''}
		</div>`;
	}
	
	
	
	
	function initSlides() {
		ddrCssVar('slide-width', `calc(100% / ${slidesPerView})`);
		
		const initSlides = [],
			initIndex = centerSlide == 'center' ? -Math.floor(slidesCount / 2) : 0,
			sCount = centerSlide == 'center' ? Math.ceil(slidesCount / 2) : slidesCount,
			initIndexes = [];
		
		for (let i = initIndex; i < sCount; i++) {
			initSlides.push(buildSlide(i));
			initIndexes.push(i);
		}
		
		$('#ddrSwiper').prepend(initSlides);
		
		$('#ddrSwiper').prepend(`<div class="ddrswiper__spacer ddrswiper__spacer-start" style="width: ${spaceWidth}px;"></div>`);
		$('#ddrSwiper').append(`<div class="ddrswiper__spacer ddrswiper__spacer-end" style="width: ${spaceWidth}px;"></div>`);
		$('#ddrSwiper').scrollLeft(slideWidth * currentSlide);
		
		initializeObserver();
		
		
		getSlidesData(initIndexes);
	}
		
	
	
	
	
	async function getSlidesData(indexes) {
		if (loadNewSlidesAbortCtrl instanceof AbortController) loadNewSlidesAbortCtrl.abort();
		loadNewSlidesAbortCtrl = new AbortController();
		
		const {data, error, headers, status} = await axiosQuery('get', 'site/timesheet/slides', {indexes}, 'json', loadNewSlidesAbortCtrl);
		
		if (error || !data) {
			$.notify('Ошибка! Не удалось загрузить данные слайдов!', 'error');
			console.log(error.message);
			return;
		}
		
		data.forEach((slideData) => setSlideContent(slideData));
	}
	
	
	
	
	function setSlideContent(slideData) {
		const slide = $('#ddrSwiper').find(`.ddrswiper__item[ddrswiper-index="${slideData?.index}"]`);
		
		if (!slide.length) {
			console.warn(`Слайд с индексом ${slideData?.index} не найден.`);
			return;
		}
		const currentHtml = slide.html();
		const newHtml = currentHtml.replace('[placer]', slideData?.content ?? '');
		slide.html(newHtml);
		slide.removeClass('ddrswiper__item-waiting');
	}
	
	
	
	
	$.easing.customQuad = function(x, t, b, c, d) {
		t /= d;
    	// exponent 1.5 вместо 2 — старт мягче, финиш всё так же плавен
    	return c * (1 - Math.pow(1 - t, 1.5)) + b;
	};
	
	

	
</script>