(function($) {
	
	$.validator.loginValidate = function(login) {
		if (!login || login.length < 8)
			return false;
		return !(/[^a-zA-Z0-9]/.test(login));
	}
	
	$.validator.addMethod("login", function(value, element, usernameField) {
		var login = element.value;
			
		return $.validator.loginValidate(login);
	}, "&nbsp;");
	
})(jQuery);