#/bin/bash

OPCODE_CACHE=apc
DB=mongodb

./tests/ci/install.sh

set -xeuf
cd ../app

libraries/lithium/console/li3 test --filters=Profiler libraries/lithium/tests/cases --verbose
libraries/lithium/console/li3 test --filters=Profiler libraries/lithium/tests/integration --verbose
