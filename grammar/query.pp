%skip   space           \s

%token  parenthese_     \(
%token _parenthese      \)
%token  bracket_        \[
%token _bracket         \]
%token  quote_          "        -> string
%token  string:string   [^"]+
%token  string:_quote   "        -> default
%token  in              IN
%token  and             AND
%token  or              OR
%token  except          EXCEPT
%token  collection      collection:
%token  word            [^\s\(\)\[\]]+

// relative order of precedence is NOT > XOR > AND > OR

#query:
    primary() ?

primary:
    secondary() ( ::except:: #except primary() )?

secondary:
    ternary() ( ::or:: #or primary() )?

ternary:
    quaternary() ( ::and:: #and primary() )?

quaternary:
    ( collection_filter() #collection) | quinary()

quinary:
    ( group() | term() ) ( ::in:: #in word() )?

collection_filter:
    ::collection:: word()

group:
    ( ::parenthese_:: #group primary() ::_parenthese:: )

term:
    ( bracketed_text() #thesaurus_term ) | ( text() #text )

bracketed_text:
    ::bracket_:: text() ::_bracket::

text:
    ( word() | keyword() | symbol() )+ context()?

#context:
    ::parenthese_:: ( word() )+ ::_parenthese::

word:
    <word> | string()

string:
    ::quote_:: <string> ::_quote::

keyword:
    <in> | <except> | <and> | <or> | <collection>

symbol:
    ::parenthese_:: | ::_parenthese:: | ::bracket_:: | ::_bracket::
