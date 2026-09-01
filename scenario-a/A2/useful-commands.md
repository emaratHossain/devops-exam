badhon@vmi3536696:~$ ss -lptn
State             Recv-Q            Send-Q                       Local Address:Port                         Peer Address:Port            Process            
LISTEN            0                 4096                               0.0.0.0:8080                              0.0.0.0:*                                  
LISTEN            0                 4096                         127.0.0.53%lo:53                                0.0.0.0:*                                  
LISTEN            0                 5                                  0.0.0.0:5050                              0.0.0.0:*                                  
LISTEN            0                 4096                               0.0.0.0:22                                0.0.0.0:*                                  
LISTEN            0                 5                                  0.0.0.0:6060                              0.0.0.0:*                                  
LISTEN            0                 4096                            127.0.0.54:53                                0.0.0.0:*                                  
LISTEN            0                 4096                                  [::]:8080                                 [::]:*                                  
LISTEN            0                 4096                                  [::]:22                                   [::]:*                                  
badhon@vmi3536696:~$ sudo ss -lptn
State       Recv-Q      Send-Q           Local Address:Port           Peer Address:Port      Process                                                        
LISTEN      0           4096                   0.0.0.0:8080                0.0.0.0:*          users:(("docker-proxy",pid=29201,fd=8))                       
LISTEN      0           4096             127.0.0.53%lo:53                  0.0.0.0:*          users:(("systemd-resolve",pid=81869,fd=15))                   
LISTEN      0           5                      0.0.0.0:5050                0.0.0.0:*          users:(("python3",pid=89741,fd=3))                            
LISTEN      0           4096                   0.0.0.0:22                  0.0.0.0:*          users:(("sshd",pid=82531,fd=3),("systemd",pid=1,fd=129))      
LISTEN      0           5                      0.0.0.0:6060                0.0.0.0:*          users:(("python3",pid=89750,fd=3))                            
LISTEN      0           4096                127.0.0.54:53                  0.0.0.0:*          users:(("systemd-resolve",pid=81869,fd=17))                   
LISTEN      0           4096                      [::]:8080                   [::]:*          users:(("docker-proxy",pid=29206,fd=8))                       
LISTEN      0           4096                      [::]:22                     [::]:*          users:(("sshd",pid=82531,fd=4),("systemd",pid=1,fd=131))      
badhon@vmi3536696:~$ lsof -i :5050
badhon@vmi3536696:~$ sudo lsof -i :5050
COMMAND   PID USER   FD   TYPE DEVICE SIZE/OFF NODE NAME
python3 89741 root    3u  IPv4 868402      0t0  TCP *:5050 (LISTEN)