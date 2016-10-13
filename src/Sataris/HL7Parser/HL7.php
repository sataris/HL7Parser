<?php

namespace Sataris\HL7Parser;

use Sataris\HL7Parser\Patient;

/**
 * This class allows for the easily manipulation of files that are in the HL7 format.
 */
class HL7
{

    private $file_content;

    protected $patient;

    protected $result;

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
        $this->patient = new Patient($patient[0], 'xml');
    }

    private function setResultXML()
    {
        $this->results = $this->file_content->xpath('//OBX');
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
}
