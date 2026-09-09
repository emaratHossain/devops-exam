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