<header class="header">
			header 
			
			{{-- <img src="{{asset('files/image.jpg')}}" style="width:300px;" alt=""> --}}
		</header>
		<div class="content">
			<aside class="aside"></aside>
			<main class="main">

         
				<x-datepicker group="auth" date="2022-06-21" calendarid="dfgdfgdf" onselect="rool" />
		
	
				<form action="/admin/file" method="POST" enctype="multipart/form-data">
					@csrf
					<input type="file" name="my_file">
					<button>отправить</button>
				</form>

                
                
				
				@auth('admin')
					авторизован
					@unlessverify('admin')
						<button id="resendVerifyLinkBtn">Выслать ссылку повторно</button>
					@endverify
					
					<x-input type="test" name="testname" class="w20rem" group="ddr" value="fgdfgdf" />
					
					<a href="{{route('admin.logout')}}">Выйти</a>
					
					
				@else 
					неавторизован
					<a href="{{route('admin.reg')}}">Регистрация</a>
					<a href="{{route('admin.auth')}}">Авторизация</a>	
				@endauth
				
				
				{{-- <h2>{{Auth::guard('admin')->user()->name}}</h2>
					
				<button id="resendVerifyLinkBtn">Выслать ссылку повторно</button>
				<a href="{{route('logout')}}">Выйти</a>
					
				<a href="{{route('reg')}}">Регистрация</a>
				<a href="{{route('auth')}}">Авторизация</a>	
					
					
					
				<h1>h1 Шрифт для админки</h1>
				<h2>h2 Шрифт для админки</h2>
				<h3>h3 Шрифт для админки</h3>
				<h4>h4 Шрифт для админки</h4>
				<p><sub>sub</sub> p шрифт для админки <sup>sup</sup></p>
				
				<strong>strong Шрифт для админки</strong>
				
				<br>
				<i class="fa-solid fa-frog fa-bounce"></i>
				
				
				<x-input type="test" name="testname" class="w20rem" group="ddr" value="fgdfgdf" />
				<x-checkbox name="checkname" group="ddr" />
				<x-checkbox name="checkname2" group="ddr" />
				
				<x-select :options="$ops" empty="Сука!" choose="Не выбран" choose-empty group="ddr" class="w15rem"/>
				<x-select :options="$ops" empty="Сука!" choose="Выберите пжлст" group="ddr"/>
				
				
				<input type="file" id="singleFile" name="ddrfile" multiple>
				<div id="singleFileBlock"></div>
				
				
				<x-file types="pdf" id="roolFile" group="ddr" multiple />
				
				<x-button class="pointer">Кнопка</x-button>
				
				<div class="contenteditable">
					<div contenteditable inpgroup="ddr" role="textbox">dfg</div>
				</div>
				
				
				
				
				
				<div class="w30rem">
					<div class="row">
						<div class="col">
							<x-radio name="radioname" group="ddr2" />
						</div>
						<div class="col">
							<x-radio name="radioname" group="ddr2" />
						</div>
					</div>	
				</div>
				
				 --}}
				
			</main>
		</div>
		<footer class="footer">
			footer
		</footer>
		
		
		
		
		$ops = collect([
			['value' => 'Desk', 'title' => 200],
			['value' => 'Chair', 'title' => 100, 'active' => true],
			['value' => 'Chair 2', 'title' => 300, 'active' => null],
			['value' => 'Chair 3', 'title' => 500, 'active' => 0],
		]);