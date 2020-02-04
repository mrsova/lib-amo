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
     * ClientAmo constructor
     *
     * @param string $domain Поддомен или домен amoCRM
     * @param string $login Логин amoCRM
     * @param string $apikey Ключ пользователя amoCRM
     * @param string|null $proxy Прокси сервер для отправки запроса
     */
    public function __construct($domain, $login, $apikey, $proxy = null)
    {
        // Разернуть поддомен в полный домен
        if (strpos($domain, '.') === false) {
            $domain = sprintf('%s.amocrm.ru', $domain);
        }
        $this->parameters = new Params();

        $this->parameters->addAuth('domain', $domain);
        $this->parameters->addAuth('login', $login);
        $this->parameters->addAuth('apikey', $apikey);
        if ($proxy !== null) {
            $this->parameters->addProxy($proxy);
        }
        $this->curlHandle = new CurlHandle();

        $this->request = new Request($this->parameters, $this->curlHandle);
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