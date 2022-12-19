//noinspection JSUnusedGlobalSymbols
$.extend($.validator.methods, {
	maxlength: function (value, element, param) {
		if (this.optional(element))
			return true;

		var length = (element.files && element.files.length) ? element.files[0].size / 1024
			: ($.isArray(value) ? value.length : this.getLength(value, element));
		return length <= param;
	},
	url: function (value, element) {
		// see also https://mathiasbynens.be/demo/url-regex
		return this.optional(element) ||
			/^(?:(?:(?:https?|ftp):)?\/\/)?(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})).?)(?::\d{2,5})?(?:[/?#]\S*)?$/i.test(value);
	}
});

//noinspection JSUnusedGlobalSymbols
$.extend($.validator.prototype, {
	errorsFor: function (element) {
		var name = this.idOrName(element),
			describer = $(element).attr("aria-describedby") || (name + "-error"),
			selector = "label[for='" + name + "'], label[for='" + name + "'] *";

		// aria-describedBy should directly reference the error element
		if (describer) {
			selector += ", #" + describer.replace(/\s+/g, ", #");
		}
		return this.errors().filter(selector);
	},
	customDataMessage: function (element, method) {
		var msg = $(element).data("msg" + method.charAt(0).toUpperCase() + method.substring(1).toLowerCase());
		return (typeof msg != "undefined" && null !== msg) ? msg : $(element).data("msg");
	}
});

// Return true if the field value matches the given format RegExp
$.validator.addMethod("pattern", function (value, element, param) {
	if (this.optional(element))
		return true;

	if (typeof param === "string")
		param = new RegExp(param.charAt(0) == "^" ? param : "^(?:" + param + ")$");
	return param.test(value);
}, "Invalid format.");

$.validator.addMethod("notEqualTo", function (value, element, param) {
	return this.optional(element) || !$.validator.methods.equalTo.call(this, value, element, param);
}, "Please enter a different value, values must not be the same.");

$.validator.addMethod("integer", function (value, element) {
	return this.optional(element) || /^-?\d+$/.test(value);
}, "A positive or negative non-decimal number please");

$.validator.addMethod("ipv4", function (value, element) {
	return this.optional(element)
		|| /^(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)$/i.test(value);
}, "Please enter a valid IP v4 address.");

// Accept a value from a file input based on a required mime-type
$.validator.addMethod("accept", function (value, element, param) {
	// Browser does not support element.files and the FileList feature
	if (this.optional(element) || $(element).attr("type") !== "file" || !element.files || !element.files.length)
		return true;

	// Split mime on commas in case we have multiple types we can accept
	var typeParam = typeof param === "string" ? param.replace(/\s+/g, "") : "image/*",
		// Escape string to be used in the regex
		regex = new RegExp(".?(" + typeParam.replace(/[\-\[\]\/\{}\(\)\+\?\.\\\^\$\|]/g, "\\$&").replace(/,/g, "|").replace(/\/\*/g, "/.*") + ")$", "i");

	// Grab the mime-type from the loaded file, verify it matches
	for (var i = 0; i < element.files.length; i++) {
		if (!regex.test(element.files[i].type))
			return false;
	}

	// We've validated each file
	return true;
}, "Please enter a value with a valid mime-type.");

$.validator.addMethod("notBlank", function (value/*, element*/) {
	// only for text input
	return !!value && ($.trim(value).length > 0);
}, "This field cannot be blank.");

$.validator.addMethod("notMatch", function (value, element, param) {
	//noinspection JSUnresolvedVariable
	return this.optional(element) || !$.validator.methods.pattern.call(this, value, element, param);
}, "Invalid format.");

// Remove html tags, space chars, and punctuations
function stripHtml(value) {
	return value.replace(/<.[^<>]*?>/g, " ").replace(/&nbsp;|&#160;/gi, " ")
		.replace(/[.(),;:!?%#$'"_+=\/\-“”’]*/g, "");
}

$.validator.addMethod("maxWords", function (value, element, params) {
	return this.optional(element) || stripHtml(value).match(/\b\w+\b/g).length <= params;
}, $.validator.format("Please enter {0} words or less."));

$.validator.addMethod("minWords", function (value, element, params) {
	return this.optional(element) || stripHtml(value).match(/\b\w+\b/g).length >= params;
}, $.validator.format("Please enter at least {0} words."));

$.validator.addMethod("username", function (value, element) {
	return this.optional(element) || /^[a-zA-Z0-9.!#$%&'*+\/=?@^_`{|}~-]+$/.test(value);
}, "Invalid format.");

// http://jqueryvalidation.org/email-method/
$.validator.addMethod("emails", function (value, element) {
	// https://html.spec.whatwg.org/multipage/forms.html#valid-e-mail-address
	return this.optional(element) ||
		/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*(\s*[,;]\s*[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*)*$/.test(value);
}, "Please enter valid email addresses.");
