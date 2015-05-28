/*
 * modal.win.js for BoardDX
 * @author phiDel (xe.phidel@gmail.com)
 * @refer NHN (developers@xpressengine.com) Modal Window
 */

(function($)
{
	var PIDMODAL = function(){
			this.topZidx = 0;
		},
		pidModal = new PIDMODAL();

	PIDMODAL.prototype.topIndex = function(target)
	{
		var $target = target ? $(target) : $('body'),
			$body = $target.is('body') ? $target : $target.closest('body'),
			zidx = this.topZidx;

		if(!zidx){
			zidx++;
			try{
				$body.find('> *')
					//.filter(function(){ return $(this).css('z-index') !== 'auto'; })
					.each(function(){
						//var  style = window.getComputedStyle(this),
						//	thzidx = parseInt(style.getPropertyValue('z-index'));
						var thzidx = parseInt($(this).css('z-index') || this.style.zIndex);
						if(zidx < thzidx) zidx = thzidx;
					});
			}catch(e){
				zidx = 999999;
			}
			this.topZidx = zidx;
		}

		return zidx + 99;
	};

	PIDMODAL.prototype.waitMessage = function(url, target)
	{
		var $target = target ? $(target) : $('body'),
			$body = $target.is('body') ? $target : $target.closest('body');

	    if(!$('div.wait[data-modal-child=message]', $body).length){
	    	url = url.setQuery('is_modal','');
	        $msg = $('<div class="message update wait" data-modal-child="message">')
	        .html('<p>'+waiting_message+'<br />If time delays continue, <a href="'+url+'"><span>click here</span></a>.</p>')
	        .css({position:'fixed', top:10, left:10, zIndex:pidModal.topIndex(target)});
	     	$body.append($msg);
	    }
	};

	PIDMODAL.prototype.iFrame = function(id, target)
	{
		var $body, $frame = $('#' + id, target);

		if(!$frame.length){
			$body = $('body', target);
			$frame = $('<section id="' + id + '" class="pid_modal-frame">').appendTo($body).hide();
		}else{
			$frame.not('.pid_modal-frame').addClass('pid_modal-target');
		}
		if(!$frame.find('.pid_modal-body').length) $frame.append($('<div class="pid_modal-body">'));

		return $frame;
	};

	PIDMODAL.prototype.backDrop = function(target)
	{
		var $bkdrop = $('#pidModalBackdrop', target);
		if(!$bkdrop.length){
			$bkdrop = $('<div id="pidModalBackdrop" class="pid_modal-backdrop">').appendTo($('body', target)).hide();
		}
		return $bkdrop;
	};

	$.fn.extend(
	{
		pidModalgoUrl: function(url, resize, callback, mdmode)
		{
			var $modal = $(this), $oFrm = $('#pidOframe', $modal) /*, is_iframe*/;

		    // false. the target position control
			var validModal = function(ifrm)
			{
	        	var doc = ifrm.contentDocument || ifrm.contentWindow.document;
		        if(!$('script[src*='+_PID_MODULE_+']', doc).length)
		        {
		        	$(doc).find('a').click(function(e)
		        	{
		        		e.stopPropagation();
		        		e.preventDefault();
		        		parent.location.replace($(this).attr('href'));
		        	});

		        	$oFrm.pidModalAutoResize();
		        }
			};

			url = url.setQuery('is_modal', mdmode);

			//is_iframe = (/msie|chromium|chrome/.test(navigator.userAgent.toLowerCase()) === true);
			if($oFrm.length)
			{
				//(is_iframe) ? $oFrm.get(0).src = url : $oFrm.attr('data', url);
				$oFrm.get(0).src = url;
			}else{
				// object는 아직 문제가 많아, 그냥 iframe 사용하기로...
				$oFrm = $('<iframe id="pidOframe" data-resize="'+ resize +'" allowTransparency="true" frameborder="0" scrolling="no" />')
					.on('load', function(){$(this).parent().scrollTop(0); validModal(this); callback(this);})
					.attr('src', url).appendTo($('.pid_modal-body:eq(0)', $modal));

				// if(is_iframe) {; autoResize(this, resize)
				// 	$('<iframe id="oFrm" allowTransparency="true" frameborder="0" scrolling="'+(scroll ? scroll : 'auto')+'" />')
				// 		.load(function(){$(this).pidModalResize(resize);}).attr('src', url).appendTo($('.pid_modal-body:eq(0)', $modal));
				// } else {
				// 	if(scroll === 'no') scroll = 'hidden';
				// 	$('<object id="oFrm" style="overflow-x:hidden;overflow-y:'+(scroll ? scroll : 'auto')+'" />')
				// 		.load(function(){$(this).pidModalResize(resize);}).attr('data', url).appendTo($('.pid_modal-body:eq(0)', $modal));
				// }
			}

			pidModal.waitMessage(url, $oFrm.closest('body'));
		},
		pidModalSetTitle: function(header, footer, close_callback)
		{
			var htmlDecode = function(str)
			{
				var o = {'&amp;': '&','&gt;': '>','&lt;': '<','&quot;': '"'}, r;
				r = new RegExp('(' + $.map(o,function(v,k){return k;}).join('|') + ')', 'g');
				return str.replace(r, function(m, c) {return o[c];});
			};

			var $modal = $(this), $close, $pmh, $pmf,
				hstr = htmlDecode(header),
				fstr = htmlDecode(footer),
				childs = ['pid_modal-head', 'pid_modal-foot'];

			$pmh = $modal.find('div.' + childs[0]);
			if(!$pmh.length) $pmh = $('<div class="'+childs[0]+'">').hide().prependTo($modal);
			$pmf = $modal.find('div.' + childs[1]);
			if(!$pmf.length) $pmf = $('<div class="'+childs[1]+'">').hide().appendTo($modal);

			if(hstr && hstr.substring(0,9) !== ':INHERIT:')
			{
				if(hstr.substring(0,9) === ':GETHTML:')
					hstr = $(hstr.substring(9)).html();
				else hstr = '<div>' + hstr + '</div>';
				$pmh.html(hstr).css('display',hstr?'block':'none');
			}
			else if(!hstr) $pmh.html('').css('display','none');

			if(fstr && fstr.substring(0,9) !== ':INHERIT:')
			{
				if(fstr.substring(0,9) === ':GETHTML:')
					fstr = $(fstr.substring(9)).html();
				else fstr = '<div>' + fstr + '</div>';
				$pmf.html(fstr).css('display',fstr?'block':'none');
			}
			else if(!fstr) $pmf.html('').css('display','none');

			// set close button
			$close = $modal.find('.pid_modal-close');
			if(!$close.length)
			{
				$close =
					$('<button type="button" title="press esc to close" class="pid_modal-close">&times;</button>')
					.click(close_callback);
				$modal.prepend($close);
			}
		},
		pidModalAutoResize: function()
		{
	        var $this = $(this), $modal, $bdrop, $body, $moh, $mof, $mob, pw, ph, smode, timer;

	        $body = $this[0].contentDocument || $this[0].contentWindow.document;
	        if($body === undefined) return;

	        $body = $('body', $body);
	        $modal = $this.parent().parent();
			$target = $modal.closest('body');

	        if(!$body.text()){
	        	$modal.hide();
	        	return;
	        }

	        clearInterval($this.data('timer') || 0);

	        //var isIframe = (parent.location != parent.parent.location);
	        // if(isIframe) {
	        // 	$bg = $('.pid_modal-backdrop', $target);
	        // } else $bg = $(parent);

	        $this.add($body.addClass('pid_modal-document')).scrollTop(0);

			$moh = $('div.pid_modal-head', $modal);
			$mob = $('div.pid_modal-body', $modal);
			$mof = $('div.pid_modal-foot', $modal);

			smode = $this.attr('data-resize') || 'auto';

	        var setTargetTimer = function()
	        {
	        	return setInterval(function()
	        	{
			        var h = $body.outerHeight(true);

			        if(h > 10){
		        		if($this.height() !== h) $this.height(h);
			        	if($mob.height() !== h) $mob.height(h);
			        }

	        		if(!$modal.find('#pidOframe').length) clearInterval(timer);
	        	}, 500);
	        },
	        setModalTimer = function()
	        {
	        	return setInterval(function()
	        	{
	        		var t, h, fh, bh;

	        		pw = $bdrop.width();
	        		fh = $body.outerHeight(true);

					$modal.outerWidth(pw - 80);

	        		if(fh > 10)
	        		{
	        			ph = $bdrop.height();
	        			h = ($moh.outerHeight(true) || 0) + ($mof.outerHeight(true) || 0);

	        			bh = ph - h - 100;
		        		bh = (smode === 'hfix' || (fh > bh)) ? bh : fh;

		        		if($this.height() !== fh || $mob.height() !== bh)
		        		{
		        			$this.height(fh);
							$mob.css({'height' : bh+'px', 'overflow-y' : (fh>bh?'auto':'hidden')});
						}

				        h = $modal.outerHeight(true);
				        t = Math.floor(((ph - h) / 2) - 10);
				        $modal.css({top: (t > 10 ? t : 10), left: Math.floor((pw - $modal.outerWidth(true)) / 2)});
				    }
	        		if(!$modal.find('#pidOframe').length) clearInterval(timer);
	        	}, 500);
	        };

	        //is target frame
	        if($modal.is('.pid_modal-target'))
	        {
				$mob.height(1);
				$modal.show();

		        timer = setTargetTimer();

				$('[data-modal-child=message]', $target)
		        .fadeOut(1500, function(){
		            $(this).remove();
		        });
	        }else{

	        	$bdrop = pidModal.backDrop($target);
	        	pw = $bdrop.width();
				$mob.height(1);
	        	$modal.outerWidth(pw - 80).show();

		        if ($modal.position().left < 1)
		        {
		            $modal.animate({
		                top: 10,
		                left: Math.floor((pw - $modal.outerWidth(true)) / 2)
		            },{
		                complete: function()
		                {
		                	timer = setModalTimer();

					        $('[data-modal-child=message]', $target)
					        .fadeOut(1500, function(){
					            $(this).remove();
					        });
		                }
		            });
		        } else {timer = setModalTimer();}
		    }

		    $this.data('timer', timer);
		},
		pidModalWindow: function(target, bg_close)
		{
			this
				.not('.pid_modal-anchor')
				.addClass('pid_modal-anchor')
				.each(function()
				{
					var a = ($(this).attr('type') || '').split('/');
					if(a[1] !== 'modal') return;
					this.modalId = a[2] ? a[2] : 'pidModalFrame';
				})
				.click(function()
				{
					var $this = $(this), $modal = pidModal.iFrame(this.modalId, target);

					$modal.hide().css({top:'0',left:'-150%'});
					$this.trigger('open.mw');

					// if($modal.data('state') === 'showing'){
					//  $this.trigger('open.mw');
					// }else{
					// 	$this.trigger('close.mw');
					// }

					return false;
				})
				.bind('open.mw', function()
				{
					var $this = $(this), $modal, $bdrop, $body, before_event, duration, url, mdmode, zidx = 0;

					// before event trigger
					before_event = $.Event('before-open.mw');
					$this.trigger(before_event);

					// is event canceled?
					if(before_event.isDefaultPrevented()) return false;

					$modal = pidModal.iFrame(this.modalId, target);

					if(!$modal.is('.pid_modal-target'))
					{
						// set header, footer, close
						$modal.pidModalSetTitle(
							($this.attr('data-header') || $this.attr('title')) || '',
							$this.attr('data-footer') || '',
							function(){ $this.trigger('close.mw'); return false; }
						);
					}

					url = ($this.data('go-url') || $this.attr('href')) || 'about:blank';
					mdmode = $modal.is('.pid_modal-target') ? 3 : 1;

					if($modal.data('state') !== 'showing')
					{
						// set state : showing
						$modal.data('state', 'showing');
						// get duration
						duration = $this.data('duration') || 'fast';

						if($modal.is('.pid_modal-target'))
						{
							$modal.find('> *').hide(duration, function(event){
								$modal.find('> div.pid_modal-body').show();
							});
						}else{
							$(document).on('keydown.mw', function(event)
							{
								if(event.which === 27){ //esc
									$this.trigger('close.mw');
									return false;
								}
							});

							$bdrop = pidModal.backDrop(target);
							$bdrop.css('z-index', '1');
							$modal.not('.pid_modal-target').css('z-index', '2');

							zidx = pidModal.topIndex(target);
							$body = $('body', target);
							$bdrop.data('body_overflow', $body.css('overflow-x')).css('z-index', zidx).show();
							$bdrop.css('z-index', zidx).show();
							$modal.css('z-index', zidx + 1).find('button.pid_modal-close:first').focus();
							$body.css('overflow-x','hidden');
							if(bg_close) $bdrop.click(function(){ $this.trigger('close.mw'); return false; });
						}
					}

					// after event trigger
					var after = function(oframe){$this.trigger('after-open.mw', [oframe]);};

					$modal.find('div.pid_modal-body').css('overflow-y', 'hidden');
					$modal.pidModalgoUrl(url, $this.attr('data-resize') || 'auto', after, mdmode);

					//if(url){}else{$modal.fadeIn(duration, after);}
				})
				.bind('close.mw', function()
				{
					var parentReload = function(){
						var url = window.location.href
								.setQuery('is_modal','')
								.setQuery('document_srl','');
						window.parent.location.replace(url);
					};

					var $this = $(this), $modal, $bdrop, before_event, duration;

					// before event trigger
					before_event = $.Event('before-close.mw');
					$this.trigger(before_event);
					// is event canceled?
					if(before_event.isDefaultPrevented()) return false;

					try{
						$modal = pidModal.iFrame(this.modalId, target);
		        		if($modal.attr('data-parent-reload')||0) parentReload();
					}catch(e){
						// 쓰댕 ie 에서 is,not,find 함수 못 찾는 버그 처리
						// jquery.js 문제라는말이 있던대 내가 풀 문제는 아니기에...
						parentReload();
						return false;
					}

					// get duration
					duration = $this.data('duration') || 'fast';
					// set state : hiding
					$modal.data('state', 'hiding');
					// after event trigger
					var after = function(){$this.trigger('after-close.mw');};

					//this.focus();

					if($modal.is('.pid_modal-target'))
					{
						$modal.find('> *').show(duration, function(event)
						{
							$modal.find('> div.pid_modal-head').hide();
							$modal.find('> div.pid_modal-body').hide();
							$modal.find('> div.pid_modal-foot').hide();
						});
					}else{
						$bdrop = pidModal.backDrop(target);
						$modal.fadeOut(duration, function()
						{
							after();
							$(this).not('.pid_modal-target').hide();
							$bdrop.hide();
							$('body', target).css('overflow-x', $bdrop.hide().data('body_overflow') || 'auto');
							$(this).find('> div.pid_modal-body').children().remove();
						});
					}
				});

		},
		pidModalFlashFix: function()
		{
			$('embed[type*=flash]').each(function(){var o=$(this);if(o.attr('wmode')!='transparent');o.attr('wmode', 'opaque');});
			$('iframe[src*=youtube]').each(function(){var o=$(this);o.attr('src',(o.attr('src')).setQuery('wmode', 'opaque'));});
		}
	});

	try {
		var $oFrm = $(window.frameElement);
		if($oFrm.is('[id=pidOframe]'))
		{
			$frmDoc = $oFrm.closest('body');

			pidModal.waitMessage(window.location.href, $frmDoc);
			$oFrm.pidModalAutoResize();

			$(document)
			.on('ready', function()
			{
				$('[data-modal-hide]').on('click', function(){
					$oFrm.parent().parent().find('button.pid_modal-close:first').click();
					return false;
				});
			});

			$(window)
			.on("unload", function()
			{
				$oFrm.parent().height(1).parent().hide().css({top:'0',left:'-150%'});
				$('[data-modal-child=message]', $frmDoc).remove();
			});
		}
	}catch(e){}
}
)(jQuery);