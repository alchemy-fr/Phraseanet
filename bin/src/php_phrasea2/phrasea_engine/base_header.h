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

#include <sys/types.h>
#include <sys/timeb.h>
#include <math.h>
#include <stdio.h>
#include <stdlib.h>
#include <fcntl.h>


#pragma warning(push)
#pragma warning(disable:4005)		// supprime le warning sur double def de _WIN32_WINNT
#include "php.h"
#pragma warning(pop)

//#include "php_globals.h"
#include "ext/standard/info.h"
#include "ext/standard/php_string.h"
#include "zend_exceptions.h"

#ifdef PHP_WIN32
# include <winsock.h>
# define signal(a, b) NULL
#elif defined(NETWARE)
# include <sys/socket.h>
# define signal(a, b) NULL
#else
# if HAVE_SIGNAL_H
#  include <signal.h>
# endif
# if HAVE_SYS_TYPES_H
#  include <sys/types.h>
# endif
# include <netdb.h>
# include <netinet/in.h>
#endif

// ******* tosee : mysql.h d�plac�
#include <mysql.h>

// ******* tosee : fichiers headers de pgsql
#ifdef PHP_WIN32
#ifdef PGSUPPORT
# include <libpq-fe.h>
#endif
  // ******* tosee : HAVE_MEMMOVE est d�j� d�fini dans un include de php (via php.h)
# undef HAVE_MEMMOVE
#ifdef PGSUPPORT
# include <pg_config_os.h>
#endif
#else
#  undef PACKAGE_VERSION
#  undef PACKAGE_TARNAME
#  undef PACKAGE_NAME
#  undef PACKAGE_STRING
#  undef PACKAGE_BUGREPORT
#ifdef PGSUPPORT
# include <postgres.h>
#endif
#endif

/*
#include "../php_phrasea2/phrasea_ext.h"
#include "../php_phrasea2/phrasea_types.h"
#include "sql.h"
*/
#include "trace_memory.h"

#define RAMLOG "/home/gaulier/ramlog.txt"

