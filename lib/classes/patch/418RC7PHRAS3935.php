<?php


use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class patch_418RC7PHRAS3935 implements patchInterface
{
    /** @var string */
    private $release = '4.1.8-rc7';

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

        if (!$conf->has(['main', 'instance_id'])) {
            if ($conf->has(['phraseanet-service', 'phraseanet_local_id'])) {
                // get phraseanet_local_id if exist
                $conf->set(['main', 'instance_id'], $conf->get(['phraseanet-service', 'phraseanet_local_id']));
                $conf->remove(['phraseanet-service', 'phraseanet_local_id']);
            } else {
                // instance key is already a random value
                $instanceKey = $conf->get(['main', 'key']);

                $instanceId = md5($instanceKey);

                $conf->set(['main', 'instance_id'], $instanceId);
            }
        }
    }
}
