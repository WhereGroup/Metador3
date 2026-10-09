
```bash
git clone git@repo.wheregroup.com:metador2/metador3.git
cd metador3

touch .env.proxy.local

bin/compose up --build

docker exec -it metador3 "/bin/sh"

cd app
composer update -o
bin/console doctrine:migrations:migrate
bin/console metador:init:database
yarn && yarn build

```