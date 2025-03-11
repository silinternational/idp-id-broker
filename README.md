# idp-id-broker #


## Requirements ##
- Docker

## Setup ##
1. Clone this repo
2. Copy ```local.env.dist``` to ```local.env``` and fill in the required variable values.
3. Run `make start`

## Configuration

By default, configuration is read from environment variables. These are documented
in the `local.env.dist` file. Optionally, you can define configuration in AWS Systems Manager.
To do this, set the following environment variables to point to the configuration in
AWS:

* `AWS_REGION` - the AWS region in use
* `APP_ID` - AppConfig application ID or name
* `CONFIG_ID` - AppConfig configuration profile ID or name
* `ENV_ID` - AppConfig environment ID or name
* `PARAMETER_STORE_PATH` - Parameter Store base path for this app, e.g. "/idp-name/"

In addition, the AWS API requires authentication. It is best to use an access role
such as an [ECS Task Role](https://docs.aws.amazon.com/AmazonECS/latest/developerguide/task-iam-roles.html).
If that is not an option, you can specify an access token using the `AWS_ACCESS_KEY_ID` and
`AWS_SECRET_ACCESS_KEY` variables.

If `PARAMETER_STORE_PATH` is given, AWS Parameter Store will be used. Each parameter in AWS Parameter
Store is set as an environment variable in the execution environment.

If `PARAMETER_STORE_PATH` is not given but the AppConfig variables are, AWS AppConfig will be used.
The content of the AppConfig configuration profile takes the form of a typical .env file, using `#`
for comments and `=` for variable assignment. Any variables read from AppConfig will overwrite variables
set in the execution environment.

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

## Adding groups to SAML `member` attribute from a Google Sheet

The `local.env.dist` file shows how to add the necessary environment variables
in order to sync values from a Google Sheet to the `user.groups_external` field
in the database, which are then included in the SAML `member` attribute that can
be sent to the website that the user is signing into. See the
`EXTERNAL_GROUPS_SYNC_*` entries in the `local.env.dist` file.

### How Many of What?

You will need...

* One Google Cloud Console Project.
  * Example:  
    `My IDPs External Groups Sync`
* One Google Sheet per application that needs custom groups.
  * Examples:  
    `App A SSO Groups`  
    `App B SSO Groups`
* One Service Account (in that Project) per IDP that you want to sync the
  custom groups into for that application.
  * Examples:  
    `IDP 1 groups for App A`  
    `IDP 2 groups for App A`
* One tab in each Google Sheet per IDP that you want to sync that application's
  custom groups into.
  * Examples:  
    `idp1`  
    `idp2`

### Specific How-To Steps

To do this...

1. Use at least version 6.8.0 of ID Broker.
2. Create a Google Sheet named for the application that needs the groups
   (e.g. `App A SSO groups`).
3. Create a tab (in that Google Sheet) named after the short/code name of your
   IDP (e.g. `idp1`) with two columns: `email` and `groups`.
   - To add groups for a specific user, put the user's (lowercase) email address
     for that IDP in the `email` cell in their row.
   - Only use one row per user.
   - Put all of a user's desired groups in their `groups` cell, separated by
     commas. Example: "ext-appa-managers, ext-appa-designers"
   - Group names must begin with your chosen prefix and a dash
     (e.g. "ext-appa-").
4. Create a Google Cloud Console Project (e.g. `My IDPs External Groups Sync`).
5. Add a Service Account to that Project.
   - I recommend naming it after both the IDP you will use it for and the
     application that needs the groups (e.g. `IDP 1 groups for App A`).
6. Create a JSON Key for that Service Account.
7. Share the Google Sheet that you created earlier with the `client_email` value
   in that JSON Key file (as a Viewer, no notification).
8. Set the following environment variables for your ID Broker instance:
   - `EXTERNAL_GROUPS_SYNC_set1AppPrefix`
     - Set this to some prefix starting with "ext-", e.g. `ext-appa`
   - `EXTERNAL_GROUPS_SYNC_set1GoogleSheetId`
     - Set this to the ID of the Google Sheet you created earlier.
   - `EXTERNAL_GROUPS_SYNC_set1JsonAuthString`
     - Use the JSON key you just created here, compacted to a single line by
       something like this command:  
       `cat service-account-key-from-google-abcdef123456.json | jq -c "."`
9. You can also set the following environment variable if you want to send a
   notification email any time the sync runs and encounters errors (such as
   "No user found for email address ..." or "The given group (ext-appb-users)
   does not start with the given prefix (ext-appa)"):
   - `EXTERNAL_GROUPS_SYNC_set1ErrorsEmailRecipient`
     - Set this to a single email address.
10. If you need to sync those custom groups to another IDP...
    - Ensure that IDP is also running a recent enough version of ID Broker.
    - Create another tab in your Google Sheet.
    - Create another Service Account and JSON Key.
    - Share the Google Account with that new JSON Key's `client_email`.
    - Set the above environment variables in that other IDP, using the same
      app-prefix and Google Sheet ID, but the JSON Auth String from the new JSON
      Key that you created.
11. If you need to sync custom groups for _another app_ to your IDP...
    - Create another Google Sheet similarly, but named for that other app, with
      a tab for each of the relevant IDPs.
    - Create another Service Account (and JSON Key) in that existing Google
      Cloud Console Project.
    - Share the Google Account with that JSON Key's `client_email`.
    - Add another set of the above environment variables, but with the next
      number in the lowercased portion
      (e.g. `EXTERNAL_GROUPS_SYNC_set2AppPrefix`), using an app-prefix for that
      other app, the new Google Sheet's ID, and the new JSON Key (as the JSON
      Auth String).

### Rotating external-groups sync credentials

You can easily rotate the credentials for a Service Account by creating a new
JSON Key for it. Then simply update the
`EXTERNAL_GROUPS_SYNC_set(NUMBER)JsonAuthString` environment variable to use the
contents of that new JSON Key.

Since you can have multiple Keys on Google Cloud for a given Service Account,
you can create a new Key in the Google Cloud Console, switch from the old one to
the new one here, then remove the old one from the Google Cloud Console. In
other words, you can wait to delete the previous Key from that Service Account
until you have deployed the new credentials, if desired, to avoid service
interruption.
