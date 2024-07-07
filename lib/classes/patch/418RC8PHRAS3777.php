<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class patch_418RC8PHRAS3777 implements patchInterface
{
    /** @var string */
    private $release = '4.1.8-rc8';

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

        // remove ginger configuration
        if ($conf->has(['externalservice', 'ginger'])) {
            $conf->remove(['externalservice', 'ginger']);
        }

        // remove bridge
        if ($conf->has(['main', 'bridge'])) {
            $conf->remove(['main', 'bridge']);
        }

        // remove old pusher configuration
        // copy it under externalservice
        if ($conf->has(['pusher'])) {
            $p = $conf->get(['pusher']);
            $conf->set(['externalservice', 'pusher'], $p);

            $conf->remove(['pusher']);
        }

        // if no pusher configuration
        if (!$conf->has(['externalservice', 'pusher'])) {
            $pusher = [
                'auth_key' => 'pusher-auth_key',
                'secret' => 'pusher-secret',
                'app_id' => 'pusher-app_id'
            ];
            $conf->set(['externalservice', 'pusher'], $pusher);
        }

        // if no happyscribe configuration
        if (!$conf->has(['externalservice', 'happyscribe'])) {
            $h = [
                'token'             => 'token',
                'organization_id'   => '123456',
                'transcript_format' => 'vtt',
                'subdef_source'     => 'preview'
            ];
            $conf->set(['externalservice', 'happyscribe'], $h);
        }

        // remove cooliris
        if ($conf->has(['crossdomain', 'allow-access-from'])) {
            $tValues = $conf->get(['crossdomain', 'allow-access-from']);

            foreach ($tValues as $k => $value) {
                if (array_search('*.cooliris.com', $value) != false) {
                    unset($tValues[$k]);
                }
            }

            $conf->set(['crossdomain', 'allow-access-from'], $tValues);
        }

        // set downloadAsync if not exist
        if (!$conf->has(['workers', 'queues', 'downloadAsync'])) {
            $value = ['max_retry' => 3, 'ttl_retry' => 10000];
            $conf->set(['workers', 'queues', 'downloadAsync'], $value);
        }

        // patch for stamp PHRAS-3520
        if (!$conf->has(['registry', 'actions', 'export-stamp-choice'])) {
            $conf->set(['registry', 'actions', 'export-stamp-choice'], false);
        }
        if (!$conf->has(['registry', 'actions', 'stamp-subdefs'])) {
            $conf->set(['registry', 'actions', 'stamp-subdefs'], false);
        }

    }
}
