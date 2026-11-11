#/bin/bash

export OPCODE_CACHE=apc
export DB=mongodb

./tests/ci/install.sh

set -xeuf
cd ../app

set +e

#libraries/lithium/console/li3 test --filters=Profiler libraries/lithium/tests/cases --verbose
#libraries/lithium/console/li3 test --filters=Profiler libraries/lithium/tests/integration --verbose

libraries/lithium/console/li3 test --filters=Profiler libraries/lithium/tests/cases/data/source --verbose
libraries/lithium/console/li3 test --filters=Profiler libraries/lithium/tests/integration/data --verbose
