<?php

/**
 * @class  beluxeModel
 * @author phiDel (xe.phidel@gmail.com)
 * @brief model class of the BoardDX module
 */

class beluxeModel extends beluxe
{

	/**************************************************************/
	/*********** @initialization                        ***********/

	function init() {
	}

	/**************************************************************/
	/*********** @private function                      ***********/

	/* @brief Get a module info */
	function _getModuleInfo($a_modsrl = 0)
	{
		if (!$this->module_info || !$this->module_info->module_srl) {
			// module model 객체 생성
			$cmModule = &getModel('module');
			if ($a_modsrl) $oMi = $cmModule->getModuleInfoByModuleSrl($a_modsrl);
			else {
				$mid = Context::get('mid');
				if (!$mid) $mid = Context::get('cur_mid');
				if (!$mid) return;
				$site_info = Context::get('site_module_info');
				if ($site_info) $site_srl = $site_info->site_srl;
				$oMi = $cmModule->getModuleInfoByMid($mid, $site_srl);
			}
			$this->module_info = $oMi;
			$this->module_srl = $oMi->module_srl;
		}

		return $this->module_info;
	}

	function _setDocumentItem($a_doclst, $is_exvars = true) {
		$out = array();

		foreach ($a_doclst as $key => $attr) {
			$doc_srl = $attr->document_srl;
			if (!$doc_srl) continue;

			if (!isset($GLOBALS['XE_DOCUMENT_LIST'][$doc_srl])) {
				$oDocNew = NULL;
				$oDocNew = new documentItem();
				$oDocNew->setAttribute($attr, FALSE);
				$GLOBALS['XE_DOCUMENT_LIST'][$doc_srl] = $oDocNew;
			}

			$out[$doc_srl] = $GLOBALS['XE_DOCUMENT_LIST'][$doc_srl];
		}

		if ($is_exvars) {
			$cmDocument = &getModel('document');
			$cmDocument->setToAllDocumentExtraVars();
			foreach ($out as $doc_srl => $_tmp) {
				$out[$doc_srl] = $GLOBALS['XE_DOCUMENT_LIST'][$doc_srl];
			}
		}

		return $out;
	}

	function _setCommentItem($a_cmtlst) {
		require_once (_XE_PATH_ . 'modules/comment/comment.item.php');

		$out = $acc = array();

		foreach ($a_cmtlst as $key => $attr) {
			$cmt_srl = $attr->comment_srl;
			if (!$cmt_srl) continue;

			if (!isset($GLOBALS['XE_COMMENT_LIST'][$cmt_srl])) {
				$oComNew = NULL;
				$oComNew = new commentItem();
				$oComNew->setAttribute($attr);
				if ($oComNew->isGranted()) $acc[$cmt_srl] = true;
				if ($attr->parent_srl > 0 && $acc[$attr->parent_srl] === true && $attr->is_secret == 'Y' && !$oComNew->isAccessible()) {
					$oComNew->setAccessible();
				}
				$GLOBALS['XE_COMMENT_LIST'][$cmt_srl] = $oComNew;
			}

			$out[$cmt_srl] = $GLOBALS['XE_COMMENT_LIST'][$cmt_srl];
		}

		return $out;
	}

	function _getDocumentColumns($a_docsrl, $a_collst = array(), $is_obj = false) {
		$re = array();
		if (!$a_docsrl) return $is_obj ? new Object(-1) : $re;

		$oDocIfo = $GLOBALS['XE_DOCUMENT_LIST'][$a_docsrl];

		if (isset($oDocIfo)) {
			foreach ($a_collst as $tv) {
				if (!isset($oDocIfo->variables[$tv])) continue;
				$re[$tv] = $oDocIfo->variables[$tv];
			}
		}

		if (!isset($oDocIfo) || count($a_collst) != count($re)) {
			$t_cols = $a_collst;
			$t_cols[] = 'document_srl';
			$t_cols[] = 'module_srl';
			$cmDocument = &getModel('document');
			$oDocIfo = $cmDocument->getDocument($a_docsrl, $this->grant->manager, FALSE, $t_cols);
			if ($oDocIfo->isExists()) {
				foreach ($a_collst as $tv) $re[$tv] = $oDocIfo->get($tv);
			}
		}

		return $is_obj ? $oDocIfo : $re;
	}

	function _getCommentColumns($a_cmtsrl, $a_collst = array(), $is_obj = false) {
		$re = array();
		if (!$a_cmtsrl) return $is_obj ? new Object(-1) : $re;

		$oCmtIfo = $GLOBALS['XE_COMMENT_LIST'][$a_cmtsrl];

		if (isset($oCmtIfo)) {
			foreach ($a_collst as $tv) {
				if (!isset($oCmtIfo->variables[$tv])) continue;
				$re[$tv] = $oCmtIfo->variables[$tv];
			}
		}

		if (!isset($oCmtIfo) || count($a_collst) != count($re)) {
			$t_cols = $a_collst;
			$t_cols[] = 'comment_srl';
			$t_cols[] = 'document_srl';
			$t_cols[] = 'module_srl';
			$cmComment = &getModel('comment');
			$oCmtIfo = $cmComment->getComment($a_cmtsrl, $this->grant->manager, FALSE, $t_cols);
			if ($oCmtIfo->isExists()) {
				foreach ($a_collst as $tv) $re[$tv] = $oCmtIfo->get($tv);
			}
		}

		return $is_obj ? $oCmtIfo : $re;
	}

	/**************************************************************/
	/*********** @public function                       ***********/
	/**************************************************************/

	// 직접 불러 ajax 사용해 그려 줄려고 만들었으나 역시 ruleset 작동안해서...
	// ruleset 필요없는 부분에서나 쓰려고 남겨둠...
	function getBeluxeTemplateFile()
	{
		$mid = Context::get('mid');
		$tmp_file = Context::get('template_file');
		if(!$tmp_file || !$this->module_srl) return new Object(-1, 'msg_invalid_request');

		//파일이름 순수 알파벳만 받음
		if(!preg_match("/[A-Za-z]+/i", $tmp_file)) return new Object(-1, 'msg_invalid_request');

		// 대상 항목을 구함
		$colifo = $this->getColumnInfo($this->module_srl);
		Context::set('column_info', $colifo);
		Context::set('oThis', new beluxeItem($this->module_srl));

		$cmThis = &getView(__XEFM_NAME__);
		$tpl_path = $cmThis->_templateFileLoad($tmp_file);

		$oTplNew = new TemplateHandler();
		$html = $oTplNew->compile($tpl_path, $tmp_file.'.html');

		$this->add('html', $html);
	}

	function getModuleContent($a_modsrl, $a_mode)
	{
		if (!$a_modsrl) return;

		$collst = ($a_mode==='M'?'m':'').'content';

		$args->module_srl = $a_modsrl;
		$out = executeQuery('beluxe.getModuleContent', $args, array($collst));

		return $out->toBool() ? $out->data->{$collst} : '';
	}

	/* @brief Bringing the Categories list the specific module */
	function getCategoryList($a_modsrl, $a_catesrl = 0)
	{
		if (!$a_modsrl) return;

		if (!isset($GLOBALS['BELUXE_CATEGORY_LIST'][$a_modsrl])) {

			$oCacheNew = &CacheHandler::getInstance('object');
			if ($oCacheNew->isSupport()) {
				$object_key = 'module_category_list:' . $a_modsrl;
				$cache_key = $oCacheNew->getGroupKey('site_and_module', $object_key);
				if ($oCacheNew->isValid($cache_key)) {
					$re = $oCacheNew->get($cache_key);
					$GLOBALS['BELUXE_CATEGORY_LIST'][$a_modsrl] = $re;
					return $a_catesrl ? $re[$a_catesrl] : $re;
				}
			}

			$_tmp = new stdClass();
			$oMi = $this->_getModuleInfo($a_modsrl);

			// root에 기본값 입력
			if ($oMi->module_srl) {
				$navi = explode('|@|', $oMi->default_type_option);
				$_tmp->mid = $oMi->mid;
				$_tmp->module_srl = $oMi->module_srl;
				$_tmp->title = $oMi->default_category_title ? $oMi->default_category_title : Context::getLang('category');

				if(Mobile::isFromMobilePhone()) {
					if((int) $oMi->mobile_list_count) $navi[2] = $oMi->mobile_list_count;
					if((int) $oMi->mobile_page_count) $navi[3] = $oMi->mobile_page_count;
					if((int) $oMi->mobile_clist_count) $navi[4] = $oMi->mobile_clist_count;
					if((int) $oMi->mobile_dlist_count) $navi[5] = $oMi->mobile_dlist_count;
				}

				$_tmp->navigation = (object)array(
					'sort_index' => $navi[0] ? $navi[0] : 'list_order',
					'order_type' => $navi[1] ? $navi[1] : 'asc',
					'list_count' => (int) ($navi[2] ? $navi[2] : 20),
					'page_count' => (int) ($navi[3] ? $navi[3] : 10),
					'clist_count' => (int) (is_numeric($navi[4]) ? $navi[4] : 50),
					'dlist_count' => (int) (is_numeric($navi[5]) ? $navi[5] : ($navi[2] ? $navi[2] : 20))
				);
			}

			require_once (__XEFM_PATH__ . 'classes.cache.php');
			$cate_list = BeluxeCache::categoryList($a_modsrl, $_tmp);
			$GLOBALS['BELUXE_CATEGORY_LIST'][$a_modsrl] = $cate_list;

			//insert in cache
			if ($oCacheNew->isSupport()) $oCacheNew->put($cache_key, $cate_list, 3600);
		}

		$re = $GLOBALS['BELUXE_CATEGORY_LIST'][$a_modsrl];
		return $a_catesrl ? $re[$a_catesrl] : $re;
	}

	/* @brief Get a list config */
	function getColumnInfo($a_modsrl)
	{
		$oCacheNew = CacheHandler::getInstance('object', NULL, TRUE);
		if ($oCacheNew->isSupport()) {
			$object_key = 'module_column_config:' . $a_modsrl;
			$cache_key = $oCacheNew->getGroupKey('site_and_module', $object_key);
			if($oCacheNew->isValid($cache_key)) return $oCacheNew->get($cache_key);
		}

		require_once (__XEFM_PATH__ . 'classes.cache.php');
		$obj = BeluxeCache::columnConfigList($a_modsrl);

		$obj = $obj ? $obj : array();
		foreach ($obj as $val) {
			$val->name = Context::getLang($val->name);

			// 설명은 확장변수만 변경
			if ($idx > 0) $val->desc = Context::getLang($val->desc);
		}
		//insert in cache
		if ($oCacheNew->isSupport()) $oCacheNew->put($cache_key, $obj, 3600);

		return $obj;
	}

	/* @brief Get a document_srl */
	function getLatestDocumentSrl($a_modsrl = 0)
	{
		$oMi = $this->_getModuleInfo($a_modsrl);
		if (!$oMi->module_srl) return;

		$args->module_srl = $a_modsrl;
		$out = executeQuery('beluxe.getLatestDocumentSrl', $args);
		return $out->toBool() && $out->data ? (int)$out->data->document_srl : 0;
	}

	/* @brief Get a prev/next list */
	// 분류,정렬,검색 등을 고려하면 덩치가 커질거 같아...
	// 그냥 간단히 3번 돌려 해결함 (TODO 나중에 좀더 빠른 방법 연구)
	function getNavigationList($obj, $a_ectnotice = FALSE, $a_loadextra = TRUE, $a_collst = array())
	{
		$cmDocument = &getModel('document');

		// 계산을 위해 페이지 값 구함
		$page = $obj->page;
		$lstcnt = $obj->list_count;

		$obj->page = $obj->page ? $obj->page : 1;
		$out = $cmDocument->getDocumentList($obj, $a_ectnotice, $a_loadextra, $a_collst);
		$outtmp2 = (array)$out->data;

		$is_prev = $obj->page > 1 ? ($obj->page - 1) : 0;
		$is_next = $out->page_navigation->last_page > $obj->page ? ($obj->page + 1) : 0;

		if ($is_prev) {
			$obj->page = $is_prev;
			$outtmp1 = $cmDocument->getDocumentList($obj, $a_ectnotice, $a_loadextra, $a_collst);
			if (count($outtmp1->data)) $outtmp2 = array_merge((array)$outtmp1->data, (array)$outtmp2);
		}

		if ($is_next) {
			$obj->page = $is_next;
			$outtmp3 = $cmDocument->getDocumentList($obj, $a_ectnotice, $a_loadextra, $a_collst);
			if (count($outtmp3->data)) $outtmp2 = array_merge((array)$outtmp2, (array)$outtmp3->data);
		}

		$idx = 0;
		$cur_d_srl = $obj->current_document_srl;

		foreach ($outtmp2 as $key => $val) {
			if ($val->document_srl == $cur_d_srl) break;
			$idx++;
		}

		$is_prev = $idx - $lstcnt;
		$is_prev = $is_prev > 0 ? $is_prev : 0;
		$is_next = $idx + $lstcnt + 1;
		$is_next = $is_next > ($lstcnt * 2 + 1) ? ($lstcnt * 2 + 1) : $is_next;

		$data = array_slice($outtmp2, $is_prev, $is_next);

		foreach ($data as $key => $val) {
			$data[$key]->selected = $val->document_srl == $cur_d_srl;
		}

		$out->data = $data;
		$out->total_count = count($out->data);
		$out->current_key = ($idx - $is_prev);

		return $out;
	}

	/* @brief Get a history list */
	function getHistoryList($a_docsrl, $a_page, $a_lstcnt)
	{
		$cmDocument = &getModel('document');
		return $cmDocument->getHistories($a_docsrl, $a_lstcnt, $a_page);
	}

	/* @brief Get a notice list */
	function getNoticeList($a_modsrl = 0)
	{
		$oMi = $this->_getModuleInfo($a_modsrl);
		if (!$oMi->module_srl) return;

		if ($oMi->notice_category === 'Y') $cate_srl = Context::get('category_srl');

		$cmDocument = &getModel('document');
		$args->module_srl = $oMi->module_srl;
		if ($cate_srl) $args->category_srl = $cate_srl;

		return $cmDocument->getNoticeList($args);
	}

	/* @brief Get a best list */
	function getBestList($a_modsrl = 0)
	{
		$oMi = $this->_getModuleInfo($a_modsrl);
		if (!$oMi->module_srl) return;

		$a_modsrl = $oMi->module_srl;
		$sort_index = $oMi->best_index;
		$list_count = (int)$oMi->best_count;
		$s_voted_count = (int)$oMi->best_voted;
		if ($oMi->best_category === 'Y') $cate_srl = Context::get('category_srl');

		$oCacheNew = &CacheHandler::getInstance('object');
		if ($oCacheNew->isSupport()) {
			$option_key = md5(implode(',', array($sort_index, $list_count, $s_voted_count, $cate_srl)));
			$object_key = 'module_document_list:' . $a_modsrl . ':key_' . $option_key;
			$cache_key = $oCacheNew->getGroupKey('site_and_module', $object_key);
			if ($oCacheNew->isValid($cache_key)) return $oCacheNew->get($cache_key);
		}

		$start_date = date('YmdHis', time() - (60 * 60 * 24 * (int)$oMi->best_date));

		$args->module_srl = $a_modsrl;
		$args->list_count = $list_count;
		$args->sort_index = $sort_index;
		if ($sort_index === 'readed_count') $args->s_readed_count = $s_voted_count ? $s_voted_count : 1;
		else $args->s_voted_count = $s_voted_count ? $s_voted_count : 1;
		$args->start_date = $start_date;
		$args->order_type = 'desc';
		if ($cate_srl) $args->category_srl = $cate_srl;

		$out = executeQueryArray('beluxe.getBestList', $args, $a_collst);
		if (!$out->toBool() || !$out->data) return;

		$out->data = $this->_setDocumentItem($out->data);

		//insert in cache
		if ($oCacheNew->isSupport()) $oCacheNew->put($cache_key, $out, 300);

		return $out;
	}

	/* @brief Get a best list */
	function getBestCommentList($a_docsrl)
	{
		$oMi = $this->_getModuleInfo();
		if (!$oMi->module_srl) return;

		$list_count = (int)$oMi->best_c_count;
		$s_voted_count = (int)$oMi->best_c_voted;

		$oCacheNew = &CacheHandler::getInstance('object');
		if ($oCacheNew->isSupport()) {
			$option_key = md5(implode(',', array($list_count, $s_voted_count)));
			$object_key = 'module_comment_list::document_' . $a_docsrl . ':key_' . $option_key;
			$cache_key = $oCacheNew->getGroupKey('site_and_module', $object_key);
			if ($oCacheNew->isValid($cache_key)) return $oCacheNew->get($cache_key);
		}

		if((int)$oMi->best_c_date !== -1)
		{
			$start_date = date('YmdHis', time() - (60 * 60 * 24 * (int)$oMi->best_c_date));
		}

		$args->document_srl = $a_docsrl;
		$args->list_count = $list_count;
		$args->start_date = $start_date;
		$args->s_voted_count = $s_voted_count ? $s_voted_count : 1;
		$args->sort_index = 'voted_count';
		$args->order_type = 'desc';
		$out = executeQueryArray('beluxe.getBestCommentList', $args, $a_collst);
		if (!$out->toBool() || !$out->data) return;
		$out->data = $this->_setCommentItem($out->data);

		//insert in cache
		if ($oCacheNew->isSupport()) $oCacheNew->put($cache_key, $out, 300);

		return $out;
	}

	function getDocumentVotedLogs($a_docsrl, $a_point = 0, $a_mbrsrl = 0, $a_sort = '')
	{
		if ($a_mbrsrl) {
			if(is_numeric($a_mbrsrl)) $args->member_srl = $a_mbrsrl;
			else $args->ipaddress = $a_mbrsrl;
		}

		if((int)$a_point > 0) $args->more_point = $a_point;
		if((int)$a_point < 0) $args->less_point = $a_point;

		$args->document_srl = $a_docsrl;
		$args->sort_index = $a_sort ? $a_sort : 'regdate';
		$args->order_type = 'desc';
		$outlst = executeQueryArray('beluxe.getDocumentVotedLogs', $args);
		if (!$outlst->toBool() || !$outlst->data) return;

		if ($a_mbrsrl) {
			$re = current($outlst->data);
		}else{
			$re = array();
			foreach ($outlst->data as $key => $attr) {
				$re[$attr->member_srl ? $attr->member_srl : $attr->ipaddress] = $attr;
			}
		}

		return $re;
	}

	// 댓글은 목록 수 임의 조절이 안되고, GLOBALS 변수에 저장하기 위해, 직접 가져오기로 함
	function getCommentList($a_docsrl, $a_page, $is_admin, $a_lstcnt)
	{
		$oDoc = $this->_getDocumentColumns($a_docsrl, array(), true);
		if (!$oDoc->isExists() || !$oDoc->getCommentCount()) return;
		if (!$oDoc->isGranted() && $oDoc->isSecret()) return;

		$cmComment = &getModel('comment');
		$out = $cmComment->getCommentList($a_docsrl, $a_page, $is_admin, $a_lstcnt);
		if (!$out->toBool()) return;

		//읽은게 없는데 댓글 수가 있고 페이지가 있다면 다시
		if (!count($out->data) && $a_page > 1 && $oDoc->getCommentCount()) {
			$out = $cmComment->getCommentList($a_docsrl, 0, $is_admin, $a_lstcnt);
			if (!$out->toBool()) return;
		}
		if (!count($out->data)) return;

		$out->data = $this->_setCommentItem($out->data);
		return $out;
	}

	function getCommentByMemberSrl($a_docsrl, $a_mbrsrl, $a_collst = array())
	{
		$args->document_srl = $a_docsrl;
		$args->member_srl = $a_mbrsrl;
		$out = executeQueryArray('beluxe.getCommentByMemberSrl', $args, $a_collst);
		if (!$out->toBool() || !$out->data) return;
		$out->data = $this->_setCommentItem($out->data);
		return $out;
	}

	function getDocumentSrlsByAdopt($a_obj, $a_list_order = false)
	{
		// todo old version only... // search_keyword ('true','false')
		$haystack = array('true','false','Y','N');
		if(!in_array($a_obj->search_keyword, $haystack)) return array();

		$args = new stdClass();
		$args->module_srl = $a_obj->module_srl;

		// regexp 를 지원 안하는거 같다. 어쩔... 다른 방법으로 변경...
		$args->extra_vars = 'stdClass%\"beluxe\"';

		if($a_obj->search_keyword == 'true' || $a_obj->search_keyword == 'Y')
			$args->like_vars = '\"adopt_srl\"\;';
		else
			$args->notlike_vars = '\"adopt_srl\"\;';

		$args->list_count = $a_obj->list_count ? $a_obj->list_count : 20;
		$args->page_count = $a_obj->page_count ? $a_obj->page_count : 10;

		if($a_list_order !== false)
		{
			$args->list_order = $a_list_order;
			$output = executeQuery('beluxe.getDocumentSrlsByAdoptPage', $args);
			$count = $output->data->count;
			$a_obj->page = (int)(($count-1)/$args->list_count)+1;
			unset($args->list_order);
		}

		$args->page = $a_obj->page ? $a_obj->page : 1;
		$out = executeQueryArray('beluxe.getDocumentSrlsByAdopt', $args);

		if ($out->toBool()) {
			$arr = array();
			foreach ($out->data as $value) {
				$arr[] = $value->document_srl;
			}
			$out->data = $arr;
		}

		return $out;
	}

	// 댓글 검색은 내용만 지원해서 만듬...
	function getDocumentSrlsByComment($a_obj, $a_list_order = false) {

		$s_target = substr($a_obj->search_target, 10);
		$haystack = array('member_srl','ipaddress','voted_count','blamed_count');
		if(!in_array($s_target, $haystack)) return array();

		$args = new stdClass();
		$args->module_srl = $a_obj->module_srl;
		$args->{$s_target} = $a_obj->search_keyword;
		$args->list_count = $a_obj->list_count ? $a_obj->list_count : 20;
		$args->page_count = $a_obj->page_count ? $a_obj->page_count : 10;

		if($a_list_order !== false)
		{
			$args->list_order = $a_list_order;
			$output = executeQuery('beluxe.getDocumentSrlsByCommentPage', $args);
			$count = $output->data->count;
			$a_obj->page = (int)(($count-1)/$args->list_count)+1;
			unset($args->list_order);
		}

		$args->page = $a_obj->page ? $a_obj->page : 1;
		$out = executeQueryArray('beluxe.getDocumentSrlsByComment', $args);

		if ($out->toBool()) {
			$arr = array();
			foreach ($out->data as $value) {
				$arr[] = $value->document_srl;
			}
			$out->data = $arr;
		}

		return $out;
	}

	function getDocumentVotedLogCount($a_docsrl, $a_point = 0)
	{
		if (!$a_docsrl) return 0;

		$t = 'BELUXE_DOCUMENT_VOTED_LOG_COUNT';
		$x = $a_docsrl.'_'.$a_point;
		if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];

		if((int)$a_point > 0) $args->more_point = $a_point;
		if((int)$a_point < 0) $args->less_point = $a_point;

		$args->document_srl = $a_docsrl;
		$out = executeQuery('beluxe.getDocumentVotedLogCount', $args);

		$GLOBALS[$t][$x] = $out->toBool() ? (int)$out->data->count : 0;
		return $GLOBALS[$t][$x];
	}

	function getDocumentDeclaredCount($a_docsrl)
	{
		if (!$a_docsrl) return 0;

		$t = 'BELUXE_DOCUMENT_DECLARED_COUNT';
		$x = $a_docsrl;
		if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];

		$args->document_srl = $a_docsrl;
		$out = executeQuery('document.getDeclaredDocument', $args);

		$GLOBALS[$t][$x] = $out->toBool() && $out->data ? (int)$out->data->declared_count : 0;
		return $GLOBALS[$t][$x];
	}

	function getDocumentCountByAdopt($a_modsrl, $a_ised = true, $a_mbrsrl = 0)
	{
		$t = 'BELUXE_DOCUMENT_COUNT_BY_ADOPT';
		$x = $a_modsrl.'_'.$a_ised.'_'.$a_mbrsrl;
		if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];

		$args->module_srl = $a_modsrl;

		// regexp 를 지원 안하는거 같다. 어쩔... 다른 방법으로 변경...
		$args->extra_vars = 'stdClass%\"beluxe\"';

		if($a_ised) {
			$args->like_vars = '\"adopt_srl\"\;';
			if(is_numeric($a_ised)) $args->adopt_member = '\"adopt_member\"\;i:'.$a_ised.';';
		} else {
			$args->notlike_vars = '\"adopt_srl\"\;';
		}

		if($a_mbrsrl) $args->member_srl = $a_mbrsrl;

		$out = executeQuery('beluxe.getDocumentCountByAdopt', $args);

		$GLOBALS[$t][$x] = $out->toBool() ? (int) $out->data->count : 0;
		return $GLOBALS[$t][$x];
	}

	function getCommentCount($a_docsrl, $a_parsrl = null, $a_mbrsrl = null)
	{
		$t = 'BELUXE_COMMENT_COUNT';
		$x = $a_docsrl.'_'.$a_parsrl.'_'.$a_mbrsrl;
		if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];

		$args->document_srl = $a_docsrl;
		if(is_numeric($a_parsrl)) $args->parent_srl = $a_parsrl;
		if(is_numeric($a_mbrsrl)) $args->member_srl = $a_mbrsrl;
		$out = executeQuery('beluxe.getCommentCount', $args);

		$GLOBALS[$t][$x] = $out->toBool() ? (int) $out->data->count : 0;
		return $GLOBALS[$t][$x];
	}

	function setVotePoint($a_docsrl, $a_mbrsrl, $a_point, $a_upmode = FALSE, $a_ismbr = TRUE) {
		if (($a_ismbr && !$a_mbrsrl) || !$a_point) return new Object();

		// 문서번호에 해당하는 글이 있는지 확인
		$t_vals = $this->_getDocumentColumns($a_docsrl, array('voted_count', 'blamed_count'));
		if (count($t_vals) != 2) return new Object(-1, 'msg_invalid_request');

		$is_Voted = $this->isVoted($a_docsrl, $a_mbrsrl, FALSE);

		$args->document_srl = $a_docsrl;
		$a_mbrsrl ? $args->member_srl = $a_mbrsrl : $args->ipaddress = $_SERVER['REMOTE_ADDR'];

		// 카운트 업데이트 모드이면 이전 값 구함
		if ($a_upmode && $is_Voted) {
			$vinfo = $this->getDocumentVotedLogs($a_docsrl, 0, $a_mbrsrl ? $a_mbrsrl : $_SERVER['REMOTE_ADDR']);
			$old_point = $vinfo->point;
		}

		$args->point = $a_point;
		$out = executeQuery(($is_Voted ? 'beluxe.update' : 'document.insert') . 'DocumentVotedLog', $args);

		if ($a_upmode && $out->toBool()) {
			unset($args);
			$args->document_srl = $a_docsrl;
			$t_vcnt = $t_vals['voted_count'];
			$t_bcnt = $t_vals['blamed_count'];

			// 새값 넣고 이전 값은 되돌리기
			for ($i = 0; $i < 2; $i++) {
				$pt = (int)($i ? ($old_point * -1) : $a_point);
				if (!$pt) continue;

				if ((!$i && $pt < 0) || ($i && $pt > 0)) {
					$args->blamed_count = $t_bcnt + $pt;
					executeQuery('document.updateBlamedCount', $args);
				}
				else {
					$args->voted_count = $t_vcnt + $pt;
					executeQuery('document.updateVotedCount', $args);
				}
			}
		}

		return $out;
	}

	function setCustomActions($a_docsrl, $a_acts) {

		// 문서번호에 해당하는 글이 있는지 확인
		$t_vals = $this->_getDocumentColumns($a_docsrl, array('extra_vars'));
		if (!count($t_vals)) return new Object(-1, 'msg_invalid_request');

		$dx_exv = $t_vals['extra_vars'];
		if (is_string($dx_exv)) $dx_exv = unserialize($dx_exv);

		// 최대 5개로 제한함
		$c = count($a_acts);
		if ($c > 10) return new Object(-1, 'msg_max_custom_actions');

		// 초기화
		unset($dx_exv->beluxe->action);

		// 혹시 모를 오류에 대비해 값은 255 이하의 숫자로 제한
		for ($i = 0; $i < $c; $i = $i + 2) {
			$key = trim($a_acts[$i]);
			$val = abs($a_acts[$i + 1]);
			if (!strlen($key) || $val > 255) continue;
			$dx_exv->beluxe->action->{$key} = $val;
		}

		$args->document_srl = $a_docsrl;
		$args->extra_vars = serialize($dx_exv);
		return executeQuery('beluxe.updateExtraVars', $args);
	}

	function scheduleDocumentRegister($a_modsrl) {

		$_tmp = new stdClass();
		$_tmp->module_srl = $a_modsrl;
		$_tmp->last_update = date('YmdHis');
		$_tmp2 = executeQueryArray('beluxe.getScheduleDocumentRegister', $_tmp);

		if(!$_tmp2->error && count($_tmp2->data)) {
			$_tmp = new stdClass();
			$_tmp->last_update = date('YmdHis');
			$_tmp->status = 'PUBLIC';

			foreach ($_tmp2->data as $val) {
				$_tmp->document_srl = $val->document_srl;
				$_tmp->list_order = getNextSequence() * -1;
				executeQuery('beluxe.updateScheduleDocumentRegister', $_tmp);
			}
		}
	}

	function isBlind($a_consrl, $a_type = 'doc') {
		if (!$a_consrl) return true;

		$t = 'BELUXE_IS_BLIND';
		$x = $a_type.'_'.$a_consrl;
		if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];

		$oMi = $this->_getModuleInfo();
		if (!$oMi->module_srl) return true;

		if ($a_type === 'cmt') {
			if ($oMi->use_c_blind !== 'Y') return;
			$index = $oMi->blind_c_index;
			$count = (int)$oMi->blind_c_voted;
		}
		else {
			if ($oMi->use_blind !== 'Y') return;
			$index = $oMi->blind_index;
			$count = (int)$oMi->blind_voted;
		}

		if ($index === 'vote_down_count') {
			if ($a_type === 'cmt') $t_vals = $this->_getCommentColumns($a_consrl, array('blamed_count'));
			else $t_vals = $this->_getDocumentColumns($a_consrl, array('blamed_count'));
			if (!count($t_vals)) return true;

			$a_downcnt = $t_vals['blamed_count'];
			$is_blind = abs($a_downcnt) >= $count;
		}
		else {
			$args->document_srl = $args->comment_srl = $a_consrl;
			$out = executeQuery($a_type === 'cmt' ? 'comment.getDeclaredComment' : 'document.getDeclaredDocument', $args);
			$is_blind = ($out->toBool() && $out->data) ? ((int)$out->data->declared_count >= $count) : FALSE;
		}

		return $GLOBALS[$t][$x] = $is_blind;
	}

	function isLocked($a_consrl, $a_type = 'doc') {
		if (!$a_consrl) return true;

		$t = 'BELUXE_IS_LOCKED';
		$x = $a_type.'_'.$a_consrl;
		if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];

		$oMi = $this->_getModuleInfo();
		if (!$oMi->module_srl) return true;

		$is_lock = FALSE;

		if($a_type === 'cmt') {
			$t_vals = $this->_getCommentColumns($a_consrl, array('document_srl', 'regdate'));

			$a_docsrl = (int)$t_vals['document_srl'];
			$a_regdate = $t_vals['regdate'];

			if ($oMi->use_point_type === 'A') {
				$t_vals = $this->_getDocumentColumns($a_docsrl, array('extra_vars'));
				$ex_vars = $t_vals['extra_vars'];
				$ex_vars = is_string($ex_vars) ? unserialize($ex_vars) : $ex_vars;
				if(!$ex_vars->beluxe) return true;
				$adopt_srl = (int) $ex_vars->beluxe->adopt_srl ?  $ex_vars->beluxe->adopt_srl : 0;
				$is_lock = $adopt_srl == $a_consrl;
			} else if ($oMi->use_lock_comment === 'Y') $is_lock = TRUE;
			else if ($oMi->use_lock_comment === 'C') {
				$a_comcnt = $this->getCommentCount($a_docsrl, $a_consrl);
				$is_lock = (int)$oMi->use_lock_comment_option <= $a_comcnt;
			} else if ($oMi->use_lock_comment === 'T') $is_lock = (time() - ztime($a_regdate)) > ((int)$oMi->use_lock_comment_option * 60 * 60 * 24);

		} else {
			$t_vals = $this->_getDocumentColumns($a_consrl, array('comment_count', 'regdate'));
			if (count($t_vals) !== 2) return true;

			$a_comcnt = $t_vals['comment_count'];
			$a_regdate = $t_vals['regdate'];

			if ($oMi->use_point_type === 'A') $is_lock = 0 < $a_comcnt;
			else if ($oMi->use_lock_document === 'Y') $is_lock = TRUE;
			else if ($oMi->use_lock_document === 'C') $is_lock = (int)$oMi->use_lock_document_option <= $a_comcnt;
			else if ($oMi->use_lock_document === 'T') $is_lock = (time() - ztime($a_regdate)) > ((int)$oMi->use_lock_document_option * 60 * 60 * 24);
		}

		return $GLOBALS[$t][$x] = $is_lock;
	}

	function isWrote($a_consrl, $a_mbrsrl, $a_ismbr = TRUE, $a_type = 'doc') {
		if (!$a_consrl || ($a_ismbr && !$a_mbrsrl)) return;

		$t = 'BELUXE_IS_WROTE';
		$x = $a_type.'_'.$a_consrl.'_'.$a_mbrsrl;
		if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];

		$a_mbrsrl ? $args->member_srl = $a_mbrsrl : $args->ipaddress = $_SERVER['REMOTE_ADDR'];
		$args->document_srl = $a_consrl;
		$out = executeQuery('beluxe.getCommentCount', $args);
		$is_wrote = $out->toBool() ? (int)$out->data->count > 0 : FALSE;

		return $GLOBALS[$t][$x] = $is_wrote;
	}

	function isRead($a_consrl, $a_mbrsrl, $a_ismbr = TRUE, $a_type = 'doc') {
		if (!$a_consrl || ($a_ismbr && !$a_mbrsrl)) return;

		$t = 'BELUXE_IS_READ';
		$x = $a_type.'_'.$a_consrl.'_'.$a_mbrsrl;
		if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];

		$a_mbrsrl ? $args->member_srl = $a_mbrsrl : $args->ipaddress = $_SERVER['REMOTE_ADDR'];
		$args->document_srl = $a_consrl;
		$out = executeQuery('beluxe.getReadedCount', $args);
		$is_readed = $out->toBool() ? (int)$out->data->count > 0 : FALSE;

		return $GLOBALS[$t][$x] = $is_readed;
	}

	function isVoted($a_consrl, $a_mbrsrl, $a_ismbr = TRUE, $a_type = 'doc') {
		if (!$a_consrl || ($a_ismbr && !$a_mbrsrl)) return;

		$t = 'BELUXE_IS_VOTED';
		$x = $a_type.'_'.$a_consrl.'_'.$a_mbrsrl;
		if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];

		$a_mbrsrl ? $args->member_srl = $a_mbrsrl : $args->ipaddress = $_SERVER['REMOTE_ADDR'];
		$args->document_srl = $a_consrl;
		$out = executeQuery('document.getDocumentVotedLogInfo', $args);
		$is_voted = $out->toBool() ? (int)$out->data->count > 0 : FALSE;

		return $GLOBALS[$t][$x] = $is_voted;
	}

	function isScrap($a_consrl, $a_mbrsrl) {
		if (!$a_consrl || !$a_mbrsrl) return;

		$t = 'BELUXE_IS_SCRAP';
		$x = 'doc_'.$a_consrl.'_'.$a_mbrsrl;
		if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];

		$args->document_srl = $a_consrl;
		$args->member_srl = $a_mbrsrl;
		$out = executeQuery('member.getScrapDocument', $args);

		$is_scrap = $out->toBool() ? (int)$out->data->count > 0 : FALSE;

		return $GLOBALS[$t][$x] = $is_scrap;
	}

	function isDownloaded($a_filesrl, $a_mbrsrl, $a_ismbr = TRUE, $a_type = 'doc') {
		if (!$a_filesrl || ($a_ismbr && !$a_mbrsrl)) return;

		$t = 'BELUXE_IS_DOWNLOADED';
		$x = $a_type.'_'.$a_filesrl.'_'.$a_mbrsrl;
		if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];

		$a_mbrsrl ? $args->member_srl = $a_mbrsrl : $args->ipaddress = $_SERVER['REMOTE_ADDR'];
		$args->file_srl = $a_filesrl;
		$out = executeQuery('beluxe.getDownloadedCount', $args);
		$is_downed = $out->toBool() ? (int)$out->data->count > 0 : FALSE;

		return $GLOBALS[$t][$x] = $is_downed;
	}

	/* @brief return module name in sitemap */
	function triggerModuleListInSitemap(&$obj) {
		array_push($obj, __XEFM_NAME__);
	}
}

/* End of file beluxe.model.php */
/* Location: ./modules/beluxe/beluxe.model.php */