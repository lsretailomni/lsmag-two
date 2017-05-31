#!/usr/bin/env bash

while [ 1 == 1 ]; do
    ps aux | grep "[u]nison -repeat=watch magento2"
    if [ $? != 0 ]
    then
        (su - magento2 -c 'unison -repeat=watch magento2') &
    fi
    sleep 10
done
