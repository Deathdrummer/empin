import './contextmenu.min.css';
const ContextMenu = require('./contextmenu.min').default;	
let menu;




$('body').on('contextmenu', '[contextmenu]', function(e) {
	
	menu = new ContextMenu([{
		"text": "Item 1",
		"icon": "&#9819;",
		"sub": [
			{
				"text": "Item 1.1",
				"enabled": false
			}
		]
		},
		{
			"text": "Item 2"
	}]);
	menu.hide();
	menu.display(e);
});


/*import "./index.css";

$('body').on('contextmenu', '[contextmenu]', function(e) {
	let d = $(this).attr('contextmenu').split(':'),
		func = d[0],
		args = d[1]?.split(',');
	
	if (!$[func]) {
		e.preventDefault();
		throw new Error('Ошибка! contextmenu -> Указанная функция не создана!');
	}
	
	
	let cMId = 'ddrContextMenu'+generateCode('Lnlnlnn'),
		menuHtml = '<div class="ddrcontextmenu" ddrcontextmenuwrap>';
		menuHtml += '<div class="ddrcontextmenu__block" id="'+cMId+'" style="top:'+e.clientY+'px;left:'+e.clientX+'px;" ddrcontextmenu>';
		menuHtml += '</div>';
		menuHtml += '</div>';
	
	if ($('body').find('[ddrcontextmenuwrap]').length) {
		$('body').find('[ddrcontextmenuwrap]').remove();
	}
	
	$('body').append(menuHtml);
	
	$('#'+cMId).ddrWait({
		iconHeight: '40px',
		iconColor: 'hue-rotate(160deg)'
	});
	
	$[func](...args);
	
	$('body').one(tapEvent, function(e) {
		console.log('one');
		if ($('body').find('[ddrcontextmenuwrap]').length) {
			$('body').find('[ddrcontextmenuwrap]').remove();
		}
	});

});*/



