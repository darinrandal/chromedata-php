<?php

namespace Darinrandal\ChromeData\Response;

class ADSColor
{
    /**
     * Stores the color code of the current vehicle color
     *
     * @var string
     */
    protected $colorCode;

    /**
     * Stores the color name of the current vehicle color
     *
     * @var string
     */
    protected $colorName;

    /**
     * Stores the base/generic color of the current vehicle color
     *
     * @var string
     */
    protected $colorBase;

    /**
     * Pass in a SimpleXMLElement for the exteriorColors object and get back a ADSColor object.
     *
     * ADSColor constructor.
     * @param \stdClass $colorElement
     */
    public function __construct(\stdClass $colorElement)
    {
        $this->colorCode = $colorElement->colorCode;
        $this->colorName = $colorElement->colorName;
        $this->colorBase = $colorElement->genericColor->name;
    }

    /**
     * Returns the color code
     *
     * @return string
     */
    public function getColorCode(): string
    {
        return $this->colorCode;
    }

    /**
     * Returns the color name
     *
     * @return string
     */
    public function getColorName(): string
    {
        return $this->colorName;
    }

    /**
     * Returns the base/generic color of the current color
     *
     * @return string
     */
    public function getColorBase(): string
    {
        return $this->colorBase;
    }

    /**
     * Convert to json for stringify
     *
     * @return string
     */
    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    public function toArray(): array
    {
        return [
            'colorCode' => $this->colorCode,
            'colorName' => $this->colorName,
            'colorBase' => $this->colorBase,
        ];
    }
}
