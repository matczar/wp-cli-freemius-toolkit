# WP-CLI Freemius Toolkit

> This WP-CLI package allows you to manage the plugin on your Freemius account.

## Install

### Requirements

- PHP >= 7.3
- WP-CLI >= 2.4.0

### Via WP-CLI Package Manager (requires wp-cli >= 2.4.0)
Just run `wp package install matczar/wp-cli-freemius-toolkit`

## Configuration

- Create a `.freemius` file containing the developer ID along with the API access keys in the folder with the global WP-CLI configuration (use `wp cli info` command to check the path of this folder). All this data can be obtained on your profile page in the [Freemius dashboard](https://dashboard.freemius.com/#!/profile/).

```.dotenv
FS__API_DEV_ID=
FS__API_PUBLIC_KEY="pk_example_public_key"
FS__API_SECRET_KEY="sk_example_secret_key"
```

- In the plugin folder you want to manage, create a `.freemius.yml` file containing your Freemius plugin ID and a list of files and folders to be sent to the API when creating the new plugin version. The plugin ID can be found on the page with its Settings.

Example of `.freemius.yml` file:
```yaml
plugin_id: 123456789
include:
  - assets
  - includes
  - my-plugin.php
  - readme.txt
```

## Using

This package implements the following commands:

### wp freemius-toolkit version

Lists, deploys and delete plugin versions.

### wp freemius-toolkit version list

List plugin versions.

~~~
wp freemius-toolkit version list [--fields=<fields>] [--format=<format>]
~~~

### wp freemius-toolkit version deploy

List plugin versions.

~~~
wp freemius-toolkit version deploy [--local] [--add-freemius-contributor]
~~~

### wp freemius-toolkit version delete

Delete an existing version.

~~~
wp freemius-toolkit version delete <id>... [--yes]
~~~
