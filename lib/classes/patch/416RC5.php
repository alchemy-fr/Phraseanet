<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class patch_416RC5 implements patchInterface
{
    /** @var string */
    private $release = '4.1.6-rc5';

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
        }
        elseif ($base->get_base_type() === base::APPLICATION_BOX) {
            $this->patch_appbox($base, $app);
        }

        return true;
    }

    private function patch_databox(databox $databox, Application $app)
    {
    }

    private function patch_appbox(base $databox, Application $app)
    {

        /** @var PropertyAccess $conf */
        $conf = $app['conf'];

        if (!$conf->has(['phraseanet-service', 'uploader-service'])) {
            $clientSecret = '';
            $clientId = '';
            if ($conf->has(['workers', 'pull_assets'])) {
                $pullAssets = $conf->get(['workers', 'pull_assets']);
                $clientSecret = !empty($pullAssets['clientSecret']) ? $pullAssets['clientSecret'] : $clientSecret;
                $clientId = !empty($pullAssets['clientId']) ? $pullAssets['clientId'] : $clientId;
            }

            $config = [
                'push_verify_ssl' => true,
                'pulled_target'   => [
                    'target 1' => [
                        'pullmodeUri'   => '',
                        'clientSecret'  => $clientSecret,
                        'clientId'      => $clientId,
                        'verify_ssl'    => true
                    ],
                ],
            ];

            $conf->set(['phraseanet-service', 'uploader-service'], $config);
        }

        $conf->remove(['workers', 'pull_assets']);
    }
}
