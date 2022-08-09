# E-Commerce Project for Enuygun PHP Bootcamp

## Steps To Start The Project

- `cd ecommerce_project`
- `composer install`
- `symfony server:start`

## Steps To Automaticly Setup The Database

- `php bin/console doctrine:database:create`
- `php bin/console make:migration`
- `php bin/console doctrine:migrations:migrate`
- `php bin/console doctrine:fixtures:load`

## Demo User Informations

- ADMIN

  - admin@demo.com
  - demo123

- USER
  - user@demo.com
  - demo123
