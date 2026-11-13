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
use Alchemy\Phrasea\Metadata\Tag\NoSource;
use Alchemy\Phrasea\Metadata\TagFactory;
use PHPExiftool\Driver\Metadata\Metadata as ExiftoolMeta;
use PHPExiftool\Driver\Value\Binary;
use PHPExiftool\Driver\Value\Mono;
use PHPExiftool\Driver\Value\Multi;
use PHPExiftool\Driver\Value\ValueInterface;
use PHPExiftool\Exception\ExceptionInterface as PHPExiftoolException;

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
     *
     * The tag is stored by name: phpexiftool tag classes are generated and cannot be unserialized.
     */
    public function asString()
    {
        $tag = $this->metadata->getTagGroup();
        $value = $this->metadata->getValue();

        $data = [
            'tagname' => $tag->getId(),
            'type'    => $value->getType(),
            'value'   => $value instanceof Binary ? $value->asBase64() : $value->asArray(),
        ];

        if ($tag instanceof NoSource) {
            $data['fieldname'] = $tag->getFieldName();
        }

        return serialize($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return Metadata
     */
    public static function loadFromString(Application $app, $string)
    {
        $data = @unserialize($string, ['allowed_classes' => false]);

        if ($data instanceof \__PHP_Incomplete_Class) {
            $data = static::decodeLegacyMetadata($data);
        }

        if (!is_array($data) || !isset($data['tagname'], $data['type']) || !array_key_exists('value', $data)) {
            throw new \InvalidArgumentException('Unable to load metadata from string');
        }

        try {
            $tag = isset($data['fieldname']) ? new NoSource($data['fieldname']) : TagFactory::getFromRDFTagname($data['tagname']);
        } catch (PHPExiftoolException $e) {
            throw new \InvalidArgumentException('Unable to load metadata from string', 0, $e);
        }

        switch ($data['type']) {
            case ValueInterface::TYPE_BINARY:
                $value = Binary::loadFromBase64(is_array($data['value']) ? implode('', $data['value']) : $data['value']);
                break;
            case ValueInterface::TYPE_MULTI:
                $value = new Multi((array) $data['value']);
                break;
            case ValueInterface::TYPE_MONO:
                $value = new Mono(is_array($data['value']) ? implode('', $data['value']) : $data['value']);
                break;
            default:
                throw new \InvalidArgumentException('Unable to load metadata from string');
        }

        return new static(new ExiftoolMeta($tag, $value));
    }

    /**
     * Decodes a Metadata object serialized with phpexiftool 0.x
     *
     * @return array|null
     */
    private static function decodeLegacyMetadata(\__PHP_Incomplete_Class $metadata)
    {
        $metadata = (array) $metadata;
        $tag = isset($metadata["\0*\0tag"]) ? (array) $metadata["\0*\0tag"] : [];
        $value = isset($metadata["\0*\0value"]) ? (array) $metadata["\0*\0value"] : [];

        if (!isset($tag["\0*\0GroupName"], $tag["\0*\0Name"], $value['__PHP_Incomplete_Class_Name']) || !array_key_exists("\0*\0value", $value)) {
            return null;
        }

        $types = [
            'PHPExiftool\\Driver\\Value\\Mono'   => ValueInterface::TYPE_MONO,
            'PHPExiftool\\Driver\\Value\\Multi'  => ValueInterface::TYPE_MULTI,
            'PHPExiftool\\Driver\\Value\\Binary' => ValueInterface::TYPE_BINARY,
        ];

        if (!isset($types[$value['__PHP_Incomplete_Class_Name']])) {
            return null;
        }

        $data = [
            'tagname' => $tag["\0*\0GroupName"] . ':' . $tag["\0*\0Name"],
            'type'    => $types[$value['__PHP_Incomplete_Class_Name']],
            'value'   => $value["\0*\0value"],
        ];

        if (ValueInterface::TYPE_BINARY === $data['type']) {
            $data['value'] = base64_encode($data['value']);
        }

        if (isset($tag["\0Alchemy\\Phrasea\\Metadata\\Tag\\NoSource\0fieldName"])) {
            $data['fieldname'] = $tag["\0Alchemy\\Phrasea\\Metadata\\Tag\\NoSource\0fieldName"];
        }

        return $data;
    }
}
