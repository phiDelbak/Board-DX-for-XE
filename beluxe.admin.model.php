<?php
/**
 * @class  beluxeAdminModel
 * @author phiDel (phidel@foxb.kr)
 * @brief admin model class of the BoardDX module
 */

class beluxeAdminModel extends beluxe
{

	/*****************************************************************/
	/*********** @initialization						   ***********/

	function init()
	{
		// 관리자용 언어팩은 따로 읽기
		Context::loadLang($this->module_path.'lang/admin');
	}

	/*****************************************************************/

	function getBeluxeList()
	{
		$args = new stdClass();
		$args->page = Context::get('page');
		$args->s_module_category_srl = Context::get('module_category_srl');

		$search_target_list = array('mid','browser_title');
		$search_target = Context::get('search_target');
		$search_keyword = Context::get('search_keyword');
		if(!in_array($search_target, $search_target_list)) $search_target = 'browser_title';

		$args->{'s_'.$search_target} = $search_keyword;
		$out = executeQueryArray(__XEFM_NAME__ . '.getBeluxeList', $args);
		ModuleModel::syncModuleToSite($out->data);

		$modlst = $out->data;

		foreach($modlst as $mok=>$mod)
		{
			$modlst[$mok]->grants = array();

			$args->module_srl = $mod->module_srl;
			$grants = executeQueryArray('module.getModuleGrants', $args);

			if($grants->data) {
				foreach($grants->data as $val) {
					if($val->group_srl == 0) $modlst[$mok]->grants[$val->name] = 'A';
					else if($val->group_srl == -1) $modlst[$mok]->grants[$val->name] = 'M';
					else if($val->group_srl == -2) $modlst[$mok]->grants[$val->name] = 'S';
					else $modlst[$mok]->grants[$val->name] = 'G';
				}
			}
		}

		$out->data = $modlst;
		return $out;
	}

	function getCategoryList($a_modsrl)
	{
		$tree = array();
		$args->module_srl = $a_modsrl;
		$args->sort_index = 'list_order';
		$out = executeQueryArray('document.getCategoryList', $args);
		if($out->data)
		{
			// Create a tree for loop
			foreach($out->data as $key => $node)
			{
				$cate_srl = (int) $node->category_srl;
				$par_srl = (int) $node->parent_srl;

				$node->mid = $this->mid;
				$node->group_srls = preg_match('/^[0-9,]+$/', $node->group_srls) ? explode(',', $node->group_srls) : array();

				// unserialize type and description
				$desc = $node->description;
				$desc = (strpos($desc,'|@|') !== FALSE) ? explode('|@|', $desc) : array('','', $desc);
				$node->description = $desc[2];
				$node->type = $desc[0];
				$navi = explode(',', $desc[1]);
				$node->navigation = (object)array(
					'sort_index' => $navi[0] ? $navi[0] : '',
					'order_type' => $navi[1] ? $navi[1] : '',
					'list_count' => $navi[2] ? $navi[2] : '',
					'page_count' => $navi[3] ? $navi[3] : '',
					'clist_count' => $navi[4] ? $navi[4] : ''
				);
				$tree[$par_srl][$cate_srl] = $node;
			}
		}

		$out->data = $tree;
		return $out;
	}

	function getTypeList($a_skin)
	{
		$filename = sprintf('%sskins/%s/type.xml', $this->module_path, $a_skin);
		$parser = XmlParser::loadXmlFile($filename);

		// 스킨 type 기본값 입력
		$df_types = array();
		if(count($parser->type->items->item))
		{
			$a_items = $parser->type->items;
			$a_items = is_array($a_items->item) ? $a_items->item : array($a_items->item);
			foreach ($a_items as $val)
			{
				$name = preg_replace('/[^a-zA-Z_,]/', '', $val->attrs->name);
				$df_types[$name]->title = $val->value->body;
				$df_types[$name]->sort_index = $val->attrs->sort_index ? $val->attrs->sort_index : 'list_order';
				$df_types[$name]->order_type = $val->attrs->order_type == 'desc' ? 'desc' : 'asc';
				$df_types[$name]->list_count = (int) ($val->attrs->list_count ? $val->attrs->list_count : '20');
				$df_types[$name]->page_count = (int) ($val->attrs->page_count ? $val->attrs->page_count : '10');
				$df_types[$name]->clist_count = (int) ($val->attrs->clist_count ? $val->attrs->clist_count : '50');
			}
		}

		return $df_types;
	}

	function getBeluxeAdminSkinTypes()
	{
		$skin = Context::get('skin');
		if(!$skin) return new Object(-1, 'msg_invalid_request');

		$html = '';
		$types = $this->getTypeList($skin);
		foreach($types as $tk=>$tv)
		{
			$tmp = array($tv->sort_index,$tv->order_type,$tv->list_count,$tv->page_count,$tv->clist_count);
			$html .= sprintf('<option value="%s" data-option="%s">%s</option>', $tk, implode('|@|', $tmp), $tv->title);
		}

		$this->add("html", '<select data-skin="'.$skin.'">'.$html.'</select>');
	}

	function getSkinColorList($a_modsrl, $a_set = false, $is_samples = false)
	{
		$oModIfo = Context::get('module_info');
		if(!$oModIfo||$oModIfo->module_srl!=$a_modsrl)
		{
			$cmModule = &getModel('module');
			$oModIfo = $cmModule->getModuleInfoByModuleSrl($a_modsrl);
		}

		$filename = sprintf('%sskins/%s/scol.xml', $this->module_path, $oModIfo->skin);
		$parser = XmlParser::loadXmlFile($filename);
		$set_cnt = $col_cnt = 0;
		$re->samples = array();

		if((int) $a_set || $is_samples)
		{
			if(($a_set > 0 || $is_samples) && count($parser->colors->sample->value))
			{
					$a_samples = $parser->colors->sample->value;
					$a_samples = is_array($a_samples) ? $a_samples : array($a_samples);
					foreach ($a_samples as $tk=>$tv)
					{
						$re->samples[$tk]->name = $tv->attrs->name;
						$re->samples[$tk]->code = $tv->body;
					}
			}

			if($a_set == -1)
			{
				$tmp = __XEFM_DXCFG__.sprintf('%s.scol.php', $a_modsrl);
				if(file_exists($tmp)) @include($tmp);
			}
			elseif($a_set > 0)
			{
				$skin_color = array();
				$tcode = explode(';', $re->samples[$a_set - 1]->code);
				foreach($tcode as $v)
				{
					$v = trim($v);
					$skin_color[] = ($v == 'NONE' ? '' : strtoupper($v));
				}
			}

			$set_cnt = count($skin_color);
		}

		$colors = array();
		if(count($parser->colors->item))
		{
			$a_items = $parser->colors;
			$a_items = is_array($a_items->item) ? $a_items->item : array($a_items->item);

			foreach ($a_items as $key=>$val)
			{
				if(!$val->attrs->name)continue;
				$values = array();
				$colors[$key]->title = $val->attrs->name;
				$a_vals = is_array($val->value) ? $val->value : array($val->value);

				foreach ($a_vals as $k=>$col)
				{
					if(!$col->attrs->desc && !$col->attrs->name)continue;
					$values[$k]->select = $col->select->body;
					$values[$k]->desc = $col->attrs->desc;
					$values[$k]->name = $col->attrs->name;

					if($values[$k]->name){
						$cnt = substr_count($values[$k]->name, '%');
						if($set_cnt > 0) {
							$arrtmp = array();
							for ($i=0;$i<$cnt;$i++) $arrtmp[] = $skin_color[$col_cnt++];
							$values[$k]->color = implode(';', $arrtmp);
						} else {
							$values[$k]->color = $cnt > 1 ? str_repeat(';', $cnt - 1) : '';
							//$values[$k]->color = $col->attrs->color;
						}
					}

					$values[$k]->group = array();
					$a_grps = is_array($col->groups) ? $col->groups : array($col->groups);

					foreach ($a_grps as $g=>$grp)
					{
						if(!$grp->attrs->name)continue;
						$values[$k]->group[$g]->title = $grp->attrs->name;
						$values[$k]->group[$g]->items = array();
						$a_grps2 = is_array($grp->group) ? $grp->group : array($grp->group);

						// group은 같은 색값을 사용하기에 한번만 읽기위해 임시 사용
						$is_color = false;
						$tmp_color = '';

						foreach ($a_grps2 as $g2=>$grp2)
						{
							if(!$grp2->attrs->name)continue;
							$cnt2 = substr_count($grp2->attrs->name, '%');
							$values[$k]->group[$g]->items[$g2]->name = $grp2->attrs->name;
							$values[$k]->group[$g]->items[$g2]->select = $grp2->attrs->select;

							if($set_cnt > 0) {
								if(!$is_color)
								{
									$is_color = true;
									$arrtmp = array();
									for ($i=0;$i<$cnt2;$i++) $arrtmp[] = $skin_color[$col_cnt++];
									$tmp_color = implode(';', $arrtmp);
								}
								$values[$k]->group[$g]->items[$g2]->color = $tmp_color;
							} else {
								$values[$k]->group[$g]->items[$g2]->color = $cnt2 > 1 ? str_repeat(';', $cnt2 - 1) : '';
								//$values[$k]->group[$g]->items[$g2]->color = $grp->attrs->color;
							}
						}
					}
				}

				$colors[$key]->values = $values;
			}
		}

		$re->colors = $colors;
		return $re;
	}
}

/* End of file beluxe.admin.model.php */
/* Location: ./modules/beluxe/beluxe.admin.model.php */