Deploying KisKit
================

KisKit is a straightforward PHP framework. The following are the necessary step to get it up and
running, in addition to setup outside of KisKit's scope (such as Apache/nginx setup, SQL server setup, etc.).
For those tasks, see the documentation for your specific software.

To Prepare KisKit
-----------------

1) Alter config.php and dbconfig.php to reflect your configuration - both can be found in the config directory.
2) Alter requires.json (also in the config directory) to indicate your package requirements. This file follows
the format of a composer.json file (see http://getcomposer.org/doc/04-schema.md).
3) Change directory to the system/cli directory for your project (default is `system/cli`) and run
 `php -f setup.php`. If you wish to see the command line options for setup, type `php -f setup.php -- --help`.
 You may need to determine where php is installed for your system if its not on the PATH (ie. env/bin/php).
 This will install Composer (http://getcomposer.org), install your packages and init any model tables you
 may have defined. You can rerun this setup at any time in order to install any packages you've added to
 requires.json, and to initialize tables for any models you've added.