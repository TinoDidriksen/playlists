#!/bin/bash
D=`date -u '+%Y-%m-%d %H:%M:%S'`
cp -av /cygdrive/c/Documents/Google\ Drive/Music/iTunes/*.xml ./
perl -pe 's/\r\n/\n/g;' -i 'iTunes Music Library.xml'
chmod 0664 *.xml
rm -fv *.bak
git add *.xml
git commit -m "$D"
git push -u origin master
