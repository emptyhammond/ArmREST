<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Kohana ArmREST helper class.
 *
 * @package    Kohana/ArmREST
 * @author     Matthew Hammond
 * @copyright  (c) 2012 Matthew Hammond
 * @license    http://kohanaframework.org/license
 */

/**
 * Kohana_ArmREST class.
 */
class Kohana_ArmREST {

	protected static $_config;

	private function __construct()
	{
		self::$_config = Kohana::$config->load('armrest');
	}

	public static function types()
	{
		//return self::$_config['types'];
		return Kohana::$config->load('armrest.types');
	}

	/**
	 * json function.
	 *
	 * @access public
	 * @static
	 * @param array $array (default: array())
	 * @return json
	 */
	public static function json($array = array())
	{
		return $array ? (string) (isset($_REQUEST['callback'])) ? $_REQUEST['callback'] . '(' .  json_encode($array) . ')' : json_encode($array) : null;
	}

	/**
	 * xml function.
	 *
	 * @access public
	 * @static
	 * @param array $array (default: array())
	 * @return xml
	 */
	public static function xml($array, $startElement = 'objects', $elements = 'object', $collection)
	{
		return $array ? (string) self::buildXMLData($array, $startElement, $elements, null, null, $collection) : null;
	}

	/**
	 * html function.
	 *
	 * @access public
	 * @static
	 * @param array $array
	 * @return html
	 */
	public static function html($array)
	{
		if($array)
		{
			$string = '';

			$render = function($array, &$string) use (&$render)
			{
				foreach($array as $key => $value)
				{
					if (is_numeric($key))
					{
						if(is_array($value) and sizeof($value) > 0)
						{
							$string .= $render($value, $string);
						}
						else
						{
							$string .= "<li>$value</li>\r\n";
						}
					}
					else
					{
						if(is_array($value) and sizeof($value) > 0)
						{
							$string .= "<li>$key:\r\n<ul>\r\n";
							$string .= $render($value, $string);
							$string .= "</ul>\r\n</li>\r\n";
						}
						else
						{
							$string .= "<li>$key: $value</li>\r\n";
						}
					}
				}
				unset($key, $value);
			};

			$render($array, $string);

			$string = "<ul>\r\n$string</ul>";
		}
		else
		{
			$string = null;
		}

		return $string;
	}

	/**
	 * text function.
	 *
	 * @access public
	 * @static
	 * @param array $array
	 * @return string
	 */
	public static function text($array)
	{
		if($array)
		{
			$render = function($array, &$string = '') use (&$render)
			{
				foreach($array as $key => $value)
				{
					$string .= (is_array($value) ? "\t" : "") . (is_numeric($key) ? '' : "$key:") . ( is_array($value) ? $render($value, $string) : $value) . "\n";
				}
			};

			$string = '';

			$render($array, $string);
		}
		else
		{
			$string = null;
		}

		return $string;
	}

	/**
	 * Build A XML Data Set
	 *
	 * @param array $data Associative Array containing values to be parsed into an XML Data Set(s)
	 * @param string $startElement Root Opening Tag, default fx_request
	 * @param string $xml_version XML Version, default 1.0
	 * @param string $xml_encoding XML Encoding, default UTF-8
	 * @return string XML String containig values
	 * @return mixed Boolean false on failure, string XML result on success
	 */
	public static function buildXMLData($data, $startElement = 'objects', $elements = 'object', $xml_version = '1.0', $xml_encoding = 'UTF-8', $collection = true)
	{
		if (!is_array($data))
		{
			$err = 'Invalid variable type supplied, expected array not found on line '.__LINE__." in Class: ".__CLASS__." Method: ".__METHOD__;
			trigger_error($err);
			if($this->_debug) echo $err;
			return false; //return false error occurred
		}

		$xml = new XmlWriter();
		$xml->openMemory();
		$xml->startDocument($xml_version, $xml_encoding);

		if( ! $collection)
		{
			$xml->startElement($elements);
			$data = $data[0];
		}
		else
		{
			$xml->startElement($startElement);
		}

		$xml->startAttribute('xmlns');
		$xml->text("http://www.w3.org/2005/Atom");
		$xml->endAttribute();

		/**
		* Write XML as per Associative Array
		* @param object $xml XMLWriter Object
		* @param array $data Associative Data Array
		*/
		function write(XMLWriter $xml, $data, $elements, $attributes = false)
		{
			foreach($data as $key => $value)
			{
				if (is_numeric($key)) $key = $elements;

				if(is_array($value))
				{
					if ($key === 'link')
					{
						$xml->startElement('atom:link');

						write($xml, UTF8::clean($value), $elements, true);
					}
					else
					{
						$xml->startElement($key);

						write($xml, UTF8::clean($value), $elements);
					}

					$xml->endElement();

					continue;
				}

				if ($attributes)
				{
					// Atrributes flag has been set so add key=>value pairs as attributes
					$xml->startAttribute($key);
					$xml->text($value);
					$xml->endAttribute();
				}
				elseif (is_string($value) && strlen($value) > 255)
				{
					//CDATA
					$xml->writeElement($key, UTF8::clean($value));
				}
				else
				{
					//Just write it
					$xml->writeElement($key, UTF8::clean($value));
				}

			}
		}

		write($xml, $data, $elements);

		$xml->endElement();//write end element
		//Return the XML results
		return $xml->outputMemory(true);
	}
	
	public static function last_modified($object)
	{
		return isset($object->amended) ? gmdate("D, d M Y H:i:s",$object->amended)." GMT" : gmdate("D, d M Y H:i:s")." GMT";
	}
}