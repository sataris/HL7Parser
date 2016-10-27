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

    private $results;

    private $segments;

    private $header;

    private $type;

    public function __construct($file_contents, $type)
    {
        if (empty($file_contents)) {
            throw new \Exception('File Content cannot be empty');
        }
        $this->type = $type;
        if ($this->type == 'xml') {
             $this->file_content = simplexml_load_string($file_contents);
        } else {
            $this->file_content = $file_contents;
        }
        $this->setHeader();
        $this->setPatient();
        $this->setResult();
    }

    public function getPatient()
    {
        if (empty($this->patient)) {
            throw new \Exception('You must first parse the XML before returning objects');
        }
        return $this->patient;
    }

    private function setPatient()
    {
        switch ($this->type) {
            case 'oru':
                $patient = array_slice($this->segments, 12, 28);
                $patient =  array_combine(range(1, count($patient)), array_values($patient));
                $this->patient = new Patient($patient, 'oru');
                break;
            case 'xml':
                $patient = $this->file_content->xpath('//PID');
                if (empty($patient)) {
                    throw new \Exception('This XML does not conform to the ORU standard');
                }
                $this->patient = new Patient($patient[0], 'xml');
                break;
        }
    }

    private function setHeader()
    {
        switch ($this->type) {
            case 'oru':
                if (substr($this->file_content, 0, 3) != 'MSH') {
                    throw new \Exception('This is not a valid HL7');
                }
                $field_delimiter = substr($this->file_content, 3, 1);
                $this->segments = explode($field_delimiter, $this->file_content);
                $header = array_slice($this->segments, 0, 25);
                $this->header = array_combine(range(1, count($header)), array_values($header));
                break;
            case 'xml':
                $results = $this->file_content->xpath('//MSH');

                if (empty($results)) {
                    throw new \Exception('This XML does not conform to the ORU standard');
                }
                $this->header = $results[0];
                break;
        }
    }

    private function setResult()
    {
        $resultArray = array();
        switch ($this->type) {
            case 'oru':
                $results= explode('OBX', $this->file_content);
                if (empty($results)) {
                    throw new \Exception('This ORU does not conform to the ORU standard');
                }

                unset($results[0]);
                foreach ($results as $key => $value) {
                    $results[$key] = explode('|', $value);
                }
                foreach ($results as $result) {
                    $testName = explode("^", $result[3]);
                    $array['resultMarker'] = $testName[0];
                    $array['labMarkerCode'] = $testName[0];
                    $array['testName'] = (!empty($testName[1]) ? $testName[1] : $testName[0]);
                    $array['testValue'] =  $result[5];
                    $array['testUnit'] = $result[6];
                    $array['testReference'] = explode("^", $result[7]);
                    $array['testAbnormal'] = $result[8];
                    $resultArray[] = $array;
                }
                break;
            case 'xml':
                $results = $this->file_content->xpath('//OBX');
                if (empty($results) || empty($result->{'OBX.3'})) {
                    throw new \Exception('This XML does not conform to the ORU standard');
                }
                foreach ($results as $result) {
                    $array['resultMarker'] = $result->{'OBX.3'}->{'CE.1'}->__toString();
                    $array['labMarkerCode'] = $result->{'OBX.3'}->{'CE.1'}->__toString();
                    $array['testName'] = $result->{'OBX.3'}->{'CE.2'}->__toString();
                    $array['testValue'] =  $result->{'OBX.5'}->__toString();
                    $array['testUnit'] = $result->{'OBX.6'}->{'CE.1'}->__toString();
                    $array['testReference'] =explode("^", $result->{'OBX.7'}->__toString());
                    $array['testAbnormal'] = $result->{'OBX.8'}->__toString();
                    $resultArray[] = $array;
                }
                break;
        }

        $this->results = $resultArray;
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
        if ($this->type == 'xml') {
            $header = $this->header->{'MSH.10'}->__toString();
            $header = explode("_", $header);
            return $header[1];
        } else {
            return $this->header[10];
        }
    }

    public function getResults()
    {
        return $this->results;
    }
}
