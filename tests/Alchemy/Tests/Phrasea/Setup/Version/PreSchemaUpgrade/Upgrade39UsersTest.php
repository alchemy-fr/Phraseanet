<?php

namespace Alchemy\Tests\Phrasea\Setup\Version;

use Alchemy\Phrasea\Setup\Version\PreSchemaUpgrade\Upgrade39Users;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\DBAL\Types\Type;

class Upgrade39UsersTest extends \PhraseanetTestCase
{
    const DB_NAME = 'update39_test';

    /**
     * @dataProvider provideVariousFixtures
     */
    public function testApply($fixture)
    {
        $this->loadFixture($fixture);

        $em = $this->createEntityManager();
        $upgrader = new Upgrade39Users();
        $upgrader->apply($em, $this->createAppboxMock(), self::$DI['cli']['doctrine-migration.configuration']);

        $this->assertUsrTableIsSanitized($em);
        // check usr_ids are preserved
        $this->assertUsridsArePreserved($em);

        $this->checkThatNewConstraintsCanBeApplied($em);

        $this->assertLastAppliedModelAreOk($em);
        $this->assertTemplateAreOwnedByValidUser($em);

        $this->assertEquals('onner316269684', $this->loadUser($em, 30)->getLogin());
        $this->assertTrue($this->loadUser($em, 30)->isDeleted());
    }

    private function assertLastAppliedModelAreOk(EntityManager $em)
    {
        // check update
        $this->assertEquals(176, $this->loadUser($em, 188)->getLastModel()->getId());
        // last_model does not exist
        $this->assertNull($this->loadUser($em, 105)->getLastModel());
        // last_model is a deleted user
        $this->assertNull($this->loadUser($em, 36)->getLastModel());
        // no last_model
        $this->assertNull($this->loadUser($em, 4)->getLastModel());
    }

    private function assertTemplateAreOwnedByValidUser(EntityManager $em)
    {
        // check update
        $this->assertEquals(109, $this->loadUser($em, 160)->getModelOf()->getId());
        // owner does not exist
        $this->assertNull($this->loadUser($em, 12));
        // owner has been deleted
        $this->assertNull($this->loadUser($em, 31));
        // no owner
        $this->assertNull($this->loadUser($em, 11)->getModelOf());
    }

    /**
     * @return User
     */
    private function loadUser(EntityManager $em, $id)
    {
        return $em->find('Phraseanet:User', $id);
    }

    private function checkThatNewConstraintsCanBeApplied(EntityManager $em)
    {
        $tool = new SchemaTool($em);
        $metas = $em->getMetadataFactory()->getAllMetadata();
        $tool->updateSchema($metas, true);
    }

    private function assertUsrTableIsSanitized(EntityManager $em)
    {
        $rs = $em->createNativeQuery(
            "SHOW FIELDS FROM usr_backup WHERE Field = 'usr_login';",
            (new ResultSetMapping())->addScalarResult('Type', 'Type')
        )->getSingleResult();

        $this->assertSame('varchar(128)', strtolower($rs['Type']));
    }

    private function assertUsridsArePreserved(EntityManager $em)
    {
        $sql = 'SELECT usr_id FROM usr_backup o, Users n WHERE o.usr_login = n.login AND o.usr_id = n.id AND o.usr_login NOT LIKE "(#deleted_%" AND n.deleted = 0';
        $total = $em->getConnection()->executeQuery($sql)->rowCount();

        $sql = 'SELECT usr_id FROM usr_backup WHERE usr_login NOT LIKE "(#deleted_%"';
        $expected = $em->getConnection()->executeQuery($sql)->rowCount();

        $this->assertGreaterThan(0, $total);
        // 1 template has lost its owner
        $this->assertEquals($total, $expected - 2);
    }

    public function provideVariousFixtures()
    {
        return [
            ['tests/update39_fixtureFrom38.sql'],
            ['tests/update39_fixtureFrom31.sql'],
        ];
    }

    private function loadFixture($fixture)
    {
        $em = $this->createEntityManager(null);
        try {
            $em->getConnection()->executeQuery('DROP database '.self::DB_NAME);
        } catch (DBALException $e) {

        }

        $em->getConnection()->executeQuery('CREATE DATABASE '.self::DB_NAME.' CHARACTER SET utf8 COLLATE utf8_general_ci');
        $em = $this->createEntityManager();
        $em->getConnection()->executeQuery(file_get_contents(self::$DI['cli']['root.path'].'/'.$fixture));
    }

    private function createEntityManager($dbname = self::DB_NAME)
    {
        $params = self::$DI['cli']['conf']->get(['main', 'database']);
        $params['dbname'] = $dbname;
        self::$DI['cli']['EM.dbal-conf'] = $params;

        $em = EntityManager::create($params, self::$DI['cli']['EM.config'], self::$DI['cli']['EM.events-manager']);

        $platform = $em->getConnection()->getDatabasePlatform();

        $types = [
            'blob' => 'Alchemy\Phrasea\Model\Types\Blob',
            'enum' => 'Alchemy\Phrasea\Model\Types\Blob',
            'longblob' => 'Alchemy\Phrasea\Model\Types\LongBlob',
            'varbinary' => 'Alchemy\Phrasea\Model\Types\VarBinary',
            'binary' => 'Alchemy\Phrasea\Model\Types\Binary',
        ];

        foreach ($types as $type => $class) {
            if (!Type::hasType($type)) {
                Type::addType($type, $class);
            }
            $platform->registerDoctrineTypeMapping($type, $type);
        }

        return $em;
    }
}
