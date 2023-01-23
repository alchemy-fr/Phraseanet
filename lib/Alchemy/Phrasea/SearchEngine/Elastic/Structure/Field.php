<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException;
use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Helper as ThesaurusHelper;
use databox_field;

/**
 * @todo Field labels
 */
class Field implements Typed
{

    const FACET_DISABLED = null;
    const FACET_NO_LIMIT = 0;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $databox_id;

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $is_searchable;

    /**
     * @var bool
     */
    private $is_private;

    private $facet; // facet values limit or NULL (zero means no limit)

    private $thesaurus_roots;

    private $generate_cterms;

    private $used_by_collections;

    private $used_by_databoxes;

    public static function createFromLegacyField(databox_field $field)
    {
        $type = self::getTypeFromLegacy($field);
        $databox = $field->get_databox();

        // Thesaurus concept inference
        $roots = null;
        if($type === FieldMapping::TYPE_STRING && !empty($xpath = $field->get_tbranch())) {
            $roots = ThesaurusHelper::findConceptsByXPath($databox, $xpath);
        }

        // Facet (enable + optional limit)
        $facet = $field->getFacetValuesLimit();
        if ($facet === databox_field::FACET_DISABLED) {
            $facet = self::FACET_DISABLED;
        } elseif ($facet === databox_field::FACET_NO_LIMIT) {
            $facet = self::FACET_NO_LIMIT;
        }

        return new self($field->get_name(), $type, [
            'databox_id' => $databox->get_sbas_id(),
            'searchable' => $field->is_indexable(),
            'private' => $field->isBusiness(),
            'facet' => $facet,
            'thesaurus_roots' => $roots,
            'generate_cterms' => $field->get_generate_cterms(),
            'used_by_collections' => $databox->get_collection_unique_ids(),
            'used_by_databoxes' => [$databox->get_sbas_id()]
        ]);
    }

    private static function getTypeFromLegacy(databox_field $field)
    {
        $type = $field->get_type();

        switch ($type) {
            case databox_field::TYPE_DATE:
                return FieldMapping::TYPE_DATE;
            case databox_field::TYPE_NUMBER:
                return FieldMapping::TYPE_DOUBLE;
            case databox_field::TYPE_STRING:
                return FieldMapping::TYPE_STRING;
        }

        throw new \InvalidArgumentException(sprintf('Invalid field type "%s", expected "date", "number" or "string".', $type));
    }

    public function __construct($name, $type, array $options = [])
    {
        $this->name = (string) $name;
        $this->type = $type;
        if(1) {
            $this->databox_id = \igorw\get_in($options, ['databox_id'], 0);
            $this->is_searchable = \igorw\get_in($options, ['searchable'], true);
            $this->is_private = \igorw\get_in($options, ['private'], false);
            $this->facet = \igorw\get_in($options, ['facet']);
            $this->thesaurus_roots = \igorw\get_in($options, ['thesaurus_roots'], null);
            $this->generate_cterms = \igorw\get_in($options, ['generate_cterms'], false);
            $this->used_by_collections = \igorw\get_in($options, ['used_by_collections'], []);
            $this->used_by_databoxes = \igorw\get_in($options, ['used_by_databoxes'], []);
        }
        else {
            // todo: this is faster code, but need to fix unit-tests to pass all options
            $this->databox_id = $options['databox_id'];
            $this->is_searchable = $options['searchable'];
            $this->is_private = $options['private'];
            $this->facet = $options['facet'];
            $this->thesaurus_roots = $options['thesaurus_roots'];
            $this->generate_cterms = $options['generate_cterms'];
            $this->used_by_collections = $options['used_by_collections'];
            $this->used_by_databoxes = $options['used_by_databoxes'];
        }
    }

    public function withOptions(array $options)
    {
        return new self($this->name, $this->type, $options + [
            'databox_id' => $this->databox_id,
            'searchable' => $this->is_searchable,
            'private' => $this->is_private,
            'facet' => $this->facet,
            'thesaurus_roots' => $this->thesaurus_roots,
            'generate_cterms' => $this->generate_cterms,
            'used_by_collections' => $this->used_by_collections,
            'used_by_databoxes' => $this->used_by_databoxes
        ]);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getIndexField($raw = false)
    {
        return sprintf(
            '%scaption.%s%s',
            $this->is_private ? 'private_' : '',
            $this->name,
            $raw && $this->type === FieldMapping::TYPE_STRING ? '.raw' : ''
        );
    }

    public function getConceptPathIndexField()
    {
        return sprintf('concept_path.%s', $this->name);
    }

    public function get_databox_id()
    {
        return $this->databox_id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getDependantCollections()
    {
        return $this->used_by_collections;
    }

    public function getDependantDataboxes()
    {
        return $this->used_by_databoxes;
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
        return $this->facet !== self::FACET_DISABLED;
    }

    public function getFacetValuesLimit()
    {
        return $this->facet;
    }

    public function hasConceptInference()
    {
        return $this->thesaurus_roots !== null;
    }

    public function getThesaurusRoots()
    {
        return $this->thesaurus_roots;
    }

    public function get_generate_cterms()
    {
        return $this->generate_cterms;
    }

    /**
     * Merge with another field, returning the new instance
     *
     * @param Field $other
     * @return Field
     * @throws MergeException
     */
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
            throw new MergeException(sprintf("Field %s can't be merged, incompatible searchablility", $name));
        }

        if ($other->getFacetValuesLimit() !== $this->facet) {
            throw new MergeException(sprintf("Field %s can't be merged, incompatible facet eligibility", $name));
        }

        $thesaurus_roots = null;

        if ($this->thesaurus_roots !== null || $other->thesaurus_roots !== null) {
            $thesaurus_roots = array_merge(
                (array) $this->thesaurus_roots,
                (array) $other->thesaurus_roots
            );
        }

        $used_by_collections = array_values(
            array_unique(
                array_merge(
                    $this->used_by_collections,
                    $other->used_by_collections
                ),
                SORT_REGULAR
            )
        );

        $used_by_databoxes = array_values(
            array_unique(
                array_merge(
                    $this->used_by_databoxes,
                    $other->used_by_databoxes
                ),
                SORT_REGULAR
            )
        );

        return $this->withOptions([
            'thesaurus_roots' => $thesaurus_roots,
            'used_by_collections' => $used_by_collections,
            'used_by_databoxes' => $used_by_databoxes
        ]);
    }

}
