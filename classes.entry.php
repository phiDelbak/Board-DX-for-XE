<?php
/**
 * @class  BeluxeEntry
 * @author phiDel (xe.phidel@gmail.com)
 * @brief entry class of the BoardDX module
 */

class BeluxeEntry extends Object
{
	var $ent = '';
	var $rqv = array();

	function &getInstance()
	{
		static $theInstance = NULL;
		if(!$theInstance) $theInstance = new beluxeEntry();
		return $theInstance;
	}

	function beluxeEntry()
	{
		$this->rqv = $_GET;
		$ent = urldecode($this->rqv['entry']);

		// 필요없는거 삭제
		unset($this->rqv['entry']);
		unset($this->rqv['error_return_url']);
		unset($this->rqv['success_return_url']);
		unset($this->rqv['ruleset']);

		// 필수값 넣기
		$gets = Context::gets('vid', 'mid');
		$this->rqv['vid'] = $gets->vid;
		$this->rqv['mid'] = $gets->mid;

		// entry 값이 있으면 분해후 저장
		if($ent && $this->ent != $ent)
		{
			$this->ent = $ent;
			$ent_list = explode('/', $ent);
			for($i = 0, $c = count($ent_list); $i < $c; $i += 2)
			{
				$key = $ent_list[$i];
				$val = trim($ent_list[$i + 1]);
				Context::set($key, $val);
				$this->rqv[$key] = $val;
			}
		}
	}

/**************************************************************/
/*********** @private function					  ***********/

	function _get($arlst)
	{
		$relst = array();

		for($i = 0, $c = count($arlst); $i < $c; $i++)
		{
			$key = $arlst[$i];
			if(!isset($this->rqv[$key]))continue;
			$relst->{$key} = $this->rqv[$key];
		}

		return (count($relst) > 1) ? $relst : current($relst);
	}

	function _set($arlst)
	{
		for($i = 0, $c = count($arlst); $i < $c; $i += 2)
		{
			$key = $arlst[$i];
			$val = trim($arlst[$i + 1]);

			Context::set($key, $val);

			if(strlen($val)) $this->rqv[$key] = $val;
			else unset($this->rqv[$key]);
		}
	}

/**************************************************************/

	function get()
	{
		is_a($this, 'BeluxeEntry') ? $self = $this : $self = &BeluxeEntry::getInstance();
		return func_num_args()?$self->_get(func_get_args()):(object)$self->rqv;
	}

	function set()
	{
		is_a($this, 'BeluxeEntry') ? $self = $this : $self = &BeluxeEntry::getInstance();
		if(func_num_args()) $self->_set(func_get_args());
	}

	function getUrl()
	{
		is_a($this, 'BeluxeEntry') ? $self = $this : $self = &BeluxeEntry::getInstance();

		$arnum  = func_num_args();
		$arlst = func_get_args();

		if($arnum)
		{
			$rewrite = Context::isAllowRewrite();
			$not = array('act','vid','mid','key','search_target','search_keyword');

			$i = (trim($arlst[0]) == '') ? 1 : 0;
			$e = $i ? array() : $self->rqv;

			// 값 셋팅
			for($i, $c = count($arlst); $i < $c; $i += 2)
			{
				$key = $arlst[$i];
				$val = trim($arlst[$i + 1]);

				if(strlen($val)) $e[$key] = $val;
				else unset($e[$key]);
			}

			$arg = array('');
			$try = array();

			// 보내기 형식에 맞게 분해
			foreach($e as $k => $v)
			{
				if(!strlen($v)) continue;

				$no_ent = !$rewrite || in_array($k, $not);
				$no_ent ? $arg[] = $k : $try[] = $k;
				$no_ent ? $arg[] = $v : $try[] = $v;
			}

			if($c = count($try))
			{
				$no_ent = $c == 2 && $try[0] == 'document_srl';
				$arg[] = $no_ent ? 'document_srl' : 'entry';
				$arg[] = $no_ent ? $try[1] : implode('/', $try);
			}

			// 분해된 값으로 url 추출
			$url = Context::getUrl(count($arg), $arg);
		}
		else $url = Context::getRequestUri();

		return str_replace('&amp;', '&', urldecode($url));
	}
}

/* End of file classes.entry.php */
/* Location: ./modules/beluxe/classes.entry.php */