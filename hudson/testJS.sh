#!/bin/bash

printHelp(){
	echo "";
	echo "Utilisation : [/bin/bash] testJS.sh /path/to/sources/ \"http[s]://your.instance.com/\" [full]";
	echo "";
	echo "Lance les test unitaires javascripts, l'option full permet d'avoir les prints même si les tests unitaires sont bons.";
	echo "Les trailings slashes du répertoire des sources et de l'instance sont obligatoires.";
	echo "";
}

# printHelp;


# Sources : $1
# Lien des tests : $2
# Full : $3

sources=$1;
instance=$2;
fullprint=$3;

readme=$sources"README.md"

# test si le répertoire existe
echo -n "Test de la validité du fichier : ";
if [ ! -d $sources ]
then
	echo "Le repertoire n'existe pas";
	printHelp;
	exit 1;
fi

# test si le répertoire est bien un source Phraseanet
if [ ! -e $readme ]
then
	echo "Le repertoire n'est pasa une source phraseanet";
	exit 1;
fi

echo "ok !";


# test si le lien est valable

echo -n "Test de la validité du lien : ";
wget --no-check-certificate $instance -o /dev/null
if [ ! $? -eq 0 ]
then
	echo "Le site n'existe pas";
	exit 1;
fi

echo "ok !";

echo "";
echo "";
testOK=0
# recursivité des fichiers
for jsfiles in `ls $sources"www/include/js/tests/"`
do
	echo -n $jsfiles" : ";
	phantomjs --config=$sources"hudson/config.json" $sources"hudson/run-qunit.js" $instance"/include/js/tests/"$jsfiles > /tmp/jsunitphantom
	if [ ! $? -eq 0 ]
	then
		echo "nok !";
		cat /tmp/jsunitphantom;
		testOK=1;
	fi
	echo "";
	echo "";
	echo "";
done

exit $testOK;
