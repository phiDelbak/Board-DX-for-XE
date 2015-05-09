<?php
/**
 * @class  BeluxeCache
 * @author phiDel (xe.phidel@gmail.com)
 * @brief cache class of the BoardDX module
 */

class BeluxeCache
{
	/* @brief Create a cache of column config */
	function columnConfigList($a_modsrl)
	{
		// 저장된 목록 설정값을 구하고 없으면 기본 값으로 설정
		$cmModule = &getModel('module');
		$lst_cfg = (array) $cmModule->getModulePartConfig('beluxe', $a_modsrl);

		$vt_vars = array(
			'no'=>array('1','','Y','N','N'),
			'category_srl'=>array('2','','N','N','N'),
			'title'=>array('3','','Y','N','Y'),
			'nick_name'=>array('4','','Y','N','Y'),
			'user_name'=>array('5'),
			'user_id'=>array('6'),
			'email_address'=>array('7'),
			'homepage'=>array('8'),
			'ipaddress'=>array('9'),
			'voted_count'=>array('10'),
			'blamed_count'=>array('11'),
			'readed_count'=>array('12','','Y','Y','N'),
			'regdate'=>array('13','','Y','Y','Y'),
			'last_update'=>array('14'),
			'last_updater'=>array('15'),
			'custom_status'=>array('16'),
			'content'=>array('17','','N','N','Y'),
			'comment'=>array('18','','N','N','Y'),
			'thumbnail'=>array('19'),
			'tag'=>array('20')
		);

		$args->module_srl = $a_modsrl;
		$args->sort_index = 'var_idx';
		$args->order = 'asc';
		$out = executeQueryArray('document.getDocumentExtraKeys', $args);
		if($out->toBool() && count($out->data)) $vt_vars = array_merge($vt_vars, $out->data);

		foreach($vt_vars as $key => $val)
		{
			if(gettype($val) == 'object')
			{
				$kname = 'extra_vars' . $val->idx;
				$item = $lst_cfg[$kname];

				if(is_array($item))
				{
					$aSort[$kname] = (9000 - $item[0]) * -1;
					$val->color = $item[1];
					$val->display = $item[2];
					$val->sort = $item[3];
					$val->search = $item[4];
				}
				else
				{
					$aSort[$kname] = 9000 + $key;
				}

				$ext_vars[$kname] = array(
					$a_modsrl, $val->idx, $val->name, $val->type, $val->default,
					$val->desc,	$val->is_required, $val->search, $val->value,
					$val->eid, $val->color, $val->display, $val->sort
				);
			}
			else
			{
				$item = $lst_cfg[$key];

				if(is_array($item))
				{
					$aSort[$key] = (9000 - $item[0]) * -1;
					$val = $item;
				}
				else
				{
					$aSort[$key] = count($aSort) + 1;
				}

				$ext_vars[$key] = array(
					$a_modsrl, -1, $key, 'text', 'N', '\'\'',
					'N', (string) $val[4], '', $key, (string) $val[1], (string) $val[2],
					(string) $val[3]
				);
			}
		}

		array_multisort($aSort, $ext_vars);

		// Get module information (to obtain mid)
		$cmModule = &getModel('module');
		$oModIfo = $cmModule->getModuleInfoByModuleSrl($a_modsrl, array('mid', 'site_srl'));
		$mid = $oModIfo->mid;

		$cmAdmModule = &getAdminModel('module');
		$site_srl = $oModIfo->site_srl;

		$obj = array();

		foreach($ext_vars as $key=>$val)
		{
			$obj[$key] = new ExtraItem($val[0], $val[1], $val[2], $val[3], $val[4], $val[5], $val[6], $val[7], $val[8], $val[9]);
			$obj[$key]->color = $val[10];
			$obj[$key]->display = $val[11];
			$obj[$key]->sort = $val[12];
		}

		$oCacheNew = CacheHandler::getInstance('object', NULL, TRUE);
		if($oCacheNew->isSupport())
		{
			$object_key = 'object:beluxe:'.$a_modsrl;
			$cache_key = $oCacheNew->getGroupKey('site_and_module', $object_key);
			$oCacheNew->put($cache_key, $obj);
		}

		return $obj;
	}
}

/* End of file classes.cache.php */
/* Location: ./modules/beluxe/classes.cache.php */