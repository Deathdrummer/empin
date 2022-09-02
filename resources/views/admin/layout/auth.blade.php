<div class="auth">
	<div @class([
		'auth__form',
		'auth__form_auth' => $authView == 'admin.auth.auth',
		'auth__form_reg' => $authView == 'admin.auth.reg'
	]) id="authFormBlock">
		@isset($reset)
			@include('admin.auth.reset-password', ['token' => $token, 'email' => $email])
		@else
			@include($authView)
		@endisset
	</div>
</div>


<script type="module">
	$('body').on(tapEvent, '[gotoauthform]', function() {
		let form = $(this).attr('gotoauthform');
		if (!form) throw new Error('gotoauthform ошибка! Не указана форма!');
		let authWaitForms = $('#authFormBlock').ddrWait();
		
		let params = {};
		
		if (form == 'forgot-password') {
			let email = $('#authForm').find('[name="email"]').val();
			params['email'] = email;
		}
		
		axios.get('/admin/'+form, {
			params,
			responseType: 'text'
		}).then(function (response) {
			$('#authFormBlock').html(response.data);
			//console.log(response);
		});
	});
	
	
	$('body').on('tap', '[agreement]', function(e) {
		e.preventDefault();
		
		
		const aggreePopup = ddrPopup({
			title: 'auth.popup_title',
			url: 'admin/agreement',
			buttons: [{title: 'auth.popup_agree_btn', id: 'setAggree'}, {title: 'auth.popup_disagree_btn', id: 'setDisaggree'}, 'Отмена'],
			closeByBackdrop: false,
			changeWidthAnimationDuration: '250ms'
		});
		
		aggreePopup.then(({setTitle, close, onScroll, disableButtons, enableButtons, setWidth, state}) => {
			
			$('#setAggree').on(tapEvent, function() {
				setWidth(900);
				$('[name="agreement"]').ddrInputs('checked');
				close();
			});
			
			$('#setDisaggree').on(tapEvent, function() {
				$('[name="agreement"]').ddrInputs('checked', false);
				close();
			});
			
			//disableButtons();
			
			
			/*setTimeout(() => {
				if (state.isClosed) return false;
				setTitle('auth.popup_title_alt');
			}, 2000);*/
			
			
			onScroll(function(stat) {
				if (stat == 'stop') {
					console.log(stat);
					enableButtons();
				}
				
			});
		});
		
	});
	
	
	
	
	/* axios.get('/admin/auth', {
		responseType: 'html'
	}).then(function ({data, status, statusText, headers, config}) {
		$('#rool').html(data);
	}); */
	
	
	
	
	$('body').on(tapEvent, '#authBtn', function() {
		let authWaitAuth = $('#authFormBlock').ddrWait();
		$('#authForm').ddrFormSubmit({
			url: '/admin/login',
			callback({no_auth = null, redirect = null, message = null, errors = null, status = null}, stat, headers) {	
				$('#authForm').ddrInputs('state', 'clear');
				
				if (redirect) location.href = redirect;
				else authWaitAuth.destroy();
				
				if (no_auth) {
					$.notify(no_auth, 'error');
					$('#authFormBlock').find('input').ddrInputs('error');
				}
				
				if (errors) {	
					$.each(errors, function(item, text) {
						$('[name="'+item+'"]').ddrInputs('error', text[0]);
					});
				} else if (message) {
					$.notify(message, 'error');
				}
			},
			fail(data, status) {
				authWaitAuth.destroy();
				console.log(data, status);
			}
		});
	});
	
	
	
	
	$('body').on(tapEvent, '#regBtn', function() {
		let authWaitReg = $('#authFormBlock').ddrWait();
		$('#regForm').ddrFormSubmit({
			url: '/admin/register',
			callback({reg = false, message = null, errors = null, status = null}, stat, headers) {
				$('#regForm').ddrInputs('state', 'clear');
				
				if (reg) pageReload();
				else authWaitReg.destroy();
				
				if (errors) {
					$.each(errors, function(item, text) {
						$('[name="'+item+'"]').ddrInputs('error', text[0]);
					});
				} else if (message) {
					$.notify(message, 'error');
				}
			},
			fail(data, status) {
				authWaitReg.destroy();
				console.log(data, status);
			}
		});
	});
	
	
	
	$('body').on(tapEvent, '#forgotPasswordBtn', function() {
		let waiting = $('#authFormBlock').ddrWait({
			iconHeight: '40px'
		});
		
		$('#forgotPasswordForm').ddrFormSubmit({
			url: '/admin/forgot-password',
			callback({message = null, errors = null, status = false}, stat, headers) {
				$('#forgotPasswordForm').ddrInputs('state', 'clear');
				
				if (errors) {
					$.each(errors, function(item, text) {
						$('[name="'+item+'"]').ddrInputs('error', text[0]);
					});
				} else if (message) {
					$.notify(message);
				}
				waiting.destroy();
			},
			fail(data, status) {
				console.log(data, status);
				waiting.destroy();
			}
		});
	});
	
	
	
	$('body').on(tapEvent, '#resetPasswordBtn', function() {
		$('#resetPasswordForm').ddrFormSubmit({
			url: '/admin/reset-password',
			callback({redirect = false, errors = false, message = null, status = null}, stat, headers) {
				$('#resetPasswordForm').ddrInputs('state', 'clear');
				
				if (redirect) location.href = redirect;
			
				if (errors) {
					$.each(errors, function(item, text) {
						$('[name="'+item+'"]').ddrInputs('error', text[0]);
					});
				} else if (message) {
					$.notify(message, 'error');
				}
			},
			fail(data, status) {
				console.log(data, status);
			}
		});
	});
	
	
	
	
</script>