#ifndef TRUE
	#define TRUE 1
#endif
#ifndef FALSE
	#define FALSE 0
#endif
#ifndef true
	#define true 1
#endif
#ifndef false
	#define false 0
#endif

// SQLRES *SQLCONN::store_result();
#ifndef SQL_H
#define SQL_H 1

#include "phrasea_types.h"	// define NODE

class SQLCONN
{
public:
	SQLCONN(char *host, int port, char *user, char *passwd, char *dbname, bool trace=false);
	~SQLCONN();
	char *ukey;
	bool isok();
	bool query(char *sql, int len = -1);
	const char *error();
	int escape_string(char *str, int len = -1, char *outbuff = NULL);
	void phrasea_query(char *sql, NODE *n, bool reverse=false);
	my_ulonglong affected_rows();
	// #WIN - _int64 affected_rows();
	void *get_native_conn();

	// class SQLRES *store_result();
	friend class SQLRES;
	friend class SQLROW;
private:
	int sqlengine;
	bool connok;
//	long pgnres;
//	char strport[64];
	MYSQL mysql_conn;
	int mysql_active_result_id;
};

class SQLROW
{
public:
	SQLROW();
	~SQLROW();
	char *field(int n);
	friend class SQLRES;
	friend class SQLCONN;
private:
	MYSQL_ROW   row;
	class SQLRES *parent_res;
};

class SQLRES
{
public:
	SQLRES(SQLCONN *parent_conn);
	~SQLRES();
	bool query(char *sql);
	class SQLROW *fetch_row();
	int get_nrows();
	friend class SQLROW;
	friend class SQLCONN;
	unsigned long *fetch_lengths();
private:
	class SQLROW sqlrow;
	MYSQL_RES   *res;
	SQLCONN *parent_conn;
	int nrows, ncols, currow;
};

#endif

