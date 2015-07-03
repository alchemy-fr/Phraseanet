<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Term;

class TextNode extends AbstractTermNode implements ContextAbleInterface
{
    /**
     * Merge two text nodes by concatenating their content.
     *
     * /!\ Text contexts are lost in the process
     *
     * @param  TextNode $a First node
     * @param  TextNode $b The other one
     * @return TextNode    Merged text node
     */
    public static function merge(TextNode $a, TextNode $b)
    {
        return new self(sprintf('%s%s', $a->getValue(), $b->getValue()));
    }

    /**
     * Creates a new text node with the same content and the provided context.
     *
     * /!\ The original node context will not be preserved (ie. not merged).
     *
     * @param  Context $context Context to add on the new node
     * @return TextNode         A text node with a context
     */
    public function withContext(Context $context)
    {
        return new self($this->getValue(), $context);
    }

    public function buildQuery(QueryContext $context)
    {
        $query = $this->buildMatcher($context->getLocalizedFields());

        foreach ($this->buildPrivateFieldQueries($context) as $private_field_query) {
            $query = QueryHelper::applyBooleanClause($query, 'should', $private_field_query);
        }

        foreach ($this->buildConceptQueries($context) as $concept_query) {
            $query = QueryHelper::applyBooleanClause($query, 'should', $concept_query);
        }

        return $query;
    }

    private function buildPrivateFieldQueries(QueryContext $context)
    {
        // We make a boolean clause for each collection set to shrink query size
        // (instead of a clause for each field, with his collection set)
        $fields_map = [];
        $collections_map = [];
        foreach ($context->getAllowedPrivateFields() as $field) {
            $collections = $context->getAllowedCollectionsOnPrivateField($field);
            $hash = self::hashCollections($collections);
            $collections_map[$hash] = $collections;
            if (!isset($fields_map[$hash])) {
                $fields_map[$hash] = [];
            }
            // Merge fields with others having the same collections
            $fields = $context->localizeField($field->getIndexFieldName());
            foreach ($fields as $fields_map[$hash][]);
        }

        $queries = [];
        foreach ($fields_map as $hash => $fields) {
            // Right to query on a private field is dependant of document collection
            // Here we make sure we can only match on allowed collections
            $query = [];
            $query['bool']['must'][0]['terms']['base_id'] = $collections_map[$hash];
            $query['bool']['must'][1] = $this->buildMatcher($fields);
            $queries[] = $query;
        }

        return $queries;
    }

    private function buildMatcher(array $fields)
    {
        return [
            'multi_match' => [
                'fields'   => $fields,
                'query'    => $this->text,
                'operator' => 'and',
            ]
        ];
    }

    private static function hashCollections(array $collections)
    {
        sort($collections, SORT_REGULAR);
        return implode('|', $collections);
    }

    public function __toString()
    {
        return sprintf('<text:%s>', Term::dump($this));
    }
}
