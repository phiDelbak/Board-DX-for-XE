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
    function _getModuleInfo($a_modsrl = 0) {
        if (!$this->module_srl || $this->module_srl != $this->module_info->module_srl) {
            
            if (!$this->module_info->module_srl) {
                
                // module model 객체 생성
                $cmModule = & getModel('module');
                if ($a_modsrl) $oModIfo = $cmModule->getModuleInfoByModuleSrl($a_modsrl);
                else {
                    $mid = Context::get('mid');
                    if (!$mid) $mid = Context::get('cur_mid');
                    if (!$mid) return;
                    $site_info = Context::get('site_module_info');
                    if ($site_info) $site_srl = $site_info->site_srl;
                    $oModIfo = $cmModule->getModuleInfoByMid($mid, $site_srl);
                }
                $this->module_info = $oModIfo;
            } 
            else {
                $oModIfo = $this->module_info;
            }
            
            $this->module_srl = $oModIfo->module_srl;
        }
        
        return $this->module_info;
    }
    
    function _setDocumentItem($a_doclst, $is_exvars = true) {
        $out = array();
        
        foreach ($a_doclst as $key => $attr) {
            $doc_srl = $attr->document_srl;
            if (!$doc_srl) continue;
            
            if (!$GLOBALS['XE_DOCUMENT_LIST'][$doc_srl]) {
                $oDocNew = NULL;
                $oDocNew = new documentItem();
                $oDocNew->setAttribute($attr, FALSE);
                $GLOBALS['XE_DOCUMENT_LIST'][$doc_srl] = $oDocNew;
            }
            
            $out[$doc_srl] = $GLOBALS['XE_DOCUMENT_LIST'][$doc_srl];
        }
        
        if ($is_exvars) {
            $cmDocument = & getModel('document');
            $cmDocument->setToAllDocumentExtraVars();
            foreach ($out as $doc_srl => $tmp) {
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
            
            if (!$GLOBALS['XE_COMMENT_LIST'][$cmt_srl]) {
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
            $cmDocument = & getModel('document');
            $oDocIfo = $cmDocument->getDocument($a_docsrl, FALSE, FALSE, $t_cols);
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
            $cmComment = & getModel('comment');
            $oCmtIfo = $cmComment->getComment($a_cmtsrl, FALSE, FALSE, $t_cols);
            if ($oCmtIfo->isExists()) {
                foreach ($a_collst as $tv) $re[$tv] = $oCmtIfo->get($tv);
            }
        }
        
        return $is_obj ? $oCmtIfo : $re;
    }
    
    /**
     * fruleset 사용시 클라이언트는 지원 안해주니 어쩔수없다. 이렇게 해야지
     * modules/document/document.controller.php
     * Add javascript codes into the header by checking values of document_extra_keys type, required and others
     * @param int $module_srl
     * @return void
     */
    function _addDocumentJsFilter($module_srl) {
        global $lang;
        
        $oDocumentModel = getModel('document');
        $extra_keys = $oDocumentModel->getExtraKeys($module_srl);
        if (!count($extra_keys)) return;
        
        $js_code = array();
        $js_code[] = '<script>//<![CDATA[';
        $js_code[] = '(function($){';
        $js_code[] = 'var validator = xe.getApp("validator")[0];';
        $js_code[] = 'if(!validator) return false;';
        
        // writes error messages
        foreach ($lang->filter as $key => $val) {
            if (!$val) $val = $key;
            $val = preg_replace('@\r?\n@', '\\n', addslashes($val));
            $js_code[] = sprintf("validator.cast('ADD_MESSAGE',['%s','%s']);", $key, $val);
        }
        
        $logged_info = Context::get('logged_info');
        
        foreach ($extra_keys as $idx => $val) {
            $idx = $val->idx;
            if ($val->type == 'kr_zip') {
                $idx.= '[]';
            }
            $name = str_ireplace(array('<script', '</script'), array('<scr" + "ipt', '</scr" + "ipt'), $val->name);
            $js_code[] = sprintf('validator.cast("ADD_MESSAGE", ["extra_vars%s","%s"]);', $idx, $name);
            if ($val->is_required == 'Y') $js_code[] = sprintf('validator.cast("ADD_EXTRA_FIELD", ["extra_vars%s", { required:true }]);', $idx);
        }
        
        $js_code[] = '})(jQuery);';
        $js_code[] = '//]]></script>';
        $js_code = implode("\n", $js_code);
        
        Context::addHtmlHeader($js_code);
    }
    
    /**
     * modules/document/document.model.php
     */
    function _arrangeCategory(&$p_lst, $list, $depth) {
        if (!count($list)) return;
        
        $idx = 0;
        $list_order = array();
        $mid = Context::get('mid');
        $cate_srl = Context::get('category_srl');
        
        foreach ($list as $key => $val) {
            $obj = null;
            $obj->depth = $depth;
            $obj->mid = $val['mid'];
            $obj->module_srl = $val['module_srl'];
            $obj->category_srl = $val['category_srl'];
            $obj->parent_srl = $val['parent_srl'];
            $obj->title = $val['text'];
            $obj->color = $val['color'];
            $obj->grant = $val['grant'];
            $obj->selected = ($mid == $obj->mid && $cate_srl == $obj->category_srl);
            $obj->expand = ($obj->selected || $val['expand'] == 'Y');
            $obj->child_count = 0;
            $obj->childs = array();
            $obj->total_document_count = $obj->document_count = (int)$val['document_count'];
            
            $t_prsrl = (int)$obj->parent_srl;
            $list_order[$idx++] = $obj->category_srl;
            
            // unserialize type and description
            $desc = $val['description'];
            $desc = (strpos($desc, '|@|') !== FALSE) ? explode('|@|', $desc) : array('', '', $desc);
            $obj->description = $desc[2];
            $obj->type = $desc[0];
            $navi = explode(',', $desc[1]);
            $obj->navigation = (object)array('sort_index' => $navi[0] ? $navi[0] : $p_lst[$t_prsrl]->navigation->sort_index, 'order_type' => $navi[1] ? $navi[1] : $p_lst[$t_prsrl]->navigation->order_type, 'list_count' => (int)($navi[2] ? $navi[2] : $p_lst[$t_prsrl]->navigation->list_count), 'page_count' => (int)($navi[3] ? $navi[3] : $p_lst[$t_prsrl]->navigation->page_count), 'clist_count' => (int)($navi[4] ? $navi[4] : $p_lst[$t_prsrl]->navigation->clist_count));
            
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
            if (count($val['list'])) $this->_arrangeCategory($p_lst, $val['list'], $depth + 1);
        }
        
        $p_lst[$list_order[0]]->first = true;
        $p_lst[$list_order[count($list_order) - 1]]->last = true;
    }
    
    /**************************************************************/
    
    /*********** @public function                       ***********/
    
    /**************************************************************/
    
    /* @brief Bringing the Categories list the specific module */
    function getCategoryList($a_modsrl, $a_catesrl = 0) {
        if (!$a_modsrl) return;
        
        if (!$GLOBALS['BELUXE_CATEGORY_LIST'][$a_modsrl]) {
            $cmDocument = & getModel('document');
            $php = $cmDocument->getCategoryPhpFile($a_modsrl);
            @include ($php);
            
            $cate_list = array();
            
            // 0 번에 기본값 입력
            $oModIfo = $this->_getModuleInfo($a_modsrl);
            if ($oModIfo->module_srl) {
                $navi = explode('|@|', $oModIfo->default_type_option);
                $tmp->title = $oModIfo->default_category_title;
                if (!$tmp->title) $tmp->title = Context::getLang('category');
                $tmp->mid = $oModIfo->mid;
                $tmp->module_srl = $oModIfo->module_srl;
                $tmp->navigation = (object)array('sort_index' => $navi[0] ? $navi[0] : 'list_order', 'order_type' => $navi[1] ? $navi[1] : 'asc', 'list_count' => $navi[2] ? $navi[2] : 20, 'page_count' => $navi[3] ? $navi[3] : 10, 'clist_count' => $navi[4] ? $navi[4] : 50);
                $tmp->selected = !Context::get('category_srl');
                $tmp->expand = true;
                $cate_list[0] = $tmp;
            }
            
            $this->_arrangeCategory($cate_list, $menu->list, 0);
            
            $GLOBALS['BELUXE_CATEGORY_LIST'][$a_modsrl] = $cate_list;
        }
        
        $re = $GLOBALS['BELUXE_CATEGORY_LIST'][$a_modsrl];
        return $a_catesrl ? $re[$a_catesrl] : $re;
    }
    
    /* @brief Get a list config */
    function getColumnInfo($a_modsrl) {
        $oCacheNew = CacheHandler::getInstance('object', NULL, TRUE);
        if ($oCacheNew->isSupport()) {
            $object_key = 'object:beluxe:' . $a_modsrl;
            $cache_key = $oCacheNew->getGroupKey('site_and_module', $object_key);
            
            if ($oCacheNew->isValid($cache_key)) {
                $obj = $oCacheNew->get($cache_key);
            } 
            else {
                require_once (__XEFM_PATH__ . 'classes.cache.php');
                $obj = BeluxeCache::columnConfigList($a_modsrl);
            }
        }
        
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
    function getLatestDocumentSrl($a_modsrl = 0) {
        $oModIfo = $this->_getModuleInfo($a_modsrl);
        if (!$oModIfo->module_srl) return;
        
        $args->module_srl = $a_modsrl;
        $out = executeQuery('beluxe.getLatestDocumentSrl', $args);
        return $out->toBool() && $out->data ? (int)$out->data->document_srl : 0;
    }
    
    /* @brief Get a prev/next list */
    
    // 분류,정렬,검색 등을 고려하면 덩치가 커질거 같아...
    // 그냥 간단히 3번 돌려 해결함 (TODO 나중에 좀더 빠른 방법 연구)
    function getNavigationList($obj, $a_ectnotice = FALSE, $a_loadextra = TRUE, $a_collst = array()) {
        $oCacheNew = & CacheHandler::getInstance('object');
        if ($oCacheNew->isSupport()) {
            $option_key = md5($a_ectnotice . ',' . $a_loadextra . ',' . implode(',', $a_collst));
            $object_key = 'object:beluxe:' . $obj->current_document_srl . ':' . $option_key;
            $cache_key = $oCacheNew->getGroupKey('navigationList', $object_key);
            if ($oCacheNew->isValid($cache_key)) return $oCacheNew->get($cache_key);
        }
        
        $cmDocument = & getModel('document');
        
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
        foreach ($outtmp2 as $key => $val) {
            if ($val->document_srl == $obj->current_document_srl) break;

            
            $idx++;
        }
        
        $is_prev = $idx - $lstcnt;
        $is_prev = $is_prev > 0 ? $is_prev : 0;
        $is_next = $idx + $lstcnt + 1;
        $is_next = $is_next > ($lstcnt * 2 + 1) ? ($lstcnt * 2 + 1) : $is_next;
        
        $out->data = array_slice($outtmp2, $is_prev, $is_next);
        $out->total_count = count($out->data);
        $out->current_key = ($idx - $is_prev);
        
        //$out->total_count = $out->total_page = count($out->data);
        //$out->page_navigation = new PageHandler($out->total_count, $out->total_count, $idx - $is_prev, $out->total_count);
        //$out->page = $page;
        
        //insert in cache
        if ($oCacheNew->isSupport()) $oCacheNew->put($cache_key, $out, 300);
        
        return $out;
    }
    
    /* @brief Get a history list */
    function getHistoryList($a_docsrl, $a_page, $a_lstcnt) {
        $cmDocument = & getModel('document');
        return $cmDocument->getHistories($a_docsrl, $a_lstcnt, $a_page);
    }
    
    /* @brief Get a notice list */
    function getNoticeList($a_modsrl = 0) {
        $oModIfo = $this->_getModuleInfo($a_modsrl);
        if (!$oModIfo->module_srl) return;
        
        if ($oModIfo->notice_category == 'Y') $cate_srl = Context::get('category_srl');
        
        $cmDocument = & getModel('document');
        $args->module_srl = $oModIfo->module_srl;
        if ($cate_srl) $args->category_srl = $cate_srl;
        
        return $cmDocument->getNoticeList($args);
    }
    
    /* @brief Get a best list */
    function getBestList($a_modsrl = 0) {
        $oModIfo = $this->_getModuleInfo($a_modsrl);
        if (!$oModIfo->module_srl) return;
        
        $a_modsrl = $oModIfo->module_srl;
        $sort_index = $oModIfo->best_index;
        $list_count = (int)$oModIfo->best_count;
        $s_voted_count = (int)$oModIfo->best_voted;
        if ($oModIfo->best_category == 'Y') $cate_srl = Context::get('category_srl');
        
        $oCacheNew = & CacheHandler::getInstance('object');
        if ($oCacheNew->isSupport()) {
            $option_key = md5(implode(',', array($sort_index, $list_count, $s_voted_count, $cate_srl)));
            $object_key = 'object:beluxe:' . $a_modsrl . ':' . $option_key;
            $cache_key = $oCacheNew->getGroupKey('bestDocumentList', $object_key);
            if ($oCacheNew->isValid($cache_key)) return $oCacheNew->get($cache_key);
        }
        
        $start_date = date('YmdHis', time() - (60 * 60 * 24 * (int)$oModIfo->best_date));
        
        $args->module_srl = $a_modsrl;
        $args->list_count = $list_count;
        $args->sort_index = $sort_index;
        if ($sort_index == 'readed_count') $args->s_readed_count = $s_voted_count ? $s_voted_count : 1;
        else $args->s_voted_count = $s_voted_count ? $s_voted_count : 1;
        $args->start_date = $start_date;
        $args->order_type = 'desc';
        if ($cate_srl) $args->category_srl = $cate_srl;
        
        $out = executeQueryArray('beluxe.getBestList', $args, $a_collst);
        if (!$out->toBool() || !$out->data) return;
        
        $out->data = $this->_setDocumentItem($out->data);
        
        //insert in cache
        if ($oCacheNew->isSupport()) $oCacheNew->put($cache_key, $out, 3600);
        
        return $out;
    }
    
    /* @brief Get a best list */
    function getBestCommentList($a_docsrl) {
        $oModIfo = $this->_getModuleInfo();
        if (!$oModIfo->module_srl) return;
        
        $list_count = (int)$oModIfo->best_c_count;
        $s_voted_count = (int)$oModIfo->best_c_voted;
        
        $oCacheNew = & CacheHandler::getInstance('object');
        if ($oCacheNew->isSupport()) {
            $option_key = md5(implode(',', array($list_count, $s_voted_count)));
            $object_key = 'object:beluxe:' . $a_docsrl . ':' . $option_key;
            $cache_key = $oCacheNew->getGroupKey('bestCommentList', $object_key);
            if ($oCacheNew->isValid($cache_key)) return $oCacheNew->get($cache_key);
        }
        
        $start_date = date('YmdHis', time() - (60 * 60 * 24 * (int)$oModIfo->best_c_date));
        
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
        if ($oCacheNew->isSupport()) $oCacheNew->put($cache_key, $out, 3600);
        
        return $out;
    }
    
    function getDocumentVotedLogs($a_docsrl, $aObj = NULL) {
        if ($aObj->member_srl) $args->member_srl = $aObj->member_srl;
        if ($aObj->ipaddress) $args->ipaddress = $aObj->ipaddress;
        
        $args->document_srl = $a_docsrl;
        $args->sort_index = 'point';
        $args->order_type = 'desc';
        $outlst = executeQueryArray('beluxe.getDocumentVotedLogs', $args);
        if (!$outlst->toBool() || !$outlst->data) return;
        
        $re = array();
        foreach ($outlst->data as $key => $attr) {
            $re[$attr->member_srl ? $attr->member_srl : $attr->ipaddress] = $attr;
        }
        
        return $re;
    }
    
    function getDocumentDeclaredCount($a_docsrl) {
        $args->document_srl = $a_docsrl;
        $out = executeQuery('document.getDeclaredDocument', $args);
        return $out->toBool() && $out->data ? (int)$out->data->declared_count : 0;
    }
    
    // 댓글은 목록 수 임의 조절이 안되고, GLOBALS 변수에 저장하기 위해, 직접 가져오기로 함
    function getCommentList($a_docsrl, $a_page, $is_admin, $a_lstcnt) {
        $oDoc = $this->_getDocumentColumns($a_docsrl, array(), true);
        if (!$oDoc->isExists() || !$oDoc->getCommentCount()) return;
        if (!$oDoc->isGranted() && $oDoc->isSecret()) return;
        
        $cmComment = & getModel('comment');
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
    
    function getCommentByMemberSrl($a_docsrl, $a_mbrsrl, $a_collst = array()) {
        
        // cache controll
        $oCacheNew = & CacheHandler::getInstance('object');
        if ($oCacheNew->isSupport()) {
            $option_key = md5(implode(',', $a_collst));
            $object_key = 'object:beluxe:' . $a_docsrl . ':' . $a_mbrsrl . ':' . $option_key;
            $cache_key = $oCacheNew->getGroupKey('commentListByMember', $object_key);
            if ($oCacheNew->isValid($cache_key)) return $oCacheNew->get($cache_key);
        }
        
        $args->document_srl = $a_docsrl;
        $args->member_srl = $a_mbrsrl;
        $out = executeQueryArray('beluxe.getCommentByMemberSrl', $args, $a_collst);
        if (!$out->toBool() || !$out->data) return;
        
        $out->data = $this->_setCommentItem($out->data);
        
        //insert in cache
        if ($oCacheNew->isSupport()) $oCacheNew->put($cache_key, $out, 3600);
        
        return $out;
    }
    
    function setVotePoint($a_docsrl, $a_mbrsrl, $a_point, $a_upmode = FALSE, $a_ismbr = TRUE) {
        if (($a_ismbr && !$a_mbrsrl) || !$a_point) return new Object();
        
        // 문서번호에 해당하는 글이 있는지 확인
        $t_vals = $this->_getDocumentColumns($a_docsrl, array('voted_count', 'blamed_count'));
        if (count($t_vals) != 2) return new Object(-1, 'msg_invalid_request');
        
        $t_vcnt = $t_vals['voted_count'];
        $t_bcnt = $t_vals['blamed_count'];
        $is_Voted = $this->isVoted($a_docsrl, $a_mbrsrl, FALSE);
        
        $args->document_srl = $a_docsrl;
        $a_mbrsrl ? $args->member_srl = $a_mbrsrl : $args->ipaddress = $_SERVER['REMOTE_ADDR'];
        
        // 카운트 업데이트 모드이면 이전 값 구함
        if ($a_upmode && $is_Voted) {
            $vinfo = $this->getDocumentVotedLogs($a_docsrl, $args);
            $old_point = $vinfo[$a_mbrsrl ? $args->member_srl : $args->ipaddress]->point;
        }
        
        $args->point = $a_point;
        $out = executeQuery(($is_Voted ? 'beluxe.update' : 'document.insert') . 'DocumentVotedLog', $args);
        
        if ($a_upmode && !$out->error) {
            unset($args);
            $args->document_srl = $a_docsrl;
            
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
    
    function setCustomStatus($a_docsrl, $a_value) {
        
        // 문서번호에 해당하는 글이 있는지 확인
        $t_vals = $this->_getDocumentColumns($a_docsrl, array('module_srl'));
        if (!count($t_vals)) return new Object(-1, 'msg_invalid_request');
        
        $a_value = (int)$a_value;
        $a_value = ($a_value < 1 && $a_value > 9) ? 'N' : ((string)$a_value);
        
        $args->document_srl = $a_docsrl;
        $args->is_notice = $a_value;
        return executeQuery('beluxe.updateCustomStatus', $args);
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
        
        // 혹시 모를 오류에 대비해 값은 숫자로 제한
        for ($i = 0; $i < $c; $i = $i + 2) {
            $key = $a_acts[$i];
            if (!strlen($key)) continue;
            $val = $a_acts[$i + 1];
            $dx_exv->beluxe->action->{$key} = (int)$val;
        }
        
        $args->document_srl = $a_docsrl;
        $args->extra_vars = serialize($dx_exv);
        return executeQuery('beluxe.updateExtraField', $args);
    }
    
    function isBlind($a_docsrl, $a_type = 'doc') {
        if (!$a_docsrl) return true;
        $t = 'BELUXE_IS_BLIND';
        $x = $a_docsrl;
        
        if ($_SESSION[$t][$x]) return true;
        if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];
        
        $oModIfo = $this->_getModuleInfo();
        if (!$oModIfo->module_srl) return true;
        
        if ($a_type == 'cmt') {
            if ($oModIfo->use_c_blind != 'Y') return;
            $index = $oModIfo->blind_c_index;
            $count = (int)$oModIfo->blind_c_voted;
        } 
        else {
            if ($oModIfo->use_blind != 'Y') return;
            $index = $oModIfo->blind_index;
            $count = (int)$oModIfo->blind_voted;
        }
        
        if ($index == 'vote_down_count') {
            if ($a_type == 'cmt') $t_vals = $this->_getCommentColumns($a_docsrl, array('blamed_count'));
            else $t_vals = $this->_getDocumentColumns($a_docsrl, array('blamed_count'));
            if (!count($t_vals)) return true;
            
            $a_downcnt = $t_vals['blamed_count'];
            $is_blind = abs($a_downcnt) >= $count;
        } 
        else {
            $args->document_srl = $args->comment_srl = $a_docsrl;
            $out = executeQuery($a_type == 'cmt' ? 'comment.getDeclaredComment' : 'document.getDeclaredDocument', $args);
            $is_blind = ($out->toBool() && $out->data) ? ((int)$out->data->declared_count >= $count) : FALSE;
        }
        
        return $_SESSION[$t][$x] = $GLOBALS[$t][$x] = $is_blind;
    }
    
    function isLocked($a_docsrl, $a_type = 'doc') {
        if (!$a_docsrl) return true;
        $t = 'BELUXE_IS_LOCKED';
        $x = $a_docsrl;
        
        if ($_SESSION[$t][$x]) return true;
        if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];
        
        $oModIfo = $this->_getModuleInfo();
        if (!$oModIfo->module_srl) return true;
        
        $t_vals = $this->_getDocumentColumns($a_docsrl, array('comment_count', 'regdate'));
        if (count($t_vals) != 2) return true;
        
        $a_comcnt = $t_vals['comment_count'];
        $a_comcnt = $t_vals['regdate'];
        
        $is_lock = FALSE;
        if ($oModIfo->use_lock_document == 'Y') $is_lock = TRUE;
        else if ($oModIfo->use_lock_document == 'C') $is_lock = (int)$oModIfo->use_lock_document_option <= $a_comcnt;
        else if ($oModIfo->use_lock_document == 'T') $is_lock = (time() - ztime($a_regdate)) > ((int)$oModIfo->use_lock_document_option * 60 * 60 * 24);
        
        return $_SESSION[$t][$x] = $GLOBALS[$t][$x] = $is_lock;
    }
    
    function isRestrict($a_docsrl, $a_mbrsrl, $a_ismbr = TRUE, $a_type = 'doc') {
        if (!$a_docsrl || ($a_ismbr && !$a_mbrsrl)) return true;
        $t = 'BELUXE_NOT_RESTRICT';
        $x = $a_docsrl;
        
        if ($_SESSION[$t][$x]) return false;
        if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];
        
        $oModIfo = $this->_getModuleInfo();
        if (!$oModIfo->module_srl) return true;
        
        $_SESSION[$t][$x] = FALSE;
        if ($oModIfo->use_restrict_view == 'P') $_SESSION[$t][$x] = $this->isReaded($a_docsrl, $a_mbrsrl);
        else if ($oModIfo->use_restrict_view == 'Y') $_SESSION[$t][$x] = $this->isWrote($a_docsrl, $a_mbrsrl, TRUE, 'cmt');
        
        return $GLOBALS[$t][$x] = !$_SESSION[$t][$x];
    }
    
    function isWrote($a_docsrl, $a_mbrsrl, $a_ismbr = TRUE, $a_type = 'doc') {
        if (!$a_docsrl || ($a_ismbr && !$a_mbrsrl)) return;
        $t = 'BELUXE_IS_WROTE';
        $x = $a_docsrl;
        
        if ($_SESSION[$t][$x]) return true;
        if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];
        
        $a_mbrsrl ? $args->member_srl = $a_mbrsrl : $args->ipaddress = $_SERVER['REMOTE_ADDR'];
        $args->document_srl = $a_docsrl;
        $out = executeQuery('beluxe.getCommentCount', $args);
        $is_wrote = $out->toBool() ? (int)$out->data->count > 0 : FALSE;
        
        return $_SESSION[$t][$x] = $GLOBALS[$t][$x] = $is_wrote;
    }
    
    function isReaded($a_docsrl, $a_mbrsrl, $a_ismbr = TRUE, $a_type = 'doc') {
        if (!$a_docsrl || ($a_ismbr && !$a_mbrsrl)) return;
        $t = 'BELUXE_IS_READED';
        $x = $a_docsrl;
        
        if ($_SESSION[$t][$x]) return true;
        if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];
        
        $a_mbrsrl ? $args->member_srl = $a_mbrsrl : $args->ipaddress = $_SERVER['REMOTE_ADDR'];
        $args->document_srl = $a_docsrl;
        $out = executeQuery('beluxe.getReadedCount', $args);
        $is_readed = $out->toBool() ? (int)$out->data->count > 0 : FALSE;
        
        return $_SESSION[$t][$x] = $GLOBALS[$t][$x] = $is_readed;
    }
    
    function isVoted($a_docsrl, $a_mbrsrl, $a_ismbr = TRUE, $a_type = 'doc') {
        if (!$a_docsrl || ($a_ismbr && !$a_mbrsrl)) return;
        $t = 'BELUXE_IS_VOTED';
        $x = $a_docsrl;
        
        if ($_SESSION[$t][$x]) return true;
        if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];
        
        $a_mbrsrl ? $args->member_srl = $a_mbrsrl : $args->ipaddress = $_SERVER['REMOTE_ADDR'];
        $args->document_srl = $a_docsrl;
        $out = executeQuery('document.getDocumentVotedLogInfo', $args);
        $is_voted = $out->toBool() ? (int)$out->data->count > 0 : FALSE;
        
        return $_SESSION[$t][$x] = $GLOBALS[$t][$x] = $is_voted;
    }
    
    function isDownloaded($a_filesrl, $a_mbrsrl, $a_ismbr = TRUE, $a_type = 'doc') {
        if (!$a_filesrl || ($a_ismbr && !$a_mbrsrl)) return;
        $t = 'BELUXE_IS_DOWNLOADED';
        $x = $a_filesrl;
        
        if ($_SESSION[$t][$x]) return true;
        if (isset($GLOBALS[$t][$x])) return $GLOBALS[$t][$x];
        
        $a_mbrsrl ? $args->member_srl = $a_mbrsrl : $args->ipaddress = $_SERVER['REMOTE_ADDR'];
        $args->file_srl = $a_filesrl;
        $out = executeQuery('beluxe.getDownloadedCount', $args);
        $is_downed = $out->toBool() ? (int)$out->data->count > 0 : FALSE;
        
        return $_SESSION[$t][$x] = $GLOBALS[$t][$x] = $is_downed;
    }
    
    /**
     * @brief return module name in sitemap
     *
     */
    function triggerModuleListInSitemap(&$obj) {
        array_push($obj, 'beluxe');
    }
}

/* End of file beluxe.model.php */

/* Location: ./modules/beluxe/beluxe.model.php */
