<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use DomainException;

class Structure
{
    private $fields = array();
    private $date_fields = array();
    private $thesaurus_fields = array();
    private $private = array();
    private $facets = array();
    private $aliases = array();

    /**
     * @param \databox[] $databoxes
     * @return self
     */
    public static function createFromDataboxes(array $databoxes)
    {
        $structure = new self();
        foreach ($databoxes as $databox) {
            foreach ($databox->get_meta_structure() as $fieldStructure) {
                $field = Field::createFromLegacyField($fieldStructure);
                $structure->add($field);
            }
        }
        return $structure;
    }

    public function add(Field $field)
    {
        $name = $field->getName();
        if (isset($this->fields[$name])) {
            $this->fields[$name]->mergeWith($field);
        } else {
            $this->fields[$name] = $field;
        }

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

    public function isPrivate($name)
    {
        if (isset($this->private[$name])) {
            return true;
        } elseif (isset($this->fields[$name])) {
            return false;
        } else {
            throw new DomainException(sprintf('Unknown field "%s".', $name));
        }
    }
}
