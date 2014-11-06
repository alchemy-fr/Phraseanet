%skip   space           \s

%token  quote_          "        -> string
%token  string:string   [^"]+
%token  string:_quote   "        -> default
%token  in              IN
%token  and             AND
%token  or              OR
%token  except          EXCEPT
%token  word            \S+

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
    text() ( ::in:: #in word() )?

#text:
    ( word() | keyword() )+

word:
    <word> | string()

string:
    ::quote_:: <string> ::_quote::

keyword:
    <in> | <except> | <and> | <or>
