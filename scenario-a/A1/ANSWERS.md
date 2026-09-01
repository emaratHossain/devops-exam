Question : Explain in your own words why dan can list the folder but not read the file. Two or three sentences

Answer : 

- I used this command " sudo setfacl -m g:bad-auditor:rx secrets" to give read and execute permission to the group bad-auditor for the secrets folder. This allows dan to list the folder but not read the file.

- If I used " sudo setfacl -R -m g:bad-auditor:rx secrets" it would recursively apply the permission to all files and subdirectories within the secrets folder, and dan would be able to read the file.