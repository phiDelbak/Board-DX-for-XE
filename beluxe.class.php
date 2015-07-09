<?php
/**
 * @class  beluxe
 * @author phiDel (xe.phidel@gmail.com)
 * @brief class of the BoardDX module
 */

define('__XEFM_NAME__', 'beluxe');
define('__XEFM_PATH__', _XE_PATH_.'modules/' . __XEFM_NAME__ . '/');
define('__XEFM_ORDER__', 'list_order,regdate,update_order,readed_count,voted_count,blamed_count,comment_count,title,random');

if (file_exists(__XEFM_PATH__ . 'classes.item.php')) require_once (__XEFM_PATH__ . 'classes.item.php');

class beluxe extends ModuleObject
{

	/* @brief Install the module */
	function moduleInstall() {
		$cmModule = &getModel('module');
		$ccModule = &getController('module');

		if (file_exists(__XEFM_PATH__ . 'schemas/file_downloaded_log.xml')) {
			if (!$cmModule->getTrigger('file.downloadFile', 'beluxe', 'controller', 'triggerBeforeDownloadFile', 'before')) $ccModule->insertTrigger('file.downloadFile', 'beluxe', 'controller', 'triggerBeforeDownloadFile', 'before');
			if (!$cmModule->getTrigger('file.downloadFile', 'beluxe', 'controller', 'triggerDownloadFile', 'after')) $ccModule->insertTrigger('file.downloadFile', 'beluxe', 'controller', 'triggerDownloadFile', 'after');
		}

		// 2011. 09. 20 when add new menu in sitemap, custom menu add
		if(!$cmModule->getTrigger('menu.getModuleListInSitemap', 'beluxe', 'model', 'triggerModuleListInSitemap', 'after')) $ccModule->insertTrigger('menu.getModuleListInSitemap', 'beluxe', 'model', 'triggerModuleListInSitemap', 'after');

		return new Object();
	}

	/* @brief Check the module */
	function checkUpdate() {
		$cmModule = &getModel('module');

		if (file_exists(__XEFM_PATH__ . 'schemas/file_downloaded_log.xml')) {
			if (!$cmModule->getTrigger('file.downloadFile', 'beluxe', 'controller', 'triggerBeforeDownloadFile', 'before')) return TRUE;
			if (!$cmModule->getTrigger('file.downloadFile', 'beluxe', 'controller', 'triggerDownloadFile', 'after')) return TRUE;
		}

		// 2011. 09. 20 when add new menu in sitemap, custom menu add
		if(!$cmModule->getTrigger('menu.getModuleListInSitemap', 'beluxe', 'model', 'triggerModuleListInSitemap', 'after')) return TRUE;

		return FALSE;
	}

	/* @brief Updaet the module */
	function moduleUpdate() {
		$this->moduleInstall();
		return new Object(0, 'success_updated');
	}

	/* @brief Uninstall the module */
	function moduleUninstall() {
		$ccModule = &getController('module');

		$ccModule->deleteTrigger('file.downloadFile', 'beluxe', 'controller', 'triggerBeforeDownloadFile', 'before');
		$ccModule->deleteTrigger('file.downloadFile', 'beluxe', 'controller', 'triggerDownloadFile', 'after');
		$ccModule->deleteTrigger('menu.getModuleListInSitemap', 'beluxe', 'model', 'triggerModuleListInSitemap', 'after');

		$this->recompileCache();
		return new Object();
	}

	/* @brief create the cache */
	function recompileCache() {
	}
}

/* End of file beluxe.class.php */
/* Location: ./modules/beluxe/beluxe.class.php */