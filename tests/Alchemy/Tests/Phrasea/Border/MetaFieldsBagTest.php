<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Border;

use Alchemy\Phrasea\Border\Attribute\MetaField;
use Alchemy\Phrasea\Border\MetaFieldsBag;

class MetaFieldsBagTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var MetaFieldsBag
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new MetaFieldsBag;
    }

    /**
     * @covers Alchemy\Phrasea\Border\MetadataBag::toMetadataArray
     */
    public function testToMetadataArray()
    {
        $structure = self::$DI['collection']->get_databox()->get_meta_structure();

        $monoAdded = $multiAdded = false;

        foreach ($structure as $databox_field) {
            if (!$monoAdded) {
                $this->object->set($databox_field->get_name(), new MetaField($databox_field, array('mono value')));
                $monoAdded = $databox_field->get_id();
            } elseif (!$multiAdded) {
                if ($databox_field->is_multi()) {
                    $this->object->set($databox_field->get_name(), new MetaField($databox_field, array('multi', 'value')));
                    $multiAdded = $databox_field->get_id();
                }
            } else {
                break;
            }
        }

        if (!$multiAdded || !$monoAdded) {
            $this->markTestSkipped('Unable to find multi value field');
        }

        $this->assertEquals(array(
            array(
                'meta_struct_id' => $monoAdded,
                'value'          => 'mono value',
                'meta_id'        => null
            ),
            array(
                'meta_struct_id' => $multiAdded,
                'value'          => 'multi',
                'meta_id'        => null
            ),
            array(
                'meta_struct_id' => $multiAdded,
                'value'          => 'value',
                'meta_id'        => null
            ),
            ), $this->object->toMetadataArray($structure));
    }
}
