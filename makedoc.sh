#!/bin/sh
phpdoc -ti "Deneb Documentation" \
           -o HTML:frames:DOM/earthli \
           -s on \
           -dn 'Deneb' \
           -dc 'Deneb' \
           -d Deneb \
           -f Deneb.php \
           -t phpdoc
