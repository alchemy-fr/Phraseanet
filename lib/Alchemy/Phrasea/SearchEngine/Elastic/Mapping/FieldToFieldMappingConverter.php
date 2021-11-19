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
        $ret = null;
        switch($field->getType()) {
            case FieldMapping::TYPE_DATE:
                $ret = new DateFieldMapping($field->getName(), FieldMapping::DATE_FORMAT_MYSQL_OR_CAPTION);
                if (! $field->isFacet() && ! $field->isSearchable()) {
                    $ret->disableIndexing();
                }
                else {
                    // no more need "raw" for sorting (sort arg depends on type)
                    // $ret->addChild(
                    //     (new StringFieldMapping('raw'))
                    //         ->enableRawIndexing());
                    $ret->addChild(
                        (new StringFieldMapping('light'))
                            ->setAnalyzer('general_light')
                            ->enableTermVectors()
                    );
                }
                break;

            case FieldMapping::TYPE_STRING:
                $ret = new StringFieldMapping($field->getName());
                if (! $field->isFacet() && ! $field->isSearchable()) {
                    $ret->disableIndexing();
                }
                else {
                    $ret->addChild(
                        (new StringFieldMapping('raw'))
                            ->enableRawIndexing());
                    $ret->addAnalyzedChildren($locales);
                    $ret->enableTermVectors(true);
                }
                break;

            case FieldMapping::TYPE_DOUBLE:
                $ret = new DoubleFieldMapping($field->getName());
                if (! $field->isFacet() && ! $field->isSearchable()) {
                    $ret->disableIndexing();
                }
                else {
                    // no more need "raw" for sorting (sort arg depends on type)
                    // $ret->addChild(
                    //     (new StringFieldMapping('raw'))
                    //         ->enableRawIndexing());
                    $ret->addChild(
                        (new StringFieldMapping('light'))
                            ->setAnalyzer('general_light')
                            ->enableTermVectors()
                    );
                }
                break;

            default:
                $ret = new FieldMapping($field->getName(), $field->getType());
                break;
        }

        return $ret;
    }
}
