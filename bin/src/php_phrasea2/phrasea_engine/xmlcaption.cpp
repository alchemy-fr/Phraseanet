#include "base_header.h"

#include "../php_phrasea2.h"



ZEND_FUNCTION(phrasea_xmlcaption) 
{
  int highlightlen;
  char *highlight = NULL;
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
			if(zend_parse_parameters(4 TSRMLS_CC, (char *)"llls", &zsession, &zbaseid, &zrid, &highlight, &highlightlen) == FAILURE)
			{
				RETURN_FALSE;
			}
			if(highlightlen > 63)
				highlightlen = 63;
		}
		else
			WRONG_PARAM_COUNT;
	}
/*
	if(!global_session || global_session->get_session_id() != Z_LVAL(zsession))
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

	RETVAL_FALSE;

	SQLCONN *conn = PHRASEA2_G(global_session)->connect(Z_LVAL(zbaseid));
	if(conn)
	{
		char sql[256];
		SQLRES res(conn);
//		if(name)
//		{
//			char namez[64];
//			memcpy(namez, name, namelen);
//			namez[namelen] = '\0';
//		}
		sprintf(sql, "SELECT xml FROM record WHERE record_id=%li", Z_LVAL(zrid));
//		zend_printf("SQL : %s<br>\n", sql);
		if(res.query(sql))
		{
			SQLROW *row;
			if(row = res.fetch_row())
			{
				RETVAL_STRING(row->field(0), true);
			}
		}
	}
}


ZEND_FUNCTION(phrasea_status) 
{
  int highlightlen;
  char *highlight = NULL;
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
			if(zend_parse_parameters(4 TSRMLS_CC, (char *)"llls", &zsession, &zbaseid, &zrid, &highlight, &highlightlen) == FAILURE)
			{
				RETURN_FALSE;
			}
			if(highlightlen > 63)
				highlightlen = 63;
		}
		else
			WRONG_PARAM_COUNT;
	}
/*
	if(!global_session || global_session->get_session_id() != Z_LVAL(zsession))
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

	RETVAL_FALSE;

	SQLCONN *conn = PHRASEA2_G(global_session)->connect(Z_LVAL(zbaseid));
	if(conn)
	{
		char sql[256];
		SQLRES res(conn);
//		if(name)
//		{
//			char namez[64];
//			memcpy(namez, name, namelen);
//			namez[namelen] = '\0';
//		}
		sprintf(sql, "SELECT BIN(status) FROM record WHERE record_id=%li", Z_LVAL(zrid));
//		zend_printf("SQL : %s<br>\n", sql);
		if(res.query(sql))
		{
			SQLROW *row;
			if(row = res.fetch_row())
			{
				RETVAL_STRING(row->field(0), true);
			}
		}
	}
}

/*
ZEND_FUNCTION(phrasea_setstatus) 
{
  int maskandlen;
  char *maskand = NULL;
  int maskorlen;
  char *maskor = NULL;
  zval zsession, zbaseid, zrid;
  int id = -1;
  bool conn_ok = TRUE;
	if(ZEND_NUM_ARGS()==4)
	{
		if(zend_parse_parameters(5 TSRMLS_CC, "lllss", &zsession, &zbaseid, &zrid, &maskand, &maskandlen, &maskor, &maskorlen) == FAILURE)
		{
			RETURN_FALSE;
		}
	}
	else
		WRONG_PARAM_COUNT;

	if(!PHRASEA2_G(global_session) || PHRASEA2_G(global_session)->get_session_id() != Z_LVAL(zsession))
	{
		// la session n'existe pas, on ne peut pas continuer
		RETURN_FALSE;
	}

	RETVAL_FALSE;
	SQLCONN *conn = PHRASEA2_G(global_session)->connect(Z_LVAL(zbaseid));
	if(conn)
	{
	  char *escapedmaskand=NULL, *escapedmaskand=NULL, *sql;
	  int l;
		l = conn->escape_string(xml, xmllen, NULL);	// on obtient la taille maximum escap�e
		if( (escapedmaskand = (char *)EMALLOC(l_and)) && (escapedmaskor = (char *)EMALLOC(l_or)) )
		{
			l_and = conn->escape_string(maskand, maskandlen, escapedmaskand);	// on obtient la taille r�elle
			l_or  = conn->escape_string(maskor , maskorlen,  escapedmaskor);	// on obtient la taille r�elle
			if(sql = (char *)EMALLOC(l_and + l_or + sizeof("UPDATE record SET moddate=NOW(), status=((status & _) | _) WHERE record_id=???????????")))
			{
				sprintf(sql, "UPDATE record SET moddate=NOW(), status=((status & %s) | %s) WHERE record_id=%i", escapedmaskand, escapedmaskor, Z_LVAL(zrid));

				if(conn->query(sql))
					RETVAL_TRUE;

				EFREE(sql);
			}
		}
		if(escapedmaskand
			EFREE(escapedxml);
	}
}
*/

ZEND_FUNCTION(phrasea_setstatus) 
{
  int maskandlen;
  char *maskand = NULL;
  int maskorlen;
  char *maskor = NULL;
  zval zsession, zbaseid, zrid;
  int id = -1;
  bool conn_ok = TRUE;
	if(ZEND_NUM_ARGS()==5)
	{
		if(zend_parse_parameters(5 TSRMLS_CC, (char *)"lllss", &zsession, &zbaseid, &zrid, &maskand, &maskandlen, &maskor, &maskorlen) == FAILURE)
		{
			RETURN_FALSE;
		}
	}
	else
		WRONG_PARAM_COUNT;

	if(!PHRASEA2_G(global_session) || PHRASEA2_G(global_session)->get_session_id() != Z_LVAL(zsession))
	{
		// la session n'existe pas, on ne peut pas continuer
		RETURN_FALSE;
	}

	RETVAL_FALSE;
	SQLCONN *conn = PHRASEA2_G(global_session)->connect(Z_LVAL(zbaseid));
	if(conn)
	{
	  char *sql;
	  int l=0;
		if(sql = (char *)EMALLOC(maskandlen + maskorlen + sizeof("UPDATE record SET moddate=NOW(), status=((status & _) | _) WHERE record_id=???????????")))
		{
			l += sprintf(sql+l, "UPDATE record SET moddate=NOW(), status=((status & " );
			memcpy(sql+l, maskand, maskandlen);
			l += maskandlen;
			l += sprintf(sql+l, ") | " );
			memcpy(sql+l, maskor, maskorlen);
			l += maskandlen;
			l += sprintf(sql+l, ") WHERE record_id=%li",  Z_LVAL(zrid) );

			// zend_printf("SQL : %s<br>\n", sql);
			if(conn->query(sql))
				RETVAL_TRUE;
			EFREE(sql);
		}
	}
}

ZEND_FUNCTION(phrasea_setxmlcaption) 
{
  int xmllen;
  char *xml = NULL;
  zval zsession, zbaseid, zrid;
  int id = -1;
  bool conn_ok = TRUE;
// zend_printf("phrasea_setxmlcaption !<br>\n");
	if(ZEND_NUM_ARGS()==4)
	{
		if(zend_parse_parameters(4 TSRMLS_CC, (char *)"llls", &zsession, &zbaseid, &zrid, &xml, &xmllen) == FAILURE)
		{
			RETURN_FALSE;
		}
	}
	else
		WRONG_PARAM_COUNT;
// zend_printf("phrasea_setxmlcaption : %i<br>\n", xmllen);
// RETURN_TRUE;

	if(!PHRASEA2_G(global_session) || PHRASEA2_G(global_session)->get_session_id() != Z_LVAL(zsession))
	{
		// la session n'existe pas, on ne peut pas continuer
		RETURN_FALSE;
	}
	// on cherche la base dans le cache
	// global_session->dump();

	RETVAL_FALSE;
	SQLCONN *conn = PHRASEA2_G(global_session)->connect(Z_LVAL(zbaseid));
	if(conn)
	{
	  char *escapedxml, *sql;
	  int l;
		if(sql = (char *)EMALLOC(sizeof("DELETE FROM idx WHERE record_id=??????????????????????")))
		{
			sprintf(sql, "DELETE FROM idx WHERE record_id=%li", Z_LVAL(zrid));

			conn->query(sql);

			EFREE(sql);
		}

		l = conn->escape_string(xml, xmllen, NULL);	// on obtient la taille maximum escap�e
		if(escapedxml = (char *)EMALLOC(l))
		{
			l = conn->escape_string(xml, xmllen, escapedxml);	// on obtient la taille r�elle
			if(sql = (char *)EMALLOC(l + sizeof("UPDATE record SET moddate=NOW(), status=status & ~1, xml='_' WHERE record_id=??????????????????????")))
			{
				sprintf(sql, "UPDATE record SET moddate=NOW(), status=status & ~1, xml='%s' WHERE record_id=%li", escapedxml, Z_LVAL(zrid));

				if(conn->query(sql))
					RETVAL_TRUE;

				EFREE(sql);
			}
			EFREE(escapedxml);
		}
	}
}


