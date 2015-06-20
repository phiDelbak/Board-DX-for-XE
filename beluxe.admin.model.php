<?php
/**
 * @class  beluxeAdminModel
 * @author phiDel (xe.phidel@gmail.com)
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

	function getCategories($a_modsrl)
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
					'clist_count' => is_numeric($navi[4]) ? $navi[4] : '',
					'dlist_count' => is_numeric($navi[5]) ? $navi[5] : ''
				);
				$tree[$par_srl][$cate_srl] = $node;
			}
		}

		$out->data = $tree;
		return $out;
	}

	function getTypeList($a_skin)
	{
		if(!$a_skin || $a_skin == '/USE_DEFAULT/') $a_skin = 'default';

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
				$df_types[$name]->dlist_count = (int) ($val->attrs->dlist_count ? $val->attrs->dlist_count : '20');
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
			$_tmp = array($tv->sort_index,$tv->order_type,$tv->list_count,$tv->page_count,$tv->clist_count,$tv->dlist_count);
			$html .= sprintf('<option value="%s" data-option="%s">%s</option>', $tk, implode('|@|', $_tmp), $tv->title);
		}

		$this->add("html", '<select data-skin="'.$skin.'">'.$html.'</select>');
	}
}

/* End of file beluxe.admin.model.php */
/* Location: ./modules/beluxe/beluxe.admin.model.php */