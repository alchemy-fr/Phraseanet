<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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
     *
     * @var \databox_field
     */
    protected $databox_field;

    /**
     *
     * @var mixed
     */
    protected $value;

    /**
     * Constructor
     *
     * @param \databox_field $databox_field The databox field
     * @param string         $value         A scalar value
     *
     * @throws \InvalidArgumentException When value is not scalar
     */
    public function __construct(\databox_field $databox_field, $value)
    {
        if (!is_scalar($value)) {
            throw new \InvalidArgumentException('Databox field only accept scalar values');
        }
        $this->databox_field = $databox_field;
        $this->value = $value;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->metadata = $this->databox_field = null;
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
     * @return mixed A scalar value
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
        return serialize(array(
                'id'      => $this->databox_field->get_id(),
                'sbas_id' => $this->databox_field->get_databox()->get_sbas_id(),
                'value'   => $this->value
            ));
    }

    /**
     * {@inheritdoc}
     *
     * @return MetaField
     */
    public static function loadFromString(Application $app, $string)
    {
        if (!$datas = @unserialize($string)) {
            throw new \InvalidArgumentException('Unable to load metadata from string');
        }

        try {
            return new static($app['phraseanet.appbox']
                    ->get_databox($datas['sbas_id'])
                    ->get_meta_structure()->get_element($datas['id']), $datas['value']);
        } catch (\Exception_NotFound $e) {
            throw new \InvalidArgumentException('Field does not exist anymore');
        }
    }
}
