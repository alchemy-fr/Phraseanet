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
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;

class FieldToFieldMappingConverter
{

    public function convertField(Field $field, array $locales)
    {
        if ($field->getType() === FieldMapping::TYPE_DATE) {
            return new DateFieldMapping($field->getName(), FieldMapping::DATE_FORMAT_CAPTION);
        }

        if ($field->getType() === FieldMapping::TYPE_STRING) {
            $fieldMapping = new StringFieldMapping($field->getName());

            if (! $field->isFacet() && ! $field->isSearchable()) {
                $fieldMapping->disableIndexing();
            } else {
                $fieldMapping->addChild((new StringFieldMapping('raw'))->enableRawIndexing());
                $fieldMapping->addAnalyzedChildren($locales);
                $fieldMapping->enableTermVectors(true);
            }

            return $fieldMapping;
        }

        return new FieldMapping($field->getName(), $field->getType());
    }
}
