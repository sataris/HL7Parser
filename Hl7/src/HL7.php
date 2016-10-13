<?php

namespace Sataris\HL7Parser;

/**
 * This class allows for the easily manipulation of files that are in the HL7 format.
 */
class HL7
{

    protected $file_content;

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
    protected function parseAsXml()
    {
    }
}
