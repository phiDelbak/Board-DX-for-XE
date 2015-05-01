/* NHN (developers@xpressengine.com) Modal Window
 * @Optimizer for XE by phiDel (xe.phidel@gmail.com) */



	jQuery.fn.pidModalFlashFix = function(){
		var $ = jQuery;
		$('embed[type*=flash]',this).each(function(){var o=$(this);if(o.attr('wmode')!='transparent');o.attr('wmode', 'opaque');});
		$('iframe[src*=youtube]',this).each(function(){var o=$(this);o.attr('src',(o.attr('src')).setQuery('wmode', 'opaque'));});
	};

// Modal Window
jQuery(function($){	
	var ESC = 27;
	var pidModalStack = [];
	var pidModalInitailZIndex = 1040;

	// modal backdrop
	var $pidModalBackdrop = $('<div class="pid_modal-backdrop" style="width:100%;height:100%;display:none"></div>').appendTo('body').hide();

	$.fn.pidModalWindow = function(){
		this
			.not('.pid_modal-window')
			.addClass('pid_modal-window')
			.each(function(){
				$( $(this).attr('href') ).hide();
			})
			.click(function(){
				var $this = $(this), $modal, $btnClose, disabled;

				// get and initialize modal window
				$modal = $( $this.attr('href') );

				if($modal.data('state') == 'showing') {
					$this.trigger('close.mw');
				} else {
					$this.trigger('open.mw');
				}
				return false;
			})
			.bind('open.mw', function(){
				var $this = $(this), $modal, $btnClose, disabled, before_event, duration, target = $this.attr('data-target') || '';

				// get modal window
				$modal = $( $this.attr('href') );

				// if stack top is this modal, ignore
				if(pidModalStack.length && pidModalStack[pidModalStack.length - 1].get(0) == $modal.get(0)){
					return;
				}

				if(!target && !$modal.parent('body').length) {
					$btnClose = $('<button type="button" class="pid_modal-close">&times;</button>');
					$btnClose.click(function(){ $modal.data('anchor').trigger('close.mw'); });
					$modal.find('[data-modal-hide]').click(function(){ $modal.data('anchor').trigger('close.mw'); });
					$('body').append($modal);
					$modal.prepend($btnClose); // prepend close button
				}
				else if(target)
				{
					if($modal.data('state') == 'showing') $this.trigger('close.mw');
					$(target).before($modal);
					$modal.data('target', target);
				}

				// set the related anchor
				$modal.data('anchor', $this);

				// before event trigger
				before_event = $.Event('before-open.mw');
				$this.trigger(before_event);

				// is event canceled?
				if(before_event.isDefaultPrevented()) return false;

				// get duration
				duration = $this.data('duration') || 'fast';

				// set state : showing
				$modal.data('state', 'showing');

				// after event trigger
				function after(){ $this.trigger('after-open.mw'); }

				if(!target || (target && $('[data-modal-hide]', $modal).length)){
					$(document).bind('keydown.mw', function(event){
						if(event.which == ESC) {
							$this.trigger('close.mw');
							return false;
						}
					});
				}

				$modal
					.fadeIn(duration, after)
					.find('button.pid_modal-close:first').focus();

				$('body').css('overflow','hidden');

				if(target){
					$(target).hide('fast');
				}else{
					// push to stack
					pidModalStack.push($modal);
					// show backdrop and adjust z-index
					var zIndex = pidModalInitailZIndex + ((pidModalStack.length - 1) * 2);
					$pidModalBackdrop.css('z-index', zIndex).show();
					var pidModalBackdropHeight = $pidModalBackdrop.height();
					var modalBodyHeight = pidModalBackdropHeight;

					modalBodyHeight -= $modal.find('.pid_modal-header:visible').height();
					modalBodyHeight -= $modal.find('.pid_modal-footer:visible').height();
					modalBodyHeight -= 150;

					$modal.find('.pid_modal-body').css('height', modalBodyHeight);
					$modal.css('z-index', zIndex + 1);
				}
			})
			.bind('close.mw', function(){
				var $this = $(this), before_event, $modal, duration, target;

				// get modal window
				$modal = $( $this.attr('href') );

				// if stack top is not this modal, ignore
				if(pidModalStack.length && pidModalStack[pidModalStack.length - 1].get(0) != $modal.get(0)){
					return;
				}

				// before event trigger
				before_event = $.Event('before-close.mw');
				$this.trigger(before_event);

				// is event canceled?
				if(before_event.isDefaultPrevented()) return false;

				// get duration
				duration = $this.data('duration') || 'fast';

				// set state : hiding
				$modal.data('state', 'hiding');

				// after event trigger
				function after(){ $this.trigger('after-close.mw'); }

				$modal.fadeOut(duration, after);
				$('body').css('overflow','auto');
				$this.focus();

				target = $modal.data('target') || '';

				if(target){
					$(target).show('fast');
				}else{
					// pop from stack
					pidModalStack.pop();
					// hide backdrop and adjust z-index
					var zIndex = pidModalInitailZIndex + ((pidModalStack.length - 1) * 2);
					if(pidModalStack.length){
						$pidModalBackdrop.css('z-index', zIndex);
					}else{
						$pidModalBackdrop.hide();
					}
				}
			});
	};

	/** phiDel (xe.phidel@gmail.com) **/
	// $('a.pidModalAnchor').pidModalWindow();

	$.fn.pidModalGoUrl = function(url){
		var $modal = $(this), frId = $modal.attr('data-modal-frame') || 'pidOframe',
			 waitmsg, sctop = ($modal.data('target') || '') ? $(window).scrollTop() : 0;

		$modal.data('frame_id', frId);

	    if(!$('.wait[data-modal-child=message]').length)
	    {
	        waitmsg = $('<div class="message update wait">').html(
	            '<p>' + waiting_message + '<br />If time delays continue, <a href="' + url.setQuery('is_modal','0') + '"><b>click here</b></a>.</p>'
	        ).attr('data-modal-child','message').css({'position':'absolute','left':'10px','z-index':'9'}).css('top', (sctop+10)+'px');
	     	$modal.append(waitmsg);
	    }

		if(typeof scroll != 'string') scroll = '';
		url = url.setQuery('is_modal','1');

		// ie6~8 은 object 못씀
		if(/msie|chromium/.test(navigator.userAgent.toLowerCase()) === true) {	
			return $('#'+frId, $modal).length ?
				window.frames[frId].location.replace(url) :
				$('<iframe id="'+frId+'" allowTransparency="true" frameborder="0" scrolling="'+(scroll ? scroll : 'auto')+'" />')
					.attr('src', url).appendTo($('.pid_modal-body:eq(0)', $modal))
				.load(function(e){
					$modal.pidModalResize($modal.attr('data-modal-resize'));
				});
		} else {    	
			if(scroll == 'no') scroll = 'hidden';
			return $('#'+frId, $modal).length ?
				$('#'+frId, $modal).attr('data', url):
				$('<object id="'+frId+'" style="overflow-x:hidden;overflow-y:'+(scroll ? scroll : 'auto')+'" />')
					.attr('data', url).appendTo($('.pid_modal-body:eq(0)', $modal))
				.load(function(){
					$modal.pidModalResize($modal.attr('data-modal-resize'));
				});
		}	
	};

	$('.pidModalAnchor')
	.bind('after-open.mw', function(e) {
		var $this = $(this), u = $this.data('goUrl') || '';
		if(u) $($this.attr('href')).pidModalGoUrl(u);
	}).bind('before-close.mw', function(e) {
		var $modal = $($(this).attr('href'));
		// 로딩중 안보이게 처리및 자원 제거
		$('[data-modal-child]', $modal).remove();
        $('.pid_modal-body', $modal).children().remove();
	}).pidModalWindow();
});
