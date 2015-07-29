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
            foreach ($data['CollsWait'] as $collectionId => $waiting) {
                $demands[] = $this->mapCollectionStatus($databoxId, $collectionId, "pending");
            }

            foreach ($data['CollsRefuse'] as $collectionId => $waiting) {
                $demands[] = $this->mapCollectionStatus($databoxId, $collectionId, "rejected");
            }

            foreach ($data['CollsRegistered'] as $collectionId => $waiting) {
                $demands[] = $this->mapCollectionStatus($databoxId, $collectionId, "accepted");
            }
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
