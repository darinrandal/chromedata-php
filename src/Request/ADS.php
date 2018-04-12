<?php

namespace Darinrandal\ChromeData\Request;

use Darinrandal\ChromeData\Response\ADSResponse;

class ADS extends Request
{
    public function byVIN(string $vin, ?string $trim = null, ?string $wheelbase = null): ADSResponse
    {
        $response = $this->adapter->getConnection()->post('http://services.chromedata.com/Description/7a', [
            'headers' => [
                'MIME-Version: 1.0',
                'Content-Type: text/xml; charset=utf-8'
            ],
            'body' => $this->getXml($vin, $trim, $wheelbase),
        ]);

        return new ADSResponse($response);
    }

    protected function getXml(string $vin, ?string $trim = null, ?string $wheelbase = null)
    {
        $this->adapter->getAuth()->getAccountNumber();

        // Request needs Wheelbase set to 0 to not use it
        if (empty($wheelbase)) {
            $wheelbase = '0';
        }

        return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:description7a.services.chrome.com">
    <soapenv:Header/>
    <soapenv:Body>
        <urn:VehicleDescriptionRequest>
            <urn:accountInfo 
                number="{$this->adapter->getAuth()->getAccountNumber()}" 
                secret="{$this->adapter->getAuth()->getAccountSecret()}" 
                country="US" language="en" behalfOf="?"/>
            <urn:vin>{$vin}</urn:vin>
            <urn:trimName>{$trim}</urn:trimName>
            <urn:wheelBase>{$wheelbase}</urn:wheelBase>
            <urn:switch>ShowAvailableEquipment</urn:switch>
            <urn:switch>ShowExtendedDescriptions</urn:switch>
            <urn:vehicleProcessMode>ExcludeFleetOnly</urn:vehicleProcessMode>
            <urn:optionsProcessMode>ExcludeFleetOnly</urn:optionsProcessMode>
            <urn:includeMediaGallery>ColorMatch</urn:includeMediaGallery>
        </urn:VehicleDescriptionRequest>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }

}