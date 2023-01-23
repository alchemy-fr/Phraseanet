<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\ValueChecker;
use Assert\Assertion;

class MetadataKey implements Key
{
    private $name;
    private $tag_cache = [];

    public function __construct($name)
    {
        Assertion::string($name);
        $this->name = $name;
    }

    public function getIndexField(QueryContext $context, $raw = false)
    {
        return $this->getTag($context)->getIndexField($raw);
    }

    public function getFieldType(QueryContext $context)
    {
        return $this->getTag($context)->getType();
    }

    public function isValueCompatible($value, QueryContext $context)
    {
        return ValueChecker::isValueCompatible($this->getTag($context), $value);
    }

    private function getTag(QueryContext $context)
    {
        $hash = spl_object_hash($context);
        if (!isset($this->tag_cache[$hash])) {
            $this->tag_cache[$hash] = $context->getMetadataTag($this->name);
        }
        $tag = $this->tag_cache[$hash];
        if ($tag === null) {
            throw new QueryException(sprintf('Metadata tag "%s" does not exist', $this->name));
        }
        return $tag;
    }

    public function clearCache()
    {
        $this->tag_cache = [];
    }


    public function __toString()
    {
        return sprintf('metadata.%s', $this->name);
    }
}
