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
        if ($tag->getType() === 'string') {                         // "string" is phraseanet type
            $fieldMapping = new TextFieldMapping($tag->getName());

//            $fieldMapping->disableAnalysis();
//
//            if ($tag->isAnalyzable()) {
                $fieldMapping->addChild((new TextFieldMapping('raw'))->enableRawIndexing());
//                $fieldMapping->enableAnalysis();
//            }

            return $fieldMapping;
        }

        if ($tag->getType() === 'keyword') {                         // "keyword" comes only from media_subdef::getTechnicalFieldsList()
            $fieldMapping = new KeywordFieldMapping($tag->getName());

//            $fieldMapping->disableAnalysis();
//
//            if ($tag->isAnalyzable()) {
//                $fieldMapping->addChild((new TextFieldMapping('raw'))->enableRawIndexing());
//                $fieldMapping->enableAnalysis();
//            }

            return $fieldMapping;
        }

        return new FieldMapping($tag->getName(), $tag->getType());
    }
}
