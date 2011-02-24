dnl $Id$
dnl config.m4 for extension phrasea2

AC_DEFUN([MYSQL_LIB_CHK], [
  str="$MYSQL_DIR/$1/lib$MYSQL_LIBNAME.*"
  for j in `echo $str`; do
    if test -r $j; then
      MYSQL_LIB_DIR=$MYSQL_DIR/$1
      break 2
    fi
  done
])


PHP_ARG_ENABLE(phrasea2, whether to enable phrasea2 support,
[  --enable-phrasea2           Enable phrasea2 support])

dnl PHP_ARG_WITH(phrasea2, for phrasea2 support,
dnl [  --with-phrasea2[=DIR]      Include phrasea2 support. DIR is the phrasea2 base directory])


if test "$PHP_PHRASEA2" != "no"; then
dnl  PHP_EXPAND_PATH($MYSQL_INCLUDE, MYSQL_INCLUDE)

  PHRASEAVERSION=
  MYSQL_DIR=
  MYSQL_INC_DIR=
  PHP_LIBDIR=

  dnl *************** get version ****************
  AC_MSG_CHECKING([================================================= phrasea extension version number])
  if test -r ./_VERSION.h; then
    PHRASEAVERSION=`cat ./_VERSION.h | egrep -e "#define[[:blank:]]*PHDOTVERSION" | tr -d '\r' | sed  -e "s/#define[[:blank:]]*PHDOTVERSION[[:blank:]]*\(.*\)[[:space:]]*$/\1/"`
    AC_MSG_RESULT([$PHRASEAVERSION])
  else
    AC_MSG_ERROR([Cannot find file _VERSION.h])
  fi

  dnl *************** search mysql includes dir ****************
  AC_MSG_CHECKING([MySQL includes dir])
  for i in $PHP_PHRASEA2 /usr/local /usr; do
    if test -r $i/include/mysql/mysql.h; then
      MYSQL_DIR=$i
      MYSQL_INC_DIR=$i/include/mysql
      break
    elif test -r $i/include/mysql.h; then
      MYSQL_DIR=$i
      MYSQL_INC_DIR=$i/include
      break
    fi
  done
  if test -z "$MYSQL_DIR"; then
    AC_MSG_ERROR([Cannot find MySQL header files under $PHP_PHRASEA2.])
  else
    AC_MSG_RESULT([$MYSQL_INC_DIR])
    PHP_ADD_INCLUDE($MYSQL_INC_DIR)
  fi

  dnl *************** search mysql library ****************
  AC_MSG_CHECKING([MySQL library])
  if test "$enable_maintainer_zts" = "yes"; then
    MYSQL_LIBNAME=mysqlclient_r
  else
    MYSQL_LIBNAME=mysqlclient
  fi
  case $host_alias in
    *netware*[)]
      MYSQL_LIBNAME=mysql
      ;;
  esac

  dnl for compat with PHP 4 build system
  if test -z "$PHP_LIBDIR"; then
    PHP_LIBDIR=lib
  fi

  for i in $PHP_LIBDIR $PHP_LIBDIR/mysql; do
    MYSQL_LIB_CHK($i)
  done

  if test -z "$MYSQL_LIB_DIR"; then
    AC_MSG_ERROR([Cannot find lib$MYSQL_LIBNAME under $MYSQL_DIR.])
  else
    AC_MSG_RESULT([$MYSQL_LIB_DIR / $MYSQL_LIBNAME])
  fi




  dnl # --with-phrasea2 -> check with-path
  dnl SEARCH_PATH="/usr/local /usr"     # you might want to change this
  dnl SEARCH_FOR="/include/phrasea2.h"  # you most likely want to change this
  dnl if test -r $PHP_PHRASEA2/$SEARCH_FOR; then # path given as parameter
  dnl   PHRASEA2_DIR=$PHP_PHRASEA2
  dnl else # search default path list
  dnl   AC_MSG_CHECKING([for phrasea2 files in default path])
  dnl   for i in $SEARCH_PATH ; do
  dnl     if test -r $i/$SEARCH_FOR; then
  dnl       PHRASEA2_DIR=$i
  dnl       AC_MSG_RESULT(found in $i)
  dnl     fi
  dnl   done
  dnl fi
  dnl
  dnl if test -z "$PHRASEA2_DIR"; then
  dnl   AC_MSG_RESULT([not found])
  dnl   AC_MSG_ERROR([Please reinstall the phrasea2 distribution])
  dnl fi

  dnl # --with-phrasea2 -> add include path
  dnl PHP_ADD_INCLUDE($PHRASEA2_DIR/include)

  dnl # --with-phrasea2 -> check for lib and symbol presence
  dnl LIBNAME=phrasea2 # you may want to change this
  dnl LIBSYMBOL=phrasea2 # you most likely want to change this 

  dnl PHP_CHECK_LIBRARY($LIBNAME,$LIBSYMBOL,
  dnl [
  dnl   PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $PHRASEA2_DIR/lib, PHRASEA2_SHARED_LIBADD)
  dnl   AC_DEFINE(HAVE_PHRASEA2LIB,1,[ ])
  dnl ],[
  dnl   AC_MSG_ERROR([wrong phrasea2 lib version or lib not found])
  dnl ],[
  dnl   -L$PHRASEA2_DIR/lib -lm -ldl
  dnl ])
  dnl
  dnl PHP_SUBST(PHRASEA2_SHARED_LIBADD)


  dnl ****************** tell we will compile .cpp files *****************
  PHP_REQUIRE_CXX()
  CPPFLAGS=-Wno-write-strings

  dnl ****************** tell we will link with g++ *****************
  CC=g++
 
  PHP_ADD_LIBRARY_WITH_PATH($MYSQL_LIBNAME, $MYSQL_LIB_DIR, PHRASEA2_SHARED_LIBADD)
  PHP_ADD_LIBRARY_WITH_PATH([uuid], [/usr/lib], PHRASEA2_SHARED_LIBADD)
  PHP_SUBST(PHRASEA2_SHARED_LIBADD)

  PHP_NEW_EXTENSION(phrasea2, phrasea2.cpp \
								phrasea_engine/cache.cpp\
								phrasea_engine/emptyw.cpp \
								phrasea_engine/fetchresults.cpp \
								phrasea_engine/grouping.cpp \
								phrasea_engine/phrasea_clock_t.cpp \
								phrasea_engine/qtree.cpp \
								phrasea_engine/query.cpp \
								phrasea_engine/session.cpp \
								phrasea_engine/uuid.cpp \
								phrasea_engine/sql.cpp \
								phrasea_engine/subdefs.cpp \
								phrasea_engine/xmlcaption.cpp \
				, $ext_shared, [], -DCOMPILE_DL_PHRASEA2 -DMYSQLENCODE=utf8)
fi
