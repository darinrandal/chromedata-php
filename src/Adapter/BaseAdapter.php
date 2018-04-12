<?php

namespace Darinrandal\ChromeData\Adapter;

use Darinrandal\ChromeData\Credentials;
use GuzzleHttp\Client;

class BaseAdapter implements Adapter
{
    protected $auth;

    protected $guzzle;

    public function __construct(Credentials $auth)
    {
        $this->auth = $auth;

        $this->guzzle = new Client;
    }

    public function getAuth(): Credentials
    {
        return $this->auth;
    }

    public function getConnection(): Client
    {
        return $this->guzzle;
    }

}