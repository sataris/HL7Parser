<?php

namespace Sataris\HL7Parser;

class Patient extends HL7
{

    protected $setId;

    protected $patientId;

    protected $patientIdList;

    protected $alternateId;

    protected $name;

    protected $motherMaidenName;

    protected $dob;

    protected $sex;

    protected $patientAlias;

    protected $race;

    protected $address;

    protected $countryCode;

    protected $homePhone;

    protected $workPhone;

    protected $primaryLanguage;

    protected $maritalStatus;

    protected $religion;

    protected $patientAccountNumber;

    protected $motherIdentifier;

    protected $ethnicGroups;

    protected $birthPlace;

    protected $citizenShip;

    protected $ssn;

    protected $dln;

    protected $multipleBirthIndicator;

    protected $birthOrder;

    protected $citizenship;

    protected $nationality;

    protected $vetMemberNumber;

    protected $type;

    public function __construct($content, $type)
    {
        $this->type = $type;
        switch ($type) {
            case "xml":
                $this->parseXML($content);
                break;
            default:
                $this->parseORU($content);
                break;
        }
    }

    private function parseXML($xml)
    {
        $this->setId = $this->setSetId($xml->{'PID.1'});
        $this->patientId = $this->setPatientId($xml->{'PID.2'});
        $this->patientIdList = $this->setPatientIdenfitiferList($xml->{'PID.3'});
        $this->alternateId = $this->setPatientIdenfitiferList($xml->{'PID.4'});
        $this->name = $this->setPatientName($xml->{'PID.5'});
        $this->motherMaidenName = $this->setPatientMotherMaidenName($xml->{'PID.6'});
        $this->dob = $this->setPatientDOB($xml->{'PID.7'});
        $this->sex = $this->setPatientSex($xml->{'PID.8'});
        $this->patientAlias = $this->setPatientIdenfitiferList($xml->{'PID.9'});
        $this->race = $this->setPatientRace($xml->{'PID.10'});
        $this->address = $this->setPatientAddress($xml->{'PID.11'});
        $this->countryCode = $this->setPatientCC($xml->{'PID.12'});
        $this->homePhone = $this->setPatientHomePhone($xml->{'PID.13'});
        $this->workPhone = $this->setPatientWorkPhone($xml->{'PID.14'});
        $this->primaryLanguage = $this->setPrimaryLanguage($xml->{'PID.15'});
        $this->maritalStatus = $this->setMaritalStatus($xml->{'PID.16'});
        $this->religion = $this->setReligion($xml->{'PID.17'});
        $this->patientAccountNumber = $this->setPatientAccountNumber($xml->{'PID.18'});
        $this->ssn = $this->SetSSN($xml->{'PID.19'});
        $this->dln = $this->setDLN($xml->{'PID.20'});
        $this->motherIdentifier = $this->setMID($xml->{'PID.21'});
        $this->ethnicGroups = $this->setEthnicGroup($xml->{'PID.22'});
        $this->birthPlace = $this->setBirthPlace($xml->{'PID.23'});
        $this->multipleBirthIndicator = $this->setMBI($xml->{'PID.24'});
        $this->birthOrder = $this->setBirthOrder($xml->{'PID.25'});
        $this->citizenship = $this->setCitizenship($xml->{'PID.26'});
        $this->vetMemberNumber = $this->setVMN($xml->{'PID.27'});
        $this->nationality = $this->setNationality($xml->{'PID.28'});
    }

    private function parseORU($content)
    {
        foreach ($content as $key => $value) {
            $content[$key] = explode('^', $value);
        }
        $this->setId = $content[1];
        $this->patientId = $content[2];
        $this->patientIdList = $content[3];
        $this->alternateId = $content[4];
        $this->name = $content[5];
        $this->motherMaidenName = $content[6];
        $this->dob = $content[7];
        $this->sex = $content[8];
        $this->patientAlias = $content[9];
        $this->race = $content[10];
        $this->address = $content[11];
        $this->countryCode = $content[12];
        $this->homePhone = $content[13];
        $this->workPhone = $content[14];
        $this->primaryLanguage = $content[15];
        $this->maritalStatus = $content[16];
        $this->religion = $content[17];
        $this->patientAccountNumber = $content[18];
        $this->ssn = $content[19];
        $this->dln = $content[20];
        $this->motherIdentifier = $content[21];
        $this->ethnicGroups = $content[22];
        $this->birthPlace = $content[23];
        $this->multipleBirthIndicator = $content[24];
        $this->birthOrder = $content[25];
        $this->citizenship = $content[26];
        $this->vetMemberNumber = $content[27];
        $this->nationality = $content[28];
    }

    private function setSetId($xml)
    {
        $array = [];
        if (!empty(trim($xml->__toString()))) {
            $array['PID.1'] = $xml->__toString();
        }
        if (isset($xml->{'SI'})) {
            $array['SI.1'] = $xml->{'SI'}->__toString();
        }
            
        return $array;
    }

    private function setPatientId($xml)
    {
        $array = [];
        if (!empty(trim($xml->__toString()))) {
            $array['ID.1'] = $xml->__toString();
        }
        if (isset($xml->{'CX.1'})) {
            $array['CX.1'] = $xml->{'CX.1'}->__toString();
        }

        return $array;
    }

    private function setPatientIdenfitiferList($xml)
    {
        $array = [];
        $count = 1;
        if ($xml->count() > 0) {
            foreach ($xml->children() as $child) {
                if (!empty(trim($child->__toString()))) {
                    $array['CX.' . $count][] = $child->__toString();
                }
            }
            if (count($array) > 0) {
                $this->patientIdList = $array;
            }
        }
        return $array;
    }

    private function setPatientName($xml)
    {
        $array = [];
        $count = 1;
        if (!empty(trim($xml->__toString()))) {
            $array['XPN.' . $count] = $xml->__toString();
        }
        if (!empty($xml->{'XPN.1'})) {
            foreach ($xml->{'XPN.1'}->children() as $child) {
                if (!empty(trim($child->__toString()))) {
                    $array['XPN.1']['FN.' . $count] = $child->__toString();
                    $count++;
                }
            }
        }
        return $array;
    }

    private function setPatientMotherMaidenName($xml)
    {
        return $this->readSingleXML($xml, 'XPN');
    }

    private function setPatientDOB($xml)
    {
        return $this->readSingleXML($xml, 'TS');
    }

    private function setPatientSex($xml)
    {
        return $this->readSingleXML($xml, 'IS');
    }

    private function setPatientRace($xml)
    {
        return $this->readRepeatingXML($xml, 'CE');
    }

    private function setPatientAddress($xml)
    {
        return $this->readRepeatingXML($xml, 'XAD');
    }

    private function setPatientCC($xml)
    {
       
        return $this->readSingleXML($xml, 'IS');
    }

    private function setPatientHomePhone($xml)
    {
        return $this->readRepeatingXML($xml, 'XTN');
    }

    private function setPatientWorkPhone($xml)
    {
        return $this->readRepeatingXML($xml, 'XTN');
    }

    private function setPrimaryLanguage($xml)
    {
         return $this->readSingleXML($xml, 'CE');
    }

    private function setMaritalStatus($xml)
    {
        return $this->readSingleXML($xml, 'CE');
    }

    private function setReligion($xml)
    {
        return $this->readSingleXML($xml, 'CE');
    }

    private function setPatientAccountNumber($xml)
    {
        return $this->readSingleXML($xml, 'CX');
    }

    private function setSSN($xml)
    {
        return $this->readSingleXML($xml, 'ST');
    }

    private function setDLN($xml)
    {
        return $this->readSingleXML($xml, 'DLN');
    }

    private function setMID($xml)
    {
        return $this->readRepeatingXML($xml, 'CX');
    }

    private function setEthnicGroup($xml)
    {
        return $this->readRepeatingXML($xml, 'CE');
    }

    private function setBirthPlace($xml)
    {
        return $this->readRepeatingXML($xml, 'ST');
    }

    private function setMBI($xml)
    {
        return $this->readSingleXML($xml, 'ID');
    }

    private function setBirthOrder($xml)
    {
        return $this->readSingleXML($xml, 'NM');
    }

    private function setCitizenship($xml)
    {
        return $this->readRepeatingXML($xml, 'CE');
    }

    private function setVMN($xml)
    {
        return $this->readSingleXML($xml, 'CE');
    }

    private function setNationality($xml)
    {
        return $this->readSingleXML($xml, 'CE');
    }

    private function setDeathDateTime($xml)
    {
        return $this->readSingleXML($xml, 'TS');
    }

    private function setDeathIndicator($xml)
    {
        return $this->readSingleXML($xml, 'ID');
    }

    public function getId()
    {
        if ($this->type == 'oru') {
            $array = array();
            if (!empty($this->patientId[0])) {
                $array = array_merge($this->patientId, $array);
            }
            if (!empty($this->patientIdList[0])) {
                $array = array_merge($this->patientIdList, $array);
            }
            if (!empty($this->alternateId[0])) {
                $array = array_merge($this->alternateId, $array);
            }
            return $array;
        }
        return $this->patientId;
    }

    public function getName()
    {
        $fullname = '';
        if ($this->type == 'oru') {
            foreach ($this->name as $name) {
                $fullname = $fullname . ' ' . $name;
            }
        } else {
            if (!empty($this->name['XPN.1'])) {
                foreach ($this->name['XPN.1'] as $name) {
                    $fullname = $fullname ." " . $name;
                }
            }
        }
        return trim($fullname);
    }
}
