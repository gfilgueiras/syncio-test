#!/bin/bash

source /root/.bashrc
nginx -s stop &> /dev/null; sleep 2; nginx &

sleep infinity
