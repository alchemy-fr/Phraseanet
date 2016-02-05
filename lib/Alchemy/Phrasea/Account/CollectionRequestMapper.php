<?php

namespace Alchemy\Phrasea\Account;

use Alchemy\Phrasea\Application;

class CollectionRequestMapper
{
    /**
     * @var Application
     */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;

        require_once $this->app['root.path'] . '/lib/classes/deprecated/inscript.api.php';
    }

    public function getUserRequests(\User_Adapter $user)
    {
        $databoxStatuses = giveMeBases($this->app, $user->get_id());

        $demands = array();

        foreach ($databoxStatuses as $databoxId => $data) {
            $demands = array_merge($demands, $this->mapCollectionsByStatus($databoxId, $data, "CollsWait", "pending"));
            $demands = array_merge($demands, $this->mapCollectionsByStatus($databoxId, $data, "CollsRefuse", "rejected"));
            $demands = array_merge($demands, $this->mapCollectionsByStatus($databoxId, $data, "CollsRegistered", "accepted"));
        }

        return $demands;
    }

    private function mapCollectionsByStatus($databoxId, $data, $dataKey, $statusName)
    {
        if (! is_array($data[$dataKey])) {
            return array();
        }

        $demands = array();

        foreach ($data[$dataKey] as $collectionId => $collectionData) {
            $demands[] = $this->mapCollectionStatus($databoxId, $collectionId, $statusName);
        }

        return $demands;
    }

    private function mapCollectionStatus($databoxId, $collectionId, $status)
    {
        $baseId = \phrasea::baseFromColl($databoxId, $collectionId, $this->app);

        return array(
            "databox_id" => $databoxId,
            "base_id" => $baseId,
            "collection_id" => $collectionId,
            "status" => $status
        );
    }
}
