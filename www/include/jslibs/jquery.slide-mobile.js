/*
 * jQuery jSlideTouch plugin 1.0
 *
 * Copyright (c) 2010 Damien Rottemberg damien@dealinium.com
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 *
 */


(function($){
  $.fn.jSlideTouch = function (settings) {
	
	var defaultSettings = {
			direction : 'H' 
	};
	 
	var settings = jQuery.extend(defaultSettings,settings);
	  
	
 	$(this).css({'overflow': 'hidden','position':'relative'});
	return this.each(function() {
		var cont = $(this);
		
		var children = cont.children();
		var width = 0;
		var height = 0;
		var offset = cont.outerWidth()-cont.innerWidth();
		var cumul = offset;
		var i = 0;
		var currentIndex = 0;
		var id = 'wrapper_'+(new Date().getTime());
		var classs = "wrapping";
		var wrapper = $('<div id='+id+' class='+classs+'></div>');
		var positions = [];
		
		
		if(!settings.width || !settings.height){
			$.each(children,function(key,val){
				var child = $(val);
				if(!settings.height) height = Math.max(height,child.outerHeight());
				if(!settings.width)  width = Math.max(width,child.outerWidth());
			});	
		}
		
		$.each(children,function(key,val){
				var child = $(val);
				positions[i] = cumul;
				
				if(settings.direction =='H'){
					child.css({'position':'absolute','top':'0','left':cumul+'px'});
					cumul +=  width +15;
				}
				if(settings.direction =='V'){
					child.css({'margin':  (height-child.outerHeight())/2+'px '+(width-child.outerWidth())/2+'px','position':'absolute','left':'0','top':cumul+'px'});		
					cumul +=  height +15;
				}
				child.attr('ind',i);
				child.bind('mousedown touchstart',function(e){
					currentIndex = parseInt($(this).attr('ind'));
				});
				i++;
		});
		
		positions[i] = cumul;
		cont.height(height-offset);
		cont.width(width-offset);
		cont.wrapInner(wrapper);
		wrapper = $('#'+id);
		wrapper.width(cumul);
		wrapper.height(height-offset);
		wrapper.css({'position':'relative','left':0,'top':0});
		
		
		
		cont.bind('mousedown touchstart',function(e){
			
					
			if(e.originalEvent.touches && e.originalEvent.touches.length) {
		        e = e.originalEvent.touches[0];
		    } else if(e.originalEvent.changedTouches && e.originalEvent.changedTouches.length) {
		        e = e.originalEvent.changedTouches[0];
		    }

			
			var initX = e.pageX;
			var sX = e.pageX;
			var sWX = parseInt(wrapper.css('left'));
			
			var initY = e.pageY;
			var sY = e.pageY;
			var sWY = parseInt(wrapper.css('top'));
				
			cont.bind('mousemove touchmove ',function(ev){
				if(ev.originalEvent.touches && ev.originalEvent.touches.length){
					
					ev = ev.originalEvent.touches[0];
				}	else if(ev.originalEvent.changedTouches && ev.originalEvent.changedTouches.length) {
			        ev = ev.originalEvent.changedTouches[0];
			    } 
				
				if(settings.direction =='H'){
					wrapper.css('left',sWX-(sX-ev.pageX)+'px');
					sWX = parseInt(wrapper.css('left'));
					sX = ev.pageX;
					
				}
				if(settings.direction =='V'){
					wrapper.css('top',sWY-(sY-ev.pageY)+'px');
					sWY = parseInt(wrapper.css('top'));
					sY = ev.pageY;
				}
					
			});
			cont.bind('mouseup touchend',function(ev){	
				cont.unbind('mousemove touchmove mouseup touchend');
				if(ev.originalEvent.touches && ev.originalEvent.touches.length){
					ev.preventDefault();
					ev = ev.originalEvent.touches[0];
				}	else if(ev.originalEvent.changedTouches && ev.originalEvent.changedTouches.length) {
			        ev = ev.originalEvent.changedTouches[0];
			    }
				
				if(settings.direction =='H'){	
					if(sWX>0){
						wrapper.animate({'left':0+'px'});
					}else if(sWX<-positions[children.length-1]){
							wrapper.animate({'left':-positions[children.length-1]+'px'});
					}else{	
						if(initX>sX){	
							if(initX-sX>0.4*$(document).width()){
								wrapper.animate({'left':-positions[currentIndex+1]+'px'},'fast');
							
							}else{
								wrapper.animate({'left':-positions[currentIndex]+'px'},'fast');
								
							}
						}else{
							if(sX-initX > (0.4)*$(document).width()){
								
								wrapper.animate({'left':-positions[currentIndex-1]+'px'},'fast');
							}else{
								wrapper.animate({'left':-positions[currentIndex]+'px'},'fast');
								
							}
						}	
					}
				}
				if(settings.direction =='V'){	
					if(sWY>0){
						wrapper.animate({'top':0+'px'});
					}else if(sWY<-positions[children.length-1]){
							wrapper.animate({'top':-positions[children.length-1]+'px'});
					}else{	
						if(initY>sY){	
							if(initY-sY>0.4*height){
								wrapper.animate({'top':-positions[currentIndex+1]+'px'},'fast');
							}else{
								wrapper.animate({'top':-positions[currentIndex]+'px'},'fast');
							}
						}else{
							if(initY-sY<0.4*height){
								wrapper.animate({'top':-positions[currentIndex-1]+'px'},'fast');
							}else{
								wrapper.animate({'top':-positions[currentIndex]+'px'},'fast');
							}
						}	
					}
				}	
			});
		});
	});
}
})(jQuery);   