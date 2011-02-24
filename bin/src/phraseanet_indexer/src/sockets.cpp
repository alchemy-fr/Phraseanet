#include "sockets.h"

// #include <stdio.h>

CSocket::CSocket()
{
	// printf("construct socket %08X \n", this);
	this->socket = -1;
	this->prevSocket = this->nextSocket = NULL;
}

CSocket::~CSocket()
{
	// printf("destroy socket %08X \n", this);
	shutdown(this->socket, SD_BOTH);
	closesocket(this->socket);
}

CSocketList::CSocketList()
{
	// printf("construct socketlist %08X \n", this);
	this->firstSocket = NULL;
}

CSocketList::~CSocketList()
{
	// printf("destroy socketlist %08X \n", this);
	CSocket *s;
	while( (s = this->firstSocket) )
	{
		this->firstSocket = s->nextSocket;
		delete s;
	}
}

int CSocketList::length()
{
	int l=0;
	for(CSocket *s=this->firstSocket; s; s=s->nextSocket)
		l++;
	return(l);
}

void CSocketList::add(SOCKET socket)
{
	//printf("add to socketlist %08X \n", this);
	CSocket *s;

	s = new CSocket();
	s->prevSocket = NULL;
	if(this->firstSocket)
		this->firstSocket->prevSocket = s;
	s->nextSocket = this->firstSocket;
	this->firstSocket = s;

	s->socket = socket;

	//printf("socketlist length = %d \n", this->length());
}

CSocket *CSocketList::remove(CSocket *s)
{
	//printf("remove socket %08X \n", s);
	CSocket *ret;
	if(s->prevSocket)
	{
		s->prevSocket->nextSocket = s->nextSocket;
	}
	else
	{
		this->firstSocket = s->nextSocket;
		//printf("  was first, new first = %08X \n", this->firstSocket);
	}
	if( (ret = s->nextSocket) )
	{
		s->nextSocket->prevSocket = s->prevSocket;
	}
	delete(s);
	//printf("  returning ret = %08X \n", ret);
	//printf("socketlist length = %d \n", this->length());
	return(ret);
}


