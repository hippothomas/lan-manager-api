# LAN Manager

App to help easily manage a LAN Party for the Staff

## Stack
- **Framework**: [Symfony](https://symfony.com/)
- **API Framework**: [API Platform](https://api-platform.com/)
- **Database**: [PostgreSQL](https://www.postgresql.org/)
- **Tests**: [PHPUnit](https://phpunit.readthedocs.io/)

## Running Locally
```shell
$ git clone https://github.com/hippothomas/lan-manager-api api
$ cd api
# Create the .env file
$ composer i
$ symfony server:start
```

Create a `.env.local` file similar to [.env](.env)

### Create the db structure
```shell
$ symfony console doctrine:database:create
$ symfony console doctrine:migrations:migrate
```

### Load fixtures to populate the db
```shell
$ symfony console doctrine:fixtures:load
```

## Tests
```shell
$ symfony console doctrine:database:drop --force --env=test
$ symfony console doctrine:database:create --env=test
$ symfony console doctrine:migrations:migrate -n --env=test
$ symfony console doctrine:fixtures:load -n --env=test
$ symfony php bin/phpunit
```

Don't forget to create a `.env.test.local` file similar to [.env.test](.env.test)

## License
See the [LICENSE](LICENSE.md) file for license rights and limitations (MIT).
