
#include "syslog_xnix.h"

// #include <cstring>

#ifndef NULL
	#define NULL 0
#endif
#ifndef FALSE
	#define FALSE 0
#endif
#ifndef TRUE
	#define TRUE 0
#endif


const char *CSyslog::libLevel[4] = {
								  "LOGL_DEBUG"
								, "LOGL_INFO"
								, "LOGL_WARNING"
								, "LOGL_ERR"
							};
const char *CSyslog::libCategory[13] = {
								  "LOGC_PROG_START"
								, "LOGC_PROG_END"
								, "LOGC_THREAD_START"
								, "LOGC_THREAD_END"
								, "LOGC_PRELOAD"
								, "LOGC_SQLERR"
								, "LOGC_XMLERR"
								, "LOGC_FLUSH"
								, "LOGC_INDEXING"
								, "LOGC_SIGNAL"
								, "LOGC_THESAURUS"
								, "LOGC_STRUCTURE"
								, "LOGC_ACNX_OK"
								};

CSyslog::CSyslog()
{
 //std::string s;
	this->where = TOTTY;
}

CSyslog::~CSyslog()
{
	this->close();
}

void CSyslog::open(const char *ident, int where)
{
	this->close();

	this->where = where;

	if(where == TOLOG)
	{
		openlog("phraseanet_cindexer", LOG_PID, LOG_DAEMON);
	}
}

void CSyslog::log(PHRASEA_LOG_LEVEL level, PHRASEA_LOG_CATEGORY category, const char *fmt, ...)
{
	va_list vl;
	char buff[5000];
	va_start(vl, fmt);

	vsnprintf(buff, 5000, fmt, vl);

//	printf("%s\n", buff);

	if(this->where == TOLOG)
	{
		switch(level)
		{
			case CSyslog::LOGL_DEBUG:
				syslog(LOG_DEBUG, "%s", buff); 
				break;
			case CSyslog::LOGL_INFO:
				syslog(LOG_INFO, "%s", buff); 
				break;
			case CSyslog::LOGL_WARNING:
				syslog(LOG_WARNING, "%s", buff); 
				break;
			case CSyslog::LOGL_ERR:
				syslog(LOG_ERR, "%s", buff); 
				break;
			default:
				break;	
		}
	}
	else
	{
		// TOTTY
		printf("[%s].[%s] :\n%s\n", this->libLevel[level], this->libCategory[category], buff);
	}
}

void CSyslog::close()
{
	if(this->where == TOLOG)
		closelog();
	this->where = TOTTY;
}

