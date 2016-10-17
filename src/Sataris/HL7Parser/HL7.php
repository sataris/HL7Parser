<?php

namespace Sataris\HL7Parser;

use Sataris\HL7Parser\Patient;

/**
 * This class allows for the easily manipulation of files that are in the HL7 format.
 */
class HL7
{

    private $file_content;

    public $patient;

    public $results;

    public $header;

    public function __construct($file_contents)
    {
        if (empty($file_contents)) {
            throw new \Exception('File Content cannot be empty');
        }
        $this->file_content = $file_contents;
    }

    public function parseAsCsv()
    {
    }

    protected function parseAsOru()
    {
    }

    /**
     * This function will take our xml file and parse it for the various values required as an ordinary php array.
     * @return [type] [description]
     */
    public function parseAsXml()
    {
        $this->file_content = simplexml_load_string($this->file_content);
        $this->setHeaderXML();
        $this->setPatientXML();
        $this->setResultXML();
    }

    public function getPatient()
    {
        if (empty($this->patient)) {
            throw new \Exception('You must first parse the XML before returning objects');
        }
        return $this->patient;
    }

    private function setPatientXML()
    {
        $patient = $this->file_content->xpath('//PID');
        if (empty($patient)) {
            throw new \Exception('This XML does not conform to the ORU standard');
        }
        $this->patient = new Patient($patient[0], 'xml');
    }

    private function setHeaderXML()
    {
        $results = $this->file_content->xpath('//MSH');

        if (empty($results)) {
            throw new \Exception('This XML does not conform to the ORU standard');
        }
        $this->header = $results[0];
    }

    private function setResultXML()
    {
        $results = $this->file_content->xpath('//OBX');
        if (empty($results)) {
            throw new \Exception('This XML does not conform to the ORU standard');
        }
        $this->results = $results;
    }

    protected function readSingleXML($xml, $key)
    {
        $data = null;

        if (!empty(trim($xml->__toString()))) {
            $data[$key . '.1'] = $xml->__toString();
        }
        $count = 1;
        if (!empty($xml) && !empty($xml->children())) {
            foreach ($xml->children() as $child) {
                if (!empty($child->__toString())) {
                    $data[$key.'.'.$count] = $child->__toString();
                    $count++;
                }
            }
        }
        return $data;
    }

    protected function readRepeatingXML($xml, $key)
    {
        $array = [];
        $count = 2;

        if (!empty(trim($xml->__toString()))) {
            $array[$key . '.1'] = $xml->__toString();
        }
        if (!empty($xml)) {
            foreach ($xml->children() as $child) {
                if (!empty(trim($child->__toString()))) {
                    $array[$key .'.' . $count] = $child->__toString();
                    $count++;
                }
            }
        }
        return $array;
    }

    public function convertXMLtoArray($xml, $get_attributes = 1, $priority = 'tag')
    {
        
        $contents = "";
        if (!function_exists('xml_parser_create')) {
            return array ();
        }
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($xml), $xml_values);
        xml_parser_free($parser);
        if (!$xml_values) {
            return; //Hmm...
        }
        $xml_array = array ();
        $parents = array ();
        $opened_tags = array ();
        $arr = array ();
        $current = & $xml_array;
        $repeated_tag_index = array ();
        foreach ($xml_values as $data) {
            unset($attributes, $value);
            extract($data);
            $result = array ();
            $attributes_data = array ();
            if (isset($value)) {
                if ($priority == 'tag') {
                    $result = $value;
                } else {
                    $result['value'] = $value;
                }
            }
            if (isset($attributes) and $get_attributes) {
                foreach ($attributes as $attr => $val) {
                    if ($priority == 'tag') {
                        $attributes_data[$attr] = $val;
                    } else {
                        $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                    }
                }
            }
            if ($type == "open") {
                $parent[$level -1] = & $current;
                if (!is_array($current) or (!in_array($tag, array_keys($current)))) {
                    $current[$tag] = $result;
                    if ($attributes_data) {
                        $current[$tag . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    $current = & $current[$tag];
                } else {
                    if (isset($current[$tag][0])) {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        $repeated_tag_index[$tag . '_' . $level]++;
                    } else {
                        $current[$tag] = array (
                        $current[$tag],
                        $result
                        );
                        $repeated_tag_index[$tag . '_' . $level] = 2;
                        if (isset($current[$tag . '_attr'])) {
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset($current[$tag . '_attr']);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                    $current = & $current[$tag][$last_item_index];
                }
            } elseif ($type == "complete") {
                if (!isset($current[$tag])) {
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $attributes_data) {
                        $current[$tag . '_attr'] = $attributes_data;
                    }
                } else {
                    if (isset($current[$tag][0]) and is_array($current[$tag])) {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        if ($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag . '_' . $level]++;
                    } else {
                        $current[$tag] = array (
                        $current[$tag],
                        $result
                        );
                        $repeated_tag_index[$tag . '_' . $level] = 1;
                        if ($priority == 'tag' and $get_attributes) {
                            if (isset($current[$tag . '_attr'])) {
                                $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                                unset($current[$tag . '_attr']);
                            }
                            if ($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                    }
                }
            } elseif ($type == 'close') {
                $current = & $parent[$level -1];
            }
        }
        return ($xml_array);
    }

    public function getHeaderCode()
    {
        $header = $this->header->{'MSH.10'}->__toString();
        $header = explode("_", $header);
        return $header[1];
    }
}
