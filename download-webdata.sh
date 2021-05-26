#!/bin/bash

DIR=$( dirname "$0" )

if [ ! -d "$DIR"/web-data ]; then
	mkdir "$DIR"/web-data

	echo "Order Deny,Allow" > "$DIR"/web-data/.htaccess
	echo "Deny From All" >> "$DIR"/web-data/.htaccess
fi

# Note: The Individual Address Block (IAB) is an inactive registry activity, which has been replaced by the MA-S registry product as of January 1, 2014.
wget http://standards.ieee.org/develop/regauth/iab/iab.txt -O "$DIR"/web-data/iab.txt
if [ $? -ne 0 ]; then
	sleep 300
	wget http://standards.ieee.org/develop/regauth/iab/iab.txt -O "$DIR"/web-data/iab.txt
fi

wget http://standards.ieee.org/develop/regauth/oui/oui.txt -O "$DIR"/web-data/oui.txt
if [ $? -ne 0 ]; then
	sleep 300
	wget http://standards.ieee.org/develop/regauth/oui/oui.txt -O "$DIR"/web-data/oui.txt
fi

wget http://standards.ieee.org/develop/regauth/oui28/mam.txt -O "$DIR"/web-data/mam.txt
if [ $? -ne 0 ]; then
	sleep 300
	wget http://standards.ieee.org/develop/regauth/oui28/mam.txt -O "$DIR"/web-data/mam.txt
fi

wget http://standards.ieee.org/develop/regauth/oui36/oui36.txt -O "$DIR"/web-data/oui36.txt
if [ $? -ne 0 ]; then
	sleep 300
	wget http://standards.ieee.org/develop/regauth/oui36/oui36.txt -O "$DIR"/web-data/oui36.txt
fi
