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

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class sphinx_configuration
{
    protected $app;
    const OPT_ALL_SBAS = 'all';
    const OPT_LIBSTEMMER_NONE = 'none';
    const OPT_LIBSTEMMER_FR = 'fr';
    const OPT_LIBSTEMMER_EN = 'en';
    const OPT_ENABLE_STAR_ON = 'yes';
    const OPT_ENABLE_STAR_OFF = 'no';
    const OPT_MIN_PREFIX_LEN = 0;
    const OPT_MIN_INFIX_LEN = 1;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function get_available_charsets()
    {
        $available_charsets = array();
        $dir = __DIR__ . '/charsetTable/';
        $registry = $this->app['phraseanet.registry'];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if ($file->isDir() || strpos($file->getPathname(), '/.svn/') !== false) {
                continue;
            }
            if ($file->isFile()) {
                $classname = str_replace(array(realpath(__DIR__ . '/..') . '/', '.class.php', '/'), array('', '', '_'), $file->getPathname());
                $available_charsets[$classname] = new $classname;
            }
        }
        ksort($available_charsets);

        return $available_charsets;
    }

    public function get_available_libstemmer()
    {
        return array(self::OPT_LIBSTEMMER_EN, self::OPT_LIBSTEMMER_FR, self::OPT_LIBSTEMMER_NONE);
    }

    public function get_configuration($options = array())
    {

        $defaults = array(
            'sbas'       => self::OPT_ALL_SBAS
            , 'libstemmer' => array(self::OPT_LIBSTEMMER_NONE)
            , 'enable_star'    => self::OPT_ENABLE_STAR_ON
            , 'min_prefix_len' => self::OPT_MIN_PREFIX_LEN
            , 'min_infix_len'  => self::OPT_MIN_INFIX_LEN
            , 'charset_tables' => array()
        );

        $options = array_merge($defaults, $options);

        $options['charset_tables'] = array_unique($options['charset_tables']);

        $lb = phrasea::sbas_params($this->app);

        $conf = '';

        $charsets = '';
        foreach ($options['charset_tables'] as $charset) {
            try {
                $charset_table = new $charset();
                $charsets .= $charset_table->get_table();
            } catch (Exception $e) {

            }
        }

        $charsets = explode("\n", $charsets);
        $last_detect = false;

        for ($i = (count($charsets) - 1); $i >= 0; $i--) {
            if (trim($charsets[$i]) === '') {
                unset($charsets[$i]);
                continue;
            }
            if (strpos(trim($charsets[$i]), '#') === 0) {
                unset($charsets[$i]);
                continue;
            }
            if ($last_detect === true && substr(trim($charsets[$i]), (strlen(trim($charsets[$i])) - 1), 1) !== ',')
                $charsets[$i] = rtrim($charsets[$i]) . ', ';
            $charsets[$i] = "  " . $charsets[$i] . " \\\n";
            $last_detect = true;
        }

        $charsets = "\\\n" . implode('', $charsets);

        $charset_abstract = '

    docinfo               = extern
    charset_type          = utf-8

    charset_table         = ' . $charsets . '

    # minimum indexed word length
    # default is 1 (index everything)
    min_word_len          = 1


    # whether to strip HTML tags from incoming documents
    # known values are 0 (do not strip) and 1 (do strip)
    # optional, default is 0
    html_strip            = 0


    # enable star character search
    enable_star           = 1

    # enable star search like cat*
    min_prefix_len        = 0

    # enable star search like *aculous
    min_infix_len         = 1
    ';

        foreach ($lb as $id => $params) {

            $serialized = str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $params['host'], $params['port'], $params['user'], $params['dbname']));
            $index_crc = crc32($serialized);

            $conf .= '


#------------------------------------------------------------------------------
# *****************  ' . $serialized . '
#------------------------------------------------------------------------------


  #--------------------------------------
  ### Sources Abstract

  source database_cfg' . $index_crc . '
  {
    type                  = mysql
    sql_host              = ' . $params['host'] . '
    sql_user              = ' . $params['user'] . '
    sql_pass              = toor
    sql_db                = ' . $params['dbname'] . '
    sql_port              = ' . $params['port'] . '

    # We retrieve datas in UTF-8
    sql_query_pre = SET character_set_results = "utf8", character_set_client = "utf8", \
      character_set_connection = "utf8", character_set_database = "utf8", \
      character_set_server = "utf8"
    sql_query_pre = SET NAMES utf8
  }

  #--------------------------------------
  ### Suggestions Sources
  source src_suggest' . $index_crc . ' : database_cfg' . $index_crc . '
  {
    sql_query             = SELECT id, keyword, trigrams, freq, LENGTH(keyword) AS len FROM suggest

    sql_attr_uint         = freq
    sql_attr_uint         = len
    sql_attr_string       = keyword
  }

  index suggest' . $index_crc . '
  {
    source                = src_suggest' . $index_crc . '
    path                  = /var/sphinx/datas/suggest_' . $serialized . '

' . $charset_abstract . '
  }

  #--------------------------------------
  ### Metadatas Sources
  source src_metadatas' . $index_crc . ' : database_cfg' . $index_crc . '
  {
    sql_query             = \
      SELECT m.id, m.meta_struct_id, m.record_id, m.value, \
        ' . $id . ' as sbas_id, s.id, \
        CRC32(CONCAT_WS("_", ' . $id . ', s.id)) as crc_struct_id, \
        CONCAT_WS("_", ' . $id . ', s.id) as struct_id, \
        r.parent_record_id, \
        CRC32(CONCAT_WS("_", ' . $id . ', r.coll_id)) as crc_sbas_coll, \
        CRC32(CONCAT_WS("_", ' . $id . ', r.record_id)) as crc_sbas_record, \
        CONCAT_WS("_", ' . $id . ', r.coll_id) as sbas_coll, \
        CRC32(r.type) as crc_type, r.coll_id, \
        UNIX_TIMESTAMP(credate) as created_on, 0 as deleted, \
        CRC32(CONCAT_WS("_", r.coll_id, s.business)) as crc_coll_business, \
        s.business \
      FROM metadatas m, metadatas_structure s, record r \
      WHERE m.record_id = r.record_id AND m.meta_struct_id = s.id \
        AND s.indexable = "1"

    # documents can be filtered / sorted on each sql_attr
    sql_attr_uint         = record_id
    sql_attr_uint         = sbas_id
    sql_attr_uint         = coll_id
    sql_attr_uint         = parent_record_id
    sql_attr_uint         = crc_struct_id
    sql_attr_uint         = crc_sbas_coll
    sql_attr_uint         = crc_sbas_record
    sql_attr_uint         = crc_type
    sql_attr_uint         = deleted
    sql_attr_uint         = business
    sql_attr_uint         = crc_coll_business
    sql_attr_timestamp    = created_on

    sql_attr_multi        = uint status from query; SELECT m.id as id, \
      CRC32(CONCAT_WS("_", ' . $id . ', s.name)) as name \
      FROM metadatas m, status s \
      WHERE s.record_id = m.record_id AND s.value = 1 \
      ORDER BY m.id ASC

    # datas returned in the resultset
    sql_query_info        = SELECT r.* FROM record r, metadatas m \
      WHERE m.id=$id AND m.record_id = r.record_id
  }

';

            if (in_array(self::OPT_LIBSTEMMER_NONE, $options['libstemmer'])) {
                $conf .= '
  #--------------------------------------
  ### Metadatas Index

  index metadatas' . $index_crc . ' : suggest' . $index_crc . '
  {
    source                = src_metadatas' . $index_crc . '
    path                  = /var/sphinx/datas/metadatas_' . $serialized . '

  }
';
            }

            if (in_array(self::OPT_LIBSTEMMER_FR, $options['libstemmer'])) {
                $conf .= '

  #--------------------------------------
  ### Metadatas Index Stemmed FR

  index metadatas' . $index_crc . '_stemmed_fr : suggest' . $index_crc . '
  {
    source                = src_metadatas' . $index_crc . '

    path                  = /var/sphinx/datas/metadatas_' . $serialized . '_stemmed_fr

    morphology            = libstemmer_fr

    # minimum word length at which to enable stemming
    # optional, default is 1 (stem everything)
    #
    min_stemming_len      = 1

    # whether to index original keywords along with stemmed versions
    # enables "=exactform" operator to work
    # optional, default is 0
    #
    index_exact_words     = 1
  }
';
            }
            if (in_array(self::OPT_LIBSTEMMER_EN, $options['libstemmer'])) {
                $conf .= '

  #--------------------------------------
  ### Metadatas Index Stemmed EN

  index metadatas' . $index_crc . '_stemmed_en : suggest' . $index_crc . '
  {
    source                = src_metadatas' . $index_crc . '

    path                  = /var/sphinx/datas/metadatas_' . $serialized . '_stemmed_en

    morphology            = libstemmer_en

    # minimum word length at which to enable stemming
    # optional, default is 1 (stem everything)
    #
    min_stemming_len      = 1

    # whether to index original keywords along with stemmed versions
    # enables "=exactform" operator to work
    # optional, default is 0
    #
    index_exact_words     = 1
  }
';
            }
            $conf .= '

  #--------------------------------------
  ### METAS_REALTIME Index

  index metas_realtime' . $index_crc . '
  {
    type                  = rt
    path                  = /var/sphinx/datas/metas_realtime_' . $serialized . '

' . $charset_abstract . '

    rt_field              = value
    rt_field              = meta_struct_id

    rt_attr_uint          = record_id
    rt_attr_uint          = sbas_id
    rt_attr_uint          = coll_id
    rt_attr_uint          = parent_record_id
    rt_attr_uint          = crc_struct_id
    rt_attr_uint          = crc_sbas_coll
    rt_attr_uint          = crc_sbas_record
    rt_attr_uint          = crc_type
    rt_attr_uint          = deleted
    rt_attr_uint          = business
    rt_attr_uint          = crc_coll_business
    rt_attr_timestamp     = created_on
  }

  #--------------------------------------
  ### All documents Index (give the last 1000 records added, etc...)

  source src_documents' . $index_crc . ' : database_cfg' . $index_crc . '
  {
    sql_query             = \
        SELECT r.record_id as id, record_id, r.parent_record_id, ' . $id . ' as sbas_id, \
            CRC32(CONCAT_WS("_", ' . $id . ', r.coll_id)) as crc_sbas_coll, \
            CRC32(CONCAT_WS("_", ' . $id . ', r.record_id)) as crc_sbas_record, \
            CONCAT_WS("_", ' . $id . ' , r.coll_id) as sbas_coll, \
            CRC32(r.type) as crc_type, r.coll_id, \
            UNIX_TIMESTAMP(credate) as created_on, 0 as deleted \
        FROM record r

    # documents can be filtered / sorted on each sql_attr
    sql_attr_uint         = record_id
    sql_attr_uint         = sbas_id
    sql_attr_uint         = coll_id
    sql_attr_uint         = parent_record_id
    sql_attr_uint         = crc_sbas_coll
    sql_attr_uint         = crc_sbas_record
    sql_attr_uint         = crc_type
    sql_attr_uint         = deleted
    sql_attr_timestamp    = created_on

    sql_attr_multi        = uint status from query; SELECT r.record_id as id, \
      CRC32(CONCAT_WS("_", ' . $id . ', s.name)) as name \
      FROM record r, status s \
      WHERE s.record_id = r.record_id AND s.value = 1 \
      ORDER BY r.record_id ASC

    sql_joined_field      = metas from query; \
      SELECT m.record_id as id, m.value \
      FROM metadatas m, metadatas_structure s \
      WHERE s.id = m.meta_struct_id AND s.business = 0 \
      ORDER BY m.record_id ASC

    # datas returned in the resultset
    sql_query_info        = SELECT r.* FROM record r WHERE r.record_id=$id
  }

  #--------------------------------------
  ### All documents Index

  index documents' . $index_crc . '  : suggest' . $index_crc . '
  {
    source                = src_documents' . $index_crc . '
    path                  = /var/sphinx/datas/documents_' . $serialized . '

    morphology            = none
  }

  index documents' . $index_crc . '_stemmed_fr : documents' . $index_crc . '
  {
    path                  = /var/sphinx/datas/documents_' . $serialized . '_stemmed_fr

    morphology            = libstemmer_fr

    # minimum word length at which to enable stemming
    # optional, default is 1 (stem everything)
    #
    min_stemming_len      = 1

    # whether to index original keywords along with stemmed versions
    # enables "=exactform" operator to work
    # optional, default is 0
    #
    index_exact_words     = 1
  }

  index documents' . $index_crc . '_stemmed_en : documents' . $index_crc . '
  {
    path                  = /var/sphinx/datas/documents_' . $serialized . '_stemmed_en

    morphology            = libstemmer_en

    # minimum word length at which to enable stemming
    # optional, default is 1 (stem everything)
    #
    min_stemming_len      = 1

    # whether to index original keywords along with stemmed versions
    # enables "=exactform" operator to work
    # optional, default is 0
    #
    index_exact_words     = 1
  }

  #--------------------------------------
  ### DOCS_REALTIME Index

  index docs_realtime' . $index_crc . '
  {
    type                  = rt
    path                  = /var/sphinx/datas/docs_realtime_' . $serialized . '

    ' . $charset_abstract . '

    rt_field              = value
#    rt_field              = meta_struct_id

    rt_attr_uint          = record_id
    rt_attr_uint          = sbas_id
    rt_attr_uint          = coll_id
    rt_attr_uint          = parent_record_id
#    rt_attr_uint          = crc_struct_id
    rt_attr_uint          = crc_sbas_coll
    rt_attr_uint          = crc_sbas_record
    rt_attr_uint          = crc_type
    rt_attr_uint          = deleted
    rt_attr_timestamp     = created_on
  }

#------------------------------------------------------------------------------
# *****************  End configuration for ' . $serialized . '
#------------------------------------------------------------------------------

';
        }

        $conf .='

#******************************************************************************
#******************  Sphinx Indexer Configuration  ****************************
#******************************************************************************

indexer {
  mem_limit               = 512M

  # maximum IO calls per second (for I/O throttling)
  # optional, default is 0 (unlimited)
  #
  # max_iops              = 40

  # maximum IO call size, bytes (for I/O throttling)
  # optional, default is 0 (unlimited)
  #
  # max_iosize            = 1048576
}

#******************************************************************************
#******************  Sphinx Search Daemon Configuration  **********************
#******************************************************************************

searchd
{
  # [hostname:]port[:protocol], or /unix/socket/path to listen on
  # known protocols are \'sphinx\' (SphinxAPI) and \'mysql41\' (SphinxQL)
  #
  # multi-value, multiple listen points are allowed
  # optional, defaults are 9312:sphinx and 9306:mysql41, as below
  #
  # listen                = 127.0.0.1
  # listen                = 192.168.0.1:9312
  # listen                = 9312
  # listen                = /var/run/searchd.sock
  listen                  = 9306
  listen                  = 9308:mysql41

  # log file, searchd run info is logged here
  # optional, default is \'searchd.log\'
  log                     = /var/sphinx/searchd.log

  # query log file, all search queries are logged here
  # optional, default is empty (do not log queries)
  query_log               = /var/sphinx/query.log

  # client read timeout, seconds
  # optional, default is 5
  read_timeout            = 5

  # request timeout, seconds
  # optional, default is 5 minutes
  client_timeout          = 300

  # maximum amount of children to fork (concurrent searches to run)
  # optional, default is 0 (unlimited)
  max_children            = 30

  # PID file, searchd process ID file name
  # mandatory
  pid_file                = /var/sphinx/searchd.pid

  # max amount of matches the daemon ever keeps in RAM, per-index
  # WARNING, THERE\'S ALSO PER-QUERY LIMIT, SEE SetLimits() API CALL
  # default is 1000 (just like Google)
  max_matches             = 1000000

  # seamless rotate, prevents rotate stalls if precaching huge datasets
  # optional, default is 1
  seamless_rotate         = 1

  # whether to forcibly preopen all indexes on startup
  # optional, default is 0 (do not preopen)
  preopen_indexes         = 1

  # whether to unlink .old index copies on succesful rotation.
  # optional, default is 1 (do unlink)
  unlink_old              = 1

  # multi-processing mode (MPM)
  # known values are none, fork, prefork, and threads
  # optional, default is fork
  #
  workers                 = threads # for RT to work

  # binlog files path; use empty string to disable binlog
  # optional, default is build-time configured data directory
  #
  # binlog_path           = # disable logging
  # binlog_path           = /var/data # binlog.001 etc will be created there
  binlog_path             =

  # binlog flush/sync mode
  # 0 means flush and sync every second
  # 1 means flush and sync every transaction
  # 2 means flush every transaction, sync every second
  # optional, default is 2
  #
  binlog_flush            = 2

  # binlog per-file size limit
  # optional, default is 128M, 0 means no limit
  #
  # binlog_max_log_size   = 256M

  # max threads to create for searching local parts of a distributed index
  # optional, default is 0, which means disable multi-threaded searching
  # should work with all MPMs (ie. does NOT require workers=threads)
  #
  dist_threads            = 4

  # max common subtree document cache size, per-query
  # optional, default is 0 (disable subtree optimization)
  #
  subtree_docs_cache      = 4M

  # max common subtree hit cache size, per-query
  # optional, default is 0 (disable subtree optimization)
  #
  subtree_hits_cache      = 8M

  # max allowed per-query filter count
  # optional, default is 256
  #
  max_filters             = 512

  compat_sphinxql_magics  = 0

}

';

        return $conf;
    }
}
