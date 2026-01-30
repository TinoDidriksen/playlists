#!/bin/bash
D=`date -u '+%Y-%m-%d %H:%M:%S'`
cp -av /cygdrive/c/Documents/Nextcloud/Media/Music/iTunes/*.xml ./
perl -pe 's/\r\n/\n/g;' -i 'iTunes Music Library.xml'
chmod 0664 *.xml
rm -fv *.bak
./normalize.php > new.xml && mv -v new.xml 'iTunes Music Library.xml'
git add *.xml
git commit -m "$D"
git reflog expire --expire=now --all
git repack -ad
git prune
git push -u origin main
