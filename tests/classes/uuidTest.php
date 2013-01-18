<?php

class uuidTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var uuid
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new uuid();
    }

    public function testGenerate_v3()
    {
        $datas = array();
        for ($i = 0; $i < 1000; $i ++ ) {
            $uuid = uuid::generate_v4();

            if ( ! uuid::is_valid($uuid))
                $this->fail('Generation d\'un uuid v4 invalide');

            $uuid = uuid::generate_v3($uuid, random::generatePassword(12));
            if ( ! uuid::is_valid($uuid))
                $this->fail('Generation d\'un uuid v5 invalide');

            $datas[] = $uuid;
        }

        $datas = array_unique($datas);

        if (count($datas) !== 1000)
            $this->fail('Generation de deux uuid identiques en v3' . count($datas));

        unset($datas);
    }

    public function testGenerate_v4()
    {
        $datas = array();
        for ($i = 0; $i < 1000; $i ++ ) {
            $uuid = uuid::generate_v4();

            if ( ! uuid::is_valid($uuid))
                $this->fail('Generation d\'un uuid v4 invalide');

            $datas[] = $uuid;
        }

        $datas = array_unique($datas);

        if (count($datas) !== 1000)
            $this->fail('Generation de deux uuid identiques en v4' . count($datas));

        unset($datas);
    }

    public function testGenerate_v5()
    {
        $datas = array();
        for ($i = 0; $i < 1000; $i ++ ) {
            $uuid = uuid::generate_v4();

            if ( ! uuid::is_valid($uuid))
                $this->fail('Generation d\'un uuid v4 invalide');

            $uuid = uuid::generate_v5($uuid, random::generatePassword(12));
            if ( ! uuid::is_valid($uuid))
                $this->fail('Generation d\'un uuid v5 invalide');

            $datas[] = $uuid;
        }

        $datas = array_unique($datas);

        if (count($datas) !== 1000)
            $this->fail('Generation de deux uuid identiques en v5' . count($datas));

        unset($datas);
    }

    public function testIs_valid()
    {
        for ($i = 0; $i < 1000; $i ++ ) {
            $uuid = uuid::generate_v4();
            if ( ! uuid::is_valid($uuid))
                $this->fail('Generation d\'un uuid v4 invalide');

            $uuid = uuid::generate_v5($uuid, random::generatePassword(12));
            if ( ! uuid::is_valid($uuid))
                $this->fail('Generation d\'un uuid v5 invalide');

            $uuid = uuid::generate_v3($uuid, random::generatePassword(12));
            if ( ! uuid::is_valid($uuid))
                $this->fail('Generation d\'un uuid v3 invalide');

            unset($uuid);
        }
    }

    public function testCompare()
    {
        for ($i = 0; $i < 1000; $i ++ ) {
            $uuid1 = uuid::generate_v4();
            $uuid2 = uuid::generate_v4();
            $this->assertFalse(uuid::compare($uuid1, $uuid2));
        }
    }

    public function testIs_null()
    {
        $this->assertTrue(uuid::is_null('00000000-0000-0000-0000-000000000000'));
    }
}
