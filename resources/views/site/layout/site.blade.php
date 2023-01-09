<div class="app">
	<header class="header">
		<div class="header__container">
			<div class="row justify-content-between g-20">
				<div class="col-auto">
					<div class="header__block">
						<div class="header__logo">
							<div>
								<img
									src="{{asset('assets/images/ampin.png')}}"
									alt="{{$company_name ?? 'ЭМПИН'}}"
									title="{{$company_name ?? 'ЭМПИН'}}"
									>
							</div>
							{{-- <p>{{$company_name}}</p> --}}
						</div>
					</div>
				</div>
				
				{{-- <div class="col-auto">
					<div class="header__line"></div>
				</div> --}}
				{{-- <div class="col-auto">
					<div class="header__block">
						<p class="header__pagetitle" id="sectionTitle"></p>
					</div>
				</div> --}}
				<div class="col-auto">
					<div class="header__block">
						<teleport id="headerTeleport"></teleport>
					</div>
				</div>
				{{-- <div class="col-auto ms-auto">
					<div class="header__block">
						<x-localebar group="large" />
					</div>
				</div> --}}
				{{-- <div class="col-auto">
					<div class="header__block">
						@unlessverify('site')
							<x-button id="resendVerifyLinkBtn">Выслать ссылку повторно</x-button>
						@endverify
					</div>
				</div> --}}
				
				
				
				<div class="col-auto ms-auto">
					<div class="header__block">
						<p class="fz16px "><strong>{{$user->pseudoname ?? $user->name}}</strong></p>
					</div>
				</div>
				
				
				<div class="col-auto">
					<div class="header__block">
						@if(isset($show_nav) && $show_nav)
						<div class="header__nav" headernav>
							<div class="headernav__handler" touch="header__nav_opened">
								<i class="fa-solid fa-fw fa-bars"></i>
								{{-- <p>Меню</p> --}}
							</div>
							
							
							<nav class="headernav noselect">
								<div class="headernav__item">
									@isset($nav)
										{{-- <p>sectionTitle</p> --}}
										<ul>
											@foreach($nav as $item)
												@if (!isset($item['section']))
													@continue
												@endif
												
												<li @class([
														'active' => $activeNav == $item['section'],
														'opened' => isset($item['active'])
													])
													loadsection="{{$item['section']}}"
													><span>{{$item['title']}}</span></li>	
													
											@endforeach
											
											<li class="line"></li>
											
											<teleport id="menuTeleport"></teleport>
										</ul>
									@endif
								</div>
								
								{{-- 
								{% for sectionTitle, sectionsList in sections %}
									<div class="main_nav_item">
										<p>{{sectionTitle}}</p>
										<ul>
											{% for url, title in sectionsList %}
												<li data-block="{{url}}">{% if title is iterable %}{{title.title}}{% else %}{{title}}{% endif %}</li>
											{% endfor %}
										</ul>
									</div>
								{% endfor %} --}}
							</nav>
						</div>
						@endif
						
						<div class="header__logout ml4px noselect" logout>
							<i class="fa-solid fa-fw fa-arrow-right-from-bracket"></i>
							{{-- <span>{{__('auth.logout')}}</span> --}}
						</div>
					</div>
				</div>
			</div>
		</div>
	</header>
	
	<div class="content">
		{{-- <aside class="aside noselect">
			
		</aside> --}}
		
		<main class="main">
			<div id="sectionPlace" class="main__content"></div>
			
			{{-- <div class="footer">
				<div class="row g-10">
					<div class="col-auto">
						<div class="footer__block">
							<p>ЭМПИН {{date('Y')}} г.</p>
						</div>
					</div>
				</div>
			</div> --}}
		</main>
	</div>
	
</div>



@push('scripts')
<script type="module">
	let loadSectionController,
		uriSegment = getUrlSegment(0),
		startPage = '{{$site_start_page ?? 'common'}}',
		teleportEements = [];
	
	loadSection(uriSegment);
	
	$('[loadsection]').on(tapEvent, function(e) {
		if (!$(this).hasClass('active')) {
			if (loadSectionController && 'abort' in loadSectionController) loadSectionController.abort();
			let section = $(this).attr('loadsection');
			$('[loadsection]').not(this).removeClass('active');
			$(this).addClass('active');
			loadSection(section);
		}	
	});
	
	
	
	
	
	$('body').on(tapEvent, '[logout]', function() {
		axios.get('/logout', {
			responseType: 'json'
		}).then(function ({data, status, statusText, headers, config}) {
			if (data.logout) pageReload();	
		});
	});
	
	
	
	
	
	
	
	let ddrInput = $('#singleFile').ddrInputs('file', {
		compress: {
			quality: 0.5,
			width: 200,
			height: 200,
			//minWidth: 0,
			//minHeigh: 0,
			//maxWidth: 'infinity',
			//maxHeight: 'infinity',
			resize: 'contain', // none, contain,  cover.
		},
		background: '#e2e6ed',
		before: function({selector, count}) {
			let fileBlock = $(selector).closest('.file');
			//$(fileBlock).find('[ddrfileimage]').html('<i class="fa-solid fa-spinner fa-spin-pulse"></i>');
			$(fileBlock).find('[ddrfileimage]').html('<img class="ddr-file__image_loading ddr-file__image_loading-gray-70 fa-spin" style="--fa-animation-duration: 7s;" src="assets/images/loading.gif" />');
		},
		callback: function({selector, file, ext, preview, key, name, size, type}, complete) {
			if (_.indexOf(['png', 'apng', 'jpeg', 'jpg', 'gif', 'bmp', 'webp'], ext) >= 0) {
				$('#singleFileBlock').append('<img key="'+key+'" src="'+preview+'" />');
			} else {
				$('#singleFileBlock').append('<img key="'+key+'" src="assets/images/filetypes/'+ext+'.png" />');
			}
			
			/*let fileBlock = $(selector).closest('.file');
			
			if (_.indexOf(['png', 'apng', 'jpeg', 'jpg', 'gif', 'bmp', 'webp'], ext) >= 0) {
				$(fileBlock).find('[ddrfileimage]').html('<img src="'+src+'" />');
			} else {
				$(fileBlock).find('[ddrfileimage]').html('<img src="assets/images/filetypes/'+ext+'.png" />');
			}*/
		},
		fail: function(selector, err) {
			//console.log('fail', err);
			//let fileBlock = $(selector).closest('.file');
			//$(fileBlock).find('[ddrfileimage]').empty();
		}/*,
		proxy(data) {
			files = data;
		}*/
	});
	
	
	$('button').on(tapEvent, function() {
		//let formData = ddrInput.getFormFiles();
		//console.log(typeof formData, formData.get('ddrfile'));
		$('#ddrForm').ddrFormSubmit({url: 'file', files: {name: 'ddrfile', items: ddrInput.getFormFiles()}});
	
		//$.ddrFormSubmit({fields: 'rtertert'});
		
		
	});
	
	
	$('#singleFileBlock').on(tapEvent, 'img', function() {
		let key = $(this).attr('key');
		ddrInput.removeFile(key);
		$(this).remove();
	});
	
	
	
	
	
	

	
	
	$('body').on(tapEvent, '#resendVerifyLinkBtn', function() {
		let btn = $(this).closest('.button');
		let ddrWait = $(btn).ddrWait({iconHeight: '25px', backgroundColor: '#fffc', iconColor: 'hue-rotate(170deg)'});
		
		$.ddrFormSubmit({
			url: '/email/verification-notification',
			callback({sending = null, errors = null, message = null, status = null}, stat, headers) {
				
				if (sending) {
					$.notify(sending);
				}
				
				if (message) {
					$.notify(message, 'error');
				}
				
				ddrWait.destroy();
			},
			fail(data, status) {
				console.log(data, status);
				ddrWait.destroy();
			}
		});
	});
	
	
	
	
	
	
	
	
	
	
	
	
	//-----------------------------------------------------------------------------------------------------
	
	
	
	function loadSection(section = null) {
		$('#sectionPlace.main__content_visible').removeClass('main__content_visible');
		//$('#sectionTitle.header__pagetitle_visible').removeClass('header__pagetitle_visible');
		
		let loadSectionWait = $('#sectionPlace').ddrWait({
				iconHeight: '80px',
				backgroundColor: '#eceff3e6',
				iconColor: 'hue-rotate(147deg)',
				text: 'Загрузка...',
				fontSize: '18px'
			});
			
		loadSectionController = new AbortController();
		
		history.pushState(null, null, (section ? '/'+section : ''));
		
		//console.log(history.state['section']);
		//history.replaceState({page: 3}, "title 3", "?page=3")
		
		section = section || startPage;
		
		let getSection = axiosQuery('post', '/get_section', {section});
		
		closeNav();
		removeTeleports();
		
		getSection.then(function ({data, error, status, headers}) {
			if (error || status != 200) {
				if (error.message) $.notify(error.message, 'error');
				else $.notify('Ошибка загрузки раздела!', 'error');
				$('#sectionPlace').html('');
				//$('#sectionTitle').text('');
				throw new Error('loadSection -> ошибка загрузки раздела!');
			} else {
				const dataDom = buildTeleports(data);
				$('#sectionPlace').html(dataDom);
				//$('#sectionTitle').html(setPageTitle(headers['x-page-title']));
			}
			
			//$('#sectionTitle:not(.header__pagetitle_visible)').addClass('header__pagetitle_visible');
			$('#sectionPlace:not(.main__content_visible)').addClass('main__content_visible');
			loadSectionWait.destroy();
			
			
		}).catch(err => {
			closeNav();
			
			if (axios.isCancel(err)) {
				console.log('Request canceled');
			} else {
				//$.notify('Ошибка загрузки раздела 3', 'error');
			}
			
			loadSectionWait.destroy();
		});
		
		
		
		
		/*window.onpopstate = function(event) {
		  console.log(event.state);
		}*/
	}
	
	
	
	
	
	// Извлечь из HTML блоки для телепортации, вставить телепорты и вернуть HTML без телепортов
	function buildTeleports(data = null) {
		if (_.isNull(data)) return;
		let dataDom = $(data);
		let teleports = $(dataDom).find('[teleport]');
		if (teleports.length == 0) return data;
		
		$(dataDom).find('[teleport]').remove();
		
		const hasPleces = {};
		
		$.each(teleports, function(k, teleport) {
			let to = $(teleport).attr('teleport');
			
			if ($(to)[0] && teleport) {
				teleportEements.push({
					placement: $(to)[0].outerHTML,
					data: teleport
				});
			}
			
			$(teleport).removeAttrib('teleport');
			
			if (Object.keys(hasPleces).includes(to)) $(hasPleces[to]).after(teleport);
			else $(to).replaceWith(teleport);
			hasPleces[to] = teleport;
		});
		
		return dataDom;
	}
	
	
	function removeTeleports() {
		$.each(teleportEements, function(k, {placement, data}) {
			$(data).replaceWith(placement);
		});
		//$(teleportEements).remove();
		teleportEements = [];
		//
		//$(document).find('[teleport]').remove();
	}
	
	
	function getUrlSegment(index = 0) {
		let segments = location.pathname.substr(1).split('/');
		if (index == 'last') return segments.pop();
		if (segments[index] != 'undefined') return segments[index];
		return null;
	}
	
	
	
	function setPageTitle(titles = null) {
		if (!titles) return '';
		let titlesData = JSON.parse(titles);
		if (typeof titlesData == 'string') return JSON.parse(titles);
		let allTitlesString = '';
		titlesData.forEach((title, k) => {
			if (k+1 < titlesData.length) allTitlesString += '<span class="color-gray">'+title+' / </span>';
			else allTitlesString += '<span class="color-black">'+title+'</span>';
		});
		return allTitlesString;
	}
	
	
	
	function closeNav() {
		$('[headernav].header__nav_opened').removeClass('header__nav_opened');
		$('[touch="header__nav_opened"]').attr('aria-expanded', 'false');
	}
	
	
</script>
@endpush