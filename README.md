# idp-id-broker #


## Requirements ##
Linux, macOS
1. Docker

or

Windows (not actively maintained as a development platform)
1. VirtualBox
2. Vagrant

## Setup ##
1. Clone this repo
2. (Vagrant users only) Edit Vagrantfile to specify whatever IP address you want, and adjust the synced folder 
   that gets mounted as /data if you need to
3. Copy ```local.env.dist``` to ```local.env``` and fill in the required variable values.
4. Set the following environment variables on your host (dev) machine:
   - `COMPOSER_CACHE_DIR`: the path where composer should cache packages. This makes composer 
   installs and updates much faster. Typically `/home/user/.composer`
   - `DOCKER_UIDGID`: user ID and group ID. Use `1000:1000` if you are the primary user
   on a Linux computer. Otherwise, run `id -u` and `id -g` and use the resulting numbers in place of `1000`.
5. Run `make start`, or if using Vagrant, run `vagrant up`

## Configuration
By default, configuration is read from environment variables. These are documented
in the `local.env.dist` file. Optionally, you can define configuration in AWS AppConfig.
To do this, set the following environment variables to point to the configuration in
AWS:

* `AWS_REGION` - the AWS region in use
* `APP_ID` - the application ID or name
* `CONFIG_ID` - the configuration profile ID or name
* `ENV_ID` - the environment ID or name

In addition, the AWS PHP SDK requires authentication. It is best to use an access role
such as an [ECS Task Role](https://docs.aws.amazon.com/AmazonECS/latest/developerguide/task-iam-roles.html).
If that is not an option, you can specify an access token using the `AWS_ACCESS_KEY_ID` and
`AWS_SECRET_ACCESS_KEY` variables.

The content of the configuration profile takes the form of a typical .env file, using
`#` for comments and `=` for variable assignment. Any variables read from AppConfig 
will overwrite variables set in the execution environment.

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

## Google Analytics Calls
Calls are made to Google Analytics regarding users' mfas and whether a password has been pwned.

If you want to have an indication that those calls are likely to succeed, run 
`$ make callGA`.
