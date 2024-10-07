# idp-id-broker #


## Requirements ##
- Docker

## Setup ##
1. Clone this repo
2. Copy ```local.env.dist``` to ```local.env``` and fill in the required variable values.
3. Set the following environment variables on your host (dev) machine:
   - `DOCKER_UIDGID`: user ID and group ID. Use `1000:1000` if you are the primary user
   on a Linux computer. Otherwise, run `id -u` and `id -g` and use the resulting numbers in place of `1000`.
4. Run `make start`

## Configuration
By default, configuration is read from environment variables. These are documented
in the `local.env.dist` file. Optionally, you can define configuration in AWS AppConfig.
To do this, set the following environment variables to point to the configuration in
AWS:

* `AWS_REGION` - the AWS region in use
* `APP_ID` - the application ID or name
* `CONFIG_ID` - the configuration profile ID or name
* `ENV_ID` - the environment ID or name

In addition, the AWS API requires authentication. It is best to use an access role
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
2. Run desired tests. Examples:
   * `./vendor/bin/behat features/authentication.feature`
   * `./vendor/bin/behat features/authentication.feature:298`

## Google Analytics Calls
Calls are made to Google Analytics regarding users' mfas and whether a password has been pwned.

If you want to have an indication that those calls are likely to succeed, run 
`$ make callGA`.

## Adding groups to SSO `member` attribute from a Google Sheet

The `local.env.dist` file shows how to add the necessary environment variables
in order to sync values from a Google Sheet to the `user.groups_external` field
in the database, which are then included in the SAML `member` attribute that can
be sent to the website that the user is signing into. See the
`EXTERNAL_GROUPS_SYNC_*` entries in the `local.env.dist` file.
