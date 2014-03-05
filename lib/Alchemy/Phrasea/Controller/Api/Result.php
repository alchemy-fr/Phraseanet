<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Api;

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Dumper;

class Result
{
    /** @var string */
    private $responseTime;

    /** @var integer */
    private $code = 200;

    /** @var string */
    private $errorType;

    /** @var string */
    private $errorMessage;

    /** @var string */
    private $errorDetails;

    /** @var Request */
    private $request;

    /** @var mixed */
    private $data;

    /** @var string */
    private $responseType;

    const FORMAT_JSON = 'json';
    const FORMAT_YAML = 'yaml';
    const FORMAT_JSONP = 'jsonp';

    const ERROR_BAD_REQUEST = 'Bad Request';
    const ERROR_UNAUTHORIZED = 'Unauthorized';
    const ERROR_FORBIDDEN = 'Forbidden';
    const ERROR_NOTFOUND = 'Not Found';
    const ERROR_MAINTENANCE = 'Service Temporarily Unavailable';
    const ERROR_METHODNOTALLOWED = 'Method Not Allowed';
    const ERROR_INTERNALSERVERERROR = 'Internal Server Error';

    public function __construct(Request $request, array $data = null, $code = 200, $errorType = null, $errorMessage = null, $errorDetails = null)
    {
        $date = new \DateTime();

        $this->request = $request;
        $this->responseTime = $date->format(DATE_ATOM);
        $this->data = new \stdClass();

        $this->parseResponseType();

        $this->setData($data);
        $this->code = $code;
        $this->errorType = $errorType;
        $this->errorMessage = $errorMessage;
        $this->errorDetails = $errorDetails;

        return $this;
    }

    /**
     * Creates a Symfony Response
     *
     * @return Response
     */
    public function createResponse()
    {
        $response = $this->format();
        $response->headers->set('content-type', $this->getContentType());
        $response->setStatusCode($this->getStatusCode());
        $response->setCharset('UTF-8');

        return $response;
    }

    /**
     * @param Request $request
     * @param $data
     *
     * @return Result
     */
    public static function create(Request $request, $data)
    {
        return new static($request, $data);
    }

    /**
     * @param Request $request
     * @param $code
     * @param $message
     *
     * @return Result
     *
     * @throws InvalidArgumentException
     */
    public static function createError(Request $request, $code, $message)
    {
        $errorDetails = $message;

        switch ($code) {
            case 400:
                $errorType = self::ERROR_BAD_REQUEST;
                $errorMessage = 'Parameter is invalid or missing';
                break;
            case 401:
                $errorType = self::ERROR_UNAUTHORIZED;
                $errorMessage = 'The OAuth token was provided but was invalid.';
                break;
            case 403:
                $errorType = self::ERROR_FORBIDDEN;
                $errorMessage = 'Access to the requested resource is forbidden';
                break;
            case 404:
                $errorType = self::ERROR_NOTFOUND;
                $errorMessage = 'Requested resource is not found';
                break;
            case 405:
                $errorType = self::ERROR_METHODNOTALLOWED;
                $errorMessage = 'Attempting to use POST with a GET-only endpoint, or vice-versa';
                break;
            case 500:
                $errorType = self::ERROR_INTERNALSERVERERROR;
                $errorMessage = 'Internal Server Error';
                break;
            case 503:
                $errorType = self::ERROR_MAINTENANCE;
                $errorMessage = 'Server is offline for maintenance, try again soon.';
                break;
            default:
                throw new \InvalidArgumentException('Unable to generate a response.');
        }

        return new static($request, null, $code, $errorType, $errorMessage, $errorDetails);
    }

    private function parseResponseType()
    {
        if (trim($this->request->get('callback')) !== '') {
            return $this->responseType = self::FORMAT_JSONP;
        }

        $responseTypes = array_map('strtolower', $this->request->getAcceptableContentTypes());

        if (in_array('application/json', $responseTypes)) {
            return $this->responseType = self::FORMAT_JSON;
        }
        if (in_array('application/yaml', $responseTypes)) {
            return $this->responseType = self::FORMAT_YAML;
        }
        if (in_array('text/yaml', $responseTypes)) {
            return $this->responseType = self::FORMAT_YAML;
        }

        return $this->responseType = self::FORMAT_JSON;
    }

    /**
     * Sets data to the response.
     *
     * If no datas provided, a stdClass if set,
     * so the serialized datas will be objects
     *
     * @param array $datas
     *
     * @return Result
     */
    private function setData(array $data = null)
    {
        if (null === $data || count($data) === 0) {
            $data = new \stdClass();
        }

        $this->data = $data;

        return $this;
    }

    /**
     * Formats the data and return serialized string
     *
     * @return Response
     */
    private function format()
    {
        $request_uri = sprintf('%s %s', $this->request->getMethod(), $this->request->getBasePath().$this->request->getPathInfo());

        $ret = [
            'meta' => [
                'api_version'   => V1::VERSION,
                'request'       => $request_uri,
                'response_time' => $this->responseTime,
                'http_code'     => $this->code,
                'error_type'    => $this->errorType,
                'error_message' => $this->errorMessage,
                'error_details' => $this->errorDetails,
                'charset'       => 'UTF-8',
            ],
            'response'          => $this->data,
        ];

        switch ($this->responseType) {
            case self::FORMAT_JSON:
            default:
                return new JsonResponse($ret);
            case self::FORMAT_YAML:
                if ($ret['response'] instanceof \stdClass) {
                    $ret['response'] = [];
                }

                $dumper = new Dumper();

                return new Response($dumper->dump($ret, 8));
            case self::FORMAT_JSONP:
                $response = new JsonResponse($ret);
                $response->setCallback(trim($this->request->get('callback')));

                return $response;
                break;
        }
    }

    /**
     * Returns serialized data content type
     *
     * @return string
     */
    private function getContentType()
    {
        switch ($this->responseType) {
            case self::FORMAT_JSON:
            default:
                return 'application/json';
            case self::FORMAT_YAML:
                return 'application/yaml';
            case self::FORMAT_JSONP:
                return 'text/javascript';
        }
    }

    /**
     * Returns the correct http code depending on the errors
     *
     * @return integer
     */
    private function getStatusCode()
    {
        if ($this->responseType == self::FORMAT_JSONP && $this->code != 500) {
            return 200;
        }

        return $this->code;
    }
}
