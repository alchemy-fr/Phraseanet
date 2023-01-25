<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class patch_417RC4PHRAS1718 implements patchInterface
{
    /** @var string */
    private $release = '4.1.7-rc4';

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

        // add captcha configuration

        if (!$conf->has(['registry', 'webservices', 'captchas-enabled'])) {
            if ($conf->has(['registry', 'webservices', 'captcha-enabled'])) {
                $conf->set(['registry', 'webservices', 'captchas-enabled'], $conf->get(['registry', 'webservices', 'captcha-enabled']));
            } else {
                if ($conf->has(['authentication', 'captcha', 'enabled'])) {
                    $conf->set(['registry', 'webservices', 'captchas-enabled'], $conf->get(['authentication', 'captcha', 'enabled']));
                } else {
                    $conf->set(['registry', 'webservices', 'captchas-enabled'], false);
                }
            }
        }

        if (!$conf->has(['registry', 'webservices', 'trials-before-display'])) {
            if ($conf->has(['authentication', 'captcha', 'trials-before-display'])) {
                $conf->set(['registry', 'webservices', 'trials-before-display'], $conf->get(['authentication', 'captcha', 'trials-before-display']));
            } else {
                $conf->set(['registry', 'webservices', 'trials-before-display'], 5);
            }
        }

        if (!$conf->has(['registry', 'webservices', 'recaptcha-public-key'])) {
            $conf->set(['registry', 'webservices', 'recaptcha-public-key'], '');
        }

        if (!$conf->has(['registry', 'webservices', 'recaptcha-private-key'])) {
            $conf->set(['registry', 'webservices', 'recaptcha-private-key'], '');
        }

        // unused configuration section
        $conf->remove(['authentication', 'captcha']);
        $conf->remove(['registry', 'webservices', 'captcha-enabled']);
    }
}
