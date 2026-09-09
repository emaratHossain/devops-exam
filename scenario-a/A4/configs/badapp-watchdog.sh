#!/bin/bash

curl -sf --max-time 5 http://127.0.0.1:5073/healthz || systemctl restart badapp.service