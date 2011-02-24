#ifndef _SYSLOG_INCLUDED
#define _SYSLOG_INCLUDED 1

#ifdef WIN32
#include "../WIN32/CIndexerProject/syslog_win32.h"
#else
#include "../xNIX/syslog_xnix.h"
#endif

#endif
