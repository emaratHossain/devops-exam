#!/bin/bash
# loadtest.sh - Task 31
#
# Normal load for 5 minutes.
# A 30 second burst in the middle.
# One tenant (hooli) gets heavy requests, so it looks slow.
# Every request is saved to a CSV file, then a summary is printed.
#
# Run it like this:   ./loadtest.sh
# Short test run:     DURATION=60 BURST_AT=20 BURST_LEN=10 ./loadtest.sh
# Do NOT use:         source loadtest.sh

BASE="${BASE:-http://localhost:5151}"
DURATION="${DURATION:-300}"     # total run time in seconds
BURST_AT="${BURST_AT:-150}"     # burst starts this many seconds in
BURST_LEN="${BURST_LEN:-30}"    # how long the burst lasts
BURST_SIZE="${BURST_SIZE:-10}"  # requests fired at once during the burst
HEAVY_LIMIT="${HEAVY_LIMIT:-1000}"  # how many notes the slow tenant asks for
ROUND_SLEEP="${ROUND_SLEEP:-0.2}"   # pause between normal rounds. Bigger = less load.
BURST_SLEEP="${BURST_SLEEP:-0.15}"  # pause between burst rounds. Bigger = smaller spike.

RESULTS="loadtest-results.csv"
SUMMARY="loadtest-summary.txt"

TENANTS=(acme globex initech umbrella hooli)
# Real words from the seeder, so search actually finds notes.
WORDS=(alpha anchor backup cloud engine harbor market rocket signal winter)

echo "$EXAM_TOKEN | $(date)"
echo "target: $BASE   duration: ${DURATION}s   burst: ${BURST_LEN}s at +${BURST_AT}s"
echo "round sleep: ${ROUND_SLEEP}s   burst size: ${BURST_SIZE}   heavy limit: ${HEAVY_LIMIT}"

: > "$RESULTS"

# hit <tenant> <route name> <path>
# The route name is short and fixed, so the summary can group by it.
hit() {
  curl -s -o /dev/null --max-time 30 \
    -H "X-Tenant:$1" \
    -w "%{http_code},%{time_total},$1,$2\n" \
    "$BASE$3" >> "$RESULTS"
}

# The spike. More requests at once, for a short time.
burst() {
  BEND=$((SECONDS+BURST_LEN))
  while [ $SECONDS -lt $BEND ]; do
    for i in $(seq 1 $BURST_SIZE); do
      B=${TENANTS[$RANDOM % 5]}
      hit "$B" "/api/notes" "/api/notes?limit=20" &
    done
    sleep $BURST_SLEEP
  done
  wait
}

# ---------- normal load ----------

START=$SECONDS
END=$((SECONDS+DURATION))
BURST_STARTED=0
N=0

while [ $SECONDS -lt $END ]; do
  ELAPSED=$((SECONDS-START))

  # Start the burst once, in the middle of the run.
  if [ $BURST_STARTED -eq 0 ] && [ $ELAPSED -ge $BURST_AT ]; then
    echo ">> burst START at +${ELAPSED}s"
    ( burst; echo ">> burst END" ) &
    BURST_STARTED=1
  fi

  T=${TENANTS[$RANDOM % 5]}
  W=${WORDS[$RANDOM % 10]}
  ID=$((RANDOM % 2000 + 1))

  hit "$T" "/api/notes"     "/api/notes?limit=20" &
  hit "$T" "/api/search"    "/api/search?q=$W" &
  hit "$T" "/api/stats"     "/api/stats" &
  hit "$T" "/api/notes/:id" "/api/notes/$ID" &

  # The slow tenant. Every 10th round hooli asks for 1000 notes.
  # Each note then needs its own tags query, so that is about 1001 queries
  # in one request. That is the N+1 problem, and it makes hooli the slow tenant.
  N=$((N+1))
  if [ $((N % 10)) -eq 0 ]; then
    hit hooli "/api/notes" "/api/notes?limit=$HEAVY_LIMIT" &
  fi

  sleep $ROUND_SLEEP
done

echo "waiting for the last requests..."
wait
echo "load finished."

# ---------- summary ----------

# Reads a list of times on stdin. Prints count, average, p95 and max.
stat_rows() {
  sort -n | awk -v L="$1" '
    { t[NR]=$1; s+=$1 }
    END {
      if (NR == 0) { printf "%-22s n=0\n", L; exit }
      p = int(NR * 0.95); if (p < 1) p = 1
      printf "%-22s n=%-6d avg=%7.3fs  p95=%7.3fs  max=%7.3fs\n", L, NR, s/NR, t[p], t[NR]
    }'
}

{
  echo "$EXAM_TOKEN | $(date)"
  echo "======================================================================"
  echo "LOAD TEST SUMMARY"
  echo "target     : $BASE"
  echo "duration   : ${DURATION}s, with a ${BURST_LEN}s burst starting at +${BURST_AT}s"
  echo "slow tenant: hooli, /api/notes?limit=$HEAVY_LIMIT every 10th round"
  echo "total reqs : $(wc -l < "$RESULTS" | tr -d ' ')"
  echo
  echo "--- HTTP status codes (000 means no answer / timed out) ---"
  cut -d, -f1 "$RESULTS" | sort | uniq -c | sort -rn
  echo
  echo "--- by route ---"
  for R in /api/notes /api/search /api/stats "/api/notes/:id"; do
    awk -F, -v R="$R" '$4 == R { print $2 }' "$RESULTS" | stat_rows "$R"
  done
  echo
  echo "--- by tenant (hooli must be the slowest) ---"
  for T in acme globex initech umbrella hooli; do
    awk -F, -v T="$T" '$3 == T { print $2 }' "$RESULTS" | stat_rows "$T"
  done
  echo
  echo "--- everything ---"
  awk -F, '{ print $2 }' "$RESULTS" | stat_rows "all requests"
  echo "======================================================================"
} | tee "$SUMMARY"

echo
echo "raw data: $RESULTS"
echo "summary : $SUMMARY"
