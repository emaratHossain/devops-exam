Task 16 - Task 16 (3 marks) — Reverse proxy with correct headers

- why without extra config, your app thinks every request came from 127.0.0.1
- Because nginx is the one actually connecting to my Python app. Therefore Python's remoteAddress is 127.0.0.1, not the real user's IP.

Task 17 - Task 17 (3 marks) — Load balance across both backends

- What is the difference between normal loadbalancing and least_conn ?
- Normal loadbalancing distributes requests evenly across all servers, while least_conn distributes requests to the server with the fewest active connections.

Task 18 (4 marks) — Health checks and failover

- How many requests failed before nginx stopped using the dead backend?
- In my case no requests failed, the request was just routed to the other backend port 5075.

- How long after you restarted it did traffic return to it?
- Traffic returned to it immediately after I restarted it.

- Why those specific numbers? Relate them to your max_fails and fail_timeout settings.?
- max_fails - The number of failed attempts to communicate with a server before it is considered dead.
- fail_timeout - The time period during which the number of failed attempts is counted.

- Change max_fails to 1 and fail_timeout to 30s, repeat, and report how the numbers changed.
- After setting max_fails=1 and fail_timeout=30s, nginx required only one failed connection to remove the backend from rotation, keeping it unavailable for 30 seconds. During this time, requests were handled by the healthy backend.


Task 19 (3 marks) — The slow endpoint and the 504

- raising the timeout is what everyone does first, and it is usually the wrong fix. Explain why. Think about what happens to your nginx worker connections if 500 users all hit a 45-second endpoint at once. What would you do instead in a real production system?

- Raising the timeout only makes nginx wait longer; it does not fix the slow endpoint. If 500 users hit a 45-second endpoint at once, many connections can stay occupied and overload the server. In production, I would optimize the operation and move long-running work to a background queue/worker so the HTTP request returns quickly.