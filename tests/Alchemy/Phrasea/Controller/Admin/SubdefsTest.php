<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ControllerSubdefsTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;
    protected $app;
    protected $databox;

    public function createApplication()
    {
        $this->app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Admin.php';

        $this->app['debug'] = true;
        unset($this->app['exception_handler']);

        return $this->app;
    }

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
        $this->databox = array_shift($this->app['phraseanet.appbox']->get_databoxes());
    }

    /**
     * Default route test
     */
    public function testRouteGetSubdef()
    {
        $this->client->request("GET", "/subdefs/" .  $this->databox->get_sbas_id() . "/");
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function getSubdefName()
    {
        return 'testname' . time() . mt_rand(10000, 99999);
    }

    public function testPostRouteAddSubdef()
    {
        $name = $this->getSubdefName();
        $this->client->request("POST", "/subdefs/" .  $this->databox->get_sbas_id() . "/", array('add_subdef' => array(
                'class'  => 'thumbnail',
                'name'   => $name,
                'group'  => 'image'
            )));
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $subdefs =  $this->databox->get_subdef_structure();
        $subdefs->get_subdef("image", $name);
        $subdefs->delete_subdef('image', $name);
    }

    public function testPostRouteDeleteSubdef()
    {
        $subdefs =  $this->databox->get_subdef_structure();
        $name = $this->getSubdefName();
        $subdefs->add_subdef("image", $name, "thumbnail");
        $this->client->request("POST", "/subdefs/" .  $this->databox->get_sbas_id() . "/", array('delete_subdef' => 'image_' . $name));
        $this->assertTrue($this->client->getResponse()->isRedirect());
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
        $this->client->request("POST", "/subdefs/" .  $this->databox->get_sbas_id() . "/"
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

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $subdefs = new databox_subdefsStructure( $this->databox);
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
