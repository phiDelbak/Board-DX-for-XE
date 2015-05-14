/**
 * @js  beluxe skin
 * @author phiDel (https://github.com/phiDelbak/Board-DX-for-XE, xe.phidel@gmail.com)
 * @brief Js of the BoardDX skin
 */

jQuery(function($)
{
	$('a[href=#siManageBtn]')
	.click(function()
	{
		$('.scElps ._first').each(function(){$(this).css('width', ($(this).width() - 30) + 'px');});
		$('.scCheck').show('slow');

		var $a = $(this).next();

		$('select', $a).change(function() {
			var v = $(this).val() || '', s = [];
			$('input[name=cart]:checked').each(function(i) { s[i] = $(this).val(); });
			if(s.length<1) return alert('Please select the items.', this) || false;
			exec_json('beluxe.procBeluxeChangeCustomStatus', {mid:current_mid,new_value:v,target_srls:s.join(',')}, completeCallModuleAction);
			return;
		});

		$('a[data-type]', $a).click(function() {
			switch($(this).attr('data-type')) {
				case 'manage':popopen(request_uri+'?module=document&act=dispDocumentManageDocument','manageDocument');break;
				case 'admin': location.href = current_url.setQuery('act','dispBeluxeAdminModuleInfo');
			}
			return false;
		});

		$a.show('slow');
		$(this).hide().prevAll().hide();
		return false;
	});

	$('#siCat.tabn,#siCat.colm')
	.each(function()
	{
		var $i = $(this), iscolm = $i.hasClass('colm'), $u = $('ul', this), $a = $('li.active:last', this),
			dt = parseInt($u.css("margin-top")) || 0, uh = parseInt($u.height()) - dt,
			lh = parseInt($('li:first', this).height()) + parseInt($('li:first', this).css('margin-bottom'));

		// 선택된 분류가 있으면 위치 구하고 이동
		var nn = parseInt(($a.position($i) ? $a.position($i).top : 0) / lh);
		if(nn > 0) $u.css('margin-top', ((nn * lh) * -1)  + 'px');

		if(lh >= uh)
		{
			$('.scCaNavi',this).css('display','none');
			$u.css('margin-right',iscolm ? '18px' : '0');
		}
		else
		{
			$('.scCaNavi a:eq(0)',this).click(function(){
				var t = parseInt($u.css("margin-top"));
				if(t < dt) $u.css('margin-top', (t + lh + dt) + 'px');
				return false;
			});
			$('.scCaNavi a:eq(1)',this).click(function(){
				var t = parseInt($u.css("margin-top"));
				if((t - lh - dt) > -uh) $u.css('margin-top', (t - lh - dt) + 'px');
				return false;
			});
		}

		if(iscolm)
		{
			var $p = $('#siLst:not(.noheader):eq(0), #siHrm:not(.noheader):eq(0)'),
				$k = $('.scCaLock',this), cok = getCookie('scCaLock');

			if(!$p.length) return;
			if($p.length>1) $p = $($p.get(0));

			// 초반에 크기를 구하기 위해 visibility:hidden 사용했기에 숨기고 다시 켠다.
			$i.css({'display':'none','visibility':'visible'});
			$i.css({'top':$p.position().top+'px','width':$p.width()-10+'px'});

			if($k){
				$k.click(function() {
					if($(this).hasClass('active')){
						$(this).removeClass('active');
						setCookie('scCaLock','hide',null,'/');
					} else {
						$(this).addClass('active');
						setCookie('scCaLock','',null,'/');
					}
					return false;
				});
				if(cok=='hide') $k.removeClass('active');
			}

			if($i.attr('data-autohide') == 'true'){
				$('thead tr:first th:not(.sort), ul.scFrm:first', $p).mouseenter(function(e){
					var te = e.target, $isSp = $('> span.sort:eq(0)',te);
					if($isSp.length && (e.offsetX > $isSp.position().left)) return;
					$i.trigger('fadeIn.fast');
				});
				$(document).mousemove(function(e){
					var te = e.target;
					if ($(te).parents().add(te).index($i) > -1) return;
					if(!$i.is(':hidden')&&$i.css('opacity')=='1'&&!$k.hasClass('active')) $i.fadeOut();
				});
			}

			$i.bind('fadeIn.fast', function(){
				$(this).css({'top':$p.position().top+'px','width':$p.width()-10+'px'}).fadeIn('fast');
			}).bind('fadeOut.fast', function(){
				$i.fadeOut();
			});
		}
	});

	$('#siLst.gall .scInfo[data-autohide=true]')
	.each(function()
	{
		var $i = $(this),$p = $i.closest('.scItem'), $n = $('.nick_name',this),
			$m = $i.prev('span.prtImg'), fade = $i.attr('data-fade') == 'true';

		$p.mouseenter(function(e)
		{
			$m.hide('slow');
			if(fade) $i.fadeIn('slow'); else $i.slideDown();
			$n.show('slow');
		}).mouseleave(function(e)
		{
			$n.hide('slow');
			if(fade) $i.fadeOut(); else $i.slideUp();
			$m.show('slow');
		});
	});

	$('.scSns a')
	.click(function()
	{
		var $o = $('#siHrm li.scElps strong:eq(0)'), v, t = $(this).attr('data-type'),
			co = encodeURIComponent($o.text().trim()), rl = encodeURIComponent($o.attr('title'));
		switch(t)
		{
			case 'me': v = 'http://me2day.net/posts/new?new_post[body]=%22' + co + '%22%3A' + rl; break;
			case 'fa': v = 'http://www.facebook.com/share.php?t=' + co + '&u=' + rl; break;
			case 'de': v = 'http://www.delicious.com/save?v=5&noui&jump=close&url=' + rl + '&title=' + co; break;
			default: v = 'http://twitter.com/home?status=' + co + ' ' + rl; break;
		}
		popopen(v, '_pop_sns');
		return false;
	});

	$('a[href=#tbk_action][class=delete]')
	.click(function()
	{
		if(!confirm('Do you want to delete the selected trackback?')) return false;
		var srl = $(this).closest('li').find('a[name^=trackback_]').attr('name').replace(/.*_/g,'');
		exec_json('beluxe.procBeluxeDeleteTrackback', {mid:current_mid,trackback_srl:srl}, completeCallModuleAction);
		return false;
	});

	$('a[href=#popopen][data-srl]')
	.click(function()
	{
		var srl = $(this).attr('data-srl') || '';
		if(srl) popopen(srl.setQuery('is_poped','1'),'beluxePopup');
		return false;
	});

	$('a[href=#his_action][data-mode=delete][data-srl]')
	.click(function()
	{
		if(!confirm('Do you want to delete the selected history?')) return false;
		var srl = $(this).attr('data-srl') || 0,doc = $(this).attr('data-doc') || 0;
		exec_json('beluxe.procBeluxeDeleteHistory', {mid:current_mid,history_srl:srl,document_srl:doc}, completeCallModuleAction);
		return false;
	});

	$('a[href^=#][href$=recommend][data-type]')
	.click(function()
	{
		var $i = $(this), hr = $i.attr('href'), ty = $i.attr('data-type'), srl = $i.attr('data-srl');
		var params = {target_srl : srl, cur_mid : current_mid, mid : current_mid};

		exec_json(
			ty + '.proc' + ty.ucfirst() + (hr == '#recommend' ? 'VoteUp' : 'VoteDown'),
			params,
			function(ret_obj) {
				alert(ret_obj.message);
				if(ret_obj.error === 0)
				{
					var $e = $i.find('em.cnt');
					$e.text((parseInt($e.text()) || 0) + (hr == '#recommend' ? 1 : -1));
				}
			}
		);
		return false;
	});

	$('a[href=#declare][data-type]')
	.click(function()
	{
		var $i = $(this), ty = $i.attr('data-type'), srl = $i.attr('data-srl'),
			c, tmp, rec = $i.attr('data-rec') || '0';

		c = prompt(_PID_MSGS_.declare, '');
		// 브라우저에서 블럭 옵션이 떠서 다른 방법 씀
	    //if(typeof c != 'string' || !c.trim()) return alert(_PID_MSGS_.canceled) || false;
		if(typeof c != 'string')  return false;
		if(!c.trim())
		{
			tmp = $('<div class="dxc_minimsg">').html('Please enter the message.');
			$i.parent().after(tmp);
			tmp.delay(3000).fadeOut(2500, function() {$(this).remove();});
			return false;
		}
		exec_json(
			ty + '.proc' + ty.ucfirst() + 'Declare',
			{target_srl: srl, cur_mid: current_mid, mid: current_mid},
			function(ret_obj) {
				alert(ret_obj.message);
				if(ret_obj.error === 0 && rec != '0')
				{
					var t = '[Board DX] Declare, ' + ty + ': ' + srl,
						u = current_url.setQuery('comment_srl',(ty=='comment'?srl:''));
						c = c + '<br /><br /><a href="' + u + '">'+u+'</a>';
					exec_json('communication.procCommunicationSendMessage',
						{receiver_srl: rec, title: t, content: c},
						function(ret_obj2) {
							if(ret_obj2.error !== 0) alert(ret_obj2.message);
						}
					);
				}
			}
		);

		return false;
	});

	$('.btnAdopt button[data-adopt-srl]')
	.click(function()
	{
		var $i = $(this), c, srl = $i.attr('data-adopt-srl') || '', name = $i.attr('data-adopt-name') || '';

		c = prompt('Send thanks message to ' + name, '');
		// 브라우저에서 블럭 옵션이 떠서 다른 방법 씀
		// if(typeof c != 'string' || !c.trim()) return alert(_PID_MSGS_.canceled) || false;
		if(typeof c != 'string')  return false;
		if(!c.trim())
		{
			tmp = $('<div class="dxc_minimsg">').html('Please enter the message.');
			$i.parent().after(tmp);
			tmp.delay(3000).fadeOut(2500, function() {$(this).remove();});
			return false;
		}
		exec_json(
			'beluxe.procBeluxeAdoptComment',
			{comment_srl: srl, send_message: c},
			function(ret_obj) {
				alert(ret_obj.message);
				if(ret_obj.error === 0)
				{
					location.reload();
				}
			}
		);

		return false;
	});

	$('#siFbk .scFbWt form textarea[name=content]')
	.focus(function()
	{
		$('.scWusr', $(this).closest('form')).show('slow');
	});

	$('.scHLink[data-href]')
	.click(function()
	{
		window.open($(this).attr('data-href'), ($(this).attr('data-target') || ''));
		return false;
	});

	$('.scToggle[data-target]')
	.click(function()
	{
		$($(this).attr('data-target')).slideToggle();
		return false;
	});

	$('.scClipboard')
	.click(function()
	{
		var tg = $(this).attr('data-attr') || false;
		prompt('press CTRL+C copy it to clipboard...', (tg ? $(this).attr(tg) : $(this).text()));
		return false;
	});

	// ie8 이하에서 last css fix
	if($.browser.msie===true&&Math.floor($.browser.version)<9)
	{
		$('#siLst thead tr th:not(hidden):last div').css('border-right-width','1px');
	}

	$(window)
	.load(function()
	{
		// 모바일 사용안할때 크기가 너무 줄어들면 조절
		// 모바일 사용안하는 특정상황에만 필요한 경우라 onresize에선 처리안함
		$('table#siLst').each(function()
		{
			var $th =$(this), ww = $('#siBody').parent().width(),
				tr = /*$th.position().left + */$th.outerWidth();
			if(ww < tr){
				var ta = $('tr:eq(0) th.title', $th).outerWidth() || 150;
					tt = Math.floor((tr-ww+ta) / ($('tr:eq(0) th', $th).length - 3));
				if(!ta || ta>130) return;
				$('th, td', $th).each(function(e) {
					var $i = $(this);
					if($i.is('.title')) return;
					var j = $i.width() - tt;
					$i.css({'max-width': j>0?j:1});
				});
			}
		});
		$('#siFbk .scFbH + .scClst > .scFrm').each(function()
		{
			var $th =$(this), tw = $th.outerWidth();
			if(tw < 400) {
				$('.scFbt', $th).css('width','auto');
				$('.scCmtCon', $th).css('margin-left','5px');
			}
		});

		// 제목 자동조절
		$('.scElps[data-active=true]')
		.each(function(e)
		{
			var $i = $(this), $f = $('> :eq(0)', $i),$l = $('> :eq(1)', $i),fw = $i.width(),lw = 0;
			if($l.length)
			{
				if($('> img', $l).length || $l.text().trim())
					lw = $l.addClass('_last').outerWidth(true);
				else $l.remove();
			}
			if($.browser.msie===true) $f.height($i.css('line-height') || 15);
			$f.css('width',(fw - lw - 5) + 'px').addClass('_first');
		});
		// 핫트랙
		$('.scContent [data-hottrack]')
		.each(function(e)
		{
			var $i = $(this), ix = $i.attr('data-index'), tp = $i.attr('data-type'), ur = $i.attr('data-hottrack'),
				$a = $('<a class="scHotTrack" />').attr('data-index', ix).attr('href', ur),
				w = $i.outerWidth(tp != 'gall') - (tp == 'widg' ? 8 : 4),
				h = $i.outerHeight(tp == 'webz') + (tp == 'lstcont' ? $('#siHotCont_' + ix).height() : 0) - 4;

			$a.css({'width':w+'px','height':h+'px'});
			if($i.get(0).tagName=='TR') {
				var $td = $i.find('>td:eq(0)');
				if($.browser.mozilla===true) {
					var $cs = $('<span>').css({'position':'relative','display':'block'})
						.css({'padding-left':$td.css('padding-left'),'padding-top':$td.css('padding-top')}).html($td.html());
					$a.prependTo($cs.prependTo($td.html('').css({'padding-left':'0','padding-top':'0','vertical-align':'top'})));
				} else $a.prependTo($td.css({'position':'relative'}));
			} else $a.prependTo($i);

			if($.browser.msie===true) $('<span class="iefix" />').css({'width':w+'px','height':h+'px'}).appendTo($a);
		});

		if(getCookie('scCaLock')!='hide') $('#siCat.colm').trigger('fadeIn.fast');

		var tmp = $('#siFbk a[name^=comment][data-scroll=true]').last().parent()[0];
		if(tmp) tmp.scrollIntoView(true);

		// 글쓰기
		$('#siWrt').each(function(){
			$('.scWcateList', this).change(function(){
				var v = $(this).val(), k = $(this).data('key'),
					$d = $('.scWcateList[data-key='+k+']').hide('slow'),
					$s = $('.scWcateList[data-key='+v+']');
				$(this).data('key', v);
				$('input:hidden[name=category_srl]').val(v);
				$('.scWcateList[data-key='+$d.data('key')+']').hide('slow');
				if($s.find('>option').length) $s.change().show('slow');
			});
			$('input:hidden[name=category_srl]:eq(0)', this).each(function(){
				var v = $(this).val() || 0, j, i = 0, $s;
				if(v > 0){
					for(j=0;j<3;j++) {
						$s = $('.scWcateList option[value='+v+']').closest('select').val(v).data('key', v).change();
						if(!$s||!$s.attr('data-key')) break;
						v = $s.show('slow').attr('data-key');
					}
				}else{
					$('.scWcateList:eq(0)').change();
				}
			});
			$('.scWul.extraKeys li.scWli:hidden:eq(0)', this).each(function(){
				$('#siWrt .scExTog:hidden').show().click(function(){
					$('#siWrt .scWul.extraKeys li.scWli:hidden').show('slow');
					$(this).hide();
        			return false;
				});
			});
			$('a[href=#insert_filelink]', this).click(function(){
				var $p = $(this).closest('#insert_filelink').find('> input'),
					v = $p.val(), q = $(this).attr('data-seq'), r = $(this).attr('data-srl');
				if(v === undefined || !v){
					alert('Please enter the file url.\nvirtual type example: http://... #.mov');
					$p.focus();
					return false;
				}
				exec_json(
					'Beluxe.procBeluxeInsertFileLink',
					{ 'mid':current_mid,'sequence_srl':q,'document_srl':r,'filelink_url':v },
					function(ret){
						// ckeditor
						if($('[id^=ckeditor_instance_]').length) {
							var u = xe.getApp('xeuploader');
							if(u.length===1) u[0].loadFilelist();
							else u = $('#xefu-container-'+ret.sequence_srl).xeUploader();

						// xpresseditor
						}else if($('.xpress-editor').length){
							reloadFileList(uploaderSettings[ret.sequence_srl]);
						}
					}
				);
				return false;
			});
		});
	});
});
