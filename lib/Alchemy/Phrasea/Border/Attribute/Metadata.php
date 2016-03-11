<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border\Attribute;

use Alchemy\Phrasea\Application;
use PHPExiftool\Driver\Metadata\Metadata as ExiftoolMeta;

/**
 * Phraseanet Border Metadata Attribute
 *
 * This attribute is used to store a PHPExiftool metadatas with file prior to
 * their record creation
 */
class Metadata implements AttributeInterface
{
    protected $metadata;

    /**
     * Constructor
     *
     * @param ExiftoolMeta $metadata The metadata
     */
    public function __construct(ExiftoolMeta $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->metadata = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME_METADATA;
    }

    /**
     * {@inheritdoc}
     *
     * @return ExiftoolMeta
     */
    public function getValue()
    {
        return $this->metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function asString()
    {
        return serialize($this->metadata);
    }

    /**
     * {@inheritdoc}
     *
     * @return Metadata
     */
    public static function loadFromString(Application $app, $string)
    {
        if (!$metadata = @unserialize($string)) {
            throw new \InvalidArgumentException('Unable to load metadata from string');
        }

        if (!$metadata instanceof ExiftoolMeta) {
            throw new \InvalidArgumentException('Unable to load metadata from string');
        }

        return new static($metadata);
    }
}
