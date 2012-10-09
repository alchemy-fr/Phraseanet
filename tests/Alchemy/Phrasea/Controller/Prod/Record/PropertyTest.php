<?php

require_once __DIR__ . '/../../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Controller\Prod\Record\Property;
use Symfony\Component\HttpFoundation\Request;

class PropertyTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Record\Property::displayProperty
     */
    public function testDisplayProperty()
    {
        $property = new Property();
        $request = Request::create('/prod/records/property/', 'GET', array(), array(), array() ,array('HTTP_X-Requested-With' => 'XMLHttpRequest'));
        $response = $property->displayProperty(self::$DI['app'], $request);
        $this->assertTrue($response->isOk());
    }
}
