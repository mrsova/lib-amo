<?php

namespace libAmo\Request;

use DateTime;
use libAmo\Exception;

class Request
{
    /**
     * @var bool Использовать устаревшую схему авторизации
     */
    public $v1 = false;

    /**
     * @var bool Флаг вывода отладочной информации
     */
    private $debug = false;
    /**
     * @var Params|null Экземпляр ParamsBag для хранения аргументов
     */
    private $parameters = null;
    /**
     * @var CurlHandle Экземпляр CurlHandle
     */
    private $curlHandle;
    /**
     * @var int|null Последний полученный HTTP код
     */
    private $lastHttpCode = null;
    /**
     * @var string|null Последний полученный HTTP ответ
     */
    private $lastHttpResponse = null;

    /**
     * Request constructor
     *
     * @param Params $parameters Экземпляр Params для хранения аргументов
     * @param CurlHandle|null $curlHandle Экземпляр CurlHandle для повторного использования
     */
    public function __construct(Params $parameters, CurlHandle $curlHandle = null)
    {
        $this->parameters = $parameters;
        $this->curlHandle = $curlHandle !== null ? $curlHandle : new CurlHandle();
    }

    /**
     * Установка флага вывода отладочной информации
     *
     * @param bool $flag Значение флага
     * @return $this
     */
    public function debug($flag = false)
    {
        $this->debug = (bool)$flag;
        return $this;
    }

    /**
     * Возвращает последний полученный HTTP код
     *
     * @return int|null
     */
    public function getLastHttpCode()
    {
        return $this->lastHttpCode;
    }

    /**
     * Возвращает последний полученный HTTP ответ
     *
     * @return null|string
     */
    public function getLastHttpResponse()
    {
        return $this->lastHttpResponse;
    }

    /**
     * Возвращает экземпляр ParamsBag для хранения аргументов
     *
     * @return Params|null
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Выполнить HTTP GET запрос и вернуть тело ответа
     *
     * @param string $url Запрашиваемый URL
     * @param array $parameters Список GET параметров
     * @param null|string $modified Значение заголовка IF-MODIFIED-SINCE
     * @return mixed
     * @throws Exception
     */
    public function getRequest($url, $parameters = [], $modified = null)
    {
        if (!empty($parameters)) {
            $this->parameters->addGet($parameters);
        }
        return $this->request($url, $modified);
    }

    /**
     * Выполнить HTTP POST запрос и вернуть тело ответа
     *
     * @param string $url Запрашиваемый URL
     * @param array $parameters Список POST параметров
     * @return mixed
     * @throws Exception
     */
    public function postRequest($url, $parameters = [])
    {
        if (!empty($parameters)) {
            $this->parameters->addPost($parameters);
        }
        return $this->request($url);
    }

    /**
     * Выполнить HTTP POST запрос и вернуть тело ответа
     *
     * @param string $url Запрашиваемый URL
     * @param array $parameters Список POST параметров
     * @return mixed
     * @throws Exception
     */
    public function pathRequest($url, $parameters = [])
    {
        if (!empty($parameters)) {
            $this->parameters->addPath($parameters);
        }
        return $this->request($url);
    }

    /**
     * Подготавливает список заголовков HTTP
     *
     * @param null $modified
     * @return array
     * @throws \Exception
     */
    public function prepareHeaders($modified = null)
    {
        $headers = [
            'Connection: keep-alive',
            'Content-Type: application/json',
        ];
        if ($modified !== null) {
            if (is_int($modified)) {
                $headers[] = 'IF-MODIFIED-SINCE: ' . $modified;
            } else {
                $headers[] = 'IF-MODIFIED-SINCE: ' . (new DateTime($modified))->format(DateTime::RFC1123);
            }
        }
        return $headers;
    }

    /**
     * Подготавливает URL для HTTP запроса
     *
     * @param string $url Запрашиваемый URL
     * @return string
     */
    public function prepareEndpoint($url)
    {
        if ($this->v1 === false) {
            $query = http_build_query(array_merge($this->parameters->getGet(), [
                'USER_LOGIN' => $this->parameters->getAuth('login'),
                'USER_HASH' => $this->parameters->getAuth('apikey'),
            ]), null, '&');
        } else {
            $query = http_build_query(array_merge($this->parameters->getGet(), [
                'login' => $this->parameters->getAuth('login'),
                'api_key' => $this->parameters->getAuth('apikey'),
            ]), null, '&');
        }
        return sprintf('https://%s%s?%s', $this->parameters->getAuth('domain'), $url, $query);
    }

    /**
     * Выполнить HTTP запрос и вернуть тело ответа
     *
     * @param string $url Запрашиваемый URL
     * @param null|string $modified Значение заголовка IF-MODIFIED-SINCE
     * @return mixed
     * @throws Exception
     */
    public function request($url, $modified = null)
    {
        $headers = $this->prepareHeaders($modified);
        $endpoint = $this->prepareEndpoint($url);
        $this->printDebug('url', $endpoint);
        $this->printDebug('headers', $headers);
        $ch = $this->curlHandle->open();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        if ($this->parameters->hasPath()) {
            $fields = json_encode(
                $this->parameters->getPath()
            );
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        } else if($this->parameters->hasPost()) {
            $fields = json_encode(
            /*'request' => */$this->parameters->getPost()
            );
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        }
        if ($this->parameters->hasProxy()) {
            curl_setopt($ch, CURLOPT_PROXY, $this->parameters->getProxy());
        }
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $this->curlHandle->close();
        $this->lastHttpCode = $info['http_code'];
        $this->lastHttpResponse = $result;
        $this->printDebug('curl_exec', $result);
        $this->printDebug('curl_getinfo', $info);
        $this->printDebug('curl_error', $error);
        $this->printDebug('curl_errno', $errno);
        if ($result === false && !empty($error)) {
            throw new Exception($error, $errno);
        }
        $this->parameters->clearGet();
        $this->parameters->clearPost();
        $this->parameters->clearPath();
        return $this->parseResponse($result, $info);
    }

    /**
     * Парсит HTTP ответ, проверяет на наличие ошибок и возвращает тело ответа
     *
     * @param string $response HTTP ответ
     * @param array $info Результат функции curl_getinfo
     * @return mixed
     * @throws Exception
     */
    private function parseResponse($response, $info)
    {
        return json_decode($response, true);
        /*if (floor($info['http_code'] / 100) >= 3) {
            if (isset($result['response']['error_code']) && $result['response']['error_code'] > 0) {
                $code = $result['response']['error_code'];
            } elseif ($result !== null) {
                $code = 0;
            } else {
                $code = $info['http_code'];
            }
            if ($this->v1 === false && isset($result['response']['error'])) {
                throw new Exception($result['response']['error'], $code);
            } elseif (isset($result['response'])) {
                throw new Exception(json_encode($result['response']));
            } else {
                throw new Exception('Invalid response body.', $code);
            }
        } elseif (!isset($result['response'])) {
            return false;
        }
        return $result['response'];*/
    }

    /**
     * Вывода отладочной информации
     *
     * @param string $key Заголовок отладочной информации
     * @param mixed $value Значение отладочной информации
     * @param bool $return Возврат строки вместо вывода
     * @return mixed
     */
    private function printDebug($key = '', $value = null, $return = false)
    {
        if ($this->debug !== true) {
            return false;
        }
        if (!is_string($value)) {
            $value = print_r($value, true);
        }
        $line = sprintf('[DEBUG] %s: %s', $key, $value);
        if ($return === false) {
            return print_r($line . PHP_EOL);
        }
        return $line;
    }
}
