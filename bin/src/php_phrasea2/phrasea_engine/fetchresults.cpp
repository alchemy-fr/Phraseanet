#include "base_header.h"

#include "../php_phrasea2.h"
#include "phrasea_clock_t.h"


typedef struct	hbal
				{
					unsigned long offset;
					bool closing;
				}
				HBAL;

char THEX[] = "0123456789ABCDEF";

ZEND_FUNCTION(phrasea_fetch_results) 
{
  SQLCONN *epublisher = PHRASEA2_G(global_epublisher);
  long zsession, zfirstanswer, znanswers;
  long getxml = false;
  char *markin=NULL, *markout=NULL;
  int markin_l=0, markout_l=0;
  zval *zanswers, *zanswer;
  char tmpstr[1024];	// buffer pour formater les messages 'courts' (messages d'erreur...)
  CHRONO chrono;
	if(ZEND_NUM_ARGS()==3)
	{
		if(zend_parse_parameters(3 TSRMLS_CC, (char *)"lll", &zsession, &zfirstanswer, &znanswers) == FAILURE)
		{
			RETURN_FALSE;
		}
	}
	else
	{
		if(ZEND_NUM_ARGS()==4)
		{
			if(zend_parse_parameters(4 TSRMLS_CC, (char *)"lllb", &zsession, &zfirstanswer, &znanswers, &getxml) == FAILURE)
			{
				RETURN_FALSE;
			}
		}
		else
		{
			if(ZEND_NUM_ARGS()==6)
			{
				if(zend_parse_parameters(6 TSRMLS_CC, (char *)"lllbss", &zsession, &zfirstanswer, &znanswers, &getxml, &markin, &markin_l, &markout, &markout_l) == FAILURE)
				{
					RETURN_FALSE;
				}
			}
			else
				WRONG_PARAM_COUNT;
		}
	}

	// on a besoin de l'objet session uniquement si on demande le xml
	if(getxml)
	{
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
	}

	sprintf(tmpstr, "UPDATE cache SET nact=nact+1, lastaccess=NOW() WHERE session_id=%li", zsession);
	// zend_printf("tmpstr=%s<br>\n", tmpstr);
	if(epublisher->query(tmpstr) && (epublisher->affected_rows() == 1))
	{	
		SQLRES res(epublisher);
		if(zfirstanswer < 1)
			zfirstanswer = 1;

		CACHE_ANSWER *panswer = NULL;
		FILE *fp_answers = NULL;
		unsigned int nanswers_incache = 0;


		char *fname;
		int l = strlen(PHRASEA2_G(tempPath))
				+ 9			// "_phrasea."
				+ strlen(epublisher->ukey)
				+ 9			// ".answers."
				+ 33		// zsession
				+1;			// '\0'
		if( (fname = (char *)EMALLOC(l)) )
		{
			sprintf(fname, "%s_phrasea.%s.answers.%ld.bin", PHRASEA2_G(tempPath), epublisher->ukey, zsession);

			startChrono(chrono);
			if( (fp_answers = fopen(fname, "rb")) )
			{
				unsigned int answer_index0 = zfirstanswer-1;
				if( (fseek(fp_answers, answer_index0 * sizeof(CACHE_ANSWER), SEEK_SET)) == 0)
				{
					unsigned int nanswers_toread = znanswers;
					if(panswer = (CACHE_ANSWER *)EMALLOC(nanswers_toread * sizeof(CACHE_ANSWER)))
					{
						nanswers_incache = fread(panswer, sizeof(CACHE_ANSWER), nanswers_toread, fp_answers);

						CACHE_SPOT *pspot = NULL;
						FILE *fp_spots = NULL;
						unsigned int nspots_incache = 0;
						unsigned int spot_index0 = 0;

						sprintf(fname, "%s_phrasea.%s.spots.%ld.bin", PHRASEA2_G(tempPath), epublisher->ukey, zsession);

						if( (fp_spots = fopen(fname, "rb")) )
						{
							spot_index0 = panswer[0].spots_index;
							if( fseek(fp_spots, spot_index0 * sizeof(CACHE_SPOT), SEEK_SET) == 0 )
							{
								unsigned int nspots_toread = 0;
								for(unsigned int a=0; a < nanswers_incache; a++)
									nspots_toread += panswer[a].nspots;

								if( pspot = (CACHE_SPOT *)EMALLOC(nspots_toread * sizeof(CACHE_SPOT)) )
								{
									nspots_incache = fread(pspot, sizeof(CACHE_SPOT), nspots_toread, fp_spots);
								}
							}
							fclose(fp_spots);
						}

						array_init(return_value);

						add_assoc_double(return_value, (char *)"time_readCache", stopChrono(chrono));

						char hex_status[16+1];
						unsigned long long llstatus;

						MAKE_STD_ZVAL(zanswers);
						array_init(zanswers);

						for(unsigned int a=0; a < nanswers_incache; a++)
						{
							MAKE_STD_ZVAL(zanswer);
							array_init(zanswer);

							add_assoc_long(zanswer, (char *)"base_id", panswer[a].bid);
							add_assoc_long(zanswer, (char *)"record_id", panswer[a].rid);
							add_assoc_long(zanswer, (char *)"parent_record_id", panswer[a].prid);
							llstatus = panswer[a].llstatus;
							memset(hex_status, '0', 17);
							register char *p;
							for(p=(hex_status+15); llstatus; p--)
							{
								*p = THEX[llstatus & 0x000000000000000F];
								llstatus >>= 4;
							}
							add_assoc_stringl(zanswer, (char *)"status", hex_status, 16, TRUE);

							// to debug -----------
//							add_assoc_long(zanswer, (char *)"_spots_index", panswer[a].spots_index);
//							add_assoc_long(zanswer, (char *)"_nspots", panswer[a].nspots);
							// --------------------

							if(getxml)
							{
	// zend_printf("nspots = %d \n", nspots);
	//RETURN_FALSE;
	//nspots = 0;
			startChrono(chrono);
								SQLCONN *conn = PHRASEA2_G(global_session)->connect(panswer[a].bid);
			add_assoc_double(zanswer, (char *)"time_dboxConnect", stopChrono(chrono));

								if(conn)
								{
									SQLRES res(conn);
									sprintf(tmpstr, "SELECT xml FROM record WHERE record_id=%i", panswer[a].rid);
			startChrono(chrono);
									if(res.query(tmpstr))
									{
			add_assoc_double(zanswer, (char *)"time_xmlQuery", stopChrono(chrono));
										SQLROW *row;
										char *xml;
			startChrono(chrono);
										if(row = res.fetch_row())
										{
			add_assoc_double(zanswer, (char *)"time_xmlFetch", stopChrono(chrono));
											unsigned long *siz = res.fetch_lengths();
											unsigned long xmlsize = siz[0]+1;
											// zend_printf("xmlsize=%li<br>\n", xmlsize);
											// on compte les spots
											unsigned long s0 = (panswer[a].spots_index - spot_index0);

											// on dimensionne le xml highlight�
											unsigned int nspots = panswer[a].nspots;

											if(xml = (char *)EMALLOC(xmlsize + (nspots * (markin_l+markout_l))))
											{
												memcpy(xml, row->field(0), xmlsize);
// zend_printf("bid=%li, rid=%li ; xmlsize=%ld<br>\n", panswer[a].bid, panswer[a].rid, xmlsize);

												if(nspots>0 && markin && markout)
												{
													HBAL *h;
													if(h = (HBAL *)EMALLOC(2 * nspots * sizeof(HBAL)))
													{
														// on trie les spots en d�croissant
														unsigned int s, ss;
														unsigned long t;
														bool b;
														for(s=0; s < nspots; s++)
														{
															h[s*2].offset = pspot[s0+s].start;
															h[s*2].closing = false;
															h[(s*2) + 1].offset = pspot[s0+s].start + pspot[s0+s].len;
															h[(s*2) + 1].closing = true;
														}
														for(s=0; s<(nspots*2)-1; s++)
														{
															for(ss=s+1; ss<(nspots*2); ss++)
															{
																if(h[s].offset < h[ss].offset)
																{
																	t = h[s].offset;
																	h[s].offset = h[ss].offset;
																	h[ss].offset = t;
																	b = h[s].closing;
																	h[s].closing = h[ss].closing;
																	h[ss].closing = b;
																}
															}
														}
														// on ins�re les balises
														for(s=0; s<nspots*2; s++)
														{
// zend_printf("bid=%li, rid=%li ; fetch : h[%li] = {%li , %i}<br>\n", panswer[a].bid, panswer[a].rid, s, h[s].offset, h[s].closing);
															if(h[s].closing && markout_l>0)
															{
																memmove(xml+h[s].offset+markout_l, xml+h[s].offset, xmlsize-h[s].offset);
																memcpy(xml+h[s].offset, markout, markout_l);
																xmlsize += markout_l;
															}
															else
															{
																if(!h[s].closing && markin_l>0)
																{
																	memmove(xml+h[s].offset+markin_l, xml+h[s].offset, xmlsize-h[s].offset);
																	memcpy(xml+h[s].offset, markin, markin_l);
																	xmlsize += markin_l;
																}
															}
														}
														EFREE(h);
													}
												}
												add_assoc_string(zanswer, (char *)"xml", xml, true);

												EFREE(xml);
											}

										}
									}
								}
							}
							add_next_index_zval(zanswers, zanswer);
						}
						add_assoc_zval(return_value, (char *)"results", zanswers);

						if(pspot)
							EFREE(pspot);
						EFREE(panswer);
					}
				}
				fclose(fp_answers);
			}
			EFREE(fname);
		}
	}
}

