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
### 5. Generate the SSL keys
`php bin/console lexik:jwt:generate-keypair`

### 6. Start server
`symfony server:start`

### 7.Login
You can test the API using the exemple account
```
API endpoint http://127.0.0.1:8000/api/login_check
{
    "username": "exemple@bilemo.com",
    "password": "password"
}
```
You'll recive a JWT

API documentation url `http://127.0.0.1:8000/api/doc`

## License
[MIT](https://choosealicense.com/licenses/mit/)
