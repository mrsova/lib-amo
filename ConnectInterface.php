<?php


namespace libAmo;

use libAmo\Request\Params;

interface ConnectInterface
{
    public function setParams(ParamsInterface $params): ParamsInterface;
}