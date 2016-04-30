<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: admin.php 23290 2011-07-04 01:29:24Z cnteacher $
 */

define('IN_ADMINCP', TRUE);
define('NOROBOT', TRUE);
define('ADMINSCRIPT', basename(__FILE__));
define('CURSCRIPT', 'admin');
define('HOOKTYPE', 'hookscript');
define('APPTYPEID', 0);


require './source/class/class_core.php';
require './source/function/function_misc.php';
require './source/function/function_forum.php';
require './source/function/function_admincp.php';
require './source/function/function_cache.php';

$discuz = C::app();
$discuz->init();

$admincp = new discuz_admincp();
$admincp->core  = & $discuz;
$admincp->init();

if($_G['uid']!=1 || $_G['groupid']!= 1){
	exit('only installer can enter this page.');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>SAE开发者社区-Sina App Engine 应用开发领跑者 -  Powered by Discuz!</title>

<meta name="keywords" content="SAE开发者社区,dzxsae,discuzx4sae,dzxsae官方站点,discuz4sae" />
<meta name="description" content="SAE开发者社区,sae开发者社区 " />
<meta http-equiv="MSThemeCompatible" content="Yes" />
<link rel="stylesheet" type="text/css" href="data/cache/style_1_common.css?UQt" />
</head>

<body id="nv_forum" class="pg_index" onkeydown="if(event.keyCode==27) return false;">
<?php
	$a = isset($_REQUEST['a']) ? $_REQUEST['a']:'';
	$k = isset($_REQUEST['k'])? $_REQUEST['k']:'';
	$v = isset($_REQUEST['v'])? $_REQUEST['v']:'';
?>
<div id="header">
	<h3>SAE KVDB Manager</h3>
	<a href="saekv.php?a=set">SET</a> | <a href="saekv.php?a=get">GET</a>  | <a href="saekv.php?a=del">DEL</a>  | <a href="saekv.php?a=allkv">ALL KV</a> 
</div>
<?php
if($a == 'clearcache'){
	
	clearkvfolder('./data/template/');
	clearkvfolder('/data/template/');
	clearkvfolder('data/template/');
	
	clearkvfolder('data/cache/');
	clearkvfolder('uc_client/data/');
	clearkvfolder('uc_server/data/');
	header('Location:saekv.php');
}

	if($a == 'set'){
		
		if(!empty($_POST['saekv_key']) && !empty($_POST['saekv_val']) ){
			$_POST['saekv_val'] = stripslashes($_POST['saekv_val']);
			file_put_contents('saekv://'.$_POST['saekv_key'],$_POST['saekv_val']);
			
			echo "<p>设置成功:{$_POST['saekv_key']} => <pre style=\"margin:5px;border:1px solid #CCC;\">".htmlspecialchars($_POST['saekv_val'])."</pre></p>";
		}else{
?>
			<form action="saekv.php?a=set" name="setform" method="post">
				<p>&nbsp;&nbsp;saekv://<input type="text" name="saekv_key" value="" /></p>
                          <p>Value:<textarea name="saekv_val" cols="60" row="8" ></textarea></p>
				<p>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit"  value="设置" /></p>
			</form>
<?php
		}
	}else if ($a == 'get'){
		?>
			<form action="saekv.php?a=get" name="setform" method="post">
				<p>&nbsp;&nbsp;saekv://<input type="text" name="k" value="" /></p>
				<p>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit"  value="获得" /></p>
			</form>

<?php
		if(!empty($k)){
			$v = file_get_contents('saekv://'.$k);
			if($v){
				echo "<p>取值成功:{$k} => <pre style=\"margin:5px;border:1px solid #CCC;\">".htmlspecialchars($v)."</pre></p>";
			}else{
				echo "<p>{$k}不存在！</p>";
			}
			
		}		
	}else if($a == 'del'){
		$kv = new SaeKV();
	
		$ret = $kv->init();
		if(!empty($k) ){
			$v = $kv->delete($k);
			echo "<p>saekv://{$k}删除成功！</p>";
			
		}else if(!empty($_GET['k'])){
			$v = $kv->delete($_GET['k']);
			echo "<p>saekv://{$_GET['k']}删除成功！</p>";
			
		}
		else{
?>
			<form action="saekv.php?a=del" name="setform" method="post">
				<p>&nbsp;&nbsp;saekv://<input type="text" name="k" value="" /></p>
				<p>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit"  value="删除" /></p>
			</form>

<?php		
		}
	}else if ($a =='allkv'){
		$kv = new SaeKV();
	
		$ret = $kv->init();
		$ret = $kv->pkrget('', 100);     
		while (true) {                    
			foreach($ret as $k=>$v){
                echo "<p>saekv://{$k} &nbsp;&nbsp;&nbsp;&nbsp; 
                	<a href=\"saekv.php?a=get&k={$k}\" style='color:red;'>VIEW</a> &nbsp;&nbsp; 
                	<a href=\"saekv.php?a=del&k={$k}\" onclick=\"return confirm('确认删除？');\" style='color:red;'>DEL</a></p>";
            }
			end($ret);                                
			$start_key = key($ret);
			$i = count($ret);
			if ($i < 100) break;
			$ret = $kv->pkrget('', 100, $start_key);
		}

	}
?>
</body>
</html>