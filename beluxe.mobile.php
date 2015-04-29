<?php
/**
 * @class  beluxeMobile
 * @author phiDel (phidel@foxb.kr)
 * @brief mobile class of the BoardDX module
 */

require_once(_XE_PATH_.'modules/beluxe/beluxe.view.php');

class beluxeMobile extends beluxeView
{

	function init()
	{
		$oSecurity = new Security();
		$oSecurity->encodeHTML('document_srl', 'comment_srl', 'vid', 'mid', 'page', 'category_srl', 'clist_count', 'search_target', 'search_keyword', 'sort_index', 'order_type', 'trackback_srl');
        
        // module_srl 체크
        if (!$this->module_srl || !$this->module_info->module_srl) {
            $this->mid = Context::get('mid');
            if ($this->mid) {
                $cmModule = & getModel('module');
                $oModIfo = $cmModule->getModuleInfoByMid($this->mid);
                if ($oModIfo) {
                    ModuleModel::syncModuleToSite($oModIfo);
                    $this->module_info = $oModIfo;
                    $this->module_srl = $oModIfo->module_srl;
                } 
                else return $this->stop('error');
            }
            else return $this->stop('error');
        }

		$oModIfo = $this->module_info;

		// 잘못된 방법을 막기 위한 초기화
		Context::set('module_srl', $this->module_srl);

		//필수 클래스 셋팅
		Context::set('oThis', new beluxeItem($this->module_srl));

		$navi = explode('|@|', $oModIfo->default_type_option);
		$oModIfo->default_sort_index = $navi[0] ? $navi[0] : 'list_order';
		$oModIfo->default_order_type = $navi[1] ? $navi[1] : 'asc';
		$oModIfo->default_list_count = $navi[2] ? $navi[2] : 20;
		$oModIfo->default_page_count = $navi[3] ? $navi[3] : 10;
		$oModIfo->default_clist_count = $navi[4] ? $navi[4] : 50;
		Context::set('module_info', $oModIfo);

		// 상담 기능 체크. 현재 게시판의 관리자이면 상담기능을 off시킴, 현재 사용자가 비로그인 사용자라면 글쓰기/댓글쓰기/목록보기/글보기 권한을 제거
		if($oModIfo->consultation == 'Y' && !Context::get('is_logged'))
		{
			$this->grant->list = $this->grant->write_document = $this->grant->write_comment = $this->grant->view = FALSE;
		}

		// 스킨 경로를 미리 template_path 라는 변수로 설정함
        if (!$oModIfo->skin || $oModIfo->skin == '/USE_DEFAULT/') $oModIfo->skin = 'default';
		$tpl_path = sprintf('%sskins/%s/', $this->module_path, $oModIfo->skin);
		if(!is_dir($tpl_path)) return $this->stop('msg_skin_does_not_exist');

		$oModIfo->mskin = 'mobile';
		$tpl_path = $tpl_path . 'mobile/';
		if(!is_dir($tpl_path)) return $this->stop('msg_skin_does_not_exist');
        
        $this->setTemplatePath($tpl_path);  
        Context::loadLang($tpl_path);       
        Context::addJsFile($this->module_path . 'tpl/js/module.'.((!__DEBUG__)?'min.':'').'js');

		// 검색 로봇 제한
		if($oModIfo->robots_meta_option)
		{
			Context::addHtmlHeader('<meta name="robots" content="' . $oModIfo->robots_meta_option . '" />');
		}
	}

	function dispBeluxeMobileCategory()
	{
		$cmThis = &getModel(__XEFM_NAME__);
		$cate_lst = $cmThis->getCategoryList($this->module_srl);
		Context::set('category_list', $cate_lst);

		$this->setTemplateFile('category.html');
	}

	function getBeluxeMobileCommentPage()
	{
		$clist_count = Context::get('clist_count');
		$doc_srl = Context::get('document_srl');
		$cmDocument = &getModel('document');
		if(!$doc_srl) return new Object(-1, "msg_invalid_request");

		$oDocIfo = $cmDocument->getDocument($doc_srl);
		if(!$oDocIfo->isExists()) return new Object(-1, "msg_invalid_request");

		Context::set('oDocument', $oDocIfo);

		$cmThis = &getModel(__XEFM_NAME__);
		$lst_cfg = $cmThis->getColumnInfo($this->module_srl);
		Context::set('column_info', $lst_cfg);

		$oTplNew = new TemplateHandler;
		$html = $oTplNew->compile($this->getTemplatePath(), "comment.html");
		$this->add("html", $html);
	}
}

/* End of file beluxe.mobile.php */
/* Location: ./modules/beluxe/beluxe.mobile.php */
