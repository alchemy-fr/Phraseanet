<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Assert\Assertion;
use DomainException;

final class GlobalStructure implements Structure
{
    /** @var Field[] */
    private $fields = array();
    /** @var Field[] */
    private $date_fields = array();
    /** @var Field[] */
    private $thesaurus_fields = array();
    /** @var Field[] */
    private $private = array();
    /** @var Field[] */
    private $facets = array();
    private $flags = array();
    private $metadata_tags = array();

    /**
     * @param \databox[] $databoxes
     * @return self
     */
    public static function createFromDataboxes(array $databoxes)
    {
        $fields = [];
        $flags = [];
        foreach ($databoxes as $databox) {
            foreach ($databox->get_meta_structure() as $fieldStructure) {
                $fields[] = Field::createFromLegacyField($fieldStructure);
            }
            foreach ($databox->getStatusStructure() as $status) {
                $flags[] = Flag::createFromLegacyStatus($status);
            }
        }
        return new self($fields, $flags, MetadataHelper::createTags());
    }

    public function __construct(array $fields = [], array $flags = [], array $metadata_tags = [])
    {
        Assertion::allIsInstanceOf($fields, Field::class);
        Assertion::allIsInstanceOf($flags, Flag::class);
        Assertion::allIsInstanceOf($metadata_tags, Tag::class);
        foreach ($fields as $field) {
            $this->add($field);
        }
        foreach ($flags as $flag) {
            $this->flags[$flag->getName()] = $flag;
        }
        foreach ($metadata_tags as $tag) {
            $this->metadata_tags[$tag->getName()] = $tag;
        }
    }

    public function add(Field $field)
    {
        $name = $field->getName();
        if (isset($this->fields[$name])) {
            $field = $this->fields[$name]->mergeWith($field);
        }
        $this->fields[$name] = $field;

        if ($field->getType() === Mapping::TYPE_DATE) {
            $this->date_fields[$name] = $field;
        }
        if ($field->isPrivate()) {
            $this->private[$name] = $field;
        }
        if ($field->isFacet() && $field->isSearchable()) {
            $this->facets[$name] = $field;
        }
        if ($field->hasConceptInference()) {
            $this->thesaurus_fields[$name] = $field;
        }
    }

    public function getAllFields()
    {
        return $this->fields;
    }

    public function getUnrestrictedFields()
    {
        return array_diff_key($this->fields, $this->private);
    }

    public function getPrivateFields()
    {
        return $this->private;
    }

    /**
     * @return Field[]
     */
    public function getFacetFields()
    {
        return $this->facets;
    }

    public function getThesaurusEnabledFields()
    {
        return $this->thesaurus_fields;
    }

    public function getDateFields()
    {
        return $this->date_fields;
    }

    /**
     * @param string $name
     * @return null|Field
     */
    public function get($name)
    {
        return isset($this->fields[$name]) ?
                     $this->fields[$name] : null;
    }

    public function typeOf($name)
    {
        return isset($this->fields[$name]) ?
                     $this->fields[$name]->getType() : null;
    }

    /**
     * @param $name
     * @return bool
     */
    public function isPrivate($name)
    {
        if (isset($this->private[$name])) {
            return true;
        } elseif (isset($this->fields[$name])) {
            return false;
        }

        throw new DomainException(sprintf('Unknown field "%s".', $name));
    }

    public function getAllFlags()
    {
        return $this->flags;
    }

    public function getFlagByName($name)
    {
        return isset($this->flags[$name]) ?
                     $this->flags[$name] : null;
    }

    public function getMetadataTags()
    {
        return $this->metadata_tags;
    }

    public function getMetadataTagByName($name)
    {
        return isset($this->metadata_tags[$name]) ?
                     $this->metadata_tags[$name] : null;
    }

    /**
     * Returns an array of collections indexed by field name.
     *
     * [
     *     "FieldName" => [1, 4, 5],
     *     "OtherFieldName" => [4],
     * ]
     */
    public function getCollectionsUsedByPrivateFields()
    {
        $map = [];
        foreach ($this->private as $name => $field) {
            $map[$name] = $field->getDependantCollections();
        }

        return $map;
    }
}
