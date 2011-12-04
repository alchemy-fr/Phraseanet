<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     task_manager
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class task_period_batchupload extends task_appboxAbstract
{

  public function getName()
  {
    return(("Batch upload process (XML Service)"));
  }

  public function help()
  {
    return(("Hello I'm the batch upload process."));
  }

  protected function retrieve_content(appbox $appbox)
  {
    $conn = $appbox->get_connection();

    $sql = 'UPDATE uplfile AS f INNER JOIN uplbatch AS u USING(uplbatch_id)
            SET f.error="1", u.error="1"
            WHERE u.error=0 AND u.base_id NOT IN(SELECT base_id FROM bas)';

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();

    $sql = 'SELECT uplbatch_id, sbas_id, server_coll_id, usr_id
            FROM (uplbatch u INNER JOIN bas b USING(base_id))
            WHERE complete="1" AND error="0" ORDER BY uplbatch_id';

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    return $rs;
  }

  protected function process_one_content(appbox $appbox, Array $row)
  {
    $appbox = appbox::get_instance();
    $registry = $appbox->get_registry();
    $conn = $appbox->get_connection();
    $session = $appbox->get_session();
    $this->log(sprintf(('processing batch %s'), $row['uplbatch_id']));

    $errors = '';

    $path = NULL;
    $coll_id = $row['server_coll_id'];
    $sbas_id = $row['sbas_id'];
    $usr_id = $row['usr_id'];
    $batch_id = $row['uplbatch_id'];

    try
    {
      $databox = databox::get_instance($sbas_id);
      $path = $registry->get('GV_RootPath') . 'tmp/batches/' . $batch_id . '/';
      if (!is_dir($path))
        throw new Exception(sprintf(('Batch directory \'%s\' does not exist'), $path));

      $user = User_Adapter::getInstance($usr_id, $appbox);
      $auth = new Session_Authentication_None($user);
      $session->authenticate($auth);

      $sql = 'SELECT * FROM uplfile WHERE uplbatch_id = :batch_id ORDER BY idx';
      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':batch_id' => $batch_id));
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      foreach ($rs as $row2)
      {
        $this->log(sprintf(('archiving file \'%s\''), $row2['filename']));

        try
        {
          $system_file = new system_file($path . '/' . $row2['idx']);
          $system_file->set_phrasea_tech_field(system_file::TECH_FIELD_ORIGINALNAME, $row2['filename']);
          $system_file->set_phrasea_tech_field(system_file::TECH_FIELD_PARENTDIRECTORY, '');
          $system_file->set_phrasea_tech_field(system_file::TECH_FIELD_SUBPATH, '');

          $meta = $system_file->extract_metadatas($databox->get_meta_structure());
          $collection = collection::get_from_coll_id($databox, $coll_id);

          $record = record_adapter::create($collection, $system_file, $row2['filename'], false);
          $record->set_metadatas($meta['metadatas']);
          $record->rebuild_subdefs();
          $record->reindex();
          unset($record);

          @unlink($system_file->getPathname());
          unset($system_file);
        }
        catch (Exception $e)
        {

        }
      }
      rmdir($path);
      unset($databox);
      $session->logout();
    }
    catch (Exception $e)
    {
      $this->log($e->getMessage());

      $sql = 'UPDATE uplfile AS f INNER JOIN uplbatch AS u USING(uplbatch_id)
              SET f.error="1", u.error="1"
              WHERE u.uplbatch_id = :batch_id';

      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':batch_id' => $batch_id));
      $stmt->closeCursor();
      $errors = '1';
    }

    $sql = 'UPDATE uplbatch SET complete="2", error = :error
            WHERE uplbatch_id = :batch_id';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':error' => $errors, ':batch_id' => $batch_id));
    $stmt->closeCursor();

    $this->log(sprintf(('finishing batch %s'), $row['uplbatch_id']));

    return $this;
  }

  protected function post_process_one_content(appbox $appbox, Array $row)
  {
    return $this;
  }

}
