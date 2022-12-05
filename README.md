## BileMo - API Webservice 

BileMo API is a BileMo company API

## Installation

### 1. project's requirements

symfony cli, composer

### 2. clone the project
```
cd your_project_dir
git clone https://github.com/jonatanocr/p7_api_bilemo.git
```
### 3. Configuration and dependencies
```
cd p7_api_bilemo
# edit .env.exemple to .env file to match your configuration

# make Composer install the project's dependencies into vendor/
composer install
```

### 4. Set the database
```
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load
```

## License
[MIT](https://choosealicense.com/licenses/mit/)
