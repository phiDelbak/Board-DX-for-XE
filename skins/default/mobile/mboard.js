jQuery(function($) {
	String.prototype.pidUcfirst = function() {
		var s = this;
		return s.charAt(0).toUpperCase() + s.slice(1);
	};

	$.fn.pidDeclareBtninit = function() {
		this.click(function() {
			var $i = $(this),
				c,
				ty = $i.attr('data-type'),
				srl = $i.attr('data-srl'),
				rec = $i.attr('data-rec') || '0',
				params = {
					target_srl: srl,
					cur_mid: current_mid,
					mid: current_mid
				};
			c = prompt(_DXS_MSGS_.declare, '');
			if (typeof c != 'string') return false;
			if (!c.trim()) return alert('Please enter the message.') || false;
			exec_json(
				ty + '.proc' + ty.pidUcfirst() + 'Declare',
				params,
				function(ret_obj) {
					alert(ret_obj.message);
					if (ret_obj.error === 0) {
						if (rec == '0') return location.reload() || false;
						var t = (_DXS_MSGS_.declare_msg || 'declare').replace(/%s/g, srl),
							u = current_url.setQuery('comment_srl', ('comment' ? srl : ''));
						c = c + '<br /><br /><a href="' + u + '">' + u + '</a>';
						var params2 = {
							receiver_srl: rec,
							title: t,
							content: c
						};
						exec_json('communication.procCommunicationSendMessage', params2,
							function(ret_obj2) {
								alert(ret_obj2.message);
								location.reload();
							}
						);
					}
				}
			);
			return false;
		});
	};
	$.fn.pidVoteBtninit = function() {
		this.click(function() {
			var $o = $(this),
				hr = $o.attr('href'),
				ty = $o.attr('data-type'),
				srl = $o.attr('data-srl');
			var params = {
				target_srl: srl,
				cur_mid: current_mid,
				mid: current_mid
			};
			exec_json(
				ty + '.proc' + ty.pidUcfirst() + (hr == '#recommend' ? 'VoteUp' : 'VoteDown'),
				params,
				function(ret_obj) {
					alert(ret_obj.message);
					if (ret_obj.error === 0) {
						var $e = $o.find('em.cnt');
						$e.text((parseInt($e.text()) || 0) + (hr == '#recommend' ? 1 : -1));
					}
				}
			);
			return false;
		});
	};
	pidLoadPage = function(r, z, c) {
		if (!$("#clwm").length) {
			var wmgs = '<div id="clwm" style="height:2px;overflow:hidden"><img src="../../../common/img/msg.loading.gif" style="width:100%" /></div>';
			if ($("#clpn").length)
				$("#clpn").before(wmgs);
			else $("#clst").after(wmgs);
		}
		exec_json(
			'beluxe.getBeluxeMobileCommentPage', {
				cpage: z,
				document_srl: r,
				clist_count: c,
				mid: current_mid
			},
			function(ret) {
				var $htm = $(ret.html);
				$("#cl").remove();
				$("#clpn").remove();
				$("a.prev[data-page],a.next[data-page]", $htm).click(function() {
					var r = $(this).attr('data-srl'),
						z = $(this).attr('data-page'),
						c = $(this).attr('data-count');
					pidLoadPage(r, z, c);
					return false;
				});
				$('a[href^=#][href$=recommend][data-type]', $htm).pidVoteBtninit();
				$('a[href=#declare][data-type]', $htm).pidDeclareBtninit();
				$("#clwm").remove();
				$("#clst").after($htm);
			}
		);
	};

	$('a[href=#declare][data-type]').pidDeclareBtninit();
	$('a[href^=#][href$=recommend][data-type]').pidVoteBtninit();
	$('#xe_message:eq(0)').each(function() {
		alert($('p', this).text());
	});

	$('#read:first').each(function() {
		var g = false;
		$('.tgr[data-srl]').click(function() {
			if (!g) {
				g = true;
				var r = $(this).attr('data-srl'),
					z = $(this).attr('data-page'),
					c = $(this).attr('data-count');
				pidLoadPage(r, z, c);
				return false;
			}
		});
		$('.co .mm').next().hide();
		$('.mm').click(function() {
			$(this).hide().next().show();
			return false;
		});
		$('.tbn').click(function() {
			$(this).next('.tgo').slideToggle('fast');
			return false;
		});
		$('.tgr[data-load]').each(function() {
			$(this).click();
		});
	});

	$('a[href=#categoryOpen]').click(function() {
		var $sd = $('.bd > .sd');
		if ($sd.is(':hidden')) {
			$sd.width(0).show();
			$('.bd > .st').animate({
				marginLeft: -250,
				marginRight: 250
			}, {
				step: function(now, fx) {
					$('.bd > .sd').width(now);
				}
			});
		} else {
			$('.bd > .st').animate({
				marginLeft: 0,
				marginRight: 0
			}, {
				step: function(now, fx) {
					$('.bd > .sd').width(now);
				},
				complete: function() {
					$sd.hide();
				}
			});
		}
		return false;
	});

	$('li', '.nav').each(function() {
		var w = $('.fr', this).width() || 0;
		$('a', this).css('padding-right', w + 10 + 'px');
	});

	$('a[data-type]', '.scSns')
		.click(function() {
			var $o = $('h2', '.pn:eq(0)'),
				v, co, rl;
			co = encodeURIComponent($o.text().trim());
			rl = encodeURIComponent($o.attr('title'));
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

	$('a[data-slide]', '#list').each(function() {
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

	$('#fUloader').is(function() {
		var fUfuncs = {
				upBtn: $('#fUloader').find('.flc'),
				evalScripts: function(txt, identify) {
					var ScriptFragment = '<script[^>]*>([^]+?)<\/script>',
						matchAll = new RegExp(ScriptFragment, 'img'),
						matchOne = new RegExp(ScriptFragment, 'im');
					var extractScripts = (txt.match(matchAll) || []).map(function(scriptTag) {
						var tst = (scriptTag.match(matchOne) || ['', ''])[1];
						return /^<\/?script>/i.test(tst) ? '' : tst;
					});
					eval(extractScripts.join(';'));
					return eval(identify); // window[identify]; // get variable value by name
				},
				reLoadList: function(frl) {
					var ff = document.getElementById('uploaderForm');

					exec_json(
						"file.getFileList", {
							mid: current_mid,
							editor_sequence: '1',
							upload_target_srl: ff.uploadTargetSrl.value || ''
						},
						function(ret) {
							var i, r, z, x, m, v, fs, ls, c = 0;

							fs = ret.files;
							ls = $('#siFiles').empty();

							$('#fUpreview').find('.fpv').empty().end().is(function() {
								if (fs && fs.length) $(this).show();
								else $(this).hide();
							});

							if (fs && fs.length) {
								for (i = 0, c = fs.length; i < c; i++) {
									v = fs[i];
									m = v.source_filename;
									x = v.download_url;
									r = v.file_srl;
									z = parseInt(v.file_size);
									z = z < 1024 ? z + 'Byte' : (z < 1048576 ? (Math.round((z / 1024) * 10) / 10) + 'KB' : (Math.round((z / 1048576) * 10) / 10) + 'MB');

									$('<option value="' + r + '" data-src="' + x + '"></option>')
										.appendTo(ls).text(m + ' (' + z + ')').addClass(x !== "" ? 'success' : 'error');
								}
								if (frl !== undefined && frl) ls.val(frl);
								ls.trigger('change');
							}

							fUfuncs.upBtn.text('File upload (' + c + ')');
							//fUfuncs.upIfo.html(ret.upload_status);
						}
					);
				}
			},
			afOptions = {
				beforeSend: function() {
					fUfuncs.upBtn.text('0%');
				},
				uploadProgress: function(event, position, total, percentComplete) {
					fUfuncs.upBtn.text(percentComplete + '%');
				},
				success: function() {
					fUfuncs.upBtn.text('100%');
				},
				complete: function(response) {
					var ff, uifo = fUfuncs.evalScripts(response.responseText, 'uploaded_fileinfo') || {};
					if (uifo.error < 0) {
						alert(uifo.message);
					} else {
						ff = document.getElementById('siFf');
						ff.document_srl.value = uifo.upload_target_srl || '';
						ff = document.getElementById('uploaderForm');
						ff.uploadTargetSrl.value = uifo.upload_target_srl || '';
					}
					fUfuncs.upBtn.text('File upload (0)');
					fUfuncs.reLoadList(uifo.file_srl);
				},
				error: function() {
					alert('ERROR: unable to upload files');
				}
			};

		$.fn.insertAtCaret = function(v) {
			var o = (typeof this[0].name != 'undefined') ? this[0] : this;

			if ($.browser.msie) {
				o.focus();
				sel = document.selection.createRange();
				sel.text = v;
				o.focus();
			} else if ($.browser.mozilla || $.browser.webkit) {
				var s = o.selectionStart,
					d = o.selectionEnd,
					t = o.scrollTop;
				o.value = o.value.substring(0, s) + v + o.value.substring(d, o.value.length);
				o.focus();
				o.selectionStart = s + v.length;
				o.selectionEnd = s + v.length;
				o.scrollTop = t;
			} else {
				o.value += v;
				o.focus();
			}
		};

		$('#uploaderForm')
			.find('input[type=file]')
			.on('change', function() {
				$(this).parent().trigger('submit');
			})
			.end().ajaxForm(afOptions);

		fUfuncs.upBtn.click(function() {
			$('#uploaderForm').find('input[type=file]').trigger('click');
			return false;
		});

		$('#fUpreview').is(function() {
			$(this).find('.scFcl').click(function() {
				var r = $('#siFiles').val();
				if (r && confirm('Do you want to delete a file?')) {
					exec_json(
						"file.procFileDelete", {
							file_srls: r,
							editor_sequence: '1'
						},
						function(ret) {
							fUfuncs.reLoadList();
						}
					);
				}
				return false;
			});

			$(this).find('.scFin').click(function() {
				var t, o = $('#siFiles');
				o = [
					o.find('option[value=' + o.val() + ']').text(),
					o.find('option[value=' + o.val() + ']').attr('data-src')
				];
				if (o) {
					if (o[1].match(/\.(?:(jpe?g|png|gif|ico))$/i)) {
						t = '<img src="' + o[1] + '" />';
						// } else if (o[1].match(/\.(?:(wmv|mpe?g|avi|swf|flv|mp4|as[fx]|moo?v|qt|mkv|m4v|ra?m))$/i)) {
						// 	t = '<video src="' + o[1] + '">' + o[1] + '</video>';
						// } else if (o[1].match(/\.(?:(wma|mp[1-3]|wav|midi?|ra))$/i)) {
						// 	t = '<audio src="' + o[1] + '">' + o[1] + '</audio>';
					} else {
						t = '<a href="' + o[1] + '">' + o[0] + '</a>';
					}
					$('textarea[name=content]', '#siFf').insertAtCaret(t);
					$('input[name=use_html]', '#siFf').attr('checked', 'checked');
				}
				return false;
			});

			$('#siFiles').change(function() {
				var t,
					v = $('#fUpreview').find('.fpv'),
					o = $(this),
					file_srl = o.val();

				o = [
					o.find('option[value=' + o.val() + ']').text(),
					o.find('option[value=' + o.val() + ']').attr('data-src')
				];

				if (o[1].match(/\.(?:(jpe?g|png|gif|ico))$/i)) {
					t = '<img src="' + o[1] + '" class="cover_image" />';
					v.addClass('is_image');
				} else {
					if (o[1]) t = '<img src="./modules/editor/tpl/images/files.gif" />';
					else t = '<img src="./common/img/blank.gif" />';
					v.removeClass('is_image');
				}

				$(t).click(function() {
					if (!v.is('[data-set-cover]')) return false;
					if (($(this).attr('class') || '') === 'cover_image') {
						exec_json('file.procFileSetCoverImage', {
								'file_srl': file_srl,
								'mid': current_mid,
								'editor_sequence': '1'
							},
							function(res) {
								if (res.error !== 0) return;
								alert('set cover image');
							}
						);
					}
					return false;
				}).appendTo(v.empty());
			});
		});

		fUfuncs.reLoadList();
	});
});
