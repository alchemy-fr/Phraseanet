%skip   space           \s

%token  bracket_        \(
%token _bracket         \)
%token  quote_          "        -> string
%token  string:string   [^"]+
%token  string:_quote   "        -> default
%token  in              IN
%token  and             AND
%token  or              OR
%token  except          EXCEPT
%token  word            [^\s\(\)]+

// relative order of precedence is NOT > XOR > AND > OR

#query:
    primary()

primary:
    secondary() ( ::except:: #except primary() )?

secondary:
    ternary() ( ::or:: #or primary() )?

ternary:
    quaternary() ( ::and:: #and primary() )?

quaternary:
    term() ( ::in:: #in word() )?

term:
    ( ::bracket_:: primary() ::_bracket:: ) | text()

#text:
    ( word() | keyword() | symbol() )+

word:
    <word> | string()

string:
    ::quote_:: <string> ::_quote::

keyword:
    <in> | <except> | <and> | <or>

symbol:
    ::bracket_:: | ::_bracket::
