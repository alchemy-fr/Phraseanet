<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Repositories\ApiApplicationRepository;
use Alchemy\Phrasea\Model\Manipulators\ApiApplicationManipulator;

class patch_4121PHRAS4175 implements patchInterface
{
    /** @var string */
    private $release = '4.1.21';

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
    public function apply(base $base, Application $app)
    {
        if ($base->get_base_type() === base::DATA_BOX) {
            $this->patch_databox($base, $app);
        } elseif ($base->get_base_type() === base::APPLICATION_BOX) {
            $this->patch_appbox($base, $app);
        }

        return true;
    }

    private function patch_databox(databox $databox, Application $app)
    {
    }

    private function patch_appbox(base $appbox, Application $app)
    {
        /** @var PropertyAccess $conf */
        $conf = $app['conf'];

        /** @var ApiApplicationRepository $apiAppRepository */
        $apiAppRepository = $app['repo.api-applications'];

        /** @var ApiApplicationManipulator $apiAppManipulator */
        $apiAppManipulator = $app['manipulator.api-application'];

        $nativeAppClientId = [
            '\alchemy\phraseanet\id\YZWUTqNyq8ObG4b0o4sp7NX50ScudqiV',
            '\alchemy\phraseanet\id\4f981093aebb66.06844599',
            '\alchemy\phraseanet\id\999585175b5fbb6e140efbdfea86c561'
        ]; 

        foreach ($nativeAppClientId as $clientId) {
            $application = $apiAppRepository->findByClientId($clientId);
            if (null !== $application) {
                $apiAppManipulator->delete($application);
            } 
        }

        if ($conf->has(['registry', 'api-clients', 'navigator-enabled'])) {
            $conf->remove(['registry', 'api-clients', 'navigator-enabled']);
        }

        if ($conf->has(['registry', 'api-clients', 'office-enabled'])) {
            $conf->remove(['registry', 'api-clients', 'office-enabled']);
        }

        if ($conf->has(['registry', 'api-clients', 'adobe_cc-enabled'])) {
            $conf->remove(['registry', 'api-clients', 'adobe_cc-enabled']);
        }
    }
}
