#/bin/bash
set -xeuf

# Not needed inside docker
#php tests/ci/depends.php $OPCODE_CACHE

cd ..
mkdir -p app/libraries
if [ ! -e "app/libraries/lithium" ] ; then
    ln -s ../../lithium app/libraries/lithium
fi

cd app

set +e

#TODO: If PHP_VERSION_ID < 80000, install alcaeus/mongo-php-adapter:1.2.2 instead of mongodb/mongodb
#composer require alcaeus/mongo-php-adapter:1.2.2
composer require mongodb/mongodb

cd libraries
rm -r li3_fixtures
git clone -b OPS-481-php-8.4 git@github.com:whowgames/li3_fixtures.git
cd -

composer install --ignore-platform-reqs --prefer-source

mkdir -p /tmp/tmp/logs
mkdir -p /tmp/tmp/tests
mkdir -p /tmp/tmp/cache/templates
case "$DB" in
    mysql)
        mysql -h mysql -u root -ppassword -e 'create database lithium_test;'
        ;;

    pgsql)
        psql postgresql://postgres:5432 -U postgres -c 'create database lithium_test;'
        ;;

    couchdb)
        curl -X PUT http://couchdb:5984/lithium_test/
        ;;
esac

mkdir -p ci/config
cd ci
cp ../libraries/lithium/tests/ci/config/libraries.php config/libraries.php
cp ../libraries/lithium/tests/ci/config/bootstrap.$DB.php config/bootstrap.php
