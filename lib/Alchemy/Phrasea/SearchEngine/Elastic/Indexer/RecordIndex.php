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

use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;

class RecordIndex
{
    /**
     * @var Structure
     */
    private $structure;

    public function __construct(Structure $structure)
    {
        $this->structure = $structure;
    }


    public function getMapping()
    {
        $mapping = new Mapping();
        $mapping
            // Identifiers
            ->add('record_id', 'integer')  // Compound primary key
            ->add('databox_id', 'integer') // Compound primary key
            ->add('databox_name', 'string')->notAnalyzed() // database name (still indexed for facets)
            ->add('base_id', 'integer') // Unique collection ID
            ->add('collection_id', 'integer')->notIndexed() // Useless collection ID (local to databox)
            ->add('collection_name', 'string')->notAnalyzed() // Collection name (still indexed for facets)
            ->add('uuid', 'string')->notIndexed()
            ->add('sha256', 'string')->notIndexed()
            // Mandatory metadata
            ->add('original_name', 'string')->notIndexed()
            ->add('mime', 'string')->notAnalyzed() // Indexed for Kibana only
            ->add('type', 'string')->notAnalyzed()
            ->add('record_type', 'string')->notAnalyzed() // record or story
            // Dates
            ->add('created_on', 'date')->format(Mapping::DATE_FORMAT_MYSQL_OR_CAPTION)
            ->add('updated_on', 'date')->format(Mapping::DATE_FORMAT_MYSQL_OR_CAPTION)
            // Thesaurus
            ->add('concept_path', $this->getThesaurusPathMapping())
            // EXIF
            ->add('metadata_tags', $this->getMetadataTagMapping())
            // Status
            ->add('flags', $this->getFlagsMapping())
            ->add('flags_bitfield', 'integer')->notIndexed()
            // Keep some fields arround for display purpose
            ->add('subdefs', Mapping::disabledMapping())
            ->add('title', Mapping::disabledMapping());

        // Caption mapping
        $this->buildCaptionMapping($this->structure->getUnrestrictedFields(), $mapping, 'caption');
        $this->buildCaptionMapping($this->structure->getPrivateFields(), $mapping, 'private_caption');

        return $mapping->export();
    }

    private function buildCaptionMapping(array $fields, Mapping $root, $section)
    {
        $mapping = new Mapping();

        foreach ($fields as $field) {
            $this->addFieldToMapping($field, $mapping);
        }

        $root->add($section, $mapping);
        $root
            ->add(sprintf('%s_all', $section), 'string')
            ->addLocalizedSubfields($this->locales)
            ->addRawVersion();
    }

    private function addFieldToMapping(Field $field, Mapping $mapping)
    {
        $type = $field->getType();
        $mapping->add($field->getName(), $type);

        if ($type === Mapping::TYPE_DATE) {
            $mapping->format(Mapping::DATE_FORMAT_CAPTION);
        }

        if ($type === Mapping::TYPE_STRING) {
            $searchable = $field->isSearchable();
            $facet = $field->isFacet();

            if (!$searchable && !$facet) {
                $mapping->notIndexed();
            } else {
                $mapping->addRawVersion();
                $mapping->addAnalyzedVersion($this->locales);
                $mapping->enableTermVectors(true);
            }
        }
    }

    private function getThesaurusPathMapping()
    {
        $mapping = new Mapping();

        foreach (array_keys($this->structure->getThesaurusEnabledFields()) as $name) {
            $mapping
                ->add($name, 'string')
                ->analyzer('thesaurus_path', 'indexing')
                ->analyzer('keyword', 'searching')
                ->addRawVersion()
            ;
        }

        return $mapping;
    }

    private function getMetadataTagMapping()
    {
        $mapping = new Mapping();

        foreach ($this->structure->getMetadataTags() as $tag) {
            $type = $tag->getType();

            $mapping->add($tag->getName(), $type);

            if ($type === Mapping::TYPE_STRING) {
                if ($tag->isAnalyzable()) {
                    $mapping->addRawVersion();
                } else {
                    $mapping->notAnalyzed();
                }
            }
        }

        return $mapping;
    }

    private function getFlagsMapping()
    {
        $mapping = new Mapping();

        foreach ($this->structure->getAllFlags() as $name => $_) {
            $mapping->add($name, 'boolean');
        }

        return $mapping;
    }
}
