<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Setup\Requirements\FilesystemRequirements;

class FilesystemProbe extends FilesystemRequirements implements ProbeInterface
{
    public function __construct(PropertyAccess $conf)
    {
        parent::__construct();

        $baseDir = realpath(__DIR__ . '/../../../../../');

        $paths = [
            $baseDir . '/config/configuration.yml',
        ];

        foreach ($paths as $path) {
            $this->addRecommendation(
                "00" === substr(sprintf('%o', fileperms($path)), -2),
                "$path should not be readable or writeable for other users, current mode is (".substr(sprintf('%o', fileperms($path)), -4).")",
                "Change the permissions of the \"<strong>$path</strong>\" file to 0600"
            );
        }

        if ($conf->has(['main', 'storage', 'subdefs'])) {
            $paths[] = $conf->get(['main', 'storage', 'subdefs']);
        }

        foreach ($paths as $path) {
            $this->addRequirement(
                is_writable($path),
                "$path directory must be writable",
                "Change the permissions of the \"<strong>$path</strong>\" directory so that the web server can write into it."
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return FilesystemProbe
     */
    public static function create(Application $app)
    {
        return new static($app['conf']);
    }
}
