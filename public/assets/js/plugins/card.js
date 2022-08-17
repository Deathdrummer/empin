$.fn.card = function(comand) {
	const selector = this,
		comands = {
			ready() {
				$(selector).find('[cardwait]').addClass('card__wait_closing');
				setTimeout(() => {
					$(selector).find('[cardwait]').remove();
				}, 500);
			},
			disableButton() {
				$(selector).find('[cardbutton]').ddrInputs('disable');
			},
			enableButton() {
				$(selector).find('[cardbutton]').ddrInputs('enable');
			}
		};
	
	
	
	
	
	
	
	comands[comand]();
}