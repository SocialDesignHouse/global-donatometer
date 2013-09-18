(function($) {

	var donatometer = (function() {
		var pub = {};

		var goal, amount, success, togo;

		pub.init = function init(el) {
			goal = parseFloat(el.find('.progress').data('goal')).toFixed(2);
			amount = parseFloat(el.find('.progress').data('amount')).toFixed(2);
			success = el.find('.progress').data('success');
			togo = el.find('.progress').data('togo');

			var percentage = amount / goal * 100;

			if(percentage > 100) {
				percentage = 100;

				el.find('.progress-msg').html('$' + amount + ' raised <span class="goal">' + success + '</span>').css({
					'width' : 'auto'
				});
			}

			el.find('.progress').css({
				'width' : percentage + '%'
			});
		};

		return pub;
	}());

	$(window).load(function() {
		if($('#social-donatometer').length) {
			var $donatometer = $('#social-donatometer');
			donatometer.init($donatometer);
		}
	});

})(jQuery);