#!/bin/bash

DIR=$( dirname "$0" )

if [ ! -d "$DIR"/web-data ]; then
	mkdir "$DIR"/web-data

	echo "Order Deny,Allow" > "$DIR"/web-data/.htaccess
	echo "Deny From All" >> "$DIR"/web-data/.htaccess
fi

# Note: The Individual Address Block (IAB) is an inactive registry activity, which has been replaced by the MA-S registry product as of January 1, 2014.
#wget https://standards-oui.ieee.org/iab/iab.txt -O "$DIR"/web-data/iab.txt
curl https://standards-oui.ieee.org/iab/iab.txt --output "$DIR"/web-data/iab.txt
if [ $? -ne 0 ]; then
	sleep 300
	wget https://standards-oui.ieee.org/iab/iab.txt -O "$DIR"/web-data/iab.txt
fi

#wget https://standards-oui.ieee.org/oui/oui.txt -O "$DIR"/web-data/oui.txt
curl https://standards-oui.ieee.org/oui/oui.txt --output "$DIR"/web-data/oui.txt
if [ $? -ne 0 ]; then
	sleep 300
	wget https://standards-oui.ieee.org/oui/oui.txt -O "$DIR"/web-data/oui.txt
fi

#wget https://standards-oui.ieee.org/oui28/mam.txt -O "$DIR"/web-data/mam.txt
curl https://standards-oui.ieee.org/oui28/mam.txt --output "$DIR"/web-data/mam.txt
if [ $? -ne 0 ]; then
	sleep 300
	wget https://standards-oui.ieee.org/oui28/mam.txt -O "$DIR"/web-data/mam.txt
fi

#wget https://standards-oui.ieee.org/oui36/oui36.txt -O "$DIR"/web-data/oui36.txt
curl https://standards-oui.ieee.org/oui36/oui36.txt --output "$DIR"/web-data/oui36.txt
if [ $? -ne 0 ]; then
	sleep 300
	wget https://standards-oui.ieee.org/oui36/oui36.txt -O "$DIR"/web-data/oui36.txt
fi

#wget https://standards-oui.ieee.org/cid/cid.txt -O "$DIR"/web-data/cid.txt
curl https://standards-oui.ieee.org/cid/cid.txt --output "$DIR"/web-data/cid.txt
if [ $? -ne 0 ]; then
	sleep 300
	wget https://standards-oui.ieee.org/cid/cid.txt -O "$DIR"/web-data/cid.txt
fi
