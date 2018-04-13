<?php

namespace Darinrandal\ChromeData\Request;

use Darinrandal\ChromeData\Response\ADSResponse;

class ADS extends Request
{
    /**
     * Automotive Description Service Endpoint
     */
    const ADS_ENDPOINT = 'http://services.chromedata.com/Description/7a';

    protected $vehicle = [];

    /**
     * Performs a Automotive Description Service request to ChromeData by VIN
     * Returns an ADSResponse object to access and retrieve VIN data.
     *
     * Pass in an array of vehicle data to increase the change of an exact-match style
     * ['trim' => 'Lariat', 'wheelbase' => 157, 'exterior_color' => 'White Metallic', 'drivetrain' => 'AWD']
     *
     * @param string $vin
     * @param array $vehicle
     * @return ADSResponse
     * @throws \Darinrandal\ChromeData\Response\ResponseDecodeException
     * @throws \HttpResponseException
     */
    public function byVIN(
        string $vin,
        array $vehicle = []
    ): ADSResponse
    {
        if (!$this->validateVIN($vin)) {
            throw new \InvalidArgumentException('VIN doesn\'t pass checksum validation: ' . $vin);
        }

        $this->vehicle = $vehicle;

        $response = $this->adapter->getConnection()->post(static::ADS_ENDPOINT, [
            'headers' => [
                'MIME-Version: 1.0',
                'Content-Type: text/xml; charset=utf-8'
            ],
            'body' => $this->getXml($vin, $this->vehicle['trim'] ?? '', $this->vehicle['wheelbase'] ?? ''),
        ]);

        return new ADSResponse($response, $vehicle);
    }

    /**
     * Returns the formatted XML after substituting VIN, Trim, Wheelbase, and credentials from the Auth adapter
     *
     * @param string $vin
     * @param null|string $trim
     * @param null|string $wheelbase
     * @return string
     */
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

    /**
     * Verifies a VIN checksum is valid
     *
     * @param string $vin
     * @return bool
     */
    protected function validateVIN(string $vin): bool
    {
        $vin = strtolower($vin);

        if (!preg_match('/^[^\Wioq]{17}$/', $vin)) {
            return false;
        }

        $weights = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];

        $transliterations = [
            'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8,
            'j' => 1, 'k' => 2, 'l' => 3, 'm' => 4, 'n' => 5, 'p' => 7, 'r' => 9, 's' => 2,
            't' => 3, 'u' => 4, 'v' => 5, 'w' => 6, 'x' => 7, 'y' => 8, 'z' => 9,
        ];

        $sum = 0;
        $vinLength = strlen($vin);

        for ($i = 0; $i < $vinLength; $i++) {
            if (!is_numeric($vin{$i})) {
                $sum += $transliterations[$vin{$i}] * $weights[$i];
            } else {
                $sum += $vin{$i} * $weights[$i];
            }
        }

        $checkDigit = $sum % 11;

        return ($checkDigit === 10 ? 'x' : $checkDigit) == $vin{8};
    }
}
