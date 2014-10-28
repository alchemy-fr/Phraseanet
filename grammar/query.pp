%skip   space           \s

%token  quote_          "        -> string
%token  string:string   [^"]+
%token  string:_quote   "        -> default
%token  in              IN
%token  word            \S+

#query:
    expression()+

expression:
    in_expression() | text() | unrestricted_text()

#in_expression:
    text() ::in:: ( <word> | string() )

#text:
    ( <word> | string() )+

#unrestricted_text:
    ( <word> | string() | <in> )+

string:
    ::quote_:: <string> ::_quote::
