/*
 * module.js for BoardDX
 * @author phiDel (xe.phidel@gmail.com)
 */

// const for identify
var _PID_MODULE_ = 'beluxe';

// use decodeURI
// String.prototype.urlDecode = function(){
// 	var o = this, t, r = /(%[^%]{2})/, v;
// 	while((v = r.exec(o)) != null && v.length > 1 && v[1] != '')
// 	{
// 		b = parseInt(v[1].substr(1), 16);
// 		t = String.fromCharCode(b);
// 		o = o.replace(v[1], t);
// 	}
// 	return o || '';
// };

// String.prototype.trim = function(){
// 	return this.replace(/^\s+|\s+$/g,"");
// };


// ruleset 사용시 일부 사용자 필터 따로 만들어 준다.
jQuery(function($) {

	$('form input[type=hidden][name=ruleset]').each(function() {
		var $f = $(this).closest('form');

		// ruleset 에 사용자 filter 가 있으면 필터 추가
		$('[name][data-filter-rule]', $f).each(function() {
			var v = xe.getApp('Validator')[0],
				i = $(this),
				n = i.attr('name'),
				r = i.attr('data-filter-rule'),
				m = i.attr('data-filter-name') || '';

			if (!v || !n || !r) return false;
			r = r.split(',');

			if (!(/^[a-z_]*$/i.test(r[1] || ''))) {
				v.cast('ADD_RULE', [n, new RegExp(r[1])]);
				v.cast("ADD_MESSAGE", ['invalid_' + n, ' of invalid rule']);
				r[1] = n;
			}

			if (m) v.cast("ADD_MESSAGE", [n, m]);
			v.cast("ADD_EXTRA_FIELD", [n, {
				required: r[0] === 'true',
				rule: r[1] || '',
				minlength: r[2] || 0,
				maxlength: r[3] || 0,
				equalto: r[4] || ''
			}]);
		});

		// TODO 중복 호출 막기, xe 1.5.3.4 부터 지원, 하위 호환용...
		// xe issue 1253 , nagoon
		// $('button[type=submit],input[type=submit]', $f).click(function(e){
		// 	var $o = $(e.currentTarget);
		// 	setTimeout(function(){return function(){$o.attr('disabled', 'disabled');};}(), 0);
		// 	setTimeout(function(){return function(){$o.removeAttr('disabled');};}(), 3000);
		// });
	});

	$('a[href=#beluxe]:eq(0)').text('Board DX').attr('href', 'https://github.com/phiDelbak/Board-DX-for-XE').attr('target', '_blank');
});
