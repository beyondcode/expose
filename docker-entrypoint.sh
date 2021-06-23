#!/bin/bash

/src/expose token ${token} && sed -i -E "s|'dns'\\s?=>\\s?'.*'|'dns' => true|g" ${exposeConfigPath}

sed -i "s|username|${username}|g" ${exposeConfigPath} && sed -i "s|password|${password}|g" ${exposeConfigPath}

if [[ $# -eq 0 ]]; then
    exec /src/expose serve ${domain} --port ${port} --validateAuthTokens
else
    exec /src/expose "$@"
fi
