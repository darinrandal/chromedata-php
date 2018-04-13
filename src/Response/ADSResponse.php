<?php

namespace Darinrandal\ChromeData\Response;

use Psr\Http\Message\ResponseInterface;

class ADSResponse
{
    /**
     * Stores the raw response from ChromeData API
     *
     * @var ResponseInterface
     */
    protected $rawResponse;

    /**
     * Stores the SimpleXML object for data retrieval
     *
     * @var \SimpleXMLElement
     */
    protected $xml;

    /**
     * Chrome StyleId of the working style.
     *
     * @var int|null
     */
    protected $preferredStyle;

    protected $matchedStyle;

    protected $vehicle;

    /**
     * 320x240 images
     */
    const PHOTO_SIZE_SMALL = 320;

    /**
     * 640x480 images
     */
    const PHOTO_SIZE_MEDIUM = 640;

    /**
     * 1280x960 images
     */
    const PHOTO_SIZE_LARGE = 1280;

    /**
     * 2100x1575 images
     */
    const PHOTO_SIZE_EXTRA_LARGE = 2100;

    /**
     * Use images with a white background (jpg)
     */
    const PHOTO_BACKGROUND_WHITE = 'White';

    /**
     * Use images with a transparent background (png)
     */
    const PHOTO_BACKGROUND_TRANSPARENT = 'Transparent';

    /**
     * ADSResponse constructor.
     * @param ResponseInterface $rawResponse
     * @param array $vehicle
     * @throws ResponseDecodeException
     * @throws \HttpResponseException
     */
    public function __construct(ResponseInterface $rawResponse, array $vehicle = [])
    {
        $this->rawResponse = $rawResponse;

        $this->vehicle = $vehicle;

        $this->xml = $this->convertToXmlObject($rawResponse->getBody()->getContents());

        if ($this->stylesCount() === 1) {
            $this->preferredStyle = key($this->styles());
            $this->matchedStyle = current($this->styles());
        }
    }

    /**
     * Returns an array of color-matched photos. Requires ADSColor parameter returned from findMatchingColors($color)
     *
     * @param ADSColor|null $ADSColor
     * @param int $width
     * @param string $background
     * @return array
     */
    public function colorMatchedPhotos(
        ?ADSColor $ADSColor = null,
        int $width = self::PHOTO_SIZE_MEDIUM,
        string $background = self::PHOTO_BACKGROUND_TRANSPARENT
    ): array
    {
        $images = [];

        foreach ($this->xml->style as $style) {
            if ($this->preferredStyle && (int) $style['id'] != $this->preferredStyle) {
                continue;
            }

            if (!$style->mediaGallery->colorized->count()) {
                continue;
            }

            foreach ($style->mediaGallery->colorized as $color) {
                if ((string) $color['backgroundDescription'] != $background || (int) $color['width'] != $width) {
                    continue;
                }

                if ($ADSColor && $ADSColor->getColorCode() != (string) $color['primaryColorOptionCode']) {
                    continue;
                }

                $images[] = [
                    'colorCode' => (string) $color['primaryColorOptionCode'],
                    'shot' => (int) $color['shotCode'],
                    'url' => (string) $color['url'],
                    'secondaryColorCode' => (string) $color['secondaryColorOptionCode'],
                ];
            }
        }

        /*
         * If we've been provided a color and there's more than 3 images that match
         * Only use the images that do not have a secondaryColorCode (otherwise you'll get duplicates)
         */
        if ($ADSColor && count($images) > 3) {
            $images = array_filter($images, function ($value) {
                return $value['secondaryColorCode'] === '';
            });
        }

        usort($images, function ($a, $b) {
            return $a['shot'] <=> $b['shot'];
        });

        return $images;
    }

    public function vehicleYear(): ?string
    {
        return $this->matchedStyle['year'] ?? null;
    }

    public function vehicleMake(): ?string
    {
        return $this->matchedStyle['make'] ?? null;
    }

    public function vehicleModel(): ?string
    {
        return $this->matchedStyle['model'] ?? null;
    }

    public function vehicleTrim(): ?string
    {
        return $this->matchedStyle['trim'] ?? null;
    }

    public function vehicleDrivetrain(): ?string
    {
        return $this->matchedStyle['drivetrain'] ?? null;
    }

    /**
     * Get the count of exterior colors available
     *
     * @return int
     */
    public function exteriorColorsCount(): int
    {
        return $this->xml->exteriorColor->count();
    }

    /**
     * Returns an array of all available exterior colors
     *
     * @return array
     */
    public function exteriorColors(): array
    {
        $exteriorColors = [];

        foreach ($this->xml->exteriorColor as $color) {
            // Skip any exterior colors that don't match our style id
            if ($this->preferredStyle && (int) $color->styleId != $this->preferredStyle) {
                continue;
            }

            $exteriorColors[] = [
                'code' => (string) $color['colorCode'],
                'name' => (string) $color['colorName'],
                'generic' => (string) $color->genericColor['name'],
            ];
        }

        return $exteriorColors;
    }

    /**
     * Find matching ExteriorColor objects by a color name and (optionally) color code. Returns an ADSColor
     * object used by colorMatchedPhotos.
     *
     * $colorName can be a generic color name, color code, or brand-specific color such as Banana Pearl Metallic
     *
     * @param string $colorName
     * @param null|string $colorCode
     * @return ADSColor
     */
    public function matchExteriorColor(?string $colorName = null, ?string $colorCode = null)
    {
        if ($colorName === null) {
            $colorName = $this->vehicle['exterior_color'] ?? null;

            if ($colorName === null) {
                throw new \InvalidArgumentException('No color provided');
            }
        }

        $matchedExtColors = [];
        $possibleMatchedExtColors = [];

        foreach ($this->xml->exteriorColor as $extColor) {
            // Skip any exterior colors that don't match our style id
            if ($this->preferredStyle && (int) $extColor->styleId != $this->preferredStyle) {
                continue;
            }

            $extColorCode = trim(strtolower($extColor['colorCode']));
            $extColorName = trim(strtolower($extColor['colorName']));

            // Default to something that'll have no matches for the strpos (it needs a non-empty needle)
            $vehicleExtColor = trim(strtolower($colorName)) ?: '****';
            $vehicleExtColorCode = trim(strtolower($colorCode)) ?: strtok($vehicleExtColor, '/');

            if ($vehicleExtColor == $extColorName ||
                $vehicleExtColor == $extColorCode ||
                $vehicleExtColorCode == $extColorName ||
                $vehicleExtColorCode == $extColorCode
            ) {
                $matchedExtColors[] = $extColor;
            }

            // Hack to fix incoming data. Some color codes have weird styles
            if (str_replace(['0', 'o'], ['o', '0'], $vehicleExtColor) == $extColorName ||
                str_replace(['0', 'o'], ['o', '0'], $vehicleExtColor) == $extColorCode ||
                str_replace(['0', 'o'], ['o', '0'], $vehicleExtColorCode) == $extColorName ||
                str_replace(['0', 'o'], ['o', '0'], $vehicleExtColorCode) == $extColorCode ||
                strpos($extColorName, $vehicleExtColor) !== false ||
                strpos($extColorCode, $vehicleExtColor) !== false ||
                strpos($extColorName, $vehicleExtColorCode) !== false ||
                strpos($extColorCode, $vehicleExtColorCode) !== false
            ) {
                $possibleMatchedExtColors[] = $extColor;
            }
        }

        if (count($matchedExtColors) === 1) {
            return new ADSColor($matchedExtColors[0]);
        } elseif (count($possibleMatchedExtColors) === 1) {
            return new ADSColor($possibleMatchedExtColors[0]);
        } elseif (count($possibleMatchedExtColors) > 1) {
            throw new \InvalidArgumentException($colorName . ' matched ' . count($possibleMatchedExtColors) . ' colors: ');
        }

        throw new \InvalidArgumentException($colorName . ' was not able to match any available colors');
    }

    /**
     * Returns number of styles available for the vehicle. Less is better!
     *
     * @return int
     */
    public function stylesCount(): int
    {
        return $this->xml->style->count();
    }

    /**
     * Returns an array of styles indexed by ID for the vehicle
     *
     * @return array
     */
    public function styles(): array
    {
        $styles = [];

        foreach ($this->xml->style as $style) {
            $styles[(int) $style['id']] = [
                'id' => (int) $style['id'],
                'name' => (string) $style['nameWoTrim'],
                'year' => (string) $style['modelYear'],
                'make' => (string) $style->division,
                'model' => (string) $style->model,
                'trim' => (string) $style['trim'],
                'body' => (string) $style['altBodyType'],
                'doors' => (string) $style['passDoors'],
                'drivetrain' => (string) $style['drivetrain'],
            ];
        }

        return $styles;
    }

    /**
     * Get Preferred Style Id used to determine what features, images, and packages a vehicle qualifies for
     *
     * @return int|null
     */
    public function getPreferredStyle(): ?int
    {
        return $this->preferredStyle;
    }

    /**
     * Set Preferred Style Id used to determine what features, images, and packages a vehicle qualifies for
     *
     * @param int $preferredStyle
     * @return bool
     */
    public function setPreferredStyle(int $preferredStyle): bool
    {
        if (! isset($this->styles()[$preferredStyle])) {
            return false;
        }

        $this->preferredStyle = $preferredStyle;

        return true;
    }

    /**
     * Converts string XML response to SimpleXMLObject for iteration and data retrieval
     *
     * @param string $chromeXml
     * @return \SimpleXMLElement
     * @throws ResponseDecodeException
     * @throws \HttpResponseException
     */
    private function convertToXmlObject(string $chromeXml)
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
