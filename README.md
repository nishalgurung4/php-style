# php-style

## Description

Personal Laravel Coding style set up for the CI/CD

## Usage

### Installing and Configuring PHP CS FIXER  into PHP project

Install `friendsofphp/php-cs-fixer` using compoer

```sh
composer require friendsofphp/php-cs-fixer --dev
```

Add following line in the `composer.json` file

```
 "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/nishalgurung4/php-style"
        }
    ],
```

Install the project using composer

```sh
composer require nishalgurung4/php-style --dev
```

create `.php-cs-fixer.dist.php` file for the configuration

```php
<?php

$finder = PhpCsFixer\Finder::create()
  ->notPath('vendor')
    ->notPath('bootstrap')
    ->notPath('storage')
    ->in(__DIR__)
    ->name('*.php')
    ->notName('*.blade.php');

 
 return Nishal\styles($finder);
```

You can fix style using following command

```sh
vendor/bin/php-cs-fixer fix
```

You can define script in the `composer.json` file

```json
"scripts": {
        "format": [
            "vendor/bin/php-cs-fixer fix"
        ]
    }
```

so that you can run following command to check and fix the style

```sh
composer format
```

This will create `.php-cs-fixer.cache` file so it is better to ignore it in the `.gitignore` file.

```sh
echo ".php-css-fixer.cache" >> .gitignore
```

Now if you want to integrate CI/CD using github, you need to create workflows inside `.github/workflows` folder or simply you can create it from the github dashboard.
Here is an example that I have configured with the AWS codedeploy.

Create `.github/workflows/deploy.yml` file and add following content.

```yml
name: Laravel Deployment

on:
  push:
    branches: [ main, dev ]
  pull_request:
    branches: [ main, dev ]

jobs:
  tests-production:
    if: ${{ github.ref == 'refs/heads/main'}}
    runs-on: ubuntu-20.04

    steps:
    - uses: shivammathur/setup-php@15c43e89cdef867065b0213be354c2841860869e
      with:
        php-version: '8.0'
    - uses: actions/checkout@v2
    - name: Copy .env
      run: php -r "file_exists('.env') || copy('.env.production', '.env');"
    - name: Install Dependencies
      run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist
    - name: Generate key
      run: php artisan key:generate
    - name: Directory Permissions
      run: chmod -R 777 storage bootstrap/cache
    - name: Create Database
      run: |
        mkdir -p database
        touch database/database.sqlite
    - name: Execute tests (Unit and Feature tests) via PHPUnit
      env:
        DB_CONNECTION: sqlite
        DB_DATABASE: database/database.sqlite
      run: vendor/bin/phpunit

  deploy-production:
    if: ${{ github.ref == 'refs/heads/main'}}

    needs: tests-production

    runs-on: ubuntu-20.04

    steps:
    - name: "Configure AWS Credentials"
      uses: aws-actions/configure-aws-credentials@v1
      with:
        aws-access-key-id: ${{ secrets.AWS_ACCESS_KEY}}
        aws-secret-access-key: ${{ secrets.AWS_SECRET_ACCESS_KEY}}
        aws-region: [replace_it_with_aws_region]

    - name: "Deploy to AWS"
      run: |
        aws deploy create-deployment \
          --application-name laravel-app-code-deploy-app \
          --deployment-config-name CodeDeployDefault.OneAtATime \
          --deployment-group-name laravel-app-code-deploy-group \
          --description "Deploy from Github" \
          --github-location repository=nishalgurung4/laravel,commitId=${{github.sha}}

  tests-staging:
    if: ${{ github.ref == 'refs/heads/dev'}}
    runs-on: ubuntu-20.04

    steps:
    - uses: shivammathur/setup-php@15c43e89cdef867065b0213be354c2841860869e
      with:
        php-version: '8.0'
    - uses: actions/checkout@v2
    - name: Copy .env
      run: php -r "file_exists('.env') || copy('.env.production', '.env');"
    - name: Install Dependencies
      run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist
    - name: Generate key
      run: php artisan key:generate
    - name: Directory Permissions
      run: chmod -R 777 storage bootstrap/cache
    - name: Create Database
      run: |
        mkdir -p database
        touch database/database.sqlite
    - name: Execute tests (Unit and Feature tests) via PHPUnit
      env:
        DB_CONNECTION: sqlite
        DB_DATABASE: database/database.sqlite
      run: vendor/bin/phpunit

  deploy-staging:
    if: ${{ github.ref == 'refs/heads/dev'}}

    needs: tests-staging

    runs-on: ubuntu-20.04

    steps:
    - name: "Configure AWS Credentials"
      uses: aws-actions/configure-aws-credentials@v1
      with:
        aws-access-key-id: ${{ secrets.AWS_ACCESS_KEY}}
        aws-secret-access-key: ${{ secrets.AWS_SECRET_ACCESS_KEY}}
        aws-region: [replace_it_with_aws_region]

    - name: "Deploy to AWS"
      run: |
        aws deploy create-deployment \
          --application-name laravel-app-code-deploy-app \
          --deployment-config-name CodeDeployDefault.OneAtATime \
          --deployment-group-name laravel-app-code-deploy-group-staging \
          --description "Deploy from Github" \
          --github-location repository=nishalgurung4/laravel,commitId=${{github.sha}}
```

You can create another workflow `.github/workflows/format_php.yml` for the PHP CS fixer rules for CI.

```yml
name: Format PHP

on:
    push:
        branches: [main, dev]
    pull_request:
        branches: [main, dev]

jobs:
    php-cs-fixer:
        runs-on: ubuntu-20.04
        steps:
            - name: Checkout code
              uses: actions/checkout@v2

            - name: Install composer dependencies
              run: composer install

            - name: Run PHP CS Fixer
              run: composer format

            - name: Commit changes
              uses: stefanzweifel/git-auto-commit-action@v4
              with:
                  commit_message: Fix PHP styling
                  branch: ${{ github.head_ref }}
              env:
                  GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}

```

### Configuring Prettier for styling js and css

Install Prettier locally

```sh
npm install --save-dev --save-exact prettier
```

create `.prettierrc.json` to configure it. Add following line

```json
{
    "printWidth": 100,
    "singleQuote": true,
    "tabWidth": 4,
    "trailingComma": "es5"
}
```

Next, create a `.prettierignore` file to let the Prettier CLI and editors know which files to not format.

```
vendor/
public/
```

To format all files with Prettier, run following command

```sh
npx prettier --write .
```

You can also run `npx prettier --check .` in CI to make sure that your project stays formatted.

Let's add script in `package.json` file so that you can run prettier using `npm run format` command

```json
"script": {
        "format": "prettier --write 'resources/**/*.{css,js,vue}'"
    }
```

Now you can create another workflow `.github/workflows/format_prettier.yml` for the Prettier style fix.

```yml
name: Format Prettier

on:
    push:
        branches: [main, dev]
    pull_request:
        branches: [main, dev]

jobs:
    prettier:
        runs-on: ubuntu-20.04
        steps:
            - name: Checkout code
              uses: actions/checkout@v2

            - name: Install NPM dependencies
              run: npm ci

            - name: Run Prettier
              run: npm run format

            - name: Commit changes
              uses: stefanzweifel/git-auto-commit-action@v4
              with:
                  commit_message: Apply Prettier changes
                  branch: ${{ github.head_ref }}
              env:
                  GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}

```

`Note: Above workflow also fix the changes if needed and it will auto commit the changes`


