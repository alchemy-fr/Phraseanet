var AudioPlayer = function () {
	var instances = [];
	var activePlayerID;
	var playerURL = "";
	var defaultOptions = {};
	var currentVolume = -1;
	
	function getPlayer(playerID) {
		return document.all ? window[playerID] : document[playerID];
	}
	
	return {
		setup: function (url, options) {
	        playerURL = url;
	        defaultOptions = options;
	    },

		getPlayer: function (playerID) {
			return getPlayer(playerID);
		},
	    
	    embed: function (elementID, options) {
			var instanceOptions = {};
	        var key;
	        var so;
			var bgcolor;
			var wmode;
			
			var flashParams = {};
			var flashVars = {};
			var flashAttributes = {};
	
	        // Merge default options and instance options
			for (key in defaultOptions) {
	            instanceOptions[key] = defaultOptions[key];
	        }
	        for (key in options) {
	            instanceOptions[key] = options[key];
	        }
	        
			if (instanceOptions.transparentpagebg == "yes") {
				flashParams.bgcolor = "#FFFFFF";
				flashParams.wmode = "transparent";
			} else {
				if (instanceOptions.pagebg) {
					flashParams.bgcolor = "#" + instanceOptions.pagebg;
				}
				flashParams.wmode = "opaque";
			}
			
			flashParams.menu = "false";
			
	        for (key in instanceOptions) {
				if (key == "pagebg" || key == "width" || key == "transparentpagebg") {
					continue;
				}
	            flashVars[key] = instanceOptions[key];
	        }
			
			flashAttributes.name = elementID;
			flashAttributes.style = "outline: none";
			
			flashVars.playerID = elementID;
			
			swfobject.embedSWF(playerURL, elementID, instanceOptions.width.toString(), "24", "9", false, flashVars, flashParams, flashAttributes);
			
			
			instances.push(elementID);
	    },
		
		syncVolumes: function (playerID, volume) {	
			currentVolume = volume;
			for (var i = 0; i < instances.length; i++) {
				if (instances[i] != playerID) {
					getPlayer(instances[i]).setVolume(currentVolume);
				}
			}
		},
		
		activate: function (playerID) {
			if (activePlayerID && activePlayerID != playerID) {
				getPlayer(activePlayerID).close();
			}
			
			activePlayerID = playerID;
		},
		
		load: function (playerID, soundFile, titles, artists) {
			getPlayer(playerID).load(soundFile, titles, artists);
		},
		
		close: function (playerID) {
			getPlayer(playerID).close();
			if (playerID == activePlayerID) {
				activePlayerID = null;
			}
		},
		
		open: function (playerID) {
			getPlayer(playerID).open();
		},
		
		getVolume: function (playerID) {
			return currentVolume;
		}
		
	}
	
}();
