#!/bin/sh

export PATH=$PHP_BIN_V70:$PATH
composer install
mkdir -p output
tar czf output/discuz-miniapp-plugin.tar.gz src/ vendor/
