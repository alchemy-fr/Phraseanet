<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Api
 *
 * @author nicolas
 */
class DailymotionWithoutOauth2 extends Dailymotion
{

    /**
     * Call a remote method.
     *
     * @param $method String the method name to call.
     * @param $args Array an associative array of arguments.
     *
     * @return mixed the method response
     *
     * @throws DailymotionApiException          if API return an error
     * @throws DailymotionAuthException         if can't authenticate the request
     * @throws DailymotionAuthRequiredException if not authentication info is available
     * @throws DailymotionTransportException    if an error occurs during request.
     */
    public function call($method, $args = array(), $access_token = null)
    {
        $headers = array('Content-Type: application/json');
        $payload = json_encode(array
            (
            'call' => $method,
            'args' => $args,
            ));

        $status_code = null;
        try {
            $result = json_decode($this->oauthRequest($this->apiEndpointUrl, $payload, $access_token, $headers, $status_code), true);
        } catch (DailymotionAuthException $e) {

            if ($e->error === 'invalid_token') {
                throw new Bridge_Exception_ActionAuthNeedReconnect();
            } else {
                throw $e;
            }
        }

        if ( ! isset($result)) {
            throw new DailymotionApiException('Invalid API server response.');
        } elseif ($status_code !== 200) {
            throw new DailymotionApiException('Unknown error: ' . $status_code, $status_code);
        } elseif (is_array($result) && isset($result['error'])) {
            $message = isset($result['error']['message']) ? $result['error']['message'] : null;
            $code = isset($result['error']['code']) ? $result['error']['code'] : null;
            if ($code === 403) {
                throw new DailymotionAuthRequiredException($message, $code);
            } else {
                throw new DailymotionApiException($message, $code);
            }
        } elseif ( ! isset($result['result'])) {
            throw new DailymotionApiException("Invalid API server response: no `result' key found.");
        }

        return $result['result'];
    }

    /**
     * Upload a file on the Dailymotion servers and generate an URL to be used with API methods.
     *
     * @param $filePath String a path to the file to upload
     *
     * @return String the resulting URL
     */
    public function uploadFile($filePath, $oauth_token)
    {
        $result = $this->call('file.upload', array(), $oauth_token);
        $timeout = $this->timeout;
        $this->timeout = null;
        $result = json_decode($this->httpRequest($result['upload_url'], array('file' => '@' . $filePath)), true);
        $this->timeout = $timeout;

        return $result['url'];
    }
}

