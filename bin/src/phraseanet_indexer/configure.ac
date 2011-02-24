#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.60])

AC_INIT([phraseanet_indexer], [3.6.1.1])

AM_INIT_AUTOMAKE(no-dependencies no-define no-dist no-installinfo)

AC_OUTPUT(Makefile)

# AC_CONFIG_SRCDIR([simpleopt/SimpleOpt.h])
# AC_CONFIG_HEADERS([config.h])

# Checks for programs.
AC_PROG_CXX
# AC_PROG_CC

XTRAINCPATHS="-I/usr/include/libxml2/"

# Checks for MySQL lib
AC_MSG_CHECKING([MySQL client library])
for i in / /usr /usr/local /opt/local; do
	if test -r $i/lib/libmysqlclient.a; then
		MYSQL_LIBDIR=$i/lib/
	elif test -r $i/lib/mysql/libmysqlclient.a; then
		MYSQL_LIBDIR=$i/lib/mysql/
	elif test -r $i/lib/mysql5/mysql/libmysqlclient.a; then
		MYSQL_LIBDIR=$i/lib/mysql5/mysql/
	fi
done
if test -z "$MYSQL_LIBDIR"; then
	AC_MSG_ERROR([Cannot find MySQL client library])
else
	AC_MSG_RESULT([$MYSQL_LIBDIR])
	LDFLAGS="$LDFLAGS -L$MYSQL_LIBDIR"
fi


# Checks for MySQL headers.
AC_MSG_CHECKING([MySQL headers])
for i in / /usr /usr/local /opt/local; do
	if test -r $i/include/mysql.h; then
		MYSQL_HEADERDIR=$i/include/
	elif test -r $i/include/mysql/mysql.h; then
		MYSQL_HEADERDIR=$i/include/mysql/
	elif test -r $i/include/mysql5/mysql/mysql.h; then
		MYSQL_HEADERDIR=$i/include/mysql5/mysql/
	fi
done
if test -z "$MYSQL_HEADERDIR"; then
	AC_MSG_ERROR([Cannot find MySQL headers])
else
	AC_MSG_RESULT([$MYSQL_HEADERDIR])
	XTRAINCPATHS="$XTRAINCPATHS -I$MYSQL_HEADERDIR"
fi


CFLAGS="$CFLAGS $XTRAINCPATHS"
CPPFLAGS="$CPPFLAGS $XTRAINCPATHS"

AC_CHECK_HEADERS([fcntl.h limits.h locale.h malloc.h memory.h netinet/in.h stdlib.h string.h sys/socket.h sys/timeb.h unistd.h wchar.h])

# AC_CHECK_HEADER([libxml/tree.h],, [AC_MSG_ERROR([cannot find xml headers])])

# Checks for typedefs, structures, and compiler characteristics.
AC_HEADER_STDBOOL
AC_C_INLINE
AC_TYPE_SIZE_T

# Checks for library functions.
AC_FUNC_ERROR_AT_LINE
AC_FUNC_MALLOC
AC_FUNC_REALLOC
AC_CHECK_FUNCS([memset select setlocale socket strcasecmp strchr strrchr])


AC_CHECK_LIB(xml2,xmlParseDoc,,AC_MSG_ERROR([cannot link to libxml2]))
AC_CHECK_LIB(expat,XML_Parse,,AC_MSG_ERROR([cannot link to libexpat]))
AC_CHECK_LIB(mysqlclient,my_init,,AC_MSG_ERROR([cannot link to libmysqlclient]))
AC_CHECK_LIB(pthread,pthread_create,,AC_MSG_ERROR([cannot link to pthread]))

# AC_CONFIG_FILES([simpleopt/Makefile])
AC_OUTPUT
