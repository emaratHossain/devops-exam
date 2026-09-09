Task 12. (10 marks) — Write the systemd unit
    - A dedicated user `badappuser` was created, and used in the nginx service file.
        - [Service]
          User=badappuser

    - We add Restart=on-failure to the service file. But we also add StartLimitIntervalSec=60 and StartLimitBurst=5 to prevent the service from restarting too frequently.
        - [Service]
          Restart=on-failure
          StartLimitIntervalSec=60
          StartLimitBurst=5

    - We checked service must start after postgresql.service & network-online.target
        - [Unit]
          Wants=network-online.target
          After=network-online.target postgresql.service

    - We added a SyslogIdentifier to the service file for easier log filtering.
        - [Service]
          SyslogIdentifier=badapp


Task 13 (4 marks) — Trigger the restart limit
    - why would you want this limit in production instead of restarting forever?
    - A restart limit prevents a broken application from restarting forever. If the application has a serious bug, unlimited restarts can waste CPU and memory, generate large amounts of logs, and make the problem harder to diagnose. The limit makes systemd stop the service so an administrator can investigate it.

    - how you would notice this in production if you were not watching the terminal?
    - I would monitor the service status and systemd journal using a monitoring system or alerting tool like (Grafana, Prometheus). A high restart count or repeated service failures would trigger an alert so I could investigate the application logs.


Task 14 (4 marks) — journalctl queries
    - Last 10 minutes: `sudo journalctl -u badapp.service --since "10 minutes ago"`
    - Errors and worse: `sudo journalctl -u badapp.service -p err..emerg`
    - Current boot: `sudo journalctl -u badapp.service -b`
    - Previous boot: `sudo journalctl -u badapp.service -b -1`
    - JSON: `sudo journalctl -u badapp.service -o json -n 20`
    - Follow live: `sudo journalctl -u badapp.service -f`

Task 15 (4 marks) - The app that is alive but dead
    - Explain in one paragraph why Restart=on-failure did not catch /hang issue .
    - `Restart=on-failure` did not catch the `/hang` problem because the application process was still running and had not crashed or exited with an error. Systemd therefore considered the service healthy, even though the app was no longer responding to HTTP requests. The watchdog detects this by checking `/healthz` and restarting the service when the check fails.

    - badapp-watchdog.sh - Created and configured to check `/healthz` and restart the service if it fails. 
    - badapp-watchdog.service - Created and configured to run the watchdog script. 
    - badapp-watchdog.timer - Created and configured to run the watchdog service every 30 seconds.