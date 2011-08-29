#ifndef CONNBAS_DBOX_INCLUDED
#define CONNBAS_DBOX_INCLUDED 1
#include <libxml/tree.h>
#include <libxml/parser.h>
#include <libxml/xpath.h>
#include <libxml/xpathInternals.h>

#include "connbas.h"
#include "sbas.h"


#if defined(LIBXML_XPATH_ENABLED) && defined(LIBXML_SAX1_ENABLED) && defined(LIBXML_OUTPUT_ENABLED)
#else
	#error XPath support not compiled in libxml
#endif


class CConnbas_dbox:public CConnbas
{
	private:
		// ---------------------------------------------------------------
		// UPDATE record SET status=status & ~2 WHERE record_id IN (?) 
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_setRecordsToReindexTh2;


		// ---------------------------------------------------------------
		// DELETE FROM idx WHERE record_id IN (?)
		// DELETE FROM prop WHERE record_id IN (?)
		// DELETE FROM thit WHERE record_id IN (?)
		// ---------------------------------------------------------------
//		CMysqlStmt *cstmt_delRecRefs2[3];

		// ---------------------------------------------------------------
		// UPDATE pref SET value=?, updated_on=? WHERE prop=cterms
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_updatePref_cterms;


		// ---------------------------------------------------------------
		// SELECT prop, UNIX_TIMESTAMP(updated_on) FROM pref WHERE prop IN('structure', 'cterms', 'thesaurus')
 		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_selectPref_moddates;

		// ---------------------------------------------------------------
		// INSERT INTO kword (kword_id, keyword) VALUES (? , ?) 
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_insertKword;

		// ---------------------------------------------------------------
		// SELECT kword_id FROM kword WHERE keyword=? 
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_selectKword;

		// ---------------------------------------------------------------
		// INSERT INTO idx (record_id, kword_id, iw, xpath_id, hitstart, hitlen) VALUES (?, ?, ?, ?, ?, ?) 
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_insertIdx;

		// ---------------------------------------------------------------
		// INSERT INTO xpath (xpath_id, xpath) VALUES (? , ?) 
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_insertXPath;

		// ---------------------------------------------------------------
		// SELECT xpath_id FROM xpath WHERE xpath=? 
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_selectXPath;

		// ---------------------------------------------------------------
		// UPDATE uids SET uid=uid+? WHERE name=?
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_updateUids;

		// ---------------------------------------------------------------
		// SELECT uid FROM uids WHERE name=?
		// -CMysqlStmt-------------------------------------------------------------
		CMysqlStmt *cstmt_selectUid;

		// ---------------------------------------------------------------
		// SELECT struct, thesaurus, cterms FROM pref WHERE struct_id=0
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_selectPrefs;

		// ---------------------------------------------------------------
		// SELECT cterms FROM pref WHERE struct_id=0
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_selectCterms;


		// ---------------------------------------------------------------
		// SELECT kword_id, keyword FROM kword
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_selectKwords;


		// ---------------------------------------------------------------
		// SELECT xpath_id, xpath FROM xpath
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_selectXPaths;

		// ---------------------------------------------------------------
		// SELECT record_id, xml FROM record WHERE (status & 7) IN (4,5,6) ORDER BY record_id ASC
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_selectRecords;

		
		// ---------------------------------------------------------------
		// INSERT INTO thit (record_id, xpath_id, name, value, hitstart, hitlen) VALUES (?, ?, ?, ?, ?, ?) 
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_insertTHit;


		// ---------------------------------------------------------------
		// INSERT INTO prop (record_id, xpath_id, name, value, type) VALUES (?, ?, ?, ?, ?) 
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_insertProp;



		// ---------------------------------------------------------------
		// UPDATE record SET status=status & ~4 WHERE record_id=?
		// UPDATE record SET status=status | 4 WHERE record_id=?
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_updateRecord_lock;
		CMysqlStmt *cstmt_updateRecord_unlock;


		// ---------------------------------------------------------------
		// SELECT CAST(value AS UNSIGNED), updated_on<created_on AS k FROM pref WHERE prop='indexes' LIMIT 1
		// ---------------------------------------------------------------
		CMysqlStmt *cstmt_needReindex;


		char *struct_buffer;
		size_t struct_buffer_size;

		char *thesaurus_buffer;
		size_t thesaurus_buffer_size;

		char *cterms_buffer;
		size_t cterms_buffer_size;

		char *cterms_buffer2;
		size_t cterms_buffer2_size;

		char *xml_buffer;
		size_t xml_buffer_size;

		// CMysqlStmt *firstStmt;

	public:
		unsigned int sbas_id;	// utile � conserver, par ex. pour inclure dans les messages
		CConnbas_dbox(unsigned int sbas_id, const char *host, const char *user, const char *passwd, const char *szDB, unsigned int port);
		~CConnbas_dbox();
		void close();

		int setRecordsToReindexTh2(char *lrid, unsigned long lrid_len);
		int delRecRefs2(char *lrid, unsigned long lrid_len);
		int updatePref_cterms(char *cterms, unsigned long cterms_size, char *moddate );
		int selectPref_moddates(time_t *struct_moddate, time_t *thesaurus_moddate, time_t *cterms_moddate);
		int insertKword(char *keyword, unsigned long len, unsigned int *kword_id );
		int insertIdx(unsigned int record_id, unsigned int kword_id, unsigned int iw, unsigned int xpath_id, unsigned int hitstart, unsigned int hitlen);
		int insertXPath(char *xpath, unsigned int *xpath_id );
		int selectPrefs(char **pstruct, unsigned long *struct_length, char **pthesaurus, unsigned long *thesaurus_length, char **pcterms, unsigned long *cterms_length);
		int selectCterms(char **pcterms, unsigned long *cterms_length);

		
		int scanKwords(void ( *callBack)(CConnbas_dbox *connbas, unsigned int kword_id, char *keyword, unsigned long keyword_len) );
		int scanXPaths(void ( *callBack)(CConnbas_dbox *connbas, unsigned int xpath_id, char *xpath, unsigned long xpath_len) );
		
		
		int scanRecords(void (*callBack)(CConnbas_dbox *connbas, unsigned int record_id, char *xml, unsigned long len), SBAS_STATUS *running );

		// unsigned long addKeyword(unsigned char *keyword, unsigned int len);
		int lockPref();
		int unlockTables();
		int insertTHit(unsigned int record_id, unsigned int xpath_id, char *name, char *value, unsigned int hitstart, unsigned int hitlen);
		int insertProp(unsigned int record_id, unsigned int xpath_id, char *name, char *value, int type);

		
		unsigned int getID(const char *keyword, unsigned int n=1 );

		int updateRecord_lock(unsigned int record_id);
		int updateRecord_unlock(unsigned int record_id);
		int updateRecord_unlock2(char *lrid, unsigned long lrid_len);

		int selectPrefsIndexes(int *value, int *toReindex);
		void reindexAll();
		// int sql_connect();
		// int getPrefsDates(unsigned long *struct_moddate, unsigned long *thesaurus_moddate, unsigned long *cterms_moddate);
};

#endif


