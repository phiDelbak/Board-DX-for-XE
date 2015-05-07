/**
 * jQuery.timers - Timer abstractions for jQuery
 * Written by Blair Mitchelmore (blair DOT mitchelmore AT gmail DOT com)
 * Licensed under the WTFPL (http://sam.zoy.org/wtfpl/).
 * Date: 2009/10/16
 *
 * @author Blair Mitchelmore
 * @version 1.2
 *
 **/
jQuery(function($) {
    $.fn.extend({
        everyTime: function(interval, label, fn, times) {
            return this.each(function() {
                $.timer.add(this, interval, label, fn, times);
            });
        },
        oneTime: function(interval, label, fn) {
            return this.each(function() {
                $.timer.add(this, interval, label, fn, 1);
            });
        },
        stopTime: function(label, fn) {
            return this.each(function() {
                $.timer.remove(this, label, fn);
            });
        }
    });
    $.extend({
        timer: {
            global: [],
            guid: 1,
            dataKey: "jQuery.timer",
            regex: /^([0-9]+(?:\.[0-9]*)?)\s*(.*s)?$/,
            powers: {
                // Yeah this is major overkill...
                'ms': 1,
                'cs': 10,
                'ds': 100,
                's': 1000,
                'das': 10000,
                'hs': 100000,
                'ks': 1000000
            },
            timeParse: function(value) {
                if (value === undefined || value === null) return null;
                var result = this.regex.exec($.trim(value.toString()));
                if (result[2]) {
                    var num = parseFloat(result[1]);
                    var mult = this.powers[result[2]] || 1;
                    return num * mult;
                } else {
                    return value;
                }
            },
            add: function(element, interval, label, fn, times) {
                var counter = 0;
                if ($.isFunction(label)) {
                    if (!times) times = fn;
                    fn = label;
                    label = interval;
                }
                interval = $.timer.timeParse(interval);
                if (typeof interval != 'number' || isNaN(interval) || interval < 0) return;
                if (typeof times != 'number' || isNaN(times) || times < 0) times = 0;
                times = times || 0;
                var timers = $.data(element, this.dataKey) || $.data(element, this.dataKey, {});
                if (!timers[label]) timers[label] = {};
                fn.timerID = fn.timerID || this.guid++;
                var handler = function() {
                    if ((++counter > times && times !== 0) || fn.call(element, counter) === false) $.timer.remove(element, label, fn);
                };
                handler.timerID = fn.timerID;
                if (!timers[label][fn.timerID]) timers[label][fn.timerID] = window.setInterval(handler, interval);
                this.global.push(element);
            },
            remove: function(element, label, fn) {
                var timers = $.data(element, this.dataKey),
                    ret = null;
                if (timers) {
                    if (!label) {
                        for (label in timers) this.remove(element, label, fn);
                    } else if (timers[label]) {
                        if (fn) {
                            if (fn.timerID) {
                                window.clearInterval(timers[label][fn.timerID]);
                                delete timers[label][fn.timerID];
                            }
                        } else {
                            for (var f in timers[label]) {
                                window.clearInterval(timers[label][f]);
                                delete timers[label][f];
                            }
                        }
                        for (ret in timers[label]) break;
                        if (!ret) {
                            ret = null;
                            delete timers[label];
                        }
                    }
                    for (ret in timers) break;
                    if (!ret) $.removeData(element, this.dataKey);
                }
            }
        }
    });
    $(window).bind("unload", function() {
        $.each($.timer.global, function(index, item) {
            $.timer.remove(item);
        });
    });
        
    /** phiDel (xe.phidel@gmail.com) // onresize는 혹시 몰라서 그냥 타이머로 **/
    $.fn.pidModalResize = function(resize){
        var $modal = $(this), target = $modal.data('anchor').attr('data-target') || '', 
            name = $modal.data('frame_id') || 'pidOframe';
        resize = resize || 'auto';

        if (!target) {
            $('#' + name, $modal).each(function() {
                var $this = $(this),
                    $parent = $(parent);
                var doc = $this.get(0).contentDocument || $this.get(0).contentWindow.document;
                if (doc === undefined) return;
                var $body = $('body', doc),
                    $form = $('form:first', $body);
                $body.css({
                    padding: '0',
                    margin: '0',
                    overflow: 'hidden'
                });
                $form.css({
                    padding: '0',
                    margin: '0',
                    overflow: 'hidden'
                });
                if (resize == 'hfix') $body.height($parent.height() - 100);
                $this.height($body.outerHeight(true));
                $modal.everyTime(500, 'time_resize_event', function(i) {
                    var $fg = $('.pid_modal-body:eq(0)', $modal);
                    if ($modal.is(':hidden') || !$this.length) {
                        //$fg.removeAttr( 'style' );
                        //$body.removeAttr( 'style' );
                        //$this.removeAttr( 'style' );
                        $modal.stopTime('time_resize_event');
                        return;
                    }
                    var chkh = $parent.height() - 100,
                        bdoh = $body.outerHeight(true);
                    $body.css('overflow-y', chkh > bdoh ? 'hidden' : 'auto');
                    $this.css({
                        'height': (chkh > bdoh ? bdoh : chkh),
                        'width': ($parent.width() - 80)
                    });
                    $fg.height($this.outerHeight(true));
                    var t = (($parent.height() - $fg.outerHeight()) / 2) - 10;
                    c = {
                        top: (t > 10 ? t : 10) + 'px',
                        left: (($parent.width() - $fg.outerWidth()) / 2) + 'px'
                    };
                    if ($fg.position().left < 0) {
                        $fg.animate(c);
                    } else {
                        $fg.css(c);
                    }
                });
            });
        } else {
            $('#' + name, $modal).each(function() {
                var $this = $(this);
                var doc = $this.get(0).contentDocument || $this.get(0).contentWindow.document;
                if (doc === undefined) return;
                var $body = $('body', doc),
                    $form = $('form:first', $body);
                $body.css({
                    padding: '0',
                    margin: '0',
                    overflow: 'hidden'
                });
                $form.css({
                    padding: '0',
                    margin: '0',
                    overflow: 'hidden'
                });
                $this.height($body.outerHeight(true));
                $modal.everyTime(500, 'time_resize_event2', function(i) {
                    if ($modal.is(':hidden') || !$this.length) {
                        $modal.stopTime('time_resize_event2');
                        if (!$this.length) return;
                        $this.css('height', '');
                        return;
                    }
                    $this.height($body.outerHeight(true));
                });
            });
        }
        
        $('[data-modal-child=message]', parent.document).fadeOut(2500, function() {
            $(this).remove();
        });
    };
});