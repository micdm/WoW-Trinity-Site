#!/bin/bash

BASE='/home/www/php/'
INPUT='wow/trunk/'
OUTPUT='wow.upload/'

#Переходим в корневую папку:
WORKDIR=`pwd`
cd $BASE

#Смотрим, откуда взять номер ревизии:
if [ "$1" ]; then
    REVISION=$1
else
    REVISION=`cat $OUTPUT/revision.txt`
fi

echo "Updating from $REVISION"

#Получаем списки файлов для удаления/обновления:
REMOVE=`svn diff --summarize -r $REVISION:HEAD $INPUT | grep -P '^D' | awk '{print $2}'`
MODIFIED=`svn diff --summarize -r $REVISION:HEAD $INPUT | grep -P '^(A|M)' | awk '{print $2}'`

#Пересоздаем новую директорию:
rm -r $OUTPUT
mkdir $OUTPUT

#Копируем файлы в новую директорию:
for i in $MODIFIED;
do
    NEW=`echo $i | replace $INPUT $OUTPUT`;
    mkdir -p `dirname $NEW` && cp $i $NEW;
    echo $NEW;
done

#Напоминаем, если нужно удалить файлы:
if [ "$REMOVE" ]; then
    echo -e '\nDont forget to remove files:\n'$REMOVE
fi

#Сохраняем номер ревизии:
NEW_REVISION=`svn info $INPUT | grep 'Revision' | grep -Po '(\d+)'`
echo $NEW_REVISION > $OUTPUT/revision.txt

#Возвращаемся:
cd $WORKDIR
