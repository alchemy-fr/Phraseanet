#include "base_header.h"
#include "phrasea_clock_t.h"

#include "../php_phrasea2.h"

void arrdump(zval **root, int depth);
void dumptree(NODE *n);
void freetree(NODE *n);
NODE *poptree(NODE *n);
// int poptree(NODE *n);
void freehits(ANSWER *a);
void freeanswer(ANSWER *a);
// void sort_int(int nint, int *tint);

// true global ok here
static const char *math2sql[] = { (char *)"=", (char *)"<>", (char *)">", (char *)"<", (char *)">=", (char *)"<=" };

char *kwclause(KEYWORD *k)
{
  KEYWORD *k0 = k;
  char *p;
  int l = 0;
  char *s = NULL, c1, c2;
  bool hasmeta;
  int i;
	// on commence par calculer la place n�cessaire � i=0, on �crit � i=1
	for(i=0; i<2; i++)
	{
		l = 0;
		for(k=k0; k; k=k->nextkeyword)
		{
			l += s ? s[l++]='(',0 : 1;		// "("
			hasmeta = FALSE;
			c1 = c2 = '\0';
			for(p=k->kword; !hasmeta && *p; p++)
			{
				if(*p=='*' || *p=='?')
					hasmeta = TRUE;
				else
				{
					if(!c1)
						c1 = *p;
					else
						if(!c2)
							c2 = *p;
				}
			}
			if(hasmeta)
			{
				if(c2)
					l += s ? sprintf(s+l, "k2='%c%c' AND ", c1, c2) : 12;		// "k2='cc' AND "
				else
					if(c1)
						l += s ? sprintf(s+l, "SUBSTRING(k2 FROM 1 FOR 1)='%c' AND ", c1) : 35;	// "SUBSTR(k2,1,1)='c' AND "
//						l += s ? sprintf(s+l, "SUBSTR(k2,1,1)='%c' AND ", c1) : 23;	// "SUBSTR(k2,1,1)='c' AND "
					l += s ? sprintf(s+l, "keyword LIKE '") : 14;			// "keyword LIKE '"
			}
			else
			{
				l += s ? sprintf(s+l, "keyword='") : 9;			// "keyword='";
			}

			for(p=k->kword; *p; p++)
			{
				switch(*p)
				{
					case '\'':
						l += s ? s[l++]='\\',s[l++]='\'',0 : 2;		// "\'"
						break;
					case '*':
						l += s ? s[l++]='%',0 : 1;		// "%"
						break;
					case '?':
						l += s ? s[l++]='_',0 : 1;		// "_"
						break;
					default:
						l += s ? s[l++]= *p,0 : 1;		// "c"
						break;
				}
			}
			l += s ? s[l++]='\'',s[l++]=')',0 : 2;		// "')"
			if(k->nextkeyword)
				l += s ? sprintf(s+l, " OR ") : 4;			// " OR "
		}
		l += s ? s[l++]='\0',0 : 1;		// \0 final
		if(!s && l>0)
		{
			if(!(s = (char *)(EMALLOC(l))))
				break;
		}
	}
	return(s);
}


NODE *qtree2tree(zval **root, int depth)
{
  TSRMLS_FETCH();
  zval **tmp1, **tmp2;
  NODE *n = NULL;
  int i;
  KEYWORD *k;

	if(Z_TYPE_PP(root) == IS_ARRAY)
	{
		// le premier element (num) donne le type de node
		if(zend_hash_index_find(HASH_OF(*root), 0, (void **) &tmp1)==SUCCESS  &&  Z_TYPE_PP(tmp1)==IS_LONG)
		{
			if(n = (NODE *)EMALLOC(sizeof(NODE)))
			{
				// zend_printf("N ");

				n->type = Z_LVAL_P(*tmp1);
				n->firstanswer = n->lastanswer = NULL;
				n->nbranswers = 0;
				n->nleaf = 0;
				n->isempty = FALSE;
				n->time_C = -1;
				n->time_sqlQuery = n->time_sqlStore = n->time_sqlFetch = -1;

				switch(n->type)
				{
					case PHRASEA_KEYLIST:
						n->content.multileaf.firstkeyword = n->content.multileaf.lastkeyword = NULL;
						for(i=1; TRUE; i++)
						{
							if(zend_hash_index_find(HASH_OF(*root), i, (void **) &tmp1)==SUCCESS  &&  Z_TYPE_PP(tmp1)==IS_STRING)
							{
								if(k = (KEYWORD *)(EMALLOC(sizeof(KEYWORD))))
								{
									k->kword = ESTRDUP(Z_STRVAL_P(*tmp1));
									k->nextkeyword = NULL;
									if(!(n->content.multileaf.firstkeyword))
										n->content.multileaf.firstkeyword = k;
									if(n->content.multileaf.lastkeyword)
										n->content.multileaf.lastkeyword->nextkeyword = k;
									n->content.multileaf.lastkeyword = k;
								}
							}
							else
								break;
						}
						break;
					case PHRASEA_KW_LAST:	// last
						n->content.numparm.v = DEFAULTLAST;
						if(zend_hash_index_find(HASH_OF(*root), 1, (void **) &tmp1)==SUCCESS  &&  Z_TYPE_PP(tmp1)==IS_LONG)
						{
							n->content.numparm.v = Z_LVAL_P(*tmp1);
						}
						n->nleaf = 1;
						break;
					case PHRASEA_KW_ALL:	// all
						n->nleaf = 1;
						break;
					case PHRASEA_OP_AND:	// and
					case PHRASEA_OP_OR:	// or
					case PHRASEA_OP_EXCEPT:	// except
					case PHRASEA_OP_EQUAL:
					case PHRASEA_OP_COLON:
					case PHRASEA_OP_NOTEQU:
					case PHRASEA_OP_GT:
					case PHRASEA_OP_LT:
					case PHRASEA_OP_GEQT:
					case PHRASEA_OP_LEQT:
					case PHRASEA_OP_IN:	// in
						n->content.boperator.numparm = 0;
						n->content.boperator.l = n->content.boperator.r = NULL;
						if((zend_hash_index_find(HASH_OF(*root), 1, (void **) &tmp1) == SUCCESS) && (zend_hash_index_find(HASH_OF(*root), 2, (void **) &tmp2) == SUCCESS))
						{
							n->content.boperator.l = qtree2tree(tmp1, depth+1);
							n->content.boperator.r = qtree2tree(tmp2, depth+1);
							n->nleaf = n->content.boperator.l->nleaf + n->content.boperator.r->nleaf;
						}
						break;
					case PHRASEA_OP_NEAR:	// near
					case PHRASEA_OP_BEFORE:	// near
					case PHRASEA_OP_AFTER:	// near
						n->content.boperator.numparm = 12;
						n->content.boperator.l = n->content.boperator.r = NULL;
						i = 1;
						if((zend_hash_index_find(HASH_OF(*root), i, (void **) &tmp1) == SUCCESS)  &&  Z_TYPE_PP(tmp1)==IS_LONG)
						{
							n->content.boperator.numparm = Z_LVAL_P(*tmp1);
							i++;
						}
						if((zend_hash_index_find(HASH_OF(*root), i, (void **) &tmp1) == SUCCESS) && (zend_hash_index_find(HASH_OF(*root), i+1, (void **) &tmp2) == SUCCESS))
						{
							n->content.boperator.l = qtree2tree(tmp1, depth+1);
							n->content.boperator.r = qtree2tree(tmp2, depth+1);
							n->nleaf = n->content.boperator.l->nleaf + n->content.boperator.r->nleaf;
						}
						break;
					default:
//						sprintf(tmpstr, "Unknown node type (%i).", n->type);
//						PHRASEA2_G(connect_error) = ESTRDUP(tmpstr);
//						PHRASEA2_G(connect_errno) = -1;
//						php_error_docref(NULL TSRMLS_CC, E_ERROR, "%s", PHRASEA2_G(connect_error));
						EFREE(n);
						n = NULL;
						break;
				}
			}
		}
		else
		{
			if(n = (NODE *)EMALLOC(sizeof(NODE)))
			{
				n->type = PHRASEA_OP_NULL;
				n->firstanswer = n->lastanswer = NULL;
				n->nbranswers = 0;
				n->nleaf = 0;
				n->isempty = FALSE;
				n->time_C = -1;
				n->time_sqlQuery = n->time_sqlStore = n->time_sqlFetch = -1;
			}
		}
	}
	else
	{
		if(n = (NODE *)EMALLOC(sizeof(NODE)))
		{
			n->type = PHRASEA_OP_NULL;
			n->firstanswer = n->lastanswer = NULL;
			n->nbranswers = 0;
			n->nleaf = 0;
			n->isempty = FALSE;
			n->time_C = -1;
			n->time_sqlQuery = n->time_sqlStore = n->time_sqlFetch = -1;
		}
	}
	return(n);
}


void querytree2(NODE *n, int depth, SQLCONN *sqlconn, zval *result, long multidocMode)
{
  char sql[102400];
  HIT *hit, *hitl, *hitr;
  ANSWER *answer, *al, *ar, *allast, *arlast, *prev_al;
  int lnbranswers, rnbranswers;
  int prox, prox0;
  char *p;
  int l;
  bool sqlok;
  KEYWORD *plk;
  zval *objl = NULL;
  zval *objr = NULL;
  CHRONO chrono_all;
  bool need2tmpmask = false;

 	TSRMLS_FETCH();

	if(result)
	{
		array_init(result);
	}

	sql[0] = '\0';
	startChrono(chrono_all);
	if(n)
	{
		n->time_C = -1;
		n->time_sqlQuery = n->time_sqlStore = n->time_sqlFetch = -1;;

		if(result)
			add_assoc_long(result, (char *)"type", n->type);

		switch(n->type)
		{
			case PHRASEA_OP_NULL:	// question vide
				n->firstanswer = n->lastanswer = NULL;
				n->nbranswers = 0;
				n->nleaf = 0;
				break;
			case PHRASEA_KW_ALL:	// all
				// on effectue la requ�te sql
//				sprintf(sql, "SELECT record.record_id, record.parent_record_id, record.coll_id, NULL AS hitstart, NULL AS hitlen, NULL AS iw, HEX(status) AS status FROM %s ORDER BY record_id DESC", sqltrec);
				sprintf(sql, "SELECT record.record_id, record.parent_record_id, record.coll_id, NULL AS hitstart, NULL AS hitlen, NULL AS iw, status, NULL AS sha256"
						" FROM"
						" (record INNER JOIN _tmpmask ON _tmpmask.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=%s)"
						" ORDER BY record_id DESC"
						, (multidocMode==PHRASEA_MULTIDOC_REGONLY?"record.record_id":"0"));
				sqlconn->phrasea_query(sql, n);
				break;
			case PHRASEA_KW_LAST:	// last
				if(n->content.numparm.v > 0)
				{
					sprintf(sql, "SELECT record.record_id, record.parent_record_id, record.coll_id, NULL AS hitstart, NULL AS hitlen, NULL AS iw, status, NULL AS sha256"
							" FROM"
							" (record INNER JOIN _tmpmask ON _tmpmask.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=%s)"
							" ORDER BY record_id DESC LIMIT %i"
							, (multidocMode==PHRASEA_MULTIDOC_REGONLY?"record.record_id":"0")
							, n->content.numparm.v);
					sqlconn->phrasea_query(sql, n);
				}
				break;
			case PHRASEA_KEYLIST:	// simple word
			case PHRASEA_OP_IN:
				sqlok = FALSE;
				plk = NULL;
				// l = sprintf(sql, "SELECT idx.record_id, iw, record.xml FROM ((kword NATURAL JOIN idx) NATURAL JOIN record) WHERE ");
				if(n->type == PHRASEA_OP_IN)
				{
					if(n->content.boperator.l && n->content.boperator.r && n->content.boperator.l->type==PHRASEA_KEYLIST && n->content.boperator.r->type==PHRASEA_KEYLIST)
					{
						l = sprintf(sql, "SELECT idx.record_id, record.parent_record_id, record.coll_id, hitstart, hitlen, iw, status, NULL AS sha256 FROM"
								" ((kword NATURAL JOIN idx) NATURAL JOIN"
								" (record INNER JOIN _tmpmask ON _tmpmask.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=%s)"
								"), xpath WHERE "
								, (multidocMode==PHRASEA_MULTIDOC_REGONLY?"record.record_id":"0"));
						sqlok = TRUE;
						plk = n->content.boperator.l->content.multileaf.firstkeyword;
					}
				}
				else
				{	// PHRASEA_KEYLIST
					l = sprintf(sql, "SELECT idx.record_id, record.parent_record_id, record.coll_id, hitstart, hitlen, iw, status, NULL AS sha256"
							" FROM"
							" ((kword NATURAL JOIN idx) NATURAL JOIN"
							" (record INNER JOIN _tmpmask ON _tmpmask.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=%s)"
							") WHERE "
							, (multidocMode==PHRASEA_MULTIDOC_REGONLY?"record.record_id":"0"));
					plk = n->content.multileaf.firstkeyword;
				}
// return;
				if(plk && (p = kwclause(plk)))
				{
// zend_printf("<!-- p=%s  -->\n", p);
//zend_flush();
// return;
					l += sprintf(sql+l, "(%s)", p);
					EFREE(p);
					sqlok = TRUE;
					if(plk->nextkeyword)
					{
						MAKE_STD_ZVAL(objl);
						array_init(objl);
						while(plk)
						{
							add_next_index_string(objl, plk->kword, TRUE);
							plk = plk->nextkeyword;
						}
						if(result)
							add_assoc_zval(result, (char *)"keyword", objl);
					}
					else
					{
						if(result)
							add_assoc_string(result, (char *)"keyword", plk->kword, TRUE);
					}
				}
//return;

				if(sqlok)
				{
					if(n->type == PHRASEA_OP_IN)
					{
						if(n->content.boperator.r->content.multileaf.firstkeyword && n->content.boperator.r->content.multileaf.firstkeyword->kword)
						{
							l += sprintf(sql+l, " AND (idx.xpath_id=xpath.xpath_id) AND (xpath REGEXP 'DESCRIPTION\\\\[0\\\\]/%s\\\\[[0-9]+\\\\]') ORDER BY record_id DESC", n->content.boperator.r->content.multileaf.firstkeyword->kword);
							if(result)
								add_assoc_string(result, (char *)"field", n->content.boperator.r->content.multileaf.firstkeyword->kword, TRUE);
						}
					}
					else
					{
						l += sprintf(sql+l, " ORDER BY record_id DESC");
					}
					sqlconn->phrasea_query(sql, n);
				}
				break;
			case PHRASEA_OP_EQUAL:
			case PHRASEA_OP_NOTEQU:
			case PHRASEA_OP_GT:
			case PHRASEA_OP_LT:
			case PHRASEA_OP_GEQT:
			case PHRASEA_OP_LEQT:
				if(n->content.boperator.l && n->content.boperator.r && n->content.boperator.l->type==PHRASEA_KEYLIST && n->content.boperator.r->type==PHRASEA_KEYLIST)
				{
					if(n->content.boperator.l->content.multileaf.firstkeyword && n->content.boperator.r->content.multileaf.firstkeyword)
					{
						if(n->content.boperator.l->content.multileaf.firstkeyword->kword && n->content.boperator.r->content.multileaf.firstkeyword->kword)
						{
							if(result)
							{
								add_assoc_string(result, (char *)"field", n->content.boperator.l->content.multileaf.firstkeyword->kword, TRUE);
								add_assoc_string(result, (char *)"value", n->content.boperator.r->content.multileaf.firstkeyword->kword, TRUE);
							}
							// on effectue la requ�te sql
						//	if(n->content.voperator.strvalue)
						//	{
							sql[0] = '\0';

							char *fname = n->content.boperator.l->content.multileaf.firstkeyword->kword;

							// champ technique sha256 ?
							if(!sql[0] && strcmp(fname, "sha256")==0)
							{
								if(n->type==PHRASEA_OP_EQUAL && strcmp(n->content.boperator.r->content.multileaf.firstkeyword->kword, "sha256")==0)
								{
									// special query "sha256=sha256" (doublons)
//			sprintf(sql, "SELECT record.record_id, record.parent_record_id, record.coll_id, NULL AS hitstart, NULL AS hitlen, NULL AS iw, record.status, record.sha256 FROM"
//					" (record INNER JOIN _tmpmask ON _tmpmask.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=%s)"
//					" INNER JOIN record AS b ON (record.sha256=b.sha256 AND record.coll_id=b.coll_id) WHERE b.record_id!=record.record_id GROUP BY record_id ORDER BY record_id DESC", prec);
									need2tmpmask = true;
									sprintf(sql, "SELECT a.record_id, a.parent_record_id, a.coll_id, NULL AS hitstart, NULL AS hitlen, NULL AS iw, a.status, a.sha256 FROM"
											" (record AS a INNER JOIN _tmpmask  AS xa ON xa.coll_id=a.coll_id AND ((a.status ^ xa.mask_xor) & xa.mask_and)=0 AND a.parent_record_id=%s)"
											" INNER JOIN"
											" (record AS b INNER JOIN _tmpmask2 AS xb ON xb.coll_id=b.coll_id AND ((b.status ^ xb.mask_xor) & xb.mask_and)=0 AND b.parent_record_id=%s)"
											" ON (a.sha256=b.sha256 AND a.record_id!=b.record_id) GROUP BY record_id ORDER BY record_id DESC"
											, (multidocMode==PHRASEA_MULTIDOC_REGONLY?"a.record_id":"0")
											, (multidocMode==PHRASEA_MULTIDOC_REGONLY?"b.record_id":"0"));
//			sprintf(sql, "SELECT a.record_id, a.parent_record_id, a.coll_id, NULL AS hitstart, NULL AS hitlen, NULL AS iw, a.status, a.sha256 FROM"
//					" (record AS a INNER JOIN _tmpmask ON _tmpmask.coll_id=a.coll_id AND ((a.status ^ mask_xor) & mask_and)=0 AND a.parent_record_id=%s)"
//					" INNER JOIN"
//					" (record AS b INNER JOIN _tmpmask AS xb ON xb.coll_id=b.coll_id AND ((b.status ^ mask_xor) & mask_and)=0 AND b.parent_record_id=%s)"
//					" ON (a.sha256=b.sha256 AND a.record_id!=b.record_id) GROUP BY record_id ORDER BY record_id DESC", prec, prec);
								}
								else
								{
									sprintf(sql, "SELECT record.record_id, record.parent_record_id, record.coll_id, NULL AS hitstart, NULL AS hitlen, NULL AS iw, status, record.sha256"
											" FROM"
											" (record INNER JOIN _tmpmask ON _tmpmask.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=%s)"
											" WHERE (sha256%s%s) ORDER BY record_id DESC"
											, (multidocMode==PHRASEA_MULTIDOC_REGONLY?"record.record_id":"0")
											, math2sql[n->type-PHRASEA_OP_EQUAL]
											, n->content.boperator.r->content.multileaf.firstkeyword->kword);
								}
							}

							// champ technique recordid ?
							if(!sql[0] && strcmp(fname, "recordid")==0)
							{
								sprintf(sql, "SELECT record.record_id, record.parent_record_id, record.coll_id, NULL AS hitstart, NULL AS hitlen, NULL AS iw, status, NULL AS sha256"
										" FROM"
										" (record INNER JOIN _tmpmask ON _tmpmask.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=%s)"
										" WHERE (record_id%s%s) ORDER BY record_id DESC"
										, (multidocMode==PHRASEA_MULTIDOC_REGONLY?"record.record_id":"0")
										, math2sql[n->type-PHRASEA_OP_EQUAL]
										, n->content.boperator.r->content.multileaf.firstkeyword->kword);
							}

							// champ technique recordtype ?
							if(!sql[0] && strcmp(fname, "recordtype")==0)
							{
								sprintf(sql, "SELECT record.record_id, record.parent_record_id, record.coll_id, NULL AS hitstart, NULL AS hitlen, NULL AS iw, status, NULL AS sha256"
										" FROM"
										" (record INNER JOIN _tmpmask ON _tmpmask.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=%s)"
										" WHERE (type%s'%s') ORDER BY record_id DESC"
										, (multidocMode==PHRASEA_MULTIDOC_REGONLY?"record.record_id":"0")
										, math2sql[n->type-PHRASEA_OP_EQUAL]
										, n->content.boperator.r->content.multileaf.firstkeyword->kword);
							}

							// champ technique thumbnail ou preview ou document ?
							if(!sql[0] && (strcmp(fname, "thumbnail")==0 || strcmp(fname, "preview")==0 || strcmp(fname, "document")==0))
							{
								char w[] = "NOT(ISNULL(subdef.record_id))";
								if(n->content.boperator.r->content.multileaf.firstkeyword->kword[0]=='0')
									strcpy(w, "ISNULL(subdef.record_id)");
								sprintf(sql, "SELECT record.record_id, record.parent_record_id, record.coll_id, NULL AS hitstart, NULL AS hitlen, NULL AS iw, status, NULL AS sha256 FROM"
										" ((record INNER JOIN _tmpmask ON _tmpmask.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=%s))"
										" LEFT JOIN subdef ON (subdef.name='%s' AND subdef.record_id=record.record_id) WHERE %s ORDER BY record_id DESC"
										, (multidocMode==PHRASEA_MULTIDOC_REGONLY?"record.record_id":"0")
										, fname
										, w);
							}

							// champ technique recordstatus ?
							if(!sql[0] && strcmp(fname, "recordstatus")==0)
							{
#ifdef __no_WIN__
								char buff_and[64];
								char *val;
								xINT64 mask_and;
							//	unsigned long long mask_xor;
								for(val=n->content.boperator.r->content.multileaf.firstkeyword->kword; *val; val++) 
								{
									switch(*val)
									{
										case '0':
											mask_and = (mask_and<<1) && 0xFFFFFFFFFFFFFFFF;
							//				mask_xor = (mask_xor<<1) && 0x0000000000000000;
											break;
										case '1':
											mask_and = (mask_and<<1) && 0xFFFFFFFFFFFFFFFF;
							//				mask_xor = (mask_xor<<1) && 0x0000000000000001;
											break;
										default:
											mask_and = (mask_and<<1) && 0xFFFFFFFFFFFFFFFE;
							//				mask_xor = (mask_xor<<1) && 0x0000000000000000;
											break;
									}
								}
#else
								// pas de support direct 64 bits
								char buff_and[] = "0x????????????????";
								char buff_xor[] = "0x????????????????";
								int l, q, b;
								unsigned char h_and, h_xor;
								l = strlen(n->content.boperator.r->content.multileaf.firstkeyword->kword)-1;
								for(q=15; q>=0; q--)
								{
									h_and = h_xor = 0;
									for(b=0; b<4; b++)
									{
										if(l >= 0)
										{
											switch(n->content.boperator.r->content.multileaf.firstkeyword->kword[l])
											{
												case '0':
													h_and |= (1<<b);
													break;
												case '1':
													h_and |= (1<<b);
													h_xor |= (1<<b);
													break;
											}
											l--;
										}
									}
									buff_and[2+q] = (h_and>9)?('a'+(h_and-10)):('0'+h_and);
									buff_xor[2+q] = (h_xor>9)?('a'+(h_xor-10)):('0'+h_xor);
								}
#endif

								sprintf(sql, "SELECT record.record_id, record.parent_record_id, record.coll_id, NULL AS hitstart, NULL AS hitlen, NULL AS iw, status, NULL AS sha256 FROM"
										" (record INNER JOIN _tmpmask ON _tmpmask.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=%s)"
										" WHERE ((status ^ %s) & %s = 0) ORDER BY record_id DESC"
										, (multidocMode==PHRASEA_MULTIDOC_REGONLY?"record.record_id":"0")
										, buff_xor
										, buff_and);
							}

							if(!sql[0])		// ce n'�tait pas un champ technique, c'est un champ de la base
							{
								sprintf(sql, "SELECT record.record_id, record.parent_record_id, record.coll_id, NULL AS hitstart, NULL AS hitlen, NULL AS iw, status, NULL AS sha256 FROM (prop INNER JOIN"
										" (record INNER JOIN _tmpmask ON _tmpmask.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=%s)"
										" ON prop.record_id=record.record_id) WHERE (name='%s') AND (value%s'%s') ORDER BY record_id DESC"
										, (multidocMode==PHRASEA_MULTIDOC_REGONLY?"record.record_id":"0")
										, fname
										, math2sql[n->type-PHRASEA_OP_EQUAL]
										, n->content.boperator.r->content.multileaf.firstkeyword->kword);
							}

							// zend_printf("<!-- SQL=%s -->\n", sql);
							if(need2tmpmask)
							{
								sqlconn->query("CREATE TEMPORARY TABLE _tmpmask2 SELECT * FROM _tmpmask");
								sqlconn->query("CREATE INDEX coll_id ON _tmpmask2(coll_id)");
							}

							sqlconn->phrasea_query(sql, n);

							if(need2tmpmask)
							{
								sqlconn->query("DROP TABLE _tmpmask2");
								need2tmpmask = false;
							}
						}
					}
				}
				break;
			case PHRASEA_OP_COLON:
				if(n->content.boperator.l && n->content.boperator.r && n->content.boperator.l->type==PHRASEA_KEYLIST && n->content.boperator.r->type==PHRASEA_KEYLIST)
				{
					if(n->content.boperator.l->content.multileaf.firstkeyword && n->content.boperator.r->content.multileaf.firstkeyword)
					{
						if(n->content.boperator.l->content.multileaf.firstkeyword->kword && n->content.boperator.r->content.multileaf.firstkeyword->kword)
						{
							char *fname = n->content.boperator.l->content.multileaf.firstkeyword->kword;

							int l = 1;		// "\0" final
							KEYWORD *k;
							for(k=n->content.boperator.r->content.multileaf.firstkeyword; k; k=k->nextkeyword)
							{
								l += 12 + strlen(k->kword) + 1;		// "value LIKE 'xxx'"
								if(k->nextkeyword)
									l += 4;							// " OR "
							}
							char *fvalue;
							int p = 0;
							if(fvalue = (char *)EMALLOC(l))
							{
								for(k=n->content.boperator.r->content.multileaf.firstkeyword; k; k=k->nextkeyword)
								{
									memcpy(fvalue+p, "value LIKE '", 12);
									p += 12;
									l = strlen(k->kword);
									memcpy(fvalue+p, k->kword, l);
									p += l;
									fvalue[p++] = '\'';
									if(k->nextkeyword)
									{
										memcpy(fvalue+p, " OR ", 4);
										p += 4;
									}
								}
								fvalue[p++] = '\0';


								if(result)
								{
									add_assoc_string(result, (char *)"field", n->content.boperator.l->content.multileaf.firstkeyword->kword, TRUE);
									add_assoc_string(result, (char *)"value", fvalue, TRUE);
								}

								if(fname[0]=='*' && fname[1]=='\0')
								{
									// le nom du champ est '*' : on �vite le test sur le name
									sprintf(sql, "SELECT record.record_id, record.parent_record_id, record.coll_id, hitstart, hitlen, NULL AS iw, status, NULL AS sha256 FROM (thit NATURAL JOIN"
											" (record INNER JOIN _tmpmask ON _tmpmask.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=%s)"
											") WHERE (%s) ORDER BY record_id DESC"
											, (multidocMode==PHRASEA_MULTIDOC_REGONLY?"record.record_id":"0")
											, fvalue);
								}
								else
								{
									// il y a un nom de champ, on doit l'inclure dans le sql
									sprintf(sql, "SELECT record.record_id, record.parent_record_id, record.coll_id, hitstart, hitlen, NULL AS iw, status, NULL AS sha256 FROM (thit NATURAL JOIN"
											" (record INNER JOIN _tmpmask ON _tmpmask.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=%s)"
											") WHERE (%s) AND (name='%s') ORDER BY record_id DESC"
											, (multidocMode==PHRASEA_MULTIDOC_REGONLY?"record.record_id":"0")
											, fvalue
											, fname);
								}

								EFREE(fvalue);

 // zend_printf("<!-- XSQL=%s -->\n", sql);
								sqlconn->phrasea_query(sql, n);
							}
						}
					}
				}
				break;
			case PHRASEA_OP_AND:	// and
				if(n->content.boperator.l && n->content.boperator.r)
				{
					if(result)
					{
						MAKE_STD_ZVAL(objl);
						MAKE_STD_ZVAL(objr);
					}
					querytree2(n->content.boperator.l, depth+1, sqlconn, objl, multidocMode);
					querytree2(n->content.boperator.r, depth+1, sqlconn, objr, multidocMode);
					if(result)
					{
						add_assoc_zval(result, (char *)"lbranch", objl);
						add_assoc_zval(result, (char *)"rbranch", objr);
					}

					CHRONO chrono;
					startChrono(chrono);

					al = n->content.boperator.l->firstanswer;
					ar = n->content.boperator.r->firstanswer;
					while(al && ar)
					{
						while(al && ar && al->rid != ar->rid)
						{
							while(al && ar &&  al->rid > ar->rid )
							{
// zend_printf("drop l:%i\n", al->rid);
								answer = al->nextanswer;
								freeanswer(al);
								al = answer;
							}
							while(al && ar &&  ar->rid > al->rid )
							{
// zend_printf("drop r:%i\n", ar->rid);
								answer = ar->nextanswer;
								freeanswer(ar);
								ar = answer;
							}
						}
						if(al && ar)
						{
							// zend_printf("equal l:%i r:%i\n", al->rid, ar->rid);
							// dans un 'et' (le rid est le m�me), on conserve 'al' et on jette 'ar'
							// on attache les spots de 'ar' � la fin de 'al'
							if(ar->firstspot)
							{
								if(al->lastspot)
									al->lastspot->nextspot = ar->firstspot;
								else
									al->firstspot = ar->firstspot;
								al->lastspot = ar->lastspot;
							}
							// on d�tache les anciens spots de 'ar'
							ar->firstspot = ar->lastspot = NULL;

							// un 'et' n'a pas de 'hits'
							freehits(al);
							if(!(n->firstanswer))
								n->firstanswer = al;
							if(n->lastanswer)
								n->lastanswer->nextanswer = al;
							n->lastanswer = al;
							al = al->nextanswer;
							n->lastanswer->nextanswer = NULL;

							// on jette 'ar'
							answer = ar->nextanswer;
							freeanswer(ar);
							ar = answer;

							n->nbranswers++;
						}
					}
					while(al)
					{
						answer = al->nextanswer;
						freeanswer(al);
						al = answer;
					}
					while(ar)
					{
						answer = ar->nextanswer;
						freeanswer(ar);
						ar = answer;
					}
					n->content.boperator.l->firstanswer = n->content.boperator.l->lastanswer = NULL;
					n->content.boperator.r->firstanswer = n->content.boperator.r->lastanswer = NULL;

					n->time_C = stopChrono(chrono);
				}
				break;
			case PHRASEA_OP_NEAR:	// near
			case PHRASEA_OP_BEFORE:	// near
			case PHRASEA_OP_AFTER:	// near
				// prox = n->content.boperator.numparm;
				add_assoc_long(result, (char *)"prox", n->content.boperator.numparm);
				if(n->content.boperator.l && n->content.boperator.r)
				{
					if(result)
					{
						MAKE_STD_ZVAL(objl);
						MAKE_STD_ZVAL(objr);
					}
					if(!n->content.boperator.l->isempty)
						querytree2(n->content.boperator.l, depth+1, sqlconn, objl, multidocMode);
					if(!n->content.boperator.r->isempty)
						querytree2(n->content.boperator.r, depth+1, sqlconn, objr, multidocMode);
					if(result)
					{
						add_assoc_zval(result, (char *)"lbranch", objl);
						add_assoc_zval(result, (char *)"rbranch", objr);
					}

					CHRONO chrono;
					startChrono(chrono);

					if(n->content.boperator.l->isempty && n->content.boperator.r->isempty)
					{
						n->isempty = TRUE;
//						if(result)
//						{
//							add_assoc_long(result, (char *)"ms", (long)(1000*n->time_all));
//							add_assoc_long(result, (char *)"Cms", (long)(1000*n->time_C));
//							if(n->time_sqlQuery != -1)
//								add_assoc_double(result, (char *)"time_sqlQuery", n->time_sqlQuery);
//							if(n->time_sqlStore != -1)
//								add_assoc_double(result, (char *)"time_sqlStore", n->time_sqlStore);
//							if(n->time_sqlFetch != -1)
//								add_assoc_double(result, (char *)"time_sqlFetch", n->time_sqlFetch);
//							add_assoc_long(result, (char *)"nbanswers", n->nbranswers);
//						}
						n->time_C = stopChrono(chrono);
						break;
					}
					if(n->content.boperator.l->isempty)
					{
						n->firstanswer = n->content.boperator.r->firstanswer;
						n->lastanswer = n->content.boperator.r->lastanswer;
						n->nbranswers = n->content.boperator.r->nbranswers;

						n->content.boperator.r->firstanswer = n->content.boperator.r->lastanswer = NULL;

						n->time_C = stopChrono(chrono);
						break;
					}
					if(n->content.boperator.r->isempty)
					{
						n->firstanswer = n->content.boperator.l->firstanswer;
						n->lastanswer = n->content.boperator.l->lastanswer;
						n->nbranswers = n->content.boperator.l->nbranswers;

						n->content.boperator.l->firstanswer = n->content.boperator.l->lastanswer = NULL;

						n->time_C = stopChrono(chrono);
						break;
					}

					al = n->content.boperator.l->firstanswer;
					ar = n->content.boperator.r->firstanswer;
					prox = n->content.boperator.numparm;
					// zend_printf("prox=%i\n", prox);
					while(al && ar)
					{
						while(al && ar && al->rid != ar->rid)
						{
							while(al && ar &&  al->rid > ar->rid )
							{
								// zend_printf("drop l:%i\n", al->rid);
								answer = al->nextanswer;
								freeanswer(al);
								al = answer;
							}
							while(al && ar &&  ar->rid > al->rid )
							{
								// zend_printf("drop r:%i\n", ar->rid);
								answer = ar->nextanswer;
								freeanswer(ar);
								ar = answer;
							}
						}
						if(al && ar)
						{
							// si 'al' et 'ar' sont 'near', on gardera 'al';
							// dans tous les cas, 'ar' est effac�.
// zend_printf("<!-- equal l:%i r:%i -->\n", al->rid, ar->rid);
							// on conserve un pointeur sur la liste de hits de 'al'
							hitl = al->firsthit;
							// on d�tache la liste de hits de 'al' (on va lui en  allouer de nvx si near)
							al->firsthit = al->lasthit = NULL;
							// on attache les spots de 'ar' � la fin de 'al'
							if(ar->firstspot)
							{
								if(al->lastspot)
									al->lastspot->nextspot = ar->firstspot;
								else
									al->firstspot = ar->firstspot;
								al->lastspot = ar->lastspot;
							}
							// on d�tache les anciens spots de 'ar'
							ar->firstspot = ar->lastspot = NULL;
							// on compare chaque hit de 'al' avec chaque hit de 'ar'
							while(hitl)
							{
								for(hitr=ar->firsthit; hitr; hitr=hitr->nexthit)
								{
									if(n->type == PHRASEA_OP_BEFORE || n->type == PHRASEA_OP_NEAR)
									{
// zend_printf("<!-- (l.start:%i,l.end:%i)?(r.start:%i,r.end:%i) -->\n", hitl->iws, hitl->iwe, hitr->iws, hitr->iwe);
										prox0 = hitr->iws - hitl->iwe;
// zend_printf("<!-- prox(l,r)=%i (prox required=%i) -->\n", prox0, prox);
										if(prox0 >= 0 && prox0 <= prox+1)
										{
											// zend_printf(" TRUE --> ");
											// il y a near, on alloue un nouveau hit pour 'al'
											if(hit = (HIT *)(EMALLOC(sizeof(HIT))))
											{
												hit->nexthit = NULL;
												hit->iws = hitl->iws;
												hit->iwe = hitr->iwe;

												if(!al->firsthit)
													al->firsthit = hit;
												if(al->lasthit)
													al->lasthit->nexthit = hit;
												al->lasthit = hit;
												// zend_printf("(<b>%i</b>,<b>%i</b>)\n", hit->iws, hit->iwe);
											}
										}
										else
										{
											// zend_printf(" FALSE\n");
										}
									}
									if(n->type == PHRASEA_OP_AFTER || n->type == PHRASEA_OP_NEAR)
									{
										// zend_printf("(<b>%i</b>,%i)?(%i,<b>%i</b>)", hitl->iws, hitl->iwe, hitr->iws, hitr->iwe);
										prox0 = hitl->iws - hitr->iwe;
										// zend_printf("[%i]", prox0);
										if(prox0 >= 0 && prox0 <= prox+1)
										{
											// zend_printf(" TRUE --> ");
											// il y a near, on alloue un nouveau hit pour 'al'
											if(hit = (HIT *)(EMALLOC(sizeof(HIT))))
											{
												hit->nexthit = NULL;
												hit->iws = hitr->iwe;
												hit->iwe = hitl->iws;

												if(!al->firsthit)
													al->firsthit = hit;
												if(al->lasthit)
													al->lasthit->nexthit = hit;
												al->lasthit = hit;
												// zend_printf("(<b>%i</b>,<b>%i</b>)\n", hit->iws, hit->iwe);
											}
										}
										else
										{
											 // zend_printf(" FALSE\n");
										}
									}
								}
								hit = hitl->nexthit;
								EFREE(hitl);
								hitl = hit;
							}
							// zend_printf("\n");
							
							// s'il y a eu near, 'al' a de nouveau des hits
							if(al->firsthit)	// oui : on le conserve
							{
								if(!(n->firstanswer))
									n->firstanswer = al;
								if(n->lastanswer)
									n->lastanswer->nextanswer = al;
								n->lastanswer = al;
								al = al->nextanswer;
								n->lastanswer->nextanswer = NULL;
								n->nbranswers++;
							}
							else		// non : on le vire
							{
								answer = al->nextanswer;
								freeanswer(al);
								al = answer;
							}
							// on vire toujours 'ar'
							answer = ar->nextanswer;
							freeanswer(ar);
							ar = answer;
						}
					}
					while(al)
					{
						answer = al->nextanswer;
						freeanswer(al);
						al = answer;
					}
					while(ar)
					{
						answer = ar->nextanswer;
						freeanswer(ar);
						ar = answer;
					}
					n->content.boperator.l->firstanswer = n->content.boperator.l->lastanswer = NULL;
					n->content.boperator.r->firstanswer = n->content.boperator.r->lastanswer = NULL;

					n->time_C = stopChrono(chrono);
				}
				break;
			case PHRASEA_OP_OR:	// or
				if(n->content.boperator.l && n->content.boperator.r)
				{
					if(result)
					{
						MAKE_STD_ZVAL(objl);
						MAKE_STD_ZVAL(objr);
					}
					querytree2(n->content.boperator.l, depth+1, sqlconn, objl, multidocMode);
					querytree2(n->content.boperator.r, depth+1, sqlconn, objr, multidocMode);
					if(result)
					{
						add_assoc_zval(result, (char *)"lbranch", objl);
						add_assoc_zval(result, (char *)"rbranch", objr);
					}

					CHRONO chrono;
					startChrono(chrono);

					al = n->content.boperator.l->firstanswer;
					ar = n->content.boperator.r->firstanswer;
					allast = n->content.boperator.l->lastanswer;
					arlast = n->content.boperator.r->lastanswer;
					lnbranswers	= n->content.boperator.l->nbranswers;
					rnbranswers	= n->content.boperator.r->nbranswers;
					while(al && ar)
					{
					//	zend_printf("OR al=%i, ar=%i<br>\n", al->rid, ar->rid);
						if( al->rid > ar->rid )
						{
							if(!(n->firstanswer))
								n->firstanswer = al;
							if(n->lastanswer)
								n->lastanswer->nextanswer = al;
							n->lastanswer = al;
							al = al->nextanswer;
							n->lastanswer->nextanswer = NULL;
							n->nbranswers++;

							lnbranswers--;
							continue;
						}
						if( ar->rid > al->rid )
						{
							if(!(n->firstanswer))
								n->firstanswer = ar;
							if(n->lastanswer)
								n->lastanswer->nextanswer = ar;
							n->lastanswer = ar;
							ar = ar->nextanswer;
							n->lastanswer->nextanswer = NULL;
							n->nbranswers++;

							rnbranswers--;
							continue;
						}
						if(al->rid == ar->rid)
						{
							// si un 'ou' se trouve dns le m�me record, on conserve 'al' et on jette 'ar'
							// on attache les spots de 'ar' � la fin de 'al'
							if(ar->firstspot)
							{
								if(al->lastspot)
									al->lastspot->nextspot = ar->firstspot;
								else
									al->firstspot = ar->firstspot;
								al->lastspot = ar->lastspot;
							}
							// on d�tache les anciens spots de 'ar'
							ar->firstspot = ar->lastspot = NULL;

							// on attache les hits de 'ar' � la fin de 'al'
							if(ar->firsthit)
							{
								if(al->lasthit)
									al->lasthit->nexthit = ar->firsthit;
								else
									al->firsthit = ar->firsthit;
								al->lasthit = ar->lasthit;
							}
							// on d�tache les anciens hits de 'ar'
							ar->firsthit = ar->lasthit = NULL;

						//	al->lasthit->nexthit = ar->firsthit;
						//	al->lasthit = ar->lasthit;
						//	ar->firsthit = ar->lasthit = NULL;
							if(!(n->firstanswer))
								n->firstanswer = al;
							if(n->lastanswer)
								n->lastanswer->nextanswer = al;
							n->lastanswer = al;
							al = al->nextanswer;
							n->lastanswer->nextanswer = NULL;

							// on jette 'ar'
							answer = ar->nextanswer;
							EFREE(ar);
							ar = answer;

							n->nbranswers++;

							lnbranswers--;
							rnbranswers--;
						}
					}
					// on lie tout ce qui reste dans 'ar'
					if(ar)
					{
						if(!(n->firstanswer))
							n->firstanswer = ar;
						if(n->lastanswer)
							n->lastanswer->nextanswer = ar;
						n->lastanswer = arlast;

						n->nbranswers += rnbranswers;
					}
					// on lie tout ce qui reste dans 'al'
					if(al)
					{
						if(!(n->firstanswer))
							n->firstanswer = al;
						if(n->lastanswer)
							n->lastanswer->nextanswer = al;
						n->lastanswer = allast;

						n->nbranswers += lnbranswers;
					}
					n->content.boperator.l->firstanswer = n->content.boperator.l->lastanswer = NULL;
					n->content.boperator.r->firstanswer = n->content.boperator.r->lastanswer = NULL;

					n->time_C = stopChrono(chrono);
				}
				break;
			case PHRASEA_OP_EXCEPT:	// except
				if(n->content.boperator.l && n->content.boperator.r)
				{
					if(result)
					{
						MAKE_STD_ZVAL(objl);
						MAKE_STD_ZVAL(objr);
					}
					querytree2(n->content.boperator.l, depth+1, sqlconn, objl, multidocMode);
					querytree2(n->content.boperator.r, depth+1, sqlconn, objr, multidocMode);
					if(result)
					{
						add_assoc_zval(result, (char *)"lbranch", objl);
						add_assoc_zval(result, (char *)"rbranch", objr);
					}

					CHRONO chrono;
					startChrono(chrono);

					al = n->content.boperator.l->firstanswer;
					ar = n->content.boperator.r->firstanswer;
					n->nbranswers = n->content.boperator.l->nbranswers;
					n->firstanswer = n->content.boperator.l->firstanswer;
					n->lastanswer  = n->content.boperator.l->lastanswer;
					prev_al = NULL;
					while(al && ar)
					{
						while(al && ar && al->rid != ar->rid)
						{
							while(al && ar &&  al->rid > ar->rid )
							{
								prev_al = al;
								al = al->nextanswer;
							}
							while(al && ar &&  ar->rid > al->rid )
							{
								// zend_printf("drop r:%i\n", ar->rid);
								answer = ar->nextanswer;
								freeanswer(ar);
								ar = answer;
							}
						}
						if(al && ar)
						{
							n->nbranswers--;
							answer = al->nextanswer;
							if(n->firstanswer == al)
								n->firstanswer = answer;
							if(n->lastanswer == al)
								n->lastanswer = prev_al;
							freeanswer(al);
							al = answer;
							if(prev_al)
								prev_al->nextanswer = al;
							answer = ar->nextanswer;
							freeanswer(ar);
							ar = answer;
						}
					}
					while(ar)
					{
						answer = ar->nextanswer;
						freeanswer(ar);
						ar = answer;
					}
					n->content.boperator.l->firstanswer = n->content.boperator.l->lastanswer = NULL;
					n->content.boperator.r->firstanswer = n->content.boperator.r->lastanswer = NULL;

					n->time_C = stopChrono(chrono);
				}
				break;
		}

		if(result)
		{
//			add_assoc_long(result, (char *)"ms", (long)(1000*n->time_all));
//			add_assoc_long(result, (char *)"Cms", (long)(1000*n->time_C));

			add_assoc_double(result, (char *)"time_all", stopChrono(chrono_all));
			if(n->time_C != -1)
				add_assoc_double(result, (char *)"time_C", n->time_C);
			if(sql[0] != '\0')
				add_assoc_string(result, (char *)"sql", sql, true);
			if(n->time_sqlQuery != -1)
				add_assoc_double(result, (char *)"time_sqlQuery", n->time_sqlQuery);
			if(n->time_sqlStore != -1)
				add_assoc_double(result, (char *)"time_sqlStore", n->time_sqlStore);
			if(n->time_sqlFetch != -1)
				add_assoc_double(result, (char *)"time_sqlFetch", n->time_sqlFetch);
			add_assoc_long(result, (char *)"nbanswers", n->nbranswers);
		}
	}
	else
	{
		// zend_printf("querytree : null node\n");
	}
}



void freehits(ANSWER *a)
{
  HIT *h;
	while(a->firsthit)
	{
		h = a->firsthit->nexthit;
	//	if(a->firsthit->spots)
	//		EFREE(a->firsthit->spots);
		EFREE(a->firsthit);
		// zend_printf("h ");
		a->firsthit = h;
	}
}

void freespots(ANSWER *a)
{
  SPOT *s;
	while(a->firstspot)
	{
		s = a->firstspot->nextspot;
	//	if(a->firsthit->spots)
	//		EFREE(a->firsthit->spots);
		EFREE(a->firstspot);
		// zend_printf("h ");
		a->firstspot = s;
	}
}

void freeanswer(ANSWER *a)
{
	// if(a->xml)
	//	EFREE(a->xml);
	freehits(a);
	freespots(a);
	EFREE(a);
}

void freetree(NODE *n)
{
  // HIT *h;
  ANSWER *a;
  KEYWORD *k;
	if(n)
	{
		switch(n->type)
		{
			case PHRASEA_KEYLIST:
				while(n->content.multileaf.firstkeyword)
				{
					if(n->content.multileaf.firstkeyword->kword)
						EFREE(n->content.multileaf.firstkeyword->kword);
					k = n->content.multileaf.firstkeyword->nextkeyword;
					EFREE(n->content.multileaf.firstkeyword);
					n->content.multileaf.firstkeyword = k;
				}
				n->content.multileaf.lastkeyword = NULL;
				break;
//			case PHRASEA_KEYWORD:
//				if(n->content.leaf.kword)
//				{
//					EFREE(n->content.leaf.kword);
//					n->content.leaf.kword = NULL;
//				}
//				break;
			case PHRASEA_OP_EQUAL:
			case PHRASEA_OP_NOTEQU:
			case PHRASEA_OP_GT:
			case PHRASEA_OP_LT:
			case PHRASEA_OP_GEQT:
			case PHRASEA_OP_LEQT:
			case PHRASEA_OP_AND:
			case PHRASEA_OP_OR:
			case PHRASEA_OP_EXCEPT:
			case PHRASEA_OP_NEAR:
			case PHRASEA_OP_BEFORE:
			case PHRASEA_OP_AFTER:
			case PHRASEA_OP_IN:
				freetree(n->content.boperator.l);
				freetree(n->content.boperator.r);
				break;
			default:
				break;
		}
		while(n->firstanswer)
		{
//			if(n->firstanswer->xml)
//				EFREE(n->firstanswer->xml);
//
//			while(n->firstanswer->firsthit)
//			{
//				if(n->firstanswer->firsthit->spots)
//					EFREE(n->firstanswer->firsthit->spots);
//				h = n->firstanswer->firsthit->nexthit;
//				EFREE(n->firstanswer->firsthit);
//				// zend_printf("h ");
//				n->firstanswer->firsthit = h;
//			}
			a = n->firstanswer->nextanswer;
			freeanswer(n->firstanswer);
			// EFREE(n->firstanswer);
			// zend_printf("a ");
			n->firstanswer = a;
		}
		EFREE(n);
		// zend_printf("n ");
	}
}
/**/
