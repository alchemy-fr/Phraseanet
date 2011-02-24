#include "base_header.h"

#include "../php_phrasea2.h"


/*
* renvoie un array avec les fils du grp pass� en param
*/
ZEND_FUNCTION(phrasea_grpchild)
{
    zval zsession, zbaseid, zrid ;
    char *zsit;
    int zsitlen;

    char *zusr;
    int zusrlen;

    long ztotalchildren;
    long totchild = 0;
    
    zval *zchild;
	MAKE_STD_ZVAL(zchild);
	array_init(zchild);

	ztotalchildren = 0;
    
	if(ZEND_NUM_ARGS()==5)
	{
		if(zend_parse_parameters(5 TSRMLS_CC, (char *)"lllss", &zsession, &zbaseid, &zrid, &zsit, &zsitlen,  &zusr , &zusrlen) == FAILURE)
		{
			RETURN_FALSE;
		}
	}
	else
	{
		if(ZEND_NUM_ARGS()==6)
		{
			if(zend_parse_parameters(6 TSRMLS_CC, (char *)"lllssl", &zsession, &zbaseid, &zrid, &zsit, &zsitlen,  &zusr , &zusrlen,&ztotalchildren) == FAILURE)
			{
				RETURN_FALSE;
			}
		}
		else
			WRONG_PARAM_COUNT;
	}

	if(!PHRASEA2_G(global_session) || PHRASEA2_G(global_session)->get_session_id() != Z_LVAL(zsession))
	{
		// la session n'existe pas, on ne peut pas continuer
		RETURN_FALSE;
	}

	SQLCONN *conn = PHRASEA2_G(global_session)->connect(Z_LVAL(zbaseid));
	if(conn)
	{
		char sql[512];
		SQLRES res(conn);
		
		if(ztotalchildren)
		{
			sprintf(sql, "SELECT SUM(1) AS totalchildren FROM regroup,record WHERE rid_parent=%li AND rid_child=record.record_id",  Z_LVAL(zrid));
			if(res.query(sql))
			{
				if( res.get_nrows()==1 )
				{
					SQLROW *row;
					if(row = res.fetch_row())
					{
						if( row->field(0) )
						{
							totchild = atoi(row->field(0));
						}
					}
				}
			}
		}

         
		sprintf(sql, "SELECT record_id,record.coll_id FROM regroup INNER JOIN (record INNER JOIN collusr ON site='%s' AND usr_id=%s AND collusr.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=0) ON (regroup.rid_child=record.record_id and regroup.rid_parent=%li) ORDER BY regroup.ord ASC, dateadd ASC, record_id ASC" ,zsit,zusr, Z_LVAL(zrid));
		if(res.query(sql))
		{
			if( res.get_nrows()<1 )
			{
				RETURN_NULL();
			}
			else
			{
				SQLROW *row;
				int nb=0;
				while(row = res.fetch_row())
				{
					long current_bid = PHRASEA2_G(global_session)->get_local_base_id2( Z_LVAL(zbaseid) , atoi(row->field(1)) );
					if(current_bid!=-1)
					{
						zval *ztmprec;
						MAKE_STD_ZVAL(ztmprec);
						array_init(ztmprec);
						add_next_index_long(ztmprec, current_bid);
						add_next_index_long(ztmprec, atoi(row->field(0)));
						add_next_index_zval(zchild, ztmprec );
						nb++;
					}
				}
				if(nb==0)
					RETURN_NULL();
				if(ztotalchildren)
					add_assoc_long (zchild, (char *)"totalchildren" ,totchild );
			}
		}
	}
	RETURN_ZVAL ( zchild, true, true );
}


ZEND_FUNCTION(phrasea_grpforselection)
{

//  long highlightlen;
  char *highlight = NULL;
  zval zsession, zbaseid;

	char *zridlist;
	int zridlistlen;

	char *zsit;
	int zsitlen;

	zval zusr;

	zval *zchild;
	MAKE_STD_ZVAL(zchild);
	array_init(zchild);

  int id = -1;
  bool conn_ok = TRUE;
	if(ZEND_NUM_ARGS()==5)
	{
		if(zend_parse_parameters(5 TSRMLS_CC, (char *)"llssl", &zsession, &zbaseid, &zridlist, &zridlistlen, &zsit, &zsitlen, &zusr) == FAILURE)
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
	// on cherche la base dans le cache
	// global_session->dump();

	RETVAL_FALSE;

	SQLCONN *conn = PHRASEA2_G(global_session)->connect(Z_LVAL(zbaseid));
	if(conn)
	{
		char sql[2048];
		SQLRES res(conn);

		// sprintf(sql, "SELECT record_id,parent_record_id FROM record WHERE record_id=%i", Z_LVAL(zrid));
		sprintf(sql, "SELECT record_id,record.coll_id, xml FROM (record INNER JOIN collusr ON record_id IN (%s) AND site='%s' AND usr_id=%ld AND collusr.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND parent_record_id=record_id )", zridlist, zsit, Z_LVAL(zusr) );

		if(res.query(sql))
		{
			if( res.get_nrows()<1 )
			{
				RETURN_NULL();
			}
			else
			{
				SQLROW *row;
				int nb=0;
				while(row = res.fetch_row())
				{
					long current_bid = PHRASEA2_G(global_session)->get_local_base_id2( Z_LVAL(zbaseid) , atoi(row->field(1)) );
					if(current_bid!=-1)
					{
						zval *ztmprec;
						MAKE_STD_ZVAL(ztmprec);
						array_init(ztmprec);
						add_next_index_long(ztmprec, current_bid);
						add_next_index_long(ztmprec, atoi(row->field(0)));
		
						add_next_index_string(ztmprec, ((char*)((row->field(2)))) , 1 );
		
						//add_next_index_string(ztmprec, (row->field(2)) );
						add_next_index_zval(zchild, ztmprec );
						nb++;
					}
				}
				if(nb==0)
					RETURN_NULL();
			}
		}
	}
	RETURN_ZVAL ( zchild, true, true );
}

/*
* Donne les records parent d'un record pass� en param
*/
ZEND_FUNCTION(phrasea_grpparent) 
{ 
  //	long highlightlen;
  char *highlight = NULL;
  zval zsession, zbaseid, zrid;
  char *zsit;
  int zsitlen;
  zval zusr;

  int id = -1;
  bool conn_ok = TRUE;

	if(ZEND_NUM_ARGS()==5)
	{
		if(zend_parse_parameters(5 TSRMLS_CC, (char *)"lllsl", &zsession, &zbaseid, &zrid, &zsit, &zsitlen, &zusr) == FAILURE)
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
		
	zval *zprid;
	MAKE_STD_ZVAL(zprid);
	array_init(zprid);
		
	SQLCONN *conn = PHRASEA2_G(global_session)->connect(Z_LVAL(zbaseid));
	if(conn)
	{
		char sql[512];
		SQLRES res(conn);
		
		sprintf(sql, "SELECT record.record_id, record.coll_id FROM regroup INNER JOIN (record INNER JOIN collusr ON site='%s' AND usr_id=%ld AND collusr.coll_id=record.coll_id AND ((status ^ mask_xor) & mask_and)=0 AND record.parent_record_id=record.record_id) ON (regroup.rid_parent=record.record_id) WHERE rid_child=%ld", zsit , Z_LVAL(zusr) ,Z_LVAL(zrid));
//zend_printf("SQL : %s\n", sql );
		
		// sprintf(sql, "select rid_parent from regroup where rid_child=%i", Z_LVAL(zrid));
		if(res.query(sql))
		{
			if( res.get_nrows()<1 )
			{
				RETURN_NULL();
			}
			else
			{   
				SQLROW *row;
				int nb = 0 ;
				while(row = res.fetch_row())
				{
					//add_next_index_long(zprid, atoi(row->field(0)));
	
					long current_bid = PHRASEA2_G(global_session)->get_local_base_id2( Z_LVAL(zbaseid) , atoi(row->field(1)) );
					if(current_bid!=-1)
					{
						zval *ztmprec;
						MAKE_STD_ZVAL(ztmprec);
						array_init(ztmprec);
						add_next_index_long(ztmprec, current_bid);
						add_next_index_long(ztmprec, atoi(row->field(0)));
						add_next_index_zval(zprid, ztmprec );
						nb++;
					}
				}
				if(nb==0)
					RETURN_NULL();
			}
		}
	}
	RETURN_ZVAL ( zprid, true, true );
}
          
/*
* pour un record pass� en param :
*               TRUE  : le record pass� en param est une fiche grp
*               FALSE : le record est un doc simple, pas une fiche grp
*/
ZEND_FUNCTION(phrasea_isgrp) 
{ 
//  long highlightlen;
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
		WRONG_PARAM_COUNT;
	

	 
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
		
		sprintf(sql, "SELECT record_id,parent_record_id FROM record WHERE record_id=%li", Z_LVAL(zrid)); 
		if(res.query(sql))
		{
			SQLROW *row;
			if(row = res.fetch_row())
			{
				if( atoi(row->field(1))==atoi(row->field(0)) )
				{
					RETVAL_TRUE;
				}
			}
		}
	}
}


