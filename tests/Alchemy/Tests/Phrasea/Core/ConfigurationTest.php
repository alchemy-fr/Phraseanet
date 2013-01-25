<?php

namespace Alchemy\Tests\Phrasea\Core;

use Alchemy\Phrasea\Core\Configuration;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::build
     */
    public function testBuild()
    {
        $specifications = $this->getSetupedSpecifications(array('dev' => array()));

        $configuration = Configuration::build($specifications, 'dev');

        $this->assertEquals('dev', $configuration->getEnvironnement());
        $this->assertEquals($specifications, $configuration->getSpecifications());
    }

    /**
     * @test
     * @covers Alchemy\Phrasea\Core\Configuration::build
     */
    public function buildShouldFailIfTheRequiredEnvironmentDoesNotExist()
    {
        $specifications = $this->getSetupedSpecifications(array('prod' => array()));

        try {
            Configuration::build($specifications, 'dev');
            $this->fail('Should have raised an exception');
        } catch (InvalidArgumentException $e) {

        }
    }

    /**
     * @test
     * @covers Alchemy\Phrasea\Core\Configuration::build
     */
    public function environmentShouldBeNullIsTheSpecsAreNotSetup()
    {
        $specifications = $this->getNotSetupedSpecifications();
        $configuration = Configuration::build($specifications, 'dev');

        $this->assertNull($configuration->getEnvironnement());
        $this->assertEquals($specifications, $configuration->getSpecifications());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::get
     */
    public function testGet()
    {
        $specifications = $this->getSetupedSpecifications(array('dev' => array('pif'          => 'pouf')));
        $configuration = Configuration::build($specifications, 'dev');

        $this->assertEquals('pouf', $configuration->get('pif'));
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::get
     */
    public function testGetOnNonExistentParameterShouldFail()
    {
        $specifications = $this->getSetupedSpecifications(array('dev' => array('pif'          => 'pouf')));
        $configuration = Configuration::build($specifications, 'dev');

        try {
            $configuration->get('paf');
            $this->fail('Should have raised an exception');
        } catch (ParameterNotFoundException $e) {

        }
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::has
     */
    public function testHas()
    {
        $specifications = $this->getSetupedSpecifications(array('dev' => array('pif'          => 'pouf')));
        $configuration = Configuration::build($specifications, 'dev');

        $this->assertTrue($configuration->has('pif'));
        $this->assertFalse($configuration->has('paf'));
    }

    /**
     * @test
     * @covers Alchemy\Phrasea\Core\Configuration::build
     */
    public function defaultEnvironmentShouldBeTheOneInTheEnvironmentKey()
    {
        $specifications = $this->getSetupedSpecifications(array('environment' => 'dave', 'dave'        => array('pif'          => 'pouf')));
        $configuration = Configuration::build($specifications);

        $this->assertEquals('dave', $configuration->getEnvironnement());
        $this->assertEquals($specifications, $configuration->getSpecifications());
    }

    /**
     * @test
     * @covers Alchemy\Phrasea\Core\Configuration::build
     */
    public function anErrorShouldBeThrownIfNoEnvironmentProvided()
    {
        $specifications = $this->getSetupedSpecifications(array('dave' => array('pif' => 'pouf')));

        try {
            Configuration::build($specifications);
            $this->fail('Should have raised an exception');
        } catch (RuntimeException $e) {

        }
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::setEnvironnement
     * @covers Alchemy\Phrasea\Core\Configuration::getEnvironnement
     */
    public function testSetEnvironnementShouldSetTheEnvironment()
    {
        $specifications = $this->getSetupedSpecifications(array('dev' => array('pif'  => 'pouf'), 'prod' => array('bim'          => 'bame')));
        $configuration = Configuration::build($specifications, 'dev');

        $this->assertEquals('dev', $configuration->getEnvironnement());
        $this->assertTrue($configuration->has('pif'));
        $this->assertFalse($configuration->has('bim'));

        $configuration->setEnvironnement('prod');
        $this->assertEquals('prod', $configuration->getEnvironnement());
        $this->assertFalse($configuration->has('pif'));
        $this->assertTrue($configuration->has('bim'));
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::setEnvironnement
     * @covers Alchemy\Phrasea\Core\Configuration::getEnvironnement
     */
    public function testSetEnvironnementShouldThrowAnExceptionIfEnvironmentDoesNotExists()
    {
        $specifications = $this->getSetupedSpecifications(array('dev' => array('pif'  => 'pouf'), 'prod' => array('bim'          => 'bame')));
        $configuration = Configuration::build($specifications, 'dev');

        try {
            $configuration->setEnvironnement('test');
            $this->fail('Should have raised an exception');
        } catch (InvalidArgumentException $e) {

        }
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getEnvironnement
     * @covers Alchemy\Phrasea\Core\Configuration::setEnvironnement
     */
    public function testSetEnvironnementWhenSetupNotReadyShouldAlwaysWork()
    {
        $specifications = $this->getNotSetupedSpecifications();
        $configuration = Configuration::build($specifications, 'dev');

        $configuration->setEnvironnement('prout');
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::isDebug
     */
    public function testIsDebugIsFalseWhileSetup()
    {
        $specifications = $this->getNotSetupedSpecifications();
        $configuration = Configuration::build($specifications);

        $this->assertFalse($configuration->isDebug());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::isDebug
     */
    public function testIsDebugIsFalseByDefault()
    {
        $specifications = $this->getSetupedSpecifications(array('dev' => array()));
        $configuration = Configuration::build($specifications, 'dev');

        $this->assertFalse($configuration->isDebug());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::isDebug
     */
    public function testIsDebug()
    {
        $specifications = $this->getSetupedSpecifications(array('dev' => array('phraseanet' => array('debug'        => true))));
        $configuration = Configuration::build($specifications, 'dev');

        $this->assertTrue($configuration->isDebug());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::isMaintained
     */
    public function testIsMaintainedIsFalseWhileSetup()
    {
        $specifications = $this->getNotSetupedSpecifications();
        $configuration = Configuration::build($specifications);

        $this->assertFalse($configuration->isMaintained());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::isMaintained
     */
    public function testIsMaintainedIsFalseByDefault()
    {
        $specifications = $this->getSetupedSpecifications(array('dev' => array()));
        $configuration = Configuration::build($specifications, 'dev');

        $this->assertFalse($configuration->isMaintained());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::isMaintained
     */
    public function testIsMaintained()
    {
        $specifications = $this->getSetupedSpecifications(array('dev' => array('phraseanet' => array('maintenance'  => true))));
        $configuration = Configuration::build($specifications, 'dev');

        $this->assertTrue($configuration->isMaintained());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::isDisplayingErrors
     */
    public function testIsDisplayingErrorsIsFalseWhileSetup()
    {
        $specifications = $this->getNotSetupedSpecifications();
        $configuration = Configuration::build($specifications);

        $this->assertFalse($configuration->isDisplayingErrors());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::isDisplayingErrors
     */
    public function testIsDisplayingErrorsIsFalseByDefault()
    {
        $specifications = $this->getSetupedSpecifications(array('dev' => array()));
        $configuration = Configuration::build($specifications, 'dev');

        $this->assertFalse($configuration->isDisplayingErrors());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::isDisplayingErrors
     */
    public function testIsDisplayingErrors()
    {
        $specifications = $this->getSetupedSpecifications(array('dev' => array('phraseanet' => array('display_errors' => true))));
        $configuration = Configuration::build($specifications, 'dev');

        $this->assertTrue($configuration->isDisplayingErrors());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getPhraseanet
     */
    public function testGetPhraseanet()
    {
        $phraseanet = array('display_errors' => true);

        $specifications = $this->getSetupedSpecifications(array('dev' => array('phraseanet'   => $phraseanet)));
        $configuration = Configuration::build($specifications, 'dev');

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\ParameterBag\ParameterBag', $configuration->getPhraseanet());
        $this->assertEquals($phraseanet, $configuration->getPhraseanet()->all());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::initialize
     */
    public function testInitialize()
    {
        $specifications = $this->getNotSetupedSpecifications();
        $specifications->expects($this->once())
            ->method('initialize');

        $configuration = Configuration::build($specifications);
        $configuration->initialize();

        $this->assertEquals('prod', $configuration->getEnvironnement());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::delete
     */
    public function testDelete()
    {
        $specifications = $this->getSetupedSpecifications(array('prod' => array()));
        $specifications->expects($this->once())
            ->method('delete');

        $configuration = Configuration::build($specifications, 'prod');
        $configuration->delete();
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::setConfigurations
     */
    public function testSetConfigurations()
    {
        $conf = array('prod' => array('bim' => 'boum'));

        $specifications = $this->getSetupedSpecifications(array('prod' => array()));
        $specifications->expects($this->once())
            ->method('setConfigurations')
            ->with($this->equalTo($conf));

        $configuration = Configuration::build($specifications, 'prod');
        $configuration->setConfigurations($conf);
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::setServices
     */
    public function testSetServices()
    {
        $services = array('Template' => array());

        $specifications = $this->getSetupedSpecifications(array('prod' => array()));
        $specifications->expects($this->once())
            ->method('setServices')
            ->with($this->equalTo($services));

        $configuration = Configuration::build($specifications, 'prod');
        $configuration->setServices($services);
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::resetServices
     */
    public function testResetAllServices()
    {
        $specifications = $this->getSetupedSpecifications(array('prod' => array()));
        $specifications->expects($this->once())
            ->method('resetServices')
            ->with($this->equalTo(null));

        $configuration = Configuration::build($specifications, 'prod');
        $configuration->resetServices();
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::resetServices
     */
    public function testResetByName()
    {
        $name = 'coool-service';

        $specifications = $this->getSetupedSpecifications(array('prod' => array()));
        $specifications->expects($this->once())
            ->method('resetServices')
            ->with($this->equalTo($name));

        $configuration = Configuration::build($specifications, 'prod');
        $configuration->resetServices($name);
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::setBinaries
     */
    public function testSetBinaries()
    {
        $binaries = array('binarie' => array('php' => '/usr/local/bin/php'));

        $specifications = $this->getSetupedSpecifications(array('prod' => array()));
        $specifications->expects($this->once())
            ->method('setBinaries')
            ->with($this->equalTo($binaries));

        $configuration = Configuration::build($specifications, 'prod');
        $configuration->setBinaries($binaries);
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::setConnexions
     */
    public function testSetConnexions()
    {
        $connexions = array('main' => array('path' => '/usr/local/db'));

        $specifications = $this->getSetupedSpecifications(array('prod' => array()));
        $specifications->expects($this->once())
            ->method('setConnexions')
            ->with($this->equalTo($connexions));

        $configuration = Configuration::build($specifications, 'prod');
        $configuration->setConnexions($connexions);
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getConfigurations
     */
    public function testGetConfigurations()
    {
        $specifications = $this->getNotSetupedSpecifications();
        $specifications->expects($this->once())
            ->method('getConfigurations');

        $configuration = Configuration::build($specifications, 'prod');
        $configuration->getConfigurations();
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getServices
     */
    public function testGetServices()
    {
        $specifications = $this->getSetupedSpecifications(array('prod' => array()));
        $specifications->expects($this->once())
            ->method('getServices');

        $configuration = Configuration::build($specifications, 'prod');
        $configuration->getServices();
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getBinaries
     */
    public function testGetBinaries()
    {
        $specifications = $this->getSetupedSpecifications(array('prod' => array()));
        $specifications->expects($this->once())
            ->method('getBinaries');

        $configuration = Configuration::build($specifications, 'prod');
        $configuration->getBinaries();
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getConnexions
     */
    public function testGetConnexions()
    {
        $specifications = $this->getSetupedSpecifications(array('prod' => array()));
        $specifications->expects($this->once())
            ->method('getConnexions');

        $configuration = Configuration::build($specifications, 'prod');
        $configuration->getConnexions();
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getConnexion
     */
    public function testGetConnexion()
    {
        $testConnexion = array('path' => '/tmp/db');

        $specifications = $this->getSetupedSpecifications(array('prod' => array()));
        $specifications->expects($this->once())
            ->method('getConnexions')
            ->will($this->returnValue(array('test' => $testConnexion)));

        $configuration = Configuration::build($specifications, 'prod');

        $conn = $configuration->getConnexion('test');

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\ParameterBag\ParameterBag', $conn);
        $this->assertEquals($testConnexion, $conn->all());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getConnexion
     */
    public function testGetConnexionThatDoesNotExist()
    {
        $testConnexion = array('path' => '/tmp/db');

        $specifications = $this->getSetupedSpecifications(array('prod' => array()));
        $specifications->expects($this->once())
            ->method('getConnexions')
            ->will($this->returnValue(array('test' => $testConnexion)));

        $configuration = Configuration::build($specifications, 'prod');

        try {
            $configuration->getConnexion('not-exists');
            $this->fail('Should have raised an exception');
        } catch (InvalidArgumentException $e) {

        }
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getTemplating
     */
    public function testGetTemplating()
    {
        $specifications = $this->getSetupedSpecifications(array(
            'prod' => array('template_engine' => 'ObjectTwig')
        ));

        $configuration = Configuration::build($specifications, 'prod');
        $this->assertEquals('TemplateEngine\\ObjectTwig', $configuration->getTemplating());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getCache
     */
    public function testGetCache()
    {
        $specifications = $this->getSetupedSpecifications(array(
            'prod' => array('cache' => 'ObjectCache')
        ));

        $configuration = Configuration::build($specifications, 'prod');
        $this->assertEquals('Cache\\ObjectCache', $configuration->getCache());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getOpcodeCache
     */
    public function testGetOpcodeCache()
    {
        $specifications = $this->getSetupedSpecifications(array(
            'prod' => array('opcodecache' => 'ObjectOpcodeCache')
        ));

        $configuration = Configuration::build($specifications, 'prod');
        $this->assertEquals('Cache\\ObjectOpcodeCache', $configuration->getOpcodeCache());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getOrm
     */
    public function testGetOrm()
    {
        $specifications = $this->getSetupedSpecifications(array(
            'prod' => array('orm' => 'ObjectORM')
        ));

        $configuration = Configuration::build($specifications, 'prod');
        $this->assertEquals('Orm\\ObjectORM', $configuration->getOrm());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getSearchEngine
     */
    public function testGetSearchEngine()
    {
        $specifications = $this->getSetupedSpecifications(array(
            'prod' => array('search-engine' => 'ObjectPhrasea')
        ));

        $configuration = Configuration::build($specifications, 'prod');
        $this->assertEquals('SearchEngine\\ObjectPhrasea', $configuration->getSearchEngine());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getBorder
     */
    public function testGetBorder()
    {
        $specifications = $this->getSetupedSpecifications(array(
            'prod' => array('border-manager' => 'ObjectBorder')
        ));

        $configuration = Configuration::build($specifications, 'prod');
        $this->assertEquals('Border\\ObjectBorder', $configuration->getBorder());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getTaskManager
     */
    public function testGetTaskManager()
    {
        $specifications = $this->getSetupedSpecifications(array(
            'prod' => array('task-manager' => 'ObjectTask')
        ));

        $configuration = Configuration::build($specifications, 'prod');
        $this->assertEquals('TaskManager\\ObjectTask', $configuration->getTaskManager());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getService
     * @dataProvider provideServices
     */
    public function testGetService($services, $name, $expected)
    {
        $specifications = $this->getSetupedSpecifications(array(
            'prod' => array('task-manager' => 'ObjectTask')
        ));

        $specifications->expects($this->once())
            ->method('getServices')
            ->will($this->returnValue($services));

        $configuration = Configuration::build($specifications, 'prod');
        $service = $configuration->getService($name);

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\ParameterBag\ParameterBag', $service);
        $this->assertEquals($expected, $service->all());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Configuration::getService
     * @dataProvider provideFailingServiceData
     */
    public function testGetServiceFail($services, $name)
    {
        $specifications = $this->getSetupedSpecifications(array(
            'prod' => array('task-manager' => 'ObjectTask')
        ));

        $specifications->expects($this->once())
            ->method('getServices')
            ->will($this->returnValue($services));

        $configuration = Configuration::build($specifications, 'prod');

        try {
            $configuration->getService($name);
            $this->fail('Should have raised an exception');
        } catch (InvalidArgumentException $e) {

        }
    }

    public function provideServices()
    {
        $services = array(
            'servicetld' => array(
                'sub' => array('data'),
                'anothersub' => array('datalevel1' => array(
                        'datalevel2' => array('lowleveldata')
                )),
            ),
            'anothertop' => array('pif' => 'paf')
        );

        return array(
            array($services, 'servicetld\\sub', array('data')),
            array($services, 'servicetld\\anothersub\\datalevel1', array('datalevel2' => array('lowleveldata'))),
            array($services, 'anothertop', array('pif' => 'paf')),
        );
    }

    public function provideFailingServiceData()
    {
        $services = array(
            'servicetld' => array(
                'sub' => array('data'),
                'anothersub' => array('datalevel1' => array(
                        'datalevel2' => array('lowleveldata')
                )),
            ),
            'anothertop' => array('pif' => 'paf')
        );

        return array(
            array($services, 'servicetld\\sub\\data'),
            array($services, 'servicetld\\data'),
            array($services, 'servicetld\\anothersub\\datalevel2'),
            array($services, 'anotherothertop'),
        );
    }

    private function getNotSetupedSpecifications()
    {
        $specifications = $this->getMock('Alchemy\Phrasea\Core\Configuration\SpecificationInterface');

        $specifications->expects($this->any())
            ->method('isSetup')
            ->will($this->returnValue(false));

        return $specifications;
    }

    private function getSetupedSpecifications($configuration = array())
    {
        $specifications = $this->getMock('Alchemy\Phrasea\Core\Configuration\SpecificationInterface');

        $specifications->expects($this->any())
            ->method('isSetup')
            ->will($this->returnValue(true));

        if ($configuration) {
            $specifications->expects($this->any())
                ->method('getConfigurations')
                ->will($this->returnValue($configuration));
        }

        return $specifications;
    }
}
