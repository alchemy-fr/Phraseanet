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

use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Helper;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Navigator;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\TermVisitor;
use databox;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;

class TermIndexer
{
    const TYPE_NAME = 'term';

    /**
     * @var Navigator
     */
    private $navigator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->navigator = new Navigator();
        $this->logger = $logger;
    }

    /**
     * @param BulkOperation $bulk
     * @param databox $databox
     * @throws DBALException
     */
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
            $params = [
                'id'   => $id,
                'type' => self::TYPE_NAME,
                'body' => $term
            ];

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
}
