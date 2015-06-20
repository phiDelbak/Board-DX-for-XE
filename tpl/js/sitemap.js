/*
 * sitemap.js for BoardDX
 * @author NHN (developers@xpressengine.com) site map
 * @optimizer phiDel (xe.phidel@gmail.com)
 */

jQuery(function($) {

	var
		pid_dragging = false,
		$pidPholder = $('<li class="placeholder">');

	$('#dxiStMapFrm form.dxcStMap')
		.on({
			'mousedown.st': function(event) {
				var $this, $uls, $ul, width, height, offset, position, offsets, i, dropzone, wrapper = '';

				if ($(event.target).is('a,input,label,textarea') || event.which != 1) return;

				pid_dragging = true;

				$this = $(this);
				height = $this.height();
				width = $this.width();
				$uls = $this.parentsUntil('.dxcStMap').filter('ul');
				$ul = $uls.eq(-1);

				$ul.css('position', 'relative');

				position = {
					x: event.pageX,
					y: event.pageY
				};
				offset = getPidOffset(this, $ul.get(0));

				$clone = $this.clone(true).attr('target', true);

				for (i = $uls.length - 1; i; i--) {
					$clone = $clone.wrap('<li><ul /></li>').parent().parent();
				}

				// get offsets of all list-item elements
				offsets = [];
				$ul.find('li').each(function(idx) {
					if ($this[0] === this || $this.has(this).length) return true;

					var o = getPidOffset(this, $ul.get(0));
					offsets.push({
						top: o.top,
						bottom: o.top + 32,
						item: this
					});
				});

				// Remove unnecessary elements from the clone, set class name and styles.
				// Append it to the list
				$clone
					.find('.side,input').remove().end()
					.addClass('draggable')
					.css({
						position: 'absolute',
						opacity: '.6',
						width: width,
						height: height,
						left: offset.left,
						top: offset.top,
						zIndex: 100
					})
					.appendTo($ul.eq(0));

				// Set a place holder
				$pidPholder
					.css({
						position: 'absolute',
						opacity: '.6',
						width: width,
						height: '5px',
						left: offset.left,
						top: offset.top,
						zIndex: 99
					})
					.appendTo($ul.eq(0));

				$this.css('opacity', '.6');

				$(document)
					.off('mousemove.st mouseup.st')
					.on('mousemove.st', function(event) {
						var diff, nTop, item, i, c, o, t;

						dropzone = null;

						diff = {
							x: position.x - event.pageX,
							y: position.y - event.pageY
						};
						nTop = offset.top - diff.y;

						for (i = 0, c = offsets.length; i < c; i++) {
							t = nTop;
							o = offsets[i];

							if (i === 0 && t < o.top) t = o.top;
							if (i == c - 1 && t > o.bottom) t = o.bottom;

							if (o.top <= t && o.bottom >= t) {
								dropzone = {
									element: o.item,
									state: setPidHolder(o, t)
								};
								break;
							}
						}

						$clone.css({
							top: nTop
						});
					})
					.on('mouseup.st', function(event) {
						var $dropzone, $li;

						pid_dragging = false;

						$(document).off('mousemove.st mouseup.st');
						$this.css('opacity', '');
						$clone.remove();
						$pidPholder.remove();

						// dummy list item for animation
						$li = $('<li />').height($this.height());

						if (!dropzone) return;
						$dropzone = $(dropzone.element);

						$this.before($li);

						if (dropzone.state == 'prepend') {
							if (!$dropzone.find('>ul').length) $dropzone.find('>.side').after('<ul>');
							$dropzone.find('>ul').prepend($this.hide());
						} else {
							$dropzone[dropzone.state]($this.hide());
						}

						$this.slideDown(100, function() {
							$this.removeClass('active');
						});
						$li.slideUp(100, function() {
							var $par = $li.parent();
							$li.remove();
							if (!$par.children('li').length) $par.remove();
						});

						// trigger 'dropped.st' event
						$this.trigger('dropped.st');
					});

				return false;
			},
			'mouseover.st': function() {
				if (!pid_dragging) $(this).addClass('active');
				return false;
			},
			'mouseout.st': function() {
				if (!pid_dragging) $(this).removeClass('active');
				return false;
			}
		},'li:not(.placeholder)')
		.find('li')
		.prepend('<button type="button" class="moveTo">Move to</button>')
		//.append('<span class="vr"></span><span class="hr"></span>')
		.end();

	function getPidOffset(elem, offsetParent) {
		var top = 0,
			left = 0;

		while (elem && elem != offsetParent) {
			top += elem.offsetTop;
			left += elem.offsetLeft;

			elem = elem.offsetParent;
		}

		return {
			top: top,
			left: left
		};
	}

	function setPidHolder(info, yPos) {
		if (Math.abs(info.top - yPos) <= 3) {
			$pidPholder.css({
				top: info.top - 3,
				height: '5px'
			});
			return 'before';
		} else if (Math.abs(info.bottom - yPos) <= 3) {
			$pidPholder.css({
				top: info.bottom - 3,
				height: '5px'
			});
			return 'after';
		} else {
			$pidPholder.css({
				top: info.top + 3,
				height: '27px'
			});
			return 'prepend';
		}
	}

});
