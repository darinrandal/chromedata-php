<?php

namespace Darinrandal\ChromeData\Adapter;


use Darinrandal\ChromeData\Credentials;
use GuzzleHttp\Client;

interface Adapter
{
    public function __construct(Credentials $auth);

    public function getAuth(): Credentials;

    public function getConnection(): Client;
}