#!/bin/sh

# Run tests, or apache?
TEST=1

set -ex

# Copy SSH keys from the host
mkdir -p ~/.ssh
cp -r /ssh/* ~/.ssh/

# On MacOS it tries to include a Colima config; remove that include
sed -ri '/Include .*\/\.colima\/ssh_config|UseKeychain/d' ~/.ssh/config || true

# Correct permissions so ssh is happy
chown -R "$(whoami):$(groups | cut -d' ' -f1)" ~/.ssh
chmod -R go-rwx ~/.ssh

export APACHE_DOCUMENT_ROOT
echo "export APACHE_DOCUMENT_ROOT=${APACHE_DOCUMENT_ROOT}" >> /etc/apache2/envvars

cd /home/lithium

# Find all .git directories and add their parents to safe.directory:
find "$APACHE_DOCUMENT_ROOT" -maxdepth 5 -type d -name .git -print0 \
	| sed 's@/\.git@@g' \
	| xargs -0 -n 1 -r -- git config --global --add safe.directory

composer install --ignore-platform-reqs --prefer-source

case "$TEST" in
	1|yes|on|YES|ON)
		./tests/ci/test.sh
		;;
	*)
		mkdir -p docker-logs
		exec apache2-foreground
		;;
esac
