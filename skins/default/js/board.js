 /*
  * board.js for BoardDX
  * @author phiDel (xe.phidel@gmail.com, https://github.com/phiDelbak/Board-DX-for-XE)
  */

 (function($) {
 	var sjDxFuncs = $.extend({
 		isMobj: function(m) {
 			var r, f = this.pidFrm;
 			if (!f) return r;
 			if (m === 'b') r = f.closest('body');
 			else if (m === 'm') r = f.parent().parent();
 			else if (m === 's') r = $('body', f[0].contentDocument || f[0].contentWindow.document || '');
 			return r;
 		},
 		ucfirst: function(s) {
 			return s.charAt(0).toUpperCase() + s.slice(1);
 		},
 		miniMsg: function(e, s) {
 			var tmp = $('<div class="dxc_minimsg">').html(s);
 			$(e).parent().after(tmp);
 			tmp.delay(3000).fadeOut(2500, function() {
 				$(this).remove();
 			});
 		},
 		reSized: function(skip) {
 			// 제목 자동조절
 			$('.scElps[data-active=true]')
 				.find('> :eq(0)').width('auto').removeClass('_first')
 				.end().each(function() {
 					var $i = $(this),
 						$l = $i.find('> :eq(1)'),
 						fw = $i.width(),
 						lw = 0;
 					if ($l.length) {
 						if ($l.find('> img').length || $l.text().trim())
 							lw = $l.addClass('_last').outerWidth(true);
 						else $l.remove();
 					}
 					$i.find('> :eq(0)').width(fw - lw - 5).addClass('_first');
 				});

 			// 핫트랙
 			if (!skip) {
 				$('[data-hottrack]', '.scContent')
 					.each(function() {
 						var $e = $(this),
 							$i = $e,
 							$a = $i.find('.scHotTrack'),
 							tp = $i.attr('data-hottrack'),
 							w = $i.outerWidth(tp !== 'gall') - (tp === 'widg' ? 7 : 4);

 						if (!$a.length) {
 							$a = $('<a class="scHotTrack" href="#">')
 								.click(function() {
 									// 갤러리형 슬라이드 사용시
 									if (tp === 'gall' && $i.find('a[data-slide]').length) {
 										$i.find('a[data-slide]:eq(0)').click();
 									} else {
 										$i.find('a[type=example\\/modal], a[type=text\\/html]')[0].click();
 									}
 									return false;
 								});
 						}

 						if ($i[0].tagName === 'TR') {
 							$i.find('>td:eq(0)').is(function() {
 								$e = $(this).css('position', 'relative');
 								$a.width(w).height($i.outerHeight() + (tp === 'lstc' ? $i.next().outerHeight() : 0));
 							});
 						} else $a.width(w);

 						if (!$e.find('.scHotTrack').length) $a.prependTo($e);
 					});
 			}
 		}
 	});

 	$('#siCat.tabn,#siCat.colm')
 		.each(function() {
 			var $i = $(this),
 				iscolm = $i.hasClass('colm'),
 				$u = $('.cArea ul', this),
 				$a = $('.cArea li.active:last', this),
 				$f = $('.cArea li:first', this),
 				uh, lh, nn, dt;

 			if (iscolm) {
 				var $p = $('#siLst:not(.noheader), #siHrm:not(.noheader)'),
 					$k = $('.scCaLock', this),
 					cok = getCookie('scCaLock');

 				if (!$p.length) return;

 				if ($p[0].tagName === 'TABLE') {
 					$($p[0]).find('th:eq(0)').is(function() {
 						$i.appendTo($(this).css('position', 'relative'));
 					});
 				} else $i.appendTo($p[0]).show();

 				$i.width($p.width() - 2);

 				$k.click(function() {
 					$(this).toggleClass('active');
 					setCookie('scCaLock', $(this).hasClass('active') ? '' : 'hide', null, '/');
 					return false;
 				});

 				if ($i.attr('data-autohide') == 'true') {
 					$('thead tr:first th:not(.sort), ul.scFrm:first', $p).mouseenter(function(e) {
 						var te = e.target,
 							$isSp = $('> span.sort:eq(0)', te);
 						if ($isSp.length && (e.offsetX > $isSp.position().left)) return;
 						$i.width($p.width() - 2).fadeIn();
 					});
 					$(document).mousemove(function(e) {
 						var te = e.target;
 						if ($(te).parents().add(te).index($i) > -1) return;
 						if (!$i.is(':hidden') && $i.css('opacity') == '1' && !$k.hasClass('active')) $i.fadeOut();
 					});
 				}

 				if (cok === 'hide') $k.removeClass('active');
 				if (cok !== 'hide') $i.fadeIn();
 			}

 			// 선택된 분류가 있으면 위치 구하고 이동
 			dt = parseInt($u.css("margin-top")) || 0;
 			uh = parseInt($u.height()) - dt;
 			lh = parseInt($f.outerHeight(true));

 			if (lh >= uh) {
 				$u.css('margin-right', '20px');
 			} else {
 				nn = parseInt(($a.position($i) ? $a.position($i).top : 0) / lh);
 				if (nn > 0) $u.css('margin-top', ((nn * lh) * -1) + 'px');

 				$('.scCaNavi a:eq(0)', this).click(function() {
 					var t = parseInt($u.css("margin-top"));
 					if (t < dt) $u.css('margin-top', (t + lh + dt) + 'px');
 					return false;
 				});

 				$('.scCaNavi a:eq(1)', this).click(function() {
 					var t = parseInt($u.css("margin-top"));
 					if ((t - lh - dt) > -uh) $u.css('margin-top', (t - lh - dt) + 'px');
 					return false;
 				});

 				$('.scCaNavi', this).show();
 			}
 		});

 	$('.scInfo[data-autohide=true]', '#siLst.gall')
 		.each(function() {
 			var $i = $(this),
 				$m = $i.next(),
 				t;
 			$i.css('cursor', 'pointer')
 				.click(function() {
 					$i.prev()[0].click();
 				})
 				.closest('.scItem')
 				.mouseenter(function() {
 					if (t) return true;
 					t = true;
 					$m.hide('slow');
 					$i.fadeIn('slow', function() {
 						t = false;
 					}); //if else $i.slideDown();
 				}).mouseleave(function() {
 					$i.fadeOut(); //if else $i.slideUp();
 					$m.show('slow');
 				});
 		});

 	$('.scFiles[data-autohide=true]', '#siDoc')
 		.each(function() {
 			var $i = $(this),
 				oh = $(this).outerHeight(),
 				ishide, w;

 			$i.find('>li').each(function() {
 				var t = $(this).position().top;
 				if (t > oh - 8) ishide = true;
 			});

 			if (ishide) {
 				w = $('<li><a href="#">more...</a></li>')
 					.css({
 						position: "absolute",
 						top: 5,
 						right: 5,
 						padding: 0,
 						'background-position': '-150px 0'
 					})
 					.find('>a')
 					.click(function() {
 						$(this).parent().parent().css({
 							'height': 'auto',
 							'padding-right': ''
 						}).end().remove();
 						return false;
 					})
 					.end().appendTo($i).width();
 				$i.css('padding-right', (w + 10) + 'px');
 			} else {
 				$i.removeAttr('data-autohide');
 			}

 			ishide = false;
 		});

 	$('a[href=#siManageBtn]')
 		.click(function() {
 			var $sa = $(this).closest('.scAdmActs');

 			$('.scElps ._first').each(function() {
 				$(this).css('width', ($(this).width() - 30) + 'px');
 			});
 			$('.scCheck').show('slow');
 			$sa.html($(this).next().html());

 			$('select', $sa).change(function() {
 				var v = $(this).val() || '',
 					s = [];
 				$('input[name=cart]:checked').each(function(i) {
 					s[i] = $(this).val();
 				});
 				if (s.length < 1) return alert('Please select the items.', this) || false;
 				exec_json('beluxe.procBeluxeChangeCustomStatus', {
 					mid: current_mid,
 					new_value: v,
 					target_srls: s.join(',')
 				}, completeCallModuleAction);
 				return;
 			});

 			$('a[data-type]', $sa).click(function() {
 				switch ($(this).attr('data-type')) {
 					case 'manage':
 						popopen(request_uri + '?module=document&act=dispDocumentManageDocument', 'manageDocument');
 						break;
 					case 'admin':
 						location.href = current_url.setQuery('act', 'dispBeluxeAdminModuleInfo');
 				}
 				return false;
 			});
 			return false;
 		});

 	$('a[href=#popopen][data-srl]')
 		.click(function() {
 			var srl = $(this).attr('data-srl') || '';
 			if (srl) popopen(srl.setQuery('is_poped', '1'), 'beluxePopup');
 			return false;
 		});

 	$('a[href=#tbk_action][class=delete]')
 		.click(function() {
 			if (!confirm('Do you want to delete the selected trackback?')) return false;
 			var srl = $(this).closest('li').find('a[name^=trackback_]').attr('name').replace(/.*_/g, '');
 			exec_json('beluxe.procBeluxeDeleteTrackback', {
 				mid: current_mid,
 				trackback_srl: srl
 			}, completeCallModuleAction);
 			return false;
 		});

 	$('a[href=#his_action][data-mode=delete][data-srl]')
 		.click(function() {
 			if (!confirm('Do you want to delete the selected history?')) return false;
 			var srl = $(this).attr('data-srl') || 0,
 				doc = $(this).attr('data-doc') || 0;
 			exec_json('beluxe.procBeluxeDeleteHistory', {
 				mid: current_mid,
 				history_srl: srl,
 				document_srl: doc
 			}, completeCallModuleAction);
 			return false;
 		});

 	$('a[href^=#][href$=recommend][data-type]')
 		.click(function() {
 			var $i = $(this),
 				hr = $i.attr('href'),
 				ty = $i.attr('data-type'),
 				srl = $i.attr('data-srl');
 			var params = {
 				target_srl: srl,
 				cur_mid: current_mid,
 				mid: current_mid
 			};

 			exec_json(
 				ty + '.proc' + sjDxFuncs.ucfirst(ty) + (hr == '#recommend' ? 'VoteUp' : 'VoteDown'),
 				params,
 				function(ret_obj) {
 					alert(ret_obj.message);
 					if (ret_obj.error === 0) {
 						var $e = $i.find('em.cnt');
 						$e.text((parseInt($e.text()) || 0) + (hr == '#recommend' ? 1 : -1));
 					}
 				}
 			);
 			return false;
 		});

 	$('a[href=#declare][data-type]')
 		.click(function() {
 			var $i = $(this),
 				ty = $i.attr('data-type'),
 				srl = $i.attr('data-srl'),
 				c, tmp, rec = $i.attr('data-rec') || '0';

 			c = prompt(_DXS_MSGS_.declare, '');
 			// 브라우저에서 블럭 옵션이 떠서 다른 방법 씀
 			//if(typeof c != 'string' || !c.trim()) return alert(_DXS_MSGS_.canceled) || false;
 			if (typeof c != 'string') return false;
 			if (!c.trim()) {
 				sjDxFuncs.miniMsg($i, 'Please enter the message.');
 				return false;
 			}

 			exec_json(
 				ty + '.proc' + sjDxFuncs.ucfirst(ty) + 'Declare', {
 					target_srl: srl,
 					cur_mid: current_mid,
 					mid: current_mid
 				},
 				function(ret_obj) {
 					alert(ret_obj.message);
 					if (ret_obj.error === 0 && rec !== '0') {
 						var t = (_DXS_MSGS_.declare_msg || 'declare').replace(/%s/g, srl),
 							u = current_url.setQuery('comment_srl', (ty == 'comment' ? srl : ''));
 						c = c + '<br /><br /><a href="' + u + '">' + u + '</a>';
 						exec_json('communication.procCommunicationSendMessage', {
 								receiver_srl: rec,
 								title: t,
 								content: c
 							},
 							function(ret_obj2) {
 								if (ret_obj2.error !== 0) alert(ret_obj2.message);
 							}
 						);
 					}
 				}
 			);

 			return false;
 		});

 	$('.btnAdopt [data-adopt-srl]')
 		.click(function() {
 			var $i = $(this),
 				c, srl = $i.attr('data-adopt-srl') || '',
 				name = $i.attr('data-adopt-name') || '';

 			c = prompt('Send thanks message to ' + name, '');
 			if (typeof c != 'string') return false;
 			if (!c.trim()) {
 				sjDxFuncs.miniMsg($i, 'Please enter the message.');
 				return false;
 			}

 			exec_json(
 				'beluxe.procBeluxeAdoptComment', {
 					comment_srl: srl,
 					send_message: c
 				},
 				function(ret_obj) {
 					alert(ret_obj.message);
 					if (ret_obj.error === 0) {
 						location.reload();
 					}
 				}
 			);

 			return false;
 		});

 	$('textarea[name=content]', '.scFbWt')
 		.focus(function() {
 			$('.scWusr', $(this).closest('form')).show('slow');
 		});

 	$('.scHLink[data-href]')
 		.click(function() {
 			window.open($(this).attr('data-href'), $(this).attr('data-target') || '');
 			return false;
 		});

 	$('.scToggle[data-target]')
 		.click(function() {
 			$($(this).attr('data-target')).slideToggle();
 			return false;
 		});

 	$('.scClipboard')
 		.click(function() {
 			var $i = $(this),
 				tg = $i.attr('data-attr') || false;
 			prompt('press CTRL+C copy it to clipboard...', (tg ? $i.attr(tg) : $i.text()));
 			return false;
 		});

 	// 글쓰기
 	$.fn.pidSettingWrite = function() {
 		var $exli = $('.scWul.extraKeys >li:hidden', this);
 		if ($exli.length) {
 			$('.scExTog:hidden', this).show().click(function() {
 				$exli.show('slow');
 				$(this).hide();
 				return false;
 			});
 		}

 		$('.scWcateList', this).change(function() {
 			var v = $(this).val(),
 				k = $(this).data('key'),
 				$d = $('.scWcateList[data-key=' + k + ']').hide('slow'),
 				$s = $('.scWcateList[data-key=' + v + ']');
 			$(this).data('key', v);
 			$('input:hidden[name=category_srl]').val(v);
 			$('.scWcateList[data-key=' + $d.data('key') + ']').hide('slow');
 			if ($s.find('>option').length) $s.change().show('slow');
 		});
 		$('input:hidden[name=category_srl]:eq(0)', this).each(function() {
 			var v = $(this).val() || 0,
 				j, i = 0,
 				$s;
 			if (v > 0) {
 				for (j = 0; j < 3; j++) {
 					$s = $('.scWcateList option[value=' + v + ']').closest('select').val(v).data('key', v).change();
 					if (!$s || !$s.attr('data-key')) break;
 					v = $s.show('slow').attr('data-key');
 				}
 			} else {
 				$('.scWcateList:eq(0)').change();
 			}
 		});

 		$('a[href=#insert_filelink]', this).click(function() {
 			var $p = $(this).closest('#insert_filelink').find('> input'),
 				v = $p.val(),
 				q = $(this).attr('data-seq'),
 				r = $(this).attr('data-srl');
 			if (v === undefined || !v) {
 				alert('Please enter the file url.\nvirtual type example: http://... #.mov');
 				$p.focus();
 				return false;
 			}
 			exec_json(
 				'beluxe.procBeluxeInsertFileLink', {
 					'mid': current_mid,
 					'sequence_srl': q,
 					'document_srl': r,
 					'filelink_url': v
 				},
 				function(ret) {
 					// ckeditor
 					if ($('[id^=ckeditor_instance_]').length) {
 						var u = xe.getApp('xeuploader');
 						if (u.length === 1) u[0].loadFilelist();
 						else u = $('#xefu-container-' + ret.sequence_srl).xeUploader();
 						// xpresseditor
 					} else if ($('.xpress-editor').length) {
 						reloadFileList(uploaderSettings[ret.sequence_srl]);
 					}

 					$('#upload_filelink').val('');
 				}
 			);
 			return false;
 		});
 	};

 	// check iframe
 	try {
 		var frm = $(window.frameElement);
 		if (frm.is('[id=pidOiFrame]')) {
 			sjDxFuncs.pidFrm = frm;
 			var mpp = frm.parent().parent(),
 				mbd = $('body', frm[0].contentDocument || frm[0].contentWindow.document);

 			if ($('#BELUXE_MESSAGE[data-valid-id=document_success_registed]').length) {
 				mpp.attr('data-parent-reload', 1);
 			}

 			$('.pid_modal-head:eq(0),.pid_modal-foot:eq(0)', mpp).each(function(i) {
 				var $pidtmp = $('#__PID_MODAL_' + (i ? 'FOOT' : 'HEAD') + 'ER__', mbd || '');
 				if ($pidtmp.length) {
 					$(this).html('<div>' + $pidtmp.eq(0).html() + '</div>').show();
 					$pidtmp.remove();
 				}
 			});

 			frm = null;
 			mpp = null;
 			mbd = null;
 		}
 	} catch (e) {}

 	$('a[data-type]', '.scSns')
 		.click(function() {
 			var $o = $('.scElps strong:eq(0)', '#siHrm'),
 				v, co, rl, mpp = sjDxFuncs.isMobj('m') || false;
 			co = encodeURIComponent((mpp ? mpp.find('.pid_modal-head:eq(0)').text() : $o.text()).trim());
 			rl = encodeURIComponent(mpp ? mpp.find('.pid_modal-foot:eq(0)').find('span:last').text() : $o.attr('title'));
 			switch ($(this).attr('data-type')) {
 				case 'fa':
 					v = 'http://www.facebook.com/share.php?t=' + co + '&u=' + rl;
 					break;
 				case 'de':
 					v = 'http://www.delicious.com/save?v=5&noui&jump=close&url=' + rl + '&title=' + co;
 					break;
 				default:
 					v = 'http://twitter.com/home?status=' + co + ' ' + rl;
 					break;
 			}
 			popopen(v, '_pop_sns');
 			return false;
 		});

 	if (!sjDxFuncs.pidFrm) {
 		$(window).resize(function() {
 			clearTimeout(window.resizedFinished);
 			window.resizedFinished = setTimeout(function() {
 				sjDxFuncs.reSized();
 			}, 500);
 		});
 	}

 	$(window)
 		.ready(function() {
 			//성격 급한 사람을 위해 일단 reSized 적용
 			sjDxFuncs.reSized(1);

 			$('#siWrt').eq(0).pidSettingWrite();

 			$('a[type^=example\\/modal]', '#siBody').each(function() {
 				$(this).pidModalWindow(sjDxFuncs.isMobj('b') || '');
 			});

 			$('a[data-slide]', '#siBody').each(function() {
 				$(this).on('before-open.mw', function() {
 						var srl = $(this).attr('data-slide') || 0;
 						if (!isNaN(srl)) this.manualShow = true;
 						else alert(srl);
 					})
 					.on('after-open.mw', function(e, slide) {
 						var a = this,
 							srl = $(this).attr('data-slide') || 0;
 						if (!srl) return true;
 						exec_json(
 							'file.getFileList', {
 								mid: current_mid,
 								editor_sequence: 0,
 								upload_target_srl: srl
 							},
 							function(ret_obj) {
 								var i, url, fs = ret_obj.files;
 								if (ret_obj.error === 0 && fs && fs.length) {
 									a.imglist = [];
 									for (i = 0, c = fs.length; i < c; i++) {
 										url = fs[i].download_url;
 										if (url.match(/\.(?:(jpe?g|png|gif|ico|bmp))$/i)) {
 											a.imglist[i] = url;
 										}
 									}
 									slide.list = a.imglist;
 									slide.index = 0;
 									slide.xeShow();
 								}
 							}
 						);
 					}).pidSlideShow();
 			});

 			$('[data-flash-fix=true]', '#siBody').each(function() {
 				$(this).pidModalFlashFix();
 			});

 			$('[data-link-fix=true]', '#siBody').find('a:not([target])').attr('target', '_blank');

 			// $('#searchFo').each(function() {
 			// 	var $i = $(this);
 			// 	$i.find('[name=search_target]').each(function() {
 			// 		var w = $(this).css('width', 'auto').outerWidth(true);
 			// 		$i.find('[name=search_keyword]').each(function() {
 			// 			$(this).css({'padding-left': (w + 5) + 'px', 'width': (w + ($.browser.chrome?125:35)) + 'px'});
 			// 		});
 			// 	});
 			// });
 		})
 		.load(function() {
 			//핫트랙은 이미지 로드후 크기가 변할 수 있어 다시 적용
 			sjDxFuncs.reSized();

 			$('a[data-modal-scrollinto=true]:last', sjDxFuncs.isMobj('s') || '').parent().is(function() {
 				$(this).closest('.scClst:hidden').is(function() {
 					$(this).show();
 				});
 				this.scrollIntoView(true);
 			});

 			// ie 에서 클릭(커서) 버그 방지, 그러나 다른 브라우저도 걍 포커스 주는거 나쁘지 않아서...
 			$('input:not(:hidden):eq(0)', '.pid_ajax-form').focus();

 			// 메세지가 가려지면 상단에 표시
 			$('#BELUXE_MESSAGE').each(function() {
 				var $i = $(this),
 					t = $i.offset().top + 10,
 					s = $(window).scrollTop();
 				if (t < s) {
 					var $m = $i.clone().addClass('clone').appendTo($i.parent());
 					$m.outerWidth($i.width() + 10);
 					setTimeout(function() {
 						$m.fadeOut(2000, function() {
 							$(this).remove();
 						});
 					}, 3000);
 				}
 			});
 		});

 })(jQuery);
