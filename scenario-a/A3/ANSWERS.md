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
5. Colour on screen, details in the log - green `[OK]`, red `[FAIL]`, yellow `[WARN]`. Every line also goes to `/var/log/badhon/healthcheck.log` with a timestamp.
6. Exit codes - `0` all fine, `1` a service failed, `2` config missing or unreadable. The disk warning does **not** change the exit code, because the task only says exit 1 when a *service* fails.
7. No double run - `exec 9>/var/lock/badhon/healthcheck.lock` then `flock -n 9 || exit 0`. If a copy is already running, the second copy exits quietly with code 0.




# Task 10 - Bad Input Handling

- How I handled invalid config file: 
    - The script checks the file using:
        ```bash
        if [ ! -r "$CONFIG" ]; then
            ...
            exit 2
        fi
        ```
    -  `! -r "$CONFIG"` - This checks if the file is not readable or does not exist.

- How I handled URL that does not resolve:
    - `got=$(curl -s -o /dev/null -w '%{http_code}' --connect-timeout 3 --max-time 3 "$url")` this captures the HTTP status code. I give it a 3-second timeout to avoid hanging.

    - Then following code checks if the status code matches the expected one:

        ```bash
        if [ "$got" = "$want" ]; then
            printf "${GREEN}[OK]${NC}   %-8s %s -> %s\n" "$name" "$url" "$got"
            log "OK   $name $url want=$want got=$got"
        else
            printf "${RED}[FAIL]${NC} %-8s %s -> want %s got %s\n" "$name" "$url" "$want" "$got"
            log "FAIL $name $url want=$want got=$got"
            failed=1
        fi
        ```
    - If the status code doesn't match the expected one, it prints a red failure message and logs it. But it doesn't exit immediately - it continues checking the other services.
    



