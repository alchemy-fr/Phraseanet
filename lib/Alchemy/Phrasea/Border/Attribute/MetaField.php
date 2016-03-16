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

/**
 * Phraseanet Border MetaField Attribute
 *
 * This attribute is used to store a value related to a fieldname for a file
 * prior to their record creation
 */
class MetaField implements AttributeInterface
{
    /**
     * @var \databox_field
     */
    protected $databox_field;

    /**
     * @var array
     */
    protected $value;

    /**
     * Constructor
     *
     * @param \databox_field $databox_field The databox field
     * @param array          $value         An array of scalar values
     */
    public function __construct(\databox_field $databox_field, array $value)
    {
        $this->databox_field = $databox_field;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME_METAFIELD;
    }

    /**
     * Return the databox field related
     *
     * @return \databox_field
     */
    public function getField()
    {
        return $this->databox_field;
    }

    /**
     * {@inheritdoc}
     *
     * @return array An array of scalar values
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function asString()
    {
        return serialize([
            'id'      => $this->databox_field->get_id(),
            'sbas_id' => $this->databox_field->get_databox()->get_sbas_id(),
            'value'   => $this->value
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @return MetaField
     */
    public static function loadFromString(Application $app, $string)
    {
        $data = @unserialize($string);

        if (!is_array($data) || !isset($data['sbas_id']) || !isset($data['id']) || !isset($data['value'])) {
            throw new \InvalidArgumentException('Unable to load metadata from string');
        }

        try {
            $field = $app->findDataboxById($data['sbas_id'])->get_meta_structure()->get_element($data['id']);

            return new static($field, $data['value']);
        } catch (\Exception $exception) {
            throw new \InvalidArgumentException('Field does not exist anymore', 0, $exception);
        }
    }
}
