<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: helper_form.php 34542 2014-05-26 07:32:53Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

/**
 * Description of helper_form
 *
 * @author zhangguosheng
 */
class helper_form {


	/**
	* 检查是否正确提交了表单
	* @param $var 需要检查的变量
	* @param $allowget 是否允许GET方式
	* @param $seccodecheck 验证码检测是否开启
	* @return 返回是否正确提交了表单
	*/
	public static function submitcheck($var, $allowget = 0, $seccodecheck = 0, $secqaacheck = 0) {
		if(!getgpc($var)) {
			return FALSE;
		} else {
			global $_G;
			//note mobiledata
//			if(!empty($_GET['mobiledata'])) {
//				require_once libfile('class/mobiledata');
//				$mobiledata = new mobiledata();
//				if($mobiledata->validator()) {
//					return TRUE;
//				}
//			}
			if($allowget || ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_GET['formhash']) && $_GET['formhash'] == formhash() && empty($_SERVER['HTTP_X_FLASH_VERSION']) && (empty($_SERVER['HTTP_REFERER']) ||
				strncmp($_SERVER['HTTP_REFERER'], 'http://wsq.discuz.com/', 22) === 0 || strncmp($_SERVER['HTTP_REFERER'], 'http://m.wsq.qq.com', 19) === 0 ||
				preg_replace("/https?:\/\/([^\:\/]+).*/i", "\\1", $_SERVER['HTTP_REFERER']) == preg_replace("/([^\:]+).*/", "\\1", $_SERVER['HTTP_HOST'])))) {
				if(checkperm('seccode')) {
					if($secqaacheck && !check_secqaa($_GET['secanswer'], $_GET['sechash'])) {
						showmessage('submit_secqaa_invalid');
					}
					if($seccodecheck && !check_seccode($_GET['seccodeverify'], $_GET['sechash'])) {
						showmessage('submit_seccode_invalid');
					}
				}
				return TRUE;
			} else {
				showmessage('submit_invalid');
			}
		}
	}

	/**
	 * 词语过滤
	 * @param $message - 词语过滤文本
	 * @return 成功返回原始文本，否则提示错误或被替换
	 */
	public static function censor($message, $modword = NULL, $return = FALSE) {
		global $_G;
		$censor = discuz_censor::instance();
		$censor->check($message, $modword);
		if($censor->modbanned() && empty($_G['group']['ignorecensor'])) {
			$wordbanned = implode(', ', $censor->words_found);
			if($return) {
				return array('message' => lang('message', 'word_banned', array('wordbanned' => $wordbanned)));
			}
			if(!defined('IN_ADMINCP')) {
				showmessage('word_banned', '', array('wordbanned' => $wordbanned));
			} else {
				cpmsg(lang('message', 'word_banned'), '', 'error', array('wordbanned' => $wordbanned));
			}
		}
		if($_G['group']['allowposturl'] == 0 || $_G['group']['allowposturl'] == 2) {
			$urllist = self::get_url_list($message);
			if(is_array($urllist[1])) foreach($urllist[1] as $key => $val) {
				if(!$val = trim($val)) continue;
				if(!iswhitelist($val)) {
					if($_G['group']['allowposturl'] == 0) {
						if($return) {
							return array('message' => 'post_url_nopermission');
						}
						showmessage('post_url_nopermission');
					} elseif($_G['group']['allowposturl'] == 2) {
						$message = str_replace('[url]'.$urllist[0][$key].'[/url]', $urllist[0][$key], $message);
						$message = preg_replace(
							array(
								"@\[url=[^\]]*?".preg_quote($urllist[0][$key],'@')."[^\]]*?\](.*?)\[/url\]@is",
								"@href=('|\")".preg_quote($urllist[0][$key],'@')."\\1@is",
								"@\[url\]([^\]]*?".preg_quote($urllist[0][$key],'@')."[^\]]*?)\[/url\]@is",
							),
							array(
								'\\1',
								'',
								'\\1',
							),
							$message);
					}
				}
			}
		}
		return $message;
	}

	/**
		词语过滤，检测是否含有需要审核的词
	*/
	public static function censormod($message) {
		global $_G;
		if($_G['group']['ignorecensor']) {
			return false;
		}
		$modposturl = false;
		if($_G['group']['allowposturl'] == 1) {
			$urllist = self::get_url_list($message);
			if(is_array($urllist[1])) foreach($urllist[1] as $key => $val) {
				if(!$val = trim($val)) continue;
				if(!iswhitelist($val)) {
					$modposturl = true;
				}
			}
		}
		if($modposturl) {
			return true;
		}

		$censor = discuz_censor::instance();
		$censor->check($message);
		return $censor->modmoderated();
	}


	/**
	 * 检查验证码正确性
	 * @param $value 验证码变量值
	 */
	public static function check_seccode($value, $idhash) {
		global $_G;
		if(!$_G['setting']['seccodestatus']) {
			return true;
		}
		if(!is_numeric($_G['setting']['seccodedata']['type'])) {
			$etype = explode(':', $_G['setting']['seccodedata']['type']);
			if(count($etype) > 1) {
				$codefile = DISCUZ_ROOT.'./source/plugin/'.$etype[0].'/seccode/seccode_'.$etype[1].'.php';
				$class = $etype[1];
			} else {
				$codefile = libfile('seccode/'.$_G['setting']['seccodedata']['type'], 'class');
				$class = $_G['setting']['seccodedata']['type'];
			}
			if(file_exists($codefile)) {
				@include_once $codefile;
				$class = 'seccode_'.$class;
				if(class_exists($class)) {
					$code = new $class();
					if(method_exists($code, 'check')) {
						return $code->check($value, $idhash);
					}
				}
			}
			return false;
		}
		if(!isset($_G['cookie']['seccode'.$idhash])) {
			return false;
		}
		list($checkvalue, $checktime, $checkidhash, $checkformhash) = explode("\t", authcode($_G['cookie']['seccode'.$idhash], 'DECODE', $_G['config']['security']['authkey']));
		return $checkvalue == strtoupper($value) && TIMESTAMP - 180 > $checktime && $checkidhash == $idhash && FORMHASH == $checkformhash;
	}

	/**
	 * 检查验证问答正确性
	 * @param $value 验证问答变量值
	 */
	public static function check_secqaa($value, $idhash) {
		global $_G;
		if(!$_G['setting']['secqaa']) {
			return true;
		}
		if(!isset($_G['cookie']['secqaa'.$idhash])) {
			return false;
		}
		loadcache('secqaa');
		list($checkvalue, $checktime, $checkidhash, $checkformhash) = explode("\t", authcode($_G['cookie']['secqaa'.$idhash], 'DECODE', $_G['config']['security']['authkey']));
		return $checkvalue == md5($value) && TIMESTAMP - 180 > $checktime && $checkidhash == $idhash && FORMHASH == $checkformhash;
	}


	/**
	 * 获取文字内的url列表
	 *
	 * @param $message 文字
	 * @return <array> url列表
	 *
	 */
	public static function get_url_list($message) {
		$return = array();

		(strpos($message, '[/img]') || strpos($message, '[/flash]')) && $message = preg_replace("/\[img[^\]]*\]\s*([^\[\<\r\n]+?)\s*\[\/img\]|\[flash[^\]]*\]\s*([^\[\<\r\n]+?)\s*\[\/flash\]/is", '', $message);
		if(preg_match_all("/((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|thunder|qqdl|synacast){1}:\/\/|www\.)[^ \[\]\"']+/i", $message, $urllist)) {
			foreach($urllist[0] as $key => $val) {
				$val = trim($val);
				$return[0][$key] = $val;
				if(!preg_match('/^http:\/\//is', $val)) $val = 'http://'.$val;
				$tmp = parse_url($val);
				$return[1][$key] = $tmp['host'];
				if($tmp['port']){
					$return[1][$key] .= ":$tmp[port]";
				}
			}
		}
		return $return;
	}

	/**
	 * 更新数据的审核状态
	 * @param <string> $idtype 数据类型 tid=thread pid=post blogid=blog picid=picture doid=doing sid=share aid=article uid_cid/blogid_cid/sid_cid/picid_cid/aid_cid/topicid_cid=comment
	 * @param <array/int> $ids ID 数组、ID 值
	 * @param <int> $status 状态 0=加入审核(默认) 1=忽略审核 2=审核通过
	 */
	public static function updatemoderate($idtype, $ids, $status = 0) {
		$ids = is_array($ids) ? $ids : array($ids);
		if(!$ids) {
			return;
		}
		if(!$status) {
			foreach($ids as $id) {
				//DB::insert('common_moderate', array('id' => $id, 'idtype' => $idtype, 'status' => 0, 'dateline' => $_G['timestamp']), false, true);
				C::t('common_moderate')->insert($idtype, array(
					'id' => $id,
					'status' => 0,
					'dateline' => TIMESTAMP,
				), false, true);
			}
		} elseif($status == 1) {
			 //DB::update('common_moderate', array('status' => 1), "id IN (".dimplode($ids).") AND idtype='$idtype'");
			C::t('common_moderate')->update($ids, $idtype, array('status' => 1));
		} elseif($status == 2) {
			//DB::delete('common_moderate', "id IN (".dimplode($ids).") AND idtype='$idtype'");
			C::t('common_moderate')->delete($ids, $idtype);
		}
	}
}

?>