<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Media\Subdef\Image;
/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class SubdefsTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;
    protected $databox_id;
    public function setUp()
    {
        parent::setUp();
        $databoxes = $this->getApplication()->getDataboxes();
        // Can not keep databox instance as appbox is cleared
        $databox = array_shift($databoxes);
        $this->databox_id = $databox->get_sbas_id();
    }
    public function getSubdefName()
    {
        return 'testname' . time() . mt_rand(10000, 99999);
    }
    /**
     * Default route test
     */
    public function testRouteGetSubdef()
    {
        self::$DI['client']->request("GET", "/admin/subdefs/" .  $this->databox_id . "/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }
    public function testPostRouteAddSubdef()
    {
        $name = $this->getSubdefName();
        self::$DI['client']->request("POST", "/admin/subdefs/" .  $this->databox_id . "/", ['add_subdef' => [
            'class' => 'thumbnail',
            'name'  => $name,
            'group' => 'image'
        ]]);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $app = $this->getApplication();
        $subdefs = new \databox_subdefsStructure($app->findDataboxById($this->databox_id), $app['translator']);
        $subdefs->delete_subdef('image', $name);
    }
    public function testPostRouteDeleteSubdef()
    {
        $subdefs =  $this->getApplication()->findDataboxById($this->databox_id)->get_subdef_structure();
        $name = $this->getSubdefName();
        $path = $this->getApplication()->findDataboxById($this->databox_id)->getSubdefStorage();

        $subdefs->add_subdef("image", $name, "thumbnail", "image", "1280px JPG (preview Phraseanet)", $path);
        self::$DI['client']->request("POST", "/admin/subdefs/" .  $this->databox_id . "/", ['delete_subdef' => 'image_' . $name]);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        try {
            $subdefs->get_subdef("image", $name);
            $this->fail("should raise an exception");
        } catch (\Exception $e) {
        }
    }
    public function testPostRouteAddSubdefWithNoParams()
    {
        $subdefs =  $this->getApplication()->findDataboxById($this->databox_id)->get_subdef_structure();
        $name = $this->getSubdefName();
        $path = $this->getApplication()->findDataboxById($this->databox_id)->getSubdefStorage();

        $subdefs->add_subdef("image", $name, "thumbnail", "image", "1280px JPG (preview Phraseanet)", $path);
        self::$DI['client']->request("POST", "/admin/subdefs/" .  $this->databox_id . "/"
            , ['subdefs'                             => [
                'image_' . $name
            ]
                , 'image_' . $name . '_class'        => 'thumbnail'
                , 'image_' . $name . '_downloadable' => 0
                , 'image_' . $name . '_mediatype'    => 'image'
                , 'image_' . $name . '_image'        => [
                    'size'       => 400,
                    'resolution' => 83,
                    'strip'      => 0,
                    'quality'    => 90,
                ]]
        );
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $app = $this->getApplication();
        $subdefs = new \databox_subdefsStructure($app->findDataboxById($this->databox_id), $app['translator']);
        $subdef = $subdefs->get_subdef("image", $name);
        /* @var $subdef \databox_subdef */
        $this->assertFalse($subdef->isDownloadable());
        $options = $subdef->getOptions();
        $this->assertTrue(is_array($options));
        $this->assertEquals(400, $options[Image::OPTION_SIZE]->getValue());
        $this->assertEquals(83, $options[Image::OPTION_RESOLUTION]->getValue());
        $this->assertEquals(90, $options[Image::OPTION_QUALITY]->getValue());
        $this->assertFalse($options[Image::OPTION_STRIP]->getValue());
        $subdefs->delete_subdef("image", $name);
    }
}