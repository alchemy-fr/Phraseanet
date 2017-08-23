<?php

namespace Alchemy\Phrasea\Databox\Subdef;

class SubdefPreset
{
    /**
     * @var string
     */
    private $mediaType;

    /**
     * @var string
     */
    private $label;

    /**
     * @var array
     */
    private $definitions;

    /**
     * @param string $mediaType
     * @param array $definitions
     */
    public function __construct($mediaType, array $definitions)
    {
        foreach ($definitions as $definition) {
            if (! $definition instanceof Subdef) {

            }
        }

        $this->mediaType = (string) $mediaType;
        $this->definitions = $definitions;
    }

    /**
     * @return string
     */
    public function getMediaType()
    {
        return $this->label;
    }

    /**
     * @return array
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }
}
