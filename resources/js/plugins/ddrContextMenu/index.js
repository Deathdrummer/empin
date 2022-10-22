import './index.css';




$(document).on('contextmenu', '[contextmenu]', function(e) {
	
	let menuHtml = '';
		menuHtml += '<ul class="context noselect">';
		menuHtml +=		'<li class="parent">';
		menuHtml += 		'<i class="icon fa-fw fa-solid fa-triangle-exclamation"></i>';
		menuHtml += 		'<p>Пункт меню 1</p>';
		menuHtml += 		'<i class="f fa-solid fa-chevron-right"></i>';
		menuHtml += 		'<ul class="context sub">';
		menuHtml += 			'<li>';
		menuHtml += 				'<i class="icon fa-fw fa-solid fa-download"></i>';
		menuHtml += 				'<p>Подпункт меню 1</p>';
		menuHtml += 			'</li>';
		menuHtml += 			'<li>';
		menuHtml += 				'<i class="icon fa-fw fa-solid fa-magnifying-glass"></i>';
		menuHtml += 				'<p>Подпункт меню 2</p>';
		menuHtml += 			'</li>';
		menuHtml += 			'<li>';
		menuHtml += 				'<i class="icon fa-fw fa-brands fa-discord"></i>';
		menuHtml += 				'<p>Подпункт меню 3</p>';
		menuHtml += 			'</li>';
		menuHtml += 		'</ul>';
		menuHtml += 	'</li>';
		menuHtml += 	'<li class="divline"></li>';
		menuHtml += 	'<li class="hilight">';
		menuHtml += 		'<i class="icon fa-fw fa-solid fa-download"></i>';
		menuHtml += 		'<p>Пункт меню 2</p>';
		menuHtml += 	'</li>';
		menuHtml +=		'<li class="parent">';
		menuHtml += 		'<i class="icon fa-fw fa-solid fa-triangle-exclamation"></i>';
		menuHtml += 		'<p>Пункт меню 3</p>';
		menuHtml += 		'<i class="f fa-solid fa-chevron-right"></i>';
		menuHtml += 		'<ul class="context sub">';
		menuHtml += 			'<li>';
		menuHtml += 				'<i class="icon fa-fw fa-solid fa-download"></i>';
		menuHtml += 				'<p>Подпункт меню 1</p>';
		menuHtml += 			'</li>';
		menuHtml += 			'<li>';
		menuHtml += 				'<i class="icon fa-fw fa-solid fa-magnifying-glass"></i>';
		menuHtml += 				'<p>Подпункт меню 2</p>';
		menuHtml += 			'</li>';
		menuHtml += 			'<li>';
		menuHtml += 				'<i class="icon fa-fw fa-brands fa-discord"></i>';
		menuHtml += 				'<p>Подпункт меню 3</p>';
		menuHtml += 			'</li>';
		menuHtml += 		'</ul>';
		menuHtml += 	'</li>';
		menuHtml += 	'<li class="divline"></li>';
		menuHtml += 	'<li class="hilight">';
		menuHtml += 		'<i class="icon fa-fw fa-solid fa-download"></i>';
		menuHtml += 		'<p>Пункт меню 4</p>';
		menuHtml += 	'</li>';
		menuHtml += 	'<li>';
		menuHtml += 		'<i class="icon fa-fw fa-brands fa-slack"></i>';
		menuHtml += 		'<p>Пункт меню 5</p>';
		menuHtml += 	'</li>';
		menuHtml += 	'<li>';
		menuHtml += 		'<i class="icon fa-fw fa-brands fa-slack"></i>';
		menuHtml += 		'<p>Пункт меню 6</p>';
		menuHtml += 	'</li>';
		menuHtml +=	'</ul>';
	
	
	if ($('body').find('.context').length) {
		$('body').find('.context').remove();
	}
	
	$('body').append(menuHtml);
	
	
	let $doc = $(document),
		$context = $(".context:not(.sub)"),
		$window = $(window),
		$sub = $context.find(".sub");
		
		$sub.removeClass("oppositeX oppositeY");
		
		e.preventDefault();
		
		let w = $context.width(),
			h = $context.height(),
			x = e.clientX,
			y = e.clientY,
			ww = $window.width(),
			wh = $window.height(),
			padx = 30,
			pady = 20,
			fx = x,
			fy = y,
			hitsRight = ( x + w >= ww - padx ),
			hitsBottom = ( y + h >= wh - pady );
		
		if (hitsRight) {
			fx = ww - w - padx;
		}
		
		if ( hitsBottom ) {
			fy = wh - h - pady;
		}
		
		$context.css({
			left: fx - 1,
			top: fy - 1
		});        
		
		let sw = $sub.width(),
			sh = $sub.height(),
			sx = $sub.offset().left,
			sy = $sub.offset().top,
			subHitsRight = ( sx + sw - padx >= ww - padx ),
			subHitsBottom = ( sy + sh - pady >= wh - pady );
		
		if(subHitsRight) {
			$sub.addClass("oppositeX");
		}
		
		if(subHitsBottom) {
			$sub.addClass("oppositeY");
		}
		
		$context.addClass("is-visible");
		
		
		
		$doc.on("mousedown", function(e) {
			console.log('0');
			let $tar = $(e.target);
			
			if (!$tar.is( $context ) && !$tar.closest(".context").length) {     
				$context.removeClass("is-visible");
				setTimeout(function() {
					$context.remove();
				}, 50);
				$doc.off(e);
			}
		});
	

	
	$context.on("mousedown mouseup touchstart touchend", "li:not(.nope):not(.parent)", function(e) {
		
		e.stopPropagation();
		if (e.which !== 1) return;
		
		if (hasIn(['mousedown', 'touchstart'], e.type) !== false) {
			console.log('d');
			$(this).addClass("active");
		} else if (hasIn(['mouseup', 'touchend'], e.type) !== false) {
			$(this).removeClass("active");
			console.log('u');
		}
		
		
		
		console.log('1');
		/*if (e.which === 1) {
			let $item = $(this);
			$item.removeClass("active");
			setTimeout(function() {
				$item.addClass("active");
			}, 10);
		}*/
	});
});



























$(function() {
   return;
   
   
	var $doc = $(document),
		$context;
	
	
	$doc.on( "contextmenu", function(e) {
		
		$context = $(".context:not(.sub)");
		
		
		
		var $window = $(window),
			$sub = $context.find(".sub");
		
		console.log($context, $sub);
		
		
		
		
		
		$sub.removeClass("oppositeX oppositeY");
		
		e.preventDefault();
		
		var w = $context.width();
		var h = $context.height();
		var x = e.clientX;
		var y = e.clientY;
		var ww = $window.width();
		var wh = $window.height();
		var padx = 30;
		var pady = 20;
		var fx = x;
		var fy = y;
		var hitsRight = ( x + w >= ww - padx );
		var hitsBottom = ( y + h >= wh - pady );
		
		if ( hitsRight ) {
			fx = ww - w - padx;
		}
		
		if ( hitsBottom ) {
			fy = wh - h - pady;
		}
		
		$context
			.css({
				left: fx - 1,
				top: fy - 1
			});        
		
		var sw = $sub.width();
		var sh = $sub.height();
		var sx = $sub.offset().left;
		var sy = $sub.offset().top;
		var subHitsRight = ( sx + sw - padx >= ww - padx );
		var subHitsBottom = ( sy + sh - pady >= wh - pady );
		
		if( subHitsRight ) {
			$sub.addClass("oppositeX");
		}
		
		if( subHitsBottom ) {
			$sub.addClass("oppositeY");
		}
		
		$context.addClass("is-visible");
		
		
		$doc.on("mousedown", function(e) {
			var $tar = $(e.target);
			
			if(!$tar.is( $context ) && !$tar.closest(".context").length) {     
				$context.removeClass("is-visible");
				$doc.off(e);
			}
		});
	
	});
	
	$context.on("mousedown touchstart", "li:not(.nope)", function(e) {
		if( e.which === 1 ) {

			var $item = $(this);

			$item.removeClass("active");

			setTimeout(function() {
				$item.addClass("active");
			}, 10);
			
		}
	});
	
	
});


