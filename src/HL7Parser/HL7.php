<?php

namespace Sataris\HL7Parser;

use Sataris\HL7Parser\Patient;

/**
 * This class allows for the easily manipulation of files that are in the HL7 format.
 */
class HL7
{
    public $patient;
    private $fieldSeperator = "|";
    private $componentSeperator = "^";
    private $fieldRepeater = "~";
    private $escapeCharacter = "\\";
    private $subComponentSeperator = "&";
    private $file_content;
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

    private function setHeader()
    {
        switch ($this->type) {
            case 'oru':
                if (substr($this->file_content, 0, 3) != 'MSH') {
                    throw new \Exception('This is not a valid HL7');
                }
                $this->setSeperators();
                $this->segments = explode($this->fieldSeperator, $this->file_content);
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

    private function setSeperators()
    {
        $lines = explode(PHP_EOL, $this->file_content);
        $this->fieldSeperator = substr($lines[0], 3, 1);
        $line = explode($this->fieldSeperator, $lines[0]);
        $line = str_split($line[1]);
        foreach ($line as $key => $value) {
            switch ($key) {
                case 0:
                    $this->componentSeperator = $value;
                    break;
                case 1:
                    $this->fieldRepeater = $value;
                    break;
                case 2:
                    $this->escapeCharacter = $value;
                    break;
                case 3:
                    $this->subComponentSeperator = $value;
                    break;
            }
        }
    }

    private function setPatient()
    {
        switch ($this->type) {
            case 'oru':
                $patient = array_slice($this->segments, 12, 28);
                $patient = array_combine(range(1, count($patient)), array_values($patient));
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

    private function setResult()
    {
        $resultArray = array();
        switch ($this->type) {
            case 'oru':
                $results = explode('OBR', $this->file_content);
                if (empty($results)) {
                    throw new \Exception('This ORU does not conform to the ORU standard');
                }
                unset($results[0]);
                foreach ($results as $key => $value) {
                    $temparray = preg_split('/\n|\r\n?/', $value);
                    $temparray = $this->filterArray($temparray);
                    foreach ($temparray as $tmp => $tmpvalue) {
                        $temparray[$tmp] = explode($this->fieldSeperator, $tmpvalue);
                    }
                    $results[$key] = $temparray;
                }
                foreach ($results as $result) {
                    $count = 0;
                    $testProfile = null;
                    foreach ($result as $r) {

                        if ($count == 0) {
                            $testProfile = explode($this->componentSeperator, $r[4]);
                            $testProfile = $this->filterArray($testProfile);
                            $testProfile = implode(" ", $testProfile);
                            $count++;
                            continue;
                        }
                        $testName = explode($this->componentSeperator, $r[3]);
                        $testName = $this->filterArray($testName);
                        $testName = array_values($testName);
                        $array['resultMarker'] = trim($testName[0]);
                        $array['labMarkerCode'] = trim($testName[0]);
                        $array['testName'] = (!empty($testName[1]) ? trim($testName[1]) : trim($testName[0]));

                        $array['testValue'] = $r[5];
                        $array['testUnit'] = $r[6];
                        $referenceRange = $this->filterArray(explode("^", $r[7]));

                        $array['testReference'] = (!empty($referenceRange) ? implode(" ", $referenceRange) : "");
                        $array['testAbnormal'] = $r[8];
                        $array['profile_name'] = $testProfile;
                        $resultArray[] = $array;

                    }
                }
                break;
            case 'xml':
                $results = $this->file_content->xpath('//OBX');
                if (empty($results)) {
                    throw new \Exception('This XML does not conform to the ORU standard');
                }
                foreach ($results as $result) {
                    try {
                        if (!empty($result->{'OBX.3'}->{'CE.1'})) {
                            $array['resultMarker'] = $result->{'OBX.3'}->{'CE.1'}->__toString();
                        }
                        if (!empty($result->{'OBX.3'}->{'CE.1'})) {
                            $array['labMarkerCode'] = $result->{'OBX.3'}->{'CE.1'}->__toString();
                        }
                        if (!empty($result->{'OBX.3'}->{'CE.2'})) {
                            $array['testName'] = $result->{'OBX.3'}->{'CE.2'}->__toString();
                        }
                        if (!empty($result->{'OBX.5'})) {
                            $array['testValue'] = $result->{'OBX.5'}->__toString();
                        }
                        if (!empty($result->{'OBX.6'}->{'CE.1'})) {
                            $array['testUnit'] = $result->{'OBX.6'}->{'CE.1'}->__toString();
                        }
                        if (!empty($result->{'OBX.7'})) {
                            $array['testReference'] = explode($this->componentSeperator,
                                $result->{'OBX.7'}->__toString());
                        }
                        if (!empty($result->{'OBX.8'})) {
                            $array['testAbnormal'] = $result->{'OBX.8'}->__toString();
                        }
                        if (!empty($array)) {
                            $resultArray[] = $array;
                        } else {
                            return false;
                        }
                    } catch (Exception $e) {
                        throw new \Exception('This XML does not conform to the ORU standard');
                    }
                }
                break;
        }

        $this->results = $resultArray;
    }

    private function filterArray($array)
    {
        $array = array_filter($array, function ($var) {
            return !empty($var);
        });
        foreach ($array as $key => $value) {
            $array[$key] = trim($value);
        }
        return $array;
    }

    public function getPatient()
    {
        if (empty($this->patient)) {
            throw new \Exception('You must first parse the XML before returning objects');
        }
        return $this->patient;
    }

    public function convertXMLtoArray($xml, $get_attributes = 1, $priority = 'tag')
    {

        $contents = "";
        if (!function_exists('xml_parser_create')) {
            return array();
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
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();
        $current = &$xml_array;
        $repeated_tag_index = array();
        foreach ($xml_values as $data) {
            unset($attributes, $value);
            extract($data);
            $result = array();
            $attributes_data = array();
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
                $parent[$level - 1] = &$current;
                if (!is_array($current) or (!in_array($tag, array_keys($current)))) {
                    $current[$tag] = $result;
                    if ($attributes_data) {
                        $current[$tag . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    $current = &$current[$tag];
                } else {
                    if (isset($current[$tag][0])) {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        $repeated_tag_index[$tag . '_' . $level]++;
                    } else {
                        $current[$tag] = array(
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
                    $current = &$current[$tag][$last_item_index];
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
                        $current[$tag] = array(
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
                $current = &$parent[$level - 1];
            }
        }
        return ($xml_array);
    }

    public function getHeaderCode()
    {
        if ($this->type == 'xml') {
            $header = $this->header->{'MSH.10'}->__toString();
            $header = explode("_", $header);
            if (!empty($header[1])) {
                return $header[1];
            } else {
                return false;
            }
        } else {
            return $this->header[10];
        }
    }

    public function getResults()
    {
        return $this->results;
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
                    $data[$key . '.' . $count] = $child->__toString();
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
                    $array[$key . '.' . $count] = $child->__toString();
                    $count++;
                }
            }
        }
        return $array;
    }
}
