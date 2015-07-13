jQuery(function($) {
	$(window).load(function() {
		// ie8 이하에서 last css fix
		if ($.browser.msie === true && Math.floor($.browser.version) < 9) {
			$('#siLst thead tr th:not(hidden):last div').css('border-right-width', '1px');
		}

		$('.scContent [data-hottrack] a.scHotTrack').each(function() {
			$('<span class="iefix" />').css({
				'width': $(this).width() + 'px',
				'height': $(this).height() + 'px'
			}).appendTo(this);
		});
	});
});
