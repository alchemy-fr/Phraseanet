<?php

namespace Alchemy\Tests\Phrasea\Border;

use Alchemy\Phrasea\Border\Attribute\MetaField;
use Alchemy\Phrasea\Border\MetaFieldsBag;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MetaFieldsBagTest extends \PhraseanetTestCase
{
    /**
     * @var MetaFieldsBag
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new MetaFieldsBag;
    }

    public function testToMetadataArray()
    {
        $structure = self::$DI['collection']->get_databox()->get_meta_structure();

        $monoAdded = $multiAdded = false;

        foreach ($structure as $databox_field) {
            if (!$monoAdded) {
                $this->object->set($databox_field->get_name(), new MetaField($databox_field, ['mono value']));
                $monoAdded = $databox_field->get_id();
            } elseif (!$multiAdded) {
                if ($databox_field->is_multi()) {
                    $this->object->set($databox_field->get_name(), new MetaField($databox_field, ['multi', 'value']));
                    $multiAdded = $databox_field->get_id();
                }
            } else {
                break;
            }
        }

        if (!$multiAdded || !$monoAdded) {
            $this->markTestSkipped('Unable to find multi value field');
        }

        $this->assertEquals([
            [
                'meta_struct_id' => $monoAdded,
                'value'          => 'mono value',
                'meta_id'        => null
            ],
            [
                'meta_struct_id' => $multiAdded,
                'value'          => 'multi',
                'meta_id'        => null
            ],
            [
                'meta_struct_id' => $multiAdded,
                'value'          => 'value',
                'meta_id'        => null
            ],
            ], $this->object->toMetadataArray($structure));
    }
}
