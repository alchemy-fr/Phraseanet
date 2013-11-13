<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class patch_3715alpha1a implements patchInterface
{
    /**
     * @var string
     */
    private $release = '3.7.15-alpha1';

    /** @var array */
    private $concern = array(base::APPLICATION_BOX);

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        try {
            \API_OAuth2_Application::load_from_client_id($app, \API_OAuth2_Application_OfficePlugin::CLIENT_ID);
        } catch (NotFoundHttpException $e) {
            $client = \API_OAuth2_Application::create($app, null, \API_OAuth2_Application_OfficePlugin::CLIENT_NAME);

            $client->set_activated(true);
            $client->set_grant_password(true);
            $client->set_website("http://www.phraseanet.com");
            $client->set_client_id(\API_OAuth2_Application_OfficePlugin::CLIENT_ID);
            $client->set_client_secret(\API_OAuth2_Application_OfficePlugin::CLIENT_SECRET);
            $client->set_type(\API_OAuth2_Application::DESKTOP_TYPE);
            $client->set_redirect_uri(\API_OAuth2_Application::NATIVE_APP_REDIRECT_URI);
        }

        return true;
    }
}
