<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use Symfony\Component\Finder\Finder;

// use Symfony\Component\Filesystem\Filesystem;


class patch_384alpha2a implements patchInterface
{
    /** @var string */
    private $release = '3.8.4-alpha.2';

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
    public function getDoctrineMigrations()
    {
        return [];
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
        $finder = new Finder();
        $fs = new Filesystem();
        foreach ($finder->files()->in($app['root.path'].'/config/status') as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $fileName = $file->getFileName();
            $chunks = explode('-', $fileName);

            if (count($chunks) < 4) {
                continue;
            }

            $suffix = array_pop($chunks);
            $uniqid = md5(implode('-', $chunks));

            $fs->rename($file->getRealPath(), $app['root.path'].'/config/status/' . $uniqid . '-' . $suffix);

            if ($fs->exists($app['root.path'] . '/www/custom/status/' . $file->getFileName())) {
                $fs->remove($app['root.path'] . '/www/custom/status/' . $file->getFileName());
            }
        }

        $app['filesystem']->mirror($app['root.path'] . '/config/status/', $app['root.path'] . '/www/custom/status/');
    }
}
