$(document).ready(function(){
	$('#tabs').tabs();
	$('input.input-button').hover(
			function(){parent.$(this).addClass('hover');},
			function(){parent.$(this).removeClass('hover');}
	);
	$(this).bind('keydown',function(event){
		switch(event.keyCode)
		{
			case 27:
				parent.hideDwnl();
				break;
      default:
        break;
		}
	});
});