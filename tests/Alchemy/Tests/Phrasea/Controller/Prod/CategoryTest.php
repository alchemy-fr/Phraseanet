<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;

class ControllerCategoryTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$DI['app'] = new Application('test');
    }

    public function testBasketDeletePost()
    {
        $this->XMLHTTPRequest('POST', '/prod/category/create', array(
            'title'    => 'titre test',
            'subtitle' => 'description test',
            'translation_title' => array('locale' => 'fr',
                                         'value' => 'titre test'),
            'translation_subtitle' => array('locale' => 'fr',
                                         'value' => 'description test')
        ));

        $response = self::$DI['client']->getResponse();
        var_dump($response);
    }

}
