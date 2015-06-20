/*
 * admin.js for BoardDX
 * @author phiDel (xe.phidel@gmail.com)
 */

jQuery(function($) {
	var dxvAdmTempKey = -1;

	$.fn.dxfStMapinit = function() {
		var $this = $(this);

		$('form.dxcStMap', $this).on({
			'mousedown.st': function(event) {
				if ($(event.target).is('.side .dxSmSelect,span.x_input-append *')) {
					event.which = 0;
				}
				return;
			}
		}, 'li:not(.placeholder)');

		$('form.dxcStMap', $this).on('dropped.st', 'li:not(.placeholder)', function() {
			var $th = $(this),
				$pkey, is_child;

			$pkey = $th.find('>input._parent_key');
			is_child = $th.parent('ul').parent('li').length;

			if (is_child) {
				$pkey.val($th.parent('ul').parent('li').find('>input._item_key').val());
			} else {
				$pkey.val('0');
			}
		});

		$('a[href=#addMenu]', $this).click(function() {
			var $ul = $('ul._template', $this);
			$ul.find('li').clone(true)
				.removeClass('_template')
				.find('input,select').removeAttr('disabled').end()
				.find('label').removeAttr('for', '').end()
				.find('label>input').removeAttr('id', '').end()
				.find('input._item_key').val(dxvAdmTempKey--).end()
				.show()
				.appendTo($('ul#nav_category', $this))
				.find('._lang_code').xeApplyMultilingualUI();
			return false;
		});

		$('a[href=#delete]', $this).click(function() {
			if (!confirm(xe.lang.confirm_delete)) return false;

			var module_srl = $(this).closest('form').find('>input[name=module_srl]').val(),
				category_srl = $(this).closest('li').find('>input._item_key').val(),
				params = {
					'module_srl': module_srl,
					'category_srl': category_srl
				},
				cateFuncDel = 'beluxe.procBeluxeAdminDeleteCategory';

			if (category_srl > 0) {
				exec_json(cateFuncDel, params, completeCallModuleAction);
			} else {
				$(this).closest('li').remove();
			}
			return false;
		});

		$('.dxSmSelect', $this).click(function() {
			var $th = $(this),
				$dl = $th.data('dl');

			if ($dl === undefined) {
				$dl = $('dl', $th);
				$dl.data('type', $th.attr('data-type'));
				$th.data('dl', $dl);
				oidx = $th.attr('data-index');

				$dl.on('close.dl', function() {
					if ($dl.data('type') != 'array' && $dl.data('type') != 'panel') return;

					var ins = [],
						idx = 0;

					if ($dl.data('type') == 'array') {
						$('input:checked', $dl).each(function() {
							ins[idx++] = $(this).val();
						});
					} else {
						$('input[type=checkbox]', $dl).each(function() {
							ins[idx++] = $(this).is(':checked') ? $(this).val() : '';
						});
						$('select', $dl).each(function() {
							ins[idx++] = $(this).val();
						});
						$('input[type=text][id!=item_color]', $dl).each(function() {
							ins[idx++] = $(this).val();
						});
						$('input[type=text][id=item_color]:eq(0)', $dl).each(function() {
							var col = $(this).val() || '',
								isc = col && col != 'transparent';
							$('._item_color', $th).val(col);
							$th.css('border', isc ? ('1px solid ' + col) : '');
						});
					}

					var val1 = $('._value', $th).val(),
						val2 = ins.join('|@|');

					$('._value', $th).val(val2);
					if (val1 != val2) $('._title', $th).css('color', ins.length ? 'red' : '');
				});

				// event 연결은 처음에 한번만 해야지 많이 하면 Overflow
				//$('._title', $th).click(function(){$th.click();});

				$(document).mousedown(function(event) {
					var target = event.target;
					if ($(target).parents().add(target).index($dl) > -1) return;
					$dl.trigger('close.dl').hide();
				});

				$dl.appendTo('body');

				if (oidx !== undefined) {
					$dl.html(dxvAddStmapMenuSample[oidx]);
					if ($dl.attr('default') == 'default') $('dt.defi,option.defi', $dl).remove();
					if (oidx == '1') {
						var col = $('._item_color', $th).val() || '',
							tval = $('._value', $th).val();
						$('input.color-indicator', $dl).val(col).xe_colorpicker();
						tval = tval.split('|@|');
						var objs = $('input[type=checkbox],select,input[type=text][id!=item_color]', $dl);
						for (var i = 0, c = objs.length; i < c; i++) {
							var type = $(objs[i]).attr('type');
							switch (type) {
								case ('checkbox'):
									if (tval[i] == 'Y') $(objs[i]).attr("checked", "checked");
									break;
								default:
									$(objs[i]).val(tval[i]);
							}

						}
					}
				}

				$('dt', $dl).click(function(event) {
					var target = event.target,
						$dt = $(this);
					if (target.tagName == 'INPUT' || target.tagName == 'SELECT') return;
					if ($dl.data('type') == 'array' || $dl.data('type') == 'panel') {
						$dl.trigger('close.dl');
					} else {
						$('._title', $th).text($dt.text());
						$('._value', $th).val($dt.attr('data-val'));
						$('._option', $th).val($dt.attr('data-opt'));
					}
					$dl.hide();
				});

				if ($dl.data('type') == 'array' || $dl.data('type') == 'panel') {
					$('input[type=checkbox]', $dl).click(function(event) {
						$dl.trigger('close.dl');
					});
				}
			}

			var t, l, h, wt, wl, wh;

			wt = $(window).scrollTop();
			wl = $(window).scrollLeft();
			wh = $(window).outerHeight();

			t = $th.offset().top + $th.outerHeight() - 7;
			l = $th.offset().left - $dl.outerWidth() + $th.outerWidth() - 8;
			h = $dl.outerHeight();

			if ((wt + wh - 10) < (t + h)) t = t - h - $th.outerHeight();

			$dl.css({
				'top': t,
				'left': l
			}).show();

			return false;
		});
	};

	$.fn.dxfExtraKeyinit = function() {
		var $this = $(this);

		$this.submit(function() {
			var $eids = $('input._extra_eid', $this);
			for (var i = 0, c = $eids.length; i < c; i++) {
				var sv = $($eids.get(i)).val().trim(),
					patt = /^[^a-z]|[^a-z0-9_]+$/g;
				if (sv === undefined || patt.test(sv)) {
					alert(xe.lang.msg_invalid_eid);
					$($eids.get(i)).focus();
					return false;
				}
			}
			return true;
		});

		$('a[href=#addMenu]', $this).click(function() {
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

		$('input:checkbox._extra_option', $this).click(function() {
			$('input:hidden#extra_option', $(this).closest('div.wrap')).val($(this).is(':checked') ? 'Y' : 'N');
		});

		$('a[href=#delete]', $this).click(function() {
			if (!confirm(xe.lang.confirm_delete)) return false;
			var module_srl = $(this).closest('form').find('>input[name=module_srl]').val(),
				extra_idx = $(this).closest('.wrap').find('>input._extra_idx').val(),
				params = {
					'module_srl': module_srl,
					'extra_idx': extra_idx
				},
				cateFuncDel = 'beluxe.procBeluxeAdminDeleteExtraKey';
			if (extra_idx !== undefined && extra_idx !== 0)
				exec_json(cateFuncDel, params, completeCallModuleAction);
			else $(this).closest('tr').remove();
			return false;
		});
	};

	$.fn.dxfColumninit = function() {
		$('input:checkbox.column_option', this).click(function() {
			var $par = $(this).closest('div.wrap'),
				option = [];
			option[0] = $('input:checkbox.column_display', $par).is(':checked') ? 'Y' : 'N';
			option[1] = $('input:checkbox.column_sort', $par).is(':checked') ? 'Y' : 'N';
			option[2] = $('input:checkbox.column_search', $par).is(':checked') ? 'Y' : 'N';
			$('input:hidden#column_option', $par).val(option.join('|@|'));
		});
	};

	$.fn.dxfInsertinit = function() {
		var f = this;

		$.fn.bdxDfTypeSelect = function() {
			$(this).change(function() {
				var def = $(this).attr('data-default') || '',
					ots = ($("option:selected", this).attr('data-option') || '').split('|@|');
				if (ots.length) {
					$('select[name=default_sort_index]', f).val(ots[0]);
					$('select[name=default_order_type]', f).val(ots[1]);
					$('input[name=default_list_count]', f).val(ots[2]);
					$('input[name=default_page_count]', f).val(ots[3]);
					$('input[name=default_clist_count]', f).val(ots[4]);
					$('input[name=default_dlist_count]', f).val(ots[5]);
				}
				if (def != $(this).val()) $(this).closest('div.x_control-group').addClass('opt_bks');
				else $(this).closest('div.x_control-group').removeClass('opt_bks');
			});
		};

		$('select[name=default_type]', f).bdxDfTypeSelect();

		$('select[name=skin]', f).change(function() {
			var $i = $(this),
				$li = $i.closest('div.x_control-group'),
				def = $i.attr('data-default') || '',
				skin = $i.val() || '';
			$li.next().hide();
			$li.next().find('> div.x_controls:eq(0) > select').hide().attr('name', '');
			$li.find('> p.msg_call_server').show();

			if (!skin || skin == '/USE_DEFAULT/') skin = 'default';

			var $s = $li.next().find('> div.x_controls:eq(0) > select[data-skin=' + skin + ']');

			if ($s.length) {
				$li.find('> p.msg_call_server').hide();
				$s.attr('name', 'default_type').show().change();
				$li.next().show();
			} else {
				exec_json('beluxe.getBeluxeAdminSkinTypes', {
						skin: skin
					},
					function(ret) {
						if (ret.error == '0') {
							var $o = $(ret.html);
							$o.bdxDfTypeSelect();
							$o.prependTo($li.next().find('> div.x_controls:eq(0)'));
							$o.attr('name', 'default_type').change();
						} else alert(ret.message);

						$li.find('> p.msg_call_server').hide();
						$li.next().show();
					}
				);
			}
		});
	};

	$('a[href=#remakeCache]', this).click(function() {
		var mode = $(this).attr('data-type'),
			opt = $(this).attr('data-option') || '',
			module_srl = $(this).closest('form').find('>input[name=module_srl]').val(),
			params = {
				'module_srl': module_srl,
				'option': opt
			};
		mode = mode.charAt(0).toUpperCase() + mode.slice(1);
		cateFuncMake = 'beluxe.procBeluxeAdminMake' + mode + 'Cache';
		exec_json(cateFuncMake, params, completeCallModuleAction);
		return false;
	});

	$('a.modalAnchor[href=#manageDeleteModule]').on('before-open.mw', function(e) {
		var $frm = $('#manageDeleteModule'),
			$tr = $(this).closest('tr'),
			aVal = $(this).attr('data-val').split('|@|');

		$('.module_category', $frm).text($('.module_category', $tr).text());
		$('.module_title', $frm).text($('.module_title', $tr).text());
		$('.module_regdate', $frm).text($('.module_regdate', $tr).text());
		$('.module_mid', $frm).text(aVal[0]);
		$('input:hidden[name=module_srl]', $frm).val(aVal[1]);
	});

	$('a[href=#doBeluxeSettingCopy]').click(function() {
		var aVal = $(this).attr('data-val').split('|@|');
		popopen(decodeURI(current_url).setQuery('act', 'dispBeluxeAdminInsert').setQuery('is_poped', '1').setQuery('m_target', aVal[0]));
		return false;
	});

	$('a[href=#dispBeluxeAdminInsert],a[href=#dispModuleAdminModuleGrantSetup]').click(function() {
		var aVal, isrls = [],
			href = $(this).attr('href').substring(1),
			opt = href == 'dispBeluxeAdminInsert' ? 'm_targets' : 'module_srls';

		$('input[name=cart]:checked').each(function(i) {
			aVal = $(this).val().split('|@|');
			isrls[i] = opt == 'module_srls' ? aVal[1] : aVal[0];
		});

		if (isrls.length < 1) return alert('Please select the items.') || false;

		popopen(decodeURI(current_url).setQuery('act', href).setQuery('is_poped', '1').setQuery(opt, isrls.join(',')));
		return false;
	});

	$('select.changeLocation').change(function() {
		location.href = decodeURI(current_url).setQuery($(this).attr('name'), $(this).val());
	});

	$('a[href=#popup_help][data-text]').click(function() {
		return alert($(this).attr('data-text').replace(/\\n/gi, "<br />")) || false;
	});

	$('#addition').each(function() {
		$('select[name=use_history],input[name=comment_count],input[name=enable_trackback]', this).closest('.x_control-group').hide();
	});

	$('[name=use_point_type]').click(function() {
		$('[data-control-type=restrict]').prop('disabled', $(this).val() == 'A');
	});

	$('#dxiStMapFrm').dxfStMapinit();
	$('#dxiColumnFrm').dxfColumninit();
	$('#dxiExtraKeyFrm').dxfExtraKeyinit();
	$('#dxiInsertFrm').dxfInsertinit();

	$('[data-info-target]', '.dx_skininfo').each(function() {
		var $i = $(this),
			t = ($i.attr('data-info-target') || '').split('/_VALUE_/'),
			v = t[1].split(',');
		$('[name=' + t[0] + ']', '.dx_skininfo').click(function() {
			if ($.inArray($(this).val() + '', v) > -1) $i.show();
			else $i.hide();
		});
		if ($.inArray($('[name=' + t[0] + ']:checked', '.dx_skininfo').val() + '', v) > -1) $i.show();
	});
});
