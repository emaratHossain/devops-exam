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



