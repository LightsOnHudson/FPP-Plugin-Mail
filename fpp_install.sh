#!/bin/bash
pushd $(dirname $(which $0))
apt-get -y update
apt-get -y install php5-common php5-imap
popd
