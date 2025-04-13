<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<section>
	{{-- <div id="timesheetCarousel" class="swiper">
	    <div class="swiper-wrapper mt10px">
			<div class="swiper-slide">
				<x-card class="minh50rem h100 w100 rounded-6rem"><p>-4</p></x-card>
			</div>
			<div class="swiper-slide">
				<x-card class="minh50rem h100 w100 rounded-6rem"><p>-3</p></x-card>
			</div>
			<div class="swiper-slide">
				<x-card class="minh50rem h100 w100 rounded-6rem"><p>-2</p></x-card>
			</div>
			<div class="swiper-slide">
				<x-card class="minh50rem h100 w100 rounded-6rem"><p>-1</p></x-card>
			</div>
			<div class="swiper-slide">
				<x-card class="minh50rem h100 w100 rounded-6rem"><p>0</p></x-card>
			</div>
			<div class="swiper-slide">
				<x-card class="minh50rem h100 w100 rounded-6rem"><p>1</p></x-card>
			</div>
			<div class="swiper-slide">
				<x-card class="minh50rem h100 w100 rounded-6rem"><p>2</p></x-card>
			</div>
			<div class="swiper-slide">
				<x-card class="minh50rem h100 w100 rounded-6rem"><p>3</p></x-card>
			</div>
			<div class="swiper-slide">
				<x-card class="minh50rem h100 w100 rounded-6rem"><p>4</p></x-card>
			</div>
		</div>
	</div> --}}
	
	<div class="swiper" id="timesheetCarousel">
		<div class="swiper-wrapper"></div>
	</div>
</section>


<style>
    .swiper {
		width: 100%;
		height: 100%;
    }
    
    .swiper-wrapper {
		height: calc(100% - 20px);
    }
    
    .swiper-slide {
		min-htight: 500px;
		background-color: #fff;
    }
</style>


<script type="module">
	import Swiper from 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.mjs'
	
	let virtualSlides = Array.from({ length: 10 }, (_, i) => `Слайд #${i + 1}`);
	let offset = 0;
	
	
	const swiper = new Swiper('#timesheetCarousel', {
	  slidesPerView: 3,
	  spaceBetween: 20,
	  loop: false,
	  centeredSlides: true,
	  initialSlide: 2,
	  virtual: {
	    slides: virtualSlides.map(content => `<div class="swiper-slide"><p>${content}</p></div>`),
	    renderSlide: (slide, index) => `<div class="swiper-slide"><p>${slide}</p></div>`,
	  },
	  
	  on: {
	    reachEnd() {
	      console.log('достигнут конец → подгружаем новые слайды...');
	      loadMoreSlides('forward');
	    },
	    reachBeginning() {
	      console.log('достигнуто начало ← подгружаем предыдущие слайды...');
	      loadMoreSlides('backward');
	    },
	  },
	});

function loadMoreSlides(direction) {
  // Эмулируем AJAX-запрос
  setTimeout(() => {
  	
  	console.log(direction);
  	
    if (direction === 'forward') {
      const newSlides = Array.from({ length: 5 }, (_, i) => `Слайд #${virtualSlides.length + i + 1}`);
      virtualSlides = virtualSlides.concat(newSlides);
    } else if (direction === 'backward') {
      const newSlides = Array.from({ length: 5 }, (_, i) => `Слайд #${offset - i - 1}`).reverse();
      virtualSlides = newSlides.concat(virtualSlides);
      offset -= newSlides.length;
    }

    swiper.virtual.slides = virtualSlides.map(s => `<div class="swiper-slide"><p>${s}</p></div>`);
    swiper.virtual.update(true); // обновляем виртуальные слайды

    // Центрируем после добавления в начало
    if (direction === 'backward') {
      swiper.slideTo(swiper.activeIndex + 5, 0);
    }
  }, 500);
}
	
	
	
	
	
	
	

	
	
	{{-- const swiper = new Swiper('#timesheetCarousel', {
		virtual: { slides: slidesData },
			slidesPerView: 5,
			spaceBetween: 20,
		loop: false,
		centeredSlides: true,
		initialSlide: 4,
	}); --}}
</script>