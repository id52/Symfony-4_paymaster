# Deployment
## Install requirements 
```
composer install
```

## Edit database settings
```
cp .env.dist .env
```

```
mcedit .env
```

```
DATABASE_URL=mysql://root:password@127.0.0.1:3306/invoice.drcooper.ru
```

## Create database

```
php bin/console doctrine:database:create
```

## Apply database migrations
```
php bin/console doctrine:migrations:migrate
```

## Load fixtures
```
php bin/console doctrine:fixtures:load
```

## Run the dev server
```
php -S 127.0.0.1:8000 -t public
```

```
php bin/console server:run
```

## Apache and Nginx configs: 

https://symfony.com/doc/current/setup/web_server_configuration.html


### User:
* Login: admin <br/>
* Password: admin

