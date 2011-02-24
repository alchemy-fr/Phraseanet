#include "base_header.h"

#include "../php_phrasea2.h"



ZEND_FUNCTION(phrasea_subdefs) 
{
  int namelen;
  char *name = NULL;
  zval zsession, zbaseid, zrid;
  int id = -1;
  bool conn_ok = TRUE;
	if(ZEND_NUM_ARGS()==3)
	{
		if(zend_parse_parameters(3 TSRMLS_CC, (char *)"lll", &zsession, &zbaseid, &zrid) == FAILURE)
		{
			RETURN_FALSE;
		}
	}
	else
	{
		if(ZEND_NUM_ARGS()==4)
		{
			if(zend_parse_parameters(4 TSRMLS_CC, (char *)"llls", &zsession, &zbaseid, &zrid, &name, &namelen) == FAILURE)
			{
				RETURN_FALSE;
			}
			if(namelen > 63)
				namelen = 63;
		}
		else
			WRONG_PARAM_COUNT;
	}
/*
	if(!PHRASEA2_G(global_session) || PHRASEA2_G(global_session)->get_session_id() != Z_LVAL(zsession))
	{
		zend_printf("bad global session : restoring<br>\n");
		if(CACHE_SESSION *tmp_session = new CACHE_SESSION(0))
		{
			if(tmp_session->restore(Z_LVAL(zsession)))
			{
				if(global_session)
					delete global_session;
				global_session = tmp_session;
			}
		}
	}
*/
	if(!PHRASEA2_G(global_session) || PHRASEA2_G(global_session)->get_session_id() != Z_LVAL(zsession))
	{
		// la session n'existe pas, on ne peut pas continuer
		RETURN_FALSE;
	}
	// on cherche la base dans le cache
	// global_session->dump();

	SQLCONN *conn = PHRASEA2_G(global_session)->connect(Z_LVAL(zbaseid));
	if(conn)
	{
		char sql[256];
		SQLRES res(conn);
		if(name)
		{
			char namez[64];
			memcpy(namez, name, namelen);
			namez[namelen] = '\0';
			sprintf(sql, "SELECT name, baseurl, file, width, height, mime, path, size, substit, type, sha256, bitly, credate, moddate FROM record LEFT JOIN subdef ON subdef.record_id=record.record_id WHERE record.record_id=%li AND name='%s'", Z_LVAL(zrid), namez);
		}
		else
		{
			sprintf(sql, "SELECT name, baseurl, file, width, height, mime, path, size, substit, type, sha256, bitly, credate, moddate FROM record LEFT JOIN subdef ON subdef.record_id=record.record_id WHERE record.record_id=%li", Z_LVAL(zrid));
		}
		// zend_printf("SQL : %s<br>\n", sql);

	//	RETURN_FALSE;
	//	return;
		if(res.query(sql))
		{
			SQLROW *row;
			zval *zanswer;
			array_init(return_value);
			while(row = res.fetch_row())
			{
				if(!row->field(0))
					continue;

				MAKE_STD_ZVAL(zanswer);
				array_init(zanswer);
				if(row->field(1))
					add_assoc_string(zanswer, (char *)"baseurl", row->field(1), true);
				else
					add_assoc_null(zanswer, (char *)"baseurl");

				if(row->field(2))
					add_assoc_string(zanswer, (char *)"file", row->field(2), true);
				else
					add_assoc_null(zanswer, (char *)"file");

				if(row->field(3))
					add_assoc_long(zanswer, (char *)"width", atol(row->field(3)));
				else
					add_assoc_null(zanswer, (char *)"width");
				
				if(row->field(4))
					add_assoc_long(zanswer, (char *)"height", atol(row->field(4)));
				else
					add_assoc_null(zanswer, (char *)"height");
				
				if(row->field(5))
					add_assoc_string(zanswer, (char *)"mime", row->field(5), true);
				else
					add_assoc_null(zanswer, (char *)"mime");
				
				if(row->field(6))
					add_assoc_string(zanswer, (char *)"path", row->field(6), true);
				else
					add_assoc_null(zanswer, (char *)"path");
				
				if(row->field(7))
					add_assoc_long(zanswer, (char *)"size", atol(row->field(7)));
				else
					add_assoc_null(zanswer, (char *)"size");
				
				if(row->field(8))
					add_assoc_long(zanswer, (char *)"substit", atol(row->field(8)));
				else
					add_assoc_null(zanswer, (char *)"substit");
				
				if(row->field(9))
					add_assoc_string(zanswer, (char *)"type", row->field(9), true);
				else
					add_assoc_null(zanswer, (char *)"type");
					
				if(row->field(10))
					add_assoc_string(zanswer, (char *)"sha256", row->field(10), true);
				else
					add_assoc_null(zanswer, (char *)"sha256");
					
				if(row->field(11))
					add_assoc_string(zanswer, (char *)"bitly", row->field(11), true);
				else
					add_assoc_null(zanswer, (char *)"bitly");
					
				if(row->field(12))
					add_assoc_string(zanswer, (char *)"credate", row->field(12), true);
				else
					add_assoc_null(zanswer, (char *)"credate");
					
				if(row->field(13))
					add_assoc_string(zanswer, (char *)"moddate", row->field(13), true);
				else
					add_assoc_null(zanswer, (char *)"moddate");

				add_assoc_zval(return_value, row->field(0), zanswer);
			}
		}
	}
}


