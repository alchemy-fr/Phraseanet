#ifndef CONNBAS_INCLUDED
#define CONNBAS_INCLUDED 1

// #include <string.h>
#include <stdio.h>
#include <memory.h>
#include <expat.h>
#include "_syslog.h"

// les headers mysql
//#ifdef WIN32
//# include <config-win.h>
//#endif
#include <mysql.h>
#include <mysqld_error.h>
#include <errmsg.h>

class CConnbas
{
	friend class CMysqlStmt;

	protected:
		MYSQL *mysqlCnx;

		class CMysqlStmt *firstStmt;

	public:
		bool debug;
		bool isok;
		bool crashed;
		void *userData;	// ici on peut passer/r�cup�rer des data dans les callback
		CConnbas();
		~CConnbas();
		int open(const char *host, const char *user, const char *passwd, const char *szDB, unsigned int port);
		void close();

		int execute(char *sql, int lenght);

		CMysqlStmt *newStmt(const char *sql, int nBindIn, int nBindOut);
};

class CMysqlStmt
{
	friend class CConnbas;

	private:
		class CMysqlStmt *next;
		char *sql;
		class CConnbas *connbas;	// back ref to cnx
		unsigned int nBindIn;
		unsigned int nBindOut;
	public:
		bool debug;
		MYSQL_STMT *stmt;
		MYSQL_BIND *bindi;
		MYSQL_BIND *bindo;

		CMysqlStmt(CConnbas *conn, const char *sql, unsigned int nBindIn, unsigned int nBindOut);

		~CMysqlStmt();

		const char *error();

		unsigned int errNo();

		int execute();

		int bind_param();

		int bind_result();

		int store_result();

		int fetch();

		int fetchColumn(unsigned int column);

		void free_result();

		void data_seek(my_ulonglong offset);
};


#endif
