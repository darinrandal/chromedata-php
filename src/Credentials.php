<?php

namespace Darinrandal\ChromeData;


class Credentials
{
    protected $accountNumber;

    protected $accountSecret;

    public function __construct(string $accountNumber, string $accountSecret)
    {
        $this->accountNumber = $accountNumber;
        $this->accountSecret = $accountSecret;
    }

    public function getAccountNumber(): string
    {
        return $this->accountNumber;
    }

    public function getAccountSecret(): string
    {
        return $this->accountSecret;
    }
}