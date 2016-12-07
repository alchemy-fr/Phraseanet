<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Alchemy\Phrasea\Utilities\NullableDateTime;
use Assert\Assertion;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Guzzle\Http\Url;

class media_Permalink_Adapter implements cache_cacheableInterface
{
    /** @var databox */
    protected $databox;
    /** @var media_subdef */
    protected $media_subdef;
    /** @var int */
    protected $id;
    /** @var string */
    protected $token;
    /** @var boolean */
    protected $is_activated;
    /** @var DateTime */
    protected $created_on;
    /** @var DateTime */
    protected $last_modified;
    /** @var string */
    protected $label;
    /** @var Application */
    protected $app;

    private static $bad_chars = [
        "\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07",
        "\x08", "\x09", "\x0A", "\x0B", "\x0C", "\x0D", "\x0E", "\x0F",
        " ",    "/",    "\\",   "%",    "+"
    ];

    /**
     * @param Application $app
     * @param databox $databox
     * @param media_subdef $media_subdef
     * @param array $data
     */
    public function __construct(Application $app, databox $databox, media_subdef $media_subdef, array $data = null)
    {
        $this->app = $app;
        $this->databox = $databox;
        $this->media_subdef = $media_subdef;

        if (null === $data) {
            $this->load();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function get_token()
    {
        return $this->token;
    }

    /**
     * @return bool
     */
    public function get_is_activated()
    {
        return $this->is_activated;
    }

    /**
     * @return DateTime
     */
    public function get_created_on()
    {
        return $this->created_on;
    }

    /**
     * @return DateTime
     */
    public function get_last_modified()
    {
        return $this->last_modified;
    }

    /**
     * @return string
     */
    public function get_label()
    {
        return $this->label;
    }

    /**
     * @return Url
     */
    public function get_url()
    {
        $label = $this->get_label() . '.' . pathinfo($this->media_subdef->get_file(), PATHINFO_EXTENSION);

        return Url::factory($this->app->url('permalinks_permalink', [
            'sbas_id' => $this->media_subdef->get_sbas_id(),
            'record_id' => $this->media_subdef->get_record_id(),
            'subdef' => $this->media_subdef->get_name(),
            /** @Ignore */
            'label' => str_replace(self::$bad_chars, '_', $label),
            'token' => $this->get_token(),
        ]));
    }

    /**
     * @return string
     */
    public function get_page()
    {
        return $this->app->url('permalinks_permaview', [
            'sbas_id' => $this->media_subdef->get_sbas_id(),
            'record_id' => $this->media_subdef->get_record_id(),
            'subdef' => $this->media_subdef->get_name(),
            'token' => $this->get_token(),
        ]);
    }

    /**
     * @param  string $token
     * @return $this
     */
    protected function set_token($token)
    {
        $this->token = $token;

        $sql = 'UPDATE permalinks SET token = :token, last_modified = NOW()
            WHERE id = :id';
        $stmt = $this->databox->get_connection()->prepare($sql);
        $stmt->execute([':token' => $this->token, ':id' => $this->get_id()]);
        $stmt->closeCursor();

        $this->delete_data_from_cache();

        return $this;
    }

    /**
     * @param bool $is_activated
     * @return $this
     */
    public function set_is_activated($is_activated)
    {
        $this->is_activated = (bool)$is_activated;

        $this->databox->get_connection()->executeUpdate(
            'UPDATE permalinks SET activated = :activated, last_modified = NOW() WHERE id = :id',
            ['activated' => $this->is_activated, 'id' => $this->get_id()]
        );

        $this->delete_data_from_cache();

        return $this;
    }

    /**
     * @param  string $label
     * @return $this
     */
    public function set_label($label)
    {
        $label = trim($label) ? trim($label) : 'untitled';

        while (strpos($label, '  ') !== false) {
            $label = str_replace('  ', ' ', $label);
        }

        $this->label = $this->app['unicode']->remove_nonazAZ09(
            str_replace(' ', '-', $label)
        );

        $this->databox->get_connection()->executeUpdate(
            'UPDATE permalinks SET label = :label, last_modified = NOW() WHERE id = :id',
            ['label' => $this->label, 'id' => $this->get_id()]
        );

        $this->delete_data_from_cache();

        return $this;
    }

    protected function load()
    {
        try {
            $data = $this->get_data_from_cache();
        } catch (\Exception $e) {
            $data = false;
        }

        if (is_array($data)) {
            $this->loadFromData($data);

            return;
        }

        $data = $this->databox->get_connection()->fetchAssoc(
            self::getSelectSql(),
            [':subdef_id' => $this->media_subdef->get_subdef_id()]
        );

        if (!$data) {
            throw new Exception_Media_SubdefNotFound();
        }

        $this->loadFromData($data);

        $this->set_data_to_cache($this->toArray());
    }

    private function loadFromData(array $data)
    {
        $this->id = (int)$data['id'];
        $this->token = $data['token'];
        $this->is_activated = (bool)$data['is_activated'];
        $this->created_on = new DateTime($data['created_on']);
        $this->last_modified = new DateTime($data['last_modified']);
        $this->label = $data['label'];
    }

    private function toArray()
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'is_activated' => $this->is_activated,
            'created_on' => NullableDateTime::format($this->created_on),
            'last_modified' => NullableDateTime::format($this->last_modified),
            'label' => $this->label,

        ];
    }

    /**
     * @param  Application $app
     * @param  databox $databox
     * @param  media_subdef $media_subdef
     * @return $this
     */
    public static function getPermalink(Application $app, databox $databox, media_subdef $media_subdef)
    {
        try {
            return new self($app, $databox, $media_subdef);
        } catch (\Exception $e) {
            // Could not load, try to create
        }

        return self::create($app, $databox, $media_subdef);
    }

    /**
     * @param Application $app
     * @param media_subdef[] $subdefs
     * @return media_Permalink_Adapter[]
     */
    public static function getMany(Application $app, $subdefs)
    {
        Assertion::allIsInstanceOf($subdefs, media_subdef::class);

        $permalinks = [];
        $subdefPerDatabox = [];

        foreach ($subdefs as $index => $subdef) {
            if (!isset($subdefPerDatabox[$subdef->get_sbas_id()])) {
                $subdefPerDatabox[$subdef->get_sbas_id()] = [];
            }
            $subdefPerDatabox[$subdef->get_sbas_id()][$index] = $subdef;

            $permalinks[$index] = null;
        }

        foreach ($subdefPerDatabox as $databoxId => $media_subdefs) {
            $databox = $app->findDataboxById($databoxId);

            $subdefIds = array_map(function (media_subdef $media_subdef) {
                return $media_subdef->get_subdef_id();
            }, $media_subdefs);

            $data = self::fetchData($databox, $subdefIds);

            $missing = array_diff_key($media_subdefs, $data);

            if ($missing) {
                self::createMany($app, $databox, $missing);
                $data = array_replace($data, self::fetchData($databox, array_diff_key($subdefIds, $data)));
            }

            foreach ($media_subdefs as $index => $subdef) {
                if (!isset($data[$index])) {
                    throw new \RuntimeException('Could not fetch some data. Should never happen');
                }

                $permalinks[$index] = new self($app, $databox, $subdef, $data[$index]);
            }
        }

        return $permalinks;
    }

    /**
     * Returns present data in storage with same indexes but different order
     *
     * @param databox $databox
     * @param int[] $subdefIds
     * @return array
     */
    private static function fetchData(databox $databox, array $subdefIds)
    {
        $found = [];
        $missing = [];

        foreach ($subdefIds as $index => $subdefId) {
            try {
                $data = self::doGetDataFromCache($databox, $subdefId);
            } catch (Exception $exception) {
                $data = false;
            }

            if (is_array($data)) {
                $found[$index] = $data;

                continue;
            }

            $missing[$index] = $subdefId;
        }

        if (!$missing) {
            return $found;
        }

        $dbalData = $databox->get_connection()->fetchAll(
            self::getSelectSql(),
            ['subdef_id' => array_values($missing)],
            ['subdef_id' => Connection::PARAM_INT_ARRAY]
        );

        foreach ($dbalData as $item) {
            $itemSubdefId = $item['subdef_id'];

            $databox->set_data_to_cache($item, self::generateCacheKey($itemSubdefId));

            $foundIndexes = array_keys(array_intersect($missing, [$itemSubdefId]));

            foreach ($foundIndexes as $foundIndex) {
                $found[$foundIndex] = $item;
                unset($missing[$foundIndex]);
            }
        }

        return $found;
    }

    /**
     * @param Application $app
     * @param databox $databox
     * @param media_subdef[] $subdefs
     * @throws DBALException
     * @throws \InvalidArgumentException
     */
    public static function createMany(Application $app, databox $databox, $subdefs)
    {
        $databoxId = $databox->get_sbas_id();
        $recordIds = [];

        foreach ($subdefs as $media_subdef) {
            if ($media_subdef->get_sbas_id() !== $databoxId) {
                throw new InvalidArgumentException(sprintf(
                    'All subdefs should be from databox %d, got %d',
                    $databoxId,
                    $media_subdef->get_sbas_id()
                ));
            }

            $recordIds[] = $media_subdef->get_record_id();
        }

        $databoxRecords = $databox->getRecordRepository()->findByRecordIds($recordIds);

        /** @var record_adapter[] $records */
        $records = array_combine(
            array_map(function (record_adapter $record) {
                return $record->getRecordId();
            }, $databoxRecords),
            $databoxRecords
        );

        if (count(array_unique($recordIds)) !== count($records)) {
            throw new \RuntimeException('Some records are missing');
        }

        $generator = $app['random.medium'];

        $data = [];

        foreach ($subdefs as $media_subdef) {
            $data[] = [
                'subdef_id' => $media_subdef->get_subdef_id(),
                'token' => $generator->generateString(64, TokenManipulator::LETTERS_AND_NUMBERS),
                'label' => $records[$media_subdef->get_record_id()]->get_title(['removeExtension' => true]),
            ];
        }

        try {
            $databox->get_connection()->transactional(function (Connection $connection) use ($data) {
                $sql = "INSERT INTO permalinks (subdef_id, token, activated, created_on, last_modified, label)\n"
                     . " VALUES (:subdef_id, :token, 1, NOW(), NOW(), :label)";

                $statement = $connection->prepare($sql);

                foreach ($data as $params) {
                    $statement->execute($params);
                }
            });
        } catch (Exception $e) {
            throw new RuntimeException('Permalink already exists', $e->getCode(), $e);
        }
    }

    /**
     * @param  Application $app
     * @param  databox $databox
     * @param  media_subdef $media_subdef
     * @return $this
     */
    public static function create(Application $app, databox $databox, media_subdef $media_subdef)
    {
        self::createMany($app, $databox, [$media_subdef]);

        return self::getPermalink($app, $databox, $media_subdef);
    }

    private static function generateCacheKey($id, $option = null)
    {
        return 'permalink_' . $id . ($option ? '_' . $option : '');
    }

    public function get_cache_key($option = null)
    {
        return self::generateCacheKey($this->media_subdef->get_subdef_id(), $option);
    }

    /**
     * @param databox $databox
     * @param int $subdefId
     * @param null $option
     * @return string
     */
    private static function doGetDataFromCache(databox $databox, $subdefId, $option = null)
    {
        return $databox->get_data_from_cache(self::generateCacheKey($subdefId, $option));
    }

    public function get_data_from_cache($option = null)
    {
        return self::doGetDataFromCache($this->databox, $this->media_subdef->get_subdef_id(), $option);
    }

    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        return $this->databox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    public function delete_data_from_cache($option = null)
    {
        $this->databox->delete_data_from_cache($this->get_cache_key($option));
    }

    /**
     * @return string
     */
    protected static function getSelectSql()
    {
        return <<<'SQL'
SELECT p.id, p.subdef_id, p.token, p.activated AS is_activated, p.created_on, p.last_modified, p.label
FROM permalinks p
WHERE p.subdef_id IN (:subdef_id)
SQL;
    }
}
