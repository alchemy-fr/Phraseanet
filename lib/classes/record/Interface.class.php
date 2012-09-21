<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Doctrine\ORM\EntityManager;
use MediaVorus\Media\MediaInterface;
use Monolog\Logger;
use MediaAlchemyst\Alchemyst;
use MediaVorus\MediaVorus;
use Symfony\Component\Filesystem\Filesystem;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface record_Interface
{

    public function get_creation_date();

    public function get_uuid();

    public function get_modification_date();

    public function get_number();

    public function set_number($number);

    public function set_type($type);

    public function is_grouping();

    public function get_base_id();

    public function get_record_id();

    public function get_databox();

    public function get_thumbnail();

    public function get_embedable_medias();

    public function get_status_icons();

    public function get_type();

    public function get_formated_duration();

    public function get_duration();

    public function move_to_collection(collection &$collection, appbox &$appbox);

    public function get_rollover_thumbnail();

    public function get_sha256();

    public function get_mime();

    public function get_status();

    public function get_subdef($name);

    public function get_subdefs();

    public function get_collection_logo();

    public function get_technical_infos($data = false);

    public function get_caption();

    public function get_original_name();

    public function get_title($highlight = false, searchEngine_adapter $searchEngine = null);

    public function get_preview();

    public function has_preview();

    public function get_serialize_key();

    public function get_sbas_id();

    public function substitute_subdef($name, MediaInterface $media, Application $app);

    public function set_metadatas(Array $metadatas, $force_readonly = false);

    public function reindex();

    public function rebuild_subdefs();

    public function write_metas();

    public function set_binary_status($status);

    public function get_hd_file();

    public function delete();

    public function generate_subdefs(databox $databox, Application $app, Array $wanted_subdefs = null);

    public function log_view($log_id, $referrer, $gv_sit);

    public function get_container_baskets(EntityManager $em, User_Adapter $user);
}
