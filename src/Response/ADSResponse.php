<?php

namespace Darinrandal\ChromeData\Response;

use Psr\Http\Message\ResponseInterface;

class ADSResponse
{
    protected $rawResponse;

    protected $xml;

    public function __construct(ResponseInterface $rawResponse)
    {
        $this->rawResponse = $rawResponse;

        $this->xml = $this->convertToXmlObject($rawResponse->getBody()->getContents());
    }

    public function getStyles()
    {
        $styles = [];

        foreach ($this->xml->style as $style) {
            $styles[] = [
                'id' => (int) $style['id'],
                'name' => (string) $style['nameWoTrim'],
                'body' => (string) $style['altBodyType'],
                'doors' => (string) $style['passDoors'],
                'drivetrain' => (string) $style['drivetrain'],
            ];
        }

        return $styles;
    }

    private function convertToXmlObject($chromeXml)
    {
        $bodyStart = strpos($chromeXml, "<S:Body>") + 8;
        $bodyEnd = strrpos($chromeXml, "</S:Body>");

        if ($bodyStart <= 0 || $bodyEnd <= 0) {
            throw new ResponseDecodeException('ChromeDataADS: Failure to determine body start');
        }

        try {
            $xmlObject = new \SimpleXMLElement(substr($chromeXml, $bodyStart, $bodyEnd - $bodyStart), LIBXML_NOWARNING);
        } catch (\Exception $e) {
            $bodyStart = strpos($chromeXml, '<faultstring>') + 13;
            $bodyEnd = strrpos($chromeXml, "</faultstring>");

            throw new ResponseDecodeException('ChromeDataADS: Error received from server: ' . substr($chromeXml, $bodyStart, $bodyEnd - $bodyStart));
        }

        if (isset($xmlObject->responseStatus['responseCode']) && strtolower((string) $xmlObject->responseStatus['responseCode']) == 'unsuccessful') {
            if (is_array($xmlObject->responseStatus->status)) {
                $status = (string) current($xmlObject->responseStatus->status);
            } else {
                $status = (string) $xmlObject->responseStatus->status;
            }

            throw new \HttpResponseException('ChromeDataADS: Unsuccessful request: ' . $status);
        }

        return $xmlObject;
    }
}