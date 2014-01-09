#!/usr/bin/env php
<?php
/**
 * Copyright (c) Christopher Keefer, 2013. All Rights Reserved.
 *
 * Run to automate install of dependencies for your project (as defined in composer.json) using
 * Composer ( http://getcomposer.org ).
 *
 * Checks whether it is able to connect to the database using the details in config/dbconfig.php, and if so,
 * sets up tables for each model that has defined a 'createTable' function.
 */

// Define server root, replace backslashes, ensure there's a trailing slash
define('SERVER_ROOT', rtrim(str_replace("\\", "/", realpath(dirname(__FILE__).'/../../')), '/').'/');

// Require configuration details
require_once(SERVER_ROOT . 'config/config.php');
// Require Controller class, which Setup will extend (in order to have easy access to helpers and models)
require_once(SERVER_ROOT . SYS_DIR .'core/controller.php');
require_once(SERVER_ROOT . SYS_DIR .'core/model.php');

define('VENDOR_DIR', SERVER_ROOT . "vendor/");
define('COMPOSER_INSTALL_DIR', VENDOR_DIR . "composer/");
define('COMPOSER_URL', "://getcomposer.org/composer.phar");
define('REQUIRES_FILE', SERVER_ROOT . "composer.json");

use sanemethod\kiskit\system\core\Controller;

class Setup extends Controller{

    public $chunkSize = 1048576; // File chunk size in bytes: 1024*1024 (one KiB)
    const USAGE = <<<"EOT"
[ -- Usage -- ]

\t--skip-composer\t Skip downloading and running composer.

\t--skip-composer-download\t Skip downloading composer - assumes we already have a copy of composer in the expected directory (APP_DIR/lib/vendor/composer/composer.phar).

\t--force-composer-download\t Force us to download composer even if we already have a copy of composer.phar.

\t--force-package-update\t Force composer to use our requires json, not composer.lock.

\t--skip-dbinit\t Skip initializing the database.

\t--force-db-reinit\t Force all createTable functions to run again, even if the tables already exist in the db. Useful if you add a DROP table statement at the beginning of your createTable SQL.

\t--requires=path/to/file\t Indicate the absolute path to the json file indicating package requirements (default is config/requires.json).

\t--help\t Display params and exit.
EOT;

    /**
     * On instantiating Setup, run a platform check and, if the platform checks out, download composer and
     * run it, and initialize the db.
     *
     * @param array $argv
     */
    function __construct($argv){
        // Display usage text and exit
        if (in_array('--help', $argv)) $this->displayHelp();
        // Check platform for errors/warnings in config for usage with KisKit and with Composer.
        $ok = $this->checkPlatform();
        if (!$ok) exit(1);
        // Download composer
        if (!in_array('--skip-composer-download', $argv) && !in_array('--skip-composer', $argv))
        {
            $ok = $this->downloadComposer($argv);
        }
        // Run composer
        if (!in_array('--skip-composer', $argv) && $ok) $this->runComposer($argv);
        // Init databases
        if (!in_array('--skip-dbinit', $argv)) $this->dbinit($argv);
        echo PHP_EOL."-- Finished Setup --".PHP_EOL;
    }

    /**
     * Display usage text and exit.
     */
    function displayHelp(){
        echo Setup::USAGE;
        echo PHP_EOL;
        exit(0);
    }

    /**
     * Check for problems that would prevent KisKit from working, as well as including checks that would
     * prevent Composer from working (borrowed from composers installer script).
     * @return bool
     */
    function checkPlatform(){
        $errors = array();
        $warnings = array();
        $iniPath = php_ini_loaded_file();

        if (version_compare(PHP_VERSION, '5.4', '<'))
        {
            // Require PHP version 5.4+ - mostly because we use the [] array alias in places.
            $errors['version'] = PHP_VERSION;
        }
        if (!extension_loaded('PDO'))
        {
            // Require PDO - this should be enabled by default for PHP versions 5.1+
            $errors['pdo'] = true;
        }
        if (!function_exists('json_decode'))
        {
            // Require json encoding/decoding
            $errors['json'] = true;
        }
        if (!ini_get('allow_url_fopen'))
        {
            // Require allow_url_fopen to be 'On' - we need this (to download Composer) and Composer needs it.
            $errors['allow_url_fopen'] = true;
        }
        // Composer requires the following extensions and settings
        if (ini_get('detect_unicode'))
        {
            $errors['unicode'] = 'On';
        }
        if (!extension_loaded('Phar')){

            $errors['phar'] = true;
        }
        if (!extension_loaded('filter'))
        {
            $errors['filter'] = true;
        }
        if (!extension_loaded('hash'))
        {
            $errors['hash'] = true;
        }
        if (!extension_loaded('ctype'))
        {
            $errors['ctype'] = true;
        }
        // Composer warns of poor/lacking functionality based on the following warnings
        if (!extension_loaded('openssl'))
        {
            $warnings['openssl'] = true;
        }
        if (ini_get('apc.enable_cli'))
        {
            $warnings['apc_cli'] = true;
        }
        // regex on phpinfo to get curlwrappers and sigchild state.
        ob_start();
        phpinfo(INFO_GENERAL);
        $phpinfo = ob_get_clean();
        if (preg_match('{Configure Command(?: *</td><td class="v">| *=> *)(.*?)(?:</td>|$)}m', $phpinfo, $match))
        {
            $configure = $match[1];
            if (strpos($configure, '--enable-sigchild') !== false)
            {
                $warnings['sigchild'] = true;
            }
            if (strpos($configure, '--with-curlwrappers') !== false)
            {
                $warnings['curlwrappers'] = true;
            }
        }
        // Switch on errors to display what errors were encountered, and some suggestions on how to resolve them.
        if (!empty($errors))
        {
            $errText = '-- Error --'.PHP_EOL.'Please resolve the following to continue setup:'.PHP_EOL;
            foreach($errors as $error => $val)
            {
                switch($error)
                {
                    case 'version':
                        $errText .=
                            'PHP version is '.$val.'; must be at least 5.4. Please upgrade your PHP version.'.
                            PHP_EOL;
                        break;

                    case 'json':
                        $errText .= "The json extension is missing.".PHP_EOL;
                        $errText .= "Install it or recompile php without --disable-json.".PHP_EOL;
                        break;

                    case 'phar':
                        $errText = "The phar extension is missing.".PHP_EOL;
                        $errText .= "Install it or recompile php without --disable-phar.".PHP_EOL;
                        break;

                    case 'filter':
                        $errText = "The filter extension is missing.".PHP_EOL;
                        $errText .= "Install it or recompile php without --disable-filter".PHP_EOL;
                        break;

                    case 'hash':
                        $errText = "The hash extension is missing.".PHP_EOL;
                        $errText .= "Install it or recompile php without --disable-hash".PHP_EOL;
                        break;

                    case 'ctype':
                        $errText = "The ctype extension is missing.".PHP_EOL;
                        $errText .= "Install it or recompile php without --disable-ctype".PHP_EOL;
                        break;

                    case 'unicode':
                        $errText = "The detect_unicode setting must be disabled.".PHP_EOL;
                        $errText .= "Add the following to the end of your `php.ini`:".PHP_EOL;
                        $errText .= "    detect_unicode = Off".PHP_EOL;
                        break;

                    case 'allow_url_fopen':
                        $errText = PHP_EOL."The allow_url_fopen setting is incorrect.".PHP_EOL;
                        $errText .= "Add the following to the end of your `php.ini`:".PHP_EOL;
                        $errText .= "    allow_url_fopen = On";
                        break;
                }
            }
            echo $errText;
            return false;
        }
        // Switch on warnings to indicate how to fix potential problems.
        if (!empty($warnings)) {
            $warnText = "-- Warning --".PHP_EOL.
                "Some settings on your machine may cause stability issues.".PHP_EOL;
            $warnText .= 'If you encounter issues, try changing the following:'.PHP_EOL;
            
            foreach ($warnings as $warning => $val) {
                switch ($warning) {
                    case 'apc_cli':
                        $warnText = "The apc.enable_cli setting is incorrect.".PHP_EOL;
                        $warnText .= "Add the following to the end of your `php.ini`:".PHP_EOL;
                        $warnText .= "    apc.enable_cli = Off".PHP_EOL;
                        break;

                    case 'sigchild':
                        $warnText = "PHP was compiled with --enable-sigchild which can cause issues ".
                            "on some platforms.".PHP_EOL;
                        $warnText .= "Recompile it without this flag if possible, see also:".PHP_EOL;
                        $warnText .= "    https://bugs.php.net/bug.php?id=22999".PHP_EOL;
                        break;

                    case 'curlwrappers':
                        $warnText = "PHP was compiled with --with-curlwrappers which will cause issues with ".
                            "HTTP authentication and GitHub.".PHP_EOL;
                        $warnText .= "Recompile it without this flag if possible".PHP_EOL;
                        break;

                    case 'openssl':
                        $warnText = "The openssl extension is missing, which will reduce security.".PHP_EOL;
                        $warnText .= "If possible you should enable it or recompile php with --with-openssl"
                            .PHP_EOL;
                        break;
                }
                echo $warnText;
            }
        }
        return true;
    }

    /**
     * Download composer. Return true if composer is found (and --force-composer-download is not set in $argv),
     * or when the download completes successfully.
     *
     * @param $argv
     * @return bool
     */
    function downloadComposer($argv){
        $sourceURL = (extension_loaded('openssl') ? 'https' : 'http') . COMPOSER_URL;
        set_time_limit(0);
        if (!is_dir(COMPOSER_INSTALL_DIR))
        {
            mkdir(COMPOSER_INSTALL_DIR, 0755, true);
        }
        // If we already have a copy of composer, skip download it unless --force-composer-download is specified
        if (is_file(COMPOSER_INSTALL_DIR."composer.phar") && !in_array('--force-composer-download', $argv)){
            echo "-- Composer is Available --".PHP_EOL;
            return true;
        }

        echo "-- Downloading Composer --".PHP_EOL;

        $filesize = get_headers($sourceURL, true)['Content-Length'];
        $bytesWritten = 0;
        try{
            $localF = fopen(COMPOSER_INSTALL_DIR.'composer.phar', 'w+');
            $remoteF = fopen($sourceURL, 'r');
            while(!feof($remoteF))
            {
                $bytesWritten += fwrite($localF, fread($remoteF, $this->chunkSize));
                printf("%s", "[ ".$bytesWritten." / ".$filesize." ]\r");
            }
        }catch(Exception $e){
            echo "-- Error --".PHP_EOL."Failed to open and download composer.phar.".PHP_EOL;
            exit(1);
        }
        fclose($localF);
        fclose($remoteF);
        echo PHP_EOL."-- Finished Downloading Composer --".PHP_EOL;
        return true;
    }

    /**
     * Run composer. Get our requires file, and copy it to the appropriate directory for composer to
     * find and use it, then call composer. Unlink the copy of the requires file when finished, and
     * output the returned text from the call to composer. If --force-package-update is specified,
     * delete composer.lock to force composer to use composer.json instead and generate a new .lock file.
     *
     * @param $argv
     */
    function runComposer($argv){
        $requiresFile = (in_array('requires', $argv)) ? explode('=', $argv['requires'])[1] :
            REQUIRES_FILE;
        if (!is_file($requiresFile)){
            echo "-- Error --".PHP_EOL."Failed to find requires file in specified path: ".$requiresFile.PHP_EOL;
            exit(1);
        }
        echo "-- Installing Required Packages via Composer --".PHP_EOL;
        copy($requiresFile, VENDOR_DIR . "composer.json");
        if (in_array('--force-package-update', $argv))
        {
            @unlink(SERVER_ROOT . "composer.lock");
        }
        exec('"'.PHP_BINARY.'" -f "'.COMPOSER_INSTALL_DIR.'composer.phar" install --working-dir="'
            . SERVER_ROOT, $eout);
        @unlink(VENDOR_DIR . "composer.json");
        echo join(PHP_EOL, $eout);
        echo PHP_EOL."-- Composer Run Complete --".PHP_EOL;
    }

    /**
     * Run through our models and for any that have createTable functions defined, run them.
     */
    function dbinit($argv){
        echo "-- Initializing Database & Models --".PHP_EOL;
        $models = scandir(SERVER_ROOT . APP_DIR . 'models/', SCANDIR_SORT_DESCENDING);

        // Pop off . and ..
        array_pop($models);
        array_pop($models);

        foreach($models as $modelFile){
            $modelFile = explode('.', $modelFile)[0];
            echo "Initializing Model {$modelFile}".PHP_EOL;
            $initModel = $this->model($modelFile);
            // If lastError is populated, something went wrong with the db connection
            if ($initModel->lastError !== null)
            {
                echo $initModel->lastError;
                exit(1);
            }
            if ($initModel->tableExists() && !in_array('--force-db-reinit', $argv))
            {
                echo "Table for Model {$modelFile} already exists.".PHP_EOL;
                continue;
            }
            if (method_exists($initModel, 'createTable'))
            {
                if ($initModel->createTable())
                {
                    echo "Created Table for Model {$modelFile}.".PHP_EOL;
                }
                else
                {
                    echo "-- Error --".PHP_EOL;
                    echo "Failed to execute createTable for {$modelFile}. Please check createTable function ".
                        "and SQL syntax and try again.".PHP_EOL;
                    echo $initModel->lastError.PHP_EOL;
                    exit(1);
                }
            }
            else
            {
                echo "No createTable function defined for Model {$modelFile}.".PHP_EOL;
            }
        }
        echo PHP_EOL."-- Finished Initializing Models --".PHP_EOL;
    }
}

new Setup($argv);