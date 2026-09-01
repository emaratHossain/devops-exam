Task 6 - 

Question: Run ss -lptn and lsof -i :8080 without sudo, then with sudo. The output is different. Explain why in ANSWERS.md.

Answer : 
    - ss -lptn → Shows processes I have permission to see.
    - sudo ss -lptn → Shows process information for all users.

    - lsof -i :5050 → Shows processes using port 5050 that my user has permission to see.
    - sudo lsof -i :5050 → Shows processes using port 5050 for all users.

    - I attached the screenshots in "evidence/Scenarion-A-2 | Task-6.png"


Task 7 : 

Questin 1: Was this process started manually, by systemd, or from cron? How did you find out?

Answer : 
    - I run python server manually on port 5050
    - I also run python server on port 7070 from systemd
    - At first i checked the PID for port 5050 & 7070 using `lsof -i :5050` and `lsof -i :7070`
    - Then i checked the process tree for those PIDs using `pstree -aps <PID>`
    - For port 5050, it shows it was started by `sudo python3 -m http.server 5050`, which means it was started manually.
    - For port 7070, it shows it was started by `systemd`, which means it was started by systemd.
    - I also checked cron jobs using `crontab -l` and found no cron jobs.
    - I have attached the screenshot in "evidence/Scenarion-A-2 | Task-7.png"

Question 2: If it was started by systemd, what happens if you just kill -9 the PID?

Answer : 
    - If it was started by systemd, killing the PID will not stop the process. The systemd will restart the process, cause i used `Restart=always` in the systemd service file.
    - I have attached the screenshot in "evidence/Scenarion-A-2 | Task-7.png"

Question 3: Kill it properly (the right way depends on your answer above) and show port 8080 is now free.

Answer : 
    - For port 5050, I killed the process using `kill -9 <PID>` and then checked if port 5050 is free using `lsof -i :5050`.
    - For port 7070, I killed the process using `systemctl stop python-server.service` and then checked if port 7070 is free using `lsof -i :7070`.
    - I have attached the screenshot in "evidence/Scenarion-A-2 | Task-7.png"


Task 8 - In my case i could not generate “timed out” and “connection refused” errors, but i have attached the screenshots in "evidence/Scenarion-A-2 | Task-8.png"



