#include "base_header.h"

#include "../php_phrasea2.h"



PHP_FUNCTION(phrasea_emptyw) 
{
  long zsession = -1, zbaseid = -1;
	if(ZEND_NUM_ARGS()==2)
	{
		if(zend_parse_parameters(2 TSRMLS_CC, (char *)"ll", &zsession, &zbaseid) == FAILURE)
		{
			RETURN_FALSE;
		}
	}
	else
		WRONG_PARAM_COUNT;
/*
	if(!global_session || global_session->get_session_id() != zsession)
	{
		// zend_printf("bad global session : restoring<br>\n");
		if(CACHE_SESSION *tmp_session = new CACHE_SESSION(0))
		{
			if(tmp_session->restore(zsession))
			{
				if(global_session)
					delete global_session;
				global_session = tmp_session;
			}
		}
	}
*/
	if(!PHRASEA2_G(global_session) || PHRASEA2_G(global_session)->get_session_id() != zsession)
	{
		// la session n'existe pas, on ne peut pas continuer
		RETURN_FALSE;
	}

	SQLCONN *conn = PHRASEA2_G(global_session)->connect(zbaseid);
	if(conn)
	{
		SQLRES res(conn);
		if(res.query((char *)"SELECT word FROM emptyw"))
		{
			SQLROW *row;
			array_init(return_value);
			while(row = res.fetch_row())
			{
				add_assoc_long(return_value, row->field(0), 1);
			}
		}
	}
}
