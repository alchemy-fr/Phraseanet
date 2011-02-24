#ifndef CONNBAS_ABOX_INCLUDED
#define CONNBAS_ABOX_INCLUDED 1

#include "sbas.h"
#include "connbas.h"


class CConnbas_abox:public CConnbas
{
	private:
		// ---------------------------------------------------------------------
		// SELECT host, port, dbname, sbas_id FROM sbas
		// SELECT host, port, dbname, sbas_id, user, pwd FROM sbas
		// ---------------------------------------------------------------------
		CMysqlStmt *cstmt_listSbas;
		struct
		{
			char host[65];
			char dbname[65];
			unsigned int sbas_id;
			unsigned int port;
			char user[65];
			char pwd[65];
			unsigned int indexable;
		}
		parms_listSbas;
		
		// ---------------------------------------------------------------------
		// SELECT host, port, dbname, user, pwd FROM sbas WHERE sbas_id=?
		// ---------------------------------------------------------------------
		CMysqlStmt *cstmt_getSbas;
		struct
		{
			char host[65];
			char dbname[65];
			char user[65];
			char pwd[65];
			unsigned int port;
			unsigned int sbas_id;
		}
		parms_getSbas;
		
	public:
		void *userData;	// here we can pass/get data to callback
		CConnbas_abox(const char *host, const char *user, const char *passwd, const char *szDB, unsigned int port);
		~CConnbas_abox();
		void close();
		int listSbas(char *buff, int buffsize);
		void listSbas2(CSbasList *SbasList, bool oldsbas_flag);
		int getSbas(unsigned int sbas_id, char **ret_host, unsigned int *ret_port, char **ret_dbname, char **ret_user, char **ret_pwd );

/*
		MYSQL_STMT *newStmt()
		{
			CMysqlStmt *s;
			if(s = new CMysqlStmt())
			{
				s->next = this->firstStmt;
				this->firstStmt = s;
				return(s->stmt);
			}
			return(NULL);
		}
*/
};

#endif


