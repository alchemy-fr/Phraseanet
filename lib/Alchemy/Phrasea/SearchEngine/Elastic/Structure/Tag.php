<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

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
            'exif.%s%s',
            $this->name,
            $raw && $this->type === Mapping::TYPE_STRING ? '.raw' : ''
        );
    }
}
