# ChromeData PHP SDK
####PHP 7 library for the ChromeData Automotive Description Service (ADS) and ChromeData Incentives APIs

```
<?php

require __DIR__ . '/vendor/autoload.php';

$auth = new \Darinrandal\ChromeData\Credentials('123456', 'abcdefghijk123456');
$adapter = new \Darinrandal\ChromeData\Adapter\Guzzle($auth);
$adsRequest = new \Darinrandal\ChromeData\Request\ADS($adapter);

$response = $adsRequest->byVin('1FTEW1EG9JFB51465', [
    'trim' => 'king ranch lo',
    'exterior_color' => 'oxford',
    'wheelbase' => 157,
]);

$color = $response->matchExteriorColor();

var_dump([
    'photos' => $response->colorMatchedPhotos($color),
    'exterior_color' => $color->getColorName(),
    'exterior_color_base' => $color->getColorBase(),
    'year' => $response->vehicleYear(),
    'make' => $response->vehicleMake(),
    'model' => $response->vehicleModel(),
    'trim' => $response->vehicleTrim(),
    'drivetrain' => $response->vehicleDrivetrain(),
]);
```

Above code will properly return color-matched photos and autocorrected information about the vehicle!

```
array(8) {
  ["photos"]=>array(2) {
    [0]=>
    array(4) {
      ["colorCode"]=>
      string(2) "YZ"
      ["shot"]=>
      int(1)
      ["url"]=>
      string(181) "http://media.chromedata.com/MediaGallery/media/Mjk2MjIxXk1lZGlhIEdhbGxlcnk/pCIEWZJxPLFV8BU94ufZzJ0TAc7BGeX17cAXcxAFP6HAmNjPM6AaqRmA_Hd1tdCauGGKOZNDdJo/cc_2018FOT110002_01_640_YZ.png"
      ["secondaryColorCode"]=>
      string(0) ""
    }
    [1]=>
    array(4) {
      ["colorCode"]=>
      string(2) "YZ"
      ["shot"]=>
      int(3)
      ["url"]=>
      string(167) "http://media.chromedata.com/MediaGallery/media/Mjk2MjIxXk1lZGlhIEdhbGxlcnk/pCIEWZJxPLFV8BU94ufZzPOupQd-9JmfVsN47LbxrS5yHanAfRZaZymdM_N6dZQm/cc_2018FOT110002_640_YZ.png"
      ["secondaryColorCode"]=>
      string(0) ""
    }
  }
  ["exterior_color"]=>string(12) "Oxford White"
  ["exterior_color_base"]=>string(5) "White"
  ["year"]=>string(4) "2018"
  ["make"]=>string(4) "Ford"
  ["model"]=>string(5) "F-150"
  ["trim"]=>string(10) "King Ranch"
  ["drivetrain"]=>string(16) "Four Wheel Drive"
}
```
