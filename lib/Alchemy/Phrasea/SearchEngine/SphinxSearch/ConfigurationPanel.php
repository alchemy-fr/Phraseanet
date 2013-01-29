<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\SphinxSearch;

use Alchemy\Phrasea\SearchEngine\AbstractConfigurationPanel;
use Alchemy\Phrasea\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;

class ConfigurationPanel extends AbstractConfigurationPanel
{
    const DATE_FIELD_PREFIX = 'date_field_';

    protected $charsets;
    protected $searchEngine;

    public function __construct(SphinxSearchEngine $engine)
    {
        $this->searchEngine = $engine;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sphinx-search';
    }

    /**
     * {@inheritdoc}
     */
    public function get(Application $app, Request $request)
    {
        $configuration = $this->getConfiguration();

        $params = array(
            'configuration' => $configuration,
            'configfile'    => $this->generateSphinxConf($app['phraseanet.appbox']->get_databoxes(), $configuration),
            'charsets'      => $this->getAvailableCharsets(),
            'date_fields'   => $this->getAvailableDateFields($app['phraseanet.appbox']->get_databoxes()),
        );

        return $app['twig']->render('admin/search-engine/sphinx-search.html.twig', $params);
    }

    /**
     * {@inheritdoc}
     */
    public function post(Application $app, Request $request)
    {
        $configuration = $this->getConfiguration();
        $configuration['charset_tables'] = array();
        $configuration['date_fields'] = array();

        foreach ($request->request->get('charset_tables', array()) as $table) {
            $configuration['charset_tables'][] = $table;
        }
        foreach ($request->request->get('date_fields', array()) as $field) {
            $configuration['date_fields'][] = $field;
        }

        $configuration['host'] = $request->request->get('host');
        $configuration['host'] = $request->request->get('port');
        $configuration['rt_host'] = $request->request->get('rt_host');
        $configuration['rt_port'] = $request->request->get('rt_port');

        $this->saveConfiguration($configuration);

        return $app->redirect($app['url_generator']->generate('admin_searchengine_get'));
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $configuration = @json_decode(file_get_contents($this->getConfigPathFile()), true);

        if (!is_array($configuration)) {
            $configuration = array();
        }

        if (!isset($configuration['charset_tables'])) {
            $configuration['charset_tables'] = array("common", "latin");
        }

        if (!isset($configuration['date_fields'])) {
            $configuration['date_fields'] = array();
        }

        if (!isset($configuration['host'])) {
            $configuration['host'] = '127.0.0.1';
        }

        if (!isset($configuration['port'])) {
            $configuration['port'] = 9306;
        }

        if (!isset($configuration['rt_host'])) {
            $configuration['rt_host'] = '127.0.0.1';
        }

        if (!isset($configuration['rt_port'])) {
            $configuration['rt_port'] = 9308;
        }

        return $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function saveConfiguration(array $configuration)
    {
        file_put_contents($this->getConfigPathFile(), json_encode($configuration));

        return $this;
    }

    /**
     * Returns all the charset Sphinx Search supports
     *
     * @return array An array of charsets
     */
    public function getAvailableCharsets()
    {
        if (null !== $this->charsets) {
            return $this->charsets;
        }

        $this->charsets = array();

        $finder = new Finder();
        $finder->in(__DIR__ . '/Charset/')->files()->name('*.php');

        foreach ($finder as $file) {
            $name = substr($file->getFilename(), 0, -4);
            $classname = __NAMESPACE__ . '\\Charset\\' . $name;
            if (class_exists($classname)) {
                $this->charsets[$name] = new $classname;
            }
        }

        ksort($this->charsets);

        return $this->charsets;
    }

    /**
     * Generates Sphinx Search configuration depending on the service configuration
     *
     * @param  array  $databoxes     The databoxes to index
     * @param  array  $configuration The configuration
     * @return string The sphinx search configuration
     */
    public function generateSphinxConf(array $databoxes, array $configuration)
    {
        $defaults = array(
            'charset_tables' => array(),
        );

        $options = array_merge($defaults, $configuration);

        $options['charset_tables'] = array_unique($options['charset_tables']);

        $conf = '';

        $charsets = '';
        foreach ($options['charset_tables'] as $charset) {
            $classname = __NAMESPACE__ . '\\Charset\\' . $charset;
            if (class_exists($classname)) {
                $charset_table = new $classname();
                $charsets .= $charset_table->get_table();
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

        foreach ($databoxes as $databox) {

            $index_crc = $this->searchEngine->CRCdatabox($databox);

            $date_selects = $date_left_joins = $date_fields = array();
            foreach ($configuration['date_fields'] as $name) {
                $field = $databox->get_meta_structure()->get_element_by_name($name);

                $date_fields[] = self::DATE_FIELD_PREFIX . $name;

                if ($field instanceof \databox_field) {
                    $date_selects[] = ", UNIX_TIMESTAMP(d" . $field->get_id() . ".value) as " . self::DATE_FIELD_PREFIX . $name;
                    $date_left_joins[] = "    LEFT JOIN metadatas d" . $field->get_id() . " ON (d" . $field->get_id() . ".record_id = r.record_id AND d" . $field->get_id() . ".meta_struct_id = " . $field->get_id() . ")";
                } else {
                    $date_selects[] = ", null as " . $name;
                }
            }

            $conf .= '


#------------------------------------------------------------------------------
# *****************  ' . $databox->get_viewname() . '
#------------------------------------------------------------------------------


  #--------------------------------------
  ### Sources Abstract

  source database_cfg' . $index_crc . '
  {
    type                  = mysql
    sql_host              = ' . $databox->get_host() . '
    sql_user              = ' . $databox->get_user() . '
    sql_pass              =
    sql_db                = ' . $databox->get_dbname() . '
    sql_port              = ' . $databox->get_port() . '

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
    path                  = /var/sphinx/datas/suggest_' . $index_crc . '

' . $charset_abstract . '
  }

  #--------------------------------------
  ### Metadatas Sources
  source src_metadatas' . $index_crc . ' : database_cfg' . $index_crc . '
  {
    sql_query             = \
      SELECT m.id, m.meta_struct_id, m.record_id, m.value, \
        ' . $databox->get_sbas_id() . ' as sbas_id, s.id, \
        CRC32(CONCAT_WS("_", ' . $databox->get_sbas_id() . ', s.id)) as crc_struct_id, \
        CONCAT_WS("_", ' . $databox->get_sbas_id() . ', s.id) as struct_id, \
        r.parent_record_id, \
        CRC32(CONCAT_WS("_", ' . $databox->get_sbas_id() . ', r.coll_id)) as crc_sbas_coll, \
        CRC32(CONCAT_WS("_", ' . $databox->get_sbas_id() . ', r.record_id)) as crc_sbas_record, \
        CONCAT_WS("_", ' . $databox->get_sbas_id() . ', r.coll_id) as sbas_coll, \
        CRC32(r.type) as crc_type, r.coll_id, \
        UNIX_TIMESTAMP(credate) as created_on, 0 as deleted, \
        CRC32(CONCAT_WS("_", r.coll_id, s.business)) as crc_coll_business, \
        s.business \
        ' . implode(" \\\n", $date_selects) . ' \
      FROM (metadatas m, metadatas_structure s, record r) \
          ' . implode(" \\\n", $date_left_joins) . ' \
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
';
            foreach ($date_fields as $date_field) {
                $conf.= "    sql_attr_timestamp    = $date_field\n";
            }

            $conf .= '

    sql_attr_multi        = uint status from query; SELECT m.id as id, \
      CRC32(CONCAT_WS("_", ' . $databox->get_sbas_id() . ', s.name)) as name \
      FROM metadatas m, status s \
      WHERE s.record_id = m.record_id AND s.value = 1 \
      ORDER BY m.id ASC

    # datas returned in the resultset
    sql_query_info        = SELECT r.* FROM record r, metadatas m \
      WHERE m.id=$id AND m.record_id = r.record_id
  }

  #--------------------------------------
  ### Metadatas Index

  index metadatas' . $index_crc . ' : suggest' . $index_crc . '
  {
    source                = src_metadatas' . $index_crc . '
    path                  = /var/sphinx/datas/metadatas_' . $index_crc . '

  }

  #--------------------------------------
  ### Metadatas Index Stemmed FR

  index metadatas' . $index_crc . '_stemmed_fr : suggest' . $index_crc . '
  {
    source                = src_metadatas' . $index_crc . '

    path                  = /var/sphinx/datas/metadatas_' . $index_crc . '_stemmed_fr

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

  #--------------------------------------
  ### Metadatas Index Stemmed EN

  index metadatas' . $index_crc . '_stemmed_en : suggest' . $index_crc . '
  {
    source                = src_metadatas' . $index_crc . '

    path                  = /var/sphinx/datas/metadatas_' . $index_crc . '_stemmed_en

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
  ### METAS_REALTIME Index

  index metas_realtime' . $index_crc . '
  {
    type                  = rt
    path                  = /var/sphinx/datas/metas_realtime_' . $index_crc . '

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
';

            foreach ($date_fields as $date_field) {
                $conf.= "    rt_attr_timestamp     = $date_field\n";
            }

            $conf .= '    rt_attr_multi         = status
  }

  #--------------------------------------
  ### All documents Index (give the last 1000 records added, etc...)

  source src_documents' . $index_crc . ' : database_cfg' . $index_crc . '
  {
    sql_query             = \
        SELECT r.record_id as id, r.record_id, r.parent_record_id, ' . $databox->get_sbas_id() . ' as sbas_id, \
            CRC32(CONCAT_WS("_", ' . $databox->get_sbas_id() . ', r.coll_id)) as crc_sbas_coll, \
            CRC32(CONCAT_WS("_", ' . $databox->get_sbas_id() . ', r.record_id)) as crc_sbas_record, \
            CONCAT_WS("_", ' . $databox->get_sbas_id() . ' , r.coll_id) as sbas_coll, \
            CRC32(r.type) as crc_type, r.coll_id, \
            UNIX_TIMESTAMP(r.credate) as created_on, 0 as deleted \
            ' . implode(" \\\n", $date_selects) . ' \
        FROM (record r) \
        ' . implode(" \\\n", $date_left_joins) . ' \
        WHERE 1

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
';
            foreach ($date_fields as $date_field) {
                $conf.= "    sql_attr_timestamp    = $date_field\n";
            }

            $conf .= '

    sql_attr_multi        = uint status from query; SELECT r.record_id as id, \
      CRC32(CONCAT_WS("_", ' . $databox->get_sbas_id() . ', s.name)) as name \
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
    path                  = /var/sphinx/datas/documents_' . $index_crc . '

    morphology            = none
  }

  index documents' . $index_crc . '_stemmed_fr : documents' . $index_crc . '
  {
    path                  = /var/sphinx/datas/documents_' . $index_crc . '_stemmed_fr

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
    path                  = /var/sphinx/datas/documents_' . $index_crc . '_stemmed_en

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
    path                  = /var/sphinx/datas/docs_realtime_' . $index_crc . '

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
';

            foreach ($date_fields as $date_field) {
                $conf.= "    rt_attr_timestamp    = $date_field\n";
            }

            $conf .= '    rt_attr_multi         = status
  }

#------------------------------------------------------------------------------
# *****************  End configuration for ' . $databox->get_viewname() . '
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
  listen                  = 127.0.0.1:19306
  listen                  = 127.0.0.1:19308:mysql41

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
