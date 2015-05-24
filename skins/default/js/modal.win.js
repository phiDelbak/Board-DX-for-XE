/*
 * modal.win.js for BoardDX
 * @author phiDel (xe.phidel@gmail.com)
 * @refer NHN (developers@xpressengine.com) Modal Window
 */

(function($)
{
	var pidModal = function(id, target)
	{
		this.id = id;
		this.target = target ? target : '';
	};

	pidModal.prototype.getBackDrop = function()
	{
		var $bkdrop = $('#pidModalBackdrop', this.target);
		if(!$bkdrop.length)
		{
			$bkdrop = $('<div id="pidModalBackdrop" class="pid_modal-backdrop">').appendTo($('body', this.target)).hide();
		}
		return $bkdrop;
	};

	pidModal.prototype.getModalFrame = function()
	{
		var $body, $frame = $('#' + this.id, this.target);

		if(!$frame.length)
		{
			$body = $('body', this.target);
			$frame = $('<section id="' + this.id + '" class="pid_modal-frame">').appendTo($body).hide();
		}else{
			$frame.not('.pid_modal-frame').addClass('pid_target-frame');
		}
		if(!$frame.find('.pid_modal-body').length) $frame.append($('<div class="pid_modal-body">'));

		return $frame;
	};

	pidModal.prototype.setTitle = function(header, footer, close_callback)
	{
		var htmlDecode = function(str)
		{
			var o = {'&amp;': '&','&gt;': '>','&lt;': '<','&quot;': '"'}, r;
			r = new RegExp('(' + $.map(o,function(v,k){return k;}).join('|') + ')', 'g');
			return str.replace(r, function(m, c) {return o[c];});
		};

		var $modal = this.getModalFrame(), $close, $pmh, $pmf, tmp,
			hstr = htmlDecode(header),
			fstr = htmlDecode(footer),
			childs = ['pid_modal-head', 'pid_modal-foot'];

		if(hstr.substring(0,9) !== ':INHERIT:')
		{
			if(hstr.substring(0,9) === ':GETHTML:') hstr = $(hstr.substring(9)).html();
			$pmh = $modal.find('div.' + childs[0]);
			if(!$pmh.length) $pmh = $('<div class="'+childs[0]+'">').hide().prependTo($modal);
			tmp = (hstr) ? $pmh.html(hstr).show() : $pmh.html('').hide();
		}

		if(fstr.substring(0,9) !== ':INHERIT:')
		{
			if(fstr.substring(0,9) === ':GETHTML:') fstr = $(fstr.substring(9)).html();
			$pmf = $modal.find('div.' + childs[1]);
			if(!$pmf.length) $pmf = $('<div class="'+childs[1]+'">').hide().appendTo($modal);
			tmp = (fstr) ? $pmf.html(fstr).show() : $pmf.html('').hide();
		}

		// set close button
		$close = $modal.find('.pid_modal-close');
		if(!$close.length)
		{
			$close =
				$('<button type="button" title="press esc to close" class="pid_modal-close">&times;</button>')
				.click(close_callback);
			$modal.prepend($close);
		}
	};

	pidModal.prototype.goUrl = function(url, resize, callback)
	{
		var $modal = this.getModalFrame(), $bdrop = this.getBackDrop(),
			$oFrm = $('#pidOframe', $modal), waitmsg = $('div.wait[data-modal-child=message]') /*, is_iframe*/;

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

	        	$oFrm.pidAutoResize($oFrm.attr('data-resize'));
	        }
		};

	    if(!waitmsg.length)
	    {
	        waitmsg = $('<div class="message update wait" data-modal-child="message">')
	        .html('<p>'+waiting_message+'<br />If time delays continue, <a href="'+url.setQuery('is_modal','')+'"><span>click here</span></a>.</p>');
	     	$('body', this.target).append(waitmsg);
	    }

	    waitmsg.css({position:'fixed', top:10, left:10, zIndex:($bdrop.css('z-index')+1 || 99999)});

		url = url.setQuery('is_modal', '1');

		//is_iframe = (/msie|chromium|chrome/.test(navigator.userAgent.toLowerCase()) === true);
		if($oFrm.length)
		{
			//(is_iframe) ? $oFrm.get(0).src = url : $oFrm.attr('data', url);
			$oFrm.get(0).src = url;
		}else{
			// object는 아직 문제가 많아, 그냥 iframe 사용하기로...
			$oFrm = $('<iframe id="pidOframe" data-resize="'+ resize +'" allowTransparency="true" frameborder="0" scrolling="no" />')
				.css({height: '100%', width: '100%'})
				.on('load', function(){callback(); validModal(this); $(this).parent().scrollTop(0);})
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
	};

	pidModal.prototype.getTopIndex = function()
	{
		var $body = $('body', this.target), zidx = 0;

		$body.find('> *')
			.filter(function(){ return $(this).css('z-index') !== 'auto'; })
			.each(function(event)
			{
				var thzidx = parseInt($(this).css('z-index'));
				if(zidx < thzidx) zidx = thzidx;
			});

		return zidx + 1;
	};

	$.fn.pidAutoResize = function(sizemode)
	{
        var $win = $(this), $modal, $bdrop, $body, $moh, $mof, $mob, ph, t, h, ch, timer;

        $body = $win[0].contentDocument || $win[0].contentWindow.document;
        if($body === undefined) return;

        $body = $('body', $body);
        if($body.is(':empty')) return;

        clearInterval($win.data('timer') || 0);

        //var isIframe = (parent.location != parent.parent.location);
        // if(isIframe) {
        // 	$bg = $('.pid_modal-backdrop', $modal.closest('body'));
        // } else $bg = $(parent);

        $win.add($body).scrollTop(0).css({padding: '0', margin: '0', overflow: 'hidden'});

        $modal = $win.parent().parent();
		$moh = $('div.pid_modal-head', $modal);
		$mob = $('div.pid_modal-body', $modal);
		$mof = $('div.pid_modal-foot', $modal);

        //is target frame
        if($modal.is('.pid_target-frame'))
        {
			$('[data-modal-child=message]', $modal.closest('body'))
	        .fadeOut(1500, function()
	        {
	            $(this).remove();
	        });

        	timer = setInterval(function()
        	{
		        h = $body.outerHeight(true);
		        if(h > 10)
		        {
	        		$win.height(h);
		        	$mob.height(h);
		        }
        		if(!$modal.find('#pidOframe').length) clearInterval(timer);
        	}, 500);

        	$modal.show();
        }else{
        	$modal.show();

        	$bdrop = $modal.closest('body').find('#pidModalBackdrop');

        	pw = $bdrop.outerWidth(true);
        	ph = $bdrop.outerHeight(true);

			$mob.outerWidth(pw - 80);

	        if ($modal.position().left < 1)
	        {
	            $modal.animate({
	                top: 10,
	                left: Math.floor((pw - $modal.outerWidth(true)) / 2)
	            },{
	                complete: function()
	                {
	                	$modal.height('');

				        $('[data-modal-child=message]', $modal.closest('body'))
				        .fadeOut(1500, function()
				        {
				            $(this).remove();
				        });
	                }
	            });
	        }

        	timer = setInterval(function()
        	{
        		pw = $bdrop.outerWidth(true);
        		ph = $bdrop.outerHeight(true);

				$mob.outerWidth(pw - 80);

        		h = $moh.outerHeight(true) || 0;
        		h = h + $mof.outerHeight(true) || 0;

        		ch = $body.outerHeight(true);

        		if(ch > 10)
        		{
	        		if($win.height() !== ch) $win.height(ch);

	        		if(sizemode === 'hfix' || (ch > (ph - h - 100))) ch = ph - h - 100;
	        		if($mob.height() !== ch) $mob.height(ch);

					$mob.css('overflow-y', $win.outerHeight() > $mob.height() ? 'auto' : 'hidden');

			        h = $modal.outerHeight(true);
			        t = Math.floor(((ph - h) / 2) - 10);

			        $modal.css({top: (t > 10 ? t : 10), left: Math.floor((pw - $modal.outerWidth(true)) / 2)});
			    }
        		if(!$modal.find('#pidOframe').length) clearInterval(timer);
        	}, 500);
	    }

	    $win.data('timer', timer);
    };

	$.fn.pidModalWindow = function(target, bg_close)
	{
		this
			.not('.pid_modal-anchor')
			.addClass('pid_modal-anchor')
			.each(function()
			{
				var $this = $(this), $parmd, a;

				// , url, t, i, c,
				// 	act = $this.attr('data-modal-act') || '',
				// 	param = $this.attr('data-modal-param') || '';
				// if(act || param){
				// 	a = param.split(',');
				// 	c = a.length - 1;
				// 	//첫 문자가 true면 주소 초기화
				// 	t = (a.length > 0 && a[0]==='true') ? 1 : 0;
				// 	url = t ? default_url : current_url;
				// 	url = url.setQuery('act', act);
				// 	if(t) url = url.setQuery('mid', current_mid);
				// 	for (i = t; i < c; i=i+2) url = url.setQuery(a[i], a[i+1] || '');
				// 	$this.data('go-url', url);
				// }

				a = ($this.attr('type') || '').split('/');
				if(a[1] !== 'modal') return;
				this.modal = new pidModal(a[2] ? a[2] : 'pidModalFrame', target);
			})
			.click(function()
			{
				var $this = $(this), $modal = this.modal.getModalFrame();

				if($modal.data('state') === 'showing')
				{
					if($modal.is('.pid_target-frame')) return false;
					$this.trigger('close.mw');
				}else{
					$this.trigger('open.mw');
				}

				return false;
			})
			.bind('open.mw', function()
			{
				var $this = $(this), $modal, $bdrop, $body, before_event, duration, url, zidx = 0;

				$modal = (this.modal.getModalFrame()).css({top:'0',left:'-150%'});

				// before event trigger
				before_event = $.Event('before-open.mw');
				$this.trigger(before_event);

				// is event canceled?
				if(before_event.isDefaultPrevented()) return false;

				// Position initialize
				if(!$modal.is('.pid_target-frame'))
				{
					$modal.css({top:'0',left:'-150%'});

					// set header, footer, close
					this.modal.setTitle(
						($this.attr('data-header') || $this.attr('title')) || '',
						$this.attr('data-footer') || '',
						function(){ $this.trigger('close.mw'); return false; }
					);
				}

				// after event trigger
				var after = function(){$this.trigger('after-open.mw');};

				url = ($this.data('go-url') || $this.attr('href')) || 'about:blank';

				if($modal.data('state') !== 'showing')
				{
					// set state : showing
					$modal.data('state', 'showing');
					// get duration
					duration = $this.data('duration') || 'fast';

					if($modal.is('.pid_target-frame'))
					{
						$modal.find('> *').hide(duration, function(event){
							$modal.find('> div.pid_modal-body').show();
						});
					}else{
						$(document).bind('keydown.mw', function(event)
						{
							if(event.which === 27){ //esc
								$this.trigger('close.mw');
								return false;
							}
						});

						$bdrop = this.modal.getBackDrop();
						$bdrop.css('z-index', '1');
						$modal.not('.pid_target-frame').css('z-index', '2');

						zidx = this.modal.getTopIndex();

						//$body = $('body', this.modal.target);
						//$bdrop.data('body_overflow', $body.css('overflow')).css('z-index', zidx).show();
						$bdrop.css('z-index', zidx).show();
						$modal.css('z-index', zidx + 1).find('button.pid_modal-close:first').focus();
						//$body.css('overflow','hidden');
						if(bg_close) $bdrop.click(function(){ $this.trigger('close.mw'); return false; });
					}
				}

				this.modal.goUrl(url, $this.attr('data-resize') || 'auto', after);
				//if(url){}else{$modal.fadeIn(duration, after);}
			})
			.bind('close.mw', function()
			{
				var $this = $(this), $modal, $bdrop, before_event, duration;

				$modal = this.modal.getModalFrame();

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
				var after = function(){$this.trigger('after-close.mw');};

				if($modal.is('.pid_target-frame'))
				{
					$modal.find('> *').show(duration, function(event)
					{
						$modal.find('> div.pid_modal-head').hide();
						$modal.find('> div.pid_modal-body').hide();
						$modal.find('> div.pid_modal-foot').hide();
					});
				}else{
					$bdrop = this.modal.getBackDrop();

					$modal.fadeOut(duration, function()
					{
						after();
						$(this).not('.pid_target-frame').hide();
						$bdrop.hide();
						//$('body', target).css('overflow', $bdrop.hide().data('body_overflow') || 'auto');
						$(this).find('> div.pid_modal-body').children().remove();
					});
				}

				$this.focus();
			});
	};

	$.fn.pidModalFlashFix = function()
	{
		$('embed[type*=flash]',this).each(function(){var o=$(this);if(o.attr('wmode')!='transparent');o.attr('wmode', 'opaque');});
		$('iframe[src*=youtube]',this).each(function(){var o=$(this);o.attr('src',(o.attr('src')).setQuery('wmode', 'opaque'));});
	};

	try {
		var $oFrm = $(window.frameElement);
		if($oFrm.is('[id=pidOframe]'))
		{
			$(document)
			.on('ready', function()
			{
				$oFrm.pidAutoResize($oFrm.attr('data-resize'));

				$('[data-modal-hide]').on('click', function()
				{
					$oFrm.parent().parent().find('button.pid_modal-close:first').click();
					return false;
				});
			});

			$(window)
			.on("unload", function()
			{
				 $('body').height(1);
				 $oFrm.height('').parent().height(1);
				 $('[data-modal-child=message]', $oFrm.closest('body')).remove();
			});
		}
	}catch(e){}
}
)(jQuery);