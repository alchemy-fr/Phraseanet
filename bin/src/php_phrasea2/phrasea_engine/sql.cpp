#include "base_header.h"
#include "phrasea_clock_t.h"

#include "../php_phrasea2.h"


#define QUOTE(x) _QUOTE(x)
#define _QUOTE(a) #a 

SQLCONN::SQLCONN(char *host, int port, char *user, char *passwd, char *dbname, bool trace)
{
	this->ukey = NULL;
	this->connok = false;
//	if(trace)
//		zend_printf("NEW SQLCONN(host='%s' port=%i user='%s' passwd='%s' dbname='%s' engine='%s')<br>\n", host, port, user, passwd, dbname, engine);
	this->mysql_active_result_id = 0;
	this->sqlengine = PHRASEA_MYSQLENGINE;
	mysql_init(&(this->mysql_conn));

#ifdef MYSQL_OPT_RECONNECT
my_bool reconnect = 1;
mysql_options(&(this->mysql_conn), MYSQL_OPT_RECONNECT, &reconnect);
#else
#endif

	if (mysql_real_connect(&(this->mysql_conn), host, user, passwd, NULL, port, NULL, CLIENT_COMPRESS) != NULL)
	{
#ifdef MYSQLENCODE
		if (mysql_set_character_set(&(this->mysql_conn), QUOTE(MYSQLENCODE)) == 0) 
#endif
		{
			int l = strlen(host) + 1 + 65 + 1 + (dbname ? strlen(dbname) : 0);
			if(this->ukey = (char *)EMALLOC(l))
			{
				sprintf(this->ukey, "%s_%u_%s", host, (unsigned int)port, (dbname?dbname:"") );
				this->connok = true;
				if(dbname && (mysql_select_db(&(this->mysql_conn), dbname) != 0))
				{
					// zend_printf("mysql_select_db failed<br>\n");
					// on a demand� une base � s�lectionner mais echec : on ferme
					mysql_close(&(this->mysql_conn));
//					EFREE(this->ukey);
//					this->ukey = NULL;
					this->connok = false;
				}
				if(this->connok)
				{
				// on cr�e une table temporaire qui contiendra un extrait des droits distants (basusr)
				// cette table est temporaire donc propre � cette connexion, donc propre � cet application-server
				// c'est la responsabilit� du site distant de charger/maintenir la table � jour
				// (par exemple � la connexion d'un user, il doit updater ses droits dans l'application-server sur lequel il se connecte)
			//	char *sql = "CREATE TEMPORARY TABLE IF NOT EXISTS `collusr` (`coll_id` bigint(20) NOT NULL default '0', `htmlname` varchar(50) default NULL, PRIMARY KEY (`coll_id`) ) TYPE=MyISAM";
	//				char *sql = "CREATE TEMPORARY TABLE IF NOT EXISTS `collusr` (`coll_id` bigint(20) NOT NULL default '0', `usr_id` bigint(20) NOT NULL default '0', `mask_and` bigint(20) unsigned default NULL, `mask_xor` bigint(20) unsigned default NULL, PRIMARY KEY (`coll_id`,`usr_id`) ) TYPE=HEAP";
	//				this->query(sql, -1);
				}
			}
		}
	}
}

void *SQLCONN::get_native_conn()
{
	return(&(this->mysql_conn));
}

SQLCONN::~SQLCONN()
{
	if(this->ukey)
		EFREE(this->ukey);
	if(this->connok)
	{
		mysql_close(&(this->mysql_conn));
	}
}

int SQLCONN::escape_string(char *str, int len, char *outbuff)
{
	// size_t tolen;
	if(len == -1)
		len = strlen(str);
	if(outbuff == NULL)
		return((2*len)+1);			// pas de buffer allou� : on retourne la taille requise sans escaper
	return(mysql_real_escape_string(&(this->mysql_conn), outbuff, str, len));
}

bool SQLCONN::query(char *sql, int len)
{
	int r;
//	this->pgnres = -1;
	if(this->connok)
	{
		if(len == -1)
			len = strlen(sql);
		r = mysql_real_query(&(this->mysql_conn), sql, len);
//zend_printf("SQL=%s ; myafr=%li, r=%i<br>\n", sql, mysql_affected_rows(&(this->mysql_conn)), r);
//if(r != 0)
//	zend_printf("ERR:%s<br/>\n", this->error());
		return(r == 0);
	}
	return(false);
}

const char *SQLCONN::error()
{
	if(this->connok)
	{
		return(mysql_error(&(this->mysql_conn)));
	}
	return(NULL);
}

my_ulonglong SQLCONN::affected_rows()
{
	if(this->connok)
	{
		return(mysql_affected_rows(&(this->mysql_conn)));
	}
	return (long)(-1);
}

bool SQLCONN::isok()
{
	return(this->connok);
}

bool SQLRES::query(char *sql)
{
	if(mysql_query(&(parent_conn->mysql_conn), sql) == 0)
	{
		// zend_printf("SQL=%s<br>\n", sql);
		if(this->res)
		{
			mysql_free_result(this->res);
			this->res = NULL;
		}
		if(this->res = mysql_store_result(&(parent_conn->mysql_conn)))
		{
			// !!!!!!!!!!!!!!!!!!!!! conversion de _int64 en int !!!!!!!!!!!!!!!!!
			this->nrows = (int)mysql_num_rows(res);
			this->ncols = (int)mysql_num_fields(res);
		}
		return(true);
	}
	return(false);
}

int SQLRES::get_nrows()
{
	return(this->nrows);
}

SQLRES::SQLRES(SQLCONN *parent_conn)
{
	this->parent_conn = parent_conn;
	this->res = NULL;
	this->sqlrow.parent_res = this;
	this->ncols = 0;
	this->ncols = 0;
}

SQLRES::~SQLRES()
{
	if(this->res)
		mysql_free_result(this->res);
}

SQLROW *SQLRES::fetch_row()
{
	if(this->parent_conn->connok)
	{
		if(this->res)
		{
			this->sqlrow.row = mysql_fetch_row(this->res);
			if(this->sqlrow.row)
				return(&(this->sqlrow));
		}
	}
	return(NULL);
}

unsigned long *SQLRES::fetch_lengths()
{
	if(this->parent_conn->connok)
	{
		if(this->res)
		{
			return(mysql_fetch_lengths(this->res));
		}
	}
	return(NULL);
}

SQLROW::SQLROW()
{
}

SQLROW::~SQLROW()
{
}

char *SQLROW::field(int n)
{
	return(this->row[n]);
}

#define SQLFIELD_RID 0
#define SQLFIELD_PRID 1
#define SQLFIELD_CID 2
#define SQLFIELD_HITSTART 3
#define SQLFIELD_HITLEN 4
#define SQLFIELD_IW 5
#define SQLFIELD_STATUS 6
#define SQLFIELD_SHA256 7



// pose une rqt sql � la connexion phrasea
// si result!=null, les r�sultats sont stock�s dans une entr�e "answers" de ce tableau assoc.
// sinon
// les r�sultats sont stock�s en ram dans le node
void SQLCONN::phrasea_query(char *sql, NODE *n, bool reverse)
{
  ANSWER *answer;
  long rid, lastrid;
  HIT *hit;
  SPOT *spot;
  CHRONO chrono;
  MYSQL_STMT    *stmt;
  MYSQL_BIND    bind[8];
  unsigned long length[8];
  my_bool       is_null[8];
  my_bool       error[8];
  long int_result[6];
  long long llstatus;
  unsigned char sha256[65];

	lastrid = -1;
// zend_printf("szll=%d, MYSQL=%s <br/>\n", sizeof(llstatus), sql);

	startChrono(chrono);

//zend_printf("%d <br/>\n", __LINE__);
	if( (stmt = mysql_stmt_init(&(this->mysql_conn))) )
	{
//zend_printf("%d <br/>\n", __LINE__);
		if( mysql_stmt_prepare(stmt, sql, strlen(sql)) == 0 )
		{
//zend_printf("%d <br/>\n", __LINE__);
			// Execute the SELECT query
			if(mysql_stmt_execute(stmt) == 0)
			{
				n->time_sqlQuery = stopChrono(chrono);

				// Bind the result buffers for all columns before fetching them
				memset(bind, 0, sizeof(bind));

				for(int i=0; i<8; i++)
				{
					// INTEGER COLUMN(S)
					bind[i].buffer_type = MYSQL_TYPE_LONG;
					bind[i].buffer      = (char *)(&int_result[i]);
					bind[i].is_null     = &is_null[i];
					bind[i].length      = &length[i];
					bind[i].error       = &error[i];
				}
				// status column : 64 bits
				bind[SQLFIELD_STATUS].buffer_type   = MYSQL_TYPE_LONGLONG;
				bind[SQLFIELD_STATUS].buffer        = (char *)(&llstatus);

				// sha256 column : 256 bits
				bind[SQLFIELD_SHA256].buffer_type   = MYSQL_TYPE_STRING;
				bind[SQLFIELD_SHA256].buffer_length = 65;
				bind[SQLFIELD_SHA256].buffer        = (char *)sha256;

				// Bind the result buffers
				if (mysql_stmt_bind_result(stmt, bind) == 0)
				{
//zend_printf("%d <br/>\n", __LINE__);
					// Now buffer all results to client (optional step)
					startChrono(chrono);
					if (mysql_stmt_store_result(stmt) != 0)
					{
						//fprintf(stderr, " mysql_stmt_store_result() failed\n");
						//fprintf(stderr, " %s\n", mysql_stmt_error(stmt));
						return;
					}
//zend_printf("%d <br/>\n", __LINE__);
					n->time_sqlStore = stopChrono(chrono);

					ANSWER *tmphead = n->lastanswer;
					startChrono(chrono);
					while(mysql_stmt_fetch(stmt) == 0)
					{
// zend_printf("l=%ld <br/>\n", length[6]);
						rid  = int_result[SQLFIELD_RID];
// zend_printf("%d : rid=%ld<br/>\n", __LINE__, rid);
//						prid = int_result[SQLFIELD_PRID];
//						cid  = int_result[SQLFIELD_CID];
						if(n)
						{
							if(rid != lastrid)
							{
								if(answer = (ANSWER *)(EMALLOC(sizeof(ANSWER))))
								{
									answer->firstspot = answer->lastspot = NULL;
									answer->firsthit  = answer->lasthit = NULL;
									// answer->sqloffsets
									answer->rid       = rid;
									answer->prid      = int_result[SQLFIELD_PRID];
									answer->llstatus  = llstatus;
									answer->cid       = int_result[SQLFIELD_CID];

									if(!is_null[SQLFIELD_SHA256])
										answer->osha256 = sha256;
									else
										answer->osha256 = NULL;

									answer->nextanswer = NULL;
									if(!reverse)
									{
										if(!(n->firstanswer))
											n->firstanswer = answer;
										if(n->lastanswer)
											n->lastanswer->nextanswer = answer;
										n->lastanswer = answer;
									}
									else
									{
										if(!tmphead)
										{
											if(!n->lastanswer)
											{
												n->lastanswer = answer;
											}
											answer->nextanswer = n->firstanswer;
											n->firstanswer = answer;
										}
										else
										{
											answer->nextanswer = tmphead->nextanswer;
											if(!answer->nextanswer)
											{
												n->lastanswer = answer;
											}
											tmphead->nextanswer = answer;
										}
									}
								}
								n->nbranswers++;
								lastrid = rid;
							}
						}
						if(!is_null[SQLFIELD_IW] && answer  && (hit = (HIT *)(EMALLOC(sizeof(HIT)))))
						{
							hit->iws = hit->iwe = int_result[SQLFIELD_IW];
							hit->nexthit = NULL;
							if(!(answer->firsthit))
								answer->firsthit = hit;
							if(answer->lasthit)
								answer->lasthit->nexthit = hit;
							answer->lasthit = hit;
						}
						if(!is_null[SQLFIELD_HITSTART] && !is_null[SQLFIELD_HITLEN] && answer  &&  (spot = (SPOT *)(EMALLOC(sizeof(SPOT)))))
						{
							spot->start = int_result[SQLFIELD_HITSTART];
							spot->len   = int_result[SQLFIELD_HITLEN];
							spot->nextspot = NULL;
							if(!(answer->firstspot))
								answer->firstspot = spot;
							if(answer->lastspot)
								answer->lastspot->nextspot = spot;
							answer->lastspot = spot;
						}
 					}
					n->time_sqlFetch = stopChrono(chrono);
		//			n->sql = sql;
				}
			}
			else
			{
			}
		}
		else
		{
		}
		mysql_stmt_close(stmt);
	}
}




/*
void SQLCONN::phrasea_query(char *sql, NODE *n, zval *zanswers, bool reverse)
{
  MYSQL_RES *myres;
  my_ulonglong nrows = 0;
  MYSQL_ROW myrow;
  ANSWER *answer;
  long cid, rid, prid, lastrid;
  HIT *hit;
  SPOT *spot;
  zval *zanswer, *zspots, *zspot;
  CHRONO chrono, chrono_all;
  MYSQL_STMT    *stmt;
  MYSQL_BIND    bind[6];

	cid = rid = lastrid = -1;
zend_printf("MYSQL=%s <br/>\n", sql);

	startChrono(chrono_all);
	startChrono(chrono);

	if(mysql_query(&(this->mysql_conn), sql) == 0)
	{
		n->time_sqlQuery = stopChrono(chrono);

//zend_printf("<!-- DUREE : %d msec (n->firstanswer=%ld, reverse=%i) -->\n\n", PHRASEA_GET_MS(&sqlin, &sqlout), n->firstanswer, reverse);
		startChrono(chrono);
		if((myres = mysql_store_result(&(this->mysql_conn))) != NULL)
		{
			n->time_sqlStore = stopChrono(chrono);

// zend_printf("nrows = %i\n", mysql_num_rows(myres));
			answer = NULL;
			ANSWER *tmphead = n->lastanswer;

			startChrono(chrono);

			while(myrow = mysql_fetch_row(myres))
			{
				rid  = atoi(myrow[SQLFIELD_RID]);
				prid = atoi(myrow[SQLFIELD_PRID]);
				cid  = atoi(myrow[SQLFIELD_CID]);
				if(n)
				{
// zend_printf("rid=%i : iw=%i, hit.s=%i, hit.l=%i\n", rid, atoi(myrow[iwf]), atoi(myrow[hitstartf]), atoi(myrow[hitlenf]) );
					if(zanswers)
					{
						// on stocke dans la zval
						MAKE_STD_ZVAL(zanswer);
						array_init(zanswer);
						add_assoc_long(zanswer, (char *)"rid", rid);
						add_assoc_long(zanswer, (char *)"prid", prid);
						add_assoc_stringl(zanswer, (char *)"status", myrow[SQLFIELD_STATUS], 16, TRUE);
						add_assoc_long(zanswer, (char *)"cid", cid);
						if(myrow[SQLFIELD_HITSTART] && myrow[SQLFIELD_HITLEN])
						{
							MAKE_STD_ZVAL(zspots);
							array_init(zspots);
							add_assoc_zval(zanswer, (char *)"spots", zspots);
						}
						add_next_index_zval(zanswers, zanswer);
					}
					else
					{
						if(answer = (ANSWER *)(EMALLOC(sizeof(ANSWER))))
						{
							// answer->sqloffsets
							answer->rid = rid;
							answer->prid = prid;
							memcpy(answer->status, myrow[2], 16);
							answer->cid = cid;
							answer->firstspot = answer->lastspot = NULL;
							answer->firsthit = answer->lasthit = NULL;
						//	answer->xml = NULL;
						//	answer->xmllenght = -1;
						//	if(xmlf != -1)
						//	{
						//		lengths = mysql_fetch_lengths(myres);
						//		if(answer->xml = EMALLOC(answer->xmllenght = lengths[xmlf]))
						//			memcpy(answer->xml, myrow[xmlf], answer->xmllenght);
						//	}

							answer->nextanswer = NULL;
							if(!reverse)
							{
								if(!(n->firstanswer))
									n->firstanswer = answer;
								if(n->lastanswer)
									n->lastanswer->nextanswer = answer;
								n->lastanswer = answer;
							}
							else
							{

//zend_printf("<!-- cid=%ld, rid=%ld -->\n", cid, rid);
								if(!tmphead)
								{
									if(!n->lastanswer)
									{
//zend_printf("<!-- 0.0 : n->lastanswer = answer = %li -->\n", answer->rid);
										n->lastanswer = answer;
									}
//zend_printf("<!-- 0.1 : answer->nextanswer = n->firstanswer = %li -->\n", n->firstanswer ? n->firstanswer->rid : 0);
									answer->nextanswer = n->firstanswer;
//zend_printf("<!-- 0.1 : n->firstanswer = answer = %li -->\n", answer->rid );
									n->firstanswer = answer;
								}
								else
								{
//zend_printf("<!-- 1.0 : answer->nextanswer = tmphead->nextanswer = %li -->\n", tmphead->nextanswer ? tmphead->nextanswer->rid : 0 );
									answer->nextanswer = tmphead->nextanswer;
									if(!answer->nextanswer)
									{
//zend_printf("<!-- 1.1 : n->lastanswer = answer = %li -->\n", answer->rid );
										n->lastanswer = answer;
									}
//zend_printf("<!-- 1.2 : tmphead->nextanswer = answer = %li -->\n", answer->rid );
									tmphead->nextanswer = answer;
								}
							}
						}
					}
					n->nbranswers++;
					lastrid = rid;
				}
				if(zanswers)
				{
					// on stocke dans la zval
					if(myrow[SQLFIELD_HITSTART] && myrow[SQLFIELD_HITLEN])
					{
						MAKE_STD_ZVAL(zspot);
						array_init(zspot);
						add_assoc_long(zspot, (char *)"start", atoi(myrow[SQLFIELD_HITSTART]));
						add_assoc_long(zspot, (char *)"len",   atoi(myrow[SQLFIELD_HITLEN]));
						add_next_index_zval(zspots, zspot);
					}
				}
				else
				{
					if(myrow[SQLFIELD_IW] && answer  && (hit = (HIT *)(EMALLOC(sizeof(HIT)))))
					{
						hit->iws = hit->iwe = atoi(myrow[SQLFIELD_IW]);
						hit->nexthit = NULL;
						if(!(answer->firsthit))
							answer->firsthit = hit;
						if(answer->lasthit)
							answer->lasthit->nexthit = hit;
						answer->lasthit = hit;
// zend_printf("  - rid=%i : hit.iws=hit.iwe=%i\n", rid, hit->iws);
					}
					if(myrow[SQLFIELD_HITSTART] && myrow[SQLFIELD_HITLEN] && answer  &&  (spot = (SPOT *)(EMALLOC(sizeof(SPOT)))))
					{
						spot->start = atoi(myrow[SQLFIELD_HITSTART]);
						spot->len   = atoi(myrow[SQLFIELD_HITLEN]);
						spot->nextspot = NULL;
						if(!(answer->firstspot))
							answer->firstspot = spot;
						if(answer->lastspot)
							answer->lastspot->nextspot = spot;
						answer->lastspot = spot;
// zend_printf("  - rid=%i : spot.s=%i, spot.l=%i\n", rid, spot->start, spot->len);
					}
				}
			}
			n->time_sqlFetch = stopChrono(chrono);
//			n->sql = sql;

// for(ANSWER *a=n->firstanswer; a; a=a->nextanswer)
//	zend_printf("<!-- ---------- cid=%ld, rid=%ld -->\n", a->cid, a->rid);
// zend_printf("<!-- ---------- last = %ld -->\n", n->lastanswer->rid);
			mysql_free_result(myres);
		}
	}
	n->time_all = stopChrono(chrono_all);
}
*/
