#include <sys/types.h>
#include <sys/timeb.h>
#include <math.h>

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#pragma warning(push)
#pragma warning(disable:4005)		// supprime le warning sur double def de _WIN32_WINNT
#include "php.h"
#include "php_ini.h"
#pragma warning(pop)
#include "ext/standard/info.h"



#ifdef PHP_WIN32
# include <winsock2.h>
# define signal(a, b) NULL
#elif defined(NETWARE)
# include <sys/socket.h>
# define signal(a, b) NULL
#else
# if HAVE_SIGNAL_H
#  include <signal.h>
# endif
# if HAVE_SYS_TYPES_H
#  include <sys/types.h>
# endif
# include <netdb.h>
# include <netinet/in.h>
#endif

// ******* tosee : mysql.h moved
#include <mysql.h>


#include "_VERSION.h"
#include "php_phrasea2.h"

#include "phrasea_engine/trace_memory.h"


ZEND_DECLARE_MODULE_GLOBALS(phrasea2)

// -----------------------------------------------------------------------------
// option -Wno-write-strings to gcc prevents warnings on this section
static function_entry phrasea2_functions[] = {
	PHP_FE(phrasea_info, NULL) 
	PHP_FE(phrasea_conn, NULL)
	PHP_FE(phrasea_create_session, NULL) 
	PHP_FE(phrasea_open_session, NULL) 
	PHP_FE(phrasea_save_session, NULL) 
	PHP_FE(phrasea_clear_cache, NULL) 
	PHP_FE(phrasea_register_base, NULL) 
	PHP_FE(phrasea_close_session, NULL) 
//	PHP_FE(phrasea_query, NULL) 
	PHP_FE(phrasea_query2, NULL) 
	PHP_FE(phrasea_fetch_results, NULL) 
	PHP_FE(phrasea_subdefs, NULL) 
	PHP_FE(phrasea_emptyw, NULL) 
	PHP_FE(phrasea_status, NULL) 
	PHP_FE(phrasea_xmlcaption, NULL) 
	PHP_FE(phrasea_setxmlcaption, NULL)
	PHP_FE(phrasea_isgrp,	NULL)
	PHP_FE(phrasea_grpparent,	NULL)
	PHP_FE(phrasea_grpforselection,	NULL)
	PHP_FE(phrasea_grpchild,	NULL)
	PHP_FE(phrasea_setstatus, NULL) 
	PHP_FE(phrasea_uuid_create, NULL)
	PHP_FE(phrasea_uuid_is_valid, NULL)
	PHP_FE(phrasea_uuid_compare, NULL)
	PHP_FE(phrasea_uuid_is_null, NULL)
//	PHP_FE(phrasea_uuid_variant, NULL)
//	PHP_FE(phrasea_uuid_time, NULL)
//	PHP_FE(phrasea_uuid_mac, NULL)
	PHP_FE(phrasea_uuid_parse, NULL)
	PHP_FE(phrasea_uuid_unparse, NULL)
//	PHP_FE(phrasea_return_php, NULL) 
//	PHP_FE(phrasea_out_xml, NULL) 
	PHP_FE(phrasea_list_bases, NULL) 
	{NULL, NULL, NULL}	/* Must be the last line in phrasea2_functions[] */
};
// -----------------------------------------------------------------------------


zend_module_entry phrasea2_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	(char *)"phrasea2",
	phrasea2_functions,
	PHP_MINIT(phrasea2),
	PHP_MSHUTDOWN(phrasea2),
	PHP_RINIT(phrasea2),
	PHP_RSHUTDOWN(phrasea2),
	PHP_MINFO(phrasea2),
#if ZEND_MODULE_API_NO >= 20010901
	(char *)"0.1", /* Replace with version number for your extension */
#endif
	STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_PHRASEA2
	ZEND_GET_MODULE(phrasea2)
#endif



PHP_INI_BEGIN()
PHP_INI_END()


static void php_phrasea2_init_globals(zend_phrasea2_globals *phrasea2_globals)
{
	phrasea2_globals->global_epublisher = NULL;
	phrasea2_globals->global_session = NULL;
	phrasea2_globals->tempPath[0] = '\0';
}
/*
#ifdef PHP_WIN32
char tempPathBuffer[1024];
#else
char *tempPathBuffer = "/tmp/";
#endif
*/
// -----------------------------------------------------------------------------
// option -Wno-write-strings to gcc prevents warnings on this section
PHP_MINIT_FUNCTION(phrasea2)
{
//    REGISTER_LONG_CONSTANT("PHRASEA_K", 987, CONST_CS);
// zend_printf("PHP_MINIT_FUNCTION\n");
//	REGISTER_LONG_CONSTANT((char *)"PHRASEA_MULTIDOC_DOCONLY", 123, CONST_CS | CONST_PERSISTENT);
//	zend_register_long_constant("PHRASEA_MULTIDOC_DOCONLY", 24, 666,  CONST_CS | CONST_PERSISTENT, module_number, TSRMLS_DC);
// zend_printf("REGISTER_MAIN_LONG_CONSTANT done\n");
	ZEND_INIT_MODULE_GLOBALS(phrasea2, php_phrasea2_init_globals, NULL);
	
	REGISTER_INI_ENTRIES();

//	Z_TYPE(phrasea2_module_entry) = type;

	REGISTER_LONG_CONSTANT("PHRASEA_MYSQLENGINE", PHRASEA_MYSQLENGINE, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_PGSQLENGINE", PHRASEA_PGSQLENGINE, CONST_CS | CONST_PERSISTENT);
	
	REGISTER_LONG_CONSTANT("PHRASEA_OP_OR",     PHRASEA_OP_OR,     CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_OP_AND",    PHRASEA_OP_AND,    CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_KW_ALL",    PHRASEA_KW_ALL,    CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_KW_LAST",   PHRASEA_KW_LAST,   CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_KW_FIRST",  PHRASEA_KW_FIRST,  CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_OP_EXCEPT", PHRASEA_OP_EXCEPT, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_OP_NEAR",   PHRASEA_OP_NEAR,   CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_OP_BEFORE", PHRASEA_OP_BEFORE, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_OP_AFTER",  PHRASEA_OP_AFTER,  CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_OP_IN",     PHRASEA_OP_IN,     CONST_CS | CONST_PERSISTENT);

	REGISTER_LONG_CONSTANT("PHRASEA_OP_COLON",  PHRASEA_OP_COLON,  CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_OP_EQUAL",  PHRASEA_OP_EQUAL,  CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_OP_NOTEQU", PHRASEA_OP_NOTEQU, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_OP_GT",     PHRASEA_OP_GT,     CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_OP_LT" ,    PHRASEA_OP_LT,     CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_OP_GEQT",   PHRASEA_OP_GEQT,   CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_OP_LEQT",   PHRASEA_OP_LEQT,   CONST_CS | CONST_PERSISTENT);

	REGISTER_LONG_CONSTANT("PHRASEA_KEYLIST",   PHRASEA_KEYLIST,   CONST_CS | CONST_PERSISTENT);

	REGISTER_LONG_CONSTANT("PHRASEA_MULTIDOC_DOCONLY",  PHRASEA_MULTIDOC_DOCONLY,  CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_MULTIDOC_REGONLY",  PHRASEA_MULTIDOC_REGONLY,  CONST_CS | CONST_PERSISTENT);

	REGISTER_LONG_CONSTANT("PHRASEA_ORDER_DESC",  PHRASEA_ORDER_DESC,  CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_ORDER_ASC",   PHRASEA_ORDER_ASC,   CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("PHRASEA_ORDER_ASK",   PHRASEA_ORDER_ASK,   CONST_CS | CONST_PERSISTENT);


#ifdef ZTS
# if MYSQL_VERSION_ID >= 40000
// mysql_thread_init();
# endif
#endif





#if UUID_VARIANT_NCS
	REGISTER_LONG_CONSTANT("UUID_VARIANT_NCS", UUID_VARIANT_NCS, CONST_PERSISTENT | CONST_CS);
#endif /* UUID_VARIANT_NCS */
#if UUID_VARIANT_DCE
	REGISTER_LONG_CONSTANT("UUID_VARIANT_DCE", UUID_VARIANT_DCE, CONST_PERSISTENT | CONST_CS);
#endif /* UUID_VARIANT_DCE */
#if UUID_VARIANT_MICROSOFT
	REGISTER_LONG_CONSTANT("UUID_VARIANT_MICROSOFT", UUID_VARIANT_MICROSOFT, CONST_PERSISTENT | CONST_CS);
#endif /* UUID_VARIANT_MICROSOFT */
#if UUID_VARIANT_OTHER
	REGISTER_LONG_CONSTANT("UUID_VARIANT_OTHER", UUID_VARIANT_OTHER, CONST_PERSISTENT | CONST_CS);
#endif /* UUID_VARIANT_OTHER */
	REGISTER_LONG_CONSTANT("UUID_TYPE_DEFAULT", 0, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_TYPE_TIME", UUID_TYPE_DCE_TIME, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_TYPE_DCE", UUID_TYPE_DCE_RANDOM, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_TYPE_NAME", UUID_TYPE_DCE_TIME, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_TYPE_RANDOM", UUID_TYPE_DCE_RANDOM, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_TYPE_NULL", -1, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_TYPE_INVALID", -42, CONST_PERSISTENT | CONST_CS);




	return SUCCESS;
}
// -----------------------------------------------------------------------------


PHP_RINIT_FUNCTION(phrasea2)
{
	//	zend_printf("PHP_RINIT_FUNCTION\n");
	PHRASEA2_G(global_session) = NULL;
	PHRASEA2_G(global_epublisher) = NULL;

#ifdef PHP_WIN32
	DWORD tempPathLen;
	tempPathLen = GetTempPath(1023, PHRASEA2_G(tempPath));
	if(tempPathLen>0 || tempPathLen<1023)
	{
//		tempPathBuffer[tempPathLen] = '\0';
	}
	else
	{
		PHRASEA2_G(tempPath)[0] = '\\';
		PHRASEA2_G(tempPath)[1] = '\0';
	}
#else
	strcpy(PHRASEA2_G(tempPath), "/tmp/");
#endif

	TRACELOG("RINIT");

	return SUCCESS;
}


PHP_RSHUTDOWN_FUNCTION(phrasea2)
{
//	zend_printf("PHP_RSHUTDOWN_FUNCTION\n");
	if(PHRASEA2_G(global_session))
	{
		delete PHRASEA2_G(global_session);
		PHRASEA2_G(global_session) = NULL;
	}
	if(PHRASEA2_G(global_epublisher))
	{
		delete PHRASEA2_G(global_epublisher);
		PHRASEA2_G(global_epublisher) = NULL;
	}

	TRACELOG("RSHUTDOWN");

	return SUCCESS;
}


PHP_MSHUTDOWN_FUNCTION(phrasea2)
{
	UNREGISTER_INI_ENTRIES();
	return SUCCESS;
}


PHP_MINFO_FUNCTION(phrasea2)
{
	char buf[1000];
	php_info_print_table_start();
	php_info_print_table_header(2, "phrasea2 support", "enabled" );
	php_info_print_table_row(2, "Version", QUOTE(PHDOTVERSION) );
	
	sprintf(buf, "OK ( client info : %s )", mysql_get_client_info());
	php_info_print_table_row(2, "MYSQL support", buf);
	
#ifdef PGSUPPORT
	sprintf(buf, "OK ( version %s )", "???"); // PG_VERSION);
	php_info_print_table_row(2, "PostgreSQL support", buf);
#else
	php_info_print_table_row(2, "NO PostgreSQL support", "");
#endif

#ifdef MYSQLENCODE
	php_info_print_table_row(2, "SQL connection charset", QUOTE(MYSQLENCODE) );
#else
	php_info_print_table_row(2, "SQL connection charset", "default" );
#endif
	FILE *fp_test=NULL;
	char *fname;
	bool test = false;

	int l = strlen(PHRASEA2_G(tempPath))
			+ 9			// "_phrasea."
			+ strlen("fakeukey")
			+ 9			// ".answers."
			+ 33		// zsession
			+1;			// '\0'

	if( (fname = (char *)EMALLOC(l)) )
	{
		sprintf(fname, "%s_phrasea.%s.test.%ld.bin", PHRASEA2_G(tempPath), "fakeukey", 666);
		if( (fp_test = fopen(fname, "ab")) )
		{
			fclose(fp_test);
			test = true;
		}

		php_info_print_table_row(3, "temp DIR", PHRASEA2_G(tempPath), (test?"OK":"BAD") );

		EFREE(fname);
	}

	php_info_print_table_end();

	DISPLAY_INI_ENTRIES();
}

PHP_FUNCTION(phrasea_info)
{
	if(ZEND_NUM_ARGS()!= 0)
	{
		WRONG_PARAM_COUNT;
	}
	else
	{
		char fname[1000];
		FILE *fp_test;
		array_init(return_value);
		add_assoc_string(return_value, (char *)"version", QUOTE(PHDOTVERSION), TRUE);
		add_assoc_string(return_value, (char *)"mysql_client", (char *)(mysql_get_client_info()), TRUE);
		add_assoc_string(return_value, (char *)"temp_dir", PHRASEA2_G(tempPath), TRUE);
		sprintf(fname, "%s_test.bin", PHRASEA2_G(tempPath));
		if( (fp_test = fopen(fname, "ab")) )
		{
			fclose(fp_test);
			remove(fname);
			add_assoc_bool(return_value, (char *)"temp_writable", true);
		}
		else
		{
			add_assoc_bool(return_value, (char *)"temp_writable", false);
		}
		SQLCONN *epublisher = PHRASEA2_G(global_epublisher);
		if(epublisher && epublisher->isok())
		{
			add_assoc_string(return_value, (char *)"cnx_ukey", epublisher->ukey, TRUE);
		}
		else
		{
			add_assoc_bool(return_value, (char *)"cnx_ukey", FALSE);
		}
	}
}


PHP_FUNCTION(phrasea_conn)
{
	char *zhost, *zuser, *zpasswd, *zdbname;
	int zhost_len, zuser_len, zpasswd_len, zdbname_len;
	int zport;

	if(ZEND_NUM_ARGS()!=5)
	{
		WRONG_PARAM_COUNT;
	}
	else
	{
		if(zend_parse_parameters(5 TSRMLS_CC, (char *)"slsss"
														, &zhost, &zhost_len
														, &zport
														, &zuser, &zuser_len
														, &zpasswd, &zpasswd_len
														, &zdbname, &zdbname_len) == FAILURE)
		{
			RETURN_FALSE;
		}

		// zend_printf("phrasea_conn(host='%s' port=%i user='%s' passwd='%s' dbname='%s')<br>\n", zhost, zport, zuser, zpasswd, zdbname);

		if(PHRASEA2_G(global_epublisher))
			delete(PHRASEA2_G(global_epublisher));

		PHRASEA2_G(global_epublisher) = new SQLCONN(zhost, zport, zuser, zpasswd, zdbname, (char *)"MYSQL");
		if(PHRASEA2_G(global_epublisher->isok()))
		{
			RETURN_TRUE;
		}
		else
		{
			delete(PHRASEA2_G(global_epublisher));
			PHRASEA2_G(global_epublisher) = NULL;
			RETURN_FALSE;
		}
	}
}
