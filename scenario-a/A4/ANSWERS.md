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