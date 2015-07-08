<?php
/**
 * @class  BeluxeItem
 * @author phiDel (xe.phidel@gmail.com)
 * @brief item class of the BoardDX module
 */

class BeluxeItem extends Object
{
	var $module_srl = 0;
	var $member_srl = 0;

	function beluxeItem($a_modsrl)
	{
		$this->module_srl = $a_modsrl;
		$oLogIfo = Context::get('logged_info');
		$this->member_srl = (int) $oLogIfo->member_srl;
	}

	// ruleset 사용시 callback 과 extra_keys 메세지 지원 안해주니...
	function addExtraKeyJsFilter()
	{
		$ccDocument = getController('document');
		$ccDocument->addXmlJsFilter($this->module_srl);
	}

	function setCustomActions()
	{
		$num_args = func_num_args();
		if($num_args < 3) return;

		$args_list = func_get_args();
		$a_docsrl = $args_list[0];
		unset($args_list[0]);

		$cmThis = &getModel(__XEFM_NAME__);
		return $cmThis->setCustomActions($a_docsrl, $args_list);
	}

	function getAdminId()
	{
		$modsrl = $this->module_srl;

		if(!isset($GLOBALS['BELUXE_ADMIN_ID'][$modsrl])){
			$aids = array();
			$cmModule = &getModel('module');
			$out = $cmModule->getAdminId($modsrl);
			if($out){
				foreach($out as $key=>$val){
					$aids[$key]->member_srl = $val->member_srl;
					$aids[$key]->user_id = $val->user_id;
				}
			}
			$GLOBALS['BELUXE_ADMIN_ID'][$modsrl] = $aids;
		}

		return $GLOBALS['BELUXE_ADMIN_ID'][$modsrl];
	}

	function getDefinedLang($a_lang)
	{
		if(is_string($a_lang) && strpos($a_lang, '$user_lang->') !== false) {
			$ccModule = &getController('module');
			$ccModule->replaceDefinedLangCode($a_lang);
		}
		return $a_lang;
	}

	function getBrowserInfo()
	{
		// http://php.net/manual/en/function.get-browser.php
		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		$known = array('trident', 'firefox', 'chrome', 'opr', 'safari', 'netscape', 'webkit', 'konqueror', 'gecko');
		$pattern = '/(' . implode('|', $known) . ')[\/ ]+([0-9]+(?:\.[0-9]+)?)/';

		if (!@preg_match_all($pattern, $agent, $matches)) return array('unknown' => '1');

		$i = count($matches[1])-1;
		if($matches[1][$i - 1] === 'chrome') $i--;
		return array($matches[1][$i] => $matches[2][$i]);
	}

	function getIpaddress($is_crypt)
	{
		return $is_crypt ? substr(crypt(md5($_SERVER['REMOTE_ADDR']),'dx'),2) : $_SERVER['REMOTE_ADDR'];
	}

	function getPoint()
	{
		if(!$this->member_srl) return 0;
		$cmPoint = &getModel('point');
		return $cmPoint->getPoint($this->member_srl);
	}

	function getCategoryList()
	{
		$cmThis = &getModel(__XEFM_NAME__);
		return $cmThis->getCategoryList($this->module_srl);
	}

	function getNavigationList($a_docsrl, $a_count = 5)
	{
		$args = Context::get('beluxe_list_sort_keys');
		$args->category_srl = Context::get('category_srl');
		$args->module_srl = $this->module_srl;
		$args->list_count = floor($a_count / 2);
		if($args->list_count<1) $args->list_count = 1;

		$cmDocument = &getModel('document');

		//2.2이후 문서 번호를 받음
		if(!is_object($a_docsrl)){
			$doc = $cmDocument->getDocument((int)$a_docsrl, false, true);
			if(!$doc->isExists()) return array();
		}else{
			$doc = $a_docsrl;
		}

		$args->current_document_srl = $doc->document_srl;
		if(!$args->current_document_srl) return array();

		$args->page = $cmDocument->getDocumentPage($doc, $args);

		$cmThis = &getModel(__XEFM_NAME__);
		$out = $cmThis->getNavigationList($args, FALSE, FALSE);

		return $out->data;
	}

	function getHistoryList($a_docsrl, $a_page = 0)
	{
		$cmThis = &getModel(__XEFM_NAME__);
		$out = $cmThis->getHistoryList($a_docsrl, $a_page, 5);

		if(!$out || !$out->data){
			$out->data = $out->page_navigation = array();
			$out->total_count = $out->total_page = 0;
		}

		Context::set('hpage', (int)$out->page_navigation->cur_page);
		return $out;
	}

	function getNoticeList()
	{
		$cmThis = &getModel(__XEFM_NAME__);
		$out = $cmThis->getNoticeList($this->module_srl);
		return ($out&&$out->data)?$out->data:array();
	}

	function getBestList()
	{
		$cmThis = &getModel(__XEFM_NAME__);
		$out = $cmThis->getBestList($this->module_srl);
		return ($out&&$out->data)?$out->data:array();
	}

	function getBestCommentList($a_docsrl)
	{
		$cmThis = &getModel(__XEFM_NAME__);
		$out = $cmThis->getBestCommentList($a_docsrl);
		return ($out&&$out->data)?$out->data:array();
	}

	function getDocumentExtraVars($a_docsrl = 0)
	{
		$cmDocument = &getModel('document');
		if((int) $a_docsrl)
			return $cmDocument->getExtraVars($this->module_srl, $a_docsrl);
		else
			return $cmDocument->getExtraKeys($this->module_srl);
	}

	function getDocumentVotedLogs($a_docsrl, $a_point = 0, $a_sort = '')
	{
		$cmThis = &getModel(__XEFM_NAME__);
		return $cmThis->getDocumentVotedLogs($a_docsrl, $a_point, 0, $a_sort);
	}

	function getDocumentVotedLogCount($a_docsrl, $a_point = 0)
	{
		$cmThis = &getModel(__XEFM_NAME__);
		return $cmThis->getDocumentVotedLogCount($a_docsrl, $a_point);
	}

	function getDocumentDeclaredCount($a_docsrl)
	{
		$cmThis = &getModel(__XEFM_NAME__);
		return $cmThis->getDocumentDeclaredCount($a_docsrl);
	}

	function getDocumentCountByDate($a_date = '', $a_status = array())
	{
		$cmAdmModule = &getAdminModel('document');
		return $cmAdmModule->getDocumentCountByDate($a_date, array($this->module_srl), $a_status);
	}

	function getCommentList($a_docsrl, $a_page = 0, $a_lstcnt = 50)
	{
		$grant = Context::get('grant');
		$grant = $grant ? $grant->manager : 0;

		$cmThis = &getModel(__XEFM_NAME__);
		$out = $cmThis->getCommentList($a_docsrl, (int)$a_page, $grant, (int)$a_lstcnt);
		if(!$out || !$out->data){
			$out->data = $out->page_navigation = array();
			$out->total_count = $out->total_page = 0;
		}

		Context::set('cpage', (int)$out->page_navigation->cur_page);
		return $out;
	}

	function getCommentByMemberSrl($a_docsrl, $a_mbrsrl = 0)
	{
		if(!$a_mbrsrl) $a_mbrsrl = $this->member_srl;
		if(!$a_mbrsrl) return;
		$cmThis = &getModel(__XEFM_NAME__);
		$out = $cmThis->getCommentByMemberSrl($a_docsrl, $a_mbrsrl);
		return ($out&&$out->data)?$out->data:array();
	}

	function getDocumentCountByAdopt($a_ised = true, $a_mbrsrl = 0)
	{
		$cmThis = &getModel(__XEFM_NAME__);
		return $cmThis->getDocumentCountByAdopt($this->module_srl, $a_ised, $a_mbrsrl);
	}

	function getCommentCountByAdopted($a_mbrsrl)
	{
		$cmThis = &getModel(__XEFM_NAME__);
		return $cmThis->getDocumentCountByAdopt($this->module_srl, $a_mbrsrl);
	}

	function isBlind($a_consrl, $a_type = 'doc')
	{
		$cmThis = &getModel(__XEFM_NAME__);
		return $cmThis->isBlind($a_consrl, $a_type);
	}

	function isLocked($a_consrl, $a_type = 'doc')
	{
		$cmThis = &getModel(__XEFM_NAME__);
		return $cmThis->isLocked($a_consrl, $a_type);
	}

	function isWrote($a_consrl, $a_ismbr = TRUE, $a_type = 'doc')
	{
		$cmThis = &getModel(__XEFM_NAME__);
		return $cmThis->isWrote($a_consrl, $this->member_srl, $a_ismbr, $a_type);
	}

	function isRead($a_consrl, $a_ismbr = TRUE, $a_type = 'doc')
	{
		$cmThis = &getModel(__XEFM_NAME__);
		return $cmThis->isRead($a_consrl, $this->member_srl, $a_ismbr, $a_type);
	}

	function isVoted($a_consrl, $a_ismbr = TRUE, $a_type = 'doc')
	{
		$cmThis = &getModel(__XEFM_NAME__);
		return $cmThis->isVoted($a_consrl, $this->member_srl, $a_ismbr, $a_type);
	}

	function isDownloaded($a_filesrl, $a_ismbr = TRUE, $a_type = 'doc')
	{
		$cmThis = &getModel(__XEFM_NAME__);
		return $cmThis->isDownloaded($a_filesrl, $this->member_srl, $a_ismbr, $a_type);
	}

	function isScrap($a_consrl)
	{
		$cmThis = &getModel(__XEFM_NAME__);
		return $cmThis->isScrap($a_consrl, $this->member_srl);
	}
}

/* End of file classes.item.php */
/* Location: ./modules/beluxe/classes.item.php */