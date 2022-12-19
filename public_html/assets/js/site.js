// Initializing after page loaded.
$(document).ready(function () {
	$.ajaxSetup({cache: false});

	// Bootstrap tooltips
	if (typeof $.fn.tooltip != "undefined") {
		$('[title][title!=""][data-toggle="tooltip"], [title][title!=""][data-tooltip="tooltip"]').click(function () {
			// Hide tooltip when dropdown is opened
			var $me = $(this);
			setTimeout(function () {
				$me.blur().tooltip("hide");
			}, 0);
		}).tooltip();
	}

	// jQuery Validation
	if (typeof $.validator != "undefined") {
		//noinspection JSUnusedGlobalSymbols
		$.validator.setDefaults({
			//onkeyup: false,
			errorElement: "p",
			ignore: ":disabled, :hidden:not(:file)",
			highlight: function (element) {
				$(element).parent("div")/*.removeClass("has-success")*/.addClass("has-error");
			},
			unhighlight: function (element) {
				$(element).parent("div").removeClass("has-error")/*.addClass("has-success")*/;
			}
		});

		$("form:not(.novalidate)").each(function () {
			$(this).validate();
		});
	}

	// AJAX modal
	$('.modal[data-modal-ajax]').each(function () {
		$(this).on("show.bs.modal", function (e) {
			$(this).data("relatedTarget", e.relatedTarget).find(".modal-body")
				.load($(this).data("modal-ajax"), function () {
					$('[data-toggle="tooltip"]', this).tooltip();
				});
		});
	});

	// Scroll to the specified element
	if (location.hash && location.hash.length > 1) {
		var $el = $("#" + location.hash.replace(/^#|;.*$/g, "") + ":visible");
		if ($el.length)
			$("html, body").animate({scrollTop: $el.offset().top}, 500);
	}
});
