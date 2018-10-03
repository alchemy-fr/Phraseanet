<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Translation\TranslatorInterface;

abstract class Bridge_Api_Abstract
{
    /**
     *
     * @var Bridge_Api_Auth_Interface
     */
    protected $_auth;

    /**
     *
     * @var string
     */
    protected $locale = 'en_US';
    protected $generator;
    /** @var PropertyAccess */
    protected $conf;
    protected $translator;

    /**
     * @param UrlGenerator        $generator
     * @param PropertyAccess      $conf
     * @param TranslatorInterface $translator
     *
     * @param Bridge_Api_Auth_Interface $auth
     */
    public function __construct(UrlGenerator $generator, PropertyAccess $conf, Bridge_Api_Auth_Interface $auth, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->conf = $conf;
        $this->generator = $generator;
        $this->_auth = $auth;
        $this->initialize_transport();
        $this->set_auth_params();

        return $this;
    }

    /**
     *
     * @param  Bridge_AccountSettings $settings
     * @return Bridge_Api_Abstract
     */
    public function set_auth_settings(Bridge_AccountSettings $settings)
    {
        $this->_auth->set_settings($settings);
        $this->set_transport_authentication_params();

        return $this;
    }

    /**
     *
     * @return Array The result of the primary handshake, Including tokens and others
     */
    public function connect()
    {
        if ( ! $this->is_configured())
            throw new Bridge_Exception_ApiConnectorNotConfigured('Connector not configured');
        $request_token = $this->_auth->parse_request_token();

        return $this->_auth->connect($request_token);
    }

    /**
     *
     * @return Bridge_Api_Abstract
     */
    public function reconnect()
    {
        if ( ! $this->is_configured())
            throw new Bridge_Exception_ApiConnectorNotConfigured();
        $this->_auth->reconnect();

        return $this;
    }

    /**
     *
     * @return Bridge_Api_Abstract
     */
    public function disconnect()
    {
        if ( ! $this->is_configured())
            throw new Bridge_Exception_ApiConnectorNotConfigured();
        $this->_auth->disconnect();

        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function is_connected()
    {
        return $this->_auth->is_connected();
    }

    /**
     *
     * @return string
     */
    public function get_auth_url($supp_params = [])
    {
        return $this->_auth->get_auth_url($supp_params);
    }

    /**
     *
     * @param  string              $locale
     * @return Bridge_Api_Abstract
     */
    public function set_locale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_locale()
    {
        return $this->locale;
    }

    /**
     *
     * @param  type    $object_id
     * @return boolean
     */
    public function is_valid_object_id($object_id)
    {
        return is_scalar($object_id) && ! is_bool($object_id);
    }

    /**
     * This method is called when calling any API method
     * This allows use to change the exception object.
     * For instance, you can set it to a Bridge_Exception_ActionAuthNeedReconnect
     *
     * @param  Exception $e
     * @return Void
     */
    public function handle_exception(Exception $e)
    {
        return;
    }

    /**
     * The method to initialize Authentication transport
     * It's called on constructor
     */
    abstract protected function initialize_transport();

    /**
     * The method used to set the connection params to the auth object
     * It's called after transport initialization
     */
    abstract protected function set_auth_params();

    /**
     * Set the transport authentication params to the auth object
     * It's called, every time the settings are set
     */
    abstract protected function set_transport_authentication_params();
}
