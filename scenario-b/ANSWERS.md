# Scenario B 1 — Answers

Task 21
Question 1

- Write a Dockerfile with at least two stages. The final image must:

- Contain no compiler, build tools, or dev dependencies
- Run as a non-root user
- Have a working `HEALTHCHECK` instruction

Answer 1 - 

- I have successfully built the dockerfile with two stages.
- The container is running as the user `emarat`.
- The ID output tells us that the user `emarat` has the user ID `1001` and group ID `1001`.
- The HEALTHCHECK instruction is working fine.

------------------------------------------------------------------------------------------------------------------------------------------

Task 22
Question 1 - 

- First write a naive single-stage Dockerfile (`Dockerfile.naive`) and build it, then
- build your multi-stage one. Screenshot showing both sizes. Your multi-stage image
- must be at least 60% smaller. In `ANSWERS.md`, list what you removed and what you
- gave up by removing it.

Answer 1 - 

I just have copied the necessary files from the builder stage to the final stage:

- /usr/local/lib/php/extensions/ and /usr/local/etc/php/conf.d/ from the builder stage to the final stage.
- And also copied the application code & vendor directory from the builder stage to the final stage.
- Everything else is removed from the final stage.
- I have attached the screenshots of the image sizes in the `evidence` directory.

------------------------------------------------------------------------------------------------------------------------------------------

Task 23
Question 1 - Why after changing anything in the code, the build time is increased?

Answer 1 - Docker build images in layers and each layer is cached. When we change anything in the code, the layers after the changed layer are re-built, which increases the build time. In my case i changed app/routes.php file, so after COPY app/ ./, all the layers after that are re-built, which increases the build time.


------------------------------------------------------------------------------------------------------------------------------------------

Task 24
Question 1 - Which layer is biggest? What command created it? Could it be smaller?

Answer 1 - `FROM php:8.3-cli-alpine` command created the biggest layer. And as we are already using the smallest base image (alpine), it cannot be smaller.

------------------------------------------------------------------------------------------------------------------------------------------

Task 25
Question 1 - 

- COPY .env /app/.env
- RUN cat /app/.env > /dev/null && rm /app/.env      # "deleted" — but not really
- why rm in a later layer does not help.

Answer 1 - 

- The `rm` command in a later layer does not help because the layer is already cached and the file is still there in the previous layer.
- In mycase .env and .env.* fiels are in dockerignore file, so they are not copied to the final stage.
- In my case docker image get the .env from the build context, which is configured in docker-compose.yml file.
- `docker run --rm notes-api:latest find / -name ".env" 2>/dev/null` - this command returns empty result, which means the .env file is not present in the final stage.

------------------------------------------------------------------------------------------------------------------------------------------
------------------------------------------------------------------------------------------------------------------------------------------

# Scenario B 2 — Answers

Task 26
Question 1 - Prove that depends_on[postgres] alone is insufficient: write a version with only depends_on, run docker compose up on a fresh volume, and screenshot your app crashing with a connection error.

Answer 1 - 

- postgres container takes time to start and the app container tries to connect to the database before it is ready. that is why the app crashes.
- The app container should wait for the postgres container to be ready before trying to connect to it, that is why a healthcheck is needed.
- In docker-compose.depends-only.yml file, I have only used depends_on[postgres] without healthcheck, and the app container crashes with a connection error. ( Scenarion-B-2 | Task-26 | Crashing)
- In docker-compose.yml file, I have used depends_on[postgres] with healthcheck, and the app container waits for the postgres container to be ready before trying to connect to it. ( Scenarion-B-2 | Task-26 | Healthy.png)

------------------------------------------------------------------------------------------------------------------------------------------

Task 27
Question 1 - Run docker compose down -v, then up -d. Show the notes are gone and explain in ANSWERS.md what the v flag did.

Answer 1 - The `docker compose down -v` command removes the containers and volumes. The `v` flag removes the volumes. In my case "notes-db-data" volume contains the data of the postgres database, so when we run `docker compose down -v`, the notes are gone because the volume is removed.

------------------------------------------------------------------------------------------------------------------------------------------

Task 28 
Question A - 
    - Give the container a tiny memory limit and then make it use memory.
    - Then in your app allocate a big array

Answer A - 

- `sudo docker inspect --format='{{.State.OOMKilled}}' badhon-oom-test` - It returned true
- `sudo dmesg | tail -20` - From the last 20 lines of dmesg output, I can see that the container was killed due to out of memory error. it says -
     `[741859.696834] oom-kill:constraint=CONSTRAINT_MEMCG,nodemask=(null),cpuset=docker-c32e6b2f57ba5c870e04a776f066ff53600087f1cafafa246aad4f3575259f97.scope,mems_allowed=0,oom_memcg=/system.slice/docker-c32e6b2f57ba5c870e04a776f066ff53600087f1cafafa246aad4f3575259f97.scope,task_memcg=/system.slice/docker-c32e6b2f57ba5c870e04a776f066ff53600087f1cafafa246aad4f3575259f97.scope,task=python,pid=1516044,uid=0`
- `sudo docker inspect --format='ExitCode={{.State.ExitCode}} OOMKilled={{.State.OOMKilled}}' badhon-oom-test` - It returned `ExitCode=137 OOMKilled=True`


Question B - Explain App cannot reach the DB by service name but can by IP, when they are in different networks ?

Answer B -
    - When the app service and the db service in the same network, they can communicate with each other using the service name as the hostname. Because docker can resolve the service name to the IP address of the container.
    - But when they are in different networks, docker cannot resolve the service name to the IP address of the container
    - So we have to find the IP address of the db container and use that IP address to connect to the db service
    - we can get the IP address of the db container by running `sudo docker inspect --format='{{.NetworkSettings.Networks.network_name.IPAddress}}' notes-db`

Question C - could not understand the question
Question D - could not understand the question


------------------------------------------------------------------------------------------------------------------------------------------

Task 29
Question 1 - Install a Prometheus client library and expose six metrics at `GET /metrics`.

Answer 1 -
- Library: `promphp/prometheus_client_php` (added in `app/composer.json`).

Question 2 - Why the `route` label is the pattern, not the real URL.

Answer 2 -
- let's say, we have 50 notes in the database, and we access each note by its ID. So the route will be `/api/notes/1`, `/api/notes/2`, ..., `/api/notes/50`. If we use the real URL as the label, we will have 50 different labels
- So when we will try to monitor how many requests hit `/api/notes/:id`, we will have 50 different labels, which is not good for Prometheus. That's why we use the pattern `/api/notes/:id` as the label. which will help us to monitor the requests in a more efficient way.

------------------------------------------------------------------------------------------------------------------------------------------

Task 30
Question 1 - Wire up Prometheus. Add it to the compose file, mount the config, expose port 9090.

Answer 1 -
- I added a `prometheus` service in `scenario-b/docker/docker-compose.yml` using the image `prom/prometheus:v2.55.1`.
- I mounted my config as read only: `./prometheus/prometheus.yml:/etc/prometheus/prometheus.yml:ro`. The container reads it because of the flag `--config.file=/etc/prometheus/prometheus.yml`.
- I also added a named volume `prometheus-data:/prometheus`, so the collected metrics are not lost when the container restarts. Retention is 15 days (`--storage.tsdb.retention.time=15d`).
- Port: I published `5252:9090`, so the UI opens at `http://169.58.246.108:5252/`.

- Scenarion-B-3 | Task-30 | graph.png shows the prometheus targets
- Scenarion-B-3 | Task-30 | graph2.png shows the prometheus graph

------------------------------------------------------------------------------------------------------------------------------------------

Task 31
Question 1 -
 - Run for at least five minutes
 - Hit all the endpoints, not just one
 - Use at least three different tenants
 - Include a **burst** in the middle: run your normal load, and partway through launch a second heavy load process for about 30 seconds, then stop it. You need this spike for one of the dashboard panels.
 - Make one tenant deliberately worse — send that tenant much heavier requests, e.g. `?limit=5000`, so it shows up as the slow tenant in your dashboard
 - Submit your load script, and the summary output from your load tool.

 Answer 1 -
 - loadtest.sh is in - /devops-exam/scenario-b/loadtest.sh
 - Summary output from your load tool is in - devops-exam/scenario-b/evidence/Scenarion-B-3 | Task-31 | summary.txt
 
 
------------------------------------------------------------------------------------------------------------------------------------------

Task 32
Question A 
- What are the top 5 slowest endpoints by p95 latency?
- p95 means “95% of requests were faster than this.” It is more useful than an average because averages hide the slow tail.

Answer A -
- Top 5 slowest endpoints by p95 latency are:
    1. /api/notes - max p95 score is 5sec
    2. /api/notes/:id - max p95 score is 483ms
    3. /api/search - max p95 score is 3.72sec
    4. /api/stats - max p95 score is 3.04sec
- The query I used - `topk(5, histogram_quantile(0.95, sum by (route, le) (rate(http_request_duration_seconds_bucket[5m]))))`

Question B -
- Panel B — Which endpoint consumed the most TOTAL time
- This is a different question from Panel A and it is the most important panel in this whole exam.
- Panel A tells you which endpoint is slowest *per request*. Panel B tells you where your server’s time is actually going.
- An endpoint that takes 5 seconds but is called twice an hour is less important than an endpoint that takes 200ms but is called 500 times a second. 
- state which endpoint wins each panel on your system, and explain in your own words why they are different.

Answer B -
- In panel B - /api/notes is showing at 19.22, system was working on 20 /api/notes requests
- In panel A - /api/notes has p95 score of 5sec
- The reason they are different is because panel B shows how much total time was spent on that endpoint, while panel A shows the latency of individual requests.

- The query i used for panel B -  `topk(5, sum by (route) (rate(http_request_duration_seconds_sum[5m])))`

Question C -
- Average and p99 DB query duration by query name
- Put both on the same panel so you can see the gap between them.

Answer C -
- Bottom (green, yellow, dark blue, orange) - average seconds per query
- Top (red, blue, pink, purple) - p99 seconds per query

- Explanation for count_notes query -
  - Average count_notes is around 0.2–0.3 seconds. Its p99 is 1 second or worse. So the slowest 1% of these queries take at least 3–5 times longer than the normal one.

- Query A - `sum by (query_name) (rate(db_query_duration_seconds_sum[5m])) / sum by (query_name) (rate(db_query_duration_seconds_count[5m]))`
- Query B - `histogram_quantile(0.99, sum by (query_name, le) (rate(db_query_duration_seconds_bucket[5m])))`

Question D -
- The slowest single query, and how often it runs

Answer D -
- count_notes - 1 second p99, runs 0.23 times per second
- select_tenants - 1 second p99, runs 0.59 times per second
- The query I used - 
    - `histogram_quantile(0.99, sum by (query_name, le) (rate(db_query_duration_seconds_bucket[5m])))`
    - `sum by (query_name) (rate(db_query_duration_seconds_count[5m]))`

Question E -
- Which query has the N+1 problem?

Answer E -
- api/notes endpoint has the N+1 problem - 
    - 1 query to get the 20 notes (select_notes) - total 1
    - 1 query per note for its tags (select_tags) - total 20
    - 1 query to find the tenant (select_tenants) - total 1
    - 1 query to count the total for paging (count_notes) - total 1
    - So in total there are 23 queries for 20 notes
- The query I used - `sum by (route) (rate(db_queries_per_request_sum[5m]))/sum by (route) (rate(db_queries_per_request_count[5m]))`





------------------------------------------------------------------------------------------------------------------------------------------
------------------------------------------------------------------------------------------------------------------------------------------

# Scenario B 4 — Answers

Task 36
Question 1 - Scale to 5 replicas and prove all 5 are really serving traffic.

Answer 1 -

- The server is shared, so I used a stack name prefix. My stack is `badhon_notes`, so my service is `badhon_notes_app`.
- Scaled with `sudo docker service scale badhon_notes_app=5`. `docker service ps badhon_notes_app` shows 5 tasks Running.
- `docker service ps` only proves the copies started. It does not prove traffic reaches all of them. So I made the app name itself.
- I added `App\Http\Middleware\ServedBy` and registered it with `$middleware->append(...)` in `app/bootstrap/app.php`. It puts `X-Served-By: <container id>` on every response, including `/up`.
- A container's hostname is its container ID, so every copy sends a different value.
- Then I sent 50 requests and counted the IDs. Result: 5 different IDs, 10 requests each. The load is spread evenly.

```
     10 X-Served-By: 08b24dde2f50
     10 X-Served-By: 09978ac1de5f
     10 X-Served-By: 79f71bfc8707
     10 X-Served-By: 7f6936730034
     10 X-Served-By: 9d513657721a
```

------------------------------------------------------------------------------------------------------------------------------------------

Task 37
Question 1 - Rolling update with zero downtime. Submit the status code count. Be honest about failures.

Answer 1 -

- Result: 1003 requests, all 200. Zero failures

------------------------------------------------------------------------------------------------------------------------------------------

Task 38
Question 1 - What would have happened if my image had no healthcheck? Would Swarm have noticed?

Answer 1 - **No. Swarm would not have noticed.**

- With no healthcheck, Swarm asks only one question: is the process alive? My broken v3 stays alive. It just listens on the wrong port. So Swarm calls it healthy.
- Swarm would then replace all 5 copies and say the update worked.
- End result: the whole service is dead, and no rollback. I would have to find the problem and fix it by hand.
- The healthcheck is what tells Swarm the difference between "the process is running" and "the app works".

- Evidence: `Scenarion-B-3 | Task-38 | After Back on v2.png`, `Scenarion-B-3 | Task-38 | v3 failed then v2 running.png`, `Scenarion-B-3 | Task-38 | Updated Status.png`


------------------------------------------------------------------------------------------------------------------------------------------

Task 39
Question 1 - What did you observe, and how does a limit differ from a reservation?

Answer 1 -

- I asked for 64 GB of memory on a 7.8 GB server. The copy never started. It stayed `Pending` with the error `no suitable node (insufficient resources on 1 node)`.
- A **reservation** is a booking. Swarm will not start a copy unless that much memory is free on the node. This is what blocked me.
- A **limit** is a ceiling. The copy still starts, but the kernel kills it if it uses more than that while running.

- My settings: `limits` 1G memory / 0.50 CPU, `reservations` 128M memory / 0.10 CPU. Command used - `sudo docker service update --reserve-memory 64G badhon_notes_app`. Put back with `--reserve-memory 128M`.
- Evidence: `Scenarion-B-4 | Task-39 | pending.png`

------------------------------------------------------------------------------------------------------------------------------------------

# Scenario B 5 — Answers

Task 41
Question 1 - Build a pull request pipeline that tests, builds the image, and proves the image really runs.

Answer 1 -

- The workflow is `.github/workflows/pr.yml`.
- It does five things in order: check out the code, run the tests, build the image, start the image and curl `/healthz`, and fail the run if any step fails.
- Failed run: https://github.com/emaratHossain/devops-exam/actions/runs/34606583251
- Passing run: https://github.com/emaratHossain/devops-exam/actions/runs/34606795647
------------------------------------------------------------------------------------------------------------------------------------------

Task 42
Question 1 - The first pipeline run is slow because it downloads all dependencies. Cache them. Show a cold run and a warm run.

Answer 1 -

- Cold run - https://github.com/emaratHossain/devops-exam/actions/runs/34670931251
- Warm run - https://github.com/emaratHossain/devops-exam/actions/runs/34672167471
