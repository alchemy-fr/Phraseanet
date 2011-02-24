#ifndef SBAS_INCLUDED
#define SBAS_INCLUDED 1

#include "platform_dependent.h"

#include <memory.h>
#include <string.h>
#include <stdio.h>
#include "_syslog.h"

#ifdef WIN32
# include <process.h>
#else
# include <time.h>
# include <pthread.h>
#endif

enum SBAS_STATUS
{
	SBAS_STATUS_NEW = 0,
	SBAS_STATUS_OLD = 1,
	SBAS_STATUS_TOSTOP = 2,
	SBAS_STATUS_TODELETE = 3,
	SBAS_STATUS_UNKNOWN = 4,
//	SBAS_STATUS_TORESTART = 5
};



class CSbas
{
	friend class CSbasList;
	private:
		static const char *statlibs[5];
	public:
		unsigned int sbas_id;
		char host[65];
		unsigned int  port;
		char dbname[65];
		char user[65];
		char pwd[65];

		class CSbas *next;
		SBAS_STATUS status;
		ATHREAD idxthread;		// the indexing thread on this sbas

		CSbas(unsigned int sbas_id, char *host, unsigned int port, char *dbname, char *user, char *pwd);
		~CSbas();
		bool operator ==(const class CSbas &x);
};


class CSbasList
{
	private:
		;
	public:
		class CSbas *first;
		CSbasList();
		~CSbasList();
		CSbas *add(unsigned int sbas_id, char *host, unsigned int port, char *dbname, char *user, char *pwd);
		void dump(char *title=NULL);
};


#endif
