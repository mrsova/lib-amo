<?php

namespace libAmo;

use libAmo\Request\CurlHandle;
use libAmo\Request\Params;
use libAmo\Request\Request;

/**
 * Class ClientAmo
 * Основной класс для работы с api
 *
 * Модели для вызова через маг метод
 *
 */
class ClientAmo
{
    /**
     * @var Request
     */
    public $request = null;

    /**
     * @var Params|null Экземпляр Params для хранения аргументов
     */
    public $parameters = null;

    /**
     * @var CurlHandle Экземпляр CurlHandle для повторного использования
     */
    private $curlHandle;

    /**
     * ClientAmo constructor.
     * @param Params $params
     * @param null $proxy
     */
    public function __construct(Params $params, $proxy = null)
    {
        $this->parameters = $params;
        $this->curlHandle = new CurlHandle();
        $this->request = new Request($this->parameters, $this->curlHandle);
    }


    public static function create(ConnectInterface $connect)
    {
        $params = $connect->setParams(new Params());
        $client = new self($params);

        return $client;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function checkAuth()
    {
        $response = $this->request->getRequest('/private/api/auth.php', ['type'=>'json']);
        return $response['response'];
    }

    public function getRequest()
    {
        return $this->request;
    }
}