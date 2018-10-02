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
use Alchemy\Phrasea\Setup\RequirementCollection;

class SubdefsPathsProbe extends RequirementCollection implements ProbeInterface
{
    public function __construct(\appbox $appbox)
    {
        $this->setName('Subdefs Paths');

        foreach ($appbox->get_databoxes() as $databox) {
            $this->ensureWriteableSubdefsPath($databox->get_dbname(), 'document', (string) $databox->get_sxml_structure()->path);
            foreach ($databox->get_subdef_structure() as $group => $subdefs) {
                foreach ($subdefs as $subdef) {
                    $this->ensureWriteableSubdefsPath($databox->get_dbname(), $group . '/' . $subdef->get_name(), (string) $databox->get_sxml_structure()->path);
                }
            }
        }
    }

    private function ensureWriteableSubdefsPath($dbName, $sdName, $path)
    {
        $this->addRequirement(
            is_dir($path),
            "$path ($dbName - $sdName) must be a directory",
            "Create directory \"<strong>$path</strong>\" directory so that the subdef could be stored."
        );

        $this->addRequirement(
            is_readable($path),
            "$path ($dbName - $sdName) directory must be readable",
            "Change the permissions of the \"<strong>$path</strong>\" directory so that the web server can read it."
        );

        $this->addRequirement(
            is_writable($path),
            "$path ($dbName - $sdName) directory must be writable",
            "Change the permissions of the \"<strong>$path</strong>\" directory so that the web server can write into it."
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return SubdefsPathsProbe
     */
    public static function create(Application $app)
    {
        return new static($app['phraseanet.appbox']);
    }
}
