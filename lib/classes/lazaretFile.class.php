<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class lazaretFile
{

    protected $storage = array();

    /**
     *
     * @param int $lazaret_id
     * @return lazaretFile
     */
    function __construct($lazaret_id)
    {
        $conn = connection::getPDOConnection();

        $sql = 'SELECT filename, filepath, base_id,
                   uuid, status, created_on, usr_id
             FROM lazaret WHERE id = :lazaret_id';

        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':lazaret_id' => $lazaret_id));
        $row          = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Exception(_('L\'element n\'existe pas ou plus'));

        $this->id = $lazaret_id;
        $this->filename = $row['filename'];
        $this->filepath = $row['filepath'];
        $this->status = $row['status'];
        $this->base_id = $row['base_id'];
        $this->uuid = $row['uuid'];
        $this->created_on = new DateTime($row['created_on']);
        $this->usr_id = $row['usr_id'];

        return $this;
    }

    /**
     *
     * @return lazaretFile
     */
    public function add_to_base()
    {
        $registry = registry::get_instance();

        $file = new system_file($registry->get('GV_RootPath') . 'tmp/lazaret/' . $this->filepath);

        if (($record_id = p4file::archiveFile(
            $file, $this->base_id, false, $this->filename)) === false)
            throw new Exception(_('Impossible dajouter le fichier a la base'));

        $sbas_id = phrasea::sbasFromBas($this->base_id);
        $connbas = connection::getPDOConnection($sbas_id);

        $sql = 'UPDATE record
        SET status = (status | ' . $this->status . ')
        WHERE record_id = :record_id';

        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':record_id' => $record_id));
        $stmt->closeCursor();

        $this->delete();

        return $this;
    }

    /**
     *
     * @return lazaretFile
     */
    public function delete()
    {
        $conn     = connection::getPDOConnection();
        $registry = registry::get_instance();

        try
        {
            $sql  = 'DELETE FROM lazaret WHERE id = :lazaret_id';
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(':lazaret_id' => $this->id));
            $stmt->closeCursor();

            $file      = $registry->get('GV_RootPath') . 'tmp/lazaret/' . $this->filepath;
            $thumbnail = $file . '_thumbnail.jpg';

            @unlink($thumbnail);
            @unlink($file);
        }
        catch (Exception $e)
        {

        }

        return $this;
    }

    /**
     *
     * @param Int $lazaret_id
     * @param Int $record_id
     * @return lazaretFile
     */
    public function substitute($lazaret_id, $record_id)
    {
        $registry = registry::get_instance();

        $sbas_id = phrasea::sbasFromBas($this->base_id);
        $connbas = connection::getPDOConnection($sbas_id);

        $base_id = false;

        $sql = 'SELECT coll_id FROM record WHERE record_id = :record_id';

        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':record_id' => $record_id));
        $row         = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Exception(_('Impossible de trouver la base'));

        $base_id = phrasea::baseFromColl($sbas_id, $row['coll_id']);

        $pathfile = $registry->get('GV_RootPath') . 'tmp/lazaret/' . $this->filepath;

        $record = new record_adapter($sbas_id, $record_id);
        $record->substitute_subdef('document', new system_file($pathfile));

        $this->delete();

        return $this;
    }

    public static function move_uploaded_to_lazaret(system_file $system_file, $base_id, $filename, $errors = '', $status = false)
    {
        $Core     = \bootstrap::getCore();
        $appbox   = appbox::get_instance($Core);
        $session  = $appbox->get_session();
        $registry = $appbox->get_registry();
        $conn     = $appbox->get_connection();

        if ( ! $status)
        {
            $status = '0';
        }

        $usr_id = $session->is_authenticated() ? $session->get_usr_id() : false;

        $lazaret_root = $registry->get('GV_RootPath') . 'tmp/lazaret/';
        $pathinfo     = pathinfo($filename);

        $tmp_filename = $filename;

        $n = 1;
        while (file_exists($lazaret_root . $tmp_filename))
        {
            $tmp_filename = $pathinfo['filename']
              . '-' . $n . '.' . $pathinfo['extension'];
            $n ++;
        }

        $pathfile = $lazaret_root . $tmp_filename;
        $uuid     = $system_file->read_uuid();
        $sha256   = $system_file->get_sha256();
        rename($system_file->getPathname(), $pathfile);

        unset($system_file);

        $system_file = new system_file($pathfile);
        $system_file->chmod();

        try
        {
            $spec = new MediaAlchemyst\Specification\Image();
            $spec->setDimensions(200, 200);

            $Core['media-alchemyst']
              ->open($pathfile)
              ->turnInto($spec, $pathfile . "_thumbnail.jpg")
              ->close();
        }
        catch (\MediaAlchemyst\Exception\RuntimeException $e)
        {

        }

        try
        {

            $sql = 'INSERT INTO lazaret
            (id, filename, filepath, base_id, uuid, sha256,
                  errors, status, created_on, usr_id)
          VALUES (null, :filename, :filepath, :base_id,
          :uuid, :sha256, :errors, 0b' . $status . ', NOW(), :usr_id )';

            $params = array(
              ':filename' => $filename
              , ':filepath' => $tmp_filename
              , ':base_id'  => $base_id
              , ':uuid'     => $uuid
              , ':sha256'   => $sha256
              , ':errors'   => $errors
              , ':usr_id'   => ($usr_id ? $usr_id : null
              )
            );

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $stmt->closeCursor();

            return true;
        }
        catch (Exception $e)
        {

        }

        return false;
    }

    public static function stream_thumbnail($id)
    {
        $conn     = connection::getPDOConnection();
        $registry = registry::get_instance();
        $sql      = "SELECT filepath FROM lazaret WHERE id = :lazaret_id";

        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':lazaret_id' => $id));
        $row          = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row)
        {
            $pathfile = $registry->get('GV_RootPath') . 'tmp/lazaret/'
              . $row['filepath'] . '_thumbnail.jpg';

            $response = set_export::stream_file(
                $pathfile, basename($pathfile), 'image/jpeg', 'inline'
            );
            $response->send();
        }

        return false;
    }

}
