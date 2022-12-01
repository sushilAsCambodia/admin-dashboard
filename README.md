### ENVIRONMENT SETTINGS
Add this to environment variables `SANCTUM_STATEFUL_DOMAINS="localhost:8080"`

### RUN THE FOLLLOWING ARTISAN COMMAND
`composer install`
`php artisan migrate --seed`
`php artisan key:generate`
`php artisan update:permissions`

### Run this command every time new API route with name is added
`php artisan update:permissions`


