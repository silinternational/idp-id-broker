# idp-id-broker #


## Requirements ##
1. VirtualBox
2. Vagrant

or

1. Docker

## Setup ##
1. Clone this repo
2. Delete the .git/ folder
3. [Vagrant users]: Edit Vagrantfile to specify whatever IP address you want, and adjust the synced folder 
   that gets mounted as /data if you need to
4. Copy ```local.env.dist``` to ```local.env```
5. Review config files in `common/config/main.php` and `frontend/config/main.php`
6. Define an environment variable for `COMPOSER_CACHE_DIR` for where composer should cache packages, makes composer 
   installs and updates much faster
7. Run `make start`, or if using Vagrant, run `vagrant up`

## Composer / GitHub rate limit
If you hit problems of composer unable to pull the necessary dependencies
due to a GitHub rate limit, copy the `auth.json.dist` file to `auth.json` and
provide a GitHub auth. token.

## Customizing Email Content
There are various emails that ID Broker can send, such as when a user's password
has been changed. The templates for those are in `application/common/mail/`. When
running this yourself, you can certainly replace those template files with
modified versions.

Things to remember if you customize the email templates:

 - Make sure you don't change the filenames of any of the templates.
 - Ensure that content inserted into the HTML templates (*.html.php) are
   HTML-encoded. (See the existing HTML templates for examples.)

If there is additional information that you need to include in your emails but
which isn't currently made available to the email templates, please submit a
Pull Request (PR) on GitHub. This helps us add missing information that others
might also need as well as helps us prevent sensitive information (such as a
user's password or the hash of their password) from ever being included in an
email.

## API Documentation
The API is described by [api.raml](api.raml), and an auto-generated [api.html](api.html) created by
`raml2html`. To regenerate the HTML file, run `make raml2html`.

## Running tests interactively locally
1. Run `make testcli` to build and start needed containers and drop you in a shell
2. Run desired tests, example: `./vendor/bin/behat features/authentication.feature`
