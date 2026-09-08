# Task 9 - healthcheck.sh

- `healthcheck.sh` - The script. Reads the config, checks each service, checks disk, writes the log.
- `checks.conf` - The list of services to check.

## Ports I used

- bad-app-1 | 5072 | http://127.0.0.1:5072/healthz
- bad-app-2 | 5073 | http://127.0.0.1:5073/healthz
- nginx | 5074 | http://127.0.0.1:5074/


## How each rule is done

1. Config path as argument - `CONFIG="${1:-./checks.conf}"`. If I give no argument, it uses `./checks.conf`.
2. Skip blank lines and `#` lines - `case "$name" in ""|"#"*) continue ;; esac`.
3. curl with 3 second timeout - `curl -s -o /dev/null -w '%{http_code}' --connect-timeout 3 --max-time 3 "$url"`. This prints only the HTTP status number.
4. Disk check - `df -P / | awk 'NR==2 {print $5}' | tr -d '%'`. If it is over 80, print a yellow warning.
5. Colour on screen, details in the log - green `[OK]`, red `[FAIL]`, yellow `[WARN]`. Every line also goes to `/var/log/healthcheck.log` with a timestamp.
6. Exit codes - `0` all fine, `1` a service failed, `2` config missing or unreadable. The disk warning does **not** change the exit code, because the task only says exit 1 when a *service* fails.
7. No double run - `exec 9>/var/lock/healthcheck.lock` then `flock -n 9 || exit 0`. If a copy is already running, the second copy exits quietly with code 0.


