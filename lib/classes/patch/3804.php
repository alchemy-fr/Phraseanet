<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_3804 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.8.0.a3';

    /**
     *
     * @var Array
     */
    private $concern = array(base::APPLICATION_BOX);

    /**
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
    }

    public function require_all_upgrades()
    {
        return false;
    }

    /**
     *
     * @return Array
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * @param base $appbox
     */
    public function apply(base $appbox, Application $app)
    {
        try {
            $confs = $app['phraseanet.configuration']->getConfigurations();

            foreach ($confs as $env => $conf) {
                if (in_array($env, array('environment', 'key'))) {
                    continue;
                }
                if (!isset($conf['task-manager'])) {
                    $confs[$env]['task-manager'] = 'task_manager';
                }
            }

            $app['phraseanet.configuration']->setConfigurations($confs);

            $services = $app['phraseanet.configuration']->getServices();

            if (!isset($services['TaskManager'])) {
                $app['phraseanet.configuration']->resetServices('TaskManager');
            }

            return true;
        } catch (\Exception $e) {
            throw $e;
        }

        return false;
    }
}

