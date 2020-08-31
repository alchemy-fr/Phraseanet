<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Assert\Assertion;

class Tag implements Typed
{
    private $name;
    private $type;

    public function __construct($name, $type)
    {
        Assertion::string($name);
        Assertion::string($type);
        $this->name = $name;
        $this->type = $type;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getIndexField($raw = false)
    {
        return sprintf(
            'metadata_tags.%s%s',
            $this->name,
            $raw && $this->type === FieldMapping::TYPE_TEXT ? '.raw' : ''
        );
    }
}
