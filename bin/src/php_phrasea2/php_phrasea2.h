#ifndef PHP_PHRASEA2_H
#define PHP_PHRASEA2_H

#include "phrasea_engine/cache.h"	// define CACHE_SESSION

#ifdef ZTS
#include "TSRM.h"
#endif

ZEND_BEGIN_MODULE_GLOBALS(phrasea2)
	SQLCONN *global_epublisher;
	CACHE_SESSION *global_session;
	char tempPath[1024];
ZEND_END_MODULE_GLOBALS(phrasea2)

#ifdef ZTS
#define PHRASEA2_G(v) TSRMG(phrasea2_globals_id, zend_phrasea2_globals *, v)
#else
#define PHRASEA2_G(v) (phrasea2_globals.v)
#endif

PHP_MINIT_FUNCTION(phrasea2);
PHP_MSHUTDOWN_FUNCTION(phrasea2);
PHP_RINIT_FUNCTION(phrasea2);
PHP_RSHUTDOWN_FUNCTION(phrasea2);
PHP_MINFO_FUNCTION(phrasea2);

PHP_FUNCTION(phrasea2_verif_ultime);
PHP_FUNCTION(phrasea_getVersion);
PHP_FUNCTION(phrasea_create_session);
PHP_FUNCTION(phrasea_open_session);
PHP_FUNCTION(phrasea_save_session);
PHP_FUNCTION(phrasea_clear_cache);
PHP_FUNCTION(phrasea_register_base);
PHP_FUNCTION(phrasea_close_session);
PHP_FUNCTION(phrasea_query2); 
// PHP_FUNCTION(phrasea_query); 
PHP_FUNCTION(phrasea_fetch_results);
PHP_FUNCTION(phrasea_subdefs);
PHP_FUNCTION(phrasea_emptyw);
PHP_FUNCTION(phrasea_status); 
PHP_FUNCTION(phrasea_xmlcaption); 
ZEND_FUNCTION(phrasea_setxmlcaption);
PHP_FUNCTION(phrasea_isgrp);
PHP_FUNCTION(phrasea_grpparent);
PHP_FUNCTION(phrasea_grpforselection);
PHP_FUNCTION(phrasea_grpchild);
ZEND_FUNCTION(phrasea_setstatus);
// PHP_FUNCTION(phrasea_connect); 
// PHP_FUNCTION(phrasea_return_php);
PHP_FUNCTION(phrasea_out_xml);
PHP_FUNCTION(phrasea_list_bases);
PHP_FUNCTION(phrasea_uuid_create);

PHP_FUNCTION(phrasea_uuid_is_valid);
PHP_FUNCTION(phrasea_uuid_compare);
PHP_FUNCTION(phrasea_uuid_is_null);
//PHP_FUNCTION(phrasea_uuid_variant);
//PHP_FUNCTION(phrasea_uuid_time);
//PHP_FUNCTION(phrasea_uuid_mac);
PHP_FUNCTION(phrasea_uuid_parse);
PHP_FUNCTION(phrasea_uuid_unparse);

// PHP_FUNCTION(phrasea_usebase);
PHP_FUNCTION(phrasea_conn);
PHP_FUNCTION(phrasea_info);

extern zend_module_entry phrasea2_module_entry;
#define phpext_phrasea2_ptr &phrasea2_module_entry


#include <uuid/uuid.h>



/* mirrored PHP Constants */
#define UUID_TYPE_DEFAULT 0
#define UUID_TYPE_TIME UUID_TYPE_DCE_TIME
#define UUID_TYPE_DCE UUID_TYPE_DCE_RANDOM
#define UUID_TYPE_NAME UUID_TYPE_DCE_TIME
#define UUID_TYPE_RANDOM UUID_TYPE_DCE_RANDOM
#define UUID_TYPE_NULL -1
#define UUID_TYPE_INVALID -42


// extern ZEND_DECLARE_MODULE_GLOBALS(phrasea2)
ZEND_EXTERN_MODULE_GLOBALS(phrasea2)

#endif	/* PHP_PHRASEA2_H */
