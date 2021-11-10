# Change Log
All notable changes to this project will (in theory) be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]


## [5.3.3] - 2021-11-10
### Removed
- Avoid error spam by removing email service status check

## [5.3.2] - 2021-11-08
### Changed
- Allow hyphens (UUIDs) in user employee_id

## [5.3.0] - 2021-05-04
### Added
- Added created date/time to user
- Added deactivated date/time to user
- Send email alert to HR with all abandoned users, if any (configurable)

## [5.2.3] - 2020-10-27
### Fixed
- Update backup codes `created_at` when generating new codes

## [5.2.2] - 2020-10-07
### Fixed
- Updated Docker credentials

## [5.2.0] - 2020-05-28
### Added
- Added capability to export user data to Google Sheets

## [5.1.0] - 2020-05-21
### Added
- Added `active` flag to `mfa` object in User API response
### Fixed
- Silence unhelpful log messages on mfa emails

## [5.0.2] - 2020-04-29
### Fixed
- Log messages are now written to log output immediately
### Changed
- Password-expired emails don't send if the password has been expired for a while.

## [5.0.1] - 2020-04-23
### Fixed
- Moved migration run from run-cron.sh to run.sh 
- Silenced a new Yii warning
### Changed
- pwned expiration changed from past (-20 years) to near future (+5 minutes)

## [5.0.0] - 2020-04-21
### Added
- Added haveibeenpwend checking during authentication
- Changed logging from syslog to stdout/stderr

## [4.9.0] - 2020-03-09
### Added
- Added two new configuration options: `mfa_required_for_new_users` and `mfa_allow_disable`
- Added `require_mfa` to User API responses

## [4.8.1] - 2020-01-22
### Fixed
- Expired users are actually deactivated, not just in API responses

## [4.8.0] - 2019-12-12
### Added
- Include `idpName` in `member` list in user responses

## [4.7.1] - 2019-10-02
### Fixed
- Don't send password expiring notices if the password has already expired

## [4.7.0] - 2019-08-28
### Added
- Added config option to bcc the mfa-manager-help email to another address
- Two more metrics for analytics
### Changed
- Changed "manager" to "recovery contact" in user-facing text

## [4.6.4] - 2019-08-20
### Fixed
- Change help text to match printable codes button label

## [4.6.3] - 2019-08-06
### Fixed
- Fix defect in Mfa::deleteOldRecords. 

## [4.6.2] - 2019-08-05
### Added
- Cron deletes old manager "rescue" 2SV (MFA) codes 

## [4.6.1] - 2019-07-05
### Added
- Auto-generated api.html removed to alleviate concerns highlighted by security scan 

## [4.6.0] - 2019-06-28
### Added
- Optionally include an email address on the bcc line of manager mfa emails 

## [4.5.2] - 2019-06-25
### Fixed
- An incorrect date was reported for last password change in the password 
  expiry emails.
- No validation was done on employee ID character set. This allowed the
  creation of users that couldn't be accessed through the REST API.
### Changed
- The text segment of email messages is now generated from the html templates,
  rather than separately-maintained text templates.
  
## [4.5.1] - 2019-06-17
### Fixed
- Fixed bug in field for last_login_utc to return null when null 

## [4.5.0]
### Added
- Config option for invite email delay. Default is no delay.
- Purge inactive users after a configurable amount of time.

### Changed
- Renamed UserResponse property `groups` to `member` to coincide with SAML2 naming.

## [4.4.0] - 2019-05-29
### Added
- Use `supportName` and `supportEmail` if `helpCenterUrl` is not provided
- Allow multi-line email signature

## [4.3.0] - 2019-05-21
### Added
- Added multi-field search on /user endpoint.

## [4.2.1] - 2019-05-09
### Changed
- Delay invite emails by 1 hour to allow time for a new email account to be created.
### Removed
- Removed `grace_period_ends_on` from API

## [4.2.0] - 2019-05-06
### Added
- Added recovery method reminder email, replacing daily method verify emails
- Send an email notice when an unverified recovery email is purged
- Send email notices when user's password is about to expire or has expired
- Allow more than one manager 2sv code, and delete all codes when any 2sv is used
- Extend password grace period if password expires because of user disabling 2sv 

## [4.1.1] - 2019-04-18
### Fixed
- Fixed bad recovery method verify link in email sent by cron task

## [4.1.0] - 2019-04-17
### Removed
- Removed LDAP password migration

### Added
- Re-added mfa nag, as 'add' property on `mfa` object in UserResponse
- Added recovery method nag, as 'add' property on `method` object in UserResponse

## [4.0.1] - 2019-04-12
### Changed
- Updated email templates to make links show the actual link target

## [4.0.0] - 2019-04-11
### Added
- Added recovery method and MFA review flags to user resource returned from
  `/authentication` and `/user` endpoints
- Added recovery methods, as previously defined in idp-pw-api.
- Added "Hide" feature for users with increased privacy concerns
- Added `PUT /mfa/{mfaId}` endpoint to update MFA labels.
- Added `invite` property on `/authentication` for new user invite authentication
- Added ability to restart a password recovery method verification
- New 'manager' MFA type -- on request, send a backup code to user's manager.
- Added 'groups' and 'personal_email' fields to User object and database table.
- Automatically creates a recovery method using `personal_email`, only for new users.
- Added `profile_review` property on user response, to trigger a review at login.
- Added new user onboarding flow for users without a primary email address.
- Added `PUT /user/{employeeID}/password/assess` endpoint to pre-assess a new password.

### Changed
- Changed password reuse error HTTP status code from 422 to 409
- Unverified recovery methods are now included in listing from `GET /user/{id}/method`
- Updated Welcome email to remove Insite obsolescense notification
- /method/{uid}/verify no longer requires `employee_id`
- Changed dates in API to use ISO-8601 format (e.g. 2019-01-08T12:54:00Z)
- Default MFA labels are now set according to the type of MFA (e.g. "Smartphone")
- `/mfa/{id}/verify` returns the mfa object

### Removed
- Removed spouse_email from user table, model, and API
- Removed `mfa.nag` property from user response, replaced by `profile_review`

## [3.5.0] - 2018-07-17
### Added
- Add spouse and manager email fields

## [3.4.2] - 2018-06-12
### Changed
- Receive and use the Mfa to decide about sending emails

## [3.4.1] - 2018-01-18
### Changed
- Include MFA rate limit email content

## [3.4.0] - 2018-01-17
### Added
- Automated MFA related email updates

## [3.3.2] - 2017-12-21
### Changed
- Updated email content and fixing require_mfa updates

## [3.3.1] - 2017-12-18
### Changed
- Minor GA fix

## [3.3.0] - 2017-12-08
### Added
- Added Google Analytics

## [3.2.1] - 2017-12-06
### Changed
- Changed Welcome Email content

## [3.2.0] - 2017-11-30
### Added
- New Welcome Email

## [3.1.0] - 2017-11-28
### Added
- 2-Step Verification

## [3.0.0] - 2017-08-31
### Added
- Introduces ability to have emails sent under certain circumstances, e.g., "New 
  account created"

## [2.2.0] - 2017-06-16
### Added
- Allow consumers to provide email or username during authentication.

## [2.1.0] - 2017-06-16
### Added
- Ability to search for users by username and/or email.

## [2.0.0] - 2017-06-14
### Added
- (No description)

## [1.0.0] - 2017-06-01
### Added
- Initial version of ID Broker.

[Unreleased]: https://github.com/silinternational/idp-id-broker/compare/5.3.0...HEAD
[5.3.0]: https://github.com/silinternational/idp-id-broker/compare/5.2.3...5.3.0
[5.2.3]: https://github.com/silinternational/idp-id-broker/compare/5.2.2...5.2.3
[5.2.2]: https://github.com/silinternational/idp-id-broker/compare/5.2.0...5.2.2
[5.2.0]: https://github.com/silinternational/idp-id-broker/compare/5.1.0...5.2.0
[5.1.0]: https://github.com/silinternational/idp-id-broker/compare/5.0.2...5.1.0
[5.0.2]: https://github.com/silinternational/idp-id-broker/compare/5.0.1...5.0.2
[5.0.1]: https://github.com/silinternational/idp-id-broker/compare/5.0.0...5.0.1
[5.0.0]: https://github.com/silinternational/idp-id-broker/compare/4.7.1...5.0.0
[4.7.1]: https://github.com/silinternational/idp-id-broker/compare/4.7.0...4.7.1
[4.7.0]: https://github.com/silinternational/idp-id-broker/compare/4.6.4...4.7.0
[4.6.4]: https://github.com/silinternational/idp-id-broker/compare/4.6.3...4.6.4
[4.6.3]: https://github.com/silinternational/idp-id-broker/compare/4.6.2...4.6.3
[4.6.2]: https://github.com/silinternational/idp-id-broker/compare/4.6.1...4.6.2
[4.6.1]: https://github.com/silinternational/idp-id-broker/compare/4.6.0...4.6.1
[4.6.0]: https://github.com/silinternational/idp-id-broker/compare/4.5.2...4.6.0
[4.5.2]: https://github.com/silinternational/idp-id-broker/compare/4.5.1...4.5.2
[4.5.1]: https://github.com/silinternational/idp-id-broker/compare/4.5.0...4.5.1
[4.5.0]: https://github.com/silinternational/idp-id-broker/compare/4.4.0...4.5.0
[4.4.0]: https://github.com/silinternational/idp-id-broker/compare/4.3.0...4.4.0
[4.3.0]: https://github.com/silinternational/idp-id-broker/compare/4.2.1...4.3.0
[4.2.1]: https://github.com/silinternational/idp-id-broker/compare/4.2.0...4.2.1
[4.2.0]: https://github.com/silinternational/idp-id-broker/compare/4.1.1...4.2.0
[4.1.1]: https://github.com/silinternational/idp-id-broker/compare/4.1.0...4.1.1
[4.1.0]: https://github.com/silinternational/idp-id-broker/compare/4.0.1...4.1.0
[4.0.1]: https://github.com/silinternational/idp-id-broker/compare/4.0.0...4.0.1
[4.0.0]: https://github.com/silinternational/idp-id-broker/compare/3.5.0...4.0.0
[3.5.0]: https://github.com/silinternational/idp-id-broker/compare/3.4.2...3.5.0
[3.4.2]: https://github.com/silinternational/idp-id-broker/compare/3.4.1...3.4.2
[3.4.1]: https://github.com/silinternational/idp-id-broker/compare/3.4.0...3.4.1
[3.4.0]: https://github.com/silinternational/idp-id-broker/compare/3.3.2...3.4.0
[3.3.2]: https://github.com/silinternational/idp-id-broker/compare/3.3.1...3.3.2
[3.3.1]: https://github.com/silinternational/idp-id-broker/compare/3.3.0...3.3.1
[3.3.0]: https://github.com/silinternational/idp-id-broker/compare/3.2.1...3.3.0
[3.2.1]: https://github.com/silinternational/idp-id-broker/compare/3.2.0...3.2.1
[3.2.0]: https://github.com/silinternational/idp-id-broker/compare/3.1.0...3.2.0
[3.1.0]: https://github.com/silinternational/idp-id-broker/compare/3.0.0...3.1.0
[3.0.0]: https://github.com/silinternational/idp-id-broker/compare/2.2.0...3.0.0
[2.2.0]: https://github.com/silinternational/idp-id-broker/compare/2.1.0...2.2.0
[2.1.0]: https://github.com/silinternational/idp-id-broker/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/silinternational/idp-id-broker/compare/1.0.0...2.0.0
[1.0.0]: https://github.com/silinternational/idp-id-broker/commit/06c28b8ad18545cd2bdec4d09d2f9f146394409c
