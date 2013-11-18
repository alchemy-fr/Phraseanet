<?php

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Core\Configuration\Compiler;

class CompilerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideDataToCompile
     */
    public function testCompile($data)
    {
        $compiler = new Compiler();

        $compiled = $compiler->compile($data);
        $this->assertInternalType("string", $compiled);
        $this->assertSame(0, strpos($compiled, "<?php\nreturn ["));
        $result = eval('?>'.$compiled);

        $this->assertSame($data, $result);
    }

    public function testCompileWithObject()
    {
        $compiler = new Compiler();

        $class = new \stdClass();
        $class->key = 'value';

        $data = [
            'key'  => $class,
            'key2' => 'boum',
        ];

        $compiled = $compiler->compile($data);
        $this->assertInternalType("string", $compiled);
        $this->assertSame(0, strpos($compiled, "<?php\nreturn ["));
        $result = eval('?>'.$compiled);

        $this->assertSame(['key' => ['key' => 'value'], 'key2' => 'boum'], $result);
    }

    public function provideDataToCompile()
    {
        return [
            [[]],
            [['key' => ['value1', 'value2', 'booleantrue' => true, 'booleanfalse' => false], ['gizmo']]],
            [[[[]]]],
            [[null, false, [], true]],
            [['key' => 'value', "associativeint" => 12345, 34567]],
        ];
    }
}
