 /*
 * board.js for BoardDX
 * @author phiDel (xe.phidel@gmail.com, https://github.com/phiDelbak/Board-DX-for-XE)
 */

jQuery(function($)
{
	$(window)
	.load(function()
	{
		$('.scContent [data-hottrack] a.scHotTrack').each(function(e)
		{
			$('<span class="iefix" />').css({'width':$(this).width()+'px','height':$(this).height()+'px'}).appendTo(this);
		});

		// ie8 이하에서 last css fix
		if($.browser.msie===true&&Math.floor($.browser.version)<9)
		{
			$('#siLst thead tr th:not(hidden):last div').css('border-right-width','1px');
		}
	});
});
