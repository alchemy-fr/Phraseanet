<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Helper as ThesaurusHelper;
use databox_field;

/**
 * @todo Field labels
 */
class Field
{
    private $name;
    private $type;
    private $is_searchable;
    private $is_private;
    private $is_facet;
    private $thesaurus_roots;

    public static function createFromLegacyField(databox_field $field)
    {
        $type = self::getTypeFromLegacy($field);

        // Thesaurus concept inference
        $xpath = $field->get_tbranch();
        if ($type === Mapping::TYPE_STRING && !empty($xpath)) {
            $databox = $field->get_databox();
            $roots = ThesaurusHelper::findConceptsByXPath($databox, $xpath);
        } else {
            $roots = null;
        }

        return new self(
            $field->get_name(),
            $type,
            $field->is_indexable(),
            $field->isBusiness(),
            $field->isAggregable(),
            $roots
        );
    }

    private static function getTypeFromLegacy(databox_field $field)
    {
        $type = $field->get_type();
        switch ($type) {
            case databox_field::TYPE_DATE:
                return Mapping::TYPE_DATE;
            case databox_field::TYPE_NUMBER:
                return Mapping::TYPE_DOUBLE;
            case databox_field::TYPE_STRING:
            case databox_field::TYPE_TEXT:
                return Mapping::TYPE_STRING;
            default:
                throw new Exception(sprintf('Invalid field type "%s", expected "date", "number" or "string".', $type));
        }
    }

    public function __construct($name, $type, $searchable = true, $private = false, $facet = false, array $thesaurus_roots = null)
    {
        $this->name = (string) $name;
        $this->type = (string) $type;
        $this->is_searchable = (bool) $searchable;
        $this->is_private = (bool) $private;
        $this->is_facet = (bool) $facet;
        $this->thesaurus_roots = $thesaurus_roots;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isSearchable()
    {
        return $this->is_searchable;
    }

    public function isPrivate()
    {
        return $this->is_private;
    }

    public function isFacet()
    {
        return $this->is_facet;
    }

    public function hasConceptInference()
    {
        return $this->thesaurus_roots !== null;
    }

    public function getThesaurusRoots()
    {
        return $this->thesaurus_roots;
    }

    public function mergeWith(Field $other)
    {
        if (($name = $other->getName()) !== $this->name) {
            throw new MergeException(sprintf("Fields have different names (%s vs %s)", $this->name, $name));
        }

        // Since mapping is merged between databoxes, two fields may
        // have conflicting names. Indexing is the same for a given
        // type so we reject only those with different types.

        if (($type = $other->getType()) !== $this->type) {
            throw new MergeException(sprintf("Field %s can't be merged, incompatible types (%s vs %s)", $name, $type, $this->type));
        }

        if ($other->isPrivate() !== $this->is_private) {
            throw new MergeException(sprintf("Field %s can't be merged, could not mix private and public fields with same name", $name));
        }

        if ($other->isSearchable() !== $this->is_searchable) {
            throw new MergeException(sprintf("Field %s can't be merged, incompatible searchable state", $name));
        }

        if ($other->isFacet() !== $this->is_facet) {
            throw new MergeException(sprintf("Field %s can't be merged, incompatible to_aggregate state", $name));
        }
    }
}
