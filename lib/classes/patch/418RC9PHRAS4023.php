<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class patch_418RC9PHRAS4023 implements patchInterface
{
    /** @var string */
    private $release = '4.1.8-rc9';

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

        if ($conf->has(['registry', 'webservices', 'captchas-enabled'])) {
            $captchaEnabled = $conf->get(['registry', 'webservices', 'captchas-enabled']);

            if ($captchaEnabled) {
                $conf->set(['registry', 'webservices', 'captcha-provider'], 'reCaptcha');
            } else {
                $conf->set(['registry', 'webservices', 'captcha-provider'], 'none');
            }

            $conf->remove(['registry', 'webservices', 'captchas-enabled']);
        }
    }
}
