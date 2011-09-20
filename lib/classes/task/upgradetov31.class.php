<?php

class task_upgradetov31 extends phraseatask
{

  // ==========================================================================
  // ===== les interfaces de settings (task2.php) pour ce type de tache
  // ==========================================================================
  // ====================================================================
  // getName() : must return the name of this kind of task (utf8), MANDATORY
  // ====================================================================
  public function getName()
  {
    return(_("upgrade to v3.1"));
  }

  function interfaceAvalaible()
  {
    return false;
  }

  // ==========================================================================
  // help() : text displayed if --help
  // ==========================================================================
  public function help()
  {
    return(utf8_encode("Upgrade some database values"));
  }

  // ==========================================================================
  // run() : the real code executed by each task, MANDATORY
  // ==========================================================================

  function run()
  {
    printf("taskid %s starting." . PHP_EOL, $this->taskid);
    // task can't be stopped here

    $conn = connection::getInstance();
    $running = true;

    if (!defined('GV_exiftool') || !is_executable(GV_exiftool))
    {
      printf("Exiftool is not executable, script can not process\n");
      return 'stopped';
    }


    $todo = $this->how_many_left();
    $done = 0;
    $lb = phrasea_list_bases();

    $ret = 'stopped';

    $this->setProgress($done, $todo);


    while ($conn && $running)
    {

      foreach ($lb['bases'] as $sbas)
      {
        $connbas = connection::getInstance($sbas['sbas_id']);

        if (!$connbas)
          continue;

        $sql = 'SELECT r.type, r.record_id, s.path, s.file, r.xml, uuid, sha256
                FROM record r, subdef s
                WHERE ((uuid IS NULL OR uuid = "") OR (`sha256` = "" AND `sha256` IS NOT NULL))
                  AND r.parent_record_id="0"
                  AND s.record_id = r.record_id AND s.name="document" LIMIT 100';

        if ($rs = $connbas->query($sql))
        {
          while ($row = $connbas->fetch_assoc($rs))
          {
            $pathfile = p4string::addEndSlash($row['path']) . $row['file'];
            $uuid = $row['uuid'];
            $sha256 = $row['sha256'];
            if (trim($uuid) === '')
            {
              if (!file_exists($pathfile))
              {
                printf("le fichier nexiste $pathfile pas ....\n");
                $uuid = uuid::generate_uuid();
              }
              else
              {
                $uuid_file = new uuid($pathfile);
                $uuid = $uuid_file->write_uuid();
              }
            }
            if (trim($sha256) === '')
            {
              if (!file_exists($pathfile))
              {
                printf("le fichier nexiste $pathfile pas ....\n");
                $sha256 = null;
              }
              else
              {
                $sha256 = hash_file('sha256', $pathfile);
              }
            }

            $sql = 'UPDATE record SET
              uuid="' . $connbas->escape_string($uuid) . '", sha256=' . ($sha256 === null ? 'NULL' : '"' . $connbas->escape_string($sha256) . '"') . '
              WHERE record_id="' . $connbas->escape_string($row['record_id']) . '"';
            echo "mise a jour du record " . $row['record_id'] .
            ($uuid != $row['uuid'] ? " avec uuid " . $uuid : 'uuid not moving') . " et sha256 ".($sha256=== null ? 'null' : $sha256)." \n";
            $connbas->query($sql);


            if ($row['type'] == 'video')
            {
              echo "check video...";
              $duration = 0;
              $update_done = false;

              if ($infoXml = simplexml_load_string($row['xml']))
              {
                foreach ($infoXml->doc->Attributes() as $k => $v)
                {
                  if ($k == 'duration')
                  {
                    $old_duration = (string) $v;
                    break;
                  }
                }
              }

              $pathfile = p4string::addEndSlash($row['path']) . $row["file"];

              if (file_exists($pathfile))
              {
                $prop = getVideoInfos($pathfile);
              }

              if (isset($prop['duration']) && (float) $prop['duration'] != $old_duration && (float) $prop['duration'] > 0)
              {
                try
                {
                  $ddoc = new DOMDocument();
                  $ddoc->loadXML($row['xml']);
                  $nodes = $ddoc->getElementsByTagName('doc');
                  if ($nodes->length > 0)
                  {
                    $node = $nodes->item(0);
                    $node->setAttribute('duration', trim($prop['duration']));
                  }

                  $xml = $ddoc->saveXML();
                  $sql = 'UPDATE record SET xml="' . $connbas->escape_string($xml) . '" WHERE record_id="' . $connbas->escape_string($row['record_id']) . '"';

                  echo "\t update $old_duration to " . $prop['duration'] . "\n";

                  if ($connbas->query($sql))
                    $update_done = true;
                }
                catch (Exception $e)
                {
                  
                }
              }
              else
                echo "\t no update\n";
            }

            $done++;
            $this->setProgress($done, $todo);
          }
          $connbas->free_result($rs);
        }
      }

      $todo = $this->how_many_left() + $done;

      if ($done == $todo)
      {
        $sql = 'UPDATE task2 SET status="tostop" WHERE  task_id="' . $this->taskid . '"';
        $conn->query($sql);
        $this->setProgress(0, 0);
        $ret = 'todelete';
      }

      $sql = "SELECT status FROM task2 WHERE status='tostop' AND task_id=" . $this->taskid;
      if ($rs = $conn->query($sql))
      {
        if ($row = $conn->fetch_assoc($rs))
        {
          $running = false;
        }
        $conn->free_result($rs);
      }
      $conn->close();
      unset($conn);
      sleep(1);
      $conn = connection::getInstance();
    }
    printf("taskid %s ending." . PHP_EOL, $this->taskid);

    // task can't be (re)started here
    sleep(1);

    printf("good bye world I was task upgrade to version 3.1" . PHP_EOL);

    flush();
    return $ret;
  }

  private function how_many_left()
  {
    $todo = 0;
    $lb = phrasea_list_bases();

    foreach ($lb['bases'] as $sbas)
    {
      $connbas = connection::getInstance($sbas['sbas_id']);

      if (!$connbas)
        continue;

        $sql = 'SELECT count(r.record_id) as total
                FROM record r, subdef s
                WHERE ((uuid IS NULL OR uuid = "") OR (`sha256` = "" AND `sha256` IS NOT NULL))
                  AND r.parent_record_id="0"
                  AND s.record_id = r.record_id AND s.name="document" LIMIT 100';
        
      if ($rs = $connbas->query($sql))
      {
        if ($row = $connbas->fetch_assoc($rs))
        {
          $todo += (int) $row['total'];
        }
        $connbas->free_result($rs);
      }
    }

    return $todo;
  }

}

?>