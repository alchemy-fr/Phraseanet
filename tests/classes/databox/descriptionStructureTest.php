<?php

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class databox_descriptionStructureTest extends \PhraseanetTestCase
{
    public function testToArray()
    {
        $structure = new \databox_descriptionStructure([], new unicode());

        $array = ['name1' => 'value1', 'name2' => 'value2'];

        $element = $this->provideDataboxFieldMock();
        $element->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($array));

        $structure->add_element($element);

        $this->assertEquals([$array], $structure->toArray());
    }

    private function provideDataboxFieldMock()
    {
        return $this->createMock('databox_field');
    }
}
