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
		$oMi = $cmModule->getModuleInfoByModuleSrl($a_modsrl, array('mid', 'site_srl'));
		$mid = $oMi->mid;

		$cmAdmModule = &getAdminModel('module');
		$site_srl = $oMi->site_srl;

		$obj = array();

		foreach($ext_vars as $key=>$val)
		{
			$obj[$key] = new ExtraItem($val[0], $val[1], $val[2], $val[3], $val[4], $val[5], $val[6], $val[7], $val[8], $val[9]);
			$obj[$key]->color = $val[10];
			$obj[$key]->display = $val[11];
			$obj[$key]->sort = $val[12];
		}

		return $obj;
	}

	/* @brief Create a cache of Categories */
	function categoryList($a_modsrl, $a_root)
	{
		/** modules/document/document.model.php **/
		function _arrangeCategory(&$p_lst, $list, $depth) {
			if (!count($list)) return;

			$idx = 0;
			$list_order = array();
			$mid = Context::get('mid');
			$cate_srl = Context::get('category_srl');

			foreach ($list as $key => $val) {
				$obj = new stdClass();
				$obj->depth = $depth;
				$obj->mid = $val['mid'];
				$obj->module_srl = $val['module_srl'];
				$obj->category_srl = $val['category_srl'];
				$obj->parent_srl = $val['parent_srl'];
				$obj->title = $val['text'];
				$obj->color = $val['color'];
				$obj->grant = $val['grant'];
				$obj->selected = ($mid == $obj->mid && $cate_srl == $obj->category_srl);
				$obj->expand = ($obj->selected || $val['expand'] === 'Y');
				$obj->child_count = 0;
				$obj->childs = array();
				$obj->total_document_count = $obj->document_count = (int)$val['document_count'];
				$p_lst[0]->total_document_count += $obj->document_count;

				$t_prsrl = (int)$obj->parent_srl;
				$list_order[$idx++] = $obj->category_srl;

				// unserialize type and description
				$desc = $val['description'];
				$desc = (strpos($desc, '|@|') !== FALSE) ? explode('|@|', $desc) : array('', '', $desc);
				$obj->description = $desc[2];
				$obj->type = $desc[0];
				$navi = explode(',', $desc[1]);
				$obj->navigation = (object)array(
					'sort_index' => $navi[0] ? $navi[0] : $p_lst[$t_prsrl]->navigation->sort_index,
					'order_type' => $navi[1] ? $navi[1] : $p_lst[$t_prsrl]->navigation->order_type,
					'list_count' => $navi[2] ? $navi[2] : $p_lst[$t_prsrl]->navigation->list_count,
					'page_count' => $navi[3] ? $navi[3] : $p_lst[$t_prsrl]->navigation->page_count,
					'clist_count' => is_numeric($navi[4]) ? $navi[4] : $p_lst[$t_prsrl]->navigation->clist_count,
					'dlist_count' => is_numeric($navi[5]) ? $navi[5] : $p_lst[$t_prsrl]->navigation->dlist_count
				);

				if ($t_prsrl) {
					$parent_srl = $obj->parent_srl;
					$doc_count = $obj->document_count;
					$expand = $obj->expand;
					$selected = $obj->selected;

					while ($parent_srl) {
						$p_lst[$parent_srl]->total_document_count+= $doc_count;
						$p_lst[$parent_srl]->childs[] = $obj->category_srl;
						$p_lst[$parent_srl]->child_count = count($p_lst[$parent_srl]->childs);
						if ($expand) $p_lst[$parent_srl]->expand = $expand;
						if ($selected) $p_lst[$parent_srl]->selected = $selected;
						$parent_srl = $p_lst[$parent_srl]->parent_srl;
					}
				}

				$p_lst[$key] = $obj;
				if (count($val['list'])) _arrangeCategory($p_lst, $val['list'], $depth + 1);
			}

			if(count($list_order)) {
				$p_lst[$list_order[0]]->first = true;
				$p_lst[$list_order[count($list_order) - 1]]->last = true;
			}
		}

		$cmDocument = &getModel('document');
		$php = $cmDocument->getCategoryPhpFile($a_modsrl);
		@include ($php);

		$cate_list = array();
		$cate_list[0] = $a_root;
		$cate_list[0]->expand = true;
		$cate_list[0]->selected = !Context::get('category_srl');
		$cate_list[0]->total_document_count = 0;

		_arrangeCategory($cate_list, $menu->list, 0);

		return $cate_list;
	}
}

/* End of file classes.cache.php */
/* Location: ./modules/beluxe/classes.cache.php */