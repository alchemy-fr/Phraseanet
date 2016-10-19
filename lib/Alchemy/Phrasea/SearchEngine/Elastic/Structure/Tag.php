<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Assert\Assertion;

class Tag implements Typed
{
    private $name;
    private $type;
    private $analyzable;

    public function __construct($name, $type, $analyzable = false)
    {
        Assertion::string($name);
        Assertion::string($type);
        Assertion::boolean($analyzable);
        $this->name = $name;
        $this->type = $type;
        $this->analyzable = $analyzable;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isAnalyzable()
    {
        return $this->analyzable;
    }

    public function getIndexField($raw = false)
    {
        return sprintf(
            'metadata_tags.%s%s',
            $this->name,
            $raw && $this->type === FieldMapping::TYPE_STRING ? '.raw' : ''
        );
    }
}
