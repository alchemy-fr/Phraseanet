<?php

namespace Alchemy\Phrasea\SearchEngine;

require_once __DIR__ . '/../../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

abstract class ConfigurationPanelAbstractTest extends \PhraseanetPHPUnitAuthenticatedAbstract
{

    abstract public function getPanel();

    public function testGetName()
    {
        $this->assertInternalType('string', $this->getPanel()->getName());
    }

    public function testGetConfiguration()
    {
        $this->assertInternalType('array', $this->getPanel()->getConfiguration());
    }

    public function testSaveConfiguration()
    {
        $config = $this->getPanel()->getConfiguration();
        $data = 'Yodelali' . mt_rand();
        $config['test'] = $data;
        $this->getPanel()->saveConfiguration($config);

        $config = $this->getPanel()->getConfiguration();
        $this->assertEquals($data, $config['test']);
    }

    public function testGetAvailableDateFields()
    {
        $dateFields = $this->getPanel()->getAvailableDateFields(self::$DI['app']['phraseanet.appbox']->get_databoxes());
        $this->assertInternalType('array', $dateFields);

        foreach ($dateFields as $dateField) {
            $this->assertInternalType('string', $dateField);
        }
    }

}
