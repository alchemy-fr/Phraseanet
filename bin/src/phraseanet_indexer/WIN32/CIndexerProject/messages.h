#ifndef __MESSAGES_H__
#define __MESSAGES_H__

//        German  = 0x0407:Messages_GER
////////////////////////////////////////
// Eventlog categories
//
// Categories always have to be the first entries in a message file!
//
//
//  Values are 32 bit values layed out as follows:
//
//   3 3 2 2 2 2 2 2 2 2 2 2 1 1 1 1 1 1 1 1 1 1
//   1 0 9 8 7 6 5 4 3 2 1 0 9 8 7 6 5 4 3 2 1 0 9 8 7 6 5 4 3 2 1 0
//  +---+-+-+-----------------------+-------------------------------+
//  |Sev|C|R|     Facility          |               Code            |
//  +---+-+-+-----------------------+-------------------------------+
//
//  where
//
//      Sev - is the severity code
//
//          00 - Success
//          01 - Informational
//          10 - Warning
//          11 - Error
//
//      C - is the Customer code flag
//
//      R - is a reserved bit
//
//      Facility - is the facility code
//
//      Code - is the facility's status code
//
//
// Define the facility codes
//


//
// Define the severity codes
//


//
// MessageId: MSGCAT_PROG_START
//
// MessageText:
//
//  Program start
//
#define MSGCAT_PROG_START                0x00000001L

//
// MessageId: MSGCAT_PROG_END
//
// MessageText:
//
//  Program end
//
#define MSGCAT_PROG_END                  0x00000002L

//
// MessageId: MSGCAT_THREAD_START
//
// MessageText:
//
//  Thread start
//
#define MSGCAT_THREAD_START              0x00000003L

//
// MessageId: MSGCAT_THREAD_END
//
// MessageText:
//
//  Thread end
//
#define MSGCAT_THREAD_END                0x00000004L

//
// MessageId: MSGCAT_PRELOAD
//
// MessageText:
//
//  Preload tables
//
#define MSGCAT_PRELOAD                   0x00000005L

//
// MessageId: MSGCAT_SQLERR
//
// MessageText:
//
//  SQL error
//
#define MSGCAT_SQLERR                    0x00000006L

//
// MessageId: MSGCAT_XMLERR
//
// MessageText:
//
//  XML error
//
#define MSGCAT_XMLERR                    0x00000007L

//
// MessageId: MSGCAT_FLUSH
//
// MessageText:
//
//  Flush
//
#define MSGCAT_FLUSH                     0x00000008L

//
// MessageId: MSGCAT_INDEXING
//
// MessageText:
//
//  Indexing
//
#define MSGCAT_INDEXING                  0x00000009L

//
// MessageId: MSGCAT_SIGNAL
//
// MessageText:
//
//  Signal
//
#define MSGCAT_SIGNAL                    0x0000000AL

//
// MessageId: MSGCAT_THESAURUS
//
// MessageText:
//
//  Thesaurus
//
#define MSGCAT_THESAURUS                 0x0000000BL

//
// MessageId: MSGCAT_STRUCTURE
//
// MessageText:
//
//  Structure
//
#define MSGCAT_STRUCTURE                 0x0000000CL

////////////////////////////////////////
// Events
//
//
// MessageId: EVENT_ALL
//
// MessageText:
//
//  %1
//
#define EVENT_ALL                        0x0000000DL

////////////////////////////////////////
// Additional messages
//
//
// MessageId: IDS_HELLO
//
// MessageText:
//
//  Hello World!
//
#define IDS_HELLO                        0x000003E8L

//
// MessageId: IDS_GREETING
//
// MessageText:
//
//  Hello %1. How do you do?
//
#define IDS_GREETING                     0x000003E9L


#endif  //__MESSAGES_H__
