#include "connbas.h"
#include <memory.h>
#include <string.h>
#include <stdio.h>
#include "_syslog.h"

#include "trace_memory.h"


//--------------------------------------------------------------------
// CConnbas

extern CSyslog zSyslog;
extern const char *arg_mycharset;


CConnbas::CConnbas()
{
	this->isok = false;
	this->debug = false;
	this->crashed = false;
	this->mysqlCnx = NULL;

	this->firstStmt = NULL;

	this->userData = NULL;
}

int CConnbas::open(const char *host, const char *user, const char *passwd, const char *szDB, unsigned int port)
{
	int ret = 0;
	if( (this->mysqlCnx = mysql_init((MYSQL *)0)) )
	{
#ifdef MYSQL_DATA_TRUNCATED		// mysql 5 
		my_bool flag = 0;			// don't report (even if i can deal with)
		mysql_options(this->mysqlCnx, MYSQL_REPORT_DATA_TRUNCATION, (char*)(&flag) );
#endif
		if( mysql_real_connect( this->mysqlCnx, host, user, passwd, szDB, port, NULL, CLIENT_COMPRESS ) )
		{
			if(arg_mycharset != NULL)
			{
				if(mysql_set_character_set(this->mysqlCnx, arg_mycharset) != 0)
				{
					if(this->debug)
						zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "Can't set character set %s on database %s !", arg_mycharset, szDB ) ;
					ret = -4 ;
				}
			}
			if(ret==0)
			{
#if MYSQL_VERSION_ID >= 50000			// mysql 5 
				my_bool reconnect = 0;
				mysql_options(this->mysqlCnx, MYSQL_OPT_RECONNECT, &reconnect);
#endif
				if( mysql_select_db( this->mysqlCnx, szDB ) == 0 )
				{
					// ok
				}
				else
				{
					if(this->debug)
						zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "Can't select the %s database !", szDB ) ;
					ret = -2 ;
				}
			}
		}
		else
		{
			if(this->debug)
				zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "Can't connect to the mysql server on port %d !",	MYSQL_PORT ) ;
			ret = -1 ;
		}
	}
	else
	{
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "Can't init the mysql server" ) ;
		ret = -3 ;
	}
	if(ret == 0)
		this->isok = true;
	else
		this->close();
	return(ret);
}

CConnbas::~CConnbas()
{
	this->close();
}


// ---------------------------------------------------------------
// free sql
// ---------------------------------------------------------------
int CConnbas::execute(char *sql, int lenght)
{
	if(this->crashed)
	{
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "CConnbas::execute : rejected (base is on error state)");
		return(-1);
	}
	try
	{
		int ret = 0;
		if(mysql_real_query(this->mysqlCnx, sql, lenght) != 0)
		{
			ret = -1;
			if(this->debug)
				zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "%s", mysql_error(this->mysqlCnx));
			switch(mysql_errno(this->mysqlCnx))
			{
				// erreurs fatales reconnues
				case ER_SERVER_SHUTDOWN:		// 1053 = 'Server shutdown in progress'
				case ER_NORMAL_SHUTDOWN:		// 1077 = '%s: Normal shutdown'
				case ER_GOT_SIGNAL:				// 1078 = '%s: Got signal %d. Aborting!'
				case ER_SHUTDOWN_COMPLETE:		// 1079 = '%s: Shutdown complete'
				case ER_FORCING_CLOSE:			// 1080 = '%s: Forcing close of thread %ld user: '%s' '
				case CR_OUT_OF_MEMORY:
				case CR_CONN_HOST_ERROR:		// 2003 = 'Can't connect to MySQL server on '%s' (%d)'
				case CR_SERVER_LOST:			// 2013 = 'Lost connection to MySQL server during query'
				case CR_SERVER_GONE_ERROR:
					this->crashed = 1;
					break;
				// erreurs non reconnues (non fatales ?)
				default:
					this->crashed = 0;
					break;
			}
		}
		return(ret);
	}
	catch(...)
	{
		this->crashed = 1;
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "exception in CConnbas::execute");
		return(-1);
	}
}


void CConnbas::close()
{
	this->isok = false;

	CMysqlStmt *stmt;
	while( (stmt = this->firstStmt) )
	{
		this->firstStmt = stmt->next;
		delete stmt;
	}
	if(this->mysqlCnx)
	{
		if(this->crashed)
		{
			if(this->debug)
				zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "CConnbas::close : rejected (base is on error state)");
			return;
		}
		try
		{
			mysql_close( this->mysqlCnx ) ;
		}
		catch(...)
		{
			this->crashed = 1;
			if(this->debug)
				zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "exception in CConnbas::close");
		}
		this->mysqlCnx = NULL;
	}
}

CMysqlStmt *CConnbas::newStmt(const char *sql, int nBindIn, int nBindOut)
{
// printf("newStmt(\"%s\" \n", sql);
	CMysqlStmt *s;
	if( (s = new CMysqlStmt(this, sql, nBindIn, nBindOut)) )
	{
		if(s->stmt)
		{
			s->next = this->firstStmt;
			this->firstStmt = s;
		}
		else
		{
			delete s;
			s = NULL;
		}
	}
	return(s);
}

CMysqlStmt::CMysqlStmt(CConnbas *conn, const char *sql, unsigned int nBindIn, unsigned int nBindOut)
{
  register unsigned int i;
	this->debug = true;
	this->bindi = this->bindo = NULL;
	this->next = NULL;
	this->stmt = NULL;
	this->sql = NULL;
	this->connbas = conn;	// back ref to cnx
	this->nBindIn = 0;
	this->nBindOut = 0;
	if(nBindIn > 0)
	{
		if( (this->bindi = new MYSQL_BIND[nBindIn]) == NULL)
		{
			return;
		}
		this->nBindIn = nBindIn;
		memset(this->bindi, 0, nBindIn*sizeof(MYSQL_BIND));
		for(i=0; i<nBindIn; i++)
		{
			this->bindi[i].is_null     = (my_bool*)0;	// data is always not null
			this->bindi[i].is_unsigned = 1;
		}
	}
	if(nBindOut > 0)
	{
		if( (this->bindo = new MYSQL_BIND[nBindOut]) == NULL)
		{
			if(this->bindi)
			{
				delete[] this->bindi;
				this->bindi = NULL;
				this->nBindIn = 0;
			}
			return;
		}
		this->nBindOut = nBindOut;
		memset(this->bindo, 0, nBindOut*sizeof(MYSQL_BIND));
		for(i=0; i<nBindOut; i++)
		{
			this->bindo[i].is_null     = (my_bool*)0;	// data is always not null
			this->bindo[i].is_unsigned = 1;
		}
	}
	if( (this->stmt = mysql_stmt_init(conn->mysqlCnx)) == NULL)
	{
		if(this->bindo)
		{
			delete[] this->bindo;
			this->bindo = NULL;
			this->nBindIn = 0;
		}
		if(this->bindi)
		{
			delete[] this->bindi;
			this->bindi = NULL;
			this->nBindOut = 0;
		}
		return;
	}
	if(mysql_stmt_prepare(this->stmt, sql, strlen(sql)) != 0)
	{
		zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "CMysqlStmt error : %s", mysql_stmt_error(this->stmt) );
		mysql_stmt_close(this->stmt);
		this->stmt = NULL;
		if(this->bindo)
		{
			delete[] this->bindo;
			this->bindo = NULL;
			this->nBindIn = 0;
		}
		if(this->bindi)
		{
			delete[] this->bindi;
			this->bindi = NULL;
			this->nBindOut = 0;
		}
		return;
	}
	int l = strlen(sql)+1;
	if( (this->sql = (char *)(_MALLOC_WHY(l, "connbas.cpp:CMysqlStmt:sql"))) )
		memcpy(this->sql, sql, l);
}

CMysqlStmt::~CMysqlStmt()
{
	if(this->sql)
		_FREE(this->sql);
	if(this->stmt)
		mysql_stmt_close(this->stmt);
	if(this->bindo)
		delete[] this->bindo;
	if(this->bindi)
		delete[] this->bindi;
}

const char *CMysqlStmt::error()
{
	return(mysql_stmt_error(this->stmt));
}

unsigned int CMysqlStmt::errNo()
{
	return(mysql_stmt_errno(this->stmt));
}

int CMysqlStmt::execute()
{
	int r;
	if(this->connbas->crashed)
	{
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "CMysqlStmt::execute : rejected (base is on error state)");
		return(-1);
	}
	try
	{
		if((r = mysql_stmt_execute(this->stmt)) != 0)
		{
			if(this->debug)
				zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "CMysqlStmt::execute : mysql_stmt_execute error (%d) : '%s', return(%d)", this->errNo(), this->error(), r);
			switch(this->errNo())
			{
				// erreurs non fatales
				case ER_DUP_KEY:				// 1022 = 'Can't write; duplicate key in table '%s''
				case ER_DUP_ENTRY:
					break;
				// erreurs fatales reconnues
				case ER_SERVER_SHUTDOWN:		// 1053 = 'Server shutdown in progress'
				case ER_NORMAL_SHUTDOWN:		// 1077 = '%s: Normal shutdown'
				case ER_GOT_SIGNAL:				// 1078 = '%s: Got signal %d. Aborting!'
				case ER_SHUTDOWN_COMPLETE:		// 1079 = '%s: Shutdown complete'
				case ER_FORCING_CLOSE:			// 1080 = '%s: Forcing close of thread %ld user: '%s' '
				case ER_UNKNOWN_STMT_HANDLER:	// 1243 = 'Unknown prepared statement handler (%.*s) given to %s'
				case CR_CONN_HOST_ERROR:		// 2003 = 'Can't connect to MySQL server on '%s' (%d)'
				case CR_SERVER_LOST:			// 2013 = 'Lost connection to MySQL server during query'
					this->connbas->crashed = 1;
					break;
				// erreurs non reconnues (fatales ?)
				default:
					this->connbas->crashed = 1;
					break;
			}
			// this->connbas->crashed = 1;
			return(-1);
		}
		return(r);
	}
	catch(...)
	{
		this->connbas->crashed = 1;
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "exception in CMysqlStmt::execute");
		return(-1);
	}
}

int CMysqlStmt::bind_param()
{
	int r;
	if(this->connbas->crashed)
	{
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "CMysqlStmt::bind_param : rejected (base is on error state)");
		return(-1);
	}
	try
	{
		if((r = mysql_stmt_bind_param(this->stmt, this->bindi)) != 0)
		{
			if(this->debug)
				zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "CMysqlStmt::bind_param : mysql_stmt_bind_param error (%d) : '%s'", this->errNo(), this->error());
			switch(this->errNo())
			{
				// erreurs fatales reconnues
				case ER_SERVER_SHUTDOWN:		// 1053 = 'Server shutdown in progress'
				case ER_NORMAL_SHUTDOWN:		// 1077 = '%s: Normal shutdown'
				case ER_GOT_SIGNAL:				// 1078 = '%s: Got signal %d. Aborting!'
				case ER_SHUTDOWN_COMPLETE:		// 1079 = '%s: Shutdown complete'
				case ER_FORCING_CLOSE:			// 1080 = '%s: Forcing close of thread %ld user: '%s' '
				case ER_UNKNOWN_STMT_HANDLER:	// 1243 = 'Unknown prepared statement handler (%.*s) given to %s'
				case CR_OUT_OF_MEMORY:
				case CR_CONN_HOST_ERROR:		// 2003 = 'Can't connect to MySQL server on '%s' (%d)'
				case CR_SERVER_LOST:			// 2013 = 'Lost connection to MySQL server during query'
				case CR_SERVER_GONE_ERROR:
				case CR_UNSUPPORTED_PARAM_TYPE:
					this->connbas->crashed = 1;
					break;
				// erreurs non reconnues (fatales ?)
				default:
					this->connbas->crashed = 1;
					break;
			}
		}
		return(r);
	}
	catch(...)
	{
		this->connbas->crashed = 1;
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "exception in CMysqlStmt::bind_param");
		return(-1);
	}
}

int CMysqlStmt::bind_result()
{
	int r;
	if(this->connbas->crashed)
	{
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "CMysqlStmt::bind_result : rejected (base is on error state)");
		return(-1);
	}
	try
	{
		if((r = mysql_stmt_bind_result(this->stmt, this->bindo)) != 0)
		{
			if(this->debug)
				zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "CMysqlStmt::bind_result : mysql_stmt_bind_result error (%d) : '%s'", this->errNo(), this->error());

			switch(this->errNo())
			{
				// erreurs fatales reconnues
				case ER_SERVER_SHUTDOWN:		// 1053 = 'Server shutdown in progress'
				case ER_NORMAL_SHUTDOWN:		// 1077 = '%s: Normal shutdown'
				case ER_GOT_SIGNAL:				// 1078 = '%s: Got signal %d. Aborting!'
				case ER_SHUTDOWN_COMPLETE:		// 1079 = '%s: Shutdown complete'
				case ER_FORCING_CLOSE:			// 1080 = '%s: Forcing close of thread %ld user: '%s' '
				case ER_UNKNOWN_STMT_HANDLER:	// 1243 = 'Unknown prepared statement handler (%.*s) given to %s'
				case CR_OUT_OF_MEMORY:
				case CR_CONN_HOST_ERROR:		// 2003 = 'Can't connect to MySQL server on '%s' (%d)'
				case CR_SERVER_LOST:			// 2013 = 'Lost connection to MySQL server during query'
				case CR_SERVER_GONE_ERROR:
				case CR_UNSUPPORTED_PARAM_TYPE:
					this->connbas->crashed = 1;
					break;
				// erreurs non reconnues (fatales ?)
				default:
					this->connbas->crashed = 1;
					break;
			}
		}
		return(r);
	}
	catch(...)
	{
		this->connbas->crashed = 1;
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "exception in CMysqlStmt::bind_result");
		return(-1);
	}
}

int CMysqlStmt::store_result()
{
	int r;
	if(this->connbas->crashed)
	{
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "CMysqlStmt::store_result : rejected (base is on error state)");
		return(-1);
	}
	try
	{
		if((r = mysql_stmt_store_result(this->stmt)) != 0)
		{
			if(this->debug)
				zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "CMysqlStmt::store_result : mysql_stmt_store_result (%d) : '%s'", this->errNo(), this->error());
			switch(this->errNo())
			{
				// erreurs fatales reconnues
				case ER_SERVER_SHUTDOWN:		// 1053 = 'Server shutdown in progress'
				case ER_NORMAL_SHUTDOWN:		// 1077 = '%s: Normal shutdown'
				case ER_GOT_SIGNAL:				// 1078 = '%s: Got signal %d. Aborting!'
				case ER_SHUTDOWN_COMPLETE:		// 1079 = '%s: Shutdown complete'
				case ER_FORCING_CLOSE:			// 1080 = '%s: Forcing close of thread %ld user: '%s' '
				case ER_UNKNOWN_STMT_HANDLER:	// 1243 = 'Unknown prepared statement handler (%.*s) given to %s'
				case CR_OUT_OF_MEMORY:
				case CR_CONN_HOST_ERROR:		// 2003 = 'Can't connect to MySQL server on '%s' (%d)'
				case CR_SERVER_LOST:			// 2013 = 'Lost connection to MySQL server during query'
				case CR_SERVER_GONE_ERROR:
					this->connbas->crashed = 1;
					break;
				// erreurs non reconnues (fatales ?)
				default:
					this->connbas->crashed = 1;
					break;
			}
		}
		return(r);
	}
	catch(...)
	{
		this->connbas->crashed = 1;
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "exception in CMysqlStmt::store_result");
		return(-1);
	}
}

int CMysqlStmt::fetch()
{
	int r;
	if(this->connbas->crashed)
	{
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "CMysqlStmt::fetch : rejected (base is on error state)");
		return(-1);
	}
	try
	{
		if((r = mysql_stmt_fetch(this->stmt)) != 0)
		{
			if(this->debug)
			{
				switch(r)
				{
					case MYSQL_NO_DATA:
						// printf("mysql_stmt_fetch warning : 'MYSQL_NO_DATA'\n");
						break;
#if defined(MYSQL_DATA_TRUNCATED)
					case MYSQL_DATA_TRUNCATED:
						// printf("CMysqlStmt::fetch : mysql_stmt_fetch warning : 'MYSQL_DATA_TRUNCATED'\n");
						break;
#endif
					default:
						// printf("CMysqlStmt::fetch : mysql_stmt_fetch error (%d) : '%s'\n", this->errNo(), this->error());
						switch(this->errNo())
						{
							// erreurs fatales reconnues
							case ER_SERVER_SHUTDOWN:		// 1053 = 'Server shutdown in progress'
							case ER_NORMAL_SHUTDOWN:		// 1077 = '%s: Normal shutdown'
							case ER_GOT_SIGNAL:				// 1078 = '%s: Got signal %d. Aborting!'
							case ER_SHUTDOWN_COMPLETE:		// 1079 = '%s: Shutdown complete'
							case ER_FORCING_CLOSE:			// 1080 = '%s: Forcing close of thread %ld user: '%s' '
							case ER_UNKNOWN_STMT_HANDLER:	// 1243 = 'Unknown prepared statement handler (%.*s) given to %s'
							case CR_OUT_OF_MEMORY:
							case CR_CONN_HOST_ERROR:		// 2003 = 'Can't connect to MySQL server on '%s' (%d)'
							case CR_SERVER_LOST:			// 2013 = 'Lost connection to MySQL server during query'
							case CR_SERVER_GONE_ERROR:
								this->connbas->crashed = 1;
								break;
							// erreurs non reconnues (fatales ?)
							default:
								this->connbas->crashed = 1;
								break;
						}
						break;
				}
			}
		}
		return(r);
	}
	catch(...)
	{
		this->connbas->crashed = 1;
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "exception in CMysqlStmt::fetch");
		return(-1);
	}
}

int CMysqlStmt::fetchColumn(unsigned int column)
{
	int r;
	if(column < this->nBindOut)
		r = mysql_stmt_fetch_column(this->stmt,	&(this->bindo[column]), column, 0);
	else
		r = CR_INVALID_PARAMETER_NO;
	return(r);
}


void CMysqlStmt::free_result()
{
	if(this->connbas->crashed)
	{
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "CMysqlStmt::free_result : rejected (base is on error state)");
		return;
	}
	try
	{
		mysql_stmt_free_result(this->stmt);
	}
	catch(...)
	{
		this->connbas->crashed = 1;
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "exception in CMysqlStmt::free_result");
	}
}

void CMysqlStmt::data_seek(my_ulonglong offset)
{
	if(this->connbas->crashed)
	{
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "CMysqlStmt::data_seek : rejected (base is on error state)");
		return;
	}
	try
	{
		mysql_stmt_data_seek(this->stmt, offset);
	}
	catch(...)
	{
		this->connbas->crashed = 1;
		if(this->debug)
			zSyslog.log(CSyslog::LOGL_ERR, CSyslog::LOGC_SQLERR, "exception in CMysqlStmt::data_seek");
	}
}

