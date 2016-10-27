<?php

/*
 * This file is part of phrasea-4.0.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\MappingBuilder;
use Alchemy\Phrasea\SearchEngine\Elastic\MappingProvider;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;

class RecordIndex implements MappingProvider
{
    /**
     * @var Structure
     */
    private $structure;

    /**
     * @var string[]
     */
    private $locales;

    /**
     * @param Structure $structure
     * @param array $locales
     */
    public function __construct(Structure $structure, array $locales)
    {
        $this->structure = $structure;
        $this->locales = $locales;
    }

    /**
     * @return Mapping
     */
    public function getMapping()
    {
        $mapping = new MappingBuilder();

        // Compound primary key
        $mapping->addField('record_id', FieldMapping::TYPE_INTEGER);
        $mapping->addField('databox_id', FieldMapping::TYPE_INTEGER);

        // Database name (still indexed for facets)
        $mapping->addStringField('databox_name')->disableAnalysis();
        // Unique collection ID
        $mapping->addIntegerField('base_id');
        // Useless collection ID (local to databox)
        $mapping->addIntegerField('collection_id')->disableIndexing();
        // Collection name (still indexed for facets)
        $mapping->addStringField('collection_name')->disableAnalysis();

        $mapping->addStringField('uuid')->disableIndexing();
        $mapping->addStringField('sha256')->disableIndexing();
        $mapping->addStringField('original_name')->disableIndexing();
        $mapping->addStringField('mime')->disableAnalysis();
        $mapping->addStringField('type')->disableAnalysis();
        $mapping->addStringField('record_type')->disableAnalysis();

        $mapping->addDateField('created_on', FieldMapping::DATE_FORMAT_MYSQL_OR_CAPTION);
        $mapping->addDateField('updated_on', FieldMapping::DATE_FORMAT_MYSQL_OR_CAPTION);

        $mapping->add($this->buildThesaurusPathMapping('concept_path'));
        $mapping->add($this->buildMetadataTagMapping('metadata_tags'));
        $mapping->add($this->buildFlagMapping('flags'));

        $mapping->addIntegerField('flags_bitfield')->disableIndexing();
        $mapping->addObjectField('subdefs')->disableMapping();
        $mapping->addObjectField('title')->disableMapping();

        // Caption mapping
        $this->buildCaptionMapping($mapping, 'caption', $this->structure->getUnrestrictedFields());
        $this->buildCaptionMapping($mapping, 'private_caption', $this->structure->getPrivateFields());

        return $mapping->getMapping();
    }

    private function buildCaptionMapping(MappingBuilder $parent, $name, array $fields)
    {
        $fieldConverter = new Mapping\FieldToFieldMappingConverter();
        $captionMapping = new Mapping\ComplexFieldMapping($name, FieldMapping::TYPE_OBJECT);

        $captionMapping->useAsPropertyContainer();

        foreach ($fields as $field) {
            $captionMapping->addChild($fieldConverter->convertField($field, $this->locales));
        }

        $parent->add($captionMapping);

        $localizedCaptionMapping = new Mapping\StringFieldMapping(sprintf('%s_all', $name));
        $localizedCaptionMapping
            ->addLocalizedChildren($this->locales)
            ->addChild((new Mapping\StringFieldMapping('raw'))->enableRawIndexing());

        $parent->add($localizedCaptionMapping);

        return $captionMapping;
    }

    private function buildThesaurusPathMapping($name)
    {
        $thesaurusMapping = new Mapping\ComplexFieldMapping($name, FieldMapping::TYPE_OBJECT);

        $thesaurusMapping->useAsPropertyContainer();

        foreach (array_keys($this->structure->getThesaurusEnabledFields()) as $name) {
            $child = new Mapping\StringFieldMapping($name);

            $child->setAnalyzer('thesaurus_path', 'indexing');
            $child->setAnalyzer('keyword', 'searching');
            $child->addChild((new Mapping\StringFieldMapping('raw'))->enableRawIndexing());

            $thesaurusMapping->addChild($thesaurusMapping);
        }

        return $thesaurusMapping;
    }

    private function buildMetadataTagMapping($name)
    {
        $tagConverter = new Mapping\MetadataTagToFieldMappingConverter();
        $metadataMapping = new Mapping\ComplexFieldMapping($name, FieldMapping::TYPE_OBJECT);

        $metadataMapping->useAsPropertyContainer();

        foreach ($this->structure->getMetadataTags() as $tag) {
            $metadataMapping->addChild($tagConverter->convertTag($tag));
        }

        return $metadataMapping;
    }

    private function buildFlagMapping($name)
    {
        $index = 0;
        $flagMapping = new Mapping\ComplexFieldMapping($name, FieldMapping::TYPE_OBJECT);

        $flagMapping->useAsPropertyContainer();

        foreach ($this->structure->getAllFlags() as $childName => $_) {
            if (trim($childName) == '') {
                $childName = 'flag_' . $index++;
            }

            $flagMapping->addChild(new FieldMapping($childName, FieldMapping::TYPE_BOOLEAN));
        }

        return $flagMapping;
    }
}
