<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_390alpha19a extends patchAbstract
{
    /** @var string */
    private $release = '3.9.0-alpha.19';

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
    public function apply(base $appbox, Application $app)
    {
        $storage = $app['conf']->get(['main', 'storage']);
        $storage['cache'] = $app['root.path'].'/cache';
        $storage['log'] = $app['root.path'].'/logs';
        $storage['download'] = $app['root.path'].'/tmp/download';
        $storage['lazaret'] = $app['root.path'].'/tmp/lazaret';
        $storage['caption'] = $app['root.path'].'/tmp/caption';
        $app['conf']->set(['main', 'storage'], $storage);

        // update file structure
        $this->removeDirectory($app, $app['root.path'].'/tmp/cache_twig');
        $this->removeDirectory($app, $app['root.path'].'/tmp/doctrine');
        $this->removeDirectory($app, $app['root.path'].'/tmp/profiler');
        $this->removeDirectory($app, $app['root.path'].'/tmp/serializer');
        $this->removeDirectory($app, $app['root.path'].'/tmp/translations');
        $this->removeDirectory($app, $app['root.path'].'/tmp/cache_minify');
        $this->removeDirectory($app, $app['root.path'].'/features');
        $this->removeDirectory($app, $app['root.path'].'/hudson');
        $this->removeDirectory($app, $app['root.path'].'/locales');
        $this->removeDirectory($app, $app['root.path'].'/vagrant');
        $this->removeDirectory($app, $app['root.path'].'/tmp/doctrine-proxies');


        $this->copyFile($app, $app['root.path'].'/tmp/cache_registry.php', $app['cache.path'].'/cache_registry.php');
        $this->copyFile($app, $app['root.path'].'/tmp/configuration-compiled.php', $app['root.path'].'/config/configuration-compiled.php');

        return true;
    }

    private function removeDirectory(Application $app, $originDir)
    {
        if (is_dir($originDir)) {
            $app['filesystem']->remove($originDir);
        }
    }

    private function copyFile(Application $app, $originFile, $targetFile)
    {
        if ($app['filesystem']->exists($originFile)) {
            $app['filesystem']->copy($originFile, $targetFile);
        }
    }
}
