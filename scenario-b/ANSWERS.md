# Scenario B — Answers

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

Task 23

Question 1 - Why after changing anything in the code, the build time is increased?

Answer 1 - Docker build images in layers and each layer is cached. When we change anything in the code, the layers after the changed layer are re-built, which increases the build time. In my case i changed app/routes.php file, so after COPY app/ ./, all the layers after that are re-built, which increases the build time.


Task 24

Question 1 - Which layer is biggest? What command created it? Could it be smaller?

Answer 1 - `FROM php:8.3-cli-alpine` command created the biggest layer. And as we are already using the smallest base image (alpine), it cannot be smaller.

