(function ($) {
	'use strict';

	$(function () {
		// Color picker for background color
		if ($.fn.wpColorPicker) {
			$('.bsh-color-picker').wpColorPicker();
		}

		// Opacity slider: update displayed percentage on change
		$('.bsh-opacity-slider').on('input change', function () {
			var val = parseFloat($(this).val());
			var pct = Math.round(val * 100);
			$(this).siblings('.bsh-opacity-value').text(pct + '%');
		});

		// Reset to defaults: confirm before submit
		$('#bsh-reset-form').on('submit', function () {
			return confirm(bshAdmin.resetConfirm || 'Reset all settings to defaults?');
		});
	});
})(jQuery);
