#!/bin/bash
pushd $(dirname $(which $0))
apt-get -y update
apt-get -y install php5-common php5-imap
exec /opt/fpp/scripts/update_plugin MailControl
popd
