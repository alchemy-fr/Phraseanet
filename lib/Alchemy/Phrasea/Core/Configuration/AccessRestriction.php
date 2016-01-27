<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

use Assert\Assertion;
use Psr\Log\LoggerInterface;

class AccessRestriction
{
    /**
     * @var PropertyAccess
     */
    private $propertyAccess;

    /**
     * @var \appbox
     */
    private $appbox;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $loaded = false;

    /**
     * @var bool
     */
    private $restricted = false;

    /**
     * @var array
     */
    private $availableDataboxes = [];

    /**
     * @var array
     */
    private $availableCollections = [];

    public function __construct(PropertyAccess $propertyAccess, \appbox $appbox, LoggerInterface $logger)
    {
        $this->propertyAccess = $propertyAccess;
        $this->appbox = $appbox;
        $this->logger = $logger;
    }

    /**
     * Returns true if a configuration is set.
     *
     * @return bool
     */
    public function isRestricted()
    {
        $this->load();

        return $this->restricted;
    }

    /**
     * Returns true if a databox is available given a configuration.
     *
     * @param \databox $databox
     *
     * @return bool
     */
    public function isDataboxAvailable(\databox $databox)
    {
        if (!$this->isRestricted()) {
            return true;
        }

        return in_array($databox->get_sbas_id(), $this->availableDataboxes, true);
    }

    /**
     * @param \databox[] $databoxes
     * @return \databox[]
     */
    public function filterAvailableDataboxes(array $databoxes)
    {
        Assertion::allIsInstanceOf($databoxes, \databox::class);

        if (!$this->isRestricted()) {
            return $databoxes;
        }

        $available = array_flip($this->availableDataboxes);

        return array_filter($databoxes, function (\databox $databox) use ($available) {
            return isset($available[$databox->get_sbas_id()]);
        });
    }

    /**
     * Returns true if a collection is available given a configuration.
     *
     * @param \collection $collection
     *
     * @return bool
     */
    public function isCollectionAvailable(\collection $collection)
    {
        if (!$this->isRestricted()) {
            return true;
        }

        $availableCollections = isset($this->availableCollections[$collection->get_sbas_id()])
            ? $this->availableCollections[$collection->get_sbas_id()] : [];

        return in_array($collection->get_base_id(), $availableCollections, true);
    }

    private function load()
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        $allowedDataboxIds = array_map(function ($dbConf) {
            return $dbConf['id'];
        }, $this->propertyAccess->get('databoxes', []));

        if (count($allowedDataboxIds) === 0) {
            $this->restricted = false;

            return;
        }

        $this->restricted = true;

        $databoxIds = array_map(function (\databox $databox) { return $databox->get_sbas_id(); }, $this->appbox->get_databoxes());
        $errors = array_diff($allowedDataboxIds, $databoxIds);

        if (count($errors) > 0) {
            $this->logger->error(sprintf('Misconfiguration for allowed databoxes : ids %s do not exist', implode(', ', $errors)));
        }

        $this->availableDataboxes = array_intersect($allowedDataboxIds, $databoxIds);

        $this->loadCollections();
    }

    private function loadCollections()
    {
        foreach ($this->propertyAccess->get('databoxes') as $databox) {
            if (!in_array($databox['id'], $this->availableDataboxes, true)) {
                continue;
            }

            $collections = isset($databox['collections']) ? (is_array($databox['collections']) ? $databox['collections'] : [$databox['collections']]) : [];

            $availableBaseIds = array_map(function (\collection $collection) { return $collection->get_base_id(); }, $this->appbox->get_databox($databox['id'])->get_collections());
            $errors = array_diff($collections, $availableBaseIds);

            if (count($errors) > 0) {
                $this->logger->error(sprintf('Misconfiguration for allowed collections : ids %s do not exist', implode(', ', $errors)));
            }

            $this->availableCollections[$databox['id']] = array_intersect($collections, $availableBaseIds);
        }
    }
}
