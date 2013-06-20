<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Core\Event\ApiResultEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class API_V1_result
{
    protected $app;

    /**
     *
     * @var string
     */
    protected $api_version;

    /**
     *
     * @var string
     */
    protected $response_time;

    /**
     *
     * @var int
     */
    protected $http_code = 200;

    /**
     *
     * @var string
     */
    protected $error_type;

    /**
     *
     * @var string
     */
    protected $error_message;

    /**
     *
     * @var string
     */
    protected $error_details;

    /**
     *
     * @var string
     */
    protected $request;

    /**
     *
     * @var mixed
     */
    protected $response;

    /**
     *
     * @var string
     */
    protected $response_type;

    /**
     * Constant for responsetype json
     */
    const FORMAT_JSON = 'json';
    /**
     * Constant for responsetype yaml
     */
    const FORMAT_YAML = 'yaml';
    /**
     * Constant for responsetype jsonp
     */
    const FORMAT_JSONP = 'jsonp';
    const ERROR_BAD_REQUEST = 'Bad Request';
    const ERROR_UNAUTHORIZED = 'Unauthorized';
    const ERROR_FORBIDDEN = 'Forbidden';
    const ERROR_NOTFOUND = 'Not Found';
    const ERROR_MAINTENANCE = 'Service Temporarily Unavailable';
    const ERROR_METHODNOTALLOWED = 'Method Not Allowed';
    const ERROR_INTERNALSERVERERROR = 'Internal Server Error';

    /**
     * API v1 Result constructor
     *
     * @param Application    $app
     * @param Request        $request
     * @param API_V1_adapter $api
     *
     * @return API_V1_result
     */
    public function __construct(Application $app, Request $request, API_V1_adapter $api)
    {
        $date = new DateTime();

        $this->app = $app;
        $this->request = $request;
        $this->api_version = $api->get_version();
        $this->response_time = $date->format(DATE_ATOM);
        $this->response = new stdClass();

        $this->parse_response_type();

        return $this;
    }

    protected function parse_response_type()
    {
        if (trim($this->request->get('callback')) !== '') {
            return $this->response_type = self::FORMAT_JSONP;
        }

        $accept = $this->request->getAcceptableContentTypes();
        $response_types = array();

        foreach ($accept as $key => $app_type) {
            $response_types[strtolower($app_type)] = true;
        }

        if (array_key_exists('application/json', $response_types)) {
            return $this->response_type = self::FORMAT_JSON;
        }
        if (array_key_exists('application/yaml', $response_types)) {
            return $this->response_type = self::FORMAT_YAML;
        }
        if (array_key_exists('text/yaml', $response_types)) {
            return $this->response_type = self::FORMAT_YAML;
        }

        return $this->response_type = self::FORMAT_JSON;
    }

    /**
     * Set datas to the response
     * If no datas provided (aka empty array), a stdClass if set,
     * so the serialized datas will be objects
     *
     * @param  array         $datas
     * @return API_V1_result
     */
    public function set_datas(array $datas)
    {
        if (count($datas) === 0)
            $datas = new stdClass ();
        $this->response = $datas;

        return $this;
    }

    /**
     * Return response data
     *
     * @return array
     */
    public function get_datas()
    {
        return (array) $this->response;
    }

    /**
     * Format the data and return serialized string
     *
     * @return string
     */
    public function format()
    {
        $request_uri = sprintf('%s %s'
            , $this->request->getMethod()
            , $this->request->getBasePath()
            . $this->request->getPathInfo()
        );

        $ret = array(
            'meta' => array(
                'api_version'   => $this->api_version
                , 'request'       => $request_uri
                , 'response_time' => $this->response_time
                , 'http_code'     => $this->http_code
                , 'error_type'    => $this->error_type
                , 'error_message' => $this->error_message
                , 'error_details' => $this->error_details
                , 'charset'       => 'UTF-8'
            )
            , 'response'      => $this->response
        );

        $this->app['dispatcher']->dispatch(PhraseaEvents::API_RESULT, new ApiResultEvent());

        $conf = $this->app['phraseanet.configuration'];

        if (isset($conf['main']['api-timers']) && true === $conf['main']['api-timers']) {
            $ret['timers'] = $this->app['api.timers']->toArray();
        }

        $return_value = false;

        switch ($this->response_type) {
            case self::FORMAT_JSON:
            default:
                $return_value = p4string::jsonencode($ret);
                break;
            case self::FORMAT_YAML:
                if ($ret['response'] instanceof stdClass)
                    $ret['response'] = array();

                $dumper = new Symfony\Component\Yaml\Dumper();
                $return_value = $dumper->dump($ret, 8);
                break;
            case self::FORMAT_JSONP:
                $callback = trim($this->request->get('callback'));
                $return_value = $callback . '(' . p4string::jsonencode($ret) . ')';
                break;
        }

        return $return_value;
    }

    /**
     * Return serailized datas content type
     *
     * @return string
     */
    public function get_content_type()
    {
        switch ($this->response_type) {

            case self::FORMAT_JSON:
            default:
                $return_value = 'application/json';
                break;
            case self::FORMAT_YAML:
                $return_value = 'application/yaml';
                break;
            case self::FORMAT_JSONP:
                $return_value = 'text/javascript';
                break;
        }

        return $return_value;
    }

    /**
     * Set the API_V1_result http_code, error_type, error_message and error_details
     * with the appropriate datas
     *
     * @param string $const
     * @param string $message
     *
     * @return API_V1_result
     */
    public function set_error_message($const, $message)
    {
        $this->error_details = $message;

        switch ($const) {
            case self::ERROR_BAD_REQUEST:
                $this->http_code = 400;
                $this->error_type = $const;
                $this->error_message = API_V1_exception_badrequest::get_details();
                break;
            case self::ERROR_UNAUTHORIZED:
                $this->http_code = 401;
                $this->error_type = $const;
                $this->error_message = API_V1_exception_unauthorized::get_details();
                break;
            case self::ERROR_FORBIDDEN:
                $this->http_code = 403;
                $this->error_type = $const;
                $this->error_message = API_V1_exception_forbidden::get_details();
                break;
            case self::ERROR_NOTFOUND:
                $this->http_code = 404;
                $this->error_type = $const;
                $this->error_message = API_V1_exception_notfound::get_details();
                break;
            case self::ERROR_METHODNOTALLOWED:
                $this->http_code = 405;
                $this->error_type = $const;
                $this->error_message = API_V1_exception_methodnotallowed::get_details();
                break;
            case self::ERROR_INTERNALSERVERERROR:
                $this->http_code = 500;
                $this->error_type = $const;
                $this->error_message = API_V1_exception_internalservererror::get_details();
                break;
            case self::ERROR_MAINTENANCE:
                $this->http_code = 503;
                $this->error_type = $const;
                $this->error_message = API_V1_exception_maintenance::get_details();
                break;
            case OAUTH2_ERROR_INVALID_REQUEST:
                $this->error_type = $const;
                break;
        }

        return $this;
    }

    /**
     * Set the API_V1_result http_code, error_message and error_details
     * with the appropriate datas
     *
     * @param integer $code
     *
     * @return API_V1_result
     */
    public function set_error_code($code)
    {
        switch ($code = (int) $code) {
            case 400:
                $this->http_code = $code;
                $this->error_type = self::ERROR_BAD_REQUEST;
                $this->error_message = API_V1_exception_badrequest::get_details();
                break;
            case 401:
                $this->http_code = $code;
                $this->error_type = self::ERROR_UNAUTHORIZED;
                $this->error_message = API_V1_exception_unauthorized::get_details();
                break;
            case 403:
                $this->http_code = $code;
                $this->error_type = self::ERROR_FORBIDDEN;
                $this->error_message = API_V1_exception_forbidden::get_details();
                break;
            case 404:
                $this->http_code = $code;
                $this->error_type = self::ERROR_NOTFOUND;
                $this->error_message = API_V1_exception_notfound::get_details();
                break;
            case 405:
                $this->http_code = $code;
                $this->error_type = self::ERROR_METHODNOTALLOWED;
                $this->error_message = API_V1_exception_methodnotallowed::get_details();
                break;
            case 500:
                $this->http_code = $code;
                $this->error_type = self::ERROR_INTERNALSERVERERROR;
                $this->error_message = API_V1_exception_internalservererror::get_details();
                break;
        }

        return $this;
    }

    /**
     * Returns the correct http code depending on the errors
     *
     * @return int
     */
    public function get_http_code()
    {
        if ($this->response_type == self::FORMAT_JSONP && $this->http_code != 500) {
            return 200;
        } else {
            return $this->http_code;
        }
    }

    /**
     *
     * @param int $code
     */
    public function set_http_code($code)
    {
        $this->http_code = (int) $code;
    }

    /**
     * Return a Symfony Response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function get_response()
    {
        $response = new Response(
                $this->format(),
                $this->get_http_code(),
                array('Content-Type'  => $this->get_content_type())
        );
        $response->setCharset('UTF-8');

        return $response;
    }
}
