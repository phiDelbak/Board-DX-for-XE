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
		if(!$doc_srl) return new Object(-1, 'msg_invalid_request');

		$cmDocument = &getModel('document');
		$oDocIfo = $cmDocument->getDocument($doc_srl, $this->grant->manager);
		if(!$oDocIfo->isExists()) return new Object(-1, 'msg_invalid_request');

		Context::set('oDocument', $oDocIfo);

		$cmThis = &getModel(__XEFM_NAME__);
		$lst_cfg = $cmThis->getColumnInfo($this->module_srl);
		Context::set('column_info', $lst_cfg);

		$cvThis = &getView(__XEFM_NAME__);
		$tpl_path = $cvThis->_templateFileLoad('comment');

		$oTplNew = new TemplateHandler();
		$html = $oTplNew->compile($tpl_path, 'comment.html');
		$this->add('html', $html);
	}
}

/* End of file beluxe.mobile.php */
/* Location: ./modules/beluxe/beluxe.mobile.php */