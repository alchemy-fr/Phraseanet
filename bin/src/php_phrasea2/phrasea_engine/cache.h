#ifndef CACHE_H
#define CACHE_H 1

#include "sql.h"	// define SQLCONN

class CACHE_COLL
{
public:
	CACHE_COLL(long coll_id, long base_id, char *name, char *prefs, bool registered);
	~CACHE_COLL();
	friend class CACHE_BASE;
	friend class CACHE_SESSION;
private:
	bool registered;
	void dump();
	void serialize_php(zval *colllist);
	long *serialize_bin(long *p);
	long coll_id;
	long base_id;
	char *name;
	char *prefs;
//	char *phserver_host;
//	long phserver_port;
	class CACHE_COLL *nextcoll;
	long binsize;
	long name_lenPAD;
	long prefs_lenPAD;
//	long phserver_host_lenPAD;
	long get_binsize();
};

class CACHE_BASE
{
public:
	CACHE_BASE(long base_id, char *host, long port, char *user, char *passwd, char *dbname, char *xmlstruct, long sbas_id, char *viewname, bool online);
	~CACHE_BASE();
	CACHE_COLL *addcoll(long coll_id, long base_id, char *name, char *prefs, bool registered);
	friend class CACHE_SESSION;
private:
	bool online;
	SQLCONN *conn;
	long base_id;
	long sbas_id;
	char *host;
	long host_lenPAD;
	char *viewname;
	long viewname_lenPAD;
	long port;
	char *user;
	long user_lenPAD;
	char *passwd;
	long passwd_lenPAD;
	long engine;
	char *dbname;
	long dbname_lenPAD;
	char *xmlstruct;
	long xmlstruct_lenPAD;
	long binsize;
	void dump();
	void serialize_php(zval *zbaselist, bool everything);
	long *serialize_bin(long *p);
	CACHE_COLL *firstcoll;
	class CACHE_BASE *nextbase;
	long get_binsize();
	long get_local_base_id(long distant_coll_id);
	long get_local_base_id2(long distant_coll_id);
};

class CACHE_SESSION
{
public:
	CACHE_SESSION(long session_id, SQLCONN *epublisher);
	bool save();
	bool restore(long session_id);
	~CACHE_SESSION();
	CACHE_BASE *addbase(long base_id, char *host, long port, char *user, char *passwd, char *dbname, char *xmlstruct, long sbas_id, char *viewname, bool online);
	void dump();
	void serialize_php(zval *result, bool everything);
//	char *serialize_bin(unsigned int *lbin);
	int serialize_bin(long *binbuff);
	void unserialize_bin(char *bin);
	long get_session_id();
	long get_local_base_id(long local_base_id, long distant_coll_id);
	long get_local_base_id2(long local_base_id, long distant_coll_id);
	long get_distant_coll_id(long local_base_id);
	SQLCONN *connect(long base_id);
	void set_registered(long local_base_id, bool registered);
private:
	SQLCONN *epublisher;
	long session_id;
//	long usr_id;
	CACHE_BASE *firstbase;
	long get_binsize();
//	long *binbuff;
};

#endif
