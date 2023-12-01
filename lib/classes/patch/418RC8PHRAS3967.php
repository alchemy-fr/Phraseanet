<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Symfony\Component\Yaml\Yaml;

class patch_418RC8PHRAS3967 implements patchInterface
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

        if (!$conf->has(['translator'])) {
            try {
                // retrive value for the old conf file if possible
                $config_file = ($config_dir =  $app['root.path'] . "/config/translator/") . "configuration.yml";

                @mkdir($config_dir, 0777, true);

                $oldConf = Yaml::parse(file_get_contents($config_file));

                $conf->set(['translator'], $oldConf['translator']);
            } catch (\Exception $e) {
                // if missing configuration
                $conf->set(['translator'], ['jobs' => ['keywords' => []]]);
            }

        }
    }
}
