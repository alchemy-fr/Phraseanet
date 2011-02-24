#include "base_header.h"
#include "phrasea_clock_t.h"

#include "../php_phrasea2.h"

NODE *arr2tree(zval **root, int depth);
NODE *qtree2tree(zval **root, int depth);
void arrdump(zval **root, int depth);
void dumptree(NODE *n);
void freetree(NODE *n);
NODE *poptree(NODE *n);
// int poptree(NODE *n);
void freehits(ANSWER *a);
void freeanswer(ANSWER *a);
void querytree2(NODE *n, int depth, SQLCONN *sqlconn, zval *result, long multidocMode);
void setError(int errtype, char *errstr, int errnum);
void sort_int(int nint, int *tint);
//int PHRASEA_GET_MS(struct _timeb *timestart, struct _timeb *timeend);


ZEND_FUNCTION(phrasea_query2) 
{
  SQLCONN *epublisher = PHRASEA2_G(global_epublisher);
  zval zsession, zsbasid, *zqarray, *zcolllist;
  zval zuserid;

 TRACELOG("hello world I'm phrasea_query2");

  char tmpstr[10240];	// buffer pour formater les messages 'courts' (messages d'erreur...)
  char *zsite;
  int zsitelen;
//  char *zuserid;
//  long zuseridlen;

  int id = -1;
  int i, ncoll, len;
  NODE *query;
  // ANSWER *answer;
  // HIT *hit;
  // SPOT *spot;
  bool conn_ok = TRUE;
  // zval *zanswers, *zanswer, *zspots, *zspot;
  zval **tmp1;
  char *sqlcoll;			// un petit bout de sql qui filtre les collection

  long znoCache = 0;
  long zmultidocMode = PHRASEA_MULTIDOC_DOCONLY;

  char *prec;	// "
  
  COLLID_PAIR *t_collid_pair = NULL;

	switch(ZEND_NUM_ARGS())
	{
		case 8:			// session, baseid, collist, quarray, site, userid, noCache, multidocMode
			if(zend_parse_parameters(8 TSRMLS_CC, (char *)"llaaslbl", &zsession, &zsbasid, &zcolllist, &zqarray,  &zsite, &zsitelen, &zuserid, &znoCache, &zmultidocMode) == FAILURE)
			{
				RETURN_FALSE;
			}
			if(zsitelen > 32)
				zsite[32] = '\0';
//			if(zuseridlen > 64)
//				zuserid[64] = '\0';
			// on construit le bout de sql qui joint record et collusr
			switch(zmultidocMode)
			{
				/* ===== ANCIEN MULTIDOC ===============
                case PHRASEA_MULTIDOC_REPONLY:
					sprintf(sqltrec, "(record INNER JOIN collusr ON site='%s' AND usr_id='%s' AND collusr.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND parent_record_id=0)", zsite, zuserid);
					break;
				case PHRASEA_MULTIDOC_DOCONLY:
					sprintf(sqltrec, "(record INNER JOIN collusr ON site='%s' AND usr_id='%s' AND collusr.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND parent_record_id<>0)", zsite, zuserid);
					break;
				case PHRASEA_MULTIDOC_OLDSTYLE:
					sprintf(sqltrec, "(record INNER JOIN collusr ON site='%s' AND usr_id='%s' AND collusr.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND (parent_record_id=0 OR parent_record_id=record_id))", zsite, zuserid);
					break;
				default:
				case PHRASEA_MULTIDOC_ALL:
					sprintf(sqltrec, "(record INNER JOIN collusr ON site='%s' AND usr_id='%s' AND collusr.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0)", zsite, zuserid);
					break;
				========================================= */


                /* ======== REGROUPEMENT MAI 2006 ==========*/
				case PHRASEA_MULTIDOC_REGONLY:
					// sprintf(sqltrec, "(record INNER JOIN collusr ON site='%s' AND usr_id='%ld' AND collusr.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=record.record_id)", zsite, Z_LVAL(zuserid));
					// sprintf(sqltrec, "(record INNER JOIN _tmpmask ON _tmpmask.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=record.record_id)");
					prec = "record.record_id";
					break;

				default:
				case PHRASEA_MULTIDOC_DOCONLY:
					// sprintf(sqltrec, "(record INNER JOIN collusr ON site='%s' AND usr_id='%ld' AND collusr.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=0)", zsite, Z_LVAL(zuserid));
					//sprintf(sqltrec, "(record INNER JOIN _tmpmask ON _tmpmask.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=0)");
					prec = "0";
					break;
			}
 // zend_printf("<!-- sqltrec=%s  -->\n", sqltrec);
			break;
		default:
			WRONG_PARAM_COUNT;
			break;
	}

//sprintf(sqltrec, "(record)");

/*
	if(!global_session || global_session->get_session_id() != Z_LVAL(zsession))
	{
		// zend_printf("bad global session : restoring<br>\n");
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
//zend_printf("173\n");
//		RETURN_FALSE;
//zend_printf("173 , %ld , %ld \n", PHRASEA2_G(global_session)->get_session_id(), Z_LVAL(zsession));
//		RETURN_FALSE;
	if(!PHRASEA2_G(global_session) || PHRASEA2_G(global_session)->get_session_id() != Z_LVAL(zsession))
	{
		// la session n'existe pas, on ne peut pas continuer
		RETURN_FALSE;
	}
// zend_printf("179 \n");

	SQLRES res(epublisher);


	// on remplace la liste de collections 'locales' (ids de bases) en liste de collections distantes (ids de colls)
	sqlcoll = NULL;
	if(Z_TYPE_PP(&zcolllist) == IS_ARRAY)
	{
		// on calcule la taille pour �crire tous les num�ros de coll distants
		for(ncoll=i=0; TRUE; i++)
		{
			if(zend_hash_index_find(HASH_OF(zcolllist), i, (void **) &tmp1)==SUCCESS  &&  Z_TYPE_PP(tmp1)==IS_LONG)
			{
				long distant_coll_id = PHRASEA2_G(global_session)->get_distant_coll_id(Z_LVAL_P(*tmp1));
				if(distant_coll_id != -1)
					ncoll++;
			}
			else
				break;
		}
		// on alloue et on relit
		len = 0;
		if(t_collid_pair = (COLLID_PAIR *)EMALLOC(ncoll * sizeof(COLLID_PAIR)))
		{
			for(ncoll=i=0; TRUE; i++)
			{
				if(zend_hash_index_find(HASH_OF(zcolllist), i, (void **) &tmp1)==SUCCESS  &&  Z_TYPE_PP(tmp1)==IS_LONG)
				{
					long distant_coll_id = PHRASEA2_G(global_session)->get_distant_coll_id(Z_LVAL_P(*tmp1));
					if(distant_coll_id != -1)
					{
						t_collid_pair[ncoll].local_base_id = Z_LVAL_P(*tmp1);
						t_collid_pair[ncoll].distant_coll_id = distant_coll_id;
						len += sprintf(tmpstr, "%li", distant_coll_id);
						ncoll++;
					}
				//	zend_printf("t_collid_pair[%li]={%i, %li}\n", i, t_collid_pair[i].local_base_id, t_collid_pair[i].distant_coll_id);
				}
				else
					break;
			}
		}
		else
		{
			// ALLOC ERROR
			ncoll = 0;
		}

		if(ncoll > 0)
		{
			// on construit le bout de sql qui filtre sur les collections
			/*
			if(ncoll == 1)
				len += (sizeof(" record.coll_id=")-1) + 1;
			else
				len += (sizeof(" record.coll_id IN (")-1) + (ncoll-1) + (sizeof(")")-1) + 1;
			if(sqlcoll = (char *)(EMALLOC(len)))
			{
				if(ncoll == 1)
					sprintf(sqlcoll, " record.coll_id=%li", t_collid_pair[0].distant_coll_id);
				else
				{
					len = sprintf(sqlcoll, " record.coll_id IN (");
					for(i=0; i<ncoll; i++)
					{
						if(i>0)
							sqlcoll[len++] = ',';
						len += sprintf(sqlcoll+len, "%li", t_collid_pair[i].distant_coll_id);
					}
					sprintf(sqlcoll+len, ")");
				}
			*/
			if(ncoll == 1)
				len += (sizeof(" coll_id=")-1) + 1;
			else
				len += (sizeof(" coll_id IN (")-1) + (ncoll-1) + (sizeof(")")-1) + 1;
			if(sqlcoll = (char *)(EMALLOC(len)))
			{
				if(ncoll == 1)
					sprintf(sqlcoll, " coll_id=%li", t_collid_pair[0].distant_coll_id);
				else
				{
					len = sprintf(sqlcoll, " coll_id IN (");
					for(i=0; i<ncoll; i++)
					{
						if(i>0)
							sqlcoll[len++] = ',';
						len += sprintf(sqlcoll+len, "%li", t_collid_pair[i].distant_coll_id);
					}
					sprintf(sqlcoll+len, ")");
				}


TRACELOG(sqlcoll);
// zend_printf("SQL=%s<br>\n", sqlcoll);

				// on r�cup l'adresse de la base distante
		//		sprintf(tmpstr, "SELECT base_id, server_host, server_port, server_sqlengine, server_dbname, server_user, server_pwd, server_coll_id FROM bas WHERE base_id=%li", Z_LVAL(zbaseid));
				sprintf(tmpstr, "SELECT sbas_id, host, port, sqlengine, dbname, user, pwd FROM sbas WHERE sbas_id=%li", Z_LVAL(zsbasid));
// zend_printf("%s \n", tmpstr);
				if(res.query(tmpstr))
				{
					// zend_printf("SQL=%s<br>\n", tmpstr);
					SQLROW *row = res.fetch_row();
					if(row)
					{
						// on se connecte sur la base distante
						SQLCONN *conn = new SQLCONN(row->field(1), atoi(row->field(2)), row->field(5), row->field(6), row->field(4));
						if(conn && conn->isok())
						{
							sprintf(tmpstr, "CREATE TEMPORARY TABLE _tmpmask SELECT coll_id, mask_xor, mask_and FROM collusr WHERE site='%s' AND usr_id='%ld' AND %s", zsite, Z_LVAL(zuserid), sqlcoll);
							conn->query(tmpstr);
							sprintf(tmpstr, "CREATE INDEX coll_id ON _tmpmask(coll_id)");
							conn->query(tmpstr);

							// on transforme la query en php en arbre de nodes
							query = qtree2tree(&zqarray, 0);

							// ici on interroge phrasea !
							querytree2(query, 0, conn, return_value, zmultidocMode);

							conn->query("DROP TABLE _tmpmask");

							if(!znoCache)
							{
								// on s�rialise les r�sultats en binaire
								ANSWER *answer;
								SPOT *spot;
								int n_answers=0, n_spots=0;

								for(answer=query->firstanswer; answer; answer=answer->nextanswer)
								{
									if(answer->nextanswer && answer->nextanswer->osha256 != answer->osha256)
									{
										ANSWER *a, *pa;
										for(a=(pa=answer->nextanswer)->nextanswer; a; pa=a,a=a->nextanswer)
										{
											if(a->osha256 == answer->osha256)
											{
												pa->nextanswer = a->nextanswer;
												a->nextanswer = answer->nextanswer;
												answer->nextanswer = a;

												a = pa;
											}
										}
									}
								}
//for(answer=query->firstanswer; answer; answer=answer->nextanswer)
//	zend_printf("(%d) : rid=%d, sha='%s'<br/>\n", __LINE__, answer->rid, (const char *)(answer->osha256) ) ;

								for(answer=query->firstanswer; answer; answer=answer->nextanswer)
								{
									n_answers++;
									answer->nspots = 0;
									for(spot=answer->firstspot; spot; spot = spot->nextspot)
									{
										n_spots++;
										answer->nspots++;
									}
								}

								CHRONO chrono;
								startChrono(chrono);

								if(n_answers > 0)
								{
									int answer_binsize, spot_binsize;
									CACHE_ANSWER *answer_binbuff=NULL;
									CACHE_SPOT   *spot_binbuff=NULL;

									answer_binsize = n_answers * sizeof(CACHE_ANSWER);
									spot_binsize   = n_spots   * sizeof(CACHE_SPOT);

									// pour calculer les offsets de spots, il faut savoir combien de spots sont d�j� dans la table
									unsigned int spot_index = 0;

									FILE *fp_spots=NULL, *fp_answers=NULL;
									char *fname;
									int l = strlen(PHRASEA2_G(tempPath))
											+ 9			// "_phrasea."
											+ strlen(epublisher->ukey)
											+ 9			// ".answers."
											+ 33		// zsession
											+1;			// '\0'
									if( (fname = (char *)EMALLOC(l)) )
									{
										if(n_spots > 0)
										{
											sprintf(fname, "%s_phrasea.%s.spots.%ld.bin", PHRASEA2_G(tempPath), epublisher->ukey, Z_LVAL(zsession));
											if( (fp_spots = fopen(fname, "ab")) )
											{
												// to set the offset of spots, we must know how much are already in file
												fseek(fp_spots, 0, SEEK_END);
												spot_index = ftell(fp_spots) / sizeof(CACHE_SPOT);
// zend_printf("%s : ftell=%li ; index=%li <br/>\n", fname, ftell(fp_spots), spot_index);
											}
										}
										sprintf(fname, "%s_phrasea.%s.answers.%ld.bin", PHRASEA2_G(tempPath), epublisher->ukey, Z_LVAL(zsession));
										if( (fp_answers = fopen(fname, "ab")) )
										{
											;
										}
										EFREE(fname);
									}

									if((answer_binbuff = (CACHE_ANSWER *)EMALLOC(answer_binsize)) && (n_spots==0 || (spot_binbuff = (CACHE_SPOT *)EMALLOC(spot_binsize))))
									{
										CACHE_ANSWER *panswer = answer_binbuff;
										CACHE_SPOT   *pspot   = spot_binbuff;
										long current_cid = -1;
										long current_bid = -1;
										for(answer=query->firstanswer; answer; answer=answer->nextanswer)
										{
											if(answer->cid != current_cid)
											{
												// on change de collection, on cherche l'id de la base locale correspondante

												current_bid = -1;
												for(int i=0; i<ncoll; i++)
												{
													if(t_collid_pair[i].distant_coll_id == answer->cid)
													{
														current_bid = t_collid_pair[i].local_base_id;
														break;
													}
												}
											}

//zend_printf("rid=%d  ;  spot_index = %d (nspots=%d) ", answer->rid, spot_index, answer->nspots);
											panswer->rid          = answer->rid;
											panswer->prid         = answer->prid;
											panswer->llstatus     = answer->llstatus;
											panswer->bid          = current_bid;
											panswer->spots_index  = spot_index;
											panswer->nspots       = answer->nspots;
											// spot_offset += answer->nspots * sizeof(CACHE_SPOT);
											spot_index += answer->nspots;
//zend_printf(" -:: spot_index=%d <br/>\n", spot_index);
											for(spot=answer->firstspot; spot; spot = spot->nextspot)
											{
//zend_printf("start=%d, len=%d <br/>\n", spot->start,  spot->len);
												pspot->start = spot->start;
												pspot->len   = spot->len;
//zend_printf("start=%d, len=%d <br/>\n", pspot->start, pspot->len);
												pspot++;
											}
// zend_printf("p_rid=%d  ;  p_spots_index = %d <br/>\n", panswer->rid, panswer->spots_index);
											panswer++;
										}
TRACELOG("hello world I'm phrasea_query2");

										if(fp_answers)
										{
											fwrite((const void *)answer_binbuff, 1, answer_binsize, fp_answers);
											fclose(fp_answers);
										}
										if(fp_spots)
										{
											fwrite((const void *)spot_binbuff, 1, spot_binsize, fp_spots);
											fclose(fp_spots);
										}
										EFREE(answer_binbuff);
									}
									else
									{
										// pb d'allocation !
										if(answer_binbuff)
											EFREE(answer_binbuff);
										if(spot_binbuff)
											EFREE(spot_binbuff);
									}
								}					// if(n_answers > 0)
								add_assoc_double(return_value, (char *)"time_writeCache", stopChrono(chrono));

							}				// if(!noCache)
TRACELOG("hello world I'm phrasea_query2");

							// on lib�re l'arbre
							freetree(query);
TRACELOG("hello world I'm phrasea_query2");
						}
						if(conn)
							delete conn;
					}
				}
TRACELOG("hello world I'm phrasea_query2");
				EFREE(sqlcoll);
			}
		}
		if(t_collid_pair)
			EFREE(t_collid_pair);
	}
TRACELOG("hello world I'm phrasea_query2");
}
