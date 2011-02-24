#ifndef _SYSLOG_WIN32_INCLUDED
#define _SYSLOG_WIN32_INCLUDED 1


#include <stdio.h>
#include <stdlib.h>
#include <memory.h>
#include <string.h>
#include <windows.h> 
/*
http://www.codeproject.com/system/mctutorial.asp?df=100&forumid=15526&exp=0&select=1238561
*/

#include <tchar.h>

#include "messages.h"

class CSyslog
{
	private:
		TCHAR *ident;
		HANDLE hEventLog;
		static unsigned short category[12];
		int where;
	public:
		enum { TOTTY, TOLOG };

		enum PHRASEA_LOG_LEVEL {
						  LOGL_DEBUG   = 0
						, LOGL_INFO    = 1
						, LOGL_WARNING = 2
						, LOGL_ERR     = 3
						};
		static char *libLevel[4];

		enum PHRASEA_LOG_CATEGORY {
						  LOGC_PROG_START   = 0
						, LOGC_PROG_END     = 1
						, LOGC_THREAD_START = 2
						, LOGC_THREAD_END   = 3
						, LOGC_ACNX_OK		= 12
						, LOGC_PRELOAD      = 4
						, LOGC_SQLERR       = 5
						, LOGC_XMLERR       = 6
						, LOGC_FLUSH        = 7
						, LOGC_INDEXING     = 8
						, LOGC_SIGNAL       = 9
						, LOGC_THESAURUS    = 10
						, LOGC_STRUCTURE    = 11
						};
		static char *libCategory[12];

		CSyslog();
		~CSyslog();
		void install(const TCHAR *appname);
		void open(const TCHAR *appname, int where);
		void log(PHRASEA_LOG_LEVEL level, PHRASEA_LOG_CATEGORY category, TCHAR *fmt, ...);
		void close();
//		log(TCHAR *fmt, ...);
};


#endif