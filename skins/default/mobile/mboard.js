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
			c = prompt('Please describe the reasons.', '');
			if (typeof c != 'string') return false;
			if (!c.trim()) return alert('Please enter the message.') || false;
			exec_json(
				ty + '.proc' + ty.pidUcfirst() + 'Declare',
				params,
				function(ret_obj) {
					alert(ret_obj.message);
					if (ret_obj.error === 0) {
						if (rec == '0') return location.reload() || false;
						var t = '[Board DX] Declare, ' + ty + ':' + srl,
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
});
