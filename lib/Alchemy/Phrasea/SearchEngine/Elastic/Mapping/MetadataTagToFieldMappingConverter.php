<?php

/*
 * This file is part of phrasea-4.0.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Mapping;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Tag;

class MetadataTagToFieldMappingConverter
{

    public function convertTag(Tag $tag)
    {
        if ($tag->getType() === FieldMapping::TYPE_STRING) {

            $fieldMapping = new StringFieldMapping($tag->getName());
            $fieldMapping->addChild((new StringFieldMapping('raw'))->enableRawIndexing());
            if ($tag->isAnalyzable()) {
                $fieldMapping->enableAnalysis();
            }
            else {
                $fieldMapping->disableAnalysis();
            }

            return $fieldMapping;
        }

        return new FieldMapping($tag->getName(), $tag->getType());
    }

    public function dead_convertTag(Tag $tag)
    {
        if ($tag->getType() === FieldMapping::TYPE_STRING) {
            $fieldMapping = new StringFieldMapping($tag->getName());

            $fieldMapping->disableAnalysis();

            if ($tag->isAnalyzable()) {
                $fieldMapping->addChild((new StringFieldMapping('raw'))->enableRawIndexing());
                $fieldMapping->enableAnalysis();
            }

            return $fieldMapping;
        }

        return new FieldMapping($tag->getName(), $tag->getType());
    }
}
