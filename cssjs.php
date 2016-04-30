<?php

/**
 *      rewrite输出缓存的css与js内容
 *
 *      2011-6-24 18:28:25 ZouQilong $
 */

sae_set_display_errors(false);
require_once './config/config_global.php';
if(empty($_REQUEST['type']))  $_REQUEST['type'] = 'css';
$sae_cache_filename = $_REQUEST['file'].'.'.$_REQUEST['type'];
$file_content = @file_get_contents("saekv://data/cache/$sae_cache_filename");

if($_REQUEST['type']=='css'){
	header('Content-Type:text/css');	
}
if($_REQUEST['type']=='js'){
	header('Content-Type:application/javascript');	
}
echo "/* $sae_cache_filename */\r\n"; //".var_export($_REQUEST,true)."
if($file_content !==false){
	echo "/* From saekv */\r\n";
	echo $file_content;
	exit;
}
else
{
	define('APPTYPEID', 2);
	define('CURSCRIPT', 'forum');
	define('CURMODULE', 'index');
	require './source/class/class_core.php';
	$discuz = & discuz_core::instance();
	$discuz->init();

	require_once libfile('function/cache');
	if($_REQUEST['type']=='css'){		
		updatecache('styles');//更新 css 缓存		
	}
	elseif($_REQUEST['type']=='js'){
		updatecache('smilies_js');//更新 js 缓存
	}
	
	$sae_cache_filename = $_REQUEST['file'].'.'.$_REQUEST['type']; // $sae_cache_filename变量在这里为空了，重新赋值下	
	if($file_content = @file_get_contents('saekv://data/cache/'.$sae_cache_filename)){
		file_put_contents("saemc://data/cache/$sae_cache_filename",$file_content);
		echo "/* after updatecache from saestor */\r\n";
		echo $file_content;	
		exit;
	}
}





?>