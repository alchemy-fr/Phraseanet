#include <stdio.h>
#include <windows.h>
#include <winbase.h>
#include <winsvc.h>
#include <process.h>

#define NTSERVICENAME "Phraseanet CIndexer"
#define NTSERVICEDESC "Indexe les records des bases Phrasea publiées dans une AppBOX"

VOID UnInstall(char* pName);
VOID Install(char* pPath, char* pName, char* pDesc) ;
