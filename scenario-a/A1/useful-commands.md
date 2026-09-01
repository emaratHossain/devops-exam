1. Giving permission to all the members of "devs" group to run " systemctl restart myapp". Here % indicate group name, without that it would be assumed as user
    - sudo visudo -f /etc/sudoers.d/devs-app-one - this will create and open a file named devs-app-one, put the following line in that file
    - %devs ALL=(root) /usr/bin/systemctl restart myapp


2. badhon@vmi3536696:/srv/badhon/app$ getfacl src
# file: src
# owner: root
# group: root
user::rwx
group::r-x
group:devs:rwx
group:bad-devs:rwx
mask::rwx
other::r-x

badhon@vmi3536696:/srv/badhon/app$ getfacl logs
# file: logs
# owner: root
# group: root
<!-- user::rwx -->
group::r-x
group:bad-devs:r-x
mask::r-x
other::r-x

badhon@vmi3536696:/srv/badhon/app$ sudo setfacl -R -m g:bad-ops:rwX src
badhon@vmi3536696:/srv/badhon/app$ sudo setfacl -R -m g:bad-ops:rX logs
badhon@vmi3536696:/srv/badhon/app$ ls
backups  config  logs  secrets  src
badhon@vmi3536696:/srv/badhon/app$ sudo setfacl -R -m g:bad-ops:rwX config
badhon@vmi3536696:/srv/badhon/app$ sudo setfacl -R -m g:bad-ops:rX secrets
badhon@vmi3536696:/srv/badhon/app$ sudo setfacl -R -m g:bad-auditor:rX /srv/app
badhon@vmi3536696:/srv/badhon/app$ sudo setfacl -m g:bad-auditor:r-x /srv/app/secrets
badhon@vmi3536696:/srv/badhon/app$ sudo setfacl -R -m g:bad-auditor:--- /srv/app/secrets/*
setfacl: /srv/app/secrets/*: No such file or directory
badhon@vmi3536696:/srv/badhon/app$ ls
backups  config  logs  secrets  src
badhon@vmi3536696:/srv/badhon/app$ cd secrets
badhon@vmi3536696:/srv/badhon/app/secrets$ sudo nano database.env
badhon@vmi3536696:/srv/badhon/app/secrets$ sudo setfacl -R -m g:bad-auditor:rX /srv/badhon/app
badhon@vmi3536696:/srv/badhon/app/secrets$ sudo setfacl -m g:bad-auditor:r-x /srv/badhon/app/secrets
badhon@vmi3536696:/srv/badhon/app/secrets$ sudo setfacl -R -m g:bad-auditor:--- /srv/badhon/app/secrets/*
badhon@vmi3536696:/srv/badhon/app/secrets$ 