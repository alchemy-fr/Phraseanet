<?php

namespace Alchemy\Phrasea\WorkerManager\Model;

use Alchemy\Phrasea\WorkerManager\Configuration\Config;

class DBManipulator
{
    /**
     * @param array $params = [host, port, indexName, databoxId]
     */
    public static function savePopulateStatus(array $params)
    {
        $pdo = Config::getWorkerSqliteConnection();

        $pdo->beginTransaction();

        try {
            $pdo->query("CREATE TABLE IF NOT EXISTS populate_running(host TEXT NOT NULL, port TEXT NOT NULL, index_name TEXT NOT NULL, databox_id TEXT NOT NULL);");

            $stmt = $pdo->prepare("INSERT INTO populate_running(host, port, index_name, databox_id) VALUES(:host, :port, :index_name, :databox_id)");

            $stmt->execute([
                ':host'       => $params['host'],
                ':port'       => $params['port'],
                ':index_name' => $params['indexName'],
                ':databox_id' => $params['databoxId']
            ]);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
        }
    }

    /**
     * @param array $params = [host, port, indexName, databoxId]
     */
    public static function deletePopulateStatus(array $params)
    {
        $pdo = Config::getWorkerSqliteConnection();

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("DELETE FROM populate_running WHERE host = :host AND port= :port AND index_name= :index_name AND databox_id= :databox_id");

            $stmt->execute([
                ':host'       => $params['host'],
                ':port'       => $params['port'],
                ':index_name' => $params['indexName'],
                ':databox_id' => $params['databoxId']
            ]);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
        }
    }

    /**
     *  Update commits table in the temporary sqlite worker.db
     * @param $commitId
     * @param $assetId
     * @return int  the number of the remaining assets in the commit
     */
    public static function updateRemainingAssetsListByCommit($commitId, $assetId)
    {
        $row = 1;
        $pdo = Config::getWorkerSqliteConnection();
        $pdo->beginTransaction();

        try {
            // remove assetId from the assets list
            $stmt = $pdo->prepare("DELETE FROM commits WHERE commit_id = :commit_id AND asset= :assetId");
            $stmt->execute([
                ':commit_id' => $commitId,
                ':assetId'   => $assetId
            ]);

            $stmt = $pdo->prepare("SELECT * FROM commits WHERE commit_id = :commit_id");
            $stmt->execute([
                ':commit_id' => $commitId,
            ]);

            $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $pdo->commit();

        } catch (\Exception $e) {
            $pdo->rollBack();
        }

        return count($row);
    }

    /**
     * @param $commitId
     * @return bool
     */
    public static function isCommitToBeCreating($commitId)
    {
        $pdo = Config::getWorkerSqliteConnection();

        $pdo->beginTransaction();
        $row = 0;
        try {
            $pdo->query("CREATE TABLE IF NOT EXISTS commits(commit_id TEXT NOT NULL, asset TEXT NOT NULL);");

            $stmt = $pdo->prepare("SELECT * FROM commits WHERE commit_id = :commit_id");
            $stmt->execute([
                ':commit_id' => $commitId,
            ]);

            $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $pdo->commit();
        } catch (\Exception $e) {
            //no-op
        }

        return count($row) ? true : false;
    }

    public static function saveAssetsList($commitId, $assetIds)
    {
        $pdo = Config::getWorkerSqliteConnection();

        $pdo->beginTransaction();

        try {
            $pdo->query("CREATE TABLE IF NOT EXISTS commits(commit_id TEXT NOT NULL, asset TEXT NOT NULL);");

            // insert all assets ID in the temporary sqlite database
            foreach ($assetIds as $assetId) {
                $stmt = $pdo->prepare("INSERT INTO commits(commit_id, asset) VALUES(:commit_id, :asset)");

                $stmt->execute([
                    ':commit_id' => $commitId,
                    ':asset'     => $assetId
                ]);
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
        }
    }

    /**
     * @param array $databoxIds
     * @return int
     */
    public static function checkPopulateIndexStatusByDataboxId(array $databoxIds)
    {
        $pdo = Config::getWorkerSqliteConnection();
        $in = str_repeat("?,", count($databoxIds)-1) . "?";

        $pdo->beginTransaction();

        try {
            $pdo->query("CREATE TABLE IF NOT EXISTS populate_running(host TEXT NOT NULL, port TEXT NOT NULL, index_name TEXT NOT NULL, databox_id TEXT NOT NULL);");

            $stmt = $pdo->prepare("SELECT * FROM populate_running WHERE databox_id IN ($in)");

            $stmt->execute($databoxIds);

            $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $pdo->commit();

        } catch (\Exception $e) {
            $pdo->rollBack();
        }

        return count($row);
    }
}
