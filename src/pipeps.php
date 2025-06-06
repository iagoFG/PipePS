<?php
	/// PipePS - an extensible & customizable minimalistic CMS with headless mode or onsite content editor, external plugin modules, internationalization, multi-site support and much more...
	/// @author info@iagoFG.com
	/// @license MPL-2.0
	/// custom per page launcher area (you can edit this area if needed, but is recommended to use html template files in site/usr/tpl) ///////////////
	
	function psetup() {
		if (!isset($GLOBALS['pipeps-config'])) { $GLOBALS['pipeps-config'] = array( /// if not defined, define some config values for pipeps (you can edit here or better in your own PHP that then includes this)
			'mode' => 'buffer', /// selects how pipeps input/output is managed: buffer (for webs), flush (for rapid webservices) or cli (for apps); buffer enables a buffer and can filter errors, flush immediately send data (usesful for comet or sockets) and cli is forced/used for command line tools which can have interactive.
			'page' => array('/([^\/]+)\/index.php$/', '/([^\/]+).php$/', '/[\/]([^\.\/]+)\.[^\.\/]+[\/]?$/', '/[\/]([^\.\/]+)[\/]?$/'), /// default page patterns, usually no need to change; pipeps will use these patterns to autodetermine page/template to be loaded from browser parameters/uri/url/script file
			'pipe' => array('edit' => ''), /// default pipe values
			'i18n' => array('en' => TRUE, 'es' => FALSE, 'de' => FALSE), /// languages in which page is available, marked as TRUE the default language
			'site' => '/([^\.]+)\.*[^\.]*$/', /// default site patterns, usually no need to change; pipeps will determine site name from browser uri/url/script file/domain; sites are separated repositories stored on separated folders which can be sync or moved easily from one pipeps installation to another
			'actions' => array('init', 'checkbefore', 'etcfind', 'etcrun', 'i18n', 'doactions', 'tplfind', 'include', 'checkafter'), /// default action sequence, performed when pipeps is started; usually standard action sequence will look for and load configuration, then look for and load/run a template, but you can define your own and/or call steps manually
			'log-path' => array('/var/log', '/tmp', 'c:/windows/temp', sys_get_temp_dir(), '.', TRUE), /// paths where to store log file/s; define your own log file, can use an array for defining various alternatives; use a string or use TRUE to use standard output, or FALSE to disable log output (not recomended); note that with pipeps all the non poutput with echo/var_export/... and other typical output, including errors will be dumped to this file (depending on mode)
			'tmp-path' => array('/tmp', 'c:/windows/temp', '.'), /// paths where to store temporal files
			'sites-path' => array(TRUE), /// paths where to look for site folders, fillin TRUE for using the default path: pipeps folder itself, otherwise can specify '.' or a relative or an absolute path
			'usr-path' => array(TRUE), /// folders where to store the usr data, TRUE meaning is the default route within the site folder
			'session-path' => array(TRUE, '/tmp'), /// folders where to store session files, TRUE meaning is the default path inside the site folder
			'editable-path' => array(TRUE, '/tmp'), /// folders where to store editable contents, TRUE meaning is the default path inside the site folder
		); }
	}
	if (!function_exists('main')) { function main(&$ctx) {} }  /// if not defined, define an empty main function; YOU CAN DEFINE your own in a SEPARATE FILE like the index.php including this file; main is called AFTER pipeps init sequence and actions, and before shutdown, so templates can be loaded and processed into output buffers but not yet sent to browser; if you need to change templates develop instead modules that can be called from templates, also you can define a $config['action'] array without 'tplfind', 'tplrun' and 'checkafter' and invoke yourself that steps; beyond here usually you should NOT change things


/// STARTUP/SHUTDOWN METHODS (life cycle) //////////////////////////////////////


function &pipeps(&$config) {
	register_shutdown_function('pshutdown');    /// call this method to boot pipeps using configuration defined in $config
	if (!isset($GLOBALS['pipeps-mode'])) {
		$GLOBALS['pipeps-mode'] = 0;
	}
	if (!is_array($config)) {
		$config = array( );
	}
	if (!isset($GLOBALS['pipeps-config'])) {
		$GLOBALS['pipeps-config'] = &$config;
	}
	switch (isset($config['mode']) ? $config['mode'] : 'buffer') {
	default:
	case 'buffer':
		if (pmod_vm($ctx, $ctx) >= 5.4) {
			ob_start(null, 0, PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_REMOVABLE);
		} else {
			ob_start();
		} $GLOBALS['pipeps-mode'] = 1;
		break;
	case 'flush':
		$GLOBALS['pipeps-mode'] = 2;
		break;
	case 'cli':
		$GLOBALS['pipeps-mode'] = 3;
		break;
	}
	if (defined('STDIN') || (isset($_SERVER['argv']) && count($_SERVER['argv']) > 0 && empty($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['HTTP_USER_AGENT']))) {
		$config['mode'] = 'cli';
	} $pipe = FALSE;
	$out = array();
	$ctx = array('config' => &$config, 'pipe' => &$pipe, 'out' => &$out);
	if (!isset($GLOBALS['pipeps-context'])) {
		$GLOBALS['pipeps-context'] = &$ctx;
	}
	if (!isset($GLOBALS['pipeps-args'])) {
		$GLOBALS['pipeps-args'] = FALSE;
	}
	if (!isset($config['actions'])) {
		$config['actions'] = array();
	}
	pmod_sequence($ctx, $config['actions']);
	return $ctx;
}


function pfinish(&$ctx) {
	if (isset($GLOBALS['pipeps-mode'])) {
		switch ($GLOBALS['pipeps-mode']) {
		default:
		case 1:
			$ob = ob_get_contents();    /// should be called when actions finish, otherwise pshutdown will perform also its tasks
			plog($ob);
			ob_end_clean();
			if (pmod_vm($ctx, $ctx) >= 5.4) {
				ob_start(null, 0, PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_REMOVABLE);
			} else {
				ob_start();
			}
			break;
		case 2:
			break;
		case 3:
			break;
		}
	}
	pimpl_session_shutdown($ctx);
}


function pfail(&$ctx, $errorcode = FALSE, $errorname = FALSE, $errormessage = FALSE, $send_header = TRUE) {
	if (isset($GLOBALS['pipeps-mode'])) {
		switch ($GLOBALS['pipeps-mode']) {
		default:
		case 1:
			$ob = ob_get_contents();    /// should be called when actions fail, use an HTTP-like error code
			plog($ob);
			ob_end_clean();
			if (pmod_vm($ctx, $ctx) >= 5.4) {
				ob_start(null, 0, PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_REMOVABLE);
			} else {
				ob_start();
			}
			break;
		case 2:
			break;
		case 3:
			break;
		}
	}
	if ($errorcode === FALSE) {
		$errorcode = 500;
	}
	if ($errorname) {
		$errorname = pimpl_fail($errorcode);
	} $GLOBALS['pipeps-context']['out'] = array('Error ' . $errorcode . ': ' . ($errorname == FALSE ? 'Internal Error' : $errorname) . ($errormessage === FALSE ? '' : '<br>' . $errormessage));
	plog(implode(' ', $GLOBALS['pipeps-context']['out']));
	plog(" called from " . ptraceall() . "\n");
	die();
}
function pimpl_fail($errorcode = 500) {
	switch ($errorcode) {
	case 500:
		return 'Internal Server Error';
	case 501:
		return 'Not Implemented';
	case 503:
		return 'Service Temporarily Unavailable';
	case 404:
		return 'Not Found';
	case 403:
		return 'Forbidden';
	case 429:
		return 'Too Many Requests';
	case 999:
		return '999 Request Denied';
	}
}


function pshutdown() {
	if (isset($GLOBALS['pipeps-mode'])) {
		switch ($GLOBALS['pipeps-mode']) {
		default:
		case 1:
			$ob = ob_get_contents();    /// handle pipeps request finish/shutdown
			plog($ob);
			ob_end_clean();
			foreach ($GLOBALS['pipeps-context']['out'] as $block) echo($block);
			break;
		case 2:
			break;
		case 3:
			break;
		}
	}
	pimpl_session_shutdown($GLOBALS['pipeps-context']);
	plog("\n");
	plog(FALSE);
}
function pimpl_session_shutdown(&$ctx) {
	if (is_array($GLOBALS['pipeps-session']) && $GLOBALS['pipeps-session-updated']) {
		$sitepath = pconfig($ctx, 'site-path');
		$siteusr = pconfig($ctx, 'site-usr');
		$sid = $GLOBALS['pipeps-session']['sid'];
		$osessionpath = FALSE;
		$sessionpaths = &pconfig($ctx, 'session-path', TRUE, array(TRUE));
		if ($sitepath !== FALSE && $siteusr !== FALSE) {
			foreach ($sessionpaths as $k => $v) if ($v === TRUE) {
				$sessionpaths[$k] = $sitepath . '/usr-' . $siteusr . '/sessions/';
			}
		} $sdata = '';
		ptostr($GLOBALS['pipeps-session'], $sdata);
		if (FALSE === ($write = ptrywrite($osessionpath, $sessionpaths, pescape($sid) . '.session', $sdata))) {
			plog("{" . date('Y-m-d H:i:s O') . ' warning: ' . "could not write [1] session file '" . pescape($sid) . ".session' }\n");
		} else {
			if ($osessionpath !== FALSE) {
				$sessionpaths = array($osessionpath);
			}
		} $GLOBALS['pipeps-session-updated'] = FALSE;
	}
}



/// ACTION MODULES (usually called from sequences) /////////////////////////////


function pmod_init(&$ctx, &$args) {
	if ($ctx['pipe'] === FALSE) {
		pimpl_pipe($ctx);    /// init session, buffers and other stuff needed before loading config
	} $sid = FALSE;
	if (isset($GLOBALS['pipeps-cookies']['sid'])) {
		$sid = $GLOBALS['pipeps-cookies']['sid'];
	}
	if ($sid === FALSE && isset($GLOBALS['pipeps-args']['sid'])) {
		$sid = $GLOBALS['pipeps-args']['sid'];
	}
	if ($sid === FALSE && isset($ctx['pipe']['sid'])) {
		$sid = $ctx['pipe']['sid'];
	}
	if ($sid === FALSE) {
		$sid = substr(md5(time() . rand()), 0, 12);
		$GLOBALS['pipeps-session'] = $sid;
	} else {
		$GLOBALS['pipeps-session'] = FALSE;
	} $ctx['pipe']['sid'] = $sid;
	setcookie('sid', $sid, time() + 3600 * 24 * 365);
}


function pmod_checkbefore(&$ctx, &$args) {
	$remoteip = ip2long($_SERVER['REMOTE_ADDR']);    /// check some rules and tables before proceed
	$iphash = ($remoteip & 2047) ^ (($remoteip >> 11) & 2047) ^ ($remoteip >> 22);
	$fail = FALSE;
	$lapse = 0.3;
	$maxlapse = 1000;
	$cur_time = time();
	$tmp_path = FALSE;
	$tmp_paths = pconfig($ctx, 'tmp-path', TRUE, array('./'));
	$handle = ptryread($tmp_path, $tmp_paths, array('uri' => 'pipeps-' . $iphash . '.ip', 'lock' => 'wait', 'write' => TRUE, 'handle' => TRUE));
	if ($tmp_path !== FALSE) {
		$tmp_paths = array($tmp_path);
	} $data = '';
	if ($handle !== FALSE) {
		$data = $handle['data'];
	} $pair_time = explode(',', $data);
	if (isset($pair_time[0])) {
		$old_time = $pair_time[0];
	} else {
		$old_time = 0;
	}
	if (isset($pair_time[1])) {
		$dif_time = $pair_time[1];
	} else {
		$dif_time = $maxlapse;
	}
	if ($old_time > 999999999999) {
		$old_time = 999999999999;
	}
	if ($dif_time > 999999999999) {
		$dif_time = $maxlapse;
	}
	if (!($old_time > 0)) {
		$old_time = 0;
	}
	if (!($dif_time > 0)) {
		$dif_time = 0;
	} $dif_time = floor($dif_time * 0.9 + ($cur_time - $old_time) * 0.1);
	if ($dif_time > $maxlapse) {
		$dif_time = $maxlapse;
	}
	if ($handle !== FALSE) {
		presize($handle, 0);
		pwrite($handle, pbyref($cur_time . ',' . $dif_time), 0);
		pimpl_hclose($handle);
		if ($dif_time < $lapse) {
			$fail = TRUE;
		}
	} else {
		$tmp_path = FALSE;
		$handle = ptrywrite($tmp_path, $tmp_paths, array('uri' => 'pipeps-' . $iphash . '.ip', 'lock' => 'wait'), pbyref($cur_time . ',' . $dif_time), 0);
		if ($tmp_path !== FALSE) {
			$tmp_paths = array($tmp_path);
		}
		if ($handle === FALSE) {
			plog('{Error because could not wrote to pipeps-' . $iphash . ".ip}\n");
			$fail = TRUE;
		}
	}
	if ($fail) {
		pfail($ctx, 429);
	}
}


function pmod_etcfind(&$ctx, &$args) {
	$sitepath = pconfig($ctx, 'site-path', TRUE, FALSE);    /// identify the site config and find etc file, autodetects and configures 'site-usr' and 'site-path' if not present
	if ($sitepath === FALSE) {
		$domain = strtolower(trim(getenv("HTTP_HOST")));
		$sitepath = pmatch($domain, pconfig($ctx, 'site', TRUE, '/([^\.]+)\.*[^\.]*$/'));
		if ($sitepath === FALSE) {
			$sitepath = $domain;
		} else {
			$sitepath = $sitepath[1];
		}
		pconfig($ctx, 'site-path', $sitepath);
	} $siteusr = pconfig($ctx, 'site-usr', TRUE);
	if ($siteusr === FALSE) {
		$etcpath = $sitepath . '/etc/';
		if (is_string($etcfilename = penum($etcpath, "!/index\\.|\\~|\\.bak/", "!=dir", 0))) {
			if (phas($etcpath . '/index.html')) {
				$dotpos = strrpos($etcfilename, '.');
				pconfig($ctx, 'site-usr', $dotpos >= 0 ? substr($etcfilename, 0, $dotpos) : $etcfilename);
				return array($etcfilename, $etcpath, $sitepath);
			} else {
				plog("{" . date('Y-m-d H:i:s O') . ' warning: ' . "index.html protection MUST exist at " . $sitepath . "/etc/ }\n");
			}
		} else {
			plog("{" . date('Y-m-d H:i:s O') . ' warning: ' . "cannot find config file at " . $sitepath . "/etc/ }\n");
		}
	} else if (phas($sitepath . '/etc/' . $siteusr . '.php')) {
		return array($siteusr . '.php', $sitepath . '/etc/', $sitepath);
	}
	return FALSE;
}


function pmod_etcrun(&$ctx, &$args) {
	if (is_array($args) && count($args) >= 2) {
		include($args[1] . '/' . $args[0]);    /// run the etc file, either is php or other (via module)
	}
	if ($ctx['pipe'] === FALSE) {
		pimpl_pipe($ctx);
	} $sid = $ctx['pipe']['sid'];
	$sitepath = pconfig($ctx, 'site-path');
	$siteusr = pconfig($ctx, 'site-usr');
	$osessionpath = FALSE;
	$sessionpaths = &pconfig($ctx, 'session-path', TRUE, array(TRUE));
	if ($sitepath !== FALSE && $siteusr !== FALSE) {
		foreach ($sessionpaths as $k => $v) if ($v === TRUE) {
			$sessionpaths[$k] = $sitepath . '/usr-' . $siteusr . '/sessions/';
		}
	}
	if ($GLOBALS['pipeps-session'] === FALSE) {
		$sdata = ptryread($osessionpath, $sessionpaths, pescape($sid) . '.session');
		if ($sdata === FALSE) {
			$sid = FALSE;
		} else {
			if ($osessionpath !== FALSE) {
				$sessionpaths = array($osessionpath);
			} $GLOBALS['pipeps-session'] = array();
			ptoassoc($sdata, $GLOBALS['pipeps-session']);
			$GLOBALS['pipeps-session-updated'] = FALSE;
		}
	} else {
		if (FALSE === ptrywrite($osessionpath, $sessionpaths, pescape($sid) . '.session', pbyref('&sid=' . $sid . '&ip=' . $_SERVER['REMOTE_ADDR'] . '&'))) {
			plog("{" . date('Y-m-d H:i:s O') . ' warning: ' . "could not write [2] session file '" . pescape($sid) . ".session' }\n");
		} else {
			if ($osessionpath !== FALSE) {
				$sessionpaths = array($osessionpath);
			}
		}
	}
	if ($sid === FALSE) {
		$sid = substr(md5(time() . rand()), 0, 12);
		$GLOBALS['pipeps-session'] = array('sid' => $sid, 'ip' => $_SERVER['REMOTE_ADDR']);
		$GLOBALS['pipeps-session-updated'] = FALSE;
		if (FALSE === ptrywrite($osessionpath, $sessionpaths, pescape($sid) . '.session', pbyref('&sid=' . $sid . '&ip=' . $_SERVER['REMOTE_ADDR'] . '&'))) {
			plog("{" . date('Y-m-d H:i:s O') . ' warning: ' . "could not write [3] session file '" . pescape($sid) . ".session' }\n");
		} else {
			if ($osessionpath !== FALSE) {
				$sessionpaths = array($osessionpath);
			}
		} $ctx['pipe']['sid'] = $sid;
		setcookie('sid', $sid, time() + 3600 * 24 * 365);
	}
	if ($osessionpath !== FALSE) {
		$sessionpaths = array($osessionpath);
	}
	return $args;
}


function pmod_doactions(&$ctx, &$args) {
	$dostr = parg($ctx, 'do');    /// run the etc file, either is php or other (via module)
	if (strlen($dostr) > 0) {
		$doactions = explode(',', $dostr);
		pmod_sequence($ctx, $doactions);
	} else { } return $args;
}


function pmod_tplfind(&$ctx, &$args) {
	$script = strtolower(trim(getenv("REDIRECT_URL")));    /// load and parse the page template
	$tpl = parg($ctx, 'page', FALSE);
	if ($tpl === FALSE) {
		$page_match = pconfig($ctx, 'page', TRUE, '/([^\/]+)\/index.php$/');
		$tpl = pmatch($script, $page_match);
	} else {
		$tpl = array($tpl, $tpl);
	} $sitepath = pconfig($ctx, 'site-path');
	$siteusr = pconfig($ctx, 'site-usr');
	if ($sitepath !== FALSE && $siteusr !== FALSE) {
		if (is_array($tpl) && count($tpl) >= 2 && phas($sitepath . '/usr-' . $siteusr . '/tpl/' . $tpl[1] . '.html')) {
			plog("{" . date('Y-m-d H:i:s O') . ' pipeps processing ' . getenv('REQUEST_METHOD') . ":" . getenv('REQUEST_URI') . ", template: tpl/" . $tpl[1] . ".html, site: " . ($sitepath === FALSE ? "FALSE" : $sitepath) . "}\n");
			return array($ctx['pipe']['page'] = $tpl[1]);
		} else if (phas($sitepath . '/usr-' . $siteusr . '/tpl/default.html')) {
			plog("{" . date('Y-m-d H:i:s O') . ' pipeps processing ' . getenv('REQUEST_METHOD') . ":" . getenv('REQUEST_URI') . ", template: tpl/default.html, site: " . ($sitepath === FALSE ? "FALSE" : $sitepath) . "}\n");
			return array($ctx['pipe']['page'] = 'default');
		} else {
			plog("{" . date('Y-m-d H:i:s O') . ' warning: ' . "cannot find template '" . ($sitepath . '/usr-' . $siteusr . '/tpl/' . $tpl[1] . '.html') . "' or '" . ($sitepath . '/usr-' . $siteusr . '/tpl/default.html') . "' }\n");
		}
	} else { } plog("{" . date('Y-m-d H:i:s O') . ' pipeps processing ' . getenv('REQUEST_METHOD') . ":" . getenv('REQUEST_URI') . ", template: NOT FOUND tpl(" . pdump($tpl) . "), sitepath: " . ($sitepath === FALSE ? "FALSE" : "NOT FALSE") . ", siteusr: " . ($siteusr === FALSE ? "FALSE" : "NOT FALSE") . ", count/tpl: " . (is_array($tpl) ? count($tpl) : "-") . ", site: " . ($sitepath === FALSE ? "FALSE" : $sitepath) . "}\n");
	return FALSE;
}


function pmod_checkafter(&$ctx, &$args) {
	$siteusr = pconfig($ctx, 'site-usr');    /// check output, some rules, tables and if necesary update them
	switch ($GLOBALS['pipeps-mode']) {
	default:
	case 1:
		foreach ($GLOBALS['pipeps-context']['out'] as $block) {
			if (pmatch($block, $siteusr)) {
				plog("{a contaminated output block was generated! |" . str_replace($siteusr, "__CONTAMINATION__", $block) . "| }\n");
				pfail($ctx, 500);
				break;
			}
		}
		break;
	case 2:
		break;
	case 3:
		break;
	}
	pfinish($ctx);
	return $args;
}


function pmod_login(&$ctx, &$args) {
	$user = parg($ctx, 'user');    /// run the sequence in template mode (level 0 are bypassed)
	$pass = parg($ctx, 'pass');
	if (isset($args['token']) && $args['token'] === TRUE) {
		if (is_array($GLOBALS['pipeps-session'])) {
			$GLOBALS['pipeps-session']['auth.token'] = substr(md5(time() . rand()), 0, 12);
			$GLOBALS['pipeps-session-updated'] = TRUE;
			return $GLOBALS['pipeps-session']['auth.token'] . '|' . pimpl_i18n($ctx);
		} else {
			return '';
		}
	} else if (isset($args['panel']) && $args['panel'] === TRUE) {
		$logged = is_array($GLOBALS['pipeps-session']) && isset($GLOBALS['pipeps-session']['auth.user']) && strlen($GLOBALS['pipeps-session']['auth.user']) > 0;
		pout(phtml_form(array('id' => ($logged ? "logout" : "login") . '-form', 'class' => 'login-form')));
		pout(phtml_input('hidden', "_", pcompress(pimpl_href($ctx, pbyref(array())))));
		pout(phtml_input('hidden', 'do', $logged ? 'logout' : 'login'));
		if ($logged) {
			pout(phtml('span', array('id' => 'user'), $GLOBALS['pipeps-session']['auth.user']));
		} else {
			pout(phtml_input('text', 'user'));
			pout(phtml_input('password', 'pass'));
		}
		pout(phtml_input('submit', 'submit', $logged ? 'Logout' : 'Login'));
		pout(phtml_end('form'));
	} else {
		$logged = FALSE;
		$udata = FALSE;
		if (is_array($GLOBALS['pipeps-session'])) {
			$sitepath = pconfig($ctx, 'site-path');
			$siteusr = pconfig($ctx, 'site-usr');
			if ($sitepath !== FALSE && $siteusr !== FALSE) {
				$udata = pread($sitepath . '/usr-' . $siteusr . '/users/' . pescape($user) . '.user');
				if ($udata !== FALSE) {
					$uprops = array();
					ptoassoc($udata, $uprops);
					if ((isset($uprops['hash']) && md5($pass) == $uprops['hash']) || (isset($GLOBALS['pipeps-session']['auth.token']) && ($pass == md5($GLOBALS['pipeps-session']['auth.token'] . $uprops['hash'])))) {
						foreach ($uprops as $k => $v) $GLOBALS['pipeps-session']['auth.' . $k] = $v;
						$GLOBALS['pipeps-session-updated'] = TRUE;
						$logged = $user;
					}
				}
			}
		}
		return $args;
	}
}


function pmod_logout(&$ctx, &$args) {
	if (is_array($GLOBALS['pipeps-session'])) {
		foreach ($GLOBALS['pipeps-session'] as $k => $v) if (strpos($k, 'auth.') === 0) {
			unset($GLOBALS['pipeps-session'][$k]);    /// run the sequence in template mode (level 0 are bypassed)
		} $GLOBALS['pipeps-session-updated'] = TRUE;
	}
	return $args;
}


function pmod_save(&$ctx, &$args) {
	$i18n = parg($ctx, 'i18n');    /// run the sequence in template mode (level 0 are bypassed)
	$edit = parg($ctx, 'edit');
	$submit = parg($ctx, 'submit');
	$data = punescape(parg($ctx, 'data'));
	$logged = FALSE;
	if (is_array($GLOBALS['pipeps-session']) && isset($GLOBALS['pipeps-session']['auth.perm.edit']) && $GLOBALS['pipeps-session']['auth.perm.edit'] > 0) {
		$sitepath = pconfig($ctx, 'site-path');
		$siteusr = pconfig($ctx, 'site-usr');
		$oeditablepath = FALSE;
		$editablepaths = &pconfig($ctx, 'editable-path', TRUE, array(TRUE));
		if ($sitepath !== FALSE && $siteusr !== FALSE) {
			foreach ($editablepaths as $k => $v) if ($v === TRUE) {
				$editablepaths[$k] = $sitepath . '/usr-' . $siteusr . '/edit/';
			}
		}
		if ($submit == "Save" || $submit == "\u{1F5AA}") {
			if ($i18n !== FALSE && is_string($i18n) && strlen($i18n) > 0) {
				ptrywrite($oeditablepath, $editablepaths, pescape($edit) . '.' . pescape($i18n) . '.html', $data);
			} else {
				ptrywrite($oeditablepath, $editablepaths, pescape($edit) . '.html', $data);
			}
			if ($oeditablepath !== FALSE) {
				$editablepaths = array($oeditablepath);
			}
		} else if ($submit == "Delete" || $submit == "\u{1F6AE}") {
			if ($i18n !== FALSE && is_string($i18n) && strlen($i18n) > 0) {
				ptrydelete($oeditablepath, $editablepaths, pescape($edit) . '.' . pescape($i18n) . '.html', $data);
			}
			if ($oeditablepath !== FALSE) {
				$editablepaths = array($oeditablepath);
			}
		} else { }
	} return $args;
}



/// PORTLET MODULES (usually called from templates) ////////////////////////////


function pmod_include(&$ctx, &$args) {
	$i18n_enabled = (isset($args['i18n']) && $args['i18n']) ? TRUE : FALSE;    /// include another template into its position, pass the template to import as parameter
	$i18n = $i18n_enabled ? pimpl_i18n($ctx) : FALSE;
	if (!isset($args['i18n'])) {
		$i18n = parg($ctx, 'i18n');
		$i18n_enabled = $i18n !== FALSE;
	} $sitepath = pconfig($ctx, 'site-path');
	$siteusr = pconfig($ctx, 'site-usr');
	if ($sitepath !== FALSE && $siteusr !== FALSE) {
		$tplpath = $sitepath . '/usr-' . $siteusr . '/tpl/';
		if ($ctx['pipe'] === FALSE) {
			pimpl_pipe($ctx);
		}
		if (is_array($args)) foreach ($args as $key => $value) {
			$tpl = pimpl_includekv($ctx, $key, $value);
			$tplfile = FALSE;
			if (TRUE || $tpl) {
				if ($i18n_enabled && is_string($i18n) && phas($tplpath . pescape($tpl) . '.' . pescape($i18n) . '.html')) {
					$tplfile = $tplpath . pescape($tpl) . '.' . pescape($i18n) . '.html';
				} else if (phas($tplpath . pescape($tpl) . '.html')) {
					$tplfile = $tplpath . pescape($tpl) . '.html';
				} else if (phas($tplpath . $tpl)) {
					$tplfile = $tplpath . pescape($tpl);
				}
			}
			if ($tplfile) {
				$tpldata = pread($tplfile);
				$tplarray = pparse($tpldata);
				pmod_template($ctx, $tplarray);
			}
		}
	}
	return '';
}
function pimpl_includekv(&$ctx, &$key, &$value) {
	$tpl = FALSE;
	if ($value === TRUE) {
		$tpl = pimpl_includen($ctx, $key);
	} else if (is_numeric($key)) {
		$tpl = pimpl_includen($ctx, $value);
	} else { } return $tpl;
}
function pimpl_includen(&$ctx, &$name) {
	$tpl = FALSE;
	if (strlen($name) > 0 && $name[0] == '@') {
		$var = substr($name, 1);
		if (isset($ctx[$var])) {
			$tpl = $ctx[$var];
		} else {
			if ($ctx['pipe'] === FALSE) {
				pimpl_pipe($ctx);
			}
			if (isset($ctx['pipe'][$var])) {
				$tpl = $ctx['pipe'][$var];
			} else {
				$tpl = '';
			}
		}
	} else {
		$tpl = $name;
	}
	return $tpl;
}


function pmod_editable(&$ctx, &$args) {
	$i18n_enabled = (isset($args['i18n']) && $args['i18n']) ? TRUE : FALSE;    /// manages an editable area in the page
	$i18n = $i18n_enabled ? pimpl_i18n($ctx) : FALSE;
	if (!isset($args['i18n'])) {
		$i18n = parg($ctx, 'i18n');
		$i18n_enabled = $i18n !== FALSE;
	} $sitepath = pconfig($ctx, 'site-path');
	$siteusr = pconfig($ctx, 'site-usr');
	$oeditablepath = FALSE;
	$editablepaths = &pconfig($ctx, 'editable-path', TRUE, array(TRUE));
	if ($sitepath !== FALSE && $siteusr !== FALSE) {
		foreach ($editablepaths as $k => $v) if ($v === TRUE) {
			$editablepaths[$k] = $sitepath . '/usr-' . $siteusr . '/edit/';
		}
	} $arg_edit = parg($ctx, 'edit');
	$perm_edit = pget($GLOBALS['pipeps-session'], 'auth.perm.edit', FALSE);
	if ($ctx['pipe'] === FALSE) {
		pimpl_pipe($ctx);
	}
	foreach ($args as $key => $value) {
		$edit = pimpl_includekv($ctx, $key, $value);
		if ($i18n_enabled && is_string($i18n)) {
			$editdata = ptryread($oeditablepath, $editablepaths, pescape($edit) . '.' . pescape($i18n) . '.html');
		}
		if (!$editdata) {
			$editdata = ptryread($oeditablepath, $editablepaths, pescape($edit) . '.html');
			$i18n = FALSE;
		}
		if ($oeditablepath !== FALSE) {
			$editablepaths = array($oeditablepath);
		} $editarray = pparse($editdata);
		$cs = 'class';
		if ($perm_edit) {
			if ($arg_edit == $edit) {
				$pipe = pimpl_href($ctx, pbyref($i18n_enabled && is_string($i18n) ? array('i18n' => $i18n) : array()));
				pout(phtml('div', array('id' => 'wrap-edit-' . $edit, $cs => 'wrap-edit-form')));
				pout(phtml_form(array('id' => 'edit-' . $edit, $cs => 'edit-form')));
				pout(phtml('div'));
				$h = 'hidden';
				pout(phtml_input($h, '_', pcompress($pipe)));
				if ($i18n_enabled && is_string($i18n)) {
					pout(phtml_input($h, 'i18n', $i18n));
				}
				pout(phtml_input($h, 'do', 'save'));
				$s = 'submit';
				$esb = 'edit-' . $s . '-button';
				pout(phtml_input($s, array('id' => 'load-' . $edit, $cs => $esb), $s, '&#x1F4C2;'));
				pout(phtml_input($s, array('id' => 'save-' . $edit, $cs => $esb), $s, '&#x1F5AA;'));
				pout(phtml_select(array('name' => 'i18n')));
				if (!$i18n) {
					pout(phtml_option('default', ''));
				}
				pout(phtml_options( pmod_i18n($ctx, pbyref(array('select' => TRUE))) ));
				if ($i18n) {
					pout(phtml_option('default', ''));
				}
				pout(phtml_end('select'));
				pout(phtml_input($s, array('id' => 'delete-' . $edit, $cs => $esb), $s, '&#x1F6AE;'));
				pout(phtml_a('?_=' . pimpl_urlencode(pcompress(pimpl_href($ctx, pbyref(array('edit' => FALSE)))), FALSE), array('id' => 'edit-' . $edit . '-close-link', $cs => 'edit-close-link'), 'Close'));
				pout(phtml_end('div'));
				pout(phtml_area(htmlspecialchars($editdata), array('name' => 'data')));
				pout(phtml_end('form'));
				pout(phtml_end('div'));
			} else {
				pout(phtml_a('?_=' . pimpl_urlencode(pcompress(pimpl_href($ctx, pbyref(array('edit' => $edit)))), FALSE), array('id' => 'edit-' . $edit . '-link', $cs => 'edit-link'), 'Edit'));
			}
		}
		pmod_template($ctx, $editarray);
	}
	return '';
}


function pmod_template(&$ctx, &$args) {
	foreach ($args as $key => $arg) {
		if (is_array($arg)) {
			pout(pimpl_template($ctx, $arg, 9));    /// runs a previously parsed template
		} else {
			pout($arg);
		}
	}
}
function &pimpl_template(&$ctx, &$array, $maxdepth) {
	if ($maxdepth > 0) {
		$res = array();
		for ($i = count($array) - 1; $i >= 0; --$i) {
			if (is_array($array[$i])) {
				$res[] = pimpl_template($ctx, $array[$i], $res, $maxdepth - 1);
			} else {
				$res[] = $array[$i];
			}
		} $res = array_reverse($res);
		$args = implode('', $res);
		$res = pmod_run($ctx, $args);
	} else {
		$res = FALSE;
	}
	return $res;
}


function pmod_i18n(&$ctx, &$args) {
	if (isset($args['select'])) {
		$i18n_current = pimpl_i18n($ctx);    /// returns a list of languages, firstone is always currently selected one
		$res = array();
		if ($i18n_current) {
			$res[$i18n_current] = isset($args[$i18n_current]) ? $args[$i18n_current] : $i18n_current;
		} $i18n_config = pconfig($ctx, 'i18n');
		foreach ($i18n_config as $k => $v) {
			$l = $v === TRUE || $v === FALSE ? $k : $v;
			if ($l !== $i18n_current) {
				$res[$l] = isset($args[$l]) ? $args[$l] : $l;
			}
		}
		return $res;
	} else if (isset($args['options'])) {
		$args['select'] = TRUE;
		pout(phtml_options(pmod_i18n($ctx, $args)));
	} else {
		pimpl_i18n($ctx);
	}
}
function pimpl_i18n(&$ctx) {
	static $lang = FALSE;
	if ($lang === FALSE) {
		$i18n_config = pconfig($ctx, 'i18n');
		$i18n_arg = parg($ctx, 'i18n');
		if ($i18n_arg && isset($i18n_config[$i18n_arg])) {
			if ($ctx['pipe'] === FALSE) {
				pimpl_pipe($ctx);
			} $ctx['pipe']['i18n'] = $i18n_arg;
			return $lang = $i18n_arg;
		} else {
			$i18n_accept = getenv('HTTP_ACCEPT_LANGUAGE');
			if ($i18n_accept) {
				$i18n_accept = explode(',', $i18n_accept);
				for ($i = 0; $i < count($i18n_accept); ++$i) {
					if ( (((FALSE !== ($pos = strpos($i18n_accept[$i], '-'))) || (FALSE !== ($pos = strpos($i18n_accept[$i], ';')))) && ($lang = substr($i18n_accept[$i], 0, $pos)) && isset($i18n_config[$lang])) || (isset($i18n_config[$lang = $i18n_accept[$i]])) ) {
						if ($ctx['pipe'] === FALSE) {
							pimpl_pipe($ctx);
						} $ctx['pipe']['i18n'] = $lang;
						return $i18n_config[$lang] === TRUE ? ($lang = TRUE) : $lang;
					}
				}
				return $lang = TRUE;
			} else {
				return $lang = TRUE;
			}
		}
	} else {
		return $lang;
	}
}


function pmod_sequence(&$ctx, &$args) {
	$res = array();    /// run a previously parsed sequence
	foreach ($args as $key => $arg) {
		if (is_array($arg)) {
			$res = pmod_sequence($ctx, $arg);
		} else {
			if (strpos($arg, '&') !== FALSE) {
				ptoassoc($arg, $assoc);
				foreach ($assoc as $key => $value) {
					array_shift($assoc);
					$res = pimpl_run($ctx, $value === TRUE ? $key : $value, $assoc);
					break;
				}
			} else {
				$res = pimpl_run($ctx, $arg, $res);
			}
		}
	}
	return $res;
}


function pmod_href(&$ctx, &$args) {
	if (isset($args['pipe.form'])) {
		$pipe = pimpl_href($ctx, pbyref(array()));    /// generates an hyperlink merging current pipe and given new arguments
		pout(phtml_input('hidden', '_', pcompress($pipe)));
	} else if (isset($args['pipe.debug'])) {
		$pipe = pimpl_href($ctx, pbyref(array()));
		pout($pipe);
	} else {
		$str = pimpl_href($ctx, $args);
		return "?_=" . pimpl_urlencode(pcompress($str), FALSE);
	}
}
function &pimpl_href(&$ctx, &$args) {
	$assoc = array();
	if (is_array($args)) {
		foreach ($args as $key => $value) {
			if ($value === TRUE) { } else {
				$str = $key . '=' . $value;
				ptoassoc($str, $assoc);
			}
		}
	} else {
		ptoassoc($args, $assoc);
	}
	if ($ctx['pipe'] === FALSE) {
		pimpl_pipe($ctx);
	}
	if (isset($ctx['config']) && isset($ctx['config']['pipe']) && is_array($ctx['config']['pipe'])) {
		$default_pipe = &$ctx['config']['pipe'];
	} else {
		$default_pipe = array();
	} $link = array();
	foreach ($ctx['pipe'] as $key => $value) {
		if (is_string($key) && strlen($key) > 0 && !isset($assoc[$key]) && !(isset($default_pipe[$key]) && $default_pipe[$key] == $value) && $key[0] >= 'A' && $key[0] <= 'z') {
			$link[$key] = $value;
		}
	}
	foreach ($assoc as $key => $value) {
		if (is_string($key) && !(isset($default_pipe[$key]) && ($default_pipe[$key] == $value))) {
			$link[$key] = $value;
		}
	}
	if (isset($GLOBALS['pipeps-cookies']) && isset($GLOBALS['pipeps-cookies']['sid']) && isset($link['sid']) && ($GLOBALS['pipeps-cookies']['sid'] == $link['sid'])) {
		unset($link['sid']);
	}
	ksort($link);
	$str = '';
	ptostr($link, $str);
	return $str;
}
function pimpl_urlencode($text, $iskey = TRUE) {
	if ($iskey) {
		return str_replace(" ", "+", str_replace("=", "%3D", str_replace("+", "%2B", str_replace("&", "%26", str_replace("%", "%25", $text)))));
	} else {
		return str_replace(" ", "+", str_replace("+", "%2B", str_replace("&", "%26", str_replace("%", "%25", $text))));
	}
}


function pmod_run(&$ctx, &$args, $opt_separator = ' ') {
	if (is_array($args)) { } else {
		ptoassoc($args, $assoc, $opt_separator);    /// prepares parameters and then invoke a module
		foreach ($assoc as $key => $value) {
			array_shift($assoc);
			if ($value === TRUE) {
				$arg = FALSE;
				$mod = trim($key);
			} else {
				$arg = trim($key);
				$argpos = strpos($args, $arg);
				$eqpos = strpos($args, '=', $argpos + strlen($arg));
				$args = substr($args, $eqpos + 1);
				$mod = trim($value);
			} $modlen = strlen($mod);
			if ($modlen > 0) {
				if ($mod[0] == '@') {
					$var = substr($mod, 1);
					if (strpos($var, 'arg.') === 0) {
						if ($ctx['pipe'] === FALSE) {
							pimpl_pipe($ctx);
						} $var = substr($var, 4);
						$res = isset($ctx['pipe'][$var]) ? $ctx['pipe'][$var] : '';
					} else {
						if (isset($ctx[$var])) {
							$res = $ctx[$var];
						} else {
							if ($ctx['pipe'] === FALSE) {
								pimpl_pipe($ctx);
							}
							if (isset($ctx['pipe'][$var])) {
								$res = $ctx['pipe'][$var];
							} else {
								$res = '';
							}
						}
					}
				} else if ($mod[0] == '"') {
					if ($mod[$modlen - 1] == '"') {
						$res = substr($mod, 1, $modlen - 2);
					} else {
						$res = substr($mod, 1);
					}
				} else {
					$res = pimpl_run($ctx, $mod, $assoc, $args);
				}
			}
			if ($arg !== FALSE) {
				if ($arg[0] == '@' && strlen($arg) > 0) {
					$var = substr($arg, 1);
					if (strpos($var, 'arg.') === 0) {
						$var = substr($var, 4);
						if ($ctx['pipe'] === FALSE) {
							pimpl_pipe($ctx);
						} $ctx['pipe'][$var] = $res;
					} else {
						$ctx[$var] = $res;
					} $res = "";
				}
			}
			return $res;
		}
	}
	return pimpl_run($ctx, $mod, $args);
}
function &pimpl_run(&$ctx, $mod, $args = FALSE, $ifnot = FALSE) {
	static $mods = array();
	if (isset($mods[$mod])) {
		$res = $mods[$mod]($ctx, $args);
		return $res;
	} else {
		$dynmod = 'pmod_' . $mod;
		if (is_callable($dynmod)) {
			$mods[$mod] = $dynmod;
			$res = $dynmod($ctx, $args);
			return $res;
		} else {
			$sitepath = pconfig($ctx, 'site-path');
			$siteusr = pconfig($ctx, 'site-usr');
			if ($sitepath !== FALSE && $siteusr !== FALSE && phas($sitepath . '/usr-' . $siteusr . '/mod/mod_' . $mod . '.php')) {
				ob_start();
				include($sitepath . '/usr-' . $siteusr . '/mod/mod_' . $mod . '.php');
				$contents = ob_get_contents();
				ob_end_clean();
				return $contents;
			}
			plog("{" . date('Y-m-d H:i:s O') . ' warning: ' . "could not find callable " . $mod . ", or callable " . $dynmod . ", or file '" . ('/mod/mod_' . $mod . '.php') . "' }\n");
			return $ifnot;
		}
	}
}


function pmod_vm(&$ctx, &$args) {
	static $version = FALSE;    /// get underlying vm/platform version, arguments are not used or modified but must be filled with any variable for mod-signature compatibility
	if ($version === FALSE) {
		$version_array = explode('.', phpversion());
		$res = $version_array[1] + $version_array[2] / 1000000.0;
		while ($res > 1.0) {
			$res /= 10.0;
		} $version = $res + $version_array[0];
	}
	return $version;
}



/// VIRTUAL I/O METHODS (data from blocks, tables or others) ///////////////////


function pescape($input = FALSE) {
	static $handleset = array();    /// a filter to use with names or data got from the outside/as parameters that escape filenames, paths and data for conditions so no injections can be performed
	$bypass = FALSE;
	$dummy = FALSE;
	$res = pimpl_handle($handleset, $input, $dummy, $dummy, $dummy, $bypass);
	if ($res !== $bypass) {
		return $res;
	}
	return str_replace("/", "_", str_replace("\\", "_", str_replace("'", "_", str_replace("\"", "_", str_replace("..", "_", $input)))));
}

function punescape($input = FALSE) {
	static $magic_quotes = FALSE;    /// a filter to use with contents that come from arguments that may be escaped by php automagically
	if ($magic_quotes === FALSE) {
		$magic_quotes = ini_get('magic_quotes_gpc') ? 1 : 0;
	} $retv = ($magic_quotes > 0) ? stripslashes($input) : $input;
	return $retv;
}


function presize($uri, $newsize) {
	static $handleset = array();    /// changes the size of the provided file to a new one
	$bypass = FALSE;
	$dummy = FALSE;
	$res = pimpl_handle($handleset, $uri, $dummy, $dummy, $dummy, $bypass);
	if ($res !== $bypass) {
		return $res;
	}
	if (is_array($uri) && isset($uri['handle'])) {
		return ftruncate($uri['handle'], $newsize);
	}
	return FALSE;
}


function phas($uri = FALSE, $only_files = TRUE) {
	static $handleset = array();    /// checks if a given file/resource exists, if you need to check folders pass $only_files as FALSE
	$bypass = FALSE;
	$dummy = FALSE;
	$res = pimpl_handle($handleset, $uri, $dummy, $dummy, $dummy, $bypass);
	if ($res !== $bypass) {
		return $res;
	}
	if (is_array($uri)) {
		$uri = $uri['uri'];
	}
	return $only_files ? is_file($uri) : file_exists($uri);
}

function penum($uri = FALSE, $resourcename_pattern = FALSE, $resourcetype_pattern = FALSE, $limit = -1) {
	static $handleset = array();    /// enumerate files/resources in the given uri/url/path, limit can be -1 (no limit), 0 (return first element) or greater (return list)
	$bypass = FALSE;
	$dummy = FALSE;
	$res = pimpl_handle($handleset, $uri, $resourcename_pattern, $resourcetype_pattern, $limit, $bypass);
	if ($res !== $bypass) {
		return $res;
	} else {
		$res = FALSE;
	}
	if (is_array($uri)) {
		$uri = $uri['uri'];
	}
	if ($handle = @opendir($uri)) {
		$res = pimpl_henum($handle, $uri, $resourcename_pattern, $resourcetype_pattern, $limit);
		closedir($handle);
	}
	return $res;
}
function pimpl_henum($handle, $uri, $resourcename_pattern, $resourcetype_pattern, $limit) {
	$res = array();
	for (;;) {
		if ($fn = readdir($handle)) {
			if (!$resourcetype_pattern || pmatch($ft = filetype($uri . '/' . $fn), $resourcetype_pattern)) {
				if (!$resourcename_pattern || pmatch($fn, $resourcename_pattern)) {
					if ($limit === 0) {
						return $fn;
					} else {
						$res[] = $fn;
						if ($limit >= 0 && count($res) >= $limit) {
							return $res;
						}
					}
				}
			}
		} else {
			return $res;
		}
	}
}

function &pread($uri, $offset = -1, $size = -1) {
	static $handleset = array();    /// read data from the given file/resource $uri (that can be a string or an array with options like locks...); optionally can receive an $offset for the position and the $size to read (think beyond files: some virtual devices may allow non-numeric $offsets on their mounted resources); returns the read bytes or an array for pass as $uri to subsequent calls
	$bypass = FALSE;
	$dummy = FALSE;
	$res = pimpl_handle($handleset, $uri, $offset, $size, $dummy, $bypass);
	if ($res !== $bypass) {
		return $res;
	} $handle = FALSE;
	$fsize = FALSE;
	$aunlock = FALSE;
	if (($h = pimpl_hopen($handle, $uri, FALSE, $offset, $fsize, $aunlock)) !== FALSE) {
		if (isset($GLOBALS['DEBUG_pread_wait']) && $GLOBALS['DEBUG_pread_wait'] > 0) {
			psleep($GLOBALS['DEBUG_pread_wait']);
		}
		if ($offset >= 0) {
			fseek($h, $offset);
		} $bufflen = 0;
		$buff = array();
		while (!feof($h) && ($size < 0 || $bufflen < $size)) {
			$buffi = fread($h, $size < 0 ? 4096 : $size - $bufflen);
			$bufflen += strlen($buffi);
			$buff[] = $buffi;
		} $data = implode('', $buff);
		if ($handle !== FALSE) {
			return pbyref(array('handle' => $h, 'uri' => $uri, 'lock' => $aunlock, 'data' => $data, 'size' => $fsize === TRUE ? pimpl_hgetsize($h) : $fsize, 'eof' => feof($h)));
		} else {
			if ($aunlock == 2) {
				flock($h, 3);
			}
			pimpl_hclose($h);
			return $data;
		}
	} else {
		return pbyref(FALSE);
	}
}
function pimpl_hgetsize(&$handle) {
	$pos = ftell($handle);
	fseek($handle, 0, SEEK_END);
	$filesize = ftell($handle);
	fseek($handle, $pos);
	return $filesize;
}

function pwrite($uri, &$data, $offset = -1, $disable_log = FALSE) {
	static $handleset = array();    /// write the provided $data into a given file/resource $uri (that can be a string or an array with options like locks...); optionally $offset specifies where to write, or it can be -1 so will write in previous/default pointer position (and no seek will be performed, but think beyond files: some virtual devices may allow non-numeric $offsets on their mounted resources); returns wrote bytes or an array for pass as $uri to subsequent calls
	$bypass = FALSE;
	$dummy = FALSE;
	$res = pimpl_handle($handleset, $uri, $offset, $data, $dummy, $bypass);
	if ($res !== $bypass) {
		return $res;
	} $handle = FALSE;
	$fsize = FALSE;
	$aunlock = FALSE;
	if (($h = pimpl_hopen($handle, $uri, TRUE, $offset, $fsize, $aunlock, $disable_log)) !== FALSE) {
		if (isset($GLOBALS['DEBUG_pwrite_wait']) && $GLOBALS['DEBUG_pwrite_wait'] > 0) {
			psleep($GLOBALS['DEBUG_pwrite_wait']);
		}
		if ($offset >= 0) {
			fseek($h, $offset);
		} $wrote = fwrite($h, $data);
		if ($handle !== FALSE) {
			return pbyref(array('handle' => $h, 'uri' => $uri, 'lock' => $aunlock, 'wrote' => $wrote, 'size' => $fsize === TRUE ? pimpl_hgetsize($h) : $fsize));
		} else {
			if ($aunlock == 2) {
				flock($h, 3);
			}
			pimpl_hclose($h);
			return $wrote;
		}
	} else {
		return FALSE;
	}
}

function pdelete($uri) {
	static $handleset = array();    /// deletes the provided file/resource
	$bypass = FALSE;
	$dummy = FALSE;
	$res = pimpl_handle($handleset, $uri, $dummy, $dummy, $dummy, $bypass);
	if ($res !== $bypass) {
		return $res;
	}
	if (is_array($uri)) {
		if (isset($uri['handle'])) {
			$uri = $uri['uri'];
		}
	}
	return @unlink($uri);
}


function &ptryread(&$result, $folders, $uri, $offset = -1, $size = -1) {
	if (is_array($folders)) {
		foreach ($folders as $folder) {
			$retv = pread(pimpl_tryuri($folder, $uri), $offset, $size);    /// tries to execute pread on several folder and when read is successful stops and returns the value returned by
			if ($retv !== FALSE) {
				$result = $folder;
				break;
			}
		}
		return $retv;
	} else {
		return pread(pimpl_tryuri($folder, $uri), $offset, $size);
	}
}

function ptrywrite(&$result, $folders, $uri, &$data, $offset = -1, $disable_log = FALSE) {
	if (is_array($folders)) {
		foreach ($folders as $folder) {
			$retv = pwrite(pimpl_tryuri($folder, $uri), $data, $offset, $disable_log);    /// tries to execute pwrite on several folders and when write is successful stops and returns the value returned by
			if ($retv !== FALSE) {
				$result = $folder;
				break;
			}
		}
		return $retv;
	} else {
		return pwrite(pimpl_tryuri($folders, $uri), $data, $offset, $disable_log);
	}
}
function pimpl_tryuri($folder, $uri) {
	if (is_array($uri)) {
		if (isset($uri['uri'])) {
			$retv = array();
			foreach ($uri as $k => $v) $retv[$k] = $v;
			$retv['uri'] = puri($folder, TRUE) . $uri['uri'];
			return $retv;
		} else {
			return $uri;
		}
	} else {
		return puri($folder, TRUE) . $uri;
	}
}

function ptrydelete(&$result, $folders, $uri) {
	if (is_array($folders)) {
		foreach ($folders as $folder) {
			$retv = pdelete(pimpl_tryuri($folder, $uri));    /// tries to execute pdelete on several folders and when delete is successful stops and returns the value returned by
			if ($retv !== FALSE) {
				$result = $folder;
				break;
			}
		}
		return $retv;
	} else {
		return pdelete(pimpl_tryuri($folders, $uri));
	}
}



/// OTHER METHODS (can be used from modules) ///////////////////////////////////


function &pbyref($value) {
	return $value;    /// returns a non-variable expresion as a variable for use in by reference arguments
}


function phtml($tag = 'html', $attrs = FALSE, $contents = FALSE) {
	if (is_array($tag)) {
		return "";    /// generate html code for the given tag and attributes
	} else {
		return '<' . $tag . (is_string($attrs) ? $attrs : phtml_attrs($attrs)) . ($contents === FALSE ? ' />' : ('>' . $contents . '</' . $tag . '>'));
	}
}
function phtml_attrs($attrs = FALSE, $defaults = FALSE) {
	$str = "";
	if (is_array($attrs)) foreach ($attrs as $k => $v) {
		if ($v === TRUE) {
			$str .= " " . $k . "";
		} else if ($v !== FALSE) {
			$str .= " " . $k . "='" . $v . "'";
		}
	}
	if (is_array($defaults)) foreach ($defaults as $k => $v) if (!isset($attrs[$k])) {
			if ($v === TRUE) {
				$str .= " " . $k . "";
			} else if ($v !== FALSE) {
				$str .= " " . $k . "='" . $v . "'";
			}
		}
	return $str;
}
function phtml_form($attrs = FALSE, $contents = FALSE) {
	return phtml('form', phtml_attrs($attrs, array('method' => 'post', 'enctype' => 'multipart/form-data')), $contents);
}
function phtml_a($href = FALSE, $attrs = FALSE, $contents = FALSE) {
	return phtml('a', phtml_attrs($attrs, array('href' => $href)), $contents);
}
function phtml_input($type = FALSE, $attrs = FALSE, $name = FALSE, $value = FALSE) {
	if (is_array($type)) {
		$value = $name;
		$name = $attrs;
		$attrs = $type;
		$type = 'text';
	}
	if (is_string($attrs)) {
		$value = $name;
		$name = $attrs;
		$attrs = array();
	}
	return phtml('input', phtml_attrs($attrs, array('type' => $type, 'name' => $name, 'value' => $value)));
}
function phtml_area($value = '', $attrs = FALSE) {
	return phtml('textarea', phtml_attrs($attrs), $value);
}
function phtml_select($attrs = FALSE) {
	return phtml('select', phtml_attrs($attrs));
}
function phtml_option($label = '', $key = FALSE, $isdefault = FALSE) {
	return phtml('option', phtml_attrs(array('value' => $key, 'default' => $isdefault)), $label);
}
function phtml_options($keylabels = FALSE) {
	$opts = '';
	foreach ($keylabels as $key => $label) $opts .= phtml_option($label, $key);
	return $opts;
}
function phtml_end($tag = 'html') {
	return "</" . $tag . ">";
}


function ptraceall($levelfrom = 1, $trace = FALSE) {
	return ptrace($levelfrom, $trace, -1);    /// selects contents from a php backtrace and transforms into one trace line in java-style
}
function ptrace($levelfrom = 1, $trace = FALSE, $levelcount = 1) {
	if (!$trace) {
		$trace = debug_backtrace();
	}
	if (is_array($trace)) {
		$out = '';
		if (!($levelcount > 0)) {
			$levelcount = count($trace) - $levelfrom;
		}
		for ($i = $levelfrom; $i < $levelfrom + $levelcount; ++$i) if (isset($trace[$i])) {
				$out .= pimpl_trace($i, $trace);
			}
		return $out;
	} else {
		return NULL;
	}
}
function pimpl_trace(&$level, &$trace) {
	$function = (isset($trace[$level + 1]) && $trace[$level + 1]['function'] !== 'include') ? ((isset($trace[$level + 1]['class']) ? ($trace[$level + 1]['class'] . '.') : '') . $trace[$level + 1]['function']) : '';
	if (isset($trace[$level]['file'])) {
		$file = "(" . puri($trace[$level]['file'], FALSE) . ":" . $trace[$level]['line'] . ")";
	} else {
		$file = '';
	}
	return "\n\t@" . $function . $file;
}


function &puri($unnormalized_uri, $addslash = TRUE) {
	$uri = trim(str_replace("\\", '/', $unnormalized_uri));    /// converts from a unnormalized uri/url/path to a pipeps normalized uri (with / and with or without a additional / at the end depending on $addslash argument)
	$uri = (strlen($uri) === 0) ? '' : (($uri[strlen($uri) - 1] === '/') ? ($addslash ? $uri : substr($uri, 0, -1)) : ($addslash ? $uri . '/' : $uri));
	return $uri;
}


function &pcontext(&$ctx) {
	$newctx = array();    /// creates a new child context from the given $ctx existing context
	$newctx['config'] = &$ctx['config'];
	$newctx['pipe'] = &$ctx['pipe'];
	$newctx['out'] = &$ctx['out'];
	return $newctx;
}


function &pget(&$ctx, $key, $ifnot = FALSE) {
	if (isset($ctx[$key])) {
		return $ctx[$key];    /// reads a $key/property/variable from $ctx or associative object, and if not present returns $ifnot value
	} else {
		return $ifnot;
	}
}
function &pimpl_static(&$ctx, $key, $newvalue) {
	$test = FALSE;
	return $test;
}


function &ptoassoc(&$str, &$assoc, $separator = '&') {
	if (!is_array($assoc)) {
		$assoc = array();    /// convert a string into an associative object
	} $array = explode($separator, $str);
	foreach ($array as $part) {
		if (($eqpos = strrpos($part, '=')) !== FALSE) {
			$assoc[urldecode(substr($part, 0, $eqpos))] = urldecode(substr($part, $eqpos + 1));
		} else {
			$assoc[urldecode($part)] = TRUE;
		}
	}
	return $assoc;
}


function &ptostr(&$assoc, &$str) {
	$i = strlen($str) > 0 ? 0 : -1;    /// convert an associative object to a string
	foreach ($assoc as $key => $value) {
		++$i;
		if ($i > 0) {
			$str .= '&' . pimpl_urlencode($key) . '=' . pimpl_urlencode($value, FALSE);
		} else {
			$str .= pimpl_urlencode($key) . '=' . pimpl_urlencode($value, FALSE);
		}
	}
	return $str;
}


function &parg(&$ctx, $key, $ifnot = FALSE) {
	$res = $ifnot;    /// gets the input argument given by $key
	if (isset($GLOBALS['pipeps-mode'])) {
		switch ($GLOBALS['pipeps-mode']) {
		default:
		case 1:
		case 2:
			if ($GLOBALS['pipeps-args'] === FALSE) {
				pimpl_args($ctx);
			}
			if (isset($GLOBALS['pipeps-args'][$key])) {
				$res = $GLOBALS['pipeps-args'][$key];
			} else if (isset($GLOBALS['pipeps-cookies'][$key])) {
				$res = $GLOBALS['pipeps-cookies'][$key];
			} else if (is_array($ctx)) {
				if ($ctx['pipe'] === FALSE) {
					pimpl_pipe($ctx);
				}
				if (isset($ctx['pipe'][$key])) {
					$res = $ctx['pipe'][$key];
				}
			}
			break;
		case 3:
			if (isset($GLOBALS['pipeps-args'][$key])) {
				$res = $GLOBALS['pipeps-args'][$key];
			} else if (isset($GLOBALS['pipeps-cookies'][$key])) {
				$res = $GLOBALS['pipeps-cookies'][$key];
			} else {
				if (!isset($GLOBALS['pipeps-context']['argv'])) {
					$GLOBALS['pipeps-context']['argv'] = array();
					$lastarg = FALSE;
					$prevarg = FALSE;
					foreach ($_SERVER['argv'] as $arg) {
						if ($arg[0] == '-') {
							if ($lastarg !== FALSE) {
								$GLOBALS['pipeps-context']['argv'][$lastarg] = TRUE;
							} $prevarg = $lastarg = substr($arg, 1);
						} else {
							if ($lastarg !== FALSE) {
								$GLOBALS['pipeps-context']['argv'][$lastarg] = $arg;
								$lastarg = FALSE;
							} else if ($prevarg !== FALSE) {
								$GLOBALS['pipeps-context']['argv'][$prevarg] .= ' ' . $arg;
							}
						}
					}
					if ($lastarg !== FALSE) {
						$GLOBALS['pipeps-context']['argv'][$lastarg] = TRUE;
					}
				}
				if (isset($GLOBALS['pipeps-context']['argv'][$key])) {
					$res = $GLOBALS['pipeps-context']['argv'][$key];
				}
			}
			break;
		}
	}
	return $res;
}


function &pin(&$ctx, $key, $ifnot = FALSE, $query = FALSE) {
	static $inhandle = FALSE;    /// gets an input from arguments or if not present and in cli mode asks interactively
	if (isset($GLOBALS['pipeps-mode'])) {
		$res = parg($ctx, $key, $ifnot);
		switch ($GLOBALS['pipeps-mode']) {
		case 3:
			if ($res === $ifnot) {
				if ($inhandle === FALSE) {
					$inhandle = fopen('php://stdin', 'rb');
				}
				if ($inhandle) {
					$res = trim(fgets($inhandle, 1024));
				}
				if ($res === '') {
					$res = $ifnot;
				}
			}
			break;
		default:
			if ($key === FALSE) {
				$f = fopen('php://input', 'rb');
				if ($f) {
					$res = '';
					while (!feof($f)) {
						$res .= fread($f, 8192);
					}
					fclose($f);
				}
			}
			break;
		}
	} else {
		$res = $ifnot;
	}
	return $res;
}


function pout($value) {
	if (isset($GLOBALS['pipeps-mode'])) {
		switch ($GLOBALS['pipeps-mode']) {
		default:
		case 1:
			$GLOBALS['pipeps-context']['out'][] = $value;    /// writes a value or text to the pipeps std output (valid for buffer or flush pipeps modes)
			break;
		case 2:
			echo($value);
			break;
		case 3:
			echo($value);
			break;
		}
	}
}


function plog($value, $noreentry = FALSE) {
	static $logmode = 0;    /// writes a value or text to the pipeps error/log output (valid for buffer or flush pipeps modes)
	static $loghandle = FALSE;
	static $reentry = FALSE;
	static $reentry_buffer = FALSE;
	if ($reentry || $noreentry) {
		if ($reentry_buffer === FALSE) {
			$reentry_buffer = array();
		} $reentry_buffer[] = $value;
		return FALSE;
	} $reentry = TRUE;
	if ($value === FALSE) {
		if ($loghandle === FALSE) {
			$res = FALSE;
		} else {
			$res = pimpl_hclose($loghandle);
			$loghandle = FALSE;
		} $reentry = FALSE;
		return $res;
	}
	if ($logmode === 0) {
		if (isset($GLOBALS['pipeps-context']) && isset($GLOBALS['pipeps-context']['config']) && isset($GLOBALS['pipeps-context']['config']['log-path'])) {
			if (is_array($GLOBALS['pipeps-context']['config']['log-path'])) {
				foreach ($GLOBALS['pipeps-context']['config']['log-path'] as $logpath) {
					if (($logmode = pimpl_log($loghandle, puri($logpath, TRUE) . 'pipeps.log')) > 0) {
						break;
					}
				}
			} else {
				$logmode = pimpl_log($loghandle, puri($GLOBALS['pipeps-context']['config']['log-path'], TRUE) . 'pipeps.log');
			}
		}
		if ($logmode === 0) {
			ob_end_clean();
			$GLOBALS['pipeps-context']['out'] = array("Error 500: cannot write to log file.");
			die();
		}
	}
	if ($reentry_buffer !== FALSE) {
		$tmp_buffer = $reentry_buffer;
		$reentry_buffer = FALSE;
		if ($logmode === 3) {
			pwrite($loghandle, pbyref(implode('', $tmp_buffer)), -1, TRUE);
		} else if ($logmode === 2) {
			foreach ($tmp_buffer as $tmp) error_log($tmp);
		} else if ($logmode === 1) { }
	} if ($logmode === 3) {
		$GLOBALS['pipeps-logcount'] = (isset($GLOBALS['pipeps-logcount']) ? $GLOBALS['pipeps-logcount'] : 0) + 1;
		pwrite($loghandle, $value, -1, TRUE);
	} else if ($logmode === 2) {
		error_log($value);
	} else if ($logmode === 1) { } $reentry = FALSE;
}
function plogb($value) {
	return plog($value, TRUE);
}
function pimpl_log(&$loghandle, $logfile) {
	if ($logfile === TRUE) {
		return 2;
	} else if ($logfile === FALSE) {
		return 1;
	} else {
		$loghandle = pwrite(array('uri' => $logfile, 'append' => TRUE, 'handle' => TRUE), pbyref(''));
		if ($loghandle) {
			return 3;
		} else {
			return 0;
		}
	}
}

function pdump($var) {
	return str_replace('  ', ' ', str_replace('   ', ' ', str_replace("\t", ' ', str_replace("\r", ' ', str_replace("\n", ' ', var_export($var, TRUE))))));
}


function &pparse($in = '', $Btag = '{{', $Etag = '}}') {
	$out = array();    /// parses templates into array-sequence-format
	$outL = 0;
	$out[$outL] = array();
	$inL = strlen($in);
	$inP = 0;
	$BtagL = strlen($Btag);
	$BtagP = -$BtagL;
	$EtagL = strlen($Etag);
	$EtagP = -$EtagL;
	for (;;) {
		if (($BtagP < $inP) && (($BtagP = strpos($in, $Btag, $BtagP + $BtagL)) === FALSE)) {
			$BtagP = $inL;
		}
		if (($EtagP < $inP) && (($EtagP = strpos($in, $Etag, $EtagP + $EtagL)) === FALSE)) {
			$EtagP = $inL;
		}
		if (($BtagP < $inL) && ($BtagP < $EtagP)) {
			if ($BtagP - $inP > 0) {
				$out[$outL][] = substr($in, $inP, $BtagP - $inP);
			} ++$outL;
			$inP = $BtagP + $BtagL;
			if ($outL > 0) {
				$out[$outL - 1][] = array();
				$insertionindex = count($out[$outL - 1]) - 1;
				$out[$outL] = &$out[$outL - 1][$insertionindex];
			}
		} else if (($EtagP < $inL) && ($EtagP < $BtagP)) {
			if ($EtagP - $inP > 0) {
				$out[$outL][] = substr($in, $inP, $EtagP - $inP);
			} --$outL;
			$inP = $EtagP + $EtagL;
		} else {
			break;
		}
	}
	if ($inL - $inP > 0) {
		$out[$outL][] = substr($in, $inP, $inL - $inP);
	}
	return $out[0];
}


function &pconfig(&$ctx, $key, $value = TRUE, $ifnot = FALSE) {
	if (is_string($value)) {
		if (isset($ctx['config'][$key])) {
			$res = &$ctx['config'][$key];    /// get or set a configuration value, to get $value should be TRUE, to set $value should be a string, if is a get and $key is not found $ifnot will be returned
		} else {
			$res = FALSE;
		} $ctx['config'][$key] = $value;
		return $res;
	} else if ($value === TRUE) {
		if (isset($ctx['config'][$key])) {
			return $ctx['config'][$key];
		} else {
			return $ifnot;
		}
	} else if ($value === FALSE) {
		if (isset($ctx['config'][$key])) {
			unset($ctx['config'][$key]);
		}
		return TRUE;
	} else {
		return FALSE;
	}
}


function pmatch($text, $pattern = TRUE) {
	$matches = array();    /// look for matches between the $text and $pattern
	if (is_string($pattern)) {
		switch (substr($pattern, 0, 1)) {
		case '!':
			switch (substr($pattern, 1, 1)) {
			case '/':
				return preg_match(substr($pattern, 1), $text, $matches) != 1 ? TRUE : FALSE;
			case ':':
				return !fnmatch(substr($pattern, 2), $text) ? array(substr($pattern, 2), substr($pattern, 2)) : FALSE;
			case '=':
				return substr($pattern, 2) != $text;
			default:
				$matches = strpos($text, $pattern);
				return $matches === FALSE ? FALSE : array(-1, $pattern);
			}
			break;
		case '/':
			return preg_match(substr($pattern, 0), $text, $matches) == 1 ? $matches : FALSE;
		case ':':
			return fnmatch(substr($pattern, 1), $text) ? array($text, $text) : FALSE;
		case '=':
			return substr($pattern, 1) == $text ? array(substr($pattern, 1), substr($pattern, 1)) : FALSE;
		case '|':
			return array(substr($pattern, 1), substr($pattern, 1));
		default:
			$matches = strpos($text, $pattern);
			return $matches !== FALSE ? array($matches, $pattern) : FALSE;
		}
	} else if (is_array($pattern)) {
		foreach ($pattern as $key => $part) {
			$matches = pmatch($text, $part);
			if ($matches !== FALSE) {
				return $matches;
			}
		}
	}
	return FALSE;
}


function psleep($secondsf) {
	if ($secondsf > 1.0) {
		sleep(floor($secondsf));    /// sleep thread for $secondsf seconds (float value), because on old versions PHP usleep does not work, sleep is used for integer value before usleep
		$secondsf -= floor($secondsf);
	}
	usleep($secondsf * 1000000.0);
}


function prlee_compress($str, $maxdepth = 19) {
	$len = strlen($str);    /// compress into a RLEE string
	if (!($len > 0)) {
		return '';
	} $escape0 = "\0";
	$escape1 = "\1";
	$stats = array();
	for ($i = 0; $i < 256; ++$i) {
		$stats[$i] = 0;
	}
	for ($i = 0; $i < $len; ++$i) {
		++$stats[ord($str[$i])];
	} $min = $len;
	for ($i = 0; $i < 256; ++$i) if ($stats[$i] < $min) {
			$min = $stats[$i];
			$escape0 = chr($i);
		} $min = $len;
	for ($i = 0; $i < 256; ++$i) if ($stats[$i] < $min && chr($i) != $escape0) {
			$min = $stats[$i];
			$escape1 = chr($i);
		} $compressed = ($escape0 ^ chr(255)) . ($escape1 ^ chr(255));
	$ctr = 1;
	$prev = $str[0];
	$next = ~$prev;
	$patterns = pimpl_rlee($str, $maxdepth);
	$patterni = 0;
	$patternp = $patterni < count($patterns) ? $patterns[$patterni][0] : -1;
	for ($i = 1; $i <= $len; ++$i) {
		if ($i < $len) {
			$next = $str[$i];
		} else {
			$next = ~$next;
		}
		if ($i === $patternp) {
			$i += $patterns[$patterni][2] * $patterns[$patterni][3];
			++$patterni;
			$patternp = $patterni < count($patterns) ? $patterns[$patterni][0] : -1;
		} else if ($next === $prev && $ctr < 32767) {
			++$ctr;
		} else {
			if ($ctr > 127) {
				$compressed .= $escape0 . chr($ctr & 127) . chr($ctr >> 7) . $prev;
			} else if ($ctr > 1) {
				if ($ctr == 2 && $prev != $escape0 && $prev != $escape1) {
					$compressed .= $prev . $prev;
				} else {
					$compressed .= $escape0 . chr($ctr) . $prev;
				}
			} else if ($ctr == 1) {
				if ($prev == $escape0) {
					$compressed .= $escape0 . "\0";
				} else if ($prev == $escape1) {
					$compressed .= $escape1 . "\0";
				} else {
					$compressed .= $prev;
				}
			} $ctr = 1;
		} $prev = $next;
	}
	return $compressed;
}
function pimpl_rlee($str, $maxdepth = 19) {
	return array(-1, 0, 0, 0);
}

function prlee_decompress($compressed) {
	$len = strlen($compressed);    /// decompress a RLEE string
	if (!($len > 2)) {
		return '';
	} $escape0 = $compressed[0] ^ chr(255);
	$escape1 = $compressed[1] ^ chr(255);
	$str = '';
	for ($i = 2; $i < $len; ++$i) {
		$next = $compressed[$i];
		if ($next === $escape0) {
			$next = $compressed[++$i];
			$nexti = ord($next);
			if ($nexti == 0) {
				$Str .= $escape0;
			} else {
				if ($nexti > 127) {
					$msb = $compressed[++$i];
					$ctr = ($nexti & 127) | ($msb << 7);
				} else if ($nexti > 0) {
					$ctr = $nexti;
				} $next = $compressed[++$i];
				for ($j = 0; $j < $ctr; ++$j) {
					$str .= $next;
				}
			}
		} else if ($next === $escape1) {
			$next = $compressed[++$i];
			$nexti = ord($next);
			if ($nexti == 0) {
				$Str .= $escape1;
			} else {
				if ($nexti > 127) {
					$msb = $compressed[++$i];
					$ctr = ($nexti & 127) | ($msb << 7);
				} else if ($nexti > 0) {
					$ctr = $nexti;
				} $block = '';
				$next = $compressed[++$i];
				while ($next !== $escape0) {
					$block .= $next;
					$next = $compressed[++$i];
				}
				for ($j = 0; $j < $ctr; ++$j) {
					$str .= $block;
				}
			}
		} else {
			$str .= $next;
		}
	}
	return $str;
}


function &pfetch($url, &$headers, $options = FALSE) {
	static $curl = FALSE;    /// fetch an external url, put in headers parameter whose to send and read whose received, $options['bypass'] as TRUE makes output printed directly to output (disables buffering if enabled)
	if ($curl === FALSE) {
		$curl = function_exists('curl_init') && function_exists('curl_setopt') && function_exists('curl_exec') && function_exists('curl_close') ? 1 : 0;
	} $parts = pimpl_urlsplit($url);
	if ($curl) {
		$h = curl_init();
		curl_setopt($h, CURLOPT_URL, $url);
		curl_setopt($h, CURLOPT_HEADER, 0);
		if (isset($options['post'])) {
			curl_setopt($h, CURLOPT_POST, 1);
			curl_setopt($h, CURLOPT_POSTFIELDS, $options['post']);
		}
		if ($parts[0] == 'https') {
			curl_setopt($h, CURLOPT_SSL_VERIFYPEER, 0);
		}
		curl_setopt($h, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($h);
		curl_close($h);
		return $data;
	} else {
		$h = fsockopen($parts[0] == 'https' ? 'ssl://' . $parts[1] : $parts[1], $parts[2] !== FALSE ? $parts[2] : ($parts[0] == 'https' ? 443 : 80), $errno, $errstr);
		if (!$h) {
			plog("{Error pfetch failed to open " . $url . " because " . $errstr . " (" . $errno . ") }\n");
			$data = FALSE;
			return $data;
		} else {
			$blocksize = 1024;
			$alldata = array();
			fwrite($h, (isset($options['post']) ? "GET " : "POST ") . ($parts[3] ? $parts[3] : '/') . " HTTP/1.1\r\nHost: " . ($parts[1] ? $parts[1] : 'localhost') . "\r\nConnection: Close\r\n\r\n");
			while (!feof($h)) {
				if (($data = fread($h, $blocksize)) !== FALSE) {
					$alldata[] = $data;
				}
			}
			fclose($h);
			$data = implode('', $alldata);
			return $data;
		}
	}
}
function pimpl_urlsplit($url) {
	$res = array();
	$p1 = strpos($url, '#');
	$url = $p1 === FALSE ? trim($url) : trim(substr($url, 0, $p1));
	$urll = strlen($url);
	$p0 = strpos($url, "://");
	if ($p0 === FALSE) {
		$res[0] = FALSE;
		$p0 = substr($url, 0, 2) == "//" ? 2 : 0;
	} else {
		$res[0] = substr($url, 0, $p0);
		$p0 += 3;
	} $p1 = strpos($url, "/", $p0);
	$p2 = strpos($url, ":", $p0);
	$res[1] = substr($url, $p0, $p1 === FALSE ? ($p2 === FALSE ? $urll - $p0 : $p2 - $p0) : ($p1 < $p2 || $p2 === FALSE ? $p1 - $p0 : $p2 - $p0));
	$res[2] = $p1 === FALSE ? ($p2 === FALSE ? FALSE : substr($url, $p2 + 1)) : ($p1 < $p2 || $p2 === FALSE ? FALSE : substr($url, $p2 + 1, $p1 - $p2 - 1));
	$res[3] = $p1 === FALSE ? FALSE : substr($url, $p1);
	return $res;
}



/// SHARED INTERNAL METHODS (usually you should ignore them) ///////////////////


function pimpl_args(&$ctx) {
	switch (getenv('REQUEST_METHOD')) {
	case 'GET':
		if (isset($_GET)) {
			$GLOBALS['pipeps-args'] = &$_GET;    /// inits and imports arguments cache from the QUERY_STRING, cookies and/or similar into $GLOBALS['pipeps-...']
		} else {
			$GLOBALS['pipeps-args'] = &$GLOBALS['HTTP_GET_VARS'];
		}
		break;
	case 'POST':
		if (isset($_POST)) {
			$GLOBALS['pipeps-args'] = &$_POST;
		} else {
			$GLOBALS['pipeps-args'] = &$GLOBALS['HTTP_POST_VARS'];
		}
		break;
	}
	if (isset($_COOKIE)) {
		$GLOBALS['pipeps-cookies'] = &$_COOKIE;
	} else {
		$GLOBALS['pipeps-cookies'] = &$GLOBALS['HTTP_COOKIE_VARS'];
	}
}


function pimpl_pipe(&$ctx) {
	if ($GLOBALS['pipeps-args'] === FALSE) {
		pimpl_args($ctx);
	} $ctx['pipe'] = array();
	if (isset($ctx['config']) && isset($ctx['config']['pipe'])) {
		foreach ($ctx['config']['pipe'] as $k => $v) $ctx['pipe'][$k] = $v;
	}
	if (isset($GLOBALS['pipeps-args']['pipe'])) {
		ptoassoc($GLOBALS['pipeps-args']['pipe'], $ctx['pipe']);
	} else if (isset($GLOBALS['pipeps-args']['_'])) {
		$pipe = puncompress($GLOBALS['pipeps-args']['_']);
		ptoassoc($pipe, $ctx['pipe']);
	}
} /// inits and imports arguments cache from the pipe, for internal use of pipeps_}mod_run and parg


function &pimpl_handle(&$handlerset, &$uri, &$offset_where, &$data_size_type, &$ifnot_limit, &$bypass) {
	return $bypass;    /// TODO: must be completed; customizable security and virtual driver implementation for abstract I/O; to allow default behaviour return $bypass, to reject or reimplement return a different value and default implementation will not be executed
}


function pimpl_hopen(&$handle, &$uri, $write, &$offset, &$fsize, &$alock, $disable_log = FALSE) {
	$is = is_array($uri);    /// for internal use, open/close/retrieve an $uri file handle
	$handle = ($is && isset($uri['handle'])) ? $uri['handle'] : FALSE;
	$fsize = ($is && isset($uri['size']) && $uri['size'] === TRUE) ? TRUE : FALSE;
	$truncate = !$is ? TRUE : (isset($uri['truncate']) ? $uri['truncate'] : FALSE);
	$merge = ($is && isset($uri['merge'])) ? $uri['merge'] : !$truncate;
	$append = ($is && isset($uri['append'])) ? $uri['append'] : FALSE;
	$excl = ($is && isset($uri['exclusive'])) ? $uri['exclusive'] : FALSE;
	$fread = !$is ? FALSE : ((isset($uri['writeonly']) && $uri['writeonly'] === TRUE) ? FALSE : TRUE);
	$fwrite = !$is ? FALSE : ((isset($uri['readonly']) && $uri['readonly'] === FALSE) ? FALSE : TRUE);
	$lock = ($is && isset($uri['lock'])) ? ($uri['lock'] === TRUE || $uri['lock'] == 'wait' ? 2 : ($uri['lock'] == 'nowait' ? (2 | LOCK_NB) : FALSE)) : FALSE;
	$alock = ($is && isset($uri['autounlock']) && $uri['autounlock'] === FALSE) ? FALSE : TRUE;
	$uri = ($is && isset($uri['uri'])) ? $uri['uri'] : (is_string($uri) ? $uri : FALSE);
	$h = FALSE;
	if ($uri !== FALSE || $handle !== FALSE) {
		if (!$write) {
			$h = ($handle !== FALSE && $handle !== TRUE) ? $handle : @fopen($uri, $fwrite ? 'r+b' : 'rb');
		} else {
			$h = ($handle !== FALSE && $handle !== TRUE) ? $handle : @fopen($uri, $fread ? ($append ? 'a+b' : ($merge ? 'r+b' : ($excl ? 'x+b' : 'w+b'))) : ($append ? 'ab' : ($merge ? 'r+b' : ($excl ? 'xb' : 'wb'))));
			if (!$h && !($handle !== FALSE && $handle !== TRUE) && !$append && $merge) {
				$h = @fopen($uri, $handle ? 'w+b' : 'wb');
			}
		}
		if ($h && $lock !== FALSE && !flock($h, $lock)) {
			pimpl_hclose($h);
			$h = FALSE;
		} $alock = ($alock && $lock !== FALSE) ? 2 : 1;
	}
	return $h;
}
function pimpl_hclose(&$uri) {
	if (is_array($uri) && isset($uri['handle'])) {
		return @fclose($uri['handle']);
	} else {
		return @fclose($uri);
	}
}



/// COMPATIBILITY / POLYFILL METHODS (allows extended compat) //////////////////


if (!function_exists('is_callable')) {
	function is_callable($function_name) {
		return function_exists($function_name);    /// if is_callable is not available in the current enviroment, define instead a polyfill
	}
}


if (!function_exists('gzcompress') || !function_exists('gzuncompress')) {
	function pcompress($data) {
		$res = str_replace('+', '.', str_replace('/', '~', str_replace('=', '-', base64_encode(prlee_compress($data)))));
		if (substr($res, 0, 2) == '~~') {
			return substr($res, 2);
		} else {
			return '_' . $res;
		}
	}
	function puncompress($data) {
		if (strlen($data) > 0 && $data[0] == '_') {
			return prlee_decompress(base64_decode(str_replace('.', '+', str_replace('~', '/', str_replace('-', '=', $data)))));
		} else {
			return prlee_decompress(base64_decode('//' . str_replace('.', '+', str_replace('~', '/', str_replace('-', '=', $data)))));
		}
	}
} else {
	function pcompress($data) {
		$res = str_replace('+', '.', str_replace('/', '~', str_replace('=', '-', base64_encode(gzcompress($data)))));    /// if gzcompress is not available in the current enviroment, define instead a polyfill
		if (substr($res, 0, 2) == 'eJ' && substr($res, -1, 1) == '-') {
			return substr($res, 2, -1);
		} else {
			return '_' . $res;
		}
	}
	function puncompress($data) {
		if (strlen($data) > 0 && $data[0] == '_') {
			return gzuncompress(base64_decode(substr(str_replace('.', '+', str_replace('~', '/', str_replace('-', '=', $data))), 1)));
		} else {
			return gzuncompress(base64_decode('eJ' . str_replace('.', '+', str_replace('~', '/', str_replace('-', '=', $data))) . '='));
		}
	}
}


if (!function_exists('hex2bin')) {
	function hex2bin($hexstr) {
		static $table = FALSE;    /// if hex2bin is not available in the current enviroment, define instead a polyfill
		if ($table === FALSE) {
			$table = array();
			for ($i = 0; $i < 256; ++$i) {
				if ($i >= ord('a') && $i <= ord('f')) {
					$table[$i] = $i - ord('a') + 10;
				} else if ($i >= ord('0') && $i <= ord('9')) {
					$table[$i] = $i - ord('0');
				} else {
					$table[$i] = 0;
				}
			}
		} $hexlen = strlen($hexstr);
		$binstr = '';
		$e = 0;
		for ($i = 0; $i < $hexlen; ++$i) {
			if (($i & 1) == 0) {
				$e = $table[ord($hexstr[$i])];
			} else {
				$binstr .= chr($table[ord($hexstr[$i])] + $e * 16);
			}
		}
		return $binstr;
	}
}


if (!function_exists('var_export')) {
	function var_export($expression, $return) {
		if ($return) {
			return serialize($expression);    /// if var_export is not available in the current enviroment, define instead a polyfill
		} else {
			return print_r($expression);
		}
	}
}


if (!function_exists('sys_get_temp_dir')) {
	function sys_get_temp_dir() {
		return '/tmp';    /// if sys_get_temp_dir is not available in the current enviroment, define instead a polyfill
	}
}


if (!function_exists('debug_backtrace')) {
	function debug_backtrace() {
		return array();    /// if debug_backtrace is not available in the current enviroment, define instead a polyfill
	}
}


if (!isset($GLOBALS['pipeps-mode']) || $GLOBALS['pipeps-mode'] !== -1) {
	psetup();
	main(pipeps($GLOBALS['pipeps-config']));    /// END OF METHOD DEFINITIONS (after this init pipeps and call main)
}
