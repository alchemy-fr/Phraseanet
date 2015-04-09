<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
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
        $query = array(
            'multi_match' => array(
                'fields'   => $context->getLocalizedFields(),
                'query'    => $this->text,
                'operator' => 'and',
            )
        );

        if ($conceptQueries = $this->buildConceptQueries($context)) {
            $textQuery = $query;
            $query = array();
            $query['bool']['should'] = $conceptQueries;
            $query['bool']['should'][] = $textQuery;
        }

        return $query;
    }

    public function __toString()
    {
        return sprintf('<text:%s>', Term::dump($this));
    }
}
