1. Problem : systemmd hit starting limit-
   How It happened:
    -  I created a file named /opt/badhon/app-one/app.sh.
    -  It was a simple bash script, and the code looed like following
        
        #!/bin/bash
        echo "Badhon's App One is running..."

    - Here, the echo command actually exit the script after printing "Badhon's App One is running..."
    - But in my service file i wrote "Restart=always", so systemd was trying to restart the service again and again after being exited
    - This caused the systemd to hit the starting limit and the service was not able to start

   How I solved:
    - I update the app.sh file to keep the script running by adding a infinite loop

        #!/bin/bash
        while true; do
            echo "Badhon's App One is running..."
            sleep 10
        done
    
    - Then i restart the service using `systemctl restart app-one.service`
    - Then i check the status using `systemctl status app-one.service`
    - The service is now running successfully