$.fn.ddrSwiper = function(options) {
	return this.each(function() {
		const instance = ddrSwiper({
			selector: this,
			...options
		});
		$(this).data('ddrSwiper', instance); // Сохраняем экземпляр
	});
};






/**
 * ddrSwiper — jQuery-плагин горизонтального свайпера с адаптивом и кастомной загрузкой данных.
 *
 * Пример инициализации:
 *
 * $('#mySlider').ddrSwiper({
 *     slidesPerView: 6,
 *     slidesCount: 40,
 *     responsive: [
 *         { breakpoint: 1200, slidesPerView: 4 },
 *         { breakpoint: 900, slidesPerView: 2 },
 *         { breakpoint: 600, slidesPerView: 1 }
 *     ],
 *     template: 'timesheet.slide',
 *     loadSlidesData: async function(indexes) {
 *         // Вернуть фунцию {data, error} или объект с данными
 *         return await axiosQuery('get', '/api/slides', {indexes}, 'json');
 *     },
 *     onInit: function() { console.log('Слайдер готов'); },
 *     onChange: function(idx) { console.log('Сменился слайд:', idx); },
 *     onLoadData: function(newIndexes) { console.log('Загружены новые данные:', newIndexes); }
 * });
 *
 * ------------------- Опции -------------------
 * selector           — селектор или DOM-элемент (ставится автоматически)
 * centerSlide        — 'center' или индекс стартового слайда (по умолчанию 'center')
 * slidesPerView      — видимых слайдов (по умолчанию 5)
 * slidesCount        — общее кол-во слайдов (по умолчанию 21)
 * loadNewOffset      — кол-во догружаемых слайдов (по умолчанию 10)
 * slideBlank         — HTML шаблон с [placer] для вставки данных
 * responsive         — массив [{breakpoint, slidesPerView}, ...] для адаптива
 * template           — название шаблона для рендера (например, 'timesheet.slide')
 * loadSlidesData     — function(indexes, abortCtrl) или массив/объект слайдов.
 *                      - функция должна вернуть {data, error}
 *                      - если передан массив/объект, используется как есть
 * onInit             — коллбэк при инициализации (function)
 * onChange           — коллбэк при смене активного слайда (function)
 * onLoadData         — коллбэк после загрузки новых данных (function)
 *
 * ------------------- Методы -------------------
 * $('#mySlider').data('ddrSwiper') возвращает объект с методами:
 *   scrollTo(idx)         — прокрутка к слайду с индексом idx
 *   reload()              — пересобрать слайдер
 *   getCurrentIndex()     — вернуть текущий индекс активного слайда
 *   destroy()             — очистить слайдер, отвязать обработчики
 *
 * ------------------- Важное -------------------
 * - Чтобы заблокировать свайп по элементу, добавь ему атрибут [noswipe].
 * - Если не передан loadSlidesData, ничего не загрузится (ошибка).
 * - slidesPerView меняется автоматически при ресайзе окна (если задан responsive).
 */









function ddrSwiper(options) {
	const defaults = {
		selector: '#ddrSwiper',
		centerSlide: 'center',
		slidesPerView: 5,
		slidesCount: 21,
		loadNewOffset: 10,
		slideBlank: `<div class="card minh50rem h100 w100 rounded-5rem">[placer]</div>`,
		responsive: [], // новый параметр!
		template: null,
		
		onInit: null,
		onChange: null,
		onLoadData: null,
	};
	const config = { ...defaults, ...options };
	
	
	function getSlidesPerView() {
	    let slides = config.slidesPerView; // базовое
	    if (Array.isArray(config.responsive)) {
	        const width = window.innerWidth;
	        for (let i = 0; i < config.responsive.length; i++) {
	            const rule = config.responsive[i];
	            if (width <= rule.breakpoint) {
	                slides = rule.slidesPerView;
	            }
	        }
	    }
	    return slides;
	}

	const $swiper = $(config.selector instanceof Element ? config.selector : config.selector);
	if ($swiper.length === 0) {
		console.warn(`ddrSwiper: контейнер ${config.selector} не найден`);
		return;
	}
	const swiperContainer = $swiper[0];

	let slideWidth = swiperContainer.offsetWidth / getSlidesPerView();
	let swipeStat = false;
	let countSlides = config.slidesCount;
	let slidesShift = 0;
	let currentSlideSelector = null;
	let currentSlide = config.centerSlide === 'center'
		? Math.ceil(config.slidesCount / 2) - 1
		: (config.centerSlide >= 0
			? (config.centerSlide > config.slidesCount ? config.slidesCount : config.centerSlide)
			: 0);
	let spaceWidth = slideWidth * Math.floor(getSlidesPerView() / 2);
	let containerWidth = swiperContainer.offsetWidth;
	let observer = null;
	let loadNewSlidesAbortCtrl;
	let resizeTimeout = null;
	let lastSlidesPerView = getSlidesPerView();

	// ------------------------ Инициализация слайдов ------------------------
	function initSlides() {
		ddrCssVar('slide-width', `calc(100% / ${getSlidesPerView()})`);
		const initSlides = [],
			  initIndex = config.centerSlide === 'center' ? -Math.floor(config.slidesCount / 2) : 0,
			  sCount = config.centerSlide === 'center' ? Math.ceil(config.slidesCount / 2) : config.slidesCount,
			  initIndexes = [];
		for (let i = initIndex; i < sCount; i++) {
			initSlides.push(buildSlide(i));
			initIndexes.push(i);
		}
		$swiper.prepend(initSlides);
		$swiper.prepend(`<div class="ddrswiper__spacer ddrswiper__spacer-start" style="width: ${spaceWidth}px;"></div>`);
		$swiper.append(`<div class="ddrswiper__spacer ddrswiper__spacer-end" style="width: ${spaceWidth}px;"></div>`);
		$swiper.scrollLeft(slideWidth * currentSlide);
		initializeObserver();
		getSlidesData(initIndexes);
	}

	// ------------------------ Observer ------------------------
	function initializeObserver() {
		if (observer) observer.disconnect();
		const swiperItems = swiperContainer.querySelectorAll('.ddrswiper__item');
		containerWidth = swiperContainer.offsetWidth;
		observer = new IntersectionObserver((entries) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					const intersectingItem = entry.target;
					const itemIndex = Array.from(swiperItems).indexOf(intersectingItem);
					const slideStep = itemIndex - currentSlide;
					if (swipeStat) slidesShift += slideStep;
					$swiper.trigger('ddrSetCurrentSlide', [itemIndex, intersectingItem, slideStep]);
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

	// ------------------------ Построение слайда ------------------------
	function buildSlide(index = 0) {
		return `<div class="ddrswiper__item ddrswiper__item-waiting" ddrswiper-index="${index}" ddrswiperitem>
			<div class="ddrswiper__waiting" ddrswiperwaiting>
				<div>
					<img src="/assets/images/loading.gif" notouch />
					<p>Загрузка...</p>
				</div>
			</div>
			${config.slideBlank || ''}
		</div>`;
	}

	// ------------------------ Получение данных для слайдов ------------------------
	async function getSlidesData(indexes) {
	    if (loadNewSlidesAbortCtrl instanceof AbortController) loadNewSlidesAbortCtrl.abort();
	    loadNewSlidesAbortCtrl = new AbortController();

	    let response;

	    if (typeof config.loadSlidesData === 'function') {
	        response = await config.loadSlidesData(indexes, loadNewSlidesAbortCtrl);
	    } else if (Array.isArray(config.loadSlidesData) || typeof config.loadSlidesData === 'object') {
	        response = {
	            data: config.loadSlidesData,
	            error: false
	        };
	    } else {
	        $.notify('Ошибка! Не передан источник данных для слайдов!', 'error');
	        return;
	    }

	    const {data, error} = response || {};
	    if (error || !data) {
	        $.notify('Ошибка! Не удалось загрузить данные слайдов!', 'error');
	        console.log(error?.message || error);
	        return;
	    }
	    data.forEach((slideData) => setSlideContent(slideData));
	}



	async function setSlideContent({index, ...data}) {
	    const slide = $swiper.find(`.ddrswiper__item[ddrswiper-index="${index}"]`);
	    if (!slide.length) return;

	    let $slideContent;
	    if (config.template) {
	        $slideContent = await ddrRenderWithEvents(config.template, data);
	    
	    } else if (typeof config.render === 'function') {
	        // config.render должен вернуть html/DOM или jQuery-объект!
	        $slideContent = await config.render(data, index, $swiper);
		} else if (typeof config.render === 'object') {
	        const {template, actions = null} = config?.render;
	        
			const ddrRenderWrapFn = ddrRenderWrap({
				template: template,
				middleware: async (selector, vars, abortCtrl) => {
					return data;
				},
				actions: actions,
				render: (selector, html) => {
					return html;
				}
			});
			
			$slideContent = await ddrRenderWrapFn($('<div></div>'));
		
	    } else {
	        console.error('ddrSwiper: не передан ни render, ни template!');
	        return;
	    }

	    const $placer = slide.find('[placer]');
	    $placer.empty().append($slideContent);
	    slide.removeClass('ddrswiper__item-waiting');
	}


	// ------------------------ Добавление слайдов ------------------------
	function addSlides(count) {
		if (count === 0) return;
		const firstSlide = $swiper.find('.ddrswiper__item').first();
		const lastSlide = $swiper.find('.ddrswiper__item').last();
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
		$swiper.trigger('ddrLoadSlidesData', [newIndexes]);
	}

	// ------------------------ Удаление слайдов ------------------------
	function removeSlides(count = 0) {
		if (count === 0) return;
		if (count > 0) $swiper.find('.ddrswiper__item').slice(0, count).remove();
		else if (count < 0) $swiper.find('.ddrswiper__item').slice(count).remove();
	}

	// ------------------------ Установка позиции ------------------------
	function setPosition(count) {
		if (count === 0) return;
		let scrollPos = $swiper.scrollLeft();
		$swiper.scrollLeft(scrollPos - (slideWidth * count));
	}

	// ------------------------ Получение X-координаты ------------------------
	function getPageX(e) {
		if (e.type.startsWith('touch')) {
			const touch = e.originalEvent.touches[0] || e.originalEvent.changedTouches[0];
			return touch.pageX;
		}
		return e.pageX;
	}

	// ------------------------ Навешивание событий ------------------------
	$swiper.off('mousedown.ddrscroll touchstart.ddrscroll').on('mousedown.ddrscroll touchstart.ddrscroll', function(e) {
	    if ($(e.target).closest('[noswipe]', this).length) {
	        return; // Прерываем обработку свайпа
	    }
	    
	    // Игнорируем второе событие, если уже идет свайп
	    if (swipeStat) return;

	    let isTouch = e.type === 'touchstart';
	    swipeStat = true;
	    countSlides = $swiper.find('.ddrswiper__item').length;
	    $swiper.stop(true);

	    let $item = $(this);
	    let startX = getPageX(e);
	    let initialLeft = parseInt($item.css('left')) || 0;
	    let scrollLeft = $swiper.scrollLeft();
	    let startSpaceWidth = $swiper.find('.ddrswiper__spacer-start').outerWidth();
	    let endSpaceWidth = $swiper.find('.ddrswiper__spacer-end').outerWidth();
	    let scrLeftEnd = $swiper.get(0).scrollWidth - swiperContainer.offsetWidth;
	    let fixLeft = false, fixRight = false;

	    // Обработчики move и end
	    function onMove(e) {
	        let pageX = getPageX(e);
	        let delta = pageX - startX;
	        let edgeShift;
	        let scrLeft = $swiper.scrollLeft();
	        if (scrLeft === 0) {
	            if (fixLeft === false) fixLeft = pageX;
	            edgeShift = pageX - fixLeft;
	            $swiper.find('.ddrswiper__spacer-start').css('width', startSpaceWidth + (edgeShift / 6));
	        }
	        if (scrLeft >= scrLeftEnd) {
	            if (fixRight === false) fixRight = pageX;
	            edgeShift = fixRight - pageX;
	            $swiper.find('.ddrswiper__spacer-end').css('width', endSpaceWidth + (edgeShift / 6));
	        }
	        $swiper.scrollLeft(scrollLeft - (initialLeft + delta));
	    }

	    function onEnd(e) {
	        // Снимаем обработчики
	        if (isTouch) {
	            $(document).off('touchmove.ddrscroll', onMove);
	            $(document).off('touchend.ddrscroll touchcancel.ddrscroll', onEnd);
	        } else {
	            $(document).off('mousemove.ddrscroll', onMove);
	            $(document).off('mouseup.ddrscroll', onEnd);
	        }

	        $swiper.find('.ddrswiper__spacer-start').stop(true, false).animate({width: startSpaceWidth}, 100);
	        $swiper.find('.ddrswiper__spacer-end').stop(true, false).animate({width: endSpaceWidth}, 100);
	        let scrollLeft = $swiper.scrollLeft() + (slideWidth / 2),
	            scrollSlides = Math.floor(scrollLeft / slideWidth);
	        $swiper.animate({scrollLeft: slideWidth * scrollSlides}, {
	            duration: 200,
	            easing: 'customQuad',
	            complete: () => {
	                $swiper.trigger('ddrTransitionEnd', [slidesShift]);
	                slidesShift = 0;
	            }
	        });
	        swipeStat = false;
	    }

	    // Навешиваем только нужные обработчики
	    if (isTouch) {
	        $(document).on('touchmove.ddrscroll', onMove);
	        $(document).on('touchend.ddrscroll touchcancel.ddrscroll', onEnd);
	    } else {
	        $(document).on('mousemove.ddrscroll', onMove);
	        $(document).on('mouseup.ddrscroll', onEnd);
	    }

	    e.preventDefault();
	});


	// ------------------------ Триггеры переходов и смены слайда ------------------------
	$swiper.on('ddrTransitionEnd', (e, slidesShift) => {
		$swiper.find('.ddrswiper__item.ddrswiper__item-current').removeClass('ddrswiper__item-current');
		$(currentSlideSelector).addClass('ddrswiper__item-current');
		if (slidesShift == 0) return;
		let offset = 0;
		if (config.slidesCount != countSlides) console.warn(`количество слайдов отличается от изначального! Было: ${config.slidesCount} Стало: ${countSlides}`);
		if (slidesShift > 0) {
			offset = config.loadNewOffset - (config.slidesCount - currentSlide - 1);
		} else if (slidesShift < 0) {
			offset = -(config.loadNewOffset - currentSlide);
		}
		if (offset != 0) {
			removeSlides(offset);
			addSlides(offset);
			setPosition(offset);
			initializeObserver();
		}
	});

	$swiper.on('ddrSetCurrentSlide', (e, index, slide, slideOffset) => {
		if (!swipeStat) {
			$swiper.find('.ddrswiper__item.ddrswiper__item-current').removeClass('ddrswiper__item-current');
			$(slide).addClass('ddrswiper__item-current');
		}
		
		if (typeof config.onChange === 'function') {
			config.onChange(index, slide, slideOffset);
		}
	});

	// ------------------------ Пользовательский коллбэк на загрузку новых данных ------------------------
	$swiper.on('ddrLoadSlidesData', (e, newIndexes) => {
		if (typeof config.onLoadData === 'function') {
			config.onLoadData(newIndexes);
		}
	});

	// ------------------------ Кастомное ускорение ------------------------
	$.easing.customQuad = function(x, t, b, c, d) {
		t /= d;
		return c * (1 - Math.pow(1 - t, 1.5)) + b;
	};
	
	
	
	if (typeof config.onInit === 'function') {
		config.onInit();
	}



	// ------------------------ Запуск ------------------------
	initSlides();
	
	
	// Адаптивность — пересчет при resize
	$(window).on('resize.ddrSwiper', function() {
		clearTimeout(resizeTimeout);
		resizeTimeout = setTimeout(onResize, 150); // debounce 150мс
	});

	function onResize() {
	    const currentSlidesPerView = getSlidesPerView();
	    if (currentSlidesPerView !== lastSlidesPerView) {
	        // 1. Запомнить индекс текущего активного слайда
	        let prevIndex = currentSlide;

	        // 2. Перестроить слайдер
	        $swiper.empty();
	        lastSlidesPerView = currentSlidesPerView;
	        initSlides();

	        // 3. После перестройки — прокрутить к нужному индексу (или последнему)
	        setTimeout(() => {
	            const $slides = $swiper.find('.ddrswiper__item');
	            let maxIndex = Math.max(0, $slides.length - 1);

	            // Если был выбран индекс больше, чем стало слайдов — берем последний
	            let targetIndex = prevIndex > maxIndex ? maxIndex : prevIndex;

	            const slide = $swiper.find(`.ddrswiper__item[ddrswiper-index="${targetIndex}"]`);
	            if (slide.length) {
	                slideWidth = swiperContainer.offsetWidth / getSlidesPerView();
	                let targetScrollLeft = slideWidth * targetIndex;

	                // Если только один слайд видим, всегда скроллим в начало
	                if (currentSlidesPerView === 1) {
	                    $swiper.scrollLeft(targetScrollLeft);
	                } else {
	                    // Центрируем как обычно
	                    $swiper.scrollLeft(targetScrollLeft);
	                }

	                // Отмечаем активный
	                $swiper.find('.ddrswiper__item.ddrswiper__item-current').removeClass('ddrswiper__item-current');
	                slide.addClass('ddrswiper__item-current');
	                currentSlide = targetIndex;
	                currentSlideSelector = slide[0];
	            }
	        }, 0);

	        return;
	    }

	    // Если slidesPerView не изменился — просто пересчитываем размеры и позицию
	    slideWidth = swiperContainer.offsetWidth / currentSlidesPerView;
	    spaceWidth = slideWidth * Math.floor(currentSlidesPerView / 2);
	    containerWidth = swiperContainer.offsetWidth;

	    // spacer-элементы
	    $swiper.find('.ddrswiper__spacer-start, .ddrswiper__spacer-end')
	        .css('width', spaceWidth + 'px');

	    // Тоже: если slidesPerView === 1, скроллим просто к текущему
	    if (currentSlidesPerView === 1) {
	        $swiper.scrollLeft(slideWidth * currentSlide);
	    } else {
	        $swiper.scrollLeft(slideWidth * currentSlide);
	    }

	    initializeObserver();
	}



	
	
	return {
		scrollTo: function(idx) {
		    // Проверяем, существует ли такой слайд
		    const slide = $swiper.find(`.ddrswiper__item[ddrswiper-index="${idx}"]`);
		    if (!slide.length) return;

		    // Прокрутка с анимацией
		    let targetScrollLeft = slideWidth * idx;
		    $swiper.animate({scrollLeft: targetScrollLeft}, {
		        duration: 200,
		        easing: 'customQuad',
		        complete: () => {
		            // Установим активный слайд вручную
		            $swiper.find('.ddrswiper__item.ddrswiper__item-current').removeClass('ddrswiper__item-current');
		            slide.addClass('ddrswiper__item-current');
		            currentSlide = idx;
		            currentSlideSelector = slide[0];
		        }
		    });
		},
		reload: function() {
    	// Убрать всё из контейнера
		    $swiper.empty();
		    // Перезапустить инициализацию
		    initSlides();
		},
		getCurrentIndex: function() { return currentSlide; },
		destroy: function() {
		$(window).off('resize.ddrSwiper');
			if (observer) {
				observer.disconnect();
				observer = null;
			}
			$swiper.off('.ddrscroll');
			$swiper.off('ddrTransitionEnd');
			$swiper.off('ddrSetCurrentSlide');
			$swiper.off('ddrLoadSlidesData');
			$swiper.empty();
			$swiper.removeData('ddrSwiper');
		}
	};
}
