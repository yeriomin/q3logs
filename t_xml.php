<?
#Класс для создания XML, который нормально откроется в Excel
/*class t_cml_excel
{
	var $xml;
	function createXml()
	{
		$this->xml=new xmlWriter();
		$this->xml->OpenMemory();
		$this->xml->setIndent(true);
		$this->xml->setIndentString('	');
		$this->xml->startDocument('1.0','utf-8');
	}
}*/
#Класс для работы с XML (для записи)
class t_xml
{
	var $xml;
	function __construct()
	{
	}
	function __destruct()
	{
		unset($xml);
	}
	function createXml($version,$encoding,$options=array())
	{
		$this->xml=new xmlWriter();
		$this->xml->OpenMemory();
		if (!$options['noindent'])
		{
			$this->xml->setIndent(true);
			$this->xml->setIndentString('	');
		}
		$this->xml->startDocument($version,$encoding);
	}
	function endXml()
	{
		$this->xml->endDtd();
	}
	function startElement($title,$attributes=null)
	{
		$this->xml->startElement($title);
		if (isset($attributes))
			if (count($attributes)>0)
				foreach ($attributes as $key=>$value)
					$this->xml->writeAttribute($key,$value);
	}
	function addElement($title,$attributes=null,$content='')
	{
		$this->startElement($title,$attributes);
		$this->xml->text($content);
		$this->endElement();
	}
	function endElement()
	{
		$this->xml->endElement();
	}
	function getXml()
	{
		return $this->xml->outputMemory(true);
	}
}
?>