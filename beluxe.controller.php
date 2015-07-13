<?php
/**
 * @class  beluxeController
 * @author phiDel (xe.phidel@gmail.com)
 * @brief controller class of the BoardDX module
 */

class beluxeController extends beluxe
{
	/**************************************************************/
	/*********** @initialization						***********/

	function init()
	{
	}

	/**************************************************************/
	/*********** @private function						***********/

	// ruleset, filter 같이 사용하기 위해 필요
	// ruleset 리턴 주소 이상하게 변해서 꼭 필요함
	// is_modal == 2 이면 모달 안으로 그외 부모창으로...
	function _setLocation()
	{
		$retUrl = Context::get('success_return_url');
		//$retUrl = Context::get('error_return_url');

		if($retUrl){
			$GLOBALS['BELUXE_TEMP'] = '';
			function __beluxe__callback($mc) {
				$GLOBALS['BELUXE_TEMP'] = $mc[3];
				return $mc[1].($mc[3]==='2'?$mc[2].'1':'').$mc[4];
			}
			// 주소에 모달 is_modal 옵션이 있으면 제거 2는 예외
			$retUrl = preg_replace_callback('/(.*[\?\&])(is_modal=)([1-5])(.*)/i', "__beluxe__callback", $retUrl);
			$is_modal = (int)$GLOBALS['BELUXE_TEMP'];
		}else{
			$is_modal = (int)Context::get('is_modal');
		}

		if(!$retUrl && func_num_args()){
			$retAct = Context::get('success_return_act');
			// 보안을 위해, 3자이상 순수 알파벳만 받음
			if(!preg_match("/[A-Za-z]{3,}/i", $retAct)) $retAct = '';

			$args = array_merge(
				func_get_args(),
				array(
					'mid', Context::get('mid'), 'is_modal', $is_modal === 2 ? '1' : '',
					'act', $retAct ? $retAct : '', 'success_return_act', '', 'ruleset', ''
				)
			);
			$retUrl = Context::getUrl(count($args), $args, NULL, FALSE);

			// addons 나 기타 다른 모듈에 보낼 정보
			for($i = (int)!$args[0], $c = count($args); $i < $c; $i += 2){
				$this->add($args[$i], trim($args[$i + 1]));
			}
		}

		if(in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON', 'JS_CALLBACK'))) {
			//filte 사용시
			$this->add('url', $retUrl);
		}else{
			// ruleset 사용시 // 모달이면
			if($is_modal && $is_modal !== 2) Context::set('xeVirtualRequestMethod', 'xml');
			$this->setRedirectUrl($retUrl);
		}
	}

	function _setValidMessage($a_err, $a_msg, $a_id, $a_type, $a_retUrl)
	{
		if(in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON', 'JS_CALLBACK'))) {
			$this->setMessage($a_msg);
		}else{
			$_SESSION['XE_VALIDATOR_ERROR'] = $a_err;
			$_SESSION['XE_VALIDATOR_MESSAGE'] = Context::getLang($a_msg);
			$_SESSION['XE_VALIDATOR_MESSAGE_TYPE'] = $a_type ? $a_type : ($a_err<0?'error':'info');
			$_SESSION['XE_VALIDATOR_ID'] = $a_id ? $a_id : Context::get('xe_validator_id');

			if($a_id) Context::set('xe_validator_id', $_SESSION['XE_VALIDATOR_ID']);
			if($a_retUrl) $_SESSION['XE_VALIDATOR_RETURN_URL'] = $a_retUrl;
			if($a_err < 0) $this->setMessage($a_msg);
		}
	}

	function _getModuleInfo($a_modsrl = 0)
	{
		if(!$this->module_info || !$this->module_info->module_srl) {
			$cmThis = &getModel(__XEFM_NAME__);
			$this->module_info = $cmThis->_getModuleInfo($a_modsrl);
			$this->module_srl = $this->module_info->module_srl;
		}
		return $this->module_info;
	}

	function _setAnonymous(&$pObj, $aMbrIfo)
	{
		if($pObj->anonymous === 'Y')
		{
			$pObj->notify_message = 'N';
			$pObj->member_srl = -1 * $aMbrIfo->member_srl;
			$pObj->email_address = $pObj->homepage = $pObj->user_id = '';
			$pObj->user_name = $pObj->nick_name = 'anonymous';
			$this->module_info->admin_mail = '';
			return TRUE;
		}
		else
		{
			$pObj->member_srl = $aMbrIfo->member_srl;
			$pObj->user_name = $aMbrIfo->user_name;
			$pObj->nick_name = $aMbrIfo->nick_name;
			$pObj->email_address = $aMbrIfo->email_address;
			$pObj->homepage = $aMbrIfo->homepage;
			return FALSE;
		}
	}

	function _arrangeExtraField($a_dx_exv, &$pObj)
	{
		$is_exv = $a_dx_exv ? TRUE : FALSE;
		$dx_exv = is_string($a_dx_exv) ? unserialize($a_dx_exv) : $a_dx_exv;

		// 포인트가 0 이상이면 변경 가능 -면 이득을 취했으며 변경 불가
		if($pObj->use_point)
		{
			$dxpoint = $is_exv ? (int) $dx_exv->beluxe->use_point : 0;
			if($dxpoint > -1)
			{
				$dx_exv->beluxe->use_point = $pObj->use_point;
				$pObj->use_point = $pObj->use_point - $dxpoint;
			}
			else $pObj->use_point = 0;
		}

		$oMi = $this->_getModuleInfo($pObj->module_srl);

		if(is_string($oMi->extra_fields))
		{
			$extra_fields = unserialize($oMi->extra_fields);
			foreach($extra_fields as $key => $val)
			{
				// 수정일경우 읽기전용이면 수정불가
				if($is_exv && $val['readonly'] && isset($dx_exv->beluxe->extra->{$key}))
				{
					$extras->{$key} = $dx_exv->beluxe->extra->{$key};
					continue;
				}

				$value = isset($pObj->{'exfield_' . $key}) ? $pObj->{'exfield_' . $key} : null;
				if($value === null && $val['default'] !== null) $value = $val['default'];
				$value = substr((is_array($value) ? implode('|@|', $value) : $value), 0, 255);
				$value_len = strlen($value);

				if(!$value_len && $val['required'])
					return new Object(-1, 'The ' . $key . ' is required.');
				if($val['minlength'] && $value_len < $val['minlength'])
					return new Object(-1, 'The min length of the ' . $key . ' is ' . $val['minlength'] . '.');
				if($val['maxlength'] && $value_len > $val['maxlength'])
					return new Object(-1, 'The max length of the ' . $key . ' is ' . $val['maxlength'] . '.');
				if($val['rule'] && !preg_match($val['rule'], $value))
					return new Object(-1, 'The ' . $key . ' rules are different.');

				$extras->{$key} = $value;
			}
		}

		$dx_exv->beluxe->extra = $extras;
		$pObj->extra_vars = $dx_exv;

		return new Object();
	}

	function _sendMail($a_name, $a_mail, $a_tomails, $a_title, $a_content)
	{
		$oMailNew = new Mail();
		$oMailNew->setTitle($a_title);
		$oMailNew->setContent($a_content?$a_content:'...');
		$oMailNew->setSender($a_name?$a_name:($a_mail?$a_mail:'anonymous'), $a_mail?$a_mail:'xe@board.dx');
		$target_mail = explode(',', $a_tomails);
		foreach($target_mail as $target)
		{
			if(!$target = trim($target)) continue;
			$oMailNew->setReceiptor($target, $target);
			$oMailNew->send();
		}
	}

	/**************************************************************/
	/*********** @public function						***********/
	/**************************************************************/

	function procBeluxeUpdatePageContent()
	{
		$mod_srl = Context::get('module_srl');
		if(!$mod_srl) return new Object(-1, 'msg_invalid_request');

		$content = Context::get('content');

		$args->module_srl = $mod_srl;
		$args->{(Mobile::isFromMobilePhone()?'m':'').'content'} = $content ? $content : '';

		$out = executeQuery('beluxe.updateModuleContent', $args);
		if(!$out->toBool()) return $out;

		$msg_code = 'success_updated';

		$this->_setValidMessage(0, $msg_code, 'page_content_'.$msg_code);
		$this->_setLocation('');
	}

	function procBeluxeInsertFileLink()
	{
		// 필요한 변수 설정
		$seq = Context::get('sequence_srl');
		$tar_srl = Context::get('document_srl');
		$file_url = Context::get('filelink_url');
		$mod_srl = $this->module_srl;
		if(!$mod_srl){
			$oMi = $this->_getModuleInfo();
			$mod_srl = $oMi->module_srl;
		}

		if(!preg_match("/^(https?|ftp|file|mms):[\/]{2,3}[\w\-]+\.[\w\-]+(.*?)\/.{3,}/i", $file_url))
			return new Object(-1, Context::getLang('msg_invalid_format') . "\r\nex: http, ftp, mms, file");

		$filename = basename($file_url);
		if(!$mod_srl || !$filename) return new Object(-1, 'msg_invalid_request');

		if(strlen($filename) > 33 && strpos($filename, '?') > -1)
		{
			$rex = "/^(.{1,20}).*?(.{1,10})$/i";
			$filename = preg_replace($rex, '$1...$2', $filename);
		}

		// 업로드 권한이 없거나 정보가 없을시 종료
		if(!$_SESSION['upload_info'][$seq]->enabled) return new Object(-1, 'msg_not_permitted');

		// upload_target_srl 값이 명시되지 않았을 경우 세션정보에서 추출
		if(!$tar_srl) $tar_srl = $_SESSION['upload_info'][$seq]->upload_target_srl;

		// 세션정보에도 정의되지 않았다면 새로 생성
		if(!$tar_srl) $_SESSION['upload_info'][$seq]->upload_target_srl = $tar_srl = getNextSequence();

		// direct 파일에 해킹을 의심할 수 있는 확장자가 포함되어 있으면 바로 삭제함
		// 어차피 링크 파일이라 위험 없음...
		//if(preg_match("/\.(php|phtm|html|htm|cgi|pl|exe|jsp|asp|inc)/i",$filename)) return;

		$filename = str_replace(array('<', '>'), array('%3C', '%3E'), $filename);

		// 이미지인지 기타 파일인지 체크
		$direct = preg_match("/\.(jpe?g|gif|png|bmp|ico|wm[va]|mpe?g|avi|swf|flv|mp[1-4]|as[fx]|wav|midi?|moo?v|qt|r[am]{1,2}|m4v|mkv)$/i", $filename) ? 'Y' : 'N';

		// 사용자 정보를 구함
		$oLogIfo = Context::get('logged_info');

		// 파일 정보를 정리
		$args->file_srl = getNextSequence();
		$args->upload_target_srl = $tar_srl;
		$args->module_srl = $mod_srl;
		$args->direct_download = $direct;
		$args->source_filename = $filename;
		$args->uploaded_filename = $file_url;
		$args->download_count = 0;
		$args->file_size = 0;
		$args->comment = 'link';
		$args->member_srl = (int) $oLogIfo->member_srl;
		$args->sid = md5(rand(rand(1111111, 4444444), rand(4444445, 9999999)));

		$out = executeQuery('file.insertFile', $args);
		if(!$out->toBool()) return $out;

		$msg_code = 'success_registed';

		$this->_setValidMessage(0, $msg_code, 'filelink_'.$msg_code);
		$this->_setLocation('', 'sequence_srl', $seq, 'document_srl', $tar_srl, 'file', $args);
	}

	function procBoardVerificationPassword()
	{
		// 비밀번호와 문서 번호를 받음
		$password = Context::get('password');
		$doc_srl = Context::get('document_srl');
		$cmt_srl = Context::get('comment_srl');

		$cmMember = &getModel('member');

		// comment_srl이 있을 경우 댓글이 대상
		if($cmt_srl)
		{
			// 문서번호에 해당하는 글이 있는지 확인
			$cmComment = &getModel('comment');
			$oComIfo = $cmComment->getComment($cmt_srl, FALSE, array('comment_srl', 'document_srl', 'password'));
			if(!$oComIfo->isExists()) return new Object(-1, 'msg_not_founded');
			// 문서의 비밀번호와 입력한 비밀번호의 비교
			if(!$cmMember->isValidPassword($oComIfo->get('password'), $password)) return new Object(-1, 'msg_invalid_password');

			$doc_srl = $oComIfo->get('document_srl');
			$oComIfo->setGrant();
		}
		else
		{
			// 문서번호에 해당하는 글이 있는지 확인
			$cmDocument = &getModel('document');
			$oDocIfo = $cmDocument->getDocument($doc_srl, FALSE, FALSE, array('document_srl', 'password'));
			if(!$oDocIfo->isExists()) return new Object(-1, 'msg_not_founded');
			// 문서의 비밀번호와 입력한 비밀번호의 비교
			if(!$cmMember->isValidPassword($oDocIfo->get('password'), $password)) return new Object(-1, 'msg_invalid_password');

			$oDocIfo->setGrant();
		}

		$this->_setLocation('', 'document_srl', $doc_srl, 'comment_srl', $cmt_srl);
	}

	function procBoardInsertDocument()
	{
		$args = Context::getRequestVars();
		$mod_srl = (int) $args->module_srl;
		$doc_srl = (int) $args->document_srl;
		if(!$mod_srl || $this->module_srl != $mod_srl) return new Object(-1,'msg_invalid_request');
		if(!$this->grant->write_document) return new Object(-1, 'msg_not_permitted');

		$oLogIfo = Context::get('logged_info');
		$log_mbr_srl = $oLogIfo->member_srl;

		$oMi = $this->_getModuleInfo($mod_srl);

		// 회원이라면 닉,암호 제거, 상담 기능시 비회원 에러
		if(Context::get('is_logged'))
		{
			unset($args->nick_name);
			unset($args->password);
		}
		else if($oMi->consultation === 'Y')
		{
			return new Object(-1,'msg_invalid_request');
		}

		// 값 체크
		settype($args->title, "string");
		//if(!trim($args->title)) $args->title = cut_str(strip_tags($args->content), 20);
		if(!trim($args->title)) $args->title = 'Untitled';
		if($args->tags) $args->tags = preg_replace('/\s+/', ',', strip_tags($args->tags));

		$args->allow_comment = $args->allow_comment === 'Y' ? 'Y' : 'N';
		$args->allow_trackback = $args->allow_trackback === 'Y' ? 'Y' : 'N';
		$args->is_notice = $this->grant->manager && $args->is_notice === 'Y' ? 'Y' : 'N';

		// 관리자이면
		if($this->grant->manager)
		{
			// 사용자 상태, 공지 아니면 is_notice에 입력 TODO is_notice인 이유? 필드가 없어...
			if($oMi->custom_status && $args->is_notice !== 'Y') {
				$args->is_notice = (string) ((int) $args->custom_status < 1 && (int) $args->custom_status > 9) ? 'N' : $args->custom_status;
			}
		} else {
			// 제목 색상 변경 허용이 아니라면 게시글 색상/굵기 제거
			if($oMi->use_title_color !== 'Y') {
				unset($args->title_color);
				unset($args->title_bold);
			}

			if($oMi->allow_comment === 'Y' || $oMi->allow_comment === 'N') $args->allow_comment = $oMi->allow_comment;
			if($oMi->allow_trackback === 'Y' || $oMi->allow_trackback === 'N') $args->allow_trackback = $oMi->allow_trackback;
		}

		// 사용 상태에 없는 값이면 임시로 설정, 공지는 공개로
		$use_status = explode(',', $oMi->use_status);
		if(!in_array($args->status, $use_status)) $args->status = count($use_status) ? $use_status[0] : 'PUBLIC';
		if($args->is_notice === 'Y' && $this->grant->manager) $args->status = 'PUBLIC';

		// 포인트 사용이 아니면 포인트 값 제거
		$args->use_point = (int) $args->use_point;
		$is_use_point = $oMi->use_point_type !== 'A' && ($oMi->use_restrict_view === 'P' || $oMi->use_restrict_down === 'P');
		$is_use_point = Context::get('is_logged') && ($oMi->use_point_type === 'A' || $is_use_point);
		if(!$is_use_point) unset($args->use_point);

		// document module의 객체 생성
		$cmDocument = &getModel('document');
		$ccDocument = &getController('document');

		// 이미 존재하는 글인지 체크
		$oDocIfo = $cmDocument->getDocument($doc_srl, FALSE, FALSE);
		if($oDocIfo->isExists() && $oDocIfo->get('module_srl') != $mod_srl) return new Object(-1, 'msg_invalid_request');

		// 사용자 정의 확장 필드 최대 20개로 제한함
		$outvars = $this->_arrangeExtraField($oDocIfo->isExists() ? $oDocIfo->get('extra_vars') : NULL, $args);
		if(!$outvars->toBool()) return $outvars;
		if(count($args->extra_vars->beluxe->extra) > 20) return new Object(-1, 'msg_max_extra_fields');

		// 포인트  없으면 중단
		if($is_use_point && $args->use_point > 0)
		{
			$cmPoint = &getModel('point');
			if($cmPoint->getPoint($log_mbr_srl) < $args->use_point) return new Object(-1, 'msg_not_enough_point');
		}

		// 익명 사용중인지 체크
		$is_anonymous = $log_mbr_srl && in_array($oMi->use_anonymous, array('Y', 'S'));
		$args->anonymous = ($is_anonymous && ($oMi->use_anonymous === 'Y' || $args->anonymous === 'Y')) ? 'Y' : 'N';

		$oDB = &DB::getInstance();
		if($oDB)
		{
			$oDB->begin();

			// 이미 존재하는 경우 수정
			if($oDocIfo->isExists())
			{
				// 권한 체크
				if(!$oDocIfo->isGranted())
				{
					$oDB->rollback();
					return new Object(-1,'msg_not_permitted');
				}

				if(!$this->grant->manager && ($oMi->use_lock_document !== 'N' || $oMi->use_point_type === 'A'))
				{
					// 새로 db 읽는거 방지를 위해 값 저장
					if(!isset($GLOBALS['XE_DOCUMENT_LIST'][$doc_srl]))
					{
						$_tmp->variables = array(
							'comment_count'=>$oDocIfo->get('comment_count'),
							'regdate'=>$oDocIfo->get('regdate')
						);

						$GLOBALS['XE_DOCUMENT_LIST'][$doc_srl] = $_tmp;
					}

					$cmThis = &getModel(__XEFM_NAME__);
					$is_lock = $cmThis->isLocked($doc_srl);
					if($is_lock)
					{
						$oDB->rollback();
						return new Object(-1,'msg_is_locked_document');
					}
				}

				// 관리자 아니면 수정 불가
				if(!$this->grant->manager) $args->is_notice = $oDocIfo->get('is_notice');

				//다국어일 경우
				if($oDocIfo->get('lang_code') && ($oDocIfo->get('lang_code') != Context::getLangType()))
				{
					$lnc_args->document_srl = $doc_srl;
					$lnc_args->notin_lang_code = Context::getLangType();
					$lnc_out = executeQueryArray('beluxe.getDocumentLangCode',$lnc_args);
					if($lnc_out->toBool())
					{
						$args->extra_vars->beluxe->langs = array($oDocIfo->get('lang_code'));
						foreach($lnc_out->data as $vlnc) $args->extra_vars->beluxe->langs[] = $vlnc->lang_code;
						$args->extra_vars->beluxe->langs[] = $lnc_args->notin_lang_code;
					}
				}

				$out = $ccDocument->updateDocument($oDocIfo, $args);

				// 익명 사용시 멥버 정보만 따로 업데이트
				if($out->toBool() && $log_mbr_srl && $is_anonymous)
				{
					$cmMember = &getModel('member');
					$oMbrIfo = $cmMember->getMemberInfoByMemberSrl($oDocIfo->get('member_srl'));
					$this->_setAnonymous($args, $oMbrIfo);
					executeQuery('beluxe.updateDocumentMemberInfo', $args);
				}

				$is_upCateCnt = $oDocIfo->get('category_srl') != $args->category_srl;
				$msg_code = 'success_updated';
				$page = Context::get('page');

			} else {
			// 그렇지 않으면 신규 등록

				//text_editor 옵션이 있으면 변경
				if($args->text_editor === 'Y') {
					if($args->use_html !== 'Y') $args->content = htmlspecialchars($args->content);
					$args->content = nl2br($args->content);
				}

				//익명 사용시
				$is_anonymous = $args->anonymous === 'Y';
				if($is_anonymous) $this->_setAnonymous($args, $oLogIfo);

				// 예약 등록
				if($oMi->schedule_document_register==='Y' && $args->schedule_regdate) {
					if(!preg_match("/[0-9]{8,14}/i", $args->schedule_regdate)){
						$oDB->rollback();
						return new Object(-1,'invalid_schedule_regdate');
					}

					$args->status = 'TEMP';
					$args->readed_count = '-1'; // 조회수 -로 임시 목록에서 예약 구분
					$args->last_updater = '/BELUXE_SCHEDULE_DOCUMENT_REGISTER/';
					$args->last_update = $args->schedule_regdate;
					$_tmp = strlen($args->last_update);
					if($_tmp < 14) $args->last_update .= str_repeat('0', 14 - $_tmp);
				}

				// 신규에 srl 이 있으면 첨부 파일이 들어있는 경우
				$out = $ccDocument->insertDocument($args, $is_anonymous);
				$doc_srl = $out->get('document_srl');

				// 관리자 메일이 등록되어 있으면 메일 발송
				if($out->toBool() && $oMi->admin_mail)
				{
					$_tmp = getFullUrl('','document_srl',$doc_srl);
					$this->_sendMail(
						$is_anonymous ? '' : ($args->nick_name ? $args->nick_name : $oLogIfo->nick_name),
						$is_anonymous ? '' : ($args->email_address ? $args->email_address : $oLogIfo->email_address),
						$oMi->admin_mail,
						$args->title,
						sprintf("From : <a href=\"%s\">%s</a><br/>\r\n%s", $_tmp, $_tmp, $args->content)
					);
				}

				$is_upCateCnt = TRUE;
				$msg_code = 'success_registed';
			}

			// 오류 발생시 멈춤
			if(!$out->toBool())
			{
				$oDB->rollback();
				return $out;
			}

			$doc_srl = $out->get('document_srl');

			// 포인트 사용이면 빼기
			if($is_use_point && $args->use_point)
			{
				$ccPoint = &getController('point');
				$ccPoint->setPoint($log_mbr_srl, $args->use_point, 'minus');
			}

			$oDB->commit();
		}
		else return new Object(-1,'msg_dbconnect_failed');

		/* //문서 모듈에서 실행하기에 할 필요 없음
		if($is_upCateCnt) $ccDocument->makeCategoryFile($mod_srl, false);
		*/

		$this->_setValidMessage(0, $msg_code, 'document_'.$msg_code);
		$this->_setLocation('', 'document_srl', $doc_srl);
	}

	function procBoardInsertComment()
	{
		$args = Context::getRequestVars();
		$mod_srl = (int) $args->module_srl;
		$doc_srl = (int) $args->document_srl;
		$cmt_srl = (int) $args->comment_srl;
		if(!$doc_srl || !$mod_srl || $this->module_srl != $mod_srl) return new Object(-1, 'msg_invalid_request');
		if(!$this->grant->write_comment) return new Object(-1, 'msg_not_permitted');

		$oLogIfo = Context::get('logged_info');
		$log_mbr_srl = (int) $oLogIfo->member_srl;
		$cpage = $args->cpage;

		$oMi = $this->_getModuleInfo($mod_srl);

		// 회원이라면 닉,암호 제거, 상담기능 사용시 비회원 에러
		if(Context::get('is_logged'))
		{
			unset($args->nick_name);
			unset($args->password);
		}
		else if($oMi->consultation === 'Y')
		{
			return new Object(-1,'msg_invalid_request');
		}

		// 사용 상태에 없는 값이면 비밀로 설정
		$use_status = explode(',', $oMi->use_c_status);
		if(!in_array($args->status, $use_status)) $args->status = count($use_status) ? $use_status[0] : 'PUBLIC';
		$args->is_secret = $args->status === 'SECRET' ? 'Y' : 'N';
		unset($args->status);

		// 원글이 존재하는지 체크
		$colLst = array('document_srl','module_srl','member_srl','title','allow_trackback','notify_message','status','comment_status');
		$cmDocument = &getModel('document');
		$oDocIfo = $cmDocument->getDocument($doc_srl, FALSE, FALSE, $colLst);
		if(!$oDocIfo->isExists() || $oDocIfo->get('module_srl') != $mod_srl) return new Object(-1,'msg_invalid_request');

		// comment 모듈의  객체 생성
		$cmThis = &getModel(__XEFM_NAME__);
		$cmComment = &getModel('comment');
		$ccComment = &getController('comment');
		$colLst = array('comment_srl','module_srl','parent_srl','member_srl','notify_message');

		// 이미 존재하는 글인지 체크
		$oComIfo = $cmComment->getComment($cmt_srl, FALSE, $colLst);
		if($oComIfo->isExists() && $oComIfo->get('module_srl') != $mod_srl) return new Object(-1, 'msg_invalid_request');

		// parent_srl이 있으면 체크
		if($args->parent_srl)
		{
			$oParComIfo = $cmComment->getComment($args->parent_srl, FALSE , array('comment_srl'));
			if(!$oParComIfo->comment_srl) return new Object(-1, 'msg_invalid_request');
			$args->parent_srl = $oParComIfo->comment_srl;
		}
		else unset($args->parent_srl);

		// 자신의 글에 자신의 댓글 막기 사용중이면...
		if($oMi->use_lock_owner_comment === 'Y' && !$args->parent_srl)
		{
			//$is_ipaddress = !$is_mbr_srl && ($_SERVER['REMOTE_ADDR'] == $oDocIfo->get('ipaddress'));
			if(!$log_mbr_srl || ($log_mbr_srl == (int) $oDocIfo->get('member_srl'))) {
				//return new Object(-1, 'msg_is_not_write_comment');
			}
		}

		// 신규 문서일때 댓글 수 제한이 있으면...
		$lok_cmt_cnt = (int) $oMi->use_lock_comment_count;
		if(!$oComIfo->isExists() && $lok_cmt_cnt > 0 && !$args->parent_srl)
		{
			if(!$log_mbr_srl || ($lok_cmt_cnt < $cmThis->getCommentCount($doc_srl, 0, $log_mbr_srl))) {
				return new Object(-1, 'msg_is_not_write_comment');
			}
		}

		// 익명 사용중인지 체크
		$is_anonymous = $log_mbr_srl && in_array($oMi->use_anonymous, array('Y','S'));
		$args->anonymous = ($is_anonymous && ($oMi->use_anonymous === 'Y' || $args->anonymous === 'Y')) ? 'Y' : 'N';

		unset($vote_point);
		$chk_vt_point = $oMi->use_vote_point_check === 'Y';
		$upd_vt_count = $oMi->use_update_vote_count !== 'N';

		$oDB = &DB::getInstance();
		if($oDB)
		{
			$oDB->begin();

			if(!$args->parent_srl && (int) $args->vote_point)
			{
				// 포인트 체크 사용중일때 포인트가 없으면 중단
				if($chk_vt_point)
				{
					if(!$log_mbr_srl) return new Object(-1, 'msg_invalid_request');

					$cmPoint = &getModel('point');
					if($cmPoint->getPoint($log_mbr_srl) < $args->vote_point) {
						return new Object(-1, 'msg_not_enough_point');
					}
				}

				if((int) $args->vote_point && !$cmThis->isVoted($doc_srl, $log_mbr_srl, FALSE))
				{
					$vote_point = (int) $args->vote_point;

					// 포인트 체크 사용중일땐 1 이상도 받음
					if(!$chk_vt_point)
					{
						if(strpos($oMi->use_vote_point_range, ':') !== false) {
							$range = explode(':', $oMi->use_vote_point_range);
							if($vote_point < (int) $range[0] || $vote_point > (int) $range[1])
							{
								$oDB->rollback();
								return new Object(-1, 'msg_out_of_range');
							}
						}
						else $vote_point = $vote_point < 0 ? -1 : 1;
					}

					$vpout = $cmThis->setVotePoint($doc_srl, $log_mbr_srl, $vote_point, $upd_vt_count, FALSE);
					if(!$vpout->toBool())
					{
						$oDB->rollback();
						return $vpout;
					}

					// 포인트 체크 사용중일때 포인트 감소를 위해...
					$vote_point = $chk_vt_point ? $vote_point : 0;
				}
			}

			// comment 있으면 수정으로
			if($oComIfo->isExists())
			{
				// 다시 권한체크
				if(!$oComIfo->isGranted())
				{
					$oDB->rollback();
					return new Object(-1,'msg_not_permitted');
				}

				if(!$this->grant->manager && ($oMi->use_lock_comment !== 'N' || $oMi->use_point_type === 'A'))
				{
					$cmThis = &getModel(__XEFM_NAME__);
					$is_lock = $cmThis->isLocked($cmt_srl, 'cmt');
					if($is_lock) return new Object(-1,'msg_is_locked_comment');
				}

				$args->parent_srl = $oComIfo->parent_srl;
				$out = $ccComment->updateComment($args, $this->grant->manager);

				// 익명 사용시 멥버 정보만 따로 업데이트
				if($out->toBool() && $log_mbr_srl && $is_anonymous)
				{
					$cmMember = &getModel('member');
					$oMbrIfo = $cmMember->getMemberInfoByMemberSrl($oComIfo->get('member_srl'));
					$this->_setAnonymous($args, $oMbrIfo);
					executeQuery('beluxe.updateCommentMemberInfo', $args);
				}

				$msg_code = 'success_updated';
			}
			else
			{

			// 없을 경우 신규 입력
				//text_editor 옵션이 있으면 변경
				if($args->text_editor === 'Y')
				{
					if($args->use_html !== 'Y')$args->content = htmlspecialchars($args->content);
					$args->content = nl2br($args->content);
				}

				//익명 사용시
				if($is_anonymous = $args->anonymous === 'Y') $this->_setAnonymous($args, $oLogIfo);

				$out = $ccComment->insertComment($args, $is_anonymous);

				$msg_code = 'success_registed';
				$cpage = '';

				if($out->toBool())
				{
					// 관리자 메일이 등록되어 있으면 메일 발송
					if($oMi->admin_mail)
					{
						$_tmp = getFullUrl('','document_srl',$doc_srl);
						$cmt_srl = $out->get('comment_srl');
						$this->_sendMail(
							$is_anonymous ? '' : ($args->nick_name ? $args->nick_name : $oLogIfo->nick_name),
							$is_anonymous ? '' : ($args->email_address ? $args->email_address : $oLogIfo->email_address),
							$oMi->admin_mail,
							$oDocIfo->getTitleText(),
							sprintf("From : <a href=\"%s#comment_%d\">%s#comment_%d</a><br/>\r\n%s", $_tmp, $cmt_srl, $_tmp, $cmt_srl, $args->content)
						);
					}

					if(!$this->grant->manager && $ccComment->isModuleUsingPublishValidation($mod_srl))
					{
						$msg_code = 'about_comment_validation';
					}
				}
			}

			// 오류 발생시 멈춤
			if(!$out->toBool())
			{
				$oDB->rollback();
				return $out;
			}

			// 포인트 체크 사용중일때 포인트 감소
			if($chk_vt_point && $vote_point)
			{
				$ccPoint = &getController('point');
				$ccPoint->setPoint($log_mbr_srl, $vote_point, 'minus');
			}

			$oDB->commit();
		}
		else return new Object(-1,'msg_dbconnect_failed');

		$cmt_srl = $out->get('comment_srl');

		$this->_setValidMessage(0, $msg_code, 'comment_'.$msg_code);
		$this->_setLocation('', 'document_srl', $doc_srl, 'comment_srl', $cmt_srl, 'cpage', $cpage);
	}

	function procBoardDeleteDocument()
	{
		$args = Context::getRequestVars();
		$mod_srl = (int) $args->module_srl;
		$doc_srl = (int) $args->document_srl;
		if(!$mod_srl || $this->module_srl != $mod_srl) return new Object(-1,'msg_invalid_request');

		// 문서번호에 해당하는 글이 있는지 확인
		$cmDocument = &getModel('document');
		$oDocIfo = $cmDocument->getDocument($doc_srl, FALSE, FALSE);
		if(!$oDocIfo->isExists()) return new Object(-1, 'msg_invalid_document');
		if(!$oDocIfo->isGranted()) return new Object(-1, 'msg_not_permitted');

		$oMi = $this->_getModuleInfo($mod_srl);

		if(!$this->grant->manager && ($oMi->use_lock_document !== 'N' || $oMi->use_point_type === 'A'))
		{
			//값이없으면 새로 db 읽는거 방지를 위해 값 저장
			if(!isset($GLOBALS['XE_DOCUMENT_LIST'][$doc_srl]))
			{
				$_tmp->variables = array(
					'comment_count'=>$oDocIfo->get('comment_count'),
					'regdate'=>$oDocIfo->get('regdate')
				);

				$GLOBALS['XE_DOCUMENT_LIST'][$doc_srl] = $_tmp;
			}

			$cmThis = &getModel(__XEFM_NAME__);
			$is_lock = $cmThis->isLocked($doc_srl);
			if($is_lock) return new Object(-1,'msg_is_locked_document');
		}

		$oDB = &DB::getInstance();
		if($oDB)
		{
			$oDB->begin();

			//다국어일 경우
			if($args->multilingual === 'Y')
			{
				$lnc_type = Context::getLangType();

				if($oDocIfo->get('lang_code') && ($oDocIfo->get('lang_code') != $lnc_type))
				{
					$lnc_args->document_srl = $doc_srl;
					$lnc_args->in_lang_code = $lnc_type;
					$lnc_out = executeQueryArray('beluxe.getDocumentLangCode',$lnc_args);
					if($lnc_out->toBool())
					{
						$lnc_args->in_lang_code = $lnc_type;
						$lnc_out = executeQuery('beluxe.deleteDocumentLangCode', $lnc_args);
						if(!$lnc_out->toBool())
						{
							$oDB->rollback();
							return $lnc_out;
						}

						$uns_extra = $oDocIfo->get('extra_vars');
						$uns_extra = is_string($uns_extra)?unserialize($uns_extra):$uns_extra;

						if(count($uns_extra->beluxe->langs))
						{
							$langs = array();
							foreach($uns_extra->beluxe->langs as $vlnc)
							{
								if($lnc_type == $vlnc) continue;
								$langs[] = $vlnc;
							}
							$uns_extra->beluxe->langs = count($langs) > 1 ? $langs : array();
						}

						$ex_args->document_srl = $doc_srl;
						$ex_args->extra_vars = serialize($uns_extra);
						$out = executeQuery('beluxe.updateExtraVars', $ex_args);

						$re_doc_srl = $doc_srl;
					}
					else return new Object(-1,'msg_invalid_request');
				}
				else return new Object(-1,'msg_not_default_delete');
			}
			else
			{
				// 삭제 시도
				$ccDocument = &getController('document');

				if($oMi->use_trash === 'N')
					$out = $ccDocument->deleteDocument($doc_srl, $this->grant->manager);
				else
				{
					$t_args->document_srl = $doc_srl;
					$out = $ccDocument->moveDocumentToTrash($t_args);
				}

				$re_doc_srl = '';
			}

			// 오류 발생시 멈춤
			if(!$out->toBool())
			{
				$oDB->rollback();
				return $out;
			}

			$oDB->commit();
		}
		else return new Object(-1,'msg_dbconnect_failed');

		/* //문서 모듈에서 실행하기에 할 필요 없음
		$ccDocument->makeCategoryFile($mod_srl);
		*/

		$msg_code = 'success_deleted';

		$this->_setValidMessage(0, $msg_code, 'document_'.$msg_code);
		$this->_setLocation(
			'', 'category_srl', Context::get('category_srl'), 'document_srl',
			$re_doc_srl, 'comment_srl', $cmt_srl, 'page', $out->get('page')
		);
	}

	function procBoardDeleteComment()
	{
		$args = Context::getRequestVars();

		$mod_srl = (int) $args->module_srl;
		$cmt_srl = (int) $args->comment_srl;

		if(!$mod_srl || $this->module_srl != $mod_srl) return new Object(-1,'msg_invalid_request');

		// 문서번호에 해당하는 글이 있는지 확인
		$colLst = array('comment_srl','module_srl','member_srl','notify_message');
		$cmComment = &getModel('comment');
		$oComIfo = $cmComment->getComment($cmt_srl, FALSE, $colLst);
		if(!$oComIfo->isExists()) return new Object(-1, 'msg_invalid_document');
		if(!$oComIfo->isGranted()) return new Object(-1,'msg_not_permitted');

		$oMi = $this->_getModuleInfo($mod_srl);

		if(!$this->grant->manager && ($oMi->use_lock_comment !== 'N' || $oMi->use_point_type == 'A'))
		{
			$cmThis = &getModel(__XEFM_NAME__);
			$is_lock = $cmThis->isLocked($cmt_srl, 'cmt');
			if($is_lock) return new Object(-1,'msg_is_locked_comment');
		}

		// 삭제 시도
		$ccComment = &getController('comment');
		$out = $ccComment->deleteComment($cmt_srl, $this->grant->manager);
		if(!$out->toBool()) return $out;

		$msg_code = 'success_deleted';
		$doc_srl = $out->get('document_srl');

		$this->_setValidMessage(0, $msg_code, 'comment_'.$msg_code);
		$this->_setLocation('', 'document_srl', $out->get('document_srl'), 'page', $page);
	}

	function procBeluxeDeleteHistory()
	{
		$args = Context::getRequestVars();

		$mod_srl = $this->module_srl;
		$doc_srl = (int) $args->document_srl;
		$his_srl = (int) $args->history_srl;

		if(!$his_srl ||!$doc_srl || !$mod_srl) return new Object(-1,'msg_invalid_request');

		$ccDocument = &getController('document');
		$ccDocument->deleteDocumentHistory($his_srl, $doc_srl, $mod_srl);

		$msg_code = 'success_deleted';

		$this->_setValidMessage(0, $msg_code, 'history_'.$msg_code);
		$this->_setLocation('', 'document_srl', $doc_srl);
	}

	function procBeluxeDeleteTrackback()
	{
		// 메니저만 가능
		$track_srl = Context::get('trackback_srl');
		if(!$this->grant->manager || !$track_srl) return new Object(-1,'msg_invalid_request');

		$ccTrackback = &getController('trackback');
		$out = $ccTrackback->deleteTrackback($track_srl, $this->grant->manager);
		if(!$out->toBool()) return $out;

		$msg_code = 'success_deleted';

		$this->_setValidMessage(0, $msg_code, 'trackback_'.$msg_code);
		$this->_setLocation('', 'document_srl', $out->get('document_srl'));
	}

	function procBeluxeChangeCustomStatus()
	{
		// 메니저만 가능
		$new_value = Context::get('new_value');
		$tar_srls = Context::get('target_srls');
		if(!$this->grant->manager || !$this->module_srl || !$tar_srls) return new Object(-1,'msg_invalid_request');

		$cmThis = &getModel(__XEFM_NAME__);
		$tar_srls = explode(',', $tar_srls);

		foreach($tar_srls as $val) {
			$a_value = (int) $new_value;
			$a_value = ($a_value < 1 && $a_value > 9) ? 'N' : $a_value;

			$args->document_srl = $val;
			$args->is_notice = $a_value;
			$out = executeQuery('beluxe.updateCustomStatus', $args);
		}

		unset($_SESSION['document_management']);
		$this->_setValidMessage(0, 'success_updated');
	}

	function _setUsePoint($aObj)
	{
		$doc_srl = $aObj->document_srl;
		$file_srl = $aObj->file_srl;
		$mbr_srl = $aObj->member_srl;
		$owner_srl = $aObj->owner_srl;
		$use_point = abs($aObj->use_point);
		$percent = $aObj->percent?$aObj->percent:'10';
		$dx_exv = $aObj->extra_vars;

		// 포인트가 없으면 중단
		$cmPoint = &getModel('point');
		if($cmPoint->getPoint($mbr_srl) < $use_point)  return new Object(-1, 'msg_not_enough_point');

		$oDB = &DB::getInstance();
		if($oDB)
		{
			$oDB->begin();

			$args->document_srl = $doc_srl;
			$args->file_srl = $file_srl;
			$args->member_srl = $mbr_srl;

			$exq_str = $file_srl ? 'beluxe.insertFileDownloadedLog' : 'document.insertDocumentReadedLog';
			$out = executeQuery($exq_str, $args);
			if(!$out->toBool())
			{
				$oDB->rollback();
				return $out;
			}

			// 포인트 만큼 빼기, 작성자에게 보상 더하기
			$ccPoint = &getController('point');
			$ccPoint->setPoint($mbr_srl, $use_point, 'minus');
			$ccPoint->setPoint($owner_srl, round($use_point*0.01*$percent), 'add');

			$oDB->commit();
		}
		else return new Object(-1,'msg_dbconnect_failed');

		return new Object();
	}

	function procBeluxePayPoint()
	{
		$doc_srl = Context::get('document_srl');
		if(!$doc_srl) $doc_srl = Context::get('target_srl');
		if(!$doc_srl) return new Object(-1,'msg_invalid_request');

		$oLogIfo = Context::get('logged_info');
		$mbr_srl = $oLogIfo->member_srl;
		if(!$mbr_srl) return new Object(-1,'msg_not_permitted');

		// 문서번호에 해당하는 글이 있는지 확인
		$colLst = array('document_srl','module_srl','member_srl','extra_vars');
		$cmDocument = &getModel('document');
		$oDocIfo = $cmDocument->getDocument($doc_srl, FALSE, FALSE, $colLst);
		if(!$oDocIfo->isExists()) return new Object(-1, 'msg_invalid_document');

		$mod_srl = $oDocIfo->get('module_srl');
		$oMi = $this->_getModuleInfo($mod_srl);
		if($oMi->module_srl != $mod_srl) return new Object(-1,'msg_invalid_request');

		$pt_restrict = $oMi->use_point_type !== 'A' && $oMi->use_restrict_view === 'P';
		if(!$pt_restrict) return new Object(-1,'msg_invalid_request');

		$cmThis = &getModel(__XEFM_NAME__);

		// 사용된적이 없으면
		if(!$cmThis->isRead($doc_srl, $mbr_srl))
		{
			$doc_member_srl = $oDocIfo->get('member_srl');
			$dx_exv = $oDocIfo->get('extra_vars');

			if(is_string($dx_exv)) $dx_exv = unserialize($dx_exv);
			$use_point = $dx_exv->beluxe ? (int) $dx_exv->beluxe->use_point : 0;

			if($use_point)
			{
				$args->document_srl = $doc_srl;
				$args->file_srl = NULL;
				$args->member_srl = $mbr_srl;
				$args->owner_srl = $doc_member_srl;
				$args->use_point = $use_point;
				$args->percent = (int) $oMi->use_point_percent;
				$args->extra_vars = $dx_exv;

				$out = $this->_setUsePoint($args);
				if(!$out->toBool()) return $out;
			}
			else
			{
				// 0인 포인트도 읽기위해 세션에 저장
				$GLOBALS['BELUXE_IS_READ']['doc_'.$doc_srl.'_'.$mbr_srl] = TRUE;
			}
		}
	}

	function procBeluxeRecoverPoint()
	{
		$doc_srl = Context::get('document_srl');
		if(!$doc_srl) $doc_srl = Context::get('target_srl');

		$oMi = $this->_getModuleInfo();

		$re_point = $oMi->use_vote_point_recover;
		if(!$doc_srl || !is_numeric($re_point)) return new Object(-1,'msg_invalid_request');

		$oLogIfo = Context::get('logged_info');
		$mbr_srl = $oLogIfo->member_srl;
		if(!$mbr_srl) return new Object(-1,'msg_not_permitted');

		$cmThis = &getModel(__XEFM_NAME__);
		$vtlog = $cmThis->getDocumentVotedLogs($doc_srl, 0, $mbr_srl);
		if(!$vtlog) return new Object(-1,'msg_not_founded');

		// 값이 - 면  사용된 값으로 복구 불가
		$vote_point = $vtlog->point;
		if($vote_point < 0) return new Object(-1,'msg_not_permitted_act');

		// 복구비용 빼기
		$vote_point = $vote_point - (int) $re_point;

		// 복구 비용 뺀 값이 - 면 포인트가 충분한지 체크
		if($vote_point < 0)
		{
			$cmPoint = &getModel('point');
			if($cmPoint->getPoint($mbr_srl) < abs($vote_point))  return new Object(-1, 'msg_not_enough_point');
		}

		$args->member_srl = $mbr_srl;
		$args->document_srl = $doc_srl;
		$out = executeQuery('beluxe.deleteDocumentVotedLogs', $args);
		if(!$out->toBool()) return $out;

		// 포인트 복구
		if($vote_point)
		{
			$ccPoint = &getController('point');
			$ccPoint->setPoint($mbr_srl, $vote_point, 'add');
		}

		$msg_code = 'success_recovered';

		$this->_setValidMessage(0, $msg_code);
		$this->_setLocation('', 'document_srl', $doc_srl);
	}

	function procBeluxeAdoptComment()
	{
		$cmt_srl = Context::get('comment_srl');
		if(!$cmt_srl) return new Object(-1, 'msg_invalid_request');

		$send_message = Context::get('send_message');

		$cmComment = &getModel('comment');
		$colLst = array('module_srl','document_srl','comment_srl','parent_srl','member_srl');

		// 존재하는 글인지 체크
		$oComIfo = $cmComment->getComment($cmt_srl, false, $colLst);
		if(!$oComIfo->isExists()) return new Object(-1, 'msg_not_founded');

		$cmb_srl = (int) $oComIfo->get('member_srl');
		$doc_srl = (int) $oComIfo->get('document_srl');

		$cmDocument = &getModel('document');
		$oDocIfo = $cmDocument->getDocument($doc_srl, false, false);
		if(!$oDocIfo->isExists()) return new Object(-1, 'msg_not_founded');

		$dmb_srl = (int) $oDocIfo->get('member_srl');

		// 확장 필드 사용
		$ex_vars = $oDocIfo->get('extra_vars');
		$ex_vars = is_string($ex_vars) ? unserialize($ex_vars) : $ex_vars;
		if(!$ex_vars->beluxe) return new Object(-1, 'msg_invalid_request');

		$beluxe = $ex_vars->beluxe;

		$use_point = (int) $beluxe->use_point;
		$adopt_srl = (int) $beluxe->adopt_srl;

		// 이미 채택된 답글이 있다면 중단
		if($adopt_srl){
			$oTmp = $cmComment->getComment($adopt_srl, false, $colLst);
			if($oTmp->isExists()) return new Object(-1, 'msg_invalid_request');
		}

		$oMi = $this->_getModuleInfo($oComIfo->get('module_srl'));
		$_percent = (int) $oMi->use_point_percent;

		// 확장 필드 저장
		$beluxe->adopt_srl = (int) $cmt_srl;
		if($cmb_srl) $beluxe->adopt_member = (int) $cmb_srl;

		$ex_vars->beluxe = $beluxe;
		$args->extra_vars = serialize($ex_vars);
		// 채택된 답글번호 입력
		$args->document_srl = $doc_srl;
		$output = executeQuery('beluxe.updateExtraVars', $args);
		if(!$output->toBool()) return $output;

		// 채택시 포인트 갱신을 위해
		if($cmb_srl && $use_point > 0) {
			$point = round($use_point*0.01*$_percent);
			$ccPoint = &getController('point');
			// 포인트 지급
			if($point > 0) $ccPoint->setPoint($cmb_srl, $point, 'add');
			// 나머지는 돌려줌
			if($dmb_srl && ($use_point-$point) > 0) $ccPoint->setPoint($dmb_srl, $use_point-$point, 'add');
		}

		if($cmb_srl && $send_message){
			$t = sprintf(Context::getLang('msg_adopt_thanks'), $cmt_srl);
			$u = getFullUrl('', 'document_srl',$doc_srl,'comment_srl',$cmt_srl);
			$send_message = $send_message . '<br /><br /><a href="' . $u . '">'. $u .'</a>';

			$ccCommuni = &getController('communication');
			$ccCommuni->sendMessage($dmb_srl, $cmb_srl, $t, $send_message);
		}

		return new Object(0, 'success_adopted');
	}

	function triggerBeforeDownloadFile(&$pObj)
	{
		$oLogIfo = Context::get('logged_info');
		$mbr_srl = $oLogIfo->member_srl;

		// 자신의 올린 파일이면 패스
		if($this->grant->manager || ($mbr_srl && abs($pObj->member_srl) == abs($mbr_srl))) return new Object();

		// 문서에 첨부된 파일이 아니면 오류 (TODO 원래 upload_target_type 값으로 댓글인지 알수 있었으나 xe1.5에서 누락됨)
		$cmDocument = &getModel('document');
		$colLst = array('document_srl','module_srl','member_srl','extra_vars');
		$oDocIfo = $cmDocument->getDocument($pObj->upload_target_srl, FALSE, FALSE, $colLst);
		if(!$oDocIfo->isExists())
		{
			// 댓글이면 그냥 통과
			$cmComment = &getModel('comment');
			$colLst = array('comment_srl','module_srl','member_srl');
			$oComIfo = $cmComment->getComment($pObj->upload_target_srl, FALSE, FALSE, $colLst);
			if($oComIfo->isExists()) return new Object();

			return new Object(-1,'msg_invalid_document');
		}

		$mod_srl = $oDocIfo->get('module_srl');
		$oMi = $this->_getModuleInfo($mod_srl);

		if($oMi->module_srl != $pObj->module_srl) return new Object(-1, 'msg_invalid_request');
		if($oMi->use_point_type === 'A') return new Object();

		$cmThis = &getModel(__XEFM_NAME__);

		if($oMi->use_restrict_down === 'Y')
		{
			if($cmThis->isWrote($pObj->upload_target_srl, $mbr_srl, TRUE, 'cmt'))
				return new Object();
			else return new Object(-1,'msg_required_comment');
		}
		else if($oMi->use_restrict_down === 'P')
		{
			// 포인트 사용시 맴버가 아니면 오류
			if(!$mbr_srl) return new Object(-1,'msg_not_permitted');
			// 다운로드 한적 있으면 패스
			if($cmThis->isDownloaded($pObj->file_srl, $mbr_srl)) return new Object();

			$dx_exv = $oDocIfo->get('extra_vars');
			if(is_string($dx_exv)) $dx_exv = unserialize($dx_exv);
			$use_point = $dx_exv->beluxe?(int) $dx_exv->beluxe->use_point:0;

			if($use_point)
			{
				$args->document_srl = $pObj->upload_target_srl;
				$args->file_srl = $pObj->file_srl;
				$args->member_srl = $mbr_srl;
				$args->owner_srl = $pObj->member_srl;
				$args->use_point = $use_point;
				$args->percent = (int) $oMi->use_point_percent;
				$args->extra_vars = $dx_exv;

				$out = $this->_setUsePoint($args);
				if(!$out->toBool()) return $out;
			}
			else
			{
				// 0인 포인트도 읽기위해 세션에 저장
				$GLOBALS['BELUXE_IS_DOWNLOADED']['doc_'.$pObj->file_srl.'_'.$mbr_srl] = TRUE;
			}
		}
	}

	function triggerDownloadFile(&$pObj)
	{
		return new Object();
	}

}

/* End of file beluxe.controller.php */
/* Location: ./modules/beluxe/beluxe.controller.php */