<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ControllerSubdefsTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    protected $databox;

    public function setUp()
    {
        parent::setUp();
        $databoxes = self::$DI['app']['phraseanet.appbox']->get_databoxes();
        $this->databox = array_shift($databoxes);
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
        self::$DI['client']->request("GET", "/admin/subdefs/" .  $this->databox->get_sbas_id() . "/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }


    public function testPostRouteAddSubdef()
    {
        $name = $this->getSubdefName();
        self::$DI['client']->request("POST", "/admin/subdefs/" .  $this->databox->get_sbas_id() . "/", array('add_subdef' => array(
                'class'  => 'thumbnail',
                'name'   => $name,
                'group'  => 'image'
            )));
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());

        $subdefs = new databox_subdefsStructure(new databox(self::$DI['app'], $this->databox->get_sbas_id()));
        $subdef = $subdefs->get_subdef("image", $name);
        $subdefs->delete_subdef('image', $name);
    }

    public function testPostRouteDeleteSubdef()
    {
        $subdefs =  $this->databox->get_subdef_structure();
        $name = $this->getSubdefName();
        $subdefs->add_subdef("image", $name, "thumbnail");
        self::$DI['client']->request("POST", "/admin/subdefs/" .  $this->databox->get_sbas_id() . "/", array('delete_subdef' => 'image_' . $name));
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        try {
            $subdefs->get_subdef("image", $name);
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }

    public function testPostRouteAddSubdefWithNoParams()
    {
        $subdefs =  $this->databox->get_subdef_structure();
        $name = $this->getSubdefName();
        $subdefs->add_subdef("image", $name, "thumbnail");
        self::$DI['client']->request("POST", "/admin/subdefs/" .  $this->databox->get_sbas_id() . "/"
            , array('subdefs' => array(
                'image_' . $name
            )
            , 'image_' . $name . '_class'        => 'thumbnail'
            , 'image_' . $name . '_downloadable' => 0
            , 'image_' . $name . '_mediatype'    => 'image'
            , 'image_' . $name . '_image'        => array(
                'size'       => 400,
                'resolution' => 83,
                'strip'      => 0,
                'quality'    => 90,
            ))
        );

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $subdefs = new databox_subdefsStructure(new databox(self::$DI['app'], $this->databox->get_sbas_id()));
        $subdef = $subdefs->get_subdef("image", $name);

        /* @var $subdef \databox_subdef */
        $this->assertFalse($subdef->is_downloadable());

        $options = $subdef->getOptions();

        $this->assertTrue(is_array($options));

        $this->assertEquals(400, $options[\Alchemy\Phrasea\Media\Subdef\Image::OPTION_SIZE]->getValue());
        $this->assertEquals(83, $options[\Alchemy\Phrasea\Media\Subdef\Image::OPTION_RESOLUTION]->getValue());
        $this->assertEquals(90, $options[\Alchemy\Phrasea\Media\Subdef\Image::OPTION_QUALITY]->getValue());
        $this->assertFalse($options[\Alchemy\Phrasea\Media\Subdef\Image::OPTION_STRIP]->getValue());

        $subdefs->delete_subdef("image", $name);
    }
}
