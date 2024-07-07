<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class patch_418RC12PHRAS4007 implements patchInterface
{
    /** @var string */
    private $release = '4.1.8-rc12';

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

        $providers = $conf->get(['authentication', 'providers']);
        $providersType = array_column($app['conf']->get(['authentication', 'providers']), 'type');

        // set an example of setting if not exist
        if (!in_array('openid', $providersType)) {
            $providers['openid-1'] = [
                'enabled' => false,
                'display' => false,
                'title' => 'openid 1',
                'type' => 'openid',
                'options' => [
                    'client-id'     => 'client_id',
                    'client-secret' => 'client_secret',
                    'base-url'      => 'https://keycloak.phrasea.local',
                    'realm-name'    => 'phrasea',
                    'exclusive'     => false,
                    'icon-uri'      => null,
                    'birth-group'   => '_firstlog',
                    'everyone-group' => '_everyone',
                    'metamodel'     => '_metamodel',
                    'model-gpfx'    => '_M_',
                    'model-upfx'    => '_U_',
                    'auto-logout'   => false
                ]
            ];

            $conf->set(['authentication', 'providers'], $providers);
        }
    }
}
