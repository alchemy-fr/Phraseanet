<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\ApiApplication;

class patch_3715alpha1a extends patchAbstract
{
    /**
     * @var string
     */
    private $release = '3.7.15-alpha1';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

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
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        if (null === $app['repo.api-applications']->findByClientId(\API_OAuth2_Application_OfficePlugin::CLIENT_ID)) {
            $application = $app['manipulator.api-application']->create(
                \API_OAuth2_Application_OfficePlugin::CLIENT_NAME,
                ApiApplication::DESKTOP_TYPE,
                '',
                'http://www.phraseanet.com',
                null,
                ApiApplication::NATIVE_APP_REDIRECT_URI
            );

            $application->setGrantPassword(true);
            $application->setClientId(\API_OAuth2_Application_OfficePlugin::CLIENT_ID);
            $application->setClientSecret(\API_OAuth2_Application_OfficePlugin::CLIENT_SECRET);

            $app['manipulator.api-application']->update($application);
        }

        return true;
    }
}
