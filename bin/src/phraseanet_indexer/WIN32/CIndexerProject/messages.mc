;#ifndef __MESSAGES_H__
;#define __MESSAGES_H__
;


LanguageNames =
    (
        English = 0x0409:Messages_ENU
		French  = 0x040c:Messages_FRA
;//        German  = 0x0407:Messages_GER
    )


;////////////////////////////////////////
;// Eventlog categories
;//
;// Categories always have to be the first entries in a message file!
;//

MessageId       = 1
SymbolicName    = MSGCAT_PROG_START
Severity        = Success
Language        = English
Program start
.
Language        = French
Démarrage programme
.


MessageId       = +1
SymbolicName    = MSGCAT_PROG_END
Severity        = Success
Language        = English
Program end
.
Language        = French
Fin programme
.


MessageId       = +1
SymbolicName    = MSGCAT_THREAD_START
Severity        = Success
Language        = English
Thread start
.
Language        = French
Démarrage thread
.


MessageId       = +1
SymbolicName    = MSGCAT_THREAD_END
Severity        = Success
Language        = English
Thread end
.
Language        = French
Fin thread
.


MessageId       = +1
SymbolicName    = MSGCAT_PRELOAD
Severity        = Success
Language        = English
Preload tables
.
Language        = French
Préchargement tables
.

MessageId       = +1
SymbolicName    = MSGCAT_SQLERR
Severity        = Success
Language        = English
SQL error
.
Language        = French
Erreur SQL
.

MessageId       = +1
SymbolicName    = MSGCAT_XMLERR
Severity        = Success
Language        = English
XML error
.
Language        = French
Erreur XML
.

MessageId       = +1
SymbolicName    = MSGCAT_FLUSH
Severity        = Success
Language        = English
Flush
.
Language        = French
Flush
.

MessageId       = +1
SymbolicName    = MSGCAT_INDEXING
Severity        = Success
Language        = English
Indexing
.
Language        = French
Indexation
.

MessageId       = +1
SymbolicName    = MSGCAT_SIGNAL
Severity        = Success
Language        = English
Signal
.
Language        = French
Signal
.

MessageId       = +1
SymbolicName    = MSGCAT_THESAURUS
Severity        = Success
Language        = English
Thesaurus
.
Language        = French
Thesaurus
.

MessageId       = +1
SymbolicName    = MSGCAT_STRUCTURE
Severity        = Success
Language        = English
Structure
.
Language        = French
Structure
.

;////////////////////////////////////////
;// Events
;//

MessageId       = +1
SymbolicName    = EVENT_ALL
Language        = English
%1
.
Language        = French
%1
.


;////////////////////////////////////////
;// Additional messages
;//

MessageId       = 1000
SymbolicName    = IDS_HELLO
Language        = English
Hello World!
.
Language        = French
Salut le monde!
.

MessageId       = +1
SymbolicName    = IDS_GREETING
Language        = English
Hello %1. How do you do?
.
Language        = French
Salut %1, ça roule?
.

;
;#endif  //__MESSAGES_H__
;