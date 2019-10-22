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
    'wheelbase' => 157,
]);

var_dump([
    'year' => $response->year(),
    'make' => $response->make(),
    'model' => $response->model(),
    'trim' => $response->trim(),
    'drivetrain' => $response->drivetrain(),
]);
```

Above code will properly return color-matched photos and autocorrected information about the vehicle!

```
array(8) {
  ["year"]=>string(4) "2018"
  ["make"]=>string(4) "Ford"
  ["model"]=>string(5) "F-150"
  ["trim"]=>string(10) "King Ranch"
  ["drivetrain"]=>string(16) "Four Wheel Drive"
}
```
