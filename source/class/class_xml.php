<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: class_xml.php 12943 2010-07-19 01:16:30Z monkey $
 */

function xml2array(&$xml, $isnormal = FALSE) {	
	$xml_parser = new XMLparse($isnormal);
	$data = $xml_parser->parse($xml);
	$xml_parser->destruct();
	return $data;
}

function array2xml($arr, $htmlon = TRUE, $isnormal = FALSE, $level = 1) {
	$s = $level == 1 ? "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n<root>\r\n" : '';
	$space = str_repeat("\t", $level);
	foreach($arr as $k => $v) {
		if(!is_array($v)) {
			$s .= $space."<item id=\"$k\">".($htmlon ? '<![CDATA[' : '').$v.($htmlon ? ']]>' : '')."</item>\r\n";
		} else {
			$s .= $space."<item id=\"$k\">\r\n".array2xml($v, $htmlon, $isnormal, $level + 1).$space."</item>\r\n";
		}
	}
	$s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
	return $level == 1 ? $s."</root>" : $s;
}

class XMLparse {

	var $parser;
	var $document;
	var $stack;
	var $data;
	var $last_opened_tag;
	var $isnormal;
	var $attrs = array();
	var $failed = FALSE;
	

	function __construct($isnormal) {
		$this->XMLparse($isnormal);
	}

	function XMLparse($isnormal) {
		$this->isnormal = $isnormal;
	}

	function destruct() {
	}
	
	function innerparse($sxe,&$parentData,$ns,$namespaces,$child_num = 1) {
		$data = array();
		if(empty($namespaces)){
			$namespaces = array('');
		}
		foreach ($namespaces as $namespace) {
			foreach ($sxe->attributes($namespace, true) as $key => $value) {
				if (!empty($namespace)) {
					$key = $namespace . ':' . $key;
				}
				$data['@' . $key] = (string)$value;
			}
			foreach ($sxe->children($namespace, true) as $child) {				
				$this->innerparse($child, $data, $namespace, $namespaces,$child_num);
			}			
		}
		$asString = trim((string)$sxe);
		if (empty($data)) {
			$data = $asString;//iconv('latin1','utf-8',$asString);
		} elseif (!empty($asString)) {
			$data['@'] =  $asString;//iconv('latin1','utf-8',$asString);// utf8_encode($asString);//diconv($asString,'latin1','utf-8');
		}
		if(is_array($data) && isset($data['@id'])){
			if(isset($data['@'])){
				$data[$data['@id']]=$data['@'];
				unset($data['@id'],$data['@']);
			}
			else{
				$data[$data['@id']] = $data['item'];
				unset($data['@id'],$data['@'],$data['item']);
			}
		}		
		
		if (!empty($ns)) {
			$ns .= ':';
		}
		$name = $ns . $sxe->getName();
		if (isset($parentData[$name])) {
			//if(is_array($data)){
				// name = item. 当当前item已存在，则合并到同一个item下。此时为同一层的item， 
				$parentData[$name] = array_merge($parentData[$name],$data);
			//}
			//else{
			//	$parentData[$name][] = $data;  //无此else情况出现
			//}
		} else {
			$parentData[$name] = $data;
		}
	}
	
	function parse(&$data) {
		if(empty($data)){
			return array();
		}
		//$tmp1data = iconv("cp936","UTF-8",$data);
		//$tmp2data = iconv("UTF-8","cp936",$tmp1data);
		//if($tmp2data == $data){
		//	$data = $tmp1data;
		//}
		$data = str_replace('ISO-8859-1','utf-8',$data); 
		$sxe = new SimpleXMLElement($data);
		$namespaces = $sxe->getNamespaces(true);	
		$parentData = array();
		$this->innerparse($sxe, $parentData,'',$namespaces);
		if(is_array($parentData)&& count($parentData)){
			foreach($parentData as $value){
				$parentData=$value;
				break;
			}
		}
		if(isset($parentData['root']['item'])){
			$parentData = $parentData['item'];
		}
		if(isset($parentData['item'])){
			$parentData = $parentData['item'];
		}
		return $this->document = $parentData;
	}
	
	/*
	 *去掉item标签，往上提一层。
	
	function  dealitemnode($parentData)
	{
		foreach($parentData as $key=> $value)
		{
			//if($value)
			if($key=='item')
			{
				if(is_array($value))
				{
					$thirdarray = false;
					foreach($value as $kk => $vv)
					{
						if(is_array($vv) && count($vv)==1)
						{
							foreach($vv as $kkk => $vvv)
							{
								if(isset($value[$kkk]))
								{
									if(is_array($value[$kkk]))
									{
										array_unshift($value[$kkk],$vvv);
									}
									else
									{
										$value[$kkk]=array($value[$kkk],$vvv);
									}
									
								}
								else
								{
									$value[$kkk] = $vvv;
								}
							}
							unset($value[$kk]);
						}						
					}
							
					$parentData[$key]=$this->dealitemnode($value);
				}
			}
			elseif(is_array($value))
			{
				$parentData[$key] = $this->dealitemnode($value);
			}
		}
		
		return $parentData;
	} */	

	function open(&$parser, $tag, $attributes) {
		$this->data = '';
		$this->failed = FALSE;
		if(!$this->isnormal) {
			if(isset($attributes['id']) && !is_string($this->document[$attributes['id']])) {
				$this->document  = &$this->document[$attributes['id']];
			} else {
				$this->failed = TRUE;
			}
		} else {
			if(!isset($this->document[$tag]) || !is_string($this->document[$tag])) {
				$this->document  = &$this->document[$tag];
			} else {
				$this->failed = TRUE;
			}
		}
		$this->stack[] = &$this->document;
		$this->last_opened_tag = $tag;
		$this->attrs = $attributes;
	}

	function data(&$parser, $data) {
		if($this->last_opened_tag != NULL) {
			$this->data .= $data;
		}
	}

	function close(&$parser, $tag) {
		if($this->last_opened_tag == $tag) {
			$this->document = $this->data;
			$this->last_opened_tag = NULL;
		}
		array_pop($this->stack);
		if($this->stack) {
			$this->document = &$this->stack[count($this->stack)-1];
		}
	}

}


?>