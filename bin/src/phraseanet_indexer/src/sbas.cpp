#include "sbas.h"

extern CSyslog zSyslog;



const char *CSbas::statlibs[5] = { "NEW", "OLD", "TOSTOP", "TODELETE", "UNKNOWN" };

CSbas::CSbas(unsigned int sbas_id, char *host, unsigned int port, char *dbname, char *user, char *pwd)
{
	this->next = NULL;
	this->sbas_id = sbas_id;
	memcpy(this->host, host, 65);
	this->port = port;
	memcpy(this->dbname, dbname, 65);
	memcpy(this->user, user, 65);
	memcpy(this->pwd, pwd, 65);
	this->status = SBAS_STATUS_NEW;
	this->idxthread = (ATHREAD)NULLTHREAD;
//	this->running = false;
}

CSbas::~CSbas()
{
}

bool CSbas::operator ==(const class CSbas &x)
{
	return(
//			this->sbas_id == x.sbas_id
		this->port == x.port
		&& (strcmp(this->host, x.host)==0)
		&& (strcmp(this->dbname, x.dbname)==0)
		&& (strcmp(this->user, x.user)==0)
		&& (strcmp(this->pwd, x.pwd)==0)
		);
}

CSbasList::CSbasList()
{
	this->first = NULL;
}
CSbasList::~CSbasList()
{
	CSbas *f;
	while( (f = this->first) )
	{
		this->first = f->next;
		delete f;
	}
}
CSbas *CSbasList::add(unsigned int sbas_id, char *host, unsigned int port, char *dbname, char *user, char *pwd)
{
	CSbas *f = this->first;
	this->first = new CSbas(sbas_id, host, port, dbname, user, pwd);
	this->first->next = f;
	return(this->first);
}

void CSbasList::dump(char *title)
{
	int buffsize = 666;
	char buff[33 + 666 + 33 + 1];
	char *p = buff;
	int l;

	memcpy(p, "/---- Dump SbasList ------------\n", 33);
	p += 33;

	if(title)
	{
		l = 5 + strlen(title) + 4;
		if(buffsize > l)
		{
			l = sprintf(p, "| -[ %s ]-\n", title);
			p += l;
			buffsize -= l;
		}
	}

	for(CSbas *f=this->first; f; f=f->next)
	{
		l = 10 + 34 + 10 + strlen(CSbas::statlibs[f->status]) + 2;
		if(buffsize > l)
		{
			l = sprintf(p, (char*)"| SBAS_ID=%d  (status=%s)\n", f->sbas_id, CSbas::statlibs[f->status]);
			p += l;
			buffsize -= l;
		}
	}
	memcpy(p, "\\-------------------------------\n\0", 34);

	printf("%s", buff);
}
