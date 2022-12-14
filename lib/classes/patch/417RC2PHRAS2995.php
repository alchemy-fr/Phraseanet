<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;


class patch_417RC2PHRAS2995 implements patchInterface
{
    /** @var string */
    private $release = '4.1.7-rc2';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * Returns the release version.
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
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
    public function require_all_upgrades()
    {
        return false;
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
    public function apply(base $appbox, Application $app)
    {
        /** @var PropertyAccess $conf */
        $conf = $app['conf'];
        $newProviders = [];
        $psFixed = false;
        foreach ($app['conf']->get(['authentication', 'providers'], []) as $providerId => $data) {
            if($data['type'] === "ps-auth") {
                if(!isset($data['options']['debug'])) {
                    $data['options']['debug'] = false;
                }
                if(!isset($data['options']['auto-connect-idp-name'])) {
                    $data['options']['auto-connect-idp-name'] = null;
                }
                $psFixed = true;
            }
            $newProviders[$providerId] = $data;
        }

        // add ps
        if($psFixed) {
            $conf->set(['authentication', 'providers'], $newProviders);
        }

        return true;
    }
}
