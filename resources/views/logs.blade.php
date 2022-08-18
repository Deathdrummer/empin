<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Логи</title>
		
		
		{{-- <link rel="stylesheet" href="{{mix('assets/css/app.css')}}"> --}}
		{{-- <link rel="stylesheet" href="{{mix('assets/css/admin.css')}}"> --}}

		{{-- <script src="{{mix('assets/js/manifest.js')}}"></script> --}}
		{{--<script src="{{mix('assets/js/vendor.js')}}"></script>--}}
		
		<style>
			@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@100;200&display=swap');
			
			* {
				font-family: 'JetBrains Mono', monospace;
				font-weight: 200;
			}
			
			body {
				background-color: #1a202c;
			}
			
			.header {
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				background-color: #0d1628;
				padding: 10px;
				box-shadow: 0 0 10px 0 #000;
			}
			
			.content {
				padding-top: 50px;
				padding-left: 10px;
			}
			
			.log {
				white-space: pre-wrap;
				font-size: 13px;
				color: #d3d8e3;
			}
			
			
			a {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				text-decoration: none;
				font-weight: 600;
			}
				a.reload {
					height: 30px;
					padding-left: 20px;
					padding-right: 20px;
					background-color: #20a382;
					color: #fff;
					border-radius: 5px;
					transition: background-color 0.3s;
				}
					a.reload:hover {
						background-color: #0d7e62;
					}
					
					a.reload:active {
						background-color: #035440;
						transition: background-color 0s;
					}
				
				a.clear {
					margin-left: 20px;
					color: #ff5f5f;
				}
		</style>
    </head>
    
	<body>
		<header class="header">
			<a class="reload" href="{{route('log.view')}}">Обновить</a>
			<a class="clear" href="{{route('log.clear')}}">Очистить</a>
		</header>
		<div class="content">
			<aside class="aside"></aside>
			<main class="main">
				<div class="log">{{trim($data)}}</div>
			</main>
		</div>
    </body>
</html>