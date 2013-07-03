(function($) {
	'use strict';

	$(function() {
		$('.dataTable').dataTable({
			'bJQueryUI': true
		});
		// Bootstrap compatibilty
		$('.dataTables_wrapper').addClass('form-inline');
	});
})(window.jQuery);
