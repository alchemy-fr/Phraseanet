<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer;

use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Helper;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Navigator;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\TermVisitor;
use databox;
use Psr\Log\LoggerInterface;

class TermIndexer
{
    const TYPE_NAME = 'term';

    /**
     * @var \appbox
     */
    private $appbox;

    private $navigator;
    private $locales;
    private $logger;

    public function __construct(\appbox $appbox, array $locales, LoggerInterface $logger)
    {
        $this->appbox = $appbox;
        $this->navigator = new Navigator();
        $this->locales = $locales;
        $this->logger = $logger;
    }

    public function populateIndex(BulkOperation $bulk, databox $databox)
    {
        $databoxId = $databox->get_sbas_id();

        $visitor = new TermVisitor(function ($term) use ($bulk, $databoxId) {
            // Path and id are prefixed with a databox identifier to not
            // collide with other databoxes terms

            // Term structure
            $id = sprintf('%s_%s', $databoxId, $term['id']);
            unset($term['id']);
            $term['path'] = sprintf('/%s%s', $databoxId, $term['path']);

            $this->logger->debug(sprintf("Indexing term \"%s\"", $term['path']));

            $term['databox_id'] = $databoxId;

            // Index request
            $params = array();
            $params['id'] = $id;
            $params['type'] = self::TYPE_NAME;
            $params['body'] = $term;

            $bulk->index($params, null);
        });


        $indexDate = $databox->get_connection()->fetchColumn("SELECT updated_on FROM pref WHERE prop='thesaurus'");

        $document = Helper::thesaurusFromDatabox($databox);
        $this->navigator->walk($document, $visitor);

        $databox->get_connection()->executeUpdate(
            "INSERT INTO pref (prop, value, locale, updated_on, created_on)"
            . " VALUES ('thesaurus_index', '', '-', ?, NOW())"
            . " ON DUPLICATE KEY UPDATE updated_on=?",
            [$indexDate, $indexDate]
        );
    }

    public function getMapping()
    {
        $mapping = new Mapping();
        $mapping
            ->add('raw_value', 'string')->notAnalyzed()
            ->add('value', 'string')
                ->analyzer('general_light')
                ->addMultiField('strict', 'thesaurus_term_strict')
                ->addLocalizedSubfields($this->locales)
            ->add('context', 'string')
                ->analyzer('general_light')
                ->addMultiField('strict', 'thesaurus_term_strict')
                ->addLocalizedSubfields($this->locales)
            ->add('path', 'string')
                ->analyzer('thesaurus_path', 'indexing')
                ->analyzer('keyword', 'searching')
                ->addRawVersion()
            ->add('lang', 'string')->notAnalyzed()
            ->add('databox_id', 'integer')
        ;

        return $mapping->export();
    }
}
