<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class p4file
{

    public static function apache_tokenize($file)
    {
        $ret = false;
        $registry = registry::get_instance();

        if ($registry->get('GV_h264_streaming') && is_file($file)) {
            if (($pos = mb_strpos($file, $registry->get('GV_mod_auth_token_directory_path'))) === false) {
                return false;
            }

            $server = new system_server();

            if ($server->is_nginx()) {
                $fileToProtect = mb_substr($file, mb_strlen($registry->get('GV_mod_auth_token_directory_path')));

                $secret = $registry->get('GV_mod_auth_token_passphrase');
                $protectedPath = p4string::addFirstSlash(p4string::delEndSlash($registry->get('GV_mod_auth_token_directory')));

                $hexTime = strtoupper(dechex(time() + 3600));

                $token = md5($protectedPath . $fileToProtect . '/' . $secret . '/' . $hexTime);

                $url = $protectedPath . $fileToProtect . '/' . $token . '/' . $hexTime;

                $ret = $url;
            } elseif ($server->is_apache()) {
                $fileToProtect = mb_substr($file, mb_strlen($registry->get('GV_mod_auth_token_directory_path')));


                $secret = $registry->get('GV_mod_auth_token_passphrase');        // Same as AuthTokenSecret
                $protectedPath = p4string::addEndSlash(p4string::delFirstSlash($registry->get('GV_mod_auth_token_directory')));         // Same as AuthTokenPrefix
                $hexTime = dechex(time());             // Time in Hexadecimal

                $token = md5($secret . $fileToProtect . $hexTime);

                // We build the url
                $url = '/' . $protectedPath . $token . "/" . $hexTime . $fileToProtect;


                $ret = $url;
            }
        }

        return $ret;
    }

    public static function archiveFile(system_file &$system_file, $base_id, $delete = true, $name = false)
    {
        throw new Exception('This method is deprecated');

        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $registry = $appbox->get_registry();

        $system_file->set_phrasea_tech_field(system_file::TECH_FIELD_ORIGINALNAME, $name ? $name : $system_file->getFilename());
        $system_file->set_phrasea_tech_field(system_file::TECH_FIELD_PARENTDIRECTORY, null);
        $system_file->set_phrasea_tech_field(system_file::TECH_FIELD_SUBPATH, null);

        $go = User_Adapter::getInstance($session->get_usr_id(), $appbox)
            ->ACL()
            ->has_right_on_base($base_id, 'canaddrecord');

        if ( ! $go) {
            echo "\ndroits insuffisants";

            return false;
        }

        $sbas_id = phrasea::sbasFromBas($base_id);
        $databox = databox::get_instance($sbas_id);
        $collection = collection::get_from_base_id($base_id);

        $collprefs = simplexml_load_string($collection->get_prefs());

        $server_coll_id = phrasea::collFromBas($base_id);

        if ($collprefs === false) {
            if (GV_debug)
                echo 'Error loading collprefs';

            return false;
        }

        $metadatas = $system_file->extract_metadatas($databox->get_meta_structure());

        $status = "0";

        if ($collprefs->status)
            $status = (string) ($collprefs->status);

        $record_id = $record = false;

        try {
            $record = record_adapter::create($collection, $system_file, $name);
            $record_id = $record->get_record_id();
            $record->set_metadatas($metadatas['metadatas'], true);
        } catch (Exception $e) {
            echo $e->getMessage();
            if ($record instanceof record_adapter)
                $record->delete();

            return false;
        }

        $record->set_binary_status(databox_status::dec2bin($status));
        $record->rebuild_subdefs();
        $record->reindex();

        if ($delete) {
            @unlink($system_file->getPathname());
            unset($system_file);
        }

        return $record_id;
    }

    public static function check_file_error($filename, $sbas_id, $originalname)
    {
        throw new Exception('This method is deprecated');

        $core = \bootstrap::getCore();
        $checks = array();

        $system_file = new system_file($filename);

        $doctype = $system_file->get_phrasea_type();

        $databox = databox::get_instance($sbas_id);

        if ($baseprefs = $databox->get_sxml_structure()) {

            $file_checks = $baseprefs->filechecks;
            $checks = $file_checks->$doctype;
            $checks = $checks[0];
        } else {
            throw new Exception(_('prod::erreur : impossible de lire les preferences de base'));
        }

        $errors = array();

        $media = $core['mediavorus']->guess($filename);

        $width = $height = 0;
        $colorSpace = null;

        if ($media instanceof \MediaVorus\Media\Image) {
            $width = $media->getWidth();
            $height = $media->getHeight();
            $colorSpace = strtolower($media->getColorSpace());
        }

        foreach ($checks as $name => $value) {
            switch ($name) {
                case 'name':
                    $records = record_adapter::get_records_by_originalname($databox, $original_name, false, 0, 1);
                    if (count($records) > 0)
                        $errors[] = sprintf(_('Le fichier \'%s\' existe deja'), $originalname);
                    break;
                case 'size':
                    if (min($width, $height) < (int) $value) {
                        $errors[] = sprintf(_('Taille trop petite : %dpx'), $min);
                    }
                    break;
                case 'color_space':
                    $required = strtolower(in_array($value, array('sRGB', 'RGB')) ? 'RGB' : $value);

                    $go = false;

                    if ( ! $colorSpace || $required == $colorSpace) {
                        $go = true;
                    } elseif ($required == 'rgb' && in_array($colorSpace, array('srgb', 'rgb')) > 0) {
                        $go = true;
                    }

                    if ( ! $go) {
                        $errors[] = sprintf(_('Mauvais mode colorimetrique : %s'), $colorSpace);
                    }

                    break;
            }
        }

        return $errors;
    }
}
