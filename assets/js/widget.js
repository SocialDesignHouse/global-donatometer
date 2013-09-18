

	/* -----------
	 Donatometer
	----------- */

	(function($) {

		var donatometer = (function() {
			var pub = {};

			pub.init = function init() {
				checkDateInput();
			};

			function checkDateInput() {
				Modernizr.load({
					test : Modernizr.inputtypes.date,
					nope : [
						'jquery-ui-1.10.3.custom.min.js',
						'datepicker.min.js'
					]
				});
			}

			return pub;
		}());

		$(function() {
			donatometer.init();
		});

	})(jQuery);