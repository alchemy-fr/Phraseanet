#include "base_header.h"

#include "../php_phrasea2.h"



ZEND_FUNCTION(phrasea_list_bases)
{
  SQLCONN *epublisher = PHRASEA2_G(global_epublisher);
  char sql[1024];

	ZVAL_BOOL(return_value, FALSE);
	if(ZEND_NUM_ARGS() != 0)
	{
		WRONG_PARAM_COUNT;
	}

// zend_printf("-------\n");
	if(!epublisher)
		RETURN_FALSE;

	// on cherche la liste des bases publi�es
	SQLRES res(epublisher);
// 	if(res.query("SELECT base_id, server_host, server_port, server_sqlengine, server_dbname, server_user, server_pwd, server_coll_id FROM bas WHERE active>0 ORDER BY server_host, server_port, server_dbname"))
	if(res.query((char *)"SELECT base_id, host, port, dbname, user, pwd, server_coll_id, sbas.sbas_id, viewname FROM (sbas INNER JOIN bas ON bas.sbas_id=sbas.sbas_id) WHERE active>0 ORDER BY sbas.ord, sbas.sbas_id, bas.ord"))
	{
	  long last_sbas_id=0, sbas_id;
	  char *viewname;
	  SQLROW *row;
	  SQLCONN *conn = NULL;
	  long basid;
	  
// zend_printf("0\n");
		if(CACHE_SESSION *tmp_session = new CACHE_SESSION(0, epublisher))
		{
// zend_printf("1\n");
			CACHE_BASE *cache_base = NULL;
			while(row = res.fetch_row())
			{
// zend_printf("2\n");
				basid = atol(row->field(0));
				sbas_id = atol(row->field(7));
				viewname = (row->field(8) && strlen(row->field(8))>0) ? row->field(8) : row->field(3);
				if(sbas_id != last_sbas_id)
				{
// zend_printf("3\n");
					last_sbas_id = sbas_id;

					// on a chang� de base phrasea

					if(conn)
						delete conn;

					int engine = PHRASEA_MYSQLENGINE;
					conn = new SQLCONN(row->field(1), atoi(row->field(2)), row->field(4), row->field(5), row->field(3)); 

					if(conn && conn->isok())
					{
// zend_printf("4\n");
						SQLRES res2(conn);
						if(res2.query((char *)"SELECT value AS struct FROM pref WHERE prop='structure' LIMIT 1;"))
						{
							SQLROW *row2 = res2.fetch_row();
							if(row2)
								cache_base = tmp_session->addbase(basid, row->field(1), atol(row->field(2)), row->field(4), row->field(5), row->field(3), row2->field(0), sbas_id, viewname, true);
							else
								cache_base = tmp_session->addbase(basid, row->field(1), atol(row->field(2)), row->field(4), row->field(5), row->field(3), NULL,           sbas_id, viewname, true);
						}
						else
							cache_base = tmp_session->addbase(basid, row->field(1), atol(row->field(2)), row->field(4), row->field(5), row->field(3), NULL, sbas_id, viewname, true);
					}
					else
					{
						cache_base = tmp_session->addbase(basid, row->field(1), atol(row->field(2)), row->field(4), row->field(5), row->field(3), NULL, sbas_id, viewname, false);
					}
				}

				// ici pour chaque base / collections
				if(conn && conn->isok())
				{
// zend_printf("5\n");
					SQLRES res(conn);
					long collid = atol(row->field(6));
			//		sprintf(sql, "SELECT asciiname, phserver_host, phserver_port, prefs FROM coll WHERE coll_id=%s", row->field(6));
					sprintf(sql, "SELECT asciiname, prefs FROM coll WHERE coll_id=%s", row->field(6));
					if(res.query(sql))
					{
						SQLROW *row = res.fetch_row();

//						zend_printf("asciiname='%s'<br>\n" , row->field(0) );

						if(cache_base)
							cache_base->addcoll(collid, basid, row->field(0), (char *)(row->field(1)?row->field(1):""), false);
					}
				}
			}
			// tmp_session->dump();
// zend_printf("6\n");
			if(conn)
				delete conn;

			// on s�rialize la session tmp en php
			tmp_session->serialize_php(return_value, true);		// on liste TOUT (y compris les offline et les non registered)

			delete tmp_session;
		}
		else
			RETURN_FALSE;
	}
	else
		RETURN_FALSE;
}

ZEND_FUNCTION(phrasea_clear_cache)
{
  SQLCONN *epublisher = PHRASEA2_G(global_epublisher);
  zval zsesid;
  char sql[1024];
  int sesid = -1;

	ZVAL_BOOL(return_value, FALSE);
	if(ZEND_NUM_ARGS()==1)
	{
		if(zend_parse_parameters(1 TSRMLS_CC, (char *)"l", &zsesid) == FAILURE)
			RETURN_FALSE;
	}
	else
		WRONG_PARAM_COUNT;

	if(epublisher)
	{
		if(Z_LVAL(zsesid) != 0)
		{
			// on a pass� une session, on v�rifie si elle existe
//			sprintf(sql, "UPDATE cache SET nact=nact+1, lastaccess=NOW(), answers='', spots='' WHERE session_id=%ld", Z_LVAL(zsesid));
			sprintf(sql, "UPDATE cache SET nact=nact+1, lastaccess=NOW() WHERE session_id=%ld", Z_LVAL(zsesid));
			if(epublisher->query(sql))
			{
				if(epublisher->affected_rows() == 1)
				{
					char *fname;
//zend_printf("uky=%s (l=%d)<br/>\n", epublisher->ukey, strlen(epublisher->ukey));
//zend_printf("tmp=%s<br/>\n", PHRASEA2_G(tempPath));
// zend_printf("tmp=%s (l=%d)<br/>\n", tempPathBuffer, strlen(tempPathBuffer));
//RETURN_FALSE;
					int l = strlen(PHRASEA2_G(tempPath))
							+ 9			// "_phrasea."
							+ strlen(epublisher->ukey)
							+ 9			// ".answers."
							+ 33		// zsession
							+1;			// '\0'


					if( (fname = (char *)EMALLOC(l)) )
					{
//						sprintf(fname, "%s_phrasea.answers.%ld.bin", tempPathBuffer, Z_LVAL(zsesid));
						sprintf(fname, "%s_phrasea.%s.answers.%ld.bin", PHRASEA2_G(tempPath), epublisher->ukey, Z_LVAL(zsesid));
						remove(fname);
//						sprintf(fname, "%s_phrasea.spots.%ld.bin", tempPathBuffer, Z_LVAL(zsesid));
						sprintf(fname, "%s_phrasea.%s.spots.%ld.bin", PHRASEA2_G(tempPath), epublisher->ukey, Z_LVAL(zsesid));
						remove(fname);
						EFREE(fname);
					}

					if(CACHE_SESSION *tmp_session = new CACHE_SESSION(0, epublisher))
					{
						if(tmp_session->restore(Z_LVAL(zsesid)))
						{
							if(PHRASEA2_G(global_session))
								delete PHRASEA2_G(global_session);
							PHRASEA2_G(global_session) = tmp_session;
							PHRASEA2_G(global_session)->serialize_php(return_value, false);	// on n'inclue pas les offline et les non registered
							sesid = Z_LVAL(zsesid);
							return;
						}
					}
				}
			}
		}
	}
	RETURN_FALSE;
}


ZEND_FUNCTION(phrasea_create_session)
{
  SQLCONN *epublisher = PHRASEA2_G(global_epublisher);
  zval zusrid;
  char sql[1024];
  int sesid = -1;
 //zend_printf("entree dans phrasea_open_session (global_session=%lx)\n", global_session);
	ZVAL_BOOL(return_value, FALSE);

	if(ZEND_NUM_ARGS()==1)
	{
		if(zend_parse_parameters(1 TSRMLS_CC, (char *)"l", &zusrid) == FAILURE)
			RETURN_FALSE;
	}
	else
		WRONG_PARAM_COUNT;

	if(epublisher)
	{
		// on cr�e une nouvelle session
		if(epublisher->query((char *)"LOCK TABLES uids WRITE"))
		{
			if(epublisher->query((char *)"UPDATE uids SET uid=uid+1 WHERE name='SESSION'"))
			{
				if(epublisher->affected_rows() == 1)
				{
					SQLRES res(epublisher);
					if(res.query((char *)"SELECT uid FROM uids WHERE name='SESSION'"))
					{
						SQLROW *row = res.fetch_row();
						if(row)
						{
							sesid = atol(row->field(0));
							epublisher->query((char *)"UNLOCK TABLES");
							sprintf(sql, (char *)"INSERT INTO cache (session_id, nact, lastaccess, answers, spots, session, usr_id) VALUES (%i, 0, NOW(), '', '', '', %li)", sesid, Z_LVAL(zusrid));
							if(epublisher->query(sql))
							{
								ZVAL_LONG(return_value, sesid);
							}
							else
								sesid = -1;
							
						}
						else
							epublisher->query((char *)"UNLOCK TABLES");
					}
					else
						epublisher->query((char *)"UNLOCK TABLES");
				}
				else
					epublisher->query((char *)"UNLOCK TABLES");
			}
			else
				epublisher->query((char *)"UNLOCK TABLES");
		}
	}
	if(sesid != -1)
	{
		// on a une session, on cherche la liste des bases publi�es
		SQLRES res(epublisher);
//		if(res.query("SELECT base_id, server_host, server_port, server_sqlengine, server_dbname, server_user, server_pwd, server_coll_id FROM bas WHERE active>0 ORDER BY server_host, server_port, server_dbname"))
		if(res.query((char *)"SELECT base_id, host, port, dbname, user, pwd, server_coll_id, sbas.sbas_id, viewname  FROM (sbas INNER JOIN bas ON sbas.sbas_id=bas.sbas_id) WHERE active>0 ORDER BY sbas.ord, sbas.sbas_id, bas.ord"))
		{
		  long last_sbas_id=0, sbas_id;
		  char *viewname;
		  SQLROW *row;
		  SQLCONN *conn = NULL;
		  long basid;
		  
			if(CACHE_SESSION *tmp_session = new CACHE_SESSION(sesid, epublisher))
			{
				CACHE_BASE *cache_base = NULL;
				while(row = res.fetch_row())
				{
					basid = atol(row->field(0));
					sbas_id = atol(row->field(7));
					viewname = (row->field(8) && strlen(row->field(8))>0) ? row->field(8) : row->field(3);
					if(sbas_id != last_sbas_id)
					{
						last_sbas_id = sbas_id;

						// on a chang� de base phrasea
						if(conn)
							delete conn;

						int engine = PHRASEA_MYSQLENGINE;
						conn = new SQLCONN(row->field(1), atoi(row->field(2)), row->field(4), row->field(5), row->field(3)); 

						if(conn && conn->isok())
						{
							SQLRES res2(conn);
							if(res2.query((char *)"SELECT value AS struct FROM pref WHERE prop='structure' LIMIT 1;"))
							{
								SQLROW *row2 = res2.fetch_row();
								if(row2)
									cache_base = tmp_session->addbase(basid, row->field(1), atoi(row->field(2)), row->field(4), row->field(5), row->field(3), row2->field(0), sbas_id, viewname, true);
								else
									cache_base = tmp_session->addbase(basid, row->field(1), atoi(row->field(2)), row->field(4), row->field(5), row->field(3), NULL,           sbas_id, viewname, true);
							}
							else
							{
								cache_base = tmp_session->addbase(basid, row->field(1), atoi(row->field(2)), row->field(4), row->field(5), row->field(3), NULL, sbas_id, viewname, true);
							}
						//	zend_printf("Connection OK<br>\n");
						}
						else
						{
							// si la connexion est rat�e, on ne l'inclue m�me pas dans les bases !
						//	zend_printf("Connection BAD<br>\n");
						}
					}

					// ici pour chaque base / collections
					if(conn && conn->isok())
					{
						SQLRES res(conn);
						long collid = atol(row->field(6));
					//	sprintf(sql, "SELECT asciiname, phserver_host, phserver_port, prefs FROM coll WHERE coll_id=%s", row->field(7));
						sprintf(sql, "SELECT asciiname, prefs FROM coll WHERE coll_id=%s", row->field(6));
						if(res.query(sql))
						{
							SQLROW *row = res.fetch_row();

							if(cache_base)
								cache_base->addcoll(collid, basid, row->field(0), (char *)(row->field(1)?row->field(1):""), false);
						}
					}
				}

				if(conn)
					delete conn;

				// on s�rialize la nouvelle session dans le sql, et on la garde en global
				if(PHRASEA2_G(global_session))
					delete PHRASEA2_G(global_session);
				PHRASEA2_G(global_session) = tmp_session;
				PHRASEA2_G(global_session)->save();

				PHRASEA2_G(global_session)->serialize_php(return_value, false);		// on n'inclue pas les offline et les non registered
				
				//on retourne le numero de session
				ZVAL_LONG(return_value, sesid);
			}
		}
		else
			ZVAL_LONG(return_value, sesid);
	}
	else
		RETURN_FALSE;
}

ZEND_FUNCTION(phrasea_open_session)
{
  SQLCONN *epublisher = PHRASEA2_G(global_epublisher);
  zval zsesid;
  zval zusrid;
  char sql[1024];
  long sesid = -1;
// zend_printf("entree dans phrasea_open_session (global_session=%lx)<br>", global_session);
	ZVAL_BOOL(return_value, FALSE);

	if(ZEND_NUM_ARGS()==2)
	{
		if(zend_parse_parameters(2 TSRMLS_CC, (char *)"ll", &zsesid, &zusrid) == FAILURE)
			RETURN_FALSE;
	}
	else
		WRONG_PARAM_COUNT;
	sesid = Z_LVAL(zsesid);
	if(epublisher)
	{
		if(sesid != 0)
		{
//zend_printf("LINE %i <br/>\n", __LINE__);
		//	sprintf(sql, "UPDATE cache SET nact=nact+1, lastaccess=NOW(), answers='', spots='' WHERE session_id=%li", Z_LVAL(zsesid));
			sprintf(sql, "UPDATE cache SET nact=nact+1, lastaccess=NOW() WHERE session_id=%li AND usr_id=%li", sesid, Z_LVAL(zusrid));
			if(epublisher->query(sql))
			{
//zend_printf("LINE %i <br/>\n", __LINE__);
				if(epublisher->affected_rows() == 1)
				{
//zend_printf("LINE %i <br/>\n", __LINE__);
					if(CACHE_SESSION *tmp_session = new CACHE_SESSION(0, epublisher))
					{
//zend_printf("LINE %i <br/>\n", __LINE__);
						if(tmp_session->restore(sesid))
						{
//zend_printf("LINE %i <br/>\n", __LINE__);
//RETURN_FALSE;
							if(tmp_session->get_session_id() == sesid)
							{
//zend_printf("LINE %i <br/>\n", __LINE__);
								if(PHRASEA2_G(global_session))
									delete PHRASEA2_G(global_session);
								PHRASEA2_G(global_session) = tmp_session;
								PHRASEA2_G(global_session)->serialize_php(return_value, false);	// on n'inclue pas les offline et les non registered
							}
							return;
						}							
					}
				}
			}
		}
	}
}

ZEND_FUNCTION(phrasea_register_base)
{
  SQLCONN *epublisher = PHRASEA2_G(global_epublisher);
  zval zsesid, zbaseid, zsave;
  char *zuser=NULL, *zpwd=NULL;
  int zuserlen, zpwdlen;
  int sesid = -1;

	ZVAL_BOOL(return_value, FALSE);
	if(ZEND_NUM_ARGS() == 5)
	{
		if(zend_parse_parameters(5 TSRMLS_CC, (char *)"llssb", &zsesid, &zbaseid, &zuser, &zuserlen, &zpwd, &zpwdlen, &zsave) == FAILURE)
			RETURN_FALSE;
	}
	else if(ZEND_NUM_ARGS() == 4)
	{
		if(zend_parse_parameters(4 TSRMLS_CC, (char *)"llss", &zsesid, &zbaseid, &zuser, &zuserlen, &zpwd, &zpwdlen) == FAILURE)
			RETURN_FALSE;
		Z_LVAL(zsave) = TRUE;
	}
	else if(ZEND_NUM_ARGS() == 2)
	{
		if(zend_parse_parameters(2 TSRMLS_CC, (char *)"ll", &zsesid, &zbaseid) == FAILURE)
			RETURN_FALSE;
		Z_LVAL(zsave) = TRUE;
	}
	else
	{
		WRONG_PARAM_COUNT;
	}
	if(epublisher && Z_LVAL(zsesid) != -1)
	{
		if(PHRASEA2_G(global_session))
		{
			if(PHRASEA2_G(global_session)->get_session_id() == Z_LVAL(zsesid))
			{
/* ======== au cas ou on vient de cr�er une coll  ===================
if(! PHRASEA2_G(global_session)->connect(zbaseid))
{
	SQLRES res(epublisher);
	SQLCONN *conntmp = NULL;
	CACHE_BASE *cache_base = PHRASEA2_G(global_session)->getCacheBase();
	sprintf(sql, "SELECT base_id, host, port, sqlengine, dbname, user, pwd, server_coll_id, sbas.sbas_id, sbas.thesaurus_id, viewname  FROM (sbas INNER JOIN bas ON sbas.sbas_id=bas.sbas_id) WHERE active>0 AND base_id=%D  ORDER BY sbas.ord, sbas.sbas_id, bas.ord", zbaseid );
	if(res.query(sql))
	{
		SQLROW *row = res.fetch_row();
		conntmp = new SQLCONN(row->field(1), atoi(row->field(2)), row->field(5), row->field(6), row->field(4), row->field(3));
		if(conntmp && conntmp->isok())
		{

			SQLRES res(conntmp);
			long collid = atol(row->field(7));
			sprintf(sql, "SELECT asciiname, prefs FROM coll WHERE coll_id=%s", row->field(7));
			if(res.query(sql))
			{
				SQLROW *row = res.fetch_row();
				if(cache_base)
				{
					cache_base->addcoll(collid, zbaseid, row->field(0), (char *)(row->field(1)?row->field(1):""), false);
				}
			}
		}
	}
} =============================================================== */
				// on se connecte sur la base demand�e
				SQLCONN *conn = PHRASEA2_G(global_session)->connect(Z_LVAL(zbaseid));
				if(conn)
				{
					// ici normalement on check le user/pwd, mais pour l'instant on fait semblant
				//	if(zuser && zpwd && strcmp((const char *)zuser, "alchemy")==0 && strcmp((const char *)zpwd, "alchemy")==0)
				//	{
						PHRASEA2_G(global_session)->set_registered(Z_LVAL(zbaseid), true);
						if(Z_BVAL(zsave))
							PHRASEA2_G(global_session)->save();
						// global_session->dump();
						ZVAL_BOOL(return_value, TRUE);
				//	}
				}
				else
				{
//					 zend_printf("phrasea_register_base ERR 1\n");
				}
			}
			else
			{
//				 zend_printf("phrasea_register_base ERR 3\n");
			}
		}
		else
		{
//			 zend_printf("phrasea_register_base ERR 0\n");
		}
	}
	else
	{
//		 zend_printf("phrasea_register_base ERR 2\n");
	}
}

ZEND_FUNCTION(phrasea_save_session)
{
  SQLCONN *epublisher = PHRASEA2_G(global_epublisher);
  zval zsesid;

	ZVAL_BOOL(return_value, FALSE);
	if(ZEND_NUM_ARGS() == 1)
	{
		if(zend_parse_parameters(1 TSRMLS_CC, (char *)"l", &zsesid) == FAILURE)
			RETURN_FALSE;
	}
	else
	{
		WRONG_PARAM_COUNT;
	}
	if(epublisher && Z_LVAL(zsesid) != -1)
	{
		if(PHRASEA2_G(global_session))
		{
			if(PHRASEA2_G(global_session)->get_session_id() == Z_LVAL(zsesid))
			{
				PHRASEA2_G(global_session)->save();
				ZVAL_BOOL(return_value, TRUE);
			}
			else
			{
				// zend_printf("phrasea_register_base ERR 3\n");
			}
		}
		else
		{
			// zend_printf("phrasea_register_base ERR 0\n");
		}
	}
	else
	{
		// zend_printf("phrasea_register_base ERR 2\n");
	}
}


ZEND_FUNCTION(phrasea_close_session)
{
  SQLCONN *epublisher = PHRASEA2_G(global_epublisher);
  zval zsesid;
  char sql[256];

	ZVAL_BOOL(return_value, FALSE);
	if(ZEND_NUM_ARGS()==1)
	{
		if(zend_parse_parameters(1 TSRMLS_CC, (char *)"l", &zsesid) == FAILURE)
			RETURN_FALSE;
	}
	else
		WRONG_PARAM_COUNT;

	if(epublisher)
	{
		// on a pass� une session, on la supprime du cache
		sprintf(sql, "DELETE FROM cache WHERE session_id=%li", Z_LVAL(zsesid));
		if(epublisher->query(sql))
		{
			if(epublisher->affected_rows() == 1)
			{
				char *fname;
				int l = strlen(PHRASEA2_G(tempPath))
						+ 9			// "_phrasea."
						+ strlen(epublisher->ukey)
						+ 9			// ".answers."
						+ 33		// zsession
						+1;			// '\0'
				if( (fname = (char *)EMALLOC(l)) )
				{
//						sprintf(fname, "%s_phrasea.answers.%ld.bin", tempPathBuffer, Z_LVAL(zsesid));
					sprintf(fname, "%s_phrasea.%s.answers.%ld.bin", PHRASEA2_G(tempPath), epublisher->ukey, Z_LVAL(zsesid));
					remove(fname);
//						sprintf(fname, "%s_phrasea.spots.%ld.bin", tempPathBuffer, Z_LVAL(zsesid));
					sprintf(fname, "%s_phrasea.%s.spots.%ld.bin", PHRASEA2_G(tempPath), epublisher->ukey, Z_LVAL(zsesid));
					remove(fname);
					EFREE(fname);
				}
				ZVAL_BOOL(return_value, TRUE);
			}
		}
		// on en profite pour virer les sessions expir�es
		// epublisher->query("DELETE FROM cache WHERE DATE_ADD(lastaccess, INTERVAL 5 MINUTE)<NOW()");
	}
}

