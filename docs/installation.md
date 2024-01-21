# Installation

## Docker Compose [Recommended]

### Step 1 - Download the required files
Create a directory of your choice (e.g. `./waistline-api`) to hold the `docker-compose.yml` and `.env.local` files.

```bash
mkdir waistline-api
cd ./waistline-api
```
Download `docker-compose.yml` and `.env` by running the following commands:

```bash
wget https://raw.githubusercontent.com/hippothomas/waistline-api/master/docker-compose.yml
```

```bash
wget -O .env.local https://raw.githubusercontent.com/hippothomas/waistline-api/master/.env
```

### Step 2 - Populate the .env file with custom values
You should change these values for security reason:
```dotenv
APP_ENV= # Set it to "prod"
APP_SECRET= # You should generate a new app secret
APP_BASE_URL= # The url of the app (without the ending /)
DATABASE_URL= # Your database connection string
MONGODB_URL= # Your mongodb connection string
```
You should also change the values in consequence in the `docker-compose.yml`.

### Step 3 - Start the containers
From the directory you created in Step 1, run:

```bash
docker compose up -d
```

### Step 4 - Execute migrations
Once the containers are up and running, you may have to execute these commands to instantiate the database and reset the cache:

```bash
docker exec -it waistline-api /bin/sh
php bin/console doctrine:migrations:migrate --quiet
php bin/console cache:clear --quiet
exit
```

