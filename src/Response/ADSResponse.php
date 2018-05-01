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
    protected $response;

    protected $matchedStyle;

    protected $vehicle;

    protected $status;

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
     * @param \StdClass $response
     * @param array $vehicle
     * @throws ChromeDataRequestException
     */
    public function __construct(\stdClass $response, array $vehicle = [])
    {
        $this->response = $response;

        $this->status = $response->responseStatus->responseCode ?? 'Unsuccessful';

        $this->vehicle = $vehicle;
    }

    /**
     * Returns an array of color-matched photos. Requires ADSColor parameter returned from findMatchingColors($color)
     *
     * @param int $width
     * @param string $background
     * @return array
     */
    public function photos(
        int $width = self::PHOTO_SIZE_MEDIUM,
        string $background = self::PHOTO_BACKGROUND_TRANSPARENT
    ): array
    {
        $images = [];

        foreach ($this->response->style->mediaGallery->colorized as $color) {
            if (
                // Images are labeled with a match bool flag whenever they made the color sent in
                empty($color->match) ||
                $color->backgroundDescription !== $background ||
                $color->width !== $width
            ) {
                continue;
            }

            $images[] = (array) $color;
        }

        /*
         * If we've been provided a color and there's more than 3 images that match
         * Only use the images that do not have a secondaryColorCode (otherwise you'll get duplicates)
         */
        if (count($images) > 3) {
            $images = array_filter($images, function ($value) {
                return !isset($value['secondaryColorOptionCode']);
            });
        }

        usort($images, function ($a, $b) {
            return $a['shotCode'] <=> $b['shotCode'];
        });

        return $images;
    }

    public function name(): ?string
    {
        return implode(' ', array_filter([$this->year(), $this->make(), $this->model(), $this->styleName()]));
    }

    public function color(): ?ADSColor
    {
        $color = null;

        foreach ($this->response->exteriorColor as $exteriorColor) {
            if (!empty($exteriorColor->installed)) {
                $color = $exteriorColor;
            }
        }

        return $color ? new ADSColor($color) : $color;
    }

    public function doors(): ?int
    {
        return $this->response->style->passDoors ?? null;
    }

    public function year(): ?string
    {
        return $this->response->style->modelYear ?? null;
    }

    public function make(): ?string
    {
        return $this->response->style->division->_ ?? null;
    }

    public function model(): ?string
    {
        return $this->response->style->model->_ ?? null;
    }

    public function engine(): ?array
    {
        if (!isset($this->response->engine)) {
            return null;
        }

        return [
            'type' => $this->response->engine->engineType->_,
            'fuel' => $this->response->engine->fuelType->_,
            'horsepower' => (array) $this->response->engine->horsepower,
            'torque' => (array) $this->response->engine->netTorque,
            'cylinders' => $this->response->engine->cylinders,
            'displacement' => [
                'value' => $this->response->engine->displacement->value->_,
                'unit' =>  $this->response->engine->displacement->value->unit,
            ],
            'fuelEconomy' => [
                'city' => $this->response->engine->fuelEconomy->city->low,
                'hwy' => $this->response->engine->fuelEconomy->hwy->low,
                'unit' => $this->response->engine->fuelEconomy->unit,
            ],
            'fuelCapacity' => [
                'value' => $this->response->engine->fuelCapacity->low,
                'unit' => $this->response->engine->fuelCapacity->unit,
            ],
        ];
    }

    public function standardFeatures(): array
    {
        $standardFeatures = [];

        foreach ($this->response->standard as $feature) {
            $standardFeatures[ucfirst(strtolower($feature->header->_))][] = $feature->description;
        }

        return $standardFeatures;
    }

    public function genericEquipment(): array
    {
        $genericEquipment = [];

        foreach ($this->response->genericEquipment as $equipment) {
            $genericEquipment[$equipment->definition->group->_][$equipment->definition->header->_][] = $equipment->definition->category->_;
        }

        return $genericEquipment;
    }

    public function consumerInfo(): array
    {
        $consumerInfo = [];

        foreach ($this->response->consumerInformation as $info) {
            $items = [];

            foreach ((array) $info->item as $item) {
                $items[] = $item->name . ': ' . $item->value;
            }

            $consumerInfo[$info->type->_] = $items;
        }

        return $consumerInfo;
    }

    public function bodyStyle(): ?array
    {
        $bodyTypes = [
            $this->response->style->altBodyType ?? null,
        ];

        foreach ((array) $this->response->style->bodyType as $bodyType) {
            $bodyTypes[] = $bodyType->_;
        }

        return $bodyTypes;
    }

    public function trim(): ?string
    {
        return $this->response->style->trim ?? null;
    }

    public function drivetrain(): ?string
    {
        return $this->response->style->drivetrain ?? null;
    }

    public function styleName(): ?string
    {
        return $this->response->style->name ?? null;
    }

    public function styleId(): ?int
    {
        return $this->response->style->id ?? null;
    }

    public function factoryOptions(): ?array
    {
        $options = [];

        foreach ($this->response->factoryOption as $option) {
            $options[] = [
                'name' => is_array($option->description) ? $option->description[0] : $option->description,
                'description' => is_array($option->description) ? $option->description[1] : null,
                'code' => $option->altOptionCode ?? null,
                'chromeCode' => $option->chromeCode ?? null,
                'oemCode' => $option->oemCode ?? null,
                'price' => $option->price->msrpMax ?? null,
            ];
        }

        return $options;
    }

    /**
     * Returns number of styles available for the vehicle. Less is better!
     *
     * @return int
     */
    public function stylesCount(): int
    {
        return is_array($this->response->style) ? count($this->response->style) : 1;
    }

    public function exactMatchStyle(): bool
    {
        return $this->stylesCount() === 1;
    }

    /**
     * Takes provided drivetrain and standardizes it to a 3 letter abbreviation
     * @param string $driveTrain Drivetrain provided for a vehicle
     * @return string
     */
    public static function convertDriveTrain(?string $driveTrain): ?string
    {
        $driveTrain = trim(strtolower($driveTrain));

        if (static::startsWith($driveTrain, 'front')) {
            $driveTrain = 'fwd';
        } elseif (static::startsWith($driveTrain, 'rear')) {
            $driveTrain = 'rwd';
        } elseif (static::startsWith($driveTrain, 'four')) {
            $driveTrain = '4wd';
        } elseif (static::startsWith($driveTrain, 'all')) {
            $driveTrain = 'awd';
        } elseif (!in_array($driveTrain, ['fwd', 'rwd', '4wd', 'awd'])) {
            return null;
        }

        return strtoupper($driveTrain);
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }
}
