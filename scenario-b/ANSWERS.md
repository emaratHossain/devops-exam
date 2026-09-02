# Scenario B — Answers

Task 21

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