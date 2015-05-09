<?php
/**
 * @class  beluxeMobile
 * @author phiDel (xe.phidel@gmail.com)
 * @brief mobile class of the BoardDX module
 */

require_once(__XEFM_PATH__.'beluxe.view.php');

class beluxeMobile extends beluxeView
{
	function init()
	{
		beluxeView::init();
	}

	function getBeluxeMobileCommentPage()
	{
		$doc_srl = Context::get('document_srl');
		$cmDocument = &getModel('document');
		if(!$doc_srl) return new Object(-1, "msg_invalid_request");

		$oDocIfo = $cmDocument->getDocument($doc_srl);
		if(!$oDocIfo->isExists()) return new Object(-1, "msg_invalid_request");

		Context::set('oDocument', $oDocIfo);

		$cmThis = &getModel(__XEFM_NAME__);
		$lst_cfg = $cmThis->getColumnInfo($this->module_srl);
		Context::set('column_info', $lst_cfg);

		$cvThis = &getView(__XEFM_NAME__);
		$tpl_path = $cvThis->_templateFileLoad('');

		$oTplNew = new TemplateHandler;
		$html = $oTplNew->compile($tpl_path, "comment.html");
		$this->add("html", $html);
	}

	// 1.9.8 이후 필요없어짐
	function dispBeluxeMobileCategory()
	{
		$cmThis = &getModel(__XEFM_NAME__);
		$cate_lst = $cmThis->getCategoryList($this->module_srl);
		Context::set('category_list', $cate_lst);

		$cvThis = &getView(__XEFM_NAME__);
		$tpl_path = $cvThis->_templateFileLoad('');

        $this->setTemplatePath($tpl_path);
        $this->setTemplateFile('category');
	}

}

/* End of file beluxe.mobile.php */
/* Location: ./modules/beluxe/beluxe.mobile.php */