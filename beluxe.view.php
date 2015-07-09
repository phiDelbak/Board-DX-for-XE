<?php

/**
 * @class  beluxeView
 * @author phiDel (xe.phidel@gmail.com)
 * @brief view class of the BoardDX module
 */

class beluxeView extends beluxe
{
	var $lstCfg = array();
	var $cmDoc = NULL;
	var $cmThis = NULL;
	var $oScrt = NULL;

	/**************************************************************/
	/*********** @initialization                        ***********/

	function init() {
		$this->oScrt = new Security();
		$this->oScrt->encodeHTML('mid');

		// module_srl 체크
		if (!$this->module_srl || !$this->module_info->module_srl) {
			$this->mid = Context::get('mid');
			if ($this->mid) {
				$cmModule = &getModel('module');
				$oMi = $cmModule->getModuleInfoByMid($this->mid);
				if ($oMi) {
					ModuleModel::syncModuleToSite($oMi);
					$this->module_info = $oMi;
					$this->module_srl = $oMi->module_srl;
				}
				else return $this->stop('error');
			}
			else return $this->stop('error');
		}

		$oMi = &$this->module_info;

		$navi = explode('|@|', $oMi->default_type_option);
		$oMi->default_sort_index = $navi[0] ? $navi[0] : 'list_order';
		$oMi->default_order_type = $navi[1] ? $navi[1] : 'asc';

		if(Mobile::isFromMobilePhone()) {
			if((int) $oMi->mobile_list_count) $navi[2] = $oMi->mobile_list_count;
			if((int) $oMi->mobile_page_count) $navi[3] = $oMi->mobile_page_count;
			if((int) $oMi->mobile_clist_count) $navi[4] = $oMi->mobile_clist_count;
			if((int) $oMi->mobile_dlist_count) $navi[5] = $oMi->mobile_dlist_count;
		}

		$oMi->default_list_count = (int) ($navi[2] ? $navi[2] : 20);
		$oMi->default_page_count = (int) ($navi[3] ? $navi[3] : 10);
		$oMi->default_clist_count = (int) (is_numeric($navi[4]) ? $navi[4] : 50);
		$oMi->default_dlist_count = (int) (is_numeric($navi[5]) ? $navi[5] : $oMi->default_list_count);  //값이 없을때 호환을 위해 null 체크

		if (!$oMi->skin || $oMi->skin === '/USE_DEFAULT/') {
			$oMi->skin = 'default';
			$oMi->mskin = 'default/mobile';
		}

		// 변경된 정보 저장
		$oMi->module_srl = $this->module_srl;
		$this->module_info = $oMi;
		Context::set('module_info', $oMi);

		// 팝업,프레임 레이아웃 설정
		if ((int)Context::get('is_poped') || (int)Context::get('is_modal')) {
			$this->setLayoutPath('./common/tpl');
			if ((int)Context::get('is_poped')) $this->setLayoutFile('popup_layout');
			else $this->setLayoutFile('default_layout');
		}

		Context::set('module_srl', $this->module_srl);  // 잘못된 방법을 막기 위한 초기화
		Context::set('oThis', new beluxeItem($this->module_srl));  //필수 클래스 셋팅
		Context::addJsFile($this->module_path . 'tpl/js/module.' . ((!__DEBUG__) ? 'min.' : '') . 'js');  // 공통 자바 추가

		// 검색 로봇 제한
		if ($oMi->robots_meta_option) {
			Context::addHtmlHeader('<meta name="robots" content="' . $oMi->robots_meta_option . '" />');
		}

		// 상담 기능 체크. 현재 게시판의 관리자이면 상담기능을 off시킴, 현재 사용자가 비로그인 사용자라면 글쓰기/댓글쓰기/목록보기/글보기 권한을 제거
		if ($oMi->consultation === 'Y' && !Context::get('is_logged')) {
			$this->grant->list = $this->grant->write_document = $this->grant->write_comment = $this->grant->view = FALSE;
		}
	}

	/**************************************************************/
	/*********** @private function                      ***********/

	function _templateFileLoad($a_file)
	{
		$oMi = $this->module_info ? $this->module_info : Context::get('module_info');
		$tpl_path = sprintf('%sskins/%s/%s', $this->module_path, $oMi->skin, Mobile::isFromMobilePhone()?'mobile/':'');
		if(!is_dir($tpl_path)) return $this->stop('msg_skin_does_not_exist');

		Context::loadLang($tpl_path);  // 스킨 언어팩은 따로 읽기
		$this->setTemplatePath($tpl_path);
		$this->setTemplateFile($a_file);

		return $tpl_path;
	}

	function _setValidMessage($a_err, $a_msg, $a_id, $a_type)
	{
		Context::set('XE_VALIDATOR_ERROR', $a_err);
		Context::set('XE_VALIDATOR_MESSAGE', Context::getLang($a_msg));
		Context::set('XE_VALIDATOR_MESSAGE_TYPE', $a_type ? $a_type : ($a_err<0?'error':'info'));
		Context::set('XE_VALIDATOR_ID', $a_id ? $a_id : Context::get('xe_validator_id'));
	}

	/* @brief set common info */
	function _setBeluxeCommonInfo()
	{

		// 한번만 부르려고 전역 셋팅
		$this->cmDoc = &getModel('document');
		$this->cmThis = &getModel(__XEFM_NAME__);

		// 대상 항목을 구함
		$this->lstCfg = $this->cmThis->getColumnInfo($this->module_srl);
		Context::set('column_info', $this->lstCfg);

		$this->lstCfg['temp']->iscate = $this->lstCfg['category_srl'] && $this->lstCfg['category_srl']->display == 'Y';

		// 카테고리를 사용안하면 제거
		if (!$this->lstCfg['temp']->iscate) Context::set('category_srl', '', true);
	}

	/* @brief get content list */
	function _setBeluxeContentList(&$aDoc)
	{
		$load_extvars = TRUE;
		$this->oScrt->encodeHTML('category_srl', 'sort_index', 'order_type', 'page', 'list_count', 'page_count', 'search_target', 'search_keyword');
		$args = Context::gets('category_srl', 'sort_index', 'order_type', 'page', 'list_count', 'page_count', 'search_target', 'search_keyword');

		$oMi = $this->module_info;
		$args->module_srl = $this->module_srl;
		$is_btm_cnt = (int) $oMi->default_dlist_count;
		$is_doc = $aDoc && $aDoc->isExists() ? (int)$aDoc->document_srl : 0;

		// 상담 기능시 현재 로그인 사용자 글만 나타나도록 변경
		if ($oMi->consultation === 'Y' && !$this->grant->manager) {
			$oLogIfo = Context::get('logged_info');
			$args->member_srl = $oLogIfo->member_srl;
		}

		// 분류에 navigation 이 있으면 설정
		if ($args->category_srl) {
			$ct_navi = $this->cmThis->getCategoryList($this->module_srl, $args->category_srl);
			if (count($ct_navi->navigation)) {
				$ct_navi = $ct_navi->navigation;
				if (!$args->sort_index) $args->sort_index = $ct_navi->sort_index;
				if (!$args->order_type) $args->order_type = $ct_navi->order_type;
				if (!$args->list_count) $args->list_count = (int)$ct_navi->list_count;
				if (!$args->page_count) $args->page_count = (int)$ct_navi->page_count;
				if (!$args->dlist_count) $is_btm_cnt = (int)$ct_navi->dlist_count;
			}
		}

		// 지정된 정렬값이 없다면  기본 설정 값을 이용, 확장변수면 eid 값 넣기
		if(!$this->lstCfg[$args->sort_index]){
			$args->sort_index = $oMi->default_sort_index ? $oMi->default_sort_index : 'list_order';
		}
		else if($this->lstCfg[$args->sort_index]->idx > 0) $args->sort_index = $this->lstCfg[$args->sort_index]->eid;

		if (!in_array($args->order_type, array('asc', 'desc'))) {
			$args->order_type = $oMi->default_order_type ? $oMi->default_order_type : 'asc';
		}

		// 잘못된 정렬,검색 타겟 재설정
		switch ($args->sort_index) {
			case 'no':
			case 'document_srl':
				$args->sort_index = 'list_order';
				break;
			case 'last_update':
				$args->sort_index = 'update_order';
				break;
			case 'custom_status':
				$args->sort_index = 'is_notice';
				break;
			case 'random':
				$_tmp = explode(',', __XEFM_ORDER__);
				$args->sort_index = $_tmp[rand(0, count($_tmp) - 2)];
				$_tmp = array('asc', 'desc');
				$args->order_type = $_tmp[rand(0, 1)];
				break;
		}

		//목록수, 페이지수 설정
		if (!$args->list_count) $args->list_count = $oMi->default_list_count ? $oMi->default_list_count : '20';
		if (!$args->page_count) $args->page_count = $oMi->default_page_count ? $oMi->default_page_count : '10';

		// 목록 보기 권한이 없을 경우 목록없음, 문서가 있고 하단 목록 수가 없으면 목록없음
		if (!$this->grant->list || ($is_doc && !(int) $is_btm_cnt)) {
			Context::set('document_list', array());
			Context::set('total_count', 0);
			Context::set('total_page', 1);
			Context::set('page_navigation', new PageHandler(0, 0, 1, 10));
			Context::set('page', 1);
		} else {
			// 예약 문서 확인
			if($oMi->schedule_document_register==='Y') {
				$this->cmThis->scheduleDocumentRegister($args->module_srl);
			}

			$ori_page = $nvi_page = 0;
			$is_get_srls = strpos($args->search_target, 't_comment_') === 0 || $args->search_target === 'is_adopted';

			if ($is_doc)
			{
				if(!$is_get_srls)
				{
					// 목록 수와 네비 수가 다르면 목록 페이지 값 구함, 하단 목록 수로 다시 설정
					if ($args->list_count != $is_btm_cnt)
					{
						$ori_page = $this->cmDoc->getDocumentPage($aDoc, $args);
						$args->list_count = $is_btm_cnt;
					}

					// 하단 목록 페이지가 없으면 구하고 설정
					$nvi_page = (int)Context::get('npage');
					if (!$nvi_page) $nvi_page = $this->cmDoc->getDocumentPage($aDoc, $args);
					$args->page = $nvi_page;
				}
			}

			$except_notice = $args->search_keyword ? FALSE : !$is_doc;

			if($is_get_srls)
			{
				$nvi_page = (int)Context::get('npage');
				if ($nvi_page) $args->page = $nvi_page;
				$lorder = $is_doc && !$nvi_page ? $aDoc->get('list_order') : false;

				// 댓글 검색은 내용만 지원해서 만듬...
				// 사용자 검색일땐 하단 목록 페이지도 같이 맞춰줌
				$out = ($args->search_target === 'is_adopted')
					? $this->cmThis->getDocumentSrlsByAdopt($args, $lorder)
					: $this->cmThis->getDocumentSrlsByComment($args, $lorder);

				if(count($out->data)) {
					$doc_list = $this->cmDoc->getDocuments($out->data, $this->grant->manager, $load_extvars);
					$out->data = $doc_list;
				}
			} else {
				$out = $this->cmDoc->getDocumentList($args, $except_notice, $load_extvars);
			}

			Context::set('document_list', $out->data);
			Context::set('page_navigation', $out->page_navigation);
			Context::set('total_count', $out->total_count);
			Context::set('total_page', $out->total_page);
			Context::set('npage', $is_doc ? ($nvi_page ? $nvi_page : $out->page) : '');
			Context::set('page', $ori_page ? $ori_page : $out->page);
		}

		// 다른 모듈이나 에드온에서 사용하기 위해 검색 옵션 저장
		Context::set('beluxe_list_sort_keys', $args);
	}

	/* @brief get content item */
	function _setBeluxeContentView($a_iswrite = FALSE)
	{
		$oMi = $this->module_info;
		$this->oScrt->encodeHTML('document_srl', 'category_srl');
		$doc_srl = Context::get('document_srl');

		if ($doc_srl || $a_iswrite) {

			$oLogIfo = Context::get('logged_info');
			$mbr_srl = $oLogIfo->member_srl;

			$load_extvars = TRUE;

			// 해당 콘텐츠 뷰 셋팅
			$out = $this->cmDoc->getDocument((int)$doc_srl, $this->grant->manager, $load_extvars);

			if ($out->isExists()) {
				$is_grant = $out->isGranted();
				$is_secret = $out->isSecret();

				if (!$out->isNotice()) {
					// 글 보기 권한을 체크
					$is_empty = !$this->grant->{$a_iswrite ? 'write_document' : 'view'} && !$is_grant;
					// 상담기능이 사용되면 사용자의 글인지 체크
					if (!$is_empty && $oMi->consultation === 'Y') $is_empty = !$is_grant;
					// 수정시 비회원 글 권한 체크
					if (!$is_empty && $a_iswrite && !$out->get('member_srl')) $is_empty = !$is_grant;
					// 블라인드 기능이 사용되면 블라인드 체크
					if (!$is_empty && !$this->grant->manager && $oMi->use_blind === 'Y') $is_empty = $this->cmThis->isBlind($doc_srl);
				}
				else {
					// 공지는 누구나 볼 수 있게 함
					$this->grant->view = TRUE;
					// 수정시 권한 체크
					if ($a_iswrite) $is_empty = !$is_grant;
				}

				// 권한이 없으면 빈문서
				if ($is_empty) {
					$b_title = 'msg_not_permitted';
					$out = $this->cmDoc->getDocument(0, FALSE, FALSE);
					$this->_setValidMessage(-1380, $b_title, 'not_permitted');
					$b_title = Context::getLang($b_title);
				} else {
					$is_read = true;
					// 권한이 있고 제한 기능 사용시
					if(!$is_grant && !$is_secret && $oMi->use_point_type !== 'A' && $oMi->use_restrict_view !== 'N')
					{
						$is_read = $oMi->use_restrict_view === 'Y' && $this->cmThis->isWrote($doc_srl, $mbr_srl, true, 'cmt')
								|| $oMi->use_restrict_view === 'P' && $this->cmThis->isRead($doc_srl, $mbr_srl);
						// 포인트가 0인것은 패스
						if(!$is_read) {
							$un_extra = $out->get('extra_vars');
							$un_extra = is_string($un_extra)?unserialize($un_extra):$un_extra;
							$is_read = !(int)$un_extra->beluxe->use_point;
						}

						if(!$is_read) {
							//$content = sprintf(Context::getLang('msg_restricted_view'), 100);
							$content .= $out->getSummary(100);
							$out->variables['content'] = Context::getLang('restriction').': '.$content.'...';
						}
					}

					// 조회수 증가
					if ($is_read && (!$is_secret || $is_grant)) $out->updateReadedCount();

					$b_title = $out->getTitleText();

					if ($oMi->category_trace === 'Y' && Context::get('cate_trace') !== 'N' && (!$out->isNotice() || $oMi->notice_category === 'Y')) {
						$_tmp = $this->lstCfg['temp'];
						$category_srl = Context::get('category_srl');
						$_tmp->dccate = $out->get('category_srl');
						// 넘어온 분류와 문서 분류가 다를 경우 바꿈 //공지는 제외
						if($_tmp->iscate && $_tmp->dccate != $category_srl) {
							$category_srl = $_tmp->dccate;
							Context::set('category_srl', $category_srl, true);
						}
					}
				}

				Context::addBrowserTitle($b_title);
			}else{
				Context::set('document_srl','',true);
			}
		}else{
			// 첫 목록을 대신 페이지를 보여야 할때 사용하는 옵션
			if($oMi->use_first_page === 'Y' && count(explode('&', $_SERVER['QUERY_STRING'])) === 1){
				$cont = $this->cmThis->getModuleContent($this->module_srl, Mobile::isFromMobilePhone()?'M':'P');
				Context::set('first_page', (object)array('type'=>'widget', 'content'=>$cont));
				Context::set('list_count', (int) $oMi->default_dlist_count);
			}

			$out = $this->cmDoc->getDocument(0);
		}

		// 브라우저 타이틀에 글의 제목을 추가
		Context::set('oDocument', $out);
		Context::set('cate_trace','',true);

		return $out;
	}

	/* @brief get comment item */
	function _setBeluxeCommentView($a_iswrite = FALSE) {
		$this->oScrt->encodeHTML('document_srl', 'comment_srl', 'parent_srl');

		// 목록 구현에 필요한 변수들을 가져온다
		$doc_srl = Context::get('document_srl');
		$cmt_srl = Context::get('comment_srl');
		$par_srl = Context::get('parent_srl');

		// 해당 댓글를 찾아본다
		$cmComment = &getModel('comment');
		$out = $cmComment->getComment((int)$cmt_srl, $this->grant->manager);
		if($out->isExists()) {
			// 글 보기 권한을 체크해서 권한이 없으면 빈문서
			$is_empty = !$this->grant->{$a_iswrite ? 'write_comment' : 'view'} && !$out->isGranted();
			// 수정시 비회원 글이고  권한이 없으면 빈문서
			if ($a_iswrite && !$is_empty && !$out->get('member_srl')) $is_empty = !$out->isGranted();
		}

		// 문서 번호가 없거나 권한이 없으면 빈문서
		if($is_empty || !$doc_srl) {
			$out = $cmComment->getComment(0, FALSE);
			if ($is_empty) {
				$this->_setValidMessage(-1380, 'msg_not_permitted', 'not_permitted');
			}
		}

		$par_srl = $par_srl ? $par_srl : $out->get('parent_srl');

		// 필요한 정보들 세팅
		Context::set('document_srl', $doc_srl);
		Context::set('oComment', $out);
		Context::set('oSourceComment', $cmComment->getComment((int)$par_srl, $this->grant->manager));
	}

	/**************************************************************/
	/*********** @public function                       ***********/

	function dispBoardHistory() {
		$this->_setBeluxeCommonInfo();

		$err = -1;
		$this->oScrt->encodeHTML('history_srl');
		$his_srl = (int)Context::get('history_srl');

		if ($his_srl)
		{
			$his = $this->cmDoc->getHistory($his_srl);
			if ($his && $his->document_srl){
				// 원본 문서의 권한 체크
				Context::set('document_srl', $his->document_srl);
				$doc = $this->_setBeluxeContentView();
				$is_grant = $doc->isGranted();
				$is_secret = $doc->isSecret();
				// 권한 체크
				$err = (int)Context::get('XE_VALIDATOR_ERROR');
				if (!$is_grant && ($is_secret || $err === -1380))
				{
					$err = -1380;
					$his->content = Context::getLang('msg_not_permitted');
				}else{
					$err = 0;
				}
			}
		}

		if($err < 0){
			$msg = $err === -1380 ? 'msg_not_permitted' : 'msg_invalid_request';
			$this->_setValidMessage($err, $msg, $err === -1380 ? 'not_permitted' : '');
		}

		Context::set('history_document', $his);
		$this->_templateFileLoad('history');
	}

	function dispBoardTagList() {
		if ($this->grant->list) {
			$cmTag = &getModel('tag');

			$obj->mid = $this->mid;
			$obj->list_count = Context::get('list_count');
			$obj->list_count = $obj->list_count ? $obj->list_count : 10000;
			$out = $cmTag->getTagList($obj);

			// automatically order
			if (count($out->data)) {
				$numbers = array_keys($out->data);
				shuffle($numbers);

				if (count($out->data)) {
					foreach ($numbers as $k => $v) {
						$tag_list[] = $out->data[$v];
					}
				}
			}

			Context::set('tag_list', $tag_list);
			$this->oScrt->encodeHTML('tag_list.');
		}

		$this->_templateFileLoad('doctags');
	}

	function dispBoardContentCommentList() {
		$this->_setBeluxeCommonInfo();
		$doc = $this->_setBeluxeContentView();

		$this->oScrt->encodeHTML('category_srl', 'document_srl', 'cpage','clist_count');
		$args = Context::gets('category_srl', 'document_srl', 'cpage','clist_count');

		if($doc->isExists() && $doc->document_srl) {
			$args->document_srl = $doc->document_srl;
			$cmt_cnt = $args->clist_count?$args->clist_count:$this->module_info->default_clist_count;

			// 분류에 navigation 이 있으면 설정
			if(!$args->clist_count && $args->category_srl) {
				$ct_navi = $this->cmThis->getCategoryList($this->module_srl, $args->category_srl);
				if (count($ct_navi->navigation)) {
					if($ct_navi->navigation->clist_count) $cmt_cnt = (int)$ct_navi->navigation->clist_count;
				}
			}

			$cmt_cnt = $cmt_cnt ? $cmt_cnt : 50;
			$out = $this->cmThis->getCommentList($args->document_srl, $args->cpage, $this->grant->manager, $cmt_cnt);
		}

		if(!$out){
			$out->data = $out->page_navigation = array();
			$out->total_count = $out->total_page = 0;
		}

		Context::set('comment_list', $out);
		$this->_templateFileLoad('comment');
	}

	function dispBoardContent() {
		$this->_setBeluxeCommonInfo();
		$doc = $this->_setBeluxeContentView();

		if(!(int)Context::get('is_modal')){
			$this->_setBeluxeContentList($doc);
		}

		$this->_templateFileLoad('index');
	}

	function dispBoardWrite() {
		$this->_setBeluxeCommonInfo();
		$this->_setBeluxeContentView(TRUE);
		$this->_templateFileLoad('write');
	}

	function dispBoardWriteComment() {
		$this->_setBeluxeCommonInfo();
		$this->_setBeluxeCommentView(TRUE);
		$this->_templateFileLoad('write');
	}

	function dispBoardDelete() {
		$this->dispBoardWrite();
		$this->_templateFileLoad('delete');
	}

	function dispBoardDeleteComment() {
		$this->dispBoardWriteComment();
		$this->_templateFileLoad('delete');
	}

	/**************************************************************/
}

/* End of file beluxe.view.php */
/* Location: ./modules/beluxe/beluxe.view.php */