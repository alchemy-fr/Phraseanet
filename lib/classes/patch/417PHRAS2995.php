<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;


class patch_417PHRAS2995 implements patchInterface
{
    /** @var string */
    private $release = '4.1.7-rc1';

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
        $id2title = [
            'facebook'    => 'Facebook',
            'github'      => 'Github',
            'linkedin'    => 'LinkedIn',
            'phraseanet'  => 'Phraseanet',
            'twitter'     => 'Twitter',
            'viadeo'      => 'Viadeo'
        ];

        /** @var PropertyAccess $conf */
        $conf = $app['conf'];
        $newProviders = [];
        $psFound = false;
        foreach ($app['conf']->get(['authentication', 'providers'], []) as $providerId => $data) {
            if($providerId === 'google-plus') {     // rip
                continue;
            }
            if(array_key_exists('type', $data)) {
                // already good format
                $newProviders[$providerId] = $data;
                if($data['type'] === "ps-auth") {
                    $psFound = true;
                }
            }
            else {
                // bump format
                $newProviders[$providerId] = [
                    'enabled' => $data['enabled'],
                    'display' => $data['enabled'],
                    'title'   => array_key_exists($providerId, $id2title) ? $id2title[$providerId] : $providerId,
                    'type'    => $providerId,
                    'options' => $data['options']
                ];
            }
        }

        // add ps
        if(!$psFound && !array_key_exists('ps-auth-1', $newProviders)) {
            $newProviders['ps-auth-1'] = [
                'enabled' => false,
                'display' => false,
                'title' => 'PS Auth',
                'type' => 'ps-auth',
                'options' => [
                    'client-id'     => 'client_id',
                    'client-secret' => 'client_secret',
                    'base-url'      => 'https://api-auth.phrasea.local',
                    'provider-type' => 'oauth',
                    'provider-name' => 'v2',
                    'icon-uri'      => null,
                    'birth-group'   => '_firstlog',
                    'everyone-group' => '_everyone',
                    'metamodel'     => '_metamodel',
                    'model-gpfx'    => '_M_',
                    'model-upfx'    => '_U_',
                    'auto-logout'   => false
                ]
            ];
        }

        $conf->set(['authentication', 'providers'], $newProviders);

        return true;
    }
}
