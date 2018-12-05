/**
 * Copyright (c)2005-2009 Matt Kruse (javascripttoolbox.com)
 *
 * Dual licensed under the MIT and GPL licenses.
 * This basically means you can use this code however you want for
 * free, but don't claim to have written it yourself!
 * Donations always accepted: http://www.JavascriptToolbox.com/donate/
 *
 * Please do not link to the .js files on javascripttoolbox.com from
 * your site. Copy the files locally to your server instead.
 *
 */
/**
 * jquery.contextmenu.js
 * jQuery Plugin for Context Menus
 * http://www.JavascriptToolbox.com/lib/contextmenu/
 *
 * Copyright (c) 2008 Matt Kruse (javascripttoolbox.com)
 * Dual licensed under the MIT and GPL licenses.
 *
 * @version 1.0
 * @history 1.0 2008-10-20 Initial Release
 * @todo slideUp doesn't work in IE - because of iframe?
 * @todo Hide all other menus when contextmenu is shown?
 * @todo More themes
 * @todo Nested context menus
 */
;(function($){
	$.contextMenu = {
		// props add by Alchemy
		_showEvent:null,	// the original event the caused the menu to show (useful to find the original element clicked)
		_div:null,
		//
		openEvt:"contextmenu",	// ouverture sur right-click
		closeTimer:null,		// fermer le menu apres 100ms de mouseout

		shadow:true,
		dropDown:false,
		shadowOffset:0,
		shadowOffsetX:5,
		shadowOffsetY:5,
		shadowWidthAdjust:-3,
		shadowHeightAdjust:-3,
		shadowOpacity:.2,
		shadowClass:'context-menu-shadow',
		shadowColor:'black',

		offsetX:0,
		offsetY:0,
		appendTo:'body',
		direction:'down',
		constrainToScreen:true,

		showTransition:'show',
		hideTransition:'hide',
		showSpeed:'',
		hideSpeed:'',
		showCallback:null,
		hideCallback:null,

		className:'context-menu',
		itemClassName:'context-menu-item',
		itemHoverClassName:'context-menu-item-hover',
		disabledItemClassName:'context-menu-item-disabled',
		disabledItemHoverClassName:'context-menu-item-disabled-hover',
		separatorClassName:'context-menu-separator',
		innerDivClassName:'context-menu-item-inner',
		themePrefix:'context-menu-theme-',
		theme:'default',

		separator:'context-menu-separator', // A specific key to identify a separator
		target:null, // The target of the context click, to be populated when triggered
		menu:null, // The jQuery object containing the HTML object that is the menu itself
		shadowObj:null, // Shadow object
		bgiframe:null, // The iframe object for IE6
		shown:false, // Currently being shown?
		useIframe:/*@cc_on @*//*@if (@_win32) true, @else @*/false,/*@end @*/ // This is a better check than looking at userAgent!

		// Create the menu instance
		create: function(menu,opts) {
			var cmenu = $.extend({},this,opts); // Clone all default properties to created object

			// If a selector has been passed in, then use that as the menu
			if (typeof menu=="string") {
				cmenu.menu = $(menu);
			}
			// If a function has been passed in, call it each time the menu is shown to create the menu
			else if (typeof menu=="function") {
				cmenu.menuFunction = menu;
			}
			// Otherwise parse the Array passed in
			else {
				cmenu.menu = cmenu.createMenu(menu,cmenu);
			}
			if (cmenu.menu) {
				cmenu.menu.css({display:'none'});
				$(cmenu.appendTo).append(cmenu.menu);
			}

			// Create the shadow object if shadow is enabled
			if (cmenu.shadow) {
				cmenu.createShadow(cmenu); // Extracted to method for extensibility
				if (cmenu.shadowOffset) { cmenu.shadowOffsetX = cmenu.shadowOffsetY = cmenu.shadowOffset; }
			}
			$('body').bind(cmenu.openEvt,function(){cmenu.hide();}); // If right-clicked somewhere else in the document, hide this menu
			return cmenu;
		},

		// Create an iframe object to go behind the menu
		createIframe: function() {
		    return $('<iframe frameborder="0" tabindex="-1" src="javascript:false" style="display:block;position:absolute;z-index:-1;filter:Alpha(Opacity=0);"/>');
		},

		// Accept an Array representing a menu structure and turn it into HTML
		createMenu: function(menu,cmenu) {
			var className = cmenu.className;
			$.each(cmenu.theme.split(","),function(i,n){className+=' '+cmenu.themePrefix+n;});
//			var $t = $('<div style="background-color:#ffff00; xwidth:200px; height:200px"><table style="" cellspacing=0 cellpadding=0></table></div>').click(function(){cmenu.hide(); return false;}); // We wrap a table around it so width can be flexible
			var $t = $('<table style="" cellspacing="0" cellpadding="0"></table>').click(function(){cmenu.hide(); return false;}); // We wrap a table around it so width can be flexible
			var $tr = $('<tr></tr>');
			var $td = $('<td></td>');
			var $div = cmenu._div = $('<div class="'+className+'"></div>');

			cmenu._div.hover(
						function()
						{
							if(cmenu.closeTimer)
							{
								clearTimeout(cmenu.closeTimer);
								cmenu.closeTimer = null;
							}
						},
						function()
						{
							var myClass = cmenu;
							function timerRelay()
							{
								myClass.hide();
							}
							myClass.closeTimer = setTimeout(timerRelay, 500);
						}
					);

			// Each menu item is specified as either:
			//     title:function
			// or  title: { property:value ... }
/*
			for (var i=0; i<menu.length; i++) {
				var m = menu[i];
				if (m==$.contextMenu.separator) {
					$div.append(cmenu.createSeparator());
				}
				else {
					for (var opt in menu[i]) {
						$div.append(cmenu.createMenuItem(opt,menu[i][opt])); // Extracted to method for extensibility
					}
				}
			}
*/
			for (var i=0; i<menu.length; i++)
			{
				var m = menu[i];
				if (m==$.contextMenu.separator)
				{
					$div.append(cmenu.createSeparator());
				}
				else
				{
					$div.append(cmenu.createMenuItem(m)); // Extracted to method for extensibility
				}
			}
			if ( cmenu.useIframe ) {
				$td.append(cmenu.createIframe());
			}
			$t.append($tr.append($td.append($div)));
			return $t;
		},

		// Create an individual menu item
		createMenuItem: function(obj) {
			var cmenu = this;
			var label = obj.label;
			if (typeof obj=="function") { obj={onclick:obj}; } // If passed a simple function, turn it into a property of an object
			// Default properties, extended in case properties are passed
			var o = $.extend({
				onclick:function() { },
				className:'',
				hoverClassName:cmenu.itemHoverClassName,
				icon:'',
				disabled:false,
				title:'',
				hoverItem:cmenu.hoverItem,
				hoverItemOut:cmenu.hoverItemOut
			},obj);
			// If an icon is specified, hard-code the background-image style. Themes that don't show images should take this into account in their CSS
			var iconStyle = (o.icon)?'background-image:url('+o.icon+');':'';
			var $div = $('<div class="'+cmenu.itemClassName+' '+o.className+((o.disabled)?' '+cmenu.disabledItemClassName:'')+'" title="'+o.title+'"></div>')
							// If the item is disabled, don't do anything when it is clicked
							.click(
									function(e)
									{
										if(cmenu.isItemDisabled(this))
										{
											return false;
										}
										else
										{
											return o.onclick.call(cmenu.target, this, cmenu, e, label);
										}
									}
								)
							// Change the class of the item when hovered over
							.hover(
									function()
									{
										o.hoverItem.call(this,(cmenu.isItemDisabled(this))?cmenu.disabledItemHoverClassName:o.hoverClassName);
									}
									,function()
									{
										o.hoverItemOut.call(this,(cmenu.isItemDisabled(this))?cmenu.disabledItemHoverClassName:o.hoverClassName);
									}
								);
			var $idiv = $('<div class="'+cmenu.innerDivClassName+'" style="'+iconStyle+'">'+label+'</div>');
			$div.append($idiv);
			return $div;
		},

		// Create a separator row
		createSeparator: function() {
			return $('<div class="'+this.separatorClassName+'"></div>');
		},

		// Determine if an individual item is currently disabled. This is called each time the item is hovered or clicked because the disabled status may change at any time
		isItemDisabled: function(item) { return $(item).is('.'+this.disabledItemClassName); },

		// Functions to fire on hover. Extracted to methods for extensibility
		hoverItem: function(c) { $(this).addClass(c); },
		hoverItemOut: function(c) { $(this).removeClass(c); },

		// Create the shadow object
		createShadow: function(cmenu) {
			cmenu.shadowObj = $('<div class="'+cmenu.shadowClass+'"></div>').css( {display:'none',position:"absolute", zIndex:9998, opacity:cmenu.shadowOpacity, backgroundColor:cmenu.shadowColor } );
			$(cmenu.appendTo).append(cmenu.shadowObj);
		},

		// Display the shadow object, given the position of the menu itself
		showShadow: function(x,y,e) {
			var cmenu = this;
			if (cmenu.shadow) {
				cmenu.shadowObj.css( {
					width:(cmenu.menu.width()+cmenu.shadowWidthAdjust)+"px",
					height:(cmenu.menu.height()+cmenu.shadowHeightAdjust)+"px",
					top:(y+cmenu.shadowOffsetY)+"px",
					left:(x+cmenu.shadowOffsetX)+"px"
				}).addClass(cmenu.shadowClass)[cmenu.showTransition](cmenu.showSpeed);
			}
		},

		// A hook to call before the menu is shown, in case special processing needs to be done.
		// Return false to cancel the default show operation
		beforeShow: function() { return true; },

		// Show the context menu
		show: function(t,e) {
			var cmenu=this, x=e.pageX, y=e.pageY;

			if(cmenu._div)
				cmenu._div.css('height', 'auto').css('overflow-y', 'auto');

			cmenu.target = t; // Preserve the object that triggered this context menu so menu item click methods can see it
			cmenu._showEvent  = e; // Preserve the event that triggered this context menu so menu item click methods can see it
			if (cmenu.beforeShow()!==false) {
				// If the menu content is a function, call it to populate the menu each time it is displayed
				if (cmenu.menuFunction) {
					if (cmenu.menu) { $(cmenu.menu).remove(); }
					cmenu.menu = cmenu.createMenu(cmenu.menuFunction(cmenu,t),cmenu);
					cmenu.menu.css({display:'none'});
					$(cmenu.appendTo).append(cmenu.menu);
				}
				var $c = cmenu.menu;
				x+=cmenu.offsetX; y+=cmenu.offsetY;
				var pos = cmenu.getPosition(x,y,cmenu,e); // Extracted to method for extensibility
				cmenu.showShadow(pos.x,pos.y,e);
				// Resize the iframe if needed
				if (cmenu.useIframe) {
					$c.find('iframe').css({width:$c.width()+cmenu.shadowOffsetX+cmenu.shadowWidthAdjust,height:$c.height()+cmenu.shadowOffsetY+cmenu.shadowHeightAdjust});
				}
				if(cmenu.dropDown)
				{
					$c.css('visibility','hidden').show();

					var bodySize = {x:$(window).width(), y:$(window).height()};

					if($(t).offset().top+$(t).outerHeight()+$c.height()>bodySize.y)
					{
						if($(t).offset().left+$(t).outerWidth()+$c.width()>bodySize.x)
							$c.css( {top:($(t).offset().top-$c.outerHeight())+"px", left:($(t).offset().left-$c.outerWidth())+"px", position:"absolute",zIndex:9999} )[cmenu.showTransition](cmenu.showSpeed,((cmenu.showCallback)?function(){cmenu.showCallback.call(cmenu);}:null));
						else
							$c.css( {top:($(t).offset().top-$c.outerHeight())+"px", left:($(t).offset().left)+"px", position:"absolute",zIndex:9999} )[cmenu.showTransition](cmenu.showSpeed,((cmenu.showCallback)?function(){cmenu.showCallback.call(cmenu);}:null));
					}
					else
					{

						if($(t).offset().left+$(t).outerWidth()+$c.width()>bodySize.x)
							$c.css( {top:($(t).offset().top+$(t).outerHeight())+"px", left:($(t).offset().left-$c.outerWidth())+"px", position:"absolute",zIndex:9999} )[cmenu.showTransition](cmenu.showSpeed,((cmenu.showCallback)?function(){cmenu.showCallback.call(cmenu);}:null));
						else
							$c.css( {top:($(t).offset().top+$(t).outerHeight())+"px", left:($(t).offset().left)+"px", position:"absolute",zIndex:9999} )[cmenu.showTransition](cmenu.showSpeed,((cmenu.showCallback)?function(){cmenu.showCallback.call(cmenu);}:null));

					}$c.css('visibility','visible');
				}
				else
					$c.css( {top:pos.y+"px", left:pos.x+"px", position:"absolute",zIndex:9999} )[cmenu.showTransition](cmenu.showSpeed,((cmenu.showCallback)?function(){cmenu.showCallback.call(cmenu);}:null));
				cmenu.shown=true;
				$(document).one('click',null,function(){cmenu.hide();}); // Handle a single click to the document to hide the menu
			}
		},

		// Find the position where the menu should appear, given an x,y of the click event
		getPosition: function(clickX,clickY,cmenu,e) {
			var x = clickX+cmenu.offsetX;
			var y = clickY+cmenu.offsetY;
			var h = $(cmenu.menu).height();
			var w = $(cmenu.menu).width();
			var dir = cmenu.direction;
			if (cmenu.constrainToScreen)
			{
				var $w = $(window);
				var wh = $w.height();
				var ww = $w.width();
				var st = $w.scrollTop();
				var maxTop = y - st - 5;
				var maxBottom = wh+st - y - 5;
				if(h > maxBottom)
				{
					if(h > maxTop)
					{
						if(maxTop > maxBottom)
						{
							// scrollable en haut
							h = maxTop;
							cmenu._div.css('height', h+'px').css('overflow-y', 'scroll');
							y -= h;
						}
						else
						{
							// scrollable en bas
							h = maxBottom;
							cmenu._div.css('height', h+'px').css('overflow-y', 'scroll');
						}
					}
					else
					{
						// menu ok en haut
						y -= h;
					}
				}
				else
				{
					// menu ok en bas
				}

				var maxRight = x+w-$w.scrollLeft();
				if (maxRight > ww)
				{
					x -= (maxRight-ww);
				}
			}
			return {'x':x,'y':y};
		},

		// Hide the menu, of course
		hide: function() {
			var cmenu=this;
			if (cmenu.shown) {
				if (cmenu.iframe) { $(cmenu.iframe).hide(); }
				if (cmenu.menu) { cmenu.menu[cmenu.hideTransition](cmenu.hideSpeed,((cmenu.hideCallback)?function(){cmenu.hideCallback.call(cmenu);}:null)); }
				if (cmenu.shadow) { cmenu.shadowObj[cmenu.hideTransition](cmenu.hideSpeed); }
			}
			cmenu.shown = false;
		}
	};

	// This actually adds the .contextMenu() function to the jQuery namespace
	$.fn.contextMenu = function(menu,options) {
		var cmenu = $.contextMenu.create(menu,options);
		return this.each(function(){
			$(this).bind(cmenu.openEvt,function(e){
				if(cmenu.menu.is(':visible'))
					cmenu.hide();
				else
				{
					$('body').trigger(cmenu.openEvt);
					cmenu.show(this,e);
				}
				return false;
			}).bind('mouseover',function(){return false;});
		});
	};
})(jQuery);
