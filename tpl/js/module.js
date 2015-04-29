/**
 * @author phiDel (phidel@foxb.kr)
 * @update 2011/08/08
 **/

var __XEFM_NAME__ = 'beluxe',
	__XEFM_LANG__ = new Array();

String.prototype.ucfirst = function()
{
	var s=this;return s.charAt(0).toUpperCase() + s.slice(1);
};

String.prototype.trim = function(){
	return this.replace(/^\s+|\s+$/g,"");
};

String.prototype.urlDecode = function(){
	var o = this, t, r = /(%[^%]{2})/, v;
	while((v = r.exec(o)) != null && v.length > 1 && v[1] != '')
	{
		b = parseInt(v[1].substr(1), 16);
		t = String.fromCharCode(b);
		o = o.replace(v[1], t);
	}
	return o || '';
};

String.prototype.getEntryQuerys = function() {
	var o = this, t = /([^=]+)=([^&]*)(&|$)/g, n = o.indexOf('?'),
		a = arguments, q, v, x = new Array(), s = x;
	if(n == -1) return x;

	q = o.substr(n + 1, o.length);
	q.replace(t, function(){ var z = arguments; x[z[1]] = z[2]; });
	if(typeof x['entry'] == 'string' && x['entry'])
	{
		v = (x['entry'].urlDecode()).split('/');
		for(var i=0,c=v.length; i<c; i+=2) x[v[i]] = v[i + 1];
		x['entry'] = '';
	}

	if(a.length)
	{
		for(var i in a) s[a[i]] = x[a[i]];
		return s;
	}
	else return x;
};

String.prototype.setEntryQuerys = function(a) {
	var o = this, t = /entry=([^&]*)(&|$)/g, v;
	if(typeof a != 'object') a = new Array();

	if(t.test(o))
	{
		if(v = o.match(t))
		{
			v = v[0].urlDecode();
			v = v.substr((v.indexOf('=') + 1), v.length).split('/');
			for(var i=0,c=v.length; i<c; i+=2)
			{
				if(typeof a[v[i]] != 'undefined') continue;
				a[v[i]] = v[i + 1];
			}
		}
	}

	o = o.setQuery('entry','');
	if(typeof a == 'object')
	{
		for(var i in a) o = o.setQuery(i, a[i]);
	}

	return o;
};

jQuery(function($)
{
	$('form input[type=hidden][name=ruleset]').each(function(){
		var $f = $(this).closest('form');

		// ruleset 에 filter 가 있으면 필터 추가
		$('[name][filter-rule]', $f).each(function(){
			var v = xe.getApp('Validator')[0],
				n = $(this).attr('name'),
				r = $(this).attr('filter-rule'),
				m = $(this).attr('filter-name') || '';

			if (!v || !n || !r) return false;
			r = r.split(',');

			if(m) v.cast("ADD_MESSAGE", [n, m]);
			v.cast("ADD_EXTRA_FIELD", [n,
				{
					required: r[0] == 'true',
					rule: r[1] || '',
					minlength: r[2] || 0,
					maxlength: r[3] || 0,
					equalto: r[4] || ''
				}
			]);
		});

		// TODO 중복 호출 막기, xe 1.5.3.4 부터 지원, 하위 호환용...
		// xe issue 1253 , nagoon
		$('button[type=submit],input[type=submit]', $f).click(function(e){
			var $o = $(e.currentTarget);
			setTimeout(function(){return function(){$o.attr('disabled', 'disabled');};}(), 0);
			setTimeout(function(){return function(){$o.removeAttr('disabled');};}(), 3000);
		});
	});

	// * 주의 * 아래 코드는 라이센스 관련 코드이므로 절대 변경하시면 안됩니다.
	// Only authorized users can delete the license.
	$('a[href=#beluxe]:eq(0)').text('Board DX').attr('href','http://www.foxb.kr/').attr('target','_blank').show();
});