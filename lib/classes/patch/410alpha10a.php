<?php

/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Manipulator\ApiApplicationManipulator;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Repositories\ApiApplicationRepository;


class patch_410alpha10a implements patchInterface
{
    /** @var string */
    private $release = '4.1.0-alpha.10';

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
    public function getDoctrineMigrations()
    {
        return [];
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
    public function apply(base $databox, Application $app)
    {
        // create an api application for adobeCC
        /** @var ApiApplicationRepository $repo */
        $repo = $app['repo.api-applications'];
        if(!$repo->findByClientId(\API_OAuth2_Application_AdobeCCPlugin::CLIENT_ID)) {

            /** @var ApiApplicationManipulator $manipulator */
            $manipulator = $app['manipulator.api-application'];

            $application = $manipulator->create(
                \API_OAuth2_Application_AdobeCCPlugin::CLIENT_NAME,
                ApiApplication::DESKTOP_TYPE,
                '',
                'http://www.phraseanet.com',
                null,
                ApiApplication::NATIVE_APP_REDIRECT_URI
            );

            $application->setGrantPassword(true);
            $application->setClientId(\API_OAuth2_Application_AdobeCCPlugin::CLIENT_ID);
            $application->setClientSecret(\API_OAuth2_Application_AdobeCCPlugin::CLIENT_SECRET);

            $manipulator->update($application);
        }
        return true;
    }
}
