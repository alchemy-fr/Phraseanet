<?php

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class databox_Field_DCES_DCESTestCase extends \PhraseanetTestCase
{
    /**
     * @var databox_Field_DCESAbstract
     */
    protected $object;

    public function testGet_label()
    {
        $data = explode('_', get_class($this));
        $name = str_replace('Test', '', array_pop($data));
        $this->assertEquals($name, $this->object->get_label());
    }

    public function testGet_definition()
    {
        $this->assertTrue(is_string($this->object->get_definition()));
        $this->assertTrue(strlen($this->object->get_definition()) > 18);
    }

    public function testGet_documentation_link()
    {
        $this->assertMatchesRegularExpression('/^http:\/\/dublincore\.org\/documents\/dces\/#[a-z]+$/', $this->object->get_documentation_link());
    }

    public function testSerialization()
    {
        $serializer = self::$DI['app']['serializer'];

        $data = json_decode($serializer->serialize($this->object, 'json'), true);

        $this->assertIsArray($data);
        $this->assertCount(3, $data);

        $this->assertArrayHasKey('label', $data);
        $this->assertArrayHasKey('definition', $data);
        $this->assertArrayHasKey('URI', $data);

        $this->assertIsString($data['label']);
        $this->assertIsString($data['definition']);
        $this->assertIsString($data['URI']);
    }
}
