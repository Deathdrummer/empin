@aware([
	'groupWrap'	=> null,
])

@props([
	'id' 	=> 'localebar'.rand(0,9999999),
	'group'	=> $groupWrap
])

<div id="{{$id}}" {{$attributes->class(['localebar', $group.'-localebar' => $group, 'noselect'])}}>
	<div @class([
			'localebar__handler',
			$group.'-localebar__handler' => $group
		])
		touch="{{($group ? $group.'-' : '')}}localebar_opened">
		
		<div><img src="{{asset('assets/images/flags/'.$currentLocale.'.png')}}" alt="{{$locales[$currentLocale]}}"></div>
		<span class="localebar__label">{{$locales[$currentLocale]}}</span>
		<i class="fa-solid fa-chevron-down"></i>
	</div>
	
	<div @class([
			'localebar__dropdown',
			$group.'-localebar__dropdown' => $group
		])>
		<ul @class([
				'localebar__list',
				$group.'-localebar__list' => $group
			])>
			@foreach($locales as $locale => $title)
				<li value="{{$locale}}" @class(['active' => $currentLocale == $locale]) chooselocale="{{$locale}}">
					<div><img src="{{asset('assets/images/flags/'.$locale.'.png')}}" alt="{{$title}}"></div>
				 	<span>{{$title}}</span>
				 </li>
			@endforeach
		</ul>
	</div>
</div>


<script type="module">
	const selector = '#{{$id}}',
		readyCls = '{{($group ? $group.'-' : '')}}localebar_ready',
		openedCls = '{{($group ? $group.'-' : '')}}localebar_opened';
	
	
	setTimeout(() => {
		$(selector).addClass(readyCls);
	}, 500);
	
	
	$(selector).find('[chooselocale]').on(tapEvent, function() {
		if ($(this).hasClass('active')) return false;
		let locale = $(this).attr('chooselocale');
		
		$(selector).removeClass(openedCls);
		$(selector).find('[touch]').attr('aria-expanded', false);
		
		axios.post('/admin/lang', {locale}, {
			responseType: 'json'
		}).then(function ({data, status, statusText, headers, config}) {
			if (data?.status) {
				$.notify(data?.message, 'error');
			} else if (status == 200) {
				pageReload();
			}
		}).catch(err => {
			console.log(err);
		});
	});
	
</script>