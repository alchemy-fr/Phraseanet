%skip   space           \s

%token  and             AND
%token  or              OR
%token  in              IN
%token  word            \S+

query:
    expression() *

expression:
    in_expression() | text()

in_expression:
    text() ::in:: field()

field:
    <word>

text:
    <word> +
