<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class patch_4121PHRAS4167 implements patchInterface
{
    /** @var string */
    private $release = '4.1.21';

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

        if (!$conf->has(['registry', 'actions', 'filename-sanitize-character'])) {
            $conf->set(['registry', 'actions', 'filename-sanitize-character'], '');
        }

        return true;
    }
}
