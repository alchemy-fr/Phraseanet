(function($){
	$.fn.nicoslider = function(options)
	{
		var defaults = {
				start : 0,
				color : '#F6F2F1',
				sliderHeight : false
		};
		
		var opts = $.extend({}, $.fn.nicoslider.defaults,defaults, options);
		
		return this.each(function(){
			new nicoslide(this, opts);
		});
	};
	
	
	var nicoslide = function(slider, o)
	{
		var $slider = $(slider);
		
		$sliderWidth = $slider.parent().innerWidth();
		
		 $sliderCss = {
			'width':$sliderWidth,
			'background-color':o.color
		 }
		 
		$slider.css($sliderCss);
		
		var ul = $slider.find("ul");
		var ulWidth = 0;
		var liHeight = 0;
		
		$slider.find("li").each(function(){
			ulWidth += $(this).width() + 5;
			ulWidth += parseInt($(this).css("padding-left"));
			ulWidth += parseInt($(this).css("padding-right"));
			ulWidth += parseInt($(this).css("margin-left"));
			ulWidth += parseInt($(this).css("margin-right"));
			liHeight = Math.max(liHeight,$(this).outerHeight());
		});
		//5 % of slider width
		$scrollWidth = Math.round(parseInt($sliderWidth) * parseFloat("0.05"));
		//min 30 px;
		if($scrollWidth < 30)
		{
			$scrollWidth = 30;
		}
		
		var $wrapperWidth = Math.round(parseInt($sliderWidth) - ( 2 * $scrollWidth ));
	
		if(ulWidth > $wrapperWidth)
		{	
			ul.wrapAll("<div class='wrapper'></div>");
			$wrapper = $slider.find(".wrapper");
			$ulHeight = ul.height();
			$wrapper.width($wrapperWidth);
			
			$wrapperCss= {
				'overflow':'hidden',
				'float':'left',
				'position':'relative'
			}
			
			$wrapper.css($wrapperCss);
			ul.width(ulWidth);
			$slider.prepend("<div class='scrollleft'></div>");
			$slider.append("<div class='scrollright'></div>");
			
			var rightScroll = $slider.find(".scrollright");
			var leftScroll = $slider.find(".scrollleft");
			
			rightScroll.append("<div class='rb'>&gt;</div>");
			leftScroll.append("<div class='lb'>&lt;</div>");
			
			$("div.rb").css('float', 'right');
			
			rightCss = {
				'width' : $scrollWidth - ($wrapper.outerWidth(true)  - $wrapper.innerWidth()),
				'height' : liHeight,
				'float' : 'right',
				'background-color' : o.color
			}
	
			leftCss = {
				'width' : $scrollWidth - ($wrapper.outerWidth(true)  - $wrapper.innerWidth()),
				'height' : liHeight,
				'float' : 'left',
				'background-color' : o.color
			}
			
			leftScroll.css(leftCss);
			rightScroll.css(rightCss);
			
			var rightScrollWidth = rightScroll.width();
			var leftScrollWidth = leftScroll.width();
			var scrollInterval = 100;
			var scrollXpos = 0;
			var scrollStepSpeed = 20;
			var speed = 8;
			var repeat = null;
			var shift = o.start;
			
			//calcul position zone droite
			rightScroll.bind("mousemove", function(e){
				var x = e.pageX - ($(this).offset().left);
				scrollXpos = Math.round((x / rightScrollWidth) * scrollStepSpeed);
			});
			//calcul position zone gauche
			leftScroll.bind("mousemove", function(e){
				
				var x = $(this).innerWidth() - (e.pageX - $(this).offset().left);
				scrollXpos = Math.round((x / leftScrollWidth) * scrollStepSpeed); 
			});	
			
			//scroll a droite
			rightScroll.bind("mouseenter", function(){
				repeat = setInterval(function(){
						shift +=  (scrollXpos * speed);
						
						if(shift > (ulWidth - $wrapperWidth) + 50)
						{
							shift = (ulWidth - $wrapperWidth) + 50;
						}
						
						ul.animate({
							left: -shift
						}, 1);
				}, scrollInterval);
			});
	
			//scroll a gauche
			leftScroll.bind("mouseenter", function(){
				repeat = setInterval(function(){
						shift -=  (scrollXpos * speed);
	
						if(shift < 0)
						{
							shift = 0;
						}
						
						ul.animate({
							left: -shift
						}, 1);
					
				}, scrollInterval);
			});
			
			//on stop a droite
			rightScroll.bind("mouseout", function(){
				clearInterval(repeat);
				scrollXpos = 0;
			});
	
			//on stop a gauche
			leftScroll.bind("mouseout", function(){
				clearInterval(repeat);
				scrollXpos = 0;
			});
		}
	};
})(jQuery);