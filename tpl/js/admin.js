/**
 * @author phiDel (xe.phidel@gmail.com)
 * @update 2011/08/08
 **/

var __XEFM_NAME__ = 'beluxe';

String.prototype.ucfirst = function()
{
	var s=this;return s.charAt(0).toUpperCase() + s.slice(1);
};

jQuery(function($)
{
	var sjAdmTempKey = -1;

/*
 * 	구버전용 언어 코드 찾기 (xe.1.5.x)
	function open_find_langcode(tar)
	{
		if(tar == undefined || !tar) return false;
		var url = request_uri.setQuery('module','module').setQuery('act','dispModuleAdminLangcode').setQuery('target',tar);
		// ie error prevention
		var iefix = tar.replace(/-/gi,'_');
		try {
			if(typeof(winopen_list[iefix]) != 'undefined' && winopen_list[iefix]) {
				winopen_list[iefix].close();
				winopen_list[iefix] = null;
			}
		} catch(e) {
		}
		var win = window.open(url, iefix, "width=650,height=500,scrollbars=yes,resizable=yes,toolbars=no");
		winopen_list[iefix] = win;
		// ie not working
		//$(win).unload(function() {$('#' + tar, parent.document).focus();});
		var timer = setInterval(function() {
			if(win != undefined && win.closed) {
				clearInterval(timer);
				$('#' + tar).focus();
			}
		}, 500);
		return false;
	}
*/

	$.fn.bdxSiteMapinit = function()
	{
		var $this = $(this);

		$('form.siteMap', $this).delegate('li:not(.placeholder)', {'mousedown.st' : function(event) {
			if($(event.target).is('.side .cbSelect,span.x_input-append *')) {
				event['which'] = 0;
			}
			return;
		}});

		$('form.siteMap', $this).delegate('li:not(.placeholder)', 'dropped.st', function() {
			var $th = $(this), $pkey, is_child;

			$pkey = $th.find('>input._parent_key');
			is_child = !!$th.parent('ul').parent('li').length;

			if(is_child) {
				$pkey.val($th.parent('ul').parent('li').find('>input._item_key').val());
			} else {
				$pkey.val('0');
			}
		});

		$('a[href=#addMenu]', $this).click(function()
		{
			var $ul = $('ul._template', $this);
			$ul.find('li').clone(true)
				.removeClass('_template')
				.find('input,select').removeAttr('disabled').end()
				.find('label').removeAttr('for','').end()
				.find('label>input').removeAttr('id','').end()
				.find('input._item_key').val(sjAdmTempKey--).end()
				.show()
				.appendTo($('ul#nav_category', $this))
				.find('._lang_code').xeApplyMultilingualUI();
			return false;
		});

		$('a[href=#delete]', $this).click(function() {
			if(!confirm(xe.lang.confirm_delete)) return false;

			var module_srl = $(this).closest('form').find('>input[name=module_srl]').val(),
				category_srl = $(this).closest('li').find('>input._item_key').val(),
				params = {'module_srl':module_srl,'category_srl':category_srl},
				cateFuncDel = 'proc' + (__XEFM_NAME__).ucfirst() + 'AdminDeleteCategory';

			if(category_srl > 0){
				exec_xml(__XEFM_NAME__, cateFuncDel, params, completeCallModuleAction);
			}else{
				$(this).closest('li').remove();
			}
			return false;
		});

		$('.cbSelect', $this).click(function() {
			var $th = $(this),
				$dl = $th.data('dl');

				if($dl == undefined)
				{
					$dl = $('dl', $th);
					$dl.data('type', $th.attr('data-type'));
					$th.data('dl', $dl);
					oidx = $th.attr('data-index');

					$dl.bind('close.dl', function(){
						if($dl.data('type')!='array'&&$dl.data('type')!='panel') return;

						var ins = new Array(),
							idx = 0;
						if($dl.data('type')=='array') {
							$('input:checked', $dl).each(function(){ins[idx++] = $(this).val();});
						} else {
							$('input[type=checkbox]', $dl).each(function(){ins[idx++] = $(this).is(':checked')?$(this).val():'';});
							$('select', $dl).each(function(){ins[idx++] = $(this).val();});
							$('input[type=text][id!=item_color]', $dl).each(function(){ins[idx++] = $(this).val();});
							$('input[type=text][id=item_color]:eq(0)', $dl).each(function(){
								var col = $(this).val() || '', isc = col && col !='transparent';
								$('._item_color', $th).val(col);
								$th.css('border', isc ? ('1px solid ' + col) : '');
							});
						}
						var val1 = $('._value', $th).val(), val2 = ins.join('|@|');
						$('._value', $th).val(val2);
						if(val1 != val2) $('._title', $th).css('color', ins.length?'red':'');
					});

					// event 연결은 처음에 한번만 해야지 많이 하면 Overflow
					//$('._title', $th).click(function(){$th.click();});

					$(document).mousedown(function(event){
						var target = event.target;
						if ($(target).parents().add(target).index($dl) > -1) return;
						$dl.trigger('close.dl');
						$dl.hide();
					});

					$dl.appendTo('body');

					if(oidx != undefined) {
						$dl.html(addSitemapMenuSampleOption[oidx]);
						if($dl.attr('default')=='default') $('dt.defi,option.defi',$dl).remove();
						if(oidx=='1') {
							var col = $('._item_color', $th).val() || '', tval = $('._value', $th).val();
							$('input.color-indicator', $dl).val(col).xe_colorpicker();
							tval = tval.split('|@|');
							var objs = $('input[type=checkbox],select,input[type=text][id!=item_color]', $dl);
							for(var i=0, c=objs.length;i<c;i++) {
								var type = $(objs[i]).attr('type');
								switch(type) {
									case('checkbox'):
										if(tval[i]=='Y') $(objs[i]).attr("checked", "checked");
									break;
									default: $(objs[i]).val(tval[i]);
								}

							}
						}
					}

					$('dt', $dl).click(function(event) {
						var target = event.target,
							$dt = $(this);
						if(target.tagName == 'INPUT'||target.tagName == 'SELECT') return;
						if($dl.data('type')=='array'||$dl.data('type')=='panel') {
							$dl.trigger('close.dl');
							$dl.hide();
						} else {
							$('._title', $th).text($dt.text());
							$('._value', $th).val($dt.attr('data-val'));
							$('._option', $th).val($dt.attr('data-opt'));
							$dl.hide();
						}
					});

					if($dl.data('type')=='array'||$dl.data('type')=='panel'){
						$('input[type=checkbox]', $dl).click(function(event) {
							$dl.trigger('close.dl');
						});
					}
				}

				var $ch = $('#siteMapFrame');
				$dl.css('left', ($th.offset().left - 8) + 'px');

				if(
					(($ch.offset().top + $ch.height()) < ($th.offset().top + $dl.height() + 16))
					&& ($dl.height() < ($th.offset().top - $ch.offset().top))
				)
					$dl.css('top', ($th.offset().top - $dl.height() - 16) + 'px');
				else
					$dl.css('top', ($th.offset().top + 9) + 'px');

				$dl.show();

			return false;
		});
	};

	$.fn.bdxExtraKeyinit = function()
	{
		var $this = $(this);

		$this.submit(function()
		{
			var $eids = $('input._extra_eid', $this);
			for(var i=0, c=$eids.length; i<c; i++)
			{
				var sv = $($eids.get(i)).val().trim(),
					patt = /^[^a-z]|[^a-z0-9_]+$/g;
				if(sv == undefined || patt.test(sv))
				{
					alert(xe.lang.msg_invalid_eid);
					$($eids.get(i)).focus();
					return false;
				}
			}
			return true;
		});

		$('a[href=#addMenu]', $this).click(function()
		{
			var $tbody = $('._extraList tbody', $this);
			$tbody.find('._template').clone(true)
				.removeClass('_template')
				.find('input,select').removeAttr('disabled').end()
				.find('label').removeAttr('for', '').end()
				.find('input._extra_option').removeAttr('id', '').end()
				.show()
				.appendTo($tbody)
				.find('._lang_code').xeApplyMultilingualUI();
			return false;
		});

		$('input:checkbox._extra_option', $this).click(function()
		{
			$('input:hidden#extra_option', $(this).closest('div.wrap')).val($(this).is(':checked')?'Y':'N');
		});

		$('a[href=#delete]', $this).click(function()
		{
			if(!confirm(xe.lang.confirm_delete)) return false;
			var module_srl = $(this).closest('form').find('>input[name=module_srl]').val(),
				extra_idx = $(this).closest('.wrap').find('>input._extra_idx').val(),
				params = {'module_srl':module_srl,'extra_idx':extra_idx},
				cateFuncDel = 'proc' + (__XEFM_NAME__).ucfirst() + 'AdminDeleteExtraKey';
			if(extra_idx != undefined && extra_idx !== 0)
				exec_xml(__XEFM_NAME__, cateFuncDel, params, completeCallModuleAction);
			else $(this).closest('tr').remove();
			return false;
		});
	};

	$.fn.bdxColumninit = function()
	{
		$('input:checkbox.column_option', this).click(function()
		{
			var $par = $(this).closest('div.wrap'),
				option = new Array();
			option[0] = $('input:checkbox#column_display', $par).is(':checked')?'Y':'N';
			option[1] = $('input:checkbox#column_sort', $par).is(':checked')?'Y':'N';
			option[2] = $('input:checkbox#column_search', $par).is(':checked')?'Y':'N';
			$('input:hidden#column_option', $par).val(option.join('|@|'));
		});
	};

	$.fn.bdxInsertinit = function()
	{
		var f = this;

		$.fn.bdxDfTypeSelect = function()
		{
			$(this).change(function()
			{
				var def = $(this).attr('data-default') || '', ots= ($("option:selected", this).attr('data-option') || '').split('|@|');
				if(ots.length==5)
				{
					$('select[name=default_sort_index]', f).val(ots[0]);
					$('select[name=default_order_type]', f).val(ots[1]);
					$('input[name=default_list_count]', f).val(ots[2]);
					$('input[name=default_page_count]', f).val(ots[3]);
					$('input[name=default_clist_count]', f).val(ots[4]);
				}
				if(def != $(this).val()) $(this).closest('div.x_control-group').addClass('opt_bks');
				else $(this).closest('div.x_control-group').removeClass('opt_bks');
			});
		}

		$('select[name=default_type]', f).bdxDfTypeSelect();

		$('select[name=skin]', f).change(function() {
			var $i = $(this), $li = $i.closest('div.x_control-group'), def = $i.attr('data-default') || '', skin = $i.val() || '';
			$li.next().hide();
			$li.next().find('> div.x_controls:eq(0) > select').hide().attr('name','');
			$li.find('> p.msg_call_server').show();
			var $s = $li.next().find('> div.x_controls:eq(0) > select[data-skin='+skin+']');

			if($s .length)
			{
				$li.find('> p.msg_call_server').hide();
				$s.attr('name','default_type').show().change();
				$li.next().show();
			}
			else
			{
				exec_xml('beluxe', 'getBeluxeAdminSkinTypes',  {skin : skin},
					function(ret) {
						if(ret['error']=='0')
						{
							var $o = $(ret['html']);
							$o.bdxDfTypeSelect();
							$o.prependTo($li.next().find('> div.x_controls:eq(0)'));
							$o.attr('name','default_type').change();
						}
						else alert(ret['message']);

						$li.find('> p.msg_call_server').hide();
						$li.next().show();
					},
					['html','error','message']
				);
			}
		});
	};

	$('a[href=#remakeCache]', this).click(function() {
		var mode = $(this).attr('data-type'),
			opt = $(this).attr('data-option') || '',
			module_srl = $(this).closest('form').find('>input[name=module_srl]').val(),
			params = {'module_srl':module_srl,'option':opt},
			cateFuncMake = 'proc' + (__XEFM_NAME__).ucfirst() + 'AdminMake' + mode.ucfirst() + 'Cache';
		exec_xml(__XEFM_NAME__, cateFuncMake, params, completeCallModuleAction);
		return false;
	});

	$('a.modalAnchor[href=#manageDeleteModule]').bind('before-open.mw',function(e)
	{
		var $frm = $('#manageDeleteModule'),
			$tr = $(this).closest('tr'),
			aVal = $(this).attr('data-val').split('|@|');

			$('.module_category', $frm).text($('.module_category', $tr).text());
			$('.module_title', $frm).text($('.module_title', $tr).text());
			$('.module_regdate', $frm).text($('.module_regdate', $tr).text());
			$('.module_mid', $frm).text(aVal[0]);
			$('input:hidden[name=module_srl]', $frm).val(aVal[1]);
	});

	$('a[href=#doBeluxeSettingCopy]').click(function()
	{
		var aVal = $(this).attr('data-val').split('|@|');
		popopen(decodeURI(current_url).setQuery('act', 'dispBeluxeAdminInsert').setQuery('is_poped','1').setQuery('m_target', aVal[0]));
		return false;
	});

	$('a[href=#dispBeluxeAdminInsert],a[href=#dispModuleAdminModuleGrantSetup]').click(function()
	{
		var aVal, isrls = new Array(),
			href = $(this).attr('href').substring(1),
			opt = href == 'dispBeluxeAdminInsert' ? 'm_targets' : 'module_srls';

		$('input[name=cart]:checked').each(function(i) {
			aVal = $(this).val().split('|@|');
			isrls[i] = opt == 'module_srls' ? aVal[1] : aVal[0];
		});

		if(isrls.length<1) return alert('Please select the items.') || false;

		popopen(decodeURI(current_url).setQuery('act', href).setQuery('is_poped','1').setQuery(opt, isrls.join(',')));
		return false;
	});

	$('select.changeLocation,select.changeColorsets').change(function() {
		location.href = decodeURI(current_url).setQuery($(this).attr('name'), $(this).val());
	});

	$('button[id=colorCodeView]').click(function() {
		var $o = $('#colorFrame input[name=color_code]').parent().hide().end().parent().show('slow').end(),
				cols = new Array();
		$('#colorFrame #color_list input[name^=color_value_]').each(function(i) { cols[i] = ($(this).val() || 'NONE').toUpperCase();});
		$o.val(cols.join(';')).focus().select();
		return false;
	});

	$('a[href=#popup_help][data-text]').click(function() {
		return alert($(this).attr('data-text').replace(/\\n/gi,"\n")) || false;
	});

	$('#addition').each(function() {
		$('select[name^=use_vote_],select[name=use_history],input[name=comment_count],input[name=enable_trackback]', this).closest('tr').css({position:'absolute',overflow:'hidden',width:'1px'});
		$('form table', this).each(function() {
			if(($(this).height() || 0) < 5) $(this).closest('form').css({position:'absolute',overflow:'hidden',width:'1px'});
		});
	});

	$('[name=use_point_type]').click(function() {
		if($(this).val()=='A'){
			$('[data-control-type=restrict]').prop( 'disabled', true );
		}else{
			$('[data-control-type=restrict]').prop( 'disabled', false );
		}
	});

	$('#siteMapFrame').bdxSiteMapinit();
	$('#columnFrame').bdxColumninit();
	$('#extraKeyFrame').bdxExtraKeyinit();
	$('form#insertFrame').bdxInsertinit();
});
