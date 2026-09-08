#!/bin/bash
# Task 9 - healthcheck.sh
# Reads a config file, checks each service with curl, checks disk, writes a log.
# Exit codes: 0 = all fine, 1 = a service failed, 2 = config missing/unreadable.

CONFIG="${1:-./checks.conf}"
LOG="/var/log/badhon/healthcheck.log"
LOCK="/var/lock/badhon/healthcheck.lock"
DISK_LIMIT="${DISK_LIMIT:-80}"

# 1 + 6: config file must exist and be readable
if [ ! -r "$CONFIG" ]; then
    echo "config file missing or not readable: $CONFIG" >&2
    exit 2
fi

# 7: only one copy may run. A second copy exits quietly with 0.
exec 9>"$LOCK" || exit 2
flock -n 9 || exit 0

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m'

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') $*" >> "$LOG"
}

failed=0

# 2 + 3: read every line, skip blanks and comments, curl with 3 second timeout
while IFS='|' read -r name url want || [ -n "$name" ]; do

    want="${want%$'\r'}"

    case "$name" in
        ""|"#"*) continue ;;
    esac

    got=$(curl -s -o /dev/null -w '%{http_code}' --connect-timeout 3 --max-time 3 "$url")

    # 5: colour on screen, full details in the log
    if [ "$got" = "$want" ]; then
        printf "${GREEN}[OK]${NC}   %-8s %s -> %s\n" "$name" "$url" "$got"
        log "OK   $name $url want=$want got=$got"
    else
        printf "${RED}[FAIL]${NC} %-8s %s -> want %s got %s\n" "$name" "$url" "$want" "$got"
        log "FAIL $name $url want=$want got=$got"
        failed=1
    fi

done < "$CONFIG"

# 4: disk usage of /
disk=$(df -P / | awk 'NR==2 {print $5}' | tr -d '%')

if [ "$disk" -gt "$DISK_LIMIT" ]; then
    printf "${YELLOW}[WARN]${NC} disk / is %s%% full (limit %s%%)\n" "$disk" "$DISK_LIMIT"
    log "WARN disk / is ${disk}% full (limit ${DISK_LIMIT}%)"
else
    printf "${GREEN}[OK]${NC}   disk     / is %s%% full (limit %s%%)\n" "$disk" "$DISK_LIMIT"
    log "OK   disk / is ${disk}% full (limit ${DISK_LIMIT}%)"
fi

# 6: disk is only a warning, it does not change the exit code
exit "$failed"
