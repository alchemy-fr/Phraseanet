#include "connbas_dbox.h"
#include <memory.h>
#include <string.h>
#include <stdio.h>
#include "_syslog.h"

#include "trace_memory.h"

#include <time.h>
#include <stdio.h>
#include <sys/types.h>
#include <sys/timeb.h>
#include <string.h>


extern CSyslog zSyslog;

//--------------------------------------------------------------------
// CConnbas_dbox

// char *CConnbas_dbox::sql_insertIdx   = "INSERT INTO idx (record_id, kword_id, iw, xpath_id, hitstart, hitlen) VALUES (?, ?, ?, ?, ?, ?)";
// char *CConnbas_dbox::sql_selectKword = "SELECT kword_id FROM kword WHERE keyword=?";
// char *CConnbas_dbox::sql_insertKword = "INSERT INTO kword (kword_id, keyword) VALUES (?, ?)";
// char *CConnbas_dbox::sql_selectXPath = "SELECT xpath_id FROM xpath WHERE xpath=?";
// char *CConnbas_dbox::sql_insertXPath = "INSERT INTO xpath (xpath_id, xpath) VALUES (?, ?)";
// char *CConnbas_dbox::sql_updateUids  = "UPDATE uids SET uid=uid+? WHERE name=?";
// char *CConnbas_dbox::sql_selectUid   = "SELECT uid FROM uids WHERE name=?";

// char *CConnbas_dbox::sql_insertTHit  = "INSERT INTO thit (record_id, xpath_id, name, value, hitstart, hitlen) VALUES (?, ?, ?, ?, ?, ?)";
// char *CConnbas_dbox::sql_insertProp  = "INSERT INTO prop (record_id, xpath_id, name, value) VALUES (?, ?, ?, ?)";

//char *CConnbas_dbox::sql_updateRecord_lock    = "UPDATE record SET status=status & ~2 WHERE record_id=?";
//char *CConnbas_dbox::sql_updateRecord_unlock  = "UPDATE record SET status=status | 2 WHERE record_id=?";


// extern bool scanningRecords;
extern bool running;

CConnbas_dbox::CConnbas_dbox(unsigned int sbas_id, const char *host, const char *user, const char *passwd, const char *szDB, unsigned int port)
{
	this->sbas_id = sbas_id;

	this->open(host, user, passwd, szDB, port);

	this->cstmt_setRecordsToReindexTh2 = NULL;
	this->cstmt_updatePref_cterms = NULL;
	this->cstmt_selectPref_moddates = NULL;
	this->cstmt_insertKword = NULL;
	this->cstmt_selectKword = NULL;
	this->cstmt_insertIdx   = NULL;
	this->cstmt_selectXPath = NULL;
	this->cstmt_insertXPath = NULL;
	this->cstmt_updateUids  = NULL;
	this->cstmt_selectUid   = NULL;
	this->cstmt_selectPrefs = NULL;
	this->cstmt_selectCterms = NULL;
	this->cstmt_selectKwords = NULL;
	this->cstmt_selectXPaths = NULL;

	this->cstmt_selectRecords = NULL;

	this->struct_buffer = NULL;
	this->struct_buffer_size = 0;

	this->thesaurus_buffer = NULL;
	this->thesaurus_buffer_size = 0;

	this->cterms_buffer = NULL;
	this->cterms_buffer_size = 0;

	this->cterms_buffer2 = NULL;
	this->cterms_buffer2_size = 0;

	this->xml_buffer = NULL;
	this->xml_buffer_size = 0;

	this->cstmt_insertTHit  = NULL;
	this->cstmt_insertProp  = NULL;
	this->cstmt_updateRecord_lock   = NULL;
	this->cstmt_updateRecord_unlock = NULL;

	this->cstmt_needReindex = NULL;
}

CConnbas_dbox::~CConnbas_dbox()
{
	this->close();
}



// ---------------------------------------------------------------
// UPDATE record SET status=status & ~2 WHERE record_id IN (?) 
// ---------------------------------------------------------------
int CConnbas_dbox::setRecordsToReindexTh2(char *lrid, unsigned long lrid_len)
{
	int ret = -1;
	unsigned long lencpy;

	if(!this->cstmt_setRecordsToReindexTh2)
	{
		if( (this->cstmt_setRecordsToReindexTh2 = this->newStmt("UPDATE record SET status=status & ~2 WHERE record_id IN (?)", 1, 0)) )
		{
			this->cstmt_setRecordsToReindexTh2->bindi[0].buffer_type = MYSQL_TYPE_STRING;
		}
	}

	if(this->cstmt_setRecordsToReindexTh2)
	{
		this->cstmt_setRecordsToReindexTh2->bindi[0].buffer        = (void *)(lrid);
		this->cstmt_setRecordsToReindexTh2->bindi[0].buffer_length = lencpy = lrid_len;
		this->cstmt_setRecordsToReindexTh2->bindi[0].length        = &lencpy;
		if (this->cstmt_setRecordsToReindexTh2->bind_param() == 0)
		{
			if(this->cstmt_setRecordsToReindexTh2->execute() == 0)
			{
				// status ar updated
				ret = 0;
			}
		}
	}
	return(ret);
}


// ---------------------------------------------------------------
// DELETE FROM idx WHERE record_id IN (?)
// DELETE FROM prop WHERE record_id IN (?)
// DELETE FROM thit WHERE record_id IN (?)
// ---------------------------------------------------------------

int CConnbas_dbox::delRecRefs2(char *lrid, unsigned long lrid_len)
{
	char *sql;
	if( (sql = (char *)(_MALLOC_WHY(37 + lrid_len + 1 + 1, "connbas_dbox.cpp:delRecRefs2:sql")) ) )
	{
		memcpy(sql, "DELETE FROM idx  WHERE record_id IN (", 37);
		memcpy(sql+37, lrid, lrid_len);
		sql[37+lrid_len] = ')';
		sql[37+lrid_len+1] = '\0';

		this->execute(sql, 37+lrid_len+1);

		memcpy(sql+12, "prop", 4);

		this->execute(sql, 37+lrid_len+1);

		memcpy(sql+12, "thit", 4);

		this->execute(sql, 37+lrid_len+1);

		_FREE(sql);

	}
	return(0);
}


// ---------------------------------------------------------------
// UPDATE record SET status=status | 7 WHERE record_id IN (?)
// ---------------------------------------------------------------

int CConnbas_dbox::updateRecord_unlock2(char *lrid, unsigned long lrid_len)
{
	char *sql;
	if( (sql = (char *)(_MALLOC_WHY(56 + lrid_len + 1 + 1, "connbas_dbox.cpp:updateRecord_unlock2:sql")) ) )
	{
		memcpy(sql, "UPDATE record SET status=status | 7 WHERE record_id IN (", 56);
		memcpy(sql+56, lrid, lrid_len);
		sql[56+lrid_len] = ')';
		sql[56+lrid_len+1] = '\0';

		this->execute(sql, 56+lrid_len+1);

		_FREE(sql);
	}
	return(0);
}



// ---------------------------------------------------------------
// UPDATE pref SET value=?, updated_on=? WHERE prop='cterms '
// ---------------------------------------------------------------
int CConnbas_dbox::updatePref_cterms(char *cterms, unsigned long cterms_size, char *moddate )
{
	int ret = 0;
	if(!this->cstmt_updatePref_cterms)
	{
		if( (this->cstmt_updatePref_cterms = this->newStmt("UPDATE pref SET value=?, updated_on=? WHERE prop='cterms'", 2, 0) ) )
		{
			this->cstmt_updatePref_cterms->bindi[0].buffer_type = MYSQL_TYPE_STRING;

			this->cstmt_updatePref_cterms->bindi[1].buffer_type = MYSQL_TYPE_STRING;
		}
		else
		{
			// newStmt error
			ret = -3;
		}
	}

	if(this->cstmt_updatePref_cterms)
	{
		unsigned long l_cterms_size = cterms_size;
		this->cstmt_updatePref_cterms->bindi[0].buffer      = (void *)cterms;
		this->cstmt_updatePref_cterms->bindi[0].length      = &l_cterms_size;

		unsigned long l_moddate_size = 14;
		this->cstmt_updatePref_cterms->bindi[1].buffer      = (void *)moddate;
		this->cstmt_updatePref_cterms->bindi[1].length      = &l_moddate_size;

		if (this->cstmt_updatePref_cterms->bind_param() == 0)
		{
			if(this->cstmt_updatePref_cterms->execute() != 0)
			{
				// mysql_stmt_execute error
				// printf("%s\n", mysql_stmt_error(this->stmt_updatePref_cterms) );
				ret = -1;
			}
		}
		else
		{
			// mysql_stmt_bind_param error
			ret = -2;
		}
	}
	return(ret);
}


// ---------------------------------------------------------------
// SELECT CAST(value AS UNSIGNED), updated_on<created_on AS k FROM pref WHERE prop='indexes' LIMIT 1
// ---------------------------------------------------------------
int CConnbas_dbox::selectPrefsIndexes(int *value, int *toReindex)
{
  int ret = 0;
	if(this->cstmt_needReindex == NULL)
	{
		if( (this->cstmt_needReindex = this->newStmt("SELECT CAST(value AS UNSIGNED), updated_on<created_on AS k FROM pref WHERE prop='indexes' LIMIT 1", 0, 2) ) )
		{
			this->cstmt_needReindex->bindo[0].buffer_type = MYSQL_TYPE_LONG;
			this->cstmt_needReindex->bindo[1].buffer_type = MYSQL_TYPE_LONG;
		}
		else
		{
			// newStmt error
			ret = -3;
		}
	}
	if(this->cstmt_needReindex)
	{
		this->cstmt_needReindex->bindo[0].buffer = value;
		this->cstmt_needReindex->bindo[1].buffer = toReindex;

		if(this->cstmt_needReindex->bind_result() == 0)
		{
			if(this->cstmt_needReindex->execute() == 0)
			{
				if(this->cstmt_needReindex->store_result() == 0)
				{
					if(this->cstmt_needReindex->fetch() == 0)
					{
						ret = 0;
					}
					this->cstmt_needReindex->free_result();
				}
				else
				{
					ret = -7;
				}
			}
			else
			{
				ret = -6;
			}
		}
		else
		{
			ret = -5;
		}
	}
	else
	{
		ret = -4;
	}
	return(ret);
}




// ---------------------------------------------------------------
// SELECT prop, UNIX_TIMESTAMP(updated_on) FROM pref WHERE prop IN('structure', 'cterms', 'thesaurus') 
// ---------------------------------------------------------------
int CConnbas_dbox::selectPref_moddates(time_t *struct_moddate, time_t *thesaurus_moddate, time_t *cterms_moddate)
{
  int ret = 0;

	if(this->cstmt_selectPref_moddates == NULL)
	{
		if( (this->cstmt_selectPref_moddates = this->newStmt("SELECT prop, UNIX_TIMESTAMP(updated_on) FROM pref WHERE prop IN('structure', 'cterms', 'thesaurus')", 0, 2)) )
		{
			this->cstmt_selectPref_moddates->bindo[0].buffer_type = MYSQL_TYPE_STRING;
			this->cstmt_selectPref_moddates->bindo[1].buffer_type = MYSQL_TYPE_LONG;
		}
		else
		{
			// newStmt error
			ret = -3;
		}
	}
	
	if(this->cstmt_selectPref_moddates)
	{
		int my_supdated_on;
		char prop[65];
		unsigned long prop_length;
		
		this->cstmt_selectPref_moddates->bindo[0].buffer        = (void *)(prop);
		this->cstmt_selectPref_moddates->bindo[0].buffer_length = 64;
		this->cstmt_selectPref_moddates->bindo[0].length        = &prop_length;
		this->cstmt_selectPref_moddates->bindo[0].is_null       = (my_bool*)0;	// data is always not null

		this->cstmt_selectPref_moddates->bindo[1].buffer = (void *)&my_supdated_on;

		if(this->cstmt_selectPref_moddates->bind_result() == 0)
		{
			if(this->cstmt_selectPref_moddates->execute() == 0)
			{
				if(this->cstmt_selectPref_moddates->store_result() == 0)
				{
					while(this->cstmt_selectPref_moddates->fetch() == 0)
					{
//printf("%s : %ld \n", prop, my_supdated_on);
						if(strcmp(prop, "structure")==0)
						{
							*struct_moddate = (time_t)my_supdated_on;
						}
						else if(strcmp(prop, "thesaurus")==0)
						{
							*thesaurus_moddate = (time_t)my_supdated_on;
						}
						else if(strcmp(prop, "cterms")==0)
						{
							*cterms_moddate = (time_t)my_supdated_on;
						}
						ret = 0;
					}
					this->cstmt_selectPref_moddates->free_result();
				}
				else
				{
					ret = -7;
				}
			}
			else
			{
				ret = -6;
			}
		}
		else
		{
			ret = -5;
		}
	}
	else
	{
		ret = -4;
	}
	return(ret);
}


// ---------------------------------------------------------------
// INSERT INTO kword (kword_id, k2, keyword) VALUES (? , ? , ?) 
// ---------------------------------------------------------------

int CConnbas_dbox::insertKword(char *keyword, unsigned long len, unsigned int *kword_id )
{
	int ret = -1;
	unsigned long lencpy;
	unsigned long lenk2;

	if(!this->cstmt_insertKword)
	{
		if( (this->cstmt_insertKword = this->newStmt("INSERT INTO kword (kword_id, k2, keyword) VALUES (?, ?, ?)", 3, 0)) )
		{
			this->cstmt_insertKword->bindi[0].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_insertKword->bindi[1].buffer_type = MYSQL_TYPE_STRING;

			this->cstmt_insertKword->bindi[2].buffer_type = MYSQL_TYPE_STRING;
		}
	}

	if(!this->cstmt_selectKword)
	{
		if( (this->cstmt_selectKword = this->newStmt("SELECT kword_id FROM kword WHERE keyword=?", 1, 1)) )
		{
			this->cstmt_selectKword->bindi[0].buffer_type = MYSQL_TYPE_STRING;

			this->cstmt_selectKword->bindo[0].buffer_type = MYSQL_TYPE_LONG;
		}
	}

	if( (this->cstmt_insertKword && this->cstmt_selectKword) )
	{
		this->cstmt_insertKword->bindi[0].buffer        = (void *)(kword_id);
		if((lenk2=len) > 2)
			lenk2 = 2;
		if((lencpy=len) > 64)
			lencpy = 64;
		this->cstmt_insertKword->bindi[1].buffer        = (void *)(keyword);
		this->cstmt_insertKword->bindi[1].buffer_length = lenk2;
		this->cstmt_insertKword->bindi[1].length        = &lenk2;
		this->cstmt_insertKword->bindi[2].buffer        = (void *)(keyword);
		this->cstmt_insertKword->bindi[2].buffer_length = lencpy;
		this->cstmt_insertKword->bindi[2].length        = &lencpy;
		if (this->cstmt_insertKword->bind_param() == 0)
		{
			if(this->cstmt_insertKword->execute() == 0)
			{
				// the kword has been created
				ret = 0;
			}
			else
			{
				// the insert failed : thee kword must already exists
				int r = cstmt_insertKword->errNo();
				if(r==ER_DUP_KEY || r==ER_DUP_ENTRY)
				{
					// change his id
					if((lencpy=len) > 64)
						lencpy = 64;
					this->cstmt_selectKword->bindi[0].buffer        = (void *)(keyword);
					this->cstmt_selectKword->bindi[0].buffer_length = lencpy;
					this->cstmt_selectKword->bindi[0].length        = &lencpy;

					unsigned int kid;
					this->cstmt_selectKword->bindo[0].buffer        = (void *)(&kid);

					if (this->cstmt_selectKword->bind_param() == 0)
					{
						if(this->cstmt_selectKword->bind_result() == 0)
						{
							if(this->cstmt_selectKword->execute() == 0)
							{
								if(this->cstmt_selectKword->store_result() == 0)
								{
									if(this->cstmt_selectKword->fetch() == 0)
									{
										*kword_id = kid;
										ret = 0;
									}
									this->cstmt_selectKword->free_result();
								}
							}
						}
					}
				}
			}
		}
	}
	return(ret);
}


// ---------------------------------------------------------------
// INSERT INTO idx (record_id, kword_id, iw, xpath_id, hitstart, hitlen) VALUES (?, ?, ?, ?, ?, ?) 
// ---------------------------------------------------------------

int CConnbas_dbox::insertIdx(unsigned int record_id, unsigned int kword_id, unsigned int iw, unsigned int xpath_id, unsigned int hitstart, unsigned int hitlen)
{
	int ret = -1;
	if(!this->cstmt_insertIdx)
	{
		if( (this->cstmt_insertIdx = this->newStmt("INSERT INTO idx (record_id, kword_id, iw, xpath_id, hitstart, hitlen) VALUES (?, ?, ?, ?, ?, ?)", 6, 0)) )
		{
			this->cstmt_insertIdx->bindi[0].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_insertIdx->bindi[1].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_insertIdx->bindi[2].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_insertIdx->bindi[3].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_insertIdx->bindi[4].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_insertIdx->bindi[5].buffer_type = MYSQL_TYPE_LONG;
		}
	}

	if(this->cstmt_insertIdx)
	{
		this->cstmt_insertIdx->bindi[0].buffer      = (void *)(&record_id);
		this->cstmt_insertIdx->bindi[1].buffer      = (void *)(&kword_id);
		this->cstmt_insertIdx->bindi[2].buffer      = (void *)(&iw);
		this->cstmt_insertIdx->bindi[3].buffer      = (void *)(&xpath_id);
		this->cstmt_insertIdx->bindi[4].buffer      = (void *)(&hitstart);
		this->cstmt_insertIdx->bindi[5].buffer      = (void *)(&hitlen);
		if (this->cstmt_insertIdx->bind_param() == 0)
		{
			if(this->cstmt_insertIdx->execute() == 0)
			{
				ret = 0;
			}
		}
	}
	return(ret);
}



// ---------------------------------------------------------------
// INSERT INTO xpath (xpath_id, xpath) VALUES (? , ?) 
// ---------------------------------------------------------------
int CConnbas_dbox::insertXPath(char *xpath, unsigned int *xpath_id )
{
	int ret = -1;
	size_t len = strlen(xpath);
	unsigned long lencpy;

	if(!this->cstmt_insertXPath)
	{
		if( (this->cstmt_insertXPath = this->newStmt("INSERT INTO xpath (xpath_id, xpath) VALUES (? , ?)", 2, 0)) )
		{
			this->cstmt_insertXPath->bindi[0].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_insertXPath->bindi[1].buffer_type = MYSQL_TYPE_STRING;
		}
	}

	if(!this->cstmt_selectXPath)
	{
		if( (this->cstmt_selectXPath = this->newStmt("SELECT xpath_id FROM xpath WHERE xpath=?", 1, 1)) )
		{
			this->cstmt_selectXPath->bindi[0].buffer_type = MYSQL_TYPE_STRING;

			this->cstmt_selectXPath->bindo[0].buffer_type = MYSQL_TYPE_LONG;
		}
	}

	if(this->cstmt_insertXPath && this->cstmt_selectXPath)
	{
		this->cstmt_insertXPath->bindi[0].buffer        = (void *)(xpath_id);
		if((lencpy=len) > 150)
			lencpy = 150;
		this->cstmt_insertXPath->bindi[1].buffer        = (void *)(xpath);
		this->cstmt_insertXPath->bindi[1].buffer_length = lencpy;
		this->cstmt_insertXPath->bindi[1].length        = &lencpy;
		if (this->cstmt_insertXPath->bind_param() == 0)
		{
//printf("-------- insertXPath='", xpath);
//for(unsigned int zz=0; zz<lencpy; zz++)
//	putchar(xpath[zz]);
//printf("' -----------", xpath);
			if(this->cstmt_insertXPath->execute() == 0)
			{
				// thee xpath has been created
				ret = 0;
//printf("--- ret %d -----------\n",  0);
			}
			else
			{
				// the insert has failed : the xpath must already exists
				int r = cstmt_insertXPath->errNo();
//printf("--- ret %d -----------\n",  r);
				if(r==ER_DUP_KEY || r==ER_DUP_ENTRY)
				{
					// get his id
					if((lencpy=len) > 150)
						lencpy = 150;
					this->cstmt_selectXPath->bindi[0].buffer        = (void *)(xpath);
					this->cstmt_selectXPath->bindi[0].buffer_length = lencpy;
					this->cstmt_selectXPath->bindi[0].length        = &lencpy;

					int xpid;
					this->cstmt_selectXPath->bindo[0].buffer        = (void *)(&xpid);

					if (this->cstmt_selectXPath->bind_param() == 0)
					{
						if(this->cstmt_selectXPath->bind_result() == 0)
						{
							if(this->cstmt_selectXPath->execute() == 0)
							{
								if(this->cstmt_selectXPath->store_result() == 0)
								{
									if(this->cstmt_selectXPath->fetch() == 0)
									{
										*xpath_id = xpid;
										ret = 0;
									}
									this->cstmt_selectXPath->free_result();
								}
							}
						}
					}
				}
			}
		}
	}
//printf("-------- insertXPath ret id=%ld -----------\n", *xpath_id);
	return(ret);
}


unsigned int CConnbas_dbox::getID(const char *name, unsigned int n )
{
	unsigned int ret = 0;
	size_t len = strlen(name);
	unsigned long lencpy;

	if(!this->cstmt_updateUids)
	{
		if( (this->cstmt_updateUids = this->newStmt("UPDATE uids SET uid=uid+? WHERE name=?", 2, 0)) )
		{
			this->cstmt_updateUids->bindi[0].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_updateUids->bindi[1].buffer_type = MYSQL_TYPE_STRING;
		}
	}
	if(!this->cstmt_selectUid)
	{
		if( (this->cstmt_selectUid = this->newStmt("SELECT uid FROM uids WHERE name=?", 1, 1)) )
		{
			this->cstmt_selectUid->bindi[0].buffer_type = MYSQL_TYPE_STRING;

			this->cstmt_selectUid->bindo[0].buffer_type = MYSQL_TYPE_LONG;
		}
	}

	if(this->cstmt_updateUids && this->cstmt_selectUid)
	{
		this->cstmt_updateUids->bindi[0].buffer        = (void *)(&n);
		if((lencpy=len) > 16)
			lencpy = 16;
		this->cstmt_updateUids->bindi[1].buffer        = (void *)(name);
		this->cstmt_updateUids->bindi[1].buffer_length = lencpy;
		this->cstmt_updateUids->bindi[1].length        = &lencpy;
		if (this->cstmt_updateUids->bind_param() == 0)
		{
			if(mysql_query(this->mysqlCnx, "LOCK TABLES uids WRITE") == 0)
			{
				if(this->cstmt_updateUids->execute() == 0)
				{
					if((lencpy=len) > 16)
						lencpy = 16;
					this->cstmt_selectUid->bindi[0].buffer        = (void *)(name);
					this->cstmt_selectUid->bindi[0].buffer_length = lencpy;
					this->cstmt_selectUid->bindi[0].length        = &lencpy;

					unsigned int uid;
					this->cstmt_selectUid->bindo[0].buffer      = (void *)(&uid);

					if (this->cstmt_selectUid->bind_param() == 0)
					{
						if(this->cstmt_selectUid->bind_result() == 0)
						{
							if(this->cstmt_selectUid->execute() == 0)
							{
								if(this->cstmt_selectUid->store_result() == 0)
								{
									if(this->cstmt_selectUid->fetch() == 0)
									{
										ret = (uid-n)+1;
									}
									this->cstmt_selectUid->free_result();
								}
							}
						}
					}
				}
				mysql_query(this->mysqlCnx, "UNLOCK TABLES");
			}
		}
	}
	return(ret);
}


void CConnbas_dbox::reindexAll()
{

	this->execute((char*)"TRUNCATE idx",	sizeof("TRUNCATE idx"));
	this->execute((char*)"TRUNCATE kword",	sizeof("TRUNCATE kword"));
	this->execute((char*)"TRUNCATE prop",	sizeof("TRUNCATE prop"));
	this->execute((char*)"TRUNCATE thit",	sizeof("TRUNCATE thit"));
	this->execute((char*)"TRUNCATE xpath",	sizeof("TRUNCATE xpath"));
	this->execute((char*)"UPDATE uids SET uid=1 WHERE name IN('KEYWORDS','XPATH')",	sizeof("UPDATE uids SET uid=1 WHERE name IN('KEYWORDS','XPATH')"));
	this->execute((char*)"UPDATE record SET status=(status|12)&~3",	sizeof("UPDATE record SET status=(status|12)&~3"));

	// ----------------------- load cterms
	char *xmlcterms;
	unsigned long  xmlcterms_length;
//	if(this->selectPrefs(NULL, NULL, NULL, NULL, &xmlcterms, &xmlcterms_length) == 0)
	if(this->selectCterms(&xmlcterms, &xmlcterms_length) == 0)
	{
		// we have the cterms, load in libxml
		xmlDocPtr          DocCterms;			// cterms libxml
		xmlXPathContextPtr XPathCtx_cterms;		// cterms xpath
		xmlKeepBlanksDefault(0);
		DocCterms = xmlParseMemory(xmlcterms, xmlcterms_length);
		if(DocCterms != NULL)
		{
			// Create xpath evaluation context
			XPathCtx_cterms = xmlXPathNewContext(DocCterms);
			if(XPathCtx_cterms != NULL)
			{
				xmlXPathObjectPtr  xpathObj_cterms = NULL;
				xpathObj_cterms = xmlXPathEvalExpression((const xmlChar*)("/cterms/te/te[starts-with(@id,'C')]"), XPathCtx_cterms);
				if(xpathObj_cterms)
				{
					if(xpathObj_cterms->nodesetval)
					{
						xmlNodeSetPtr nodes_cterms = xpathObj_cterms->nodesetval;
						for(int i=0; i<nodes_cterms->nodeNr; i++)
						{
							xmlUnlinkNode(nodes_cterms->nodeTab[i]);
							xmlFreeNode(nodes_cterms->nodeTab[i]);
						}
						char moddate[16];
						time_t atimer;
						time(&atimer);
						struct tm *today;
						today = localtime(&atimer);
						strftime((char *)moddate, 15, "%Y%m%d%H%M%S", today);

						xmlSetProp(DocCterms->children, (const xmlChar*)"modification_date", (const xmlChar *)moddate );

						xmlChar *out;
						int outsize;
						xmlDocDumpFormatMemory(DocCterms, &out, &outsize, 1);

						this->updatePref_cterms((char *)out, outsize, moddate );
						xmlFree(out);
					}
					xmlXPathFreeObject(xpathObj_cterms);
				}
			}
		}
	}
	this->execute((char*)"UPDATE pref SET updated_on=NOW() WHERE prop='indexes'",	sizeof("UPDATE pref SET updated_on=NOW() WHERE prop='indexes'"));
}

// ---------------------------------------------------------------
// SELECT struct,thesaurus,cterms FROM (
// (SELECT value as struct from pref where prop='structure') as t1, 
// (SELECT value as thesaurus from pref where prop='thesaurus') as t2, 
// (SELECT value as cterms from pref where prop='cterms') as t3 )
// ---------------------------------------------------------------
int CConnbas_dbox::selectPrefs(char **pstruct, unsigned long *struct_length, char **pthesaurus, unsigned long *thesaurus_length, char **pcterms, unsigned long *cterms_length)
{
	char micro_buffer[3][1];
	unsigned long micro_length[3];
	int ret = -1;

	if(!this->cstmt_selectPrefs)
	{
		if( (this->cstmt_selectPrefs = this->newStmt("SELECT p1.value AS struct, p2.value AS thesaurus, p3.value AS cterms"
												" FROM pref p1, pref p2, pref p3"
												" WHERE p1.prop='structure' AND p2.prop='thesaurus' AND p3.prop='cterms'", 0, 3)) )
		{
			this->cstmt_selectPrefs->bindo[0].buffer_type = MYSQL_TYPE_STRING;

			this->cstmt_selectPrefs->bindo[1].buffer_type = MYSQL_TYPE_STRING;

			this->cstmt_selectPrefs->bindo[2].buffer_type = MYSQL_TYPE_STRING;
		}
	}

	if(this->cstmt_selectPrefs)
	{
		// ------  binding of field 'struct'
		if(pstruct)
		{
			// we ask for fied 'struct' : let's bind within fuction args
			*pstruct = NULL;
			*struct_length = 0;
			if(this->struct_buffer == NULL)
			{
				if( (this->struct_buffer = (char *)(_MALLOC_WHY(this->struct_buffer_size = 1024, "connbas_dbox.cpp:selectPrefs:struct_buffer"))) == NULL)
				{
					// malloc error
					return(-2);
				}
			}
			this->cstmt_selectPrefs->bindo[0].buffer        = (void *)(this->struct_buffer);
			this->cstmt_selectPrefs->bindo[0].buffer_length = this->struct_buffer_size;
			this->cstmt_selectPrefs->bindo[0].length        = struct_length;
		}
		else
		{
			// we DONT ask 'struct' : let's bind to a micro buffer
			this->cstmt_selectPrefs->bindo[0].buffer        = (void *)(micro_buffer+0);
			this->cstmt_selectPrefs->bindo[0].buffer_length = 1;
			this->cstmt_selectPrefs->bindo[0].length        = micro_length+0;
		}

		// ------  binding of field 'thesaurus'
		if(pthesaurus)
		{
			*pthesaurus = NULL;
			*thesaurus_length = 0;
			if(this->thesaurus_buffer == NULL)
			{
				if( (this->thesaurus_buffer = (char *)(_MALLOC_WHY(this->thesaurus_buffer_size = 4096, "connbas_dbox.cpp:selectPrefs:thesaurus_buffer"))) == NULL)
				{
					// malloc error
					return(-2);
				}
			}
			this->cstmt_selectPrefs->bindo[1].buffer        = (void *)(this->thesaurus_buffer);
			this->cstmt_selectPrefs->bindo[1].buffer_length = this->thesaurus_buffer_size;
			this->cstmt_selectPrefs->bindo[1].length        = thesaurus_length;
		}
		else
		{
			this->cstmt_selectPrefs->bindo[1].buffer        = (void *)(micro_buffer+1);
			this->cstmt_selectPrefs->bindo[1].buffer_length = 1;
			this->cstmt_selectPrefs->bindo[1].length        = micro_length+1;
		}

		// ------  binding of field 'cterms'
		if(pcterms)
		{
			*pcterms = NULL;
			*cterms_length = 0;
			if(this->cterms_buffer == NULL)
			{
				if( (this->cterms_buffer = (char *)(_MALLOC_WHY(this->cterms_buffer_size = 4096, "connbas_dbox.cpp:selectPrefs:cterms_buffer"))) == NULL)
				{
					// malloc error
					return(2);
				}
			}
			this->cstmt_selectPrefs->bindo[2].buffer        = (void *)(this->cterms_buffer);
			this->cstmt_selectPrefs->bindo[2].buffer_length = this->cterms_buffer_size;
			this->cstmt_selectPrefs->bindo[2].length        = cterms_length;

		}
		else
		{
			this->cstmt_selectPrefs->bindo[2].buffer        = (void *)(micro_buffer+2);
			this->cstmt_selectPrefs->bindo[2].buffer_length = 1;
			this->cstmt_selectPrefs->bindo[2].length        = micro_length+2;
		}

		if(this->cstmt_selectPrefs->execute() == 0)
		{
			if(this->cstmt_selectPrefs->store_result() == 0)
			{
				int row_count = 0;
				ret = 0;

				while(ret==0)
				{
					if(this->cstmt_selectPrefs->bind_result() != 0)
					{
						ret = 3;	// bind error
						break;
					}

					if((ret = this->cstmt_selectPrefs->fetch()) == MYSQL_NO_DATA)
					{
						ret = 0;	// normal end
						break;
					}

#ifdef MYSQL_DATA_TRUNCATED
					if(ret == MYSQL_DATA_TRUNCATED)
						ret = 0;		//  will be catched comparing buffer sizes
#endif
					if(ret==0)
					{
						if(pstruct && *struct_length > this->struct_buffer_size+1)
						{
							// buffer too small, realloc
							if( (this->struct_buffer = (char *)_REALLOC((void *)(this->struct_buffer), this->struct_buffer_size = *struct_length+1)) )
							{
								this->cstmt_selectPrefs->bindo[0].buffer        = (void *)(this->struct_buffer);
								this->cstmt_selectPrefs->bindo[0].buffer_length = this->struct_buffer_size;
								ret = this->cstmt_selectPrefs->fetchColumn(0);
							}
							else
							{
								ret = CR_OUT_OF_MEMORY;
							}
						}
						if(ret==0 && pthesaurus && *thesaurus_length > this->thesaurus_buffer_size+1)
						{
							// buffer too small, realloc
							if( (this->thesaurus_buffer = (char *)_REALLOC((void *)(this->thesaurus_buffer), this->thesaurus_buffer_size = *thesaurus_length+1)) )
							{
								this->cstmt_selectPrefs->bindo[1].buffer        = (void *)(this->thesaurus_buffer);
								this->cstmt_selectPrefs->bindo[1].buffer_length = this->thesaurus_buffer_size;
								ret = this->cstmt_selectPrefs->fetchColumn(1);
							}
							else
							{
								ret = CR_OUT_OF_MEMORY;
							}
						}
						if(ret==0 && pcterms && *cterms_length > this->cterms_buffer_size+1)
						{
							// buffer too small, realloc
							if( (this->cterms_buffer = (char *)_REALLOC((void *)(this->cterms_buffer), this->cterms_buffer_size = *cterms_length+1)) )
							{
								this->cstmt_selectPrefs->bindo[2].buffer        = (void *)(this->cterms_buffer);
								this->cstmt_selectPrefs->bindo[2].buffer_length = this->cterms_buffer_size;
								ret = this->cstmt_selectPrefs->fetchColumn(2);
							}
							else
							{
								ret = CR_OUT_OF_MEMORY;
							}
						}

						if(ret == 0)
						{
							if(pstruct)
								*pstruct = this->struct_buffer;
							if(pthesaurus)
								*pthesaurus = this->thesaurus_buffer;
							if(pcterms)
								*pcterms = this->cterms_buffer;
							row_count++;
						}
					}
				}
				this->cstmt_selectPrefs->free_result();
			}
		}
	}
	return(ret);
}


// ---------------------------------------------------------------
// SELECT value FROM pref WHERE prop='cterms'
// ---------------------------------------------------------------
int CConnbas_dbox::selectCterms(char **pcterms, unsigned long *cterms_length)
{
	int ret;
	if(!this->cstmt_selectCterms)
	{
		if( (this->cstmt_selectCterms = this->newStmt("SELECT value FROM pref WHERE prop='cterms' LIMIT 1", 0, 1)) )
		{
			this->cstmt_selectCterms->bindo[0].buffer_type = MYSQL_TYPE_STRING;
		}
	}

	if(this->cstmt_selectCterms)
	{
		*pcterms = NULL;
		*cterms_length = 0;
		if(this->cterms_buffer == NULL)
		{
			if( (this->cterms_buffer2 = (char *)(_MALLOC_WHY(this->cterms_buffer2_size = 4096, "connbas_dbox.cpp:selectCterms:cterms_buffer2"))) == NULL)
			{
				// malloc error
				return(CR_OUT_OF_MEMORY);
			}
		}
		this->cstmt_selectCterms->bindo[0].buffer        = (void *)(this->cterms_buffer2);
		this->cstmt_selectCterms->bindo[0].buffer_length = this->cterms_buffer2_size;
		this->cstmt_selectCterms->bindo[0].length        = cterms_length;

		if(this->cstmt_selectCterms->execute() == 0)
		{
			if(this->cstmt_selectCterms->store_result() == 0)
			{
				ret = 0;

				if(this->cstmt_selectCterms->bind_result() == 0)
				{
					if( this->cstmt_selectCterms->fetch() == 0 )
					{
						if( *cterms_length > this->cterms_buffer2_size+1 )
						{
							if( (this->cterms_buffer2 = (char *)_REALLOC((void *)(this->cterms_buffer2), this->cterms_buffer2_size = *cterms_length+1)) )
							{
								this->cstmt_selectCterms->bindo[0].buffer        = (void *)(this->cterms_buffer2);
								this->cstmt_selectCterms->bindo[0].buffer_length = this->cterms_buffer2_size;
								ret = this->cstmt_selectCterms->fetchColumn(0);
							}
							else
							{
								ret = CR_OUT_OF_MEMORY;
							}
						}
						if(ret == 0)
						{
							*pcterms = this->cterms_buffer2;
						}
						this->cstmt_selectCterms->free_result();
					}
					else
					{
						ret = 2;	// fetch error
					}
				}
				else
				{
					ret = 3;	// bind error
				}
			}
		}
	}
	return(ret);
}


// ---------------------------------------------------------------
// LOCK TABLE pref WRITE, thit WRITE
// ---------------------------------------------------------------
int CConnbas_dbox::lockPref()
{
	int ret = 0;
	if(mysql_real_query(this->mysqlCnx, "LOCK TABLES pref WRITE, thit WRITE", 34) != 0)
	{
		ret = -1;
		zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "%s", mysql_error(this->mysqlCnx));
	}
	return(ret);
}

// ---------------------------------------------------------------
// UNLOCK TABLES
// ---------------------------------------------------------------
int CConnbas_dbox::unlockTables()
{
	return(mysql_real_query(this->mysqlCnx, "UNLOCK TABLES", 13)==0 ? 0 : -1);
}


// ---------------------------------------------------------------
// SELECT kword_id, keyword FROM kword
// ---------------------------------------------------------------
int CConnbas_dbox::scanKwords(void ( *callBack)(CConnbas_dbox *connbas, unsigned int kword_id, char *keyword, unsigned long keyword_len) )
{
	unsigned int kword_id;
	char keyword[65];
	unsigned long keyword_length;
	int ret = -1;

	if(!this->cstmt_selectKwords)
	{
		if( (this->cstmt_selectKwords = this->newStmt("SELECT kword_id, keyword FROM kword", 0, 2)) )
		{
			this->cstmt_selectKwords->bindo[0].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_selectKwords->bindo[1].buffer_type = MYSQL_TYPE_STRING;
		}
	}

	if(this->cstmt_selectKwords)
	{
		this->cstmt_selectKwords->bindo[0].buffer        = (void *)(&kword_id);

		this->cstmt_selectKwords->bindo[1].buffer        = (void *)(&keyword);
		this->cstmt_selectKwords->bindo[1].buffer_length = 64;
		this->cstmt_selectKwords->bindo[1].length        = &keyword_length;

		// Bind the result buffers
		if(this->cstmt_selectKwords->bind_result() == 0)
		{
			if(this->cstmt_selectKwords->execute() == 0)
			{
				if(this->cstmt_selectKwords->store_result() == 0)
				{
					ret = 0;		// we will return the number of fetched kwords (WARNING : possible int overflow)
					while(this->cstmt_selectKwords->fetch() == 0)
					{
						if(keyword_length > 64)
							keyword_length = 64;
						keyword[keyword_length] = '\0';
						(*callBack)(this, kword_id, keyword, keyword_length);
						ret++;
					}
					this->cstmt_selectKwords->free_result();
				}
			}
		}
	}
	return(ret);
}


// ---------------------------------------------------------------
// SELECT xpath_id, xpath FROM xpath
// ---------------------------------------------------------------
int CConnbas_dbox::scanXPaths(void ( *callBack)(CConnbas_dbox *connbas, unsigned int xpath_id, char *xpath, unsigned long xpath_len) )
{
	unsigned int xpath_id;
	char xpath[151];
	unsigned long xpath_length;
	int ret = -1;

	if(!this->cstmt_selectXPaths)
	{
		if( (this->cstmt_selectXPaths = this->newStmt("SELECT xpath_id, xpath FROM xpath", 0, 2)) )
		{
			this->cstmt_selectXPaths->bindo[0].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_selectXPaths->bindo[1].buffer_type = MYSQL_TYPE_STRING;
		}
	}

	if(this->cstmt_selectXPaths)
	{
		this->cstmt_selectXPaths->bindo[0].buffer        = (void *)(&xpath_id);

		this->cstmt_selectXPaths->bindo[1].buffer        = (void *)(&xpath);
		this->cstmt_selectXPaths->bindo[1].buffer_length = 150;
		this->cstmt_selectXPaths->bindo[1].length        = &xpath_length;

		// Bind the result buffers
		if(this->cstmt_selectXPaths->bind_result() == 0)
		{
			if(this->cstmt_selectXPaths->execute() == 0)
			{
				if(this->cstmt_selectXPaths->store_result() == 0)
				{
					ret = 0;		// we will return the number of fetched xpath (WARNING : possible int overflow)
					while(this->cstmt_selectXPaths->fetch() == 0)
					{
						if(xpath_length > 150)
							xpath_length = 150;
						xpath[xpath_length] = '\0';
						(*callBack)(this, xpath_id, xpath, xpath_length);
						ret++;
					}
					this->cstmt_selectXPaths->free_result();
				}
			}
		}
	}
	return(ret);
}


// ---------------------------------------------------------------
// SELECT record_id, xml FROM record ORDER BY record_id
// ---------------------------------------------------------------
int CConnbas_dbox::scanRecords(void (*callBack)(CConnbas_dbox *connbas, unsigned int record_id, char *xml, unsigned long len), SBAS_STATUS *sbas_status )
{
	unsigned long xml_length;
	unsigned int record_id;
	int ret = -1;

	int prefsIndexes_value = 1;
	int prefsIndexes_toReindex = 0;

//printf("-- 1\n");
	if(!this->cstmt_selectRecords)
	{
//printf("-- 1-2\n");
		if( (this->cstmt_selectRecords = this->newStmt("SELECT record_id, xml FROM record WHERE (status & 7) IN (4,5,6) ORDER BY record_id ASC", 0, 2)) )
		{
//printf("-- 1-3\n");
			this->cstmt_selectRecords->bindo[0].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_selectRecords->bindo[1].buffer_type = MYSQL_TYPE_STRING;
		}
	}

	if(this->cstmt_selectRecords)
	{
		// ------  bind  'record_id'
		this->cstmt_selectRecords->bindo[0].buffer      = (void *)(&record_id);

		// ------  bind  'xml'
		if(this->xml_buffer == NULL)
		{
			if( (this->xml_buffer = (char *)(_MALLOC_WHY(this->xml_buffer_size = 4096, "connbas_dbox.cpp:scanRecords:xml"))) == NULL)
			{
				// malloc error
				return(-2);
			}
		}
		this->cstmt_selectRecords->bindo[1].buffer        = (void *)(this->xml_buffer);
		this->cstmt_selectRecords->bindo[1].buffer_length = this->xml_buffer_size;
		this->cstmt_selectRecords->bindo[1].length        = &xml_length;

		if(this->cstmt_selectRecords->execute() == 0)
		{
			if(this->cstmt_selectRecords->store_result() == 0)
			{
				int row_count = 0;
				ret = 0;
				int checkReindexN = 0;
				while(ret==0 && this->cstmt_selectRecords->bind_result() == 0 && this->cstmt_selectRecords->fetch() == 0)
				{
					if(*sbas_status == SBAS_STATUS_TOSTOP )		// if the thread must stop, no more callback
						continue;								// but fetch() till the end

					// check prefs 'indexes' every 20 records
					if(prefsIndexes_toReindex == 0 && prefsIndexes_value>0 && checkReindexN++ > 20)
					{
						this->selectPrefsIndexes(&prefsIndexes_value, &prefsIndexes_toReindex);
						checkReindexN = 0;
					}
					if(prefsIndexes_toReindex > 0 || prefsIndexes_value==0)				// if the whole base has been asked to reindex, or indexation suspended
					{
						// no more callback
						continue;	// but fetch() till the end
					}

					if(xml_length > this->xml_buffer_size+1)
					{
						if( (this->xml_buffer = (char *)_REALLOC((void *)(this->xml_buffer), this->xml_buffer_size = xml_length+1)) )
						{
							this->cstmt_selectRecords->bindo[1].buffer        = (void *)(this->xml_buffer);
							this->cstmt_selectRecords->bindo[1].buffer_length = this->xml_buffer_size;
							//	printf("buffer reallocated to %ld\n", xmlbuffer_length);
							ret = this->cstmt_selectRecords->fetchColumn(1);
						}
						else
						{
							// malloc error
							ret = CR_OUT_OF_MEMORY;
						}
					}

					if(ret == 0)
					{
						(*callBack)(this, record_id, this->xml_buffer, xml_length);
						row_count++;
					}
				}
				this->cstmt_selectRecords->free_result();
			}
		}
	}
	return(ret);
}


// ---------------------------------------------------------------
// INSERT INTO thit (record_id, xpath_id, name, value, hitstart, hitlen) VALUES (?, ?, ?, ?, ?, ?) 
// ---------------------------------------------------------------
int CConnbas_dbox::insertTHit(unsigned int record_id, unsigned int xpath_id, char *name, char *value, unsigned int hitstart, unsigned int hitlen )
{
	int ret = -1;

	if(!this->cstmt_insertTHit)
	{
		if( (this->cstmt_insertTHit = this->newStmt("INSERT INTO thit (record_id, xpath_id, name, value, hitstart, hitlen) VALUES (?, ?, ?, ?, ?, ?)", 6, 0)) )
		{
			this->cstmt_insertTHit->bindi[0].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_insertTHit->bindi[1].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_insertTHit->bindi[2].buffer_type = MYSQL_TYPE_STRING;

			this->cstmt_insertTHit->bindi[3].buffer_type = MYSQL_TYPE_STRING;

			this->cstmt_insertTHit->bindi[4].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_insertTHit->bindi[5].buffer_type = MYSQL_TYPE_LONG;
		}
	}

	if(this->cstmt_insertTHit)
	{
		this->cstmt_insertTHit->bindi[0].buffer        = (void *)(&record_id);
		this->cstmt_insertTHit->bindi[1].buffer        = (void *)(&xpath_id);

		unsigned long len_name = strlen((char *)name);
		if(len_name > 32)
			len_name = 32;
		this->cstmt_insertTHit->bindi[2].buffer        = (void *)(name);
		this->cstmt_insertTHit->bindi[2].buffer_length = len_name;
		this->cstmt_insertTHit->bindi[2].length        = &len_name;

		unsigned long len_value = strlen((char *)value);
		if(len_value > 100)
			len_value = 100;
		this->cstmt_insertTHit->bindi[3].buffer        = (void *)(value);
		this->cstmt_insertTHit->bindi[3].buffer_length = len_value;
		this->cstmt_insertTHit->bindi[3].length        = &len_value;

		this->cstmt_insertTHit->bindi[4].buffer        = (void *)(&hitstart);
		this->cstmt_insertTHit->bindi[5].buffer        = (void *)(&hitlen);


		if (this->cstmt_insertTHit->bind_param() == 0)
		{
			if(this->cstmt_insertTHit->execute() == 0)
			{
				// the thit has been created
				ret = 0;
			}
		}
	}
	return(ret);
}


// ---------------------------------------------------------------
// INSERT INTO prop (record_id, xpath_id, name, value) VALUES (?, ?, ?, ?) 
// ---------------------------------------------------------------
int CConnbas_dbox::insertProp(unsigned int record_id, unsigned int xpath_id, char *name, char *value, int type)
{
	int ret = -1;

	if(!this->cstmt_insertProp)
	{
		if( (this->cstmt_insertProp = this->newStmt("INSERT INTO prop (record_id, xpath_id, name, value, type) VALUES (?, ?, ?, ?, ?)", 6, 0)) )
		{
			this->cstmt_insertProp->bindi[0].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_insertProp->bindi[1].buffer_type = MYSQL_TYPE_LONG;

			this->cstmt_insertProp->bindi[2].buffer_type = MYSQL_TYPE_STRING;

			this->cstmt_insertProp->bindi[3].buffer_type = MYSQL_TYPE_STRING;

			this->cstmt_insertProp->bindi[4].buffer_type = MYSQL_TYPE_LONG;
		}
	}

	if(this->cstmt_insertProp)
	{
		this->cstmt_insertProp->bindi[0].buffer        = (void *)(&record_id);
		this->cstmt_insertProp->bindi[1].buffer        = (void *)(&xpath_id);

		unsigned long len_name = strlen(name);
		if(len_name > 32)
			len_name = 32;
		this->cstmt_insertProp->bindi[2].buffer        = (void *)(name);
		this->cstmt_insertProp->bindi[2].buffer_length = len_name;
		this->cstmt_insertProp->bindi[2].length        = &len_name;

		unsigned long len_value = strlen(value);
		if(len_value > 100)
			len_value = 100;
		this->cstmt_insertProp->bindi[3].buffer        = (void *)(value);
		this->cstmt_insertProp->bindi[3].buffer_length = len_value;
		this->cstmt_insertProp->bindi[3].length        = &len_value;

		this->cstmt_insertProp->bindi[4].buffer        = (void *)(&type);

		if (this->cstmt_insertProp->bind_param() == 0)
		{
			if(this->cstmt_insertProp->execute() == 0)
			{
				ret = 0;
			}
		}
	}
	return(ret);
}


// ---------------------------------------------------------------
// UPDATE record SET status=status & ~4 WHERE record_id=? 
// ---------------------------------------------------------------
int CConnbas_dbox::updateRecord_lock(unsigned int record_id)
{
	int ret = -1;

	if(!this->cstmt_updateRecord_lock)
	{
		if( (this->cstmt_updateRecord_lock = this->newStmt("UPDATE record SET status=status & ~4 WHERE record_id=?", 1, 0)) )
		{
			this->cstmt_updateRecord_lock->bindi[0].buffer_type = MYSQL_TYPE_LONG;
		}
	}

	if(this->cstmt_updateRecord_lock)
	{
		this->cstmt_updateRecord_lock->bindi[0].buffer      = (void *)(&record_id);

		if (this->cstmt_updateRecord_lock->bind_param() == 0)
		{
			if(this->cstmt_updateRecord_lock->execute() == 0)
			{
				ret = 0;
			}
		}
	}
	return(ret);
}



// ---------------------------------------------------------------
// UPDATE record SET status=status | 4 WHERE record_id=? 
// ---------------------------------------------------------------
int CConnbas_dbox::updateRecord_unlock(unsigned int record_id)
{
	int ret = -1;

	if(!this->cstmt_updateRecord_unlock)
	{
		if( (this->cstmt_updateRecord_unlock = this->newStmt("UPDATE record SET status=status | 4 WHERE record_id=?", 1, 0)) )
		{
			this->cstmt_updateRecord_unlock->bindi[0].buffer_type = MYSQL_TYPE_LONG;
		}
	}

	if(this->cstmt_updateRecord_unlock)
	{
		this->cstmt_updateRecord_unlock->bindi[0].buffer      = (void *)(&record_id);

		if (this->cstmt_updateRecord_unlock->bind_param() == 0)
		{
			if(this->cstmt_updateRecord_unlock->execute() == 0)
			{
				ret = 0;
			}
		}
	}
	return(ret);
}



void CConnbas_dbox::close()
{
	this->isok = false;

	if(this->struct_buffer)
	{
		_FREE(this->struct_buffer);
		this->struct_buffer = NULL;
		this->struct_buffer_size = 0;
	}
	if(this->thesaurus_buffer)
	{
		_FREE(this->thesaurus_buffer);
		this->thesaurus_buffer = NULL;
		this->thesaurus_buffer_size = 0;
	}
	if(this->cterms_buffer)
	{
		_FREE(this->cterms_buffer);
		this->cterms_buffer = NULL;
		this->cterms_buffer_size = 0;
	}
	if(this->xml_buffer)
	{
		_FREE(this->xml_buffer);
		this->xml_buffer = NULL;
		this->xml_buffer_size = 0;
	}

	CConnbas::close();
}

