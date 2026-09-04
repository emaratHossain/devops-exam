#!/bin/bash
# loadtest.sh - Task 31
#
# Normal load for 5 minutes.
# A 30 second burst in the middle.
# One tenant (hooli) gets very heavy requests, so it looks slow.
# Every request is saved to a CSV file, then a summary is printed.
#
# Run it like this:   ./loadtest.sh
# Do NOT use:         source loadtest.sh

BASE="http://localhost:5151"
RESULTS="loadtest-results.csv"
SUMMARY="loadtest-summary.txt"

TENANTS=(acme globex initech umbrella hooli)
# Real words from the seeder, so search actually finds notes.
WORDS=(alpha anchor backup cloud engine harbor market rocket signal winter)

echo "$EXAM_TOKEN | $(date)"
echo "target: $BASE"

: > "$RESULTS"

# hit <tenant> <route name> <path>
# The route name is short and fixed, so the summary can group by it.
hit() {
  curl -s -o /dev/null --max-time 30 \
    -H "X-Tenant:$1" \
    -w "%{http_code},%{time_total},$1,$2\n" \
    "$BASE$3" >> "$RESULTS"
}

# The 30 second spike. Many requests at the same time.
burst() {
  BEND=$((SECONDS+30))
  while [ $SECONDS -lt $BEND ]; do
    for i in $(seq 1 20); do
      B=${TENANTS[$RANDOM % 5]}
      hit "$B" "/api/notes" "/api/notes?limit=20" &
    done
    sleep 0.1
  done
  wait
}

# ---------- normal load ----------

START=$SECONDS
END=$((SECONDS+300))     # 5 minutes
BURST_STARTED=0
N=0

while [ $SECONDS -lt $END ]; do
  ELAPSED=$((SECONDS-START))

  # Start the burst once, in the middle of the run (at 2:30).
  if [ $BURST_STARTED -eq 0 ] && [ $ELAPSED -ge 150 ]; then
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

  # The slow tenant. Every 5th round hooli asks for 5000 notes.
  # Each note then needs its own tags query. That is the N+1 problem.
  N=$((N+1))
  if [ $((N % 5)) -eq 0 ]; then
    hit hooli "/api/notes"  "/api/notes?limit=5000" &
    hit hooli "/api/search" "/api/search?q=alpha&limit=5000" &
  fi

  sleep 0.2
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
  echo "duration   : 300s, with a 30s burst starting at +150s"
  echo "total reqs : $(wc -l < "$RESULTS" | tr -d ' ')"
  echo
  echo "--- HTTP status codes ---"
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
