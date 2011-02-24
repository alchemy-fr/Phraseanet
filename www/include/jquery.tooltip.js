/*
 * jQuery Tooltip plugin 1.3
 *
 * http://bassistance.de/jquery-plugins/jquery-plugin-tooltip/
 * http://docs.jquery.com/Plugins/Tooltip
 *
 * Copyright (c) 2006 - 2008 J�rn Zaefferer
 *
 * $Id: jquery.tooltip.js 5741 2008-06-21 15:22:16Z joern.zaefferer $
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */

/*jsl:ignore*/;/*jsl:end*/(function($) {

		// the tooltip element
	var helper = {},
		// the title of the current element, used for restoring
		title,
		// timeout id for delayed tooltips
		tID,
		// IE 5.5 or 6
		IE = $.browser.msie && (/MSIE\s(5\.5|6\.)/).test(navigator.userAgent),
		// flag for mouse tracking
		track = false;

	$.tooltip = {
		blocked: false,
		current: null,
		visible: false,
		defaults: {
			delay: 700,
			fixable:false,
			fade: true,
			showURL: true,
			outside: true,
			extraClass: "",
			top: 15,
			left: 15,
			id: "tooltip"
		},
		block: function() {
			$.tooltip.blocked = !$.tooltip.blocked;
		}
	};

	$.fn.extend({
		tooltip: function(settings) {
			settings = $.extend({}, $.tooltip.defaults, settings);
			createHelper(settings);
			return this.each(function() {
					$.data(this, "tooltip", settings);
					// copy tooltip into its own expando and remove the title
					this.tooltipText = this.title;
					this.orEl = $(this);
					$(this).removeAttr("title");
					// also remove alt attribute to prevent default tooltip in IE
					this.alt = "";
				})
				.mouseover(save)
				.mouseout(hide)
				.mousedown(fix);
		},
		fixPNG: IE ? function() {
			return this.each(function () {
				var image = $(this).css('backgroundImage');
				if (image.match(/^url\(["']?(.*\.png)["']?\)$/i)) {
					image = RegExp.$1;
					$(this).css({
						'backgroundImage': 'none',
						'filter': "progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true, sizingMethod=crop, src='" + image + "')"
					}).each(function () {
						var position = $(this).css('position');
						if (position != 'absolute' && position != 'relative')
							$(this).css('position', 'relative');
					});
				}
			});
		} : function() { return this; },
		unfixPNG: IE ? function() {
			return this.each(function () {
				$(this).css({'filter': '', backgroundImage: ''});
			});
		} : function() { return this; },
		hideWhenEmpty: function() {
			return this.each(function() {
				$(this)[ $(this).html() ? "show" : "hide" ]();
			});
		},
		url: function() {
			return this.attr('href') || this.attr('src');
		}
	});

	function createHelper(settings) {
		// there can be only one tooltip helper
		if( helper.parent )
			return;
		// create the helper, h3 for title, div for url
		helper.parent = $('<div id="' + settings.id + '"><div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix"><a class="tooltip_closer ui-dialog-titlebar-close ui-corner-all" style="background-color:black;position:absolute;top:0;right:0;cursor:pointer;display:none;" onclick="unfix_tooltip();return false;" href="#"><span class="ui-icon ui-icon-closethick" unselectable="on" style="-moz-user-select: none;">close</span></a></div><div class="body"></div></div>')
			// add to document
			.appendTo(document.body)
			// hide it at first
			.hide();

		// apply bgiframe if available
		if ( $.fn.bgiframe )
			helper.parent.bgiframe();

		// save references to title and url elements
		helper.title = $('h3', helper.parent);
		helper.body = $('div.body', helper.parent);
		helper.url = $('div.url', helper.parent);
	}

	function settings(element) {
		return $.data(element, "tooltip");
	}

	// main event handler to start showing tooltips
	function handle(event) {

		if($($.tooltip.current).hasClass('SSTT') && $($.tooltip.current).hasClass('ui-state-active'))
			return;

		// show helper, either with timeout or on instant
		if( settings(this).delay )
			tID = setTimeout(visible, settings(this).delay);
		else
			visible();
			show();

		// if selected, update the helper position when the mouse moves
		track = !!settings(this).track;
		$(document.body).bind('mousemove', update);

		// update at least once
		update(event);
	}

	// save elements title before the tooltip is displayed
	function save(event) {
		// if this is the current source, or it has no title (occurs with click event), stop
    if(event.stopPropagation)
      event.stopPropagation();

    event.cancelBubble = true;

    if ( $.tooltip.blocked || this == $.tooltip.current || (!this.tooltipText && !settings(this).bodyHandler) )
			return;

		// save current
		$.tooltip.current = this;
		title = this.tooltipText;

		if ( settings(this).bodyHandler ) {
			helper.title.hide();
			var bodyContent = settings(this).bodyHandler.call(this);
			if (bodyContent.nodeType || bodyContent.jquery) {
				helper.body.empty().append(bodyContent);
			} else {
				helper.body.html( bodyContent );
			}
			helper.body.show();
		} else if ( settings(this).showBody ) {
			var parts = title.split(settings(this).showBody);
			helper.title.html(parts.shift()).show();
			helper.body.empty();
			for(var i = 0, part; (part = parts[i]); i++) {
				if(i > 0)
					helper.body.append("<br/>");
				helper.body.append(part);
			}
			helper.body.hideWhenEmpty();
		} else {
			helper.body.html(title).show();
		}

		// if element has href or src, add and show it, otherwise hide it
		if( settings(this).showURL && $(this).url() )
			helper.url.html( $(this).url().replace('http://', '') ).show();
		else
			helper.url.hide();

		// add an optional class for this tip
//		helper.parent.addClass(settings(this).extraClass);

		// fix PNG background for IE
		if (settings(this).fixPNG )
			helper.parent.fixPNG();
		if(settings(this).outside)
		{
			var width = 'auto';
			var height = 'auto';
			var ratio = 1;
			if ($('#' + settings($.tooltip.current).id + ' .imgTips')[0]) {
				width = parseInt($('#' + settings($.tooltip.current).id + ' .imgTips')[0].style.width);
				height = parseInt($('#' + settings($.tooltip.current).id + ' .imgTips')[0].style.height);
				ratio = width/height;
			}

			var v = viewport(),
			h = helper.parent;
			helper.parent.css({
				width:width,
				top:0,
				left:0,
				visibility:'hidden',
	//			visibility:'visible',
				display:'block',
				height:height
				});

			$(h).width($(h).width());
			width = ($(h).width()>(v.x-40))?(v.x-40):$(h).width();
			height = ($(h).height()>(v.y-40))?(v.y-40):$(h).height();

			if($('#' + settings($.tooltip.current).id + ' .audioTips').length > 0)
			{
				height = height < 26 ? 26 : height;
			}

			$(h).css({width:width,height:height});

			if (event) {

				var vert, vertS, hor, horS, top, left,ratioH,ratioV;
	//			ratio = $(h).width()/$(h).height();
				var ratioSurfaceH;
				var ratioSurfaceV, wiH,wiV,heH,heV;
				var ratioImage = $(h).width()/$(h).height();

				//position de l'image
				if ($(event.target).offset().left > (v.x - $(event.target).offset().left - $(event.target).width())) {
					hor = 'gauche';
					wiH = $(event.target).offset().left;

					horS = wiH * v.y;
					ratioSurfaceH = wiH / v.y;
				}
				else {
					hor = 'droite';
					wiH = (v.x - $(event.target).offset().left - $(event.target).width());
					horS = wiH * v.y;
					ratioSurfaceH = wiH / v.y;

				}
				if ($(event.target).offset().top > (v.y - $(event.target).offset().top - $(event.target).height())) {
					vert = 'haut';
					heV = $(event.target).offset().top;
					vertS = heV * v.x;
					ratioSurfaceV = v.x / heV;
				}
				else {
					vert = 'bas';
					heV = (v.y - $(event.target).offset().top - $(event.target).height());
					vertS = heV * v.x;
					ratioSurfaceV = v.x / heV;
				}


				//correction par ratio
				if ($('#' + settings($.tooltip.current).id + ' .imgTips')[0]) {

					if(ratioSurfaceH > ratioImage)
					{
						horS = v.y * ratioImage*v.y;
					}
					else
					{
						horS = wiH * wiH/ratioImage;
					}
					if(ratioSurfaceV > ratioImage)
					{
						vertS = heV * ratioImage*heV;
					}
					else
					{
						vertS = v.x * v.x/ratioImage;
					}
				}

				var zH;

				if(vertS > horS)
				{
	//				width = ($(h).width()>(v.x-40))?(v.x-40):$(h).width();
					var zL = event.pageX;
					var zW = $(h).width();
					zH = $(h).height();
					var ETOT = $(event.target).offset().top;
					var ETH = $(event.target).height();
					left = (zL - zW/2)<20?20:(((zL + zW/2+20)>v.x)?(v.x-zW-20):(zL -zW/2));
					switch(vert)
					{
						case 'haut':
							height = (zH>(ETOT-40))?(ETOT-40):zH;
							top = ETOT - height-20;
							break;
						case 'bas':
							height = ((v.y-ETH-ETOT-40)>zH)?zH:(v.y-ETH-ETOT-40);
							top = ETOT +ETH+20;
							break;
						default:
							break;
					}
				}
				else
				{
	//				height = ($(h).height()>(v.y-40))?(v.y-40):$(h).height();
					zH = $(h).height();
					var zT = event.pageY;
					var EOTL = $(event.target).offset().left;
					var ETW = $(event.target).width();
					var zw = $(h).width();
					top = (zT - zH/2)<20?20:(((zT + zH/2+20)>v.y)?(v.y-zH-20):(zT - zH/2));
					switch(hor)
					{
						case 'gauche':
							width = (zw>(EOTL-40))?(EOTL-40):zw;
							left = EOTL - width-20;
							break;
						case 'droite':
							width = ((v.x-ETW-EOTL-40)>zw)?zw:(v.x-ETW-EOTL-40);
							left = EOTL +ETW+20;
							break;
						default:
							break;
					}
				}

					helper.parent.css({
						width: width,
						height: height,
						left: left,
						top: top
					});


				//si ya une image on re-ajuste au ratio
				if ($('#' + settings($.tooltip.current).id + ' .imgTips')[0]) {
						if(width == 'auto')
							width = $('#' + settings($.tooltip.current).id).width();
						if(height == 'auto')
							height = $('#' + settings($.tooltip.current).id).height();
					if (ratio > 1) {
						var nh = width / ratio;
						if (nh > height) {
							width = ratio * height;
							nh = width / ratio;
						}
						height = nh;
					}
					else {
						var nw = ratio * height;
						if (nw > width) {
							height = width / ratio;
							nw = height * ratio;
						}
						width = nw;
					}
				}else{
					if(vertS < horS)
					{
						height = 'auto';
					}
				}

					helper.parent.css({
						width: width,
						height: height,
						left: left,
						top: top
					});

					$('#' + settings($.tooltip.current).id + ' .imgTips').css({
						width: width,
						height: height,
						left: left,
						top: top
					});
			}

		}
		handle.apply(this, arguments);
		return;
	}

	// delete timeout and show helper
	function show() {
		tID = null;

		if ((!IE || !$.fn.bgiframe) && settings($.tooltip.current).fade) {
			if (helper.parent.is(":animated"))
				helper.parent.stop().show().fadeTo(settings($.tooltip.current).fade, 100);
			else
				helper.parent.is(':visible') ? helper.parent.fadeTo(settings($.tooltip.current).fade, 100) : helper.parent.fadeIn(settings($.tooltip.current).fade);
		} else {
			helper.parent.show();
		}
		update();
	}

	function fix(event)
	{
		if(!settings(this).fixable)
		{
			hide(event);
			return;
		}
		event.cancelBubble = true;
		if(event.stopPropagation)
			event.stopPropagation();
		showOverlay('_tooltip','body',unfix_tooltip);
		$('#tooltip .tooltip_closer').show();
		$.tooltip.blocked = true;
	}

	function visible(){
		$.tooltip.visible = true;
		helper.parent.css({
			visibility:'visible'
		});
	}

	/**
	 * callback for mousemove
	 * updates the helper position
	 * removes itself when no current element
	 */
	function update(event)	{

		if($.tooltip.blocked)
			return;

		if (event && event.target.tagName == "OPTION") {
			return;
		}

		// stop updating when tracking is disabled and the tooltip is visible
		if ( !track && helper.parent.is(":visible")) {
			$(document.body).unbind('mousemove', update);
		}

		// if no current element is available, remove this listener
		if( $.tooltip.current === null ) {
			$(document.body).unbind('mousemove', update);
			return;
		}

		// remove position helper classes
		helper.parent.removeClass("viewport-right").removeClass("viewport-bottom");

		if(!settings($.tooltip.current).outside)
		{
			var left = helper.parent[0].offsetLeft;
			var top = helper.parent[0].offsetTop;
			helper.parent.width('auto');
			helper.parent.height('auto');
			if (event) {
				// position the helper 15 pixel to bottom right, starting from mouse position
				left = event.pageX + settings($.tooltip.current).left;
				top = event.pageY + settings($.tooltip.current).top;
				var right='auto';
				if (settings($.tooltip.current).positionLeft) {
					right = $(window).width() - left;
					left = 'auto';
				}
				helper.parent.css({
					left: left,
					right: right,
					top: top
				});
			}

			var v = viewport(),
				h = helper.parent[0];
			// check horizontal position
			if (v.x + v.cx < h.offsetLeft + h.offsetWidth) {
				left -= h.offsetWidth + 20 + settings($.tooltip.current).left;
				helper.parent.css({left: left + 'px'}).addClass("viewport-right");
			}
			// check vertical position
			if (v.y + v.cy < h.offsetTop + h.offsetHeight) {
				top -= h.offsetHeight + 20 + settings($.tooltip.current).top;
				helper.parent.css({top: top + 'px'}).addClass("viewport-bottom");
			}
		}
	}

	function viewport() {
		return {
			x: $(window).width(),
			y: $(window).height(),

			cx: 0,
			cy: 0
		};
	}

	// hide helper and restore added classes and the title
	function hide(event)
	{
		if($.tooltip.blocked || !$.tooltip.current)
			return;
		// clear timeout if possible
		if(tID)
			clearTimeout(tID);
		// no more current element
		$.tooltip.visible = false;
		var tsettings = settings($.tooltip.current);
		$.tooltip.current = null;
		function complete() {
			helper.parent.removeClass( tsettings.extraClass ).hide().css("opacity", "");
      var el =helper.parent.find('object').parent();
      el.empty();
      el.remove();
		}
		if ((!IE || !$.fn.bgiframe) && tsettings.fade) {
			if (helper.parent.is(':animated'))
				helper.parent.stop().fadeTo(tsettings.fade, 0, complete);
			else
				helper.parent.stop().fadeOut(tsettings.fade, complete);
		} else
			complete();

		if( tsettings.fixPNG )
			helper.parent.unfixPNG();
	}

})(jQuery);

function unfix_tooltip()
{
	$.tooltip.blocked = false;
	$.tooltip.visible = false;
	$.tooltip.current = null;
	$('#tooltip').hide();
	$('#tooltip .tooltip_closer').hide();
	hideOverlay('_tooltip');
}

$(document).bind('keydown', function(event){
	if(event.keyCode == 27 && $.tooltip.blocked === true)
	{
		unfix_tooltip();
	}
});