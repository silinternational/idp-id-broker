# idp-id-broker #


## Requirements ##
1. VirtualBox
2. Vagrant

or

1. Docker for Mac/Windows

## Setup ##
1. Clone this repo
2. Delete the .git/ folder
3. [Windows users]: Edit Vagrantfile to specify whatever IP address you want, and adjust the sycned folder 
   that gets mounted as /data if you need to
4. Copy ```local.env.dist``` to ```local.env```
5. Review config files in `common/config/main.php` and `frontend/config/main.php`
6. Define an environment variable for `COMPOSER_CACHE_DIR` for where composer should cache packages, makes composer 
   installs and updates much faster
7. Run `make start`, or if on Windows, run `vagrant up`

## Composer / GitHub rate limit
If you hit problems of composer unable to pull the necessary dependencies
due to a GitHub rate limit, copy the `auth.json.dist` file to `auth.json` and
provide a GitHub auth. token.
