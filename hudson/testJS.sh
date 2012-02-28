#!/bin/bash

printHelp(){
	echo "";
	echo "Use : [/bin/bash] testJS.sh /path/to/sources/ \"http[s]://your.instance.com/\" ";
	echo "";
	echo "Run JS Unittest recursively.";
	echo "You have to put trailing slashes !";
	echo "";
}


sources=$1;
instance=$2;
fullprint=$3;

readme=$sources"README.md"

# Directory exists ?
echo -n "Directory check  : ";
if [ ! -d $sources ]
then
	echo "Directory does not exists";
	printHelp;
	exit 1;
fi

# Phraseanet directory ?
if [ ! -e $readme ]
then
	echo "Not a Phraseanet Directory";
	exit 1;
fi

echo "ok !";


# Valid link ?

echo -n "Check link validity : ";
wget --no-check-certificate $instance -o /dev/null
if [ ! $? -eq 0 ]
then
	echo "Link does not exists.";
	exit 1;
fi

echo "ok !";

echo "";
echo "";
testOK=0
# recursively tests files
for jsfiles in `ls $sources"www/include/js/tests/"`
do
	echo -n $jsfiles" : ";
	phantomjs --config=$sources"hudson/config.json" $sources"hudson/run-qunit.js" $instance"/include/js/tests/"$jsfiles > /tmp/jsunitphantom
	if [ ! $? -eq 0 ]
	then
		echo "nok !";
		cat /tmp/jsunitphantom;
		testOK=1;
	else
		echo "ok !";
	fi
	echo "";
	echo "";
done

exit $testOK;
