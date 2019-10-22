# ChromeData PHP SDK
####PHP 7 library for the ChromeData Automotive Description Service (ADS) and ChromeData Incentives APIs

```
<?php

require __DIR__ . '/vendor/autoload.php';

$auth = new \Darinrandal\ChromeData\Credentials('<ACCOUNT_NUMBER>', '<ACCOUNT_SECRET>');
$adapter = new \Darinrandal\ChromeData\Adapter\Guzzle($auth);
$adsRequest = new \Darinrandal\ChromeData\Request\ADS($adapter);

$response = $adsRequest->byVin('5TDZT38A91S055073');

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
  ["year"]=>string(4) "2001"
  ["make"]=>string(6) "Toyota"
  ["model"]=>string(7) "Sequoia"
  ["transmission"]=>string(9) "Automatic"
  ["trim"]=>string(7) "Limited"
  ["drivetrain"]=>string(16) "Four Wheel Drive"
}
```
