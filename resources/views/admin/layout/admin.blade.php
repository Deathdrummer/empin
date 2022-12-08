<div class="app">
	<header class="header">
		<div class="header__container">
			<div class="header__left">
				<div class="header__block">
					<div class="header__logo">
						<img
							src="{{asset('assets/images/ampin.png')}}"
							class="w15rem"
							alt="{{$company_name ?? 'ЭМПИН'}}"
							title="{{$company_name ?? 'ЭМПИН'}}"
							>
						{{-- <p>{{$settings['company_name']}}</p> --}}
						{{-- <p>{{$company_name}}</p> --}}
					</div>
					{{-- <h2>{{$user->name}}</h2> --}}
				</div>
				
			</div>
			
			<div class="header__right">
				<div class="row justify-content-between g-20">
					<div class="col-auto">
						<div class="header__block">
							<p class="header__pagetitle" id="sectionTitle"></p>
						</div>
					</div>
					{{-- <div class="col-auto">
						<div class="header__block">
							<x-localebar group="large" />
						</div>
					</div> --}}
					{{-- <div class="col-auto">
						<div class="header__block">
							@unlessverify('admin')
								<x-button id="resendVerifyLinkBtn">Выслать ссылку повторно</x-button>
							@endverify
						</div>
					</div> --}}
					<div class="col-auto ms-auto">
						<div class="header__block">
							<div class="header__logout noselect" logout>
								<i class="fa-solid fa-arrow-right-from-bracket"></i>
								<span>{{__('auth.logout')}}</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</header>
	
	<div class="content">
		<aside class="aside noselect">
			@isset($nav)
				<div class="aside__nav" id="navBlock">
					<ul id="navItems">
						@foreach($nav as $item)
							@if (!isset($item['section']))
								@continue
							@endif
							
							<li @class([
									'active' => $activeNav == $item['section'],
									'opened' => preg_match('/\b'.$item['section'].'\./', $activeNav)
								])
								parentnav
								>
								<div
									class="aside__navitem"
									@if($item['parent_id'] == 0 && $item['nav'])loadsection="{{$item['section']}}"@endif
									>
									<span>{{$item['title']}}</span>
									@isset($item['children'])
										<div class="navitemhandler">
											<i></i>
										</div>
									@endisset
								</div>
								
								@isset($item['children'])
									<ul>
										@foreach($item['children'] as $children)
											<li @class(['active' => $activeNav == $children['section']])>
												<div class="aside__navitem" @isset($children['section']) loadsection="{{$children['section']}}" @endisset>
													<span>{{$children['title']}}</span>
												</div>
											</li>
										@endforeach
									</ul>
								@endisset
							</li>
						@endforeach
					</ul>
				</div>
			@endisset
		</aside>
		
		<main class="main">
			<div id="sectionPlace" class="main__content"></div>
			
			<div class="footer">
				<div class="row g-10">
					<div class="col-auto">
						<div class="footer__block">
							{{-- <p>ЭМПИН {{date('Y')}} г.</p> --}}
						</div>
					</div>
				</div>
			</div>
		</main>
	</div>
	
</div>



@push('scripts')
<script type="module">
	let loadSectionController,
		uriSegment = getUrlSegment(1),
		startPage = '{{$admin_start_page ?? 'common'}}';
		
	loadSection(uriSegment);
	
	$('[loadsection]').on(tapEvent, function(e) {
		if (!$(this).parent('li').hasClass('active')) {
			if (loadSectionController && 'abort' in loadSectionController) loadSectionController.abort();
			let section = $(this).attr('loadsection');
			$('[loadsection]').not(this).parent('li').removeClass('active');
			$(this).parent('li').addClass('active');
			loadSection(section);
		}	
	});
	
	
	$('#navBlock').ddrFloatingBlock('#navItems');
	
	
	
	$('[parentnav]').on(tapEvent, function() {
		if ($(this).hasClass('opened')) return false;
		$('[parentnav]').not(this).removeClass('opened');
		$(this).addClass('opened');
	});
	
	
	
	$('.navitemhandler').on(tapEvent, function(e) {
		e.stopPropagation();
		if ($(this).closest('li').hasClass('opened')) return false;
		$('[parentnav]').removeClass('opened');
		$(this).closest('li').addClass('opened');
	});
	
	
	
	
	
	
	
	
	
	
	
	$('body').on(tapEvent, '[logout]', function() {
		axios.get('/admin/logout', {
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
			url: '/admin/email/verification-notification',
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
		$('#sectionTitle.header__pagetitle_visible').removeClass('header__pagetitle_visible');
		
		let loadSectionWait = $('#sectionPlace').ddrWait({
				iconHeight: '80px',
				backgroundColor: '#eceff3e6',
				iconColor: 'hue-rotate(147deg)',
				text: 'Загрузка...',
				fontSize: '18px'
			});
			
		loadSectionController = new AbortController();
		
		history.pushState(null, null, '/admin'+(section ? '/'+section : ''));
		
		//console.log(history.state['section']);
		//history.replaceState({page: 3}, "title 3", "?page=3")
		
		section = section || startPage;
		
		let getSection = axiosQuery('post', '/admin/get_section', {section}, 'text', loadSectionController);
		
		getSection.then(function ({data, error, status, headers}) {
			if (status != 200) {
				if (error.message) $.notify(error.message, 'error');
				else $.notify('Ошибка загрузки раздела!', 'error');
				$('#sectionPlace').html('');
				$('#sectionTitle').text('');
				throw new Error('loadSection -> ошибка загрузки раздела!');
			} else {
				$('#sectionPlace').html(data);
				$('#sectionTitle').html(setPageTitle(headers['x-page-title']));
			}
			
			$('#sectionTitle:not(.header__pagetitle_visible)').addClass('header__pagetitle_visible');
			$('#sectionPlace:not(.main__content_visible)').addClass('main__content_visible');
			loadSectionWait.destroy();
			
		}).catch(err => {
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
	
	
	
</script>
@endpush