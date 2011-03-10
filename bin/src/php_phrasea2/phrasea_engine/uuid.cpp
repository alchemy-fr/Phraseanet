/*
	This part of the extension is imported from the php-uuid PECL extension written by Hartmut Holzgraefe and released under GPL 2.1

	Authors: Hartmut Holzgraefe <hartmut@php.net>


*/


#include "base_header.h"

#include "../php_phrasea2.h"



#ifndef UUID_TYPE_DCE_TIME
#ifdef __APPLE__
/* UUID Type definitions */
#define UUID_TYPE_DCE_TIME   1
#define UUID_TYPE_DCE_RANDOM 4
#endif /* __MACOS__ */
#endif /* UUID_TYPE_DCE_TIME */

ZEND_FUNCTION(phrasea_uuid_create)
{
	long uuid_type = 0;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|l", &uuid_type) == FAILURE) {
		return;
	}

	do {
		uuid_t uuid;
		char uuid_str[37];

		switch(uuid_type) {
		  case UUID_TYPE_DCE_TIME:
			uuid_generate_time(uuid);
			break;
		  case UUID_TYPE_DCE_RANDOM:
			uuid_generate_random(uuid);
			break;
		  case UUID_TYPE_DEFAULT:
			uuid_generate(uuid);
			break;
		  default:
			php_error_docref(NULL TSRMLS_CC,
							 E_WARNING,
							 "Unknown/invalid UUID type '%ld' requested, using default type instead",
							 uuid_type);
			uuid_generate(uuid);
			break;
		}

		uuid_unparse(uuid, uuid_str);

		RETURN_STRING(uuid_str, 1);
	} while (0);
}


/* {{{ proto bool uuid_is_valid(string uuid)
  Check whether a given UUID string is a valid UUID */
ZEND_FUNCTION(phrasea_uuid_is_valid)
{

	const char * uuid = NULL;
	int uuid_len = 0;



	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &uuid, &uuid_len) == FAILURE) {
		return;
	}

	do {
		uuid_t u;
		RETURN_BOOL(0 == uuid_parse(uuid, u));
	} while (0);
}
/* }}} uuid_is_valid */


/* {{{ proto int uuid_compare(string uuid1, string uuid2)
  Compare two UUIDs */
ZEND_FUNCTION(phrasea_uuid_compare)
{

	const char * uuid1 = NULL;
	int uuid1_len = 0;
	const char * uuid2 = NULL;
	int uuid2_len = 0;



	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss", &uuid1, &uuid1_len, &uuid2, &uuid2_len) == FAILURE) {
		return;
	}

	do {
		uuid_t u1, u2;

		if(uuid_parse(uuid1, u1)) RETURN_FALSE;
		if(uuid_parse(uuid2, u2)) RETURN_FALSE;

		RETURN_LONG(uuid_compare(u1, u2));
	} while (0);
}
/* }}} uuid_compare */


/* {{{ proto bool uuid_is_null(string uuid)
  Check wheter an UUID is the NULL UUID 00000000-0000-0000-0000-000000000000 */
ZEND_FUNCTION(phrasea_uuid_is_null)
{

	const char * uuid = NULL;
	int uuid_len = 0;



	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &uuid, &uuid_len) == FAILURE) {
		return;
	}

	do {
		uuid_t u;

		if(uuid_parse(uuid, u)) RETURN_FALSE;

		RETURN_BOOL(uuid_is_null(u));
	} while (0);
}
/* }}} uuid_is_null */


ZEND_FUNCTION(phrasea_uuid_parse)
{

	const char * uuid = NULL;
	int uuid_len = 0;



	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &uuid, &uuid_len) == FAILURE) {
		return;
	}

	do {
		uuid_t uuid_bin;

			if (uuid_parse(uuid, uuid_bin)) {
				RETURN_FALSE;
			}

			RETURN_STRINGL((char *)uuid_bin, sizeof(uuid_t), 1);
	} while (0);
}
/* }}} uuid_parse */


/* {{{ proto string uuid_unparse(string uuid)
   */
ZEND_FUNCTION(phrasea_uuid_unparse)
{

	const char * uuid = NULL;
	int uuid_len = 0;



	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &uuid, &uuid_len) == FAILURE) {
		return;
	}

	do {
		char uuid_txt[37];

			if (uuid_len != sizeof(uuid_t)) {
				RETURN_FALSE;
			}

			uuid_unparse((unsigned char *)uuid, uuid_txt);

			RETURN_STRINGL(uuid_txt, 36, 1);
	} while (0);
}
/* }}} uuid_unparse */

