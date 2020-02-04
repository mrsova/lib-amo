<?php

namespace libAmo\Request;


use libAmo\Exception;

class CurlHandle
{
    /**
     * @var resource Повторно используемый обработчик cURL
     */
    private $handle;

    /**
     * Закрывает обработчик cURL
     */
    public function __destruct()
    {
        if ($this->handle !== null) {
            @curl_close($this->handle);
        }
    }

    /**
     * Возвращает повторно используемый обработчик cURL или создает новый
     *
     * @return resource
     * @throws Exception
     */
    public function open()
    {
        if ($this->handle !== null) {
            return $this->handle;
        }
        if (!function_exists('curl_init')) {
            throw new Exception('The cURL PHP extension was not loaded.');
        }
        $this->handle = curl_init();
        return $this->handle;
    }

    /**
     * Сбрасывает настройки обработчика cURL
     */
    public function close()
    {
        if ($this->handle === null) {
            return;
        }
        curl_setopt($this->handle, CURLOPT_HEADERFUNCTION, null);
        curl_setopt($this->handle, CURLOPT_READFUNCTION, null);
        curl_setopt($this->handle, CURLOPT_WRITEFUNCTION, null);
        curl_setopt($this->handle, CURLOPT_PROGRESSFUNCTION, null);
        curl_reset($this->handle);
    }
}