<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use DomainException;

final class GlobalStructure implements Structure
{
    /**
     * @var Field[]
     */
    private $fields = array();

    /**
     * @var Field[][]
     */
    private $fieldsByDatabox = [];

    /**
     * @var Field[]
     * */
    private $date_fields = array();

    /**
     * @var Field[]
     */
    private $thesaurus_fields = array();

    /**
     * @var Field[]
     */
    private $private = array();

    /**
     * @var Flag[]
     */
    private $flags = array();

    /**
     * @var Tag[]
     */
    private $metadata_tags = array();


    /**
     * GlobalStructure constructor.
     * @param Field[] $fields
     * @param Flag[] $flags
     * @param Tag[] $metadata_tags
     */
    public function __construct(array $fields = [], array $flags = [], array $metadata_tags = [])
    {

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
        // store info for each field, not still merged by databox
        $this->fieldsByDatabox[$field->get_databox_id()][$field->getName()] = $field;

        // store merged infos (same field name)
        $name = $field->getName();

        if (isset($this->fields[$name])) {
            $field = $this->fields[$name]->mergeWith($field);
        }

        $this->fields[$name] = $field;

        if ($field->getType() === FieldMapping::TYPE_DATE) {
            $this->date_fields[$name] = $field;
        }

        if ($field->isPrivate()) {
            $this->private[$name] = $field;
        }

        /*
        if ($field->isFacet() && $field->isSearchable()) {
            $this->facets[$name] = $field;
        }
        */

        if ($field->hasConceptInference()) {
            $this->thesaurus_fields[$name] = $field;
        }
    }

    public function getDataboxes()
    {
        return array_keys($this->fieldsByDatabox);
    }

    /**
     * @return Field[]
     */
    public function getAllFields()
    {
        return $this->fields;
    }

    public function getAllFieldsByDatabox($databox_id)
    {
        return $this->fieldsByDatabox[$databox_id];
    }

    /**
     * @return Field[]
     */
    public function getUnrestrictedFields()
    {
        return array_diff_key($this->fields, $this->private);
    }

    /**
     * @return Field[]
     */
    public function getPrivateFields()
    {
        return $this->private;
    }

    /**
     * @return Field[]
     */
    public function getThesaurusEnabledFields()
    {
        return $this->thesaurus_fields;
    }

    /**
     * @return Field[]
     */
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
        return isset($this->fields[$name]) ? $this->fields[$name] : null;
    }

    /**
     * @param string $name
     * @return null|string
     */
    public function typeOf($name)
    {
        $field = $this->get($name);

        return $field ? $field->getType() : null;
    }

    /**
     * @param string $name
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

    /**
     * @return Flag[]
     */
    public function getAllFlags()
    {
        return $this->flags;
    }

    /**
     * @param string $name
     * @return Flag|null
     */
    public function getFlagByName($name)
    {
        return isset($this->flags[$name]) ?
                     $this->flags[$name] : null;
    }

    /**
     * @return Tag[]
     */
    public function getMetadataTags()
    {
        return $this->metadata_tags;
    }

    /**
     * @param string $name
     * @return Tag|null
     */
    public function getMetadataTagByName($name)
    {
        return isset($this->metadata_tags[$name]) ? $this->metadata_tags[$name] : null;
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
