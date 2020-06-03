<?php

namespace libAmo;

interface ParamsInterface
{
    public function addAuth($key, $value);

    public function addDomain($value);

    public function addLogin($value);

    public function addApiKey($value);

    public function addAccessToken($value);
}