<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class patch_417RC5PHRAS3804 implements patchInterface
{
    /** @var string */
    private $release = '4.1.7-rc5';

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

        // add cookieconsent axeptio configuration

        if (!$conf->has(['main', 'cookieconsent', 'axeptio'])) {
            $axeptio = [
                'enabled'               => false,
                'axeptio_id'            => '',
                'axeptio_version_fr'    => null,
                'axeptio_version_en'    => null,
                'axeptio_version_de'    => null,
                'axeptio_version_du'    => null
            ];

            $conf->set(['main', 'cookieconsent', 'axeptio'], $axeptio);
        }
    }
}
