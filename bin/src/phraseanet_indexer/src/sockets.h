#ifndef SOCKETS_INCLUDED
#define SOCKETS_INCLUDED 1

#include "platform_dependent.h"

#ifdef WIN32
//# include <Winsock2.h>
# define SD_BOTH 2
#else
# include <unistd.h>
# include <fcntl.h>
# include <sys/socket.h>
# include <netinet/in.h>
# include <errno.h> 
# define INVALID_SOCKET -1
# define SOCKET_ERROR -1
# define SD_BOTH 2
# define closesocket(socket) close(socket)
typedef int SOCKET;
typedef struct sockaddr_in SOCKADDR_IN;
typedef struct sockaddr SOCKADDR;
typedef int DWORD;
typedef struct timeval TIMEVAL;
#endif


class CSocket
{
	private:
		//CHAR Buffer[8192];
		//WSABUF DataBuf;
		//DWORD SendBytes;
		//DWORD RecvBytes;
	public:
		CSocket();
		~CSocket();
	//	void remove();
		SOCKET socket;
		CSocket *prevSocket, *nextSocket;
};

class CSocketList
{
	private:
	public:
		CSocketList();
		~CSocketList();
		CSocket *firstSocket;
		void add(SOCKET socket);
		CSocket *remove(CSocket *socket);
		int length();
};

#endif
