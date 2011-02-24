#include "sbas.h"
#include "connbas_abox.h"
#include <memory.h>
#include <string.h>
#include <stdio.h>
#include "_syslog.h"

extern CSyslog zSyslog;

//--------------------------------------------------------------------
// CConnbas_abox

CConnbas_abox::CConnbas_abox(const char *host, const char *user, const char *passwd, const char *szDB, unsigned int port)
{
	// int ret = 0;

	this->cstmt_listSbas = NULL;
	this->cstmt_getSbas = NULL;

	this->open(host, user, passwd, szDB, port);
}

CConnbas_abox::~CConnbas_abox()
{
	this->close();
}


// ---------------------------------------------------------------------
// SELECT host, port, dbname, sbas_id FROM sbas WHERE indexable=1
// ---------------------------------------------------------------------
void CConnbas_abox::listSbas2(CSbasList *SbasList, bool oldsbas_flag)
{
	unsigned long host_length;
	unsigned long dbname_length;
	unsigned long user_length;
	unsigned long pwd_length;

	char *sql = (char *)(oldsbas_flag ? "SELECT host, port, dbname, sbas_id, user, pwd FROM sbas WHERE indexable=1" : "SELECT host, port, dbname, xbas_id, user, pwd FROM xbas WHERE indexable=1");
	//printf("sql=%s\n", sql);
	if(!this->cstmt_listSbas)
	{
		if( (this->cstmt_listSbas = this->newStmt(sql , 0, 6)) )
		{
			this->cstmt_listSbas->bindo[0].buffer_type   = MYSQL_TYPE_STRING;
			this->cstmt_listSbas->bindo[0].buffer        = (void *)(&(this->parms_listSbas.host));
			this->cstmt_listSbas->bindo[0].buffer_length = 64;
			this->cstmt_listSbas->bindo[0].length        = &host_length;

			this->cstmt_listSbas->bindo[1].buffer_type   = MYSQL_TYPE_LONG;
			this->cstmt_listSbas->bindo[1].buffer        = (void *)(&(this->parms_listSbas.port));

			this->cstmt_listSbas->bindo[2].buffer_type   = MYSQL_TYPE_STRING;
			this->cstmt_listSbas->bindo[2].buffer        = (void *)(&(this->parms_listSbas.dbname));
			this->cstmt_listSbas->bindo[2].buffer_length = 64;
			this->cstmt_listSbas->bindo[2].length        = &dbname_length;

			this->cstmt_listSbas->bindo[3].buffer_type   = MYSQL_TYPE_LONG;
			this->cstmt_listSbas->bindo[3].buffer        = (void *)(&(this->parms_listSbas.sbas_id));

			this->cstmt_listSbas->bindo[4].buffer_type   = MYSQL_TYPE_STRING;
			this->cstmt_listSbas->bindo[4].buffer        = (void *)(&(this->parms_listSbas.user));
			this->cstmt_listSbas->bindo[4].buffer_length = 64;
			this->cstmt_listSbas->bindo[4].length        = &user_length;

			this->cstmt_listSbas->bindo[5].buffer_type   = MYSQL_TYPE_STRING;
			this->cstmt_listSbas->bindo[5].buffer        = (void *)(&(this->parms_listSbas.pwd));
			this->cstmt_listSbas->bindo[5].buffer_length = 64;
			this->cstmt_listSbas->bindo[5].length        = &pwd_length;
		}
	}
	if(this->cstmt_listSbas)
	{
		if(this->cstmt_listSbas->bind_result() == 0)
		{
			if(this->cstmt_listSbas->execute() == 0)
			{
				if(this->cstmt_listSbas->store_result() == 0)
				{
					while(this->cstmt_listSbas->fetch() == 0)
					{
						SbasList->add(this->parms_listSbas.sbas_id
									, this->parms_listSbas.host
									, this->parms_listSbas.port
									, this->parms_listSbas.dbname
									, this->parms_listSbas.user
									, this->parms_listSbas.pwd );
						// SbasList->add(this->parms_listSbas );
					}

					this->cstmt_listSbas->free_result();
				}
			}
		}
	}
}

int CConnbas_abox::listSbas(char *buff, int buffsize)
{
	int ret = 0;
	buffsize -= 6;				// keep room for the '|...\n\0';
	int l;
	unsigned long host_length;
	unsigned long dbname_length;

	if(!this->cstmt_listSbas)
	{
		if( (this->cstmt_listSbas = this->newStmt("SELECT host, port, dbname, sbas_id, indexable FROM sbas", 0, 5)) )
		{
			this->cstmt_listSbas->bindo[0].buffer_type = MYSQL_TYPE_STRING;
			this->cstmt_listSbas->bindo[0].buffer      = (void *)(&(this->parms_listSbas.host));
			this->cstmt_listSbas->bindo[0].buffer_length = 64;
			this->cstmt_listSbas->bindo[0].length      = &host_length;

			this->cstmt_listSbas->bindo[1].buffer_type = MYSQL_TYPE_LONG;
			this->cstmt_listSbas->bindo[1].buffer      = (void *)(&(this->parms_listSbas.port));

			this->cstmt_listSbas->bindo[2].buffer_type = MYSQL_TYPE_STRING;
			this->cstmt_listSbas->bindo[2].buffer      = (void *)(&(this->parms_listSbas.dbname));
			this->cstmt_listSbas->bindo[2].buffer_length = 64;
			this->cstmt_listSbas->bindo[2].length      = &dbname_length;

			this->cstmt_listSbas->bindo[3].buffer_type = MYSQL_TYPE_LONG;
			this->cstmt_listSbas->bindo[3].buffer      = (void *)(&(this->parms_listSbas.sbas_id));

			this->cstmt_listSbas->bindo[4].buffer_type = MYSQL_TYPE_LONG;
			this->cstmt_listSbas->bindo[4].buffer      = (void *)(&(this->parms_listSbas.indexable));
		}
	}
	if(this->cstmt_listSbas)
	{
		if(this->cstmt_listSbas->bind_result() == 0)
		{
			if(this->cstmt_listSbas->execute() == 0)
			{
				if(this->cstmt_listSbas->store_result() == 0)
				{
					while(this->cstmt_listSbas->fetch() == 0)
					{
						if(buffsize >= 206)
						{
							l = sprintf(buff, "|%s| %3d : %s:%d:%s\n", (this->parms_listSbas.indexable ? "X" : " ")
																		, this->parms_listSbas.sbas_id
																		, this->parms_listSbas.host
																		, this->parms_listSbas.port
																		, this->parms_listSbas.dbname
										);
							buff += l;
							buffsize -= l;
						}
						else
						{
							if(buffsize > 0)
							{
								memcpy(buff, "|...\n", 5);
								buff += 5;
								buffsize = 0;
							}
						}
					}
					*buff = '\0';
					this->cstmt_listSbas->free_result();
				}
			}
		}
	}
	return(ret);
}



// ---------------------------------------------------------------------
// SELECT host, port, dbname, user, pwd FROM sbas WHERE sbas_id=?
// ---------------------------------------------------------------------

int CConnbas_abox::getSbas(unsigned int sbas_id, char **ret_host, unsigned int *ret_port, char **ret_dbname, char **ret_user, char **ret_pwd )
{
	int ret = -1;
	unsigned long host_length;
	unsigned long dbname_length;
	unsigned long user_length;
	unsigned long pwd_length;

	if(!this->cstmt_getSbas)
	{
		if( (this->cstmt_getSbas = this->newStmt("SELECT host, port, dbname, user, pwd FROM sbas WHERE sbas_id=?", 1, 5)) )
		{
			this->cstmt_getSbas->bindo[0].buffer_type   = MYSQL_TYPE_STRING;
			this->cstmt_getSbas->bindo[0].buffer        = (void *)(&(this->parms_getSbas.host));
			this->cstmt_getSbas->bindo[0].buffer_length = 64;
			this->cstmt_getSbas->bindo[0].length        = &host_length;

			this->cstmt_getSbas->bindo[1].buffer_type   = MYSQL_TYPE_LONG;
			this->cstmt_getSbas->bindo[1].buffer        = (void *)(&(this->parms_getSbas.port));

			this->cstmt_getSbas->bindo[2].buffer_type   = MYSQL_TYPE_STRING;
			this->cstmt_getSbas->bindo[2].buffer        = (void *)(&(this->parms_getSbas.dbname));
			this->cstmt_getSbas->bindo[2].buffer_length = 64;
			this->cstmt_getSbas->bindo[2].length        = &dbname_length;

			this->cstmt_getSbas->bindo[3].buffer_type   = MYSQL_TYPE_STRING;
			this->cstmt_getSbas->bindo[3].buffer        = (void *)(&(this->parms_getSbas.user));
			this->cstmt_getSbas->bindo[3].buffer_length = 64;
			this->cstmt_getSbas->bindo[3].length        = &user_length;

			this->cstmt_getSbas->bindo[4].buffer_type   = MYSQL_TYPE_STRING;
			this->cstmt_getSbas->bindo[4].buffer        = (void *)(&(this->parms_getSbas.pwd));
			this->cstmt_getSbas->bindo[4].buffer_length = 64;
			this->cstmt_getSbas->bindo[4].length        = &pwd_length;

			this->cstmt_getSbas->bindi[0].buffer_type   = MYSQL_TYPE_LONG;
			this->cstmt_getSbas->bindi[0].buffer        = (void *)(&sbas_id);
		}
	}
	if(this->cstmt_getSbas)
	{
		if (this->cstmt_getSbas->bind_param() == 0)
		{
			if(this->cstmt_getSbas->bind_result() == 0)
			{
				if(this->cstmt_getSbas->execute() == 0)
				{
					if(this->cstmt_getSbas->store_result() == 0)
					{
						if(this->cstmt_getSbas->fetch() == 0)
						{
							*ret_host   = this->parms_getSbas.host;
							*ret_port   = this->parms_getSbas.port;
							*ret_dbname = this->parms_getSbas.dbname;
							*ret_user   = this->parms_getSbas.user;
							*ret_pwd    = this->parms_getSbas.pwd;

							ret = 0;
						}
						this->cstmt_getSbas->free_result();
					}
				}
			}
		}
	}
	return(ret);
}

void CConnbas_abox::close()
{
	this->isok = false;

	CConnbas::close();
}

