#/bin/bash
./tests/ci/install.sh

set -xeuf
cd ../app

#php -r 'var_dump(new MongoDB\Client(""));'

#DB=mongodb ./libraries/lithium/console/li3 test libraries/lithium/tests/integration/data --verbose
DB=mongodb_new ./libraries/lithium/console/li3 test libraries/lithium/tests/integration/data --verbose

#DB=sqlite ./libraries/lithium/console/li3 test libraries/lithium/tests --verbose

#DB=couchdb ./libraries/lithium/console/li3 test libraries/lithium/tests/cases/data --verbose
# DB=couchdb ./libraries/lithium/console/li3 test libraries/lithium/tests/integration/data --verbose

#DB=mysql ./libraries/lithium/console/li3 test libraries/lithium/tests/cases/data --verbose
#DB=mysql ./libraries/lithium/console/li3 test libraries/lithium/tests/integration/data --verbose

#DB=pgsql ./libraries/lithium/console/li3 test libraries/lithium/tests/cases/data --verbose
#DB=pgsql ./libraries/lithium/console/li3 test libraries/lithium/tests/integration/data --verbose
