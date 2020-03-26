<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2019 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class patch_410alpha26a implements patchInterface
{
    /** @var string */
    private $release = '4.1.0-alpha.26a';
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
            'google-plus' => 'Google +',
            'linkedin'    => 'LinkedIN',
            'phraseanet'  => 'Phraseanet',
            'twitter'     => 'Twitter',
            'viadeo'      => 'Viadeo'
        ];

        /** @var PropertyAccess $conf */
        $conf = $app['conf'];
        $newProviders = [];
        foreach ($app['conf']->get(['authentication', 'providers'], []) as $providerId => $data) {
            if($providerId === 'google-plus') {     // rip
                continue;
            }
            $newProviders[$providerId] = [
                'enabled' => $data['enabled'],
                'display' => $data['enabled'],
                'title'   => array_key_exists($providerId, $id2title) ? $id2title[$providerId] : $providerId,
                'type'    => $providerId,
                'options' => $data['options']
            ];
        }
        // add phraseanet
        $newProviders['phraseanet-oauth'] = [
            'enabled' => false,
            'display' => false,
            'title'   => "Phraseanet Oauth provider",
            'type'    => 'phraseanet-oauth',
            'options' => [
                'client-id' => 'client_id',
                'client-secret' => 'client_secret',
                'base-url' => 'https://baseurl',
                'provider-type' => 'provider_type',
                'provider-name' => 'provider_name',
                'icon-uri' => null
            ]
        ];

        $conf->set(['authentication', 'providers'], $newProviders);

        return true;
    }
}
