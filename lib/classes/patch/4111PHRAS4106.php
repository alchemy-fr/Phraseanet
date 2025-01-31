<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class patch_4111PHRAS4106 implements patchInterface
{
    /** @var string */
    private $release = '4.1.11';

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
        foreach ($app['conf']->get(['authentication', 'providers'], []) as $providerId => $data) {
            if (isset($data['type']) && $data['type'] === "openid") {
                if (!isset($data['options']['usegroups'])) {
                    $data['options']['usegroups'] = false;

                    $providerConfig[$providerId] = $data;

                    $conf->merge(['authentication', 'providers'], $providerConfig);
                }

                if (!isset($data['options']['fieldmap'])) {
                    $data['options']['fieldmap'] = [
                        'id'        => 'sub',
                        'login'     => 'email',
                        'firstname' => 'given_name',
                        'lastname'  => 'family_name',
                        'email'     => 'email',
                        'groups'    => 'group',
                    ];

                    $providerConfig[$providerId] = $data;

                    $conf->merge(['authentication', 'providers'], $providerConfig);
                }

                if (!isset($data['options']['groupmask'])) {
                    $data['options']['groupmask'] = "/phraseanet_([^,]+)/i";

                    $providerConfig[$providerId] = $data;

                    $conf->merge(['authentication', 'providers'], $providerConfig);
                }
            }
        }

        return true;
    }
}
