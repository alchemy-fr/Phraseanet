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

class DataboxStructureProbe extends RequirementCollection implements ProbeInterface
{
    public function __construct(\appbox $appbox)
    {
        $this->setName('Databoxes structure');

        foreach ($appbox->get_databoxes() as $databox) {
            foreach ($databox->get_meta_structure() as $field) {
                $this->verifyDataboxField($databox->get_dbname(), $field->get_name(), $field->get_original_source(), $field->get_tag()->getTagname());
            }
        }
    }

    private function verifyDataboxField($dbName, $field, $original, $tagname)
    {
        $this->addRequirement(
            $original === $tagname,
            "$dbName::$field must be be set to a valid metadata source",
            "Source \"<strong>$original</strong>\" is not a valid one, please fix it."
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return DataboxStructureProbe
     */
    public static function create(Application $app)
    {
        return new static($app['phraseanet.appbox']);
    }
}
