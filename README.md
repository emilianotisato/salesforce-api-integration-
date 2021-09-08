# Salesforce Integration App

## Installation

After cloing the repo, install all composer dependencies:

```bash
composer install
```

For convenience setup a sqlite database:

```bash
touch database/database.sqlite
```

We have your env file redy to work agains that DB:

```bash
cp .env.example .env
php artisan key:generate
```

Please fill in the salesforce email and password to obtain the tokens in the .env file (`SALESFORCE_API_AUTH_EMAIL` and `SALESFORCE_API_AUTH_PASSWORD`).

So finally migrate and seed:

```bash
php artisan migrate:fresh --seed
```

The default user is `nikola.susa@omure.com` with the pass `Omure`.

Please run your server and access to the defined local url:

```bash
php artisan serve
# remember you can change this url and port on your .env file
```

## Usage

We have installed Jeststream so you can go to `/login` in your local domain, and access with the credentials avobe.
After that you can create an API token in the top rigth menu, option "API tokens".

Use that token to as a Bearer token to query the app acording to the endpoints described in the tests.

## Testing

To run the test suite juts do:

```bash
php artisan test
```
