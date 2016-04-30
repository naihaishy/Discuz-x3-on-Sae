<?php

/*
	[UCenter] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: xml.class.php 971 2009-11-16 02:20:26Z zhaoxiongfei $
*/

function xml_unserialize(&$xml, $isnormal = FALSE) {
	$xml_parser = new XML($isnormal);
	$data = $xml_parser->parse($xml);
	$xml_parser->destruct();
	return $data;
}

function xml_serialize($arr, $htmlon = FALSE, $isnormal = FALSE, $level = 1) {
	$s = $level == 1 ? "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n<root>\r\n" : '';
	$space = str_repeat("\t", $level);
	foreach($arr as $k => $v) {
		if(!is_array($v)) {
			$s .= $space."<item id=\"$k\">".($htmlon ? '<![CDATA[' : '').$v.($htmlon ? ']]>' : '')."</item>\r\n";
		} else {
			$s .= $space."<item id=\"$k\">\r\n".xml_serialize($v, $htmlon, $isnormal, $level + 1).$space."</item>\r\n";
		}
	}
	$s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
	return $level == 1 ? $s."</root>" : $s;
}

class XML {

	var $parser;
	var $document;
	var $stack;
	var $data;
	var $last_opened_tag;
	var $isnormal;
	var $attrs = array();
	var $failed = FALSE;

	function __construct($isnormal) {
		$this->XML($isnormal);
		$this->document = array();
		$this->stack	= array();
	}

	function XML($isnormal) {
		$this->isnormal = $isnormal;
		//$this->parser = xml_parser_create('ISO-8859-1');
		//xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		//xml_set_object($this->parser, $this);
		//xml_set_element_handler($this->parser, 'open','close');
		//xml_set_character_data_handler($this->parser, 'data');
	}

	function destruct() {
		//xml_parser_free($this->parser);
	}

	function innerparse(&$data) {
		
		$sxe = new SimpleXMLElement($data);
		$xml_array = array();
		foreach ($sxe->children() as $second_gen) {
			//echo $second_gen->getName().'====val:<br/>'."\r\n";
			$value = strval($second_gen[0]);
			//echo $tag.'---'.$value.'----in if<br/>'."\r\n";
			if(trim($value))
			{
				$tag = strval($second_gen->getName());	
				$attribute_obj = $second_gen->attributes();
				$attributes = array();
				//$value = iconv('ISO8859_1','GBK',$value);
				//$value = utf8_encode($value);
				//echo urldecode($value);
				//echo iconv( "UTF8","ISO-8859-1", $value);
				// "ISO-8859-1" ¼´latin1±àÂë
				//echo $value;
				$value = mb_convert_encoding($value, "latin1", "utf-8"); 
				
				//print_r($attribute_obj);
				foreach($attribute_obj as $k => $v)
				{
					$attributes[$k]=strval($v);
				}
				if(isset($attributes['id']))
				{
					$xml_array[$attributes['id']] = $value;
					
				}
				//$this->document[$tag]['attributes'] = $attributes;
				//$xml->createElement($tag,$value, $attributes);
			}
			else
			{
				$attribute_obj = $second_gen->attributes();
				$attributes = array();
				foreach($attribute_obj as $k => $v)
				{
					$attributes[$k]=strval($v);
				}
				if(isset($attributes['id']))
				{
					$xml_array[$attributes['id']] = $this->innerparse($second_gen->asXML());
					
				}				
			}
		}
		return $xml_array;
	}
	
	function parse(&$data) 
	{
		if(empty($data))
		{
			return array();
		}
		return $this->document = $this->innerparse($data);
	}

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