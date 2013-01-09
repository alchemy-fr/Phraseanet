<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border;

use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Value\Mono;
use PHPExiftool\Driver\Value\Multi;

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

class MetadataBagTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var MetadataBag
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new MetadataBag;
    }

    /**
     * @covers Alchemy\Phrasea\Border\MetadataBag::toMetadataArray
     */
    public function testToMetadataArray()
    {
        $structure = self::$DI['collection']->get_databox()->get_meta_structure();

        $valueMono = new Mono('mono value');
        $valueMulti = new Multi(array('multi', 'value'));

        $monoAdded = $multiAdded = false;

        foreach ($structure as $databox_field) {
            if (!$monoAdded) {
                $this->object->set($databox_field->get_tag()->getTagname(), new Metadata($databox_field->get_tag(), $valueMono));
                $monoAdded = $databox_field->get_id();
            } elseif (!$multiAdded) {
                if ($databox_field->is_multi()) {
                    $this->object->set($databox_field->get_tag()->getTagname(), new Metadata($databox_field->get_tag(), $valueMulti));
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
