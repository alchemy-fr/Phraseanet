<?php

namespace Alchemy\Phrasea\SearchEngine;

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;

class SphinxSearchOptionsTest extends \PhraseanetPHPUnitAbstract
{
    public function testSerialize()
    {
        $options = new SearchEngineOptions(self::$DI['app']);
        $options->onCollections(array(self::$DI['collection']));

        $options->allowBusinessFieldsOn(array(self::$DI['collection']));

        foreach (self::$DI['collection']->get_databox()->get_meta_structure() as $field) {
            $options->setFields(array($field));
            $options->setDateFields(array($field));
            break;
        }

        $min_date = new \DateTime('-5 days');
        $max_date = new \DateTime('+5 days');

        $options->setMinDate(\DateTime::createFromFormat(DATE_ATOM, $min_date->format(DATE_ATOM)));
        $options->setMaxDate(\DateTime::createFromFormat(DATE_ATOM, $max_date->format(DATE_ATOM)));

        $serialized = $options->serialize();

        $this->assertEquals($options, SearchEngineOptions::hydrate(self::$DI['app'], $serialized));
    }
}
