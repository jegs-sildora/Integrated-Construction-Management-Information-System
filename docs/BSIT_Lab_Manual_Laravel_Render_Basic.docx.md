**DEPLOYING A LARAVEL APPLICATION TO RENDER**

# **I. OBJECTIVES**

At the end of this laboratory exercise, the student should be able to:

1. Fork and use Render's official Laravel starter repository from GitHub.

2. Create a free PostgreSQL database on Render.

3. Deploy a Laravel web service on Render and configure the required environment variables.

4. Access the deployed application through its public URL.

# **II. MATERIALS REQUIRED**

* A personal computer with internet access.

* A web browser (Google Chrome or Mozilla Firefox).

* A GitHub account (github.com).

* A Render account (render.com), signed up using the same GitHub account.

# **III. BRIEF DISCUSSION**

Laravel is a PHP web framework used to build modern web applications. Render is a cloud hosting platform that allows students and developers to deploy web applications directly from a GitHub repository.

Since Render does not have built-in support for PHP, Laravel applications run inside a prebuilt container. In this basic version of the exercise, the container is already prepared by Render, so the student only needs to fork a ready-made repository, connect it to Render, and configure a few environment variables. No Dockerfile editing is required.

# **IV. PROCEDURE**

## **Part A: Fork the Laravel Starter Repository**

1. Log in to your GitHub account at https://github.com.

2. Open a new tab and go to the starter repository: https://github.com/render-examples/php-laravel-docker

3. At the upper-right corner of the page, click the Fork button.

4. On the Create a new fork page, keep the default settings and click Create fork. The repository will now appear under your own GitHub account.

**NOTE:** Forking creates your own personal copy of the starter project so you can deploy it from Render without altering the original.

## **Part B: Sign In to Render**

1. Open https://render.com in a new tab and click Get Started for Free.

2. Choose Sign in with GitHub and authorize Render to access your GitHub repositories.

3. After signing in, you will be redirected to the Render dashboard.

## **Part C: Create a PostgreSQL Database**

1. On the dashboard, click the New \+ button and select PostgreSQL.

2. Fill in the form:

* Name: laravel-db

* Region: Singapore (or the nearest region to your location)

* Plan: Free

3. Click Create Database and wait until the status changes to Available.

4. Scroll down to the Connections section and copy the Internal Database URL. Save this value in a text file for later use.

## **Part D: Deploy the Web Service**

1. On the Render dashboard, click New \+ and select Web Service.

2. Select Build and deploy from a Git repository, then click Next.

3. Find your forked php-laravel-docker repository in the list and click Connect.

4. Fill in the deployment form with these settings:

* Name: laravel-app

* Region: Same as the database region

* Branch: main

* Runtime: Docker (selected automatically)

* Instance Type: Free

5. Scroll down to Environment Variables and add the following entries:

DATABASE\_URL \= (paste the Internal Database URL from Part C)

APP\_KEY      \= base64:SomeRandomlyGeneratedString==

**NOTE:** You can generate a valid APP\_KEY by visiting https://generate-random.org/laravel-key-generator and copying the result.

6. Click the Create Web Service button at the bottom of the page.

7. Render will begin building and deploying your application. Wait until you see the message Your service is live (this may take 5 to 10 minutes).

## **Part E: Verify the Deployment**

1. At the top of the service page, click the URL that looks like https://laravel-app.onrender.com.

2. The Laravel welcome page should appear in your browser.

3. Take a screenshot of the live page and include it in your laboratory report.

# **V. EXPECTED OUTPUT**

* A running web service on Render with a public HTTPS URL.

* The Laravel welcome page visible in the browser.

* A PostgreSQL database showing Available status on the Render dashboard.

# **VI. GUIDE QUESTIONS**

1. Why is forking the starter repository necessary before deploying on Render?

2. What is the purpose of the APP\_KEY environment variable in a Laravel application?

3. What is the difference between the Internal Database URL and the External Database URL on Render?

4. Based on this activity, what are the advantages of cloud deployment compared to running the application on localhost?

# **VII. OBSERVATIONS AND CONCLUSION**

Write a short reflection (at least 100 words) describing your experience during the deployment process and what you have learned.

\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_

\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_

\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_

\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_

