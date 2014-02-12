KisKit
======
(Keep It Simple Kit)

KisKit is a featureful but straightforward PHP web framework. Particularly suited to use with modern, REST-oriented
client-side frameworks, but flexible enough to be used however you see fit.

Kiskit offers MVC style project organization, a simple database abstraction layer, quality logging (PSR-3 compliant)
and compatibility/extensibility (framework is PSR-4 compliant, and includes easy setup and use of
[composer](http://getcomposer.org)), making adding libraries like monolog, SwiftMailer or Doctrine
(or hundreds of others) a simple and familiar process.

See [Composer](http://getcomposer.org) and [Packagist](https://packagist.org) for more details on using composer as
a php package manager, and [PHP-FIG](http://php-fig.org) for details on PSRs.


###Contents

#####[Setup](#kiskit-setup)
* [One-Time Setup Steps](#one-time-setup-steps)
* [Recurring Setup Steps](#recurring-setup-steps)

#####[Configuration Details](#configuring-kiskit)
* [Constants](#constants)
* [Config Files](#config-files)

#####[Routing](#routing-1)
* [Parameters](#routing-parameters)
* [Requests](#requests)
* [RESTful](#restful)

#####[Controllers](#controllers-1)
* [Views](#view)
* [JSON](#json)
* [File](#file)
* [Upload](#upload)
* [Model](#model)
* [Helper](#helper)
* [Lib](#lib)
* [Load](#load)

#####[Databases, PDO and Models](#databases-pdo-and-models-1)
* [Using Models](#using-models)
* [Model Properties](#model-properties)
* [Model Functions](#model-functions)
* [Model CreateTable](#model-createtable)

#####[Exceptions and Logging](#exceptions-and-logging-1)
* [Configuration](#configuration)
* [Logging Exceptions](#exceptions)
* [Logging Object](#logging-object)

KisKit Setup
============

For setup beyond the immediate scope of KisKit (such as Apache/nginx setup, SQL server setup, etc.), please see
the specific documentation for the software in question.

One-Time Setup Steps
--------------------

1. Alter `config.php`, `logconfig.php` and `dbconfig.php` to reflect your configuration - all can be found in the
`config` directory.
    * See sections [Constants](#constants) and [Config Files](#config-files) below to get more details on what each of
    these configuration files contain and do.
    * KisKit offers a simple abstraction layer for database interaction that sit atop PHP's
[PDO](http://www.php.net/manual/en/intro.pdo.php) database interface, and is thus compatible with any db solution
[supported by PDO](http://www.php.net/manual/en/pdo.drivers.php).
        * For the impatient, this includes MSSQL/SQL Azure, MySQL, SQLite and PostgreSQL.

2. Alter `composer.json` in the root directory to indicate your package requirements. This file follows
the format of a [composer.json file](http://getcomposer.org/doc/04-schema.md). A sample composer.json file
is included in the package and can function as a customizable base.

3. Define the `createTable` functions in any models you know you need at this point - these will be run in
step 4, using the database configuration details from step 1. You can skip this if you don't want to automatically
create your tables when running setup.

4. In a terminal, change directory to the system/cli directory for your project (default is `system/cli`) and run
 `php -f setup.php`. If you wish to see the command line options for setup, type `php -f setup.php -- --help`.
 You may need to determine where php is installed for your system if its not on the PATH (ie. env/bin/php).
 This will check your php install and setup against requirements, download and install Composer,
 install the required packages defined in composer.json, and init any model tables you may have defined.
 You can rerun this setup at any time in order to install any packages you've added to requires.json, and to
 initialize tables for any models you've added.
     * Note also that once you've run setup once, composer will be available in your APP_DIR/vendor/composer
     directory - you can run it via `php composer.phar`, which will give you a list of available commands.

KisKit is now prepared for use.

Recurring Setup Steps
---------------------

1. Whenever you want to add a new package to the project, you'll want to make the appropriate changes to your
`composer.json` file, and then either run `php -f setup.php` again, or run `php composer.phar` directly.
2. If you create new models and want to use the createTable functions to generate the tables for you, simply
run `php -f setup.php` again. Having the models contain the statements for table setup can hasten later setup
on a remote server, and offers insight into the table structure for developers.

Configuring Kiskit
==================

Constants
---------
In addition to any you define in `config.php`, the following constants will be present and available to all
controllers and views:

**SERVER_ROOT**: The base directory from which KisKit is executing. This is the same as the directory in which
the root index.php file can be found. For example, `F:/usr/etc/apache2/htdocs/mysitedir/`. Gauranteed to use
forward slashes (even on Windows), and to have a trailing slash.

**BASE_URL**: The base url for the request. For example, `http://mysite.com/`. The protocol will be set appropriately
(either http or https), and a trailing slash is guaranteed. This will include `/index.php/` if index.php is not being
rewritten out of the url (as is the default via the included .htaccess file).

**APP_DIR**: The base directory for your application (as opposed to the system files). This is 'app/'
by default - notice the trailing slash, which should be included if you change this value.
Your app directory must conform to the directory structure of the default app dir (you can add as many
directories as you want, but at least controllers, models and views must exist.)

**SYS_DIR**: The directory where the KisKit system files will live, that your application relies on/extends from.
This is 'system/' by default.

**DEFAULT_CONTROLLER**: When navigating to the root of your site, the named controller will be the one to handle
the request, calling its `index()` function. Default is 'home'.

**DB_GROUP**: This indicates which database group from `dbconfig.php` should be used to interact with the db.
Can be left blank if you're not going to use a database, or intend to interact with it via some method other than
PDO and KisKit models. Default value is 'development'.

**EXCEPT_HANDLER**: Indicates which log settings group from `logconfig.php` should be used to handle logging and
exceptions. Can be left blank (empty string, null, etc.) if you're going to handle logging/exceptions via some other
method. Default value is 'development'.

Config Files
------------

**config.php**: This file contains certain constants that define application directories and options, as noted in
the `CONSTANTS` section.

**dbconfig.php**: This file contains a multidimensional array, each element of which is an array of database
configuration options. Each array should be named according to the value you will set `DB_GROUP` to in `config.php`.

See [Databases, PDO and Models](#databases-pdo-and-models-1) for more details.

Example:

```php
'development' => array(
    'hostname' => '127.0.0.1',
    'driver' => 'mysql',
    // Number to specify port, null to use default port
    'port' => 3306,
    'username' => 'dbuser',
    'password' => 'dbpass',
    'database' => 'dbname'
)
```

**logconfig.php**: Similar in structure to the dbconfig.php file, containing a multidimensional array, each element
is an array of logging configuration options. Each array should be named according to the value you will set
`EXCEPTION_HANDLER` to in `config.php`.

See [Exceptions and Logging](#exceptions-and-logging-1) for more details.

Example:

```php
'development' => array(
    'logPath' => SERVER_ROOT . APP_DIR . 'logs/',
    'logTemplate' => 'error',
    'webTrace' => true,
    'stderr' => false,
    'file' => 100,
    'html' => 100,
    'json' => false
)
```

Routing
=======
The initial point of entry from a request will always be index.php at the root. Within index, the autoloader will
be loaded (to handle PSR-0 and PSR-4 conforming libs/packages), logging will be instantiated (if using
EXCEPT_HANDLER), and routing will begin.

The first section of the uri after the base is always the controller, and the second is always a public
function within that controller.

For example:
`http://mysite.com/home/dothis/`

Will instantiate the HomeController class in home.php within the app/controllers directory.
It will then check whether there's a method named 'dothis' in home, and whether dothis is public. If both are
true, dothis will handle the request. If the former is false, an exception (with code 404) is thrown. If the
latter, an exception (with code 403) is thrown. ExceptHandler will understand these as HTTP exceptions and
treat them accordingly, sending the proper headers and a customizable error page (see
[Logging](#exceptions-and-logging-1) for more details).

### Routing Parameters
Parameters can be passed into a function via either the uri or the query string when performing any request.

For example, if we have a function defined as:
```php
public function dothis(param1, param2)
```
And we make the call
`http://mysite.com/home/dothis/cheese/pizza`

Then param1 in dothis will be set to 'cheese', and param2 will be set to 'pizza'.

We can also specify by name in any order if we use the query string.

`http://mysite.com/home/dothis?param2=pizza&param1=cheese`

Will still result in param1 being 'cheese' and param2 being 'pizza' inside of the function.

We can also mix and match uri params and query string params, and named params in the query string will still
get assigned to their proper variable.

`http://mysite.com/home/dothis/pizza?param1=cheese`

Will still result in param1 being assigned 'cheese' and our leftover parameter will be assigned to param2, thus
'pizza'. If more parameters exist in the uri then are defined on the controller, all named parameters are assigned
first, and then any leftover parameters are assigned in order to the remainder. In order to access all parameters,
you can directly access the parameter array via `$this->request->params` in any controller function.

You can set defaults on any function parameter; if there are less parameters set via the uri and/or query string
than exist on the controller, the default values for the leftover parameters will prevail.

You can also directly access the $_GET or $_POST arrays from `$this->request->get` and `$this->request->post`
respectively. These are merely convenience references that point directly to the $_GET and $_POST arrays - any
changes to them will also change the $_GET and $_POST arrays. Changes to the params array will not effect either
the $_GET or $_POST, or $this->request->get/post.

On POST requests, you will need to access `$this->request->post` or $_POST in order to get variables passed via
POST. Parameters passed via the uri will still be set appropriately.

###Requests

For each request, a new Request object is created. The request object contains the request method (ie. GET, POST,
etc.), the target controller name, target function name, a merge array of parameters passed via uri sections and
via GET query string, and references to $_GET and $_POST.

The request object is always available to the called controller function via `$this->request`.

The request object has the following accessible properties:
* `verb` => The request method (GET, POST, PUT, OPTIONS, DELETE)
* `target` => The target controller file name, minus any extension.
* `controller` => The target controller class name. Note that controllers in KisKit have a postfix of 'Controller' -
so, if you have a home.php containing your home controller, the class name should be `HomeController`. You can
change or remove this prefix by setting 'conPostFix'=>false in the array passed to Router in `index.php`.
* `method` => The function being called with the target controller.
* `params` => The merged array of params from the query string and uri, set in the order that the named params exist
on the function called, if any.
* `get` => A direct reference to $_GET.
* `post` => A direct reference to $_POST.

###RESTful

As a convenience to those making a RESTful API, you can specify in the function name what request method it should
handle, instead of needing to have a messy if statement on the value of `$this->request->verb` in your function.

For example, if you want to declare a function, 'dothis', that handles only GET requests, and another that handles
only POST requests, and a final version that handles all others, you would declare:
```php
public function dothis_GET(param1, param2){}
public function dothis_POST(param1, param2, param3){}
public function dothis(){}
```

When `http://mysite.com/home/dothis/` is called via GET, the router will call dothis_GET first, if found. If not
found, it will call the un-postfixed function. Thus, you can specify handlers for specific request methods, and
a default handler for all others, with the name and the postfix clearly representing their relationship and the
conditions under which they'll be called.

Controllers
===========

All controllers must live in your APP_DIR/controllers directory. Each controller should be named according to the
class you intend. The naming rule is:

File name: name.php
Controller Class name: NameController

Note the Controller postfix. You can change or eliminate this postfix, as noted under [Requests](#requests)
should you so desire.

All controllers should extend the Controller class from system\core.

```php
use sanemethod\kiskit\system\core\Controller;

class NameController extends Controller {}
```

There is no need to explictly include the Controller php file - it will be automatically included for you.

All public fuctions within a controller are accessible via url navigation; all protected and private functions are
not, and any attempt to call them from url navigation will result in a 403.

All Controller functions will have access to `$this->request` (a [Request](#requests) object), and, if
EXCEPT_HANDLER is set, `$this->logger` (an [ExceptHandler](#logging-object) object).

###Functions
All Controllers have the following functions:

####View
`$this->view()` => Display a php view. These views can be composited.
For Example:

```php
$this->view('header');
$this->view('content');
$this->view('footer');
```

Will display a single page comprised of the resulting markup of these three views.

The first argument to view must be the view file. By default, all controllers will look in the APP_DIR/views
directory for a file named the same as the string specified, minus the extension (ie. `header.php`). You can split
the views into their own subdirectories and either specify the subdirectory when calling the view (ie.
`$this->view('home/header.php')`) or set a constant VIEW_LOC on the controller which indicates where all the views
for this controller should be found (ie. `const VIEW_LOC = 'home';`).

The second argument is optional - an array of values that will be extracted as local variables.

```php
$this->view('content', ['firstvar'=>'banana', 'secondvar'=>'pie']);
```

Inside the view file, you will now have access to $firstvar and $secondvar.

####Json
`$this->json()` => Return json to the client. This sends the appropriate headers (and thus must not be called if
any other output has previously occurred), and outputs the results of [json_encode](http://ca1.php.net/json_encode).
The first argument should be the value (array, object, etc.) to encode, and the optional second argument encoding
options as per the PHP json_encode function.

Example:

```php
$this->json(['first'=>1, 'second'=>2, 'third'=>3]);
```

Returns:

```json
{"first":1, "second":2, "third":3}
```

####File
`$this->file()` => Offers a file for download, as specified by the arguments to the function call.

Example:

```php
$this->file('path/To/File.txt', 'newFileName.txt');
```

####Upload
`$this->upload()` => Handle a (potentially chunked) file upload. Returns JSON representation of file details and
write success. If chunked upload, also returns the content range written.

Example:

```php
$this->upload('newFileName.txt', 'path/to/save/to', 'json');
```

Returns:

```json
{"file":{"size":123560, "name":"newFileName.txt", "path":"path/to/save/to", type:"mimeType"}, "success":true}
```

####Model
`$this->model()` => Load the specified model. Returns a new [model object](#using-models).

Example:

```php
$users = $this->model('users');
$firstUser = $users->selectOne(['where'=>['user_id' => 1]]);
```

####Helper
`$this->helper()` => Load a helper - a class that provide some particular functionality (like email or file
handling). Returns a new object of that class. As helpers can be a varied lot, see the individual helper to
determine its usage.

Example:

```php
$email = $this->helper('email');
$email->from('test@test.com')->to(["first@test.com", "second@test.com"])
            ->subject('Test Email')->message("Message String")->send();
```

####Lib
`$this->lib()` => Load a third-party library (installed under the vendor directory, PSR-0 or PSR-4 compliant or,
with the proper configuration in the Composer autoload, otherwise). Returns a new object of the specified class.
What this object will offer you is entirely library specific. Note that you **DON'T** have to use this to
load third-party libraries - its merely a convenience (indeed, helpers and models can also be loaded via
direct reference - just add the appropriate `use` statement above the Controller class).

Example:

```php
$monologJsonFormatter = $this->lib('Monolog\Formatter\JsonFormatter', arg1, arg2);
```

Is just an alias for:

```php
$monologJsonFormatter = new Monolog\Formatter\JsonFormatter(arg1, arg2);
```

####Load
`$this->load()` => Load an arbitrary file, and instantiate a class found therein. This method underlies the
other class loading methods, and allows you to specify certain options if you want to avoid using the defaults
(for instance, you want to load a model whose class doesn't have the 'Model' postfix).

Example:

```php
$this->load(SERVER_ROOT.APP_DIR.'models/users');
```

Will load `users.php` from the specified directory and attempt to instantiate and return an object of class `Users`.

Databases, PDO and Models
=========================
All of your models must live in the APP_DIR/models directory by default, and should extend system/core/Model.

Example:

```php
use sanemethod\kiskit\system\core\Model;

class UserModel extends Model{}
```

Note the 'Model' postfix - all model classes should end in Model (in the same way that all Controller classes
should end in Controller) by default. You can use [Load](#load) to instantiate your model class and
avoid this, if you prefer.

####Using Models
KisKit models provide a layer of abstraction over PHP PDO, which itself is a data-access abstraction layer for
various databases. It is not meant to be comprehensive - in some cases, you will want to create functions
on each model to directly interact with PDO, rather than using simple abstractions. In many cases, however,
the abstractions may save time on simple, routine operations.

All Models should have a `__construct` function that calls construct on the super class and establishes the
database connection.

```php
function __construct()
{
    parent::__construct();
    $this->dbh = $this->dbConnect();
}
```

This database handle (dbh) should then be used when manually preparing PDO SQL statements. See
[PDO](http://www.php.net/manual/en/intro.pdo.php) for more details.

#####Model properties
Every model possess the following properties:

`table` => The name of the table, as a string. Empty string by default. You should set this within the class.

Example:

```php
class UserModel extends Model{
    protected $table = 'table_name';
```

`fieldWhiteList` => An array of strings, indicating names of fields in the table that insertion and
update operations are allowed on. Any fields included in a insert or update operation that aren't in the
white list will be silently filtered out.

#####Model functions
Every model possesses the following db abstractions for selecting, inserting, updating and deleting rows.

`$this->tableExists()` => Check whether $this->table exists in the database. Returns boolean.
`$this->select()` => Select any number of rows from this table based on the $where array, where each key should be
the field we want to select on in the db, and the array value the value of said field: array(id=>5, name=>'bob').
Select all fields (default) or specify fields as an array (id, name, date) or as a string "id, name, date".
Returns an associative (potentially multi-dimensional) array if successful, false otherwise.

    @param array $options{
        @type string|array $fields
        @type array $where
        @type int $fetchMode
        @type bool $fetchOne Whether to limit this fetch to a single result.
    }
    @return bool|array

Example:

```php
var $users = $this->model('users');
$user->select(['fields'=>'name', 'where'=>['user_songs' => 1]]);
```

Will return an array of the name field of all user rows that match the criteria.

`$this->selectOne()` => As select, but will fetch only the first row of any result set.
`$this->insert()` => Inserts a single record into the table. Accepts an associative array as a parameter,
where each key should correspond to a table field name, with the associated value being inserted into that column.
It is never required to include a tables primary key unless it doesn't possess a default value.

Example:

```php
$this->insert(['name'=>'Bob', 'songs'=>5]);
```

`$this->insertMany()` => As insert, passing instead a multidimensional array, where element of the array is
an array formatted as per insert. Transactional.

`$this->update` => Update a single row based on the $record and $where arrays. The $where array should contain
key(s) that exist within our $record array, and indicate which $keys should be used for the WHERE statement.
Parameters: array $record, array|string $where.

Example:

```php
$this->update(['name'=>'joe'], ['id'=>5]);
```

`$this->updateMany()` => As per update, with the same modifications as insertMany.

`$this->delete()` => Delete any number of rows which match the where statement.
Parameter: array|string $where

Example:

```php
$this->delete(['id'=>5]);
```

#####Model CreateTable
Definining a function named `createTable()` on a model will cause that function to be run during the execution
of `setup.php`. This is an excellent way to have tables automatically generated for you when running - the code
within will only execute if the table doesn't currently exist.

Example:

```php
/**
 * Create the table.
 * @return bool false if failed to create table, else true
 */
function createTable()
{
    $ct = $this->dbh->prepare(
        "CREATE TABLE {$this->table} (
          user_id INT NOT NULL,
          user_code VARCHAR(255) NOT NULL,
          PRIMARY KEY (user_id)
        )"
    );
    $ct->execute();
}
```

Exceptions and Logging
======================

####Configuration
Error Levels:

* 100 => 'DEBUG'
* 200 => 'INFO'
* 250 => 'NOTICE'
* 300 => 'WARNING'
* 600 => 'ERROR'
* 700 => 'CRITICAL'
* 800 => 'ALERT'
* 900 => 'EMERGENCY'

Setting any of the possible error outputs to one of the error levels will cause all errors of that level and
above to be logged to the appropriate output.

Possible Error Outputs:

* 'stderr' => php standard error output (php://stderr) - usually for CLI;
* 'file' => log to a file (date-stamped, stored in logPath);
* 'html' => log as html output (using logTemplate, detail controlled by webTrace);
* 'json' => log as json output (will override html).

Webtrace:

* true == display full details of errors in html error page, including stack trace.
* false == display only simple error messages.

####Exceptions
So long as `EXCEPT_HANDLER` is set, all exceptions will be picked up on (but not caught) by ExceptHandler
(accessible from Controllers as $this->logger). This allows us to log exceptions, fatal or otherwise, to
files and other appropriate outputs. By default, ExceptHandler will output to a file named according to
the date (ie. 2014_01_31.log).

Depending on the value of `webTrace`, the html output will include either the full strack trace in addition
to a meaingful error message, or just a simple error message.

Any uncaught exceptions you throw can also trigger logging and display, so you can, for instance, throw an
exception with a 403 from isnide of a controller to trigger display of a 403 message.

Example:

```php
throw new Exception('Access Denied', 403);
```

####Logging Object
You can also without throwing exceptions from any controller, via `$this->logger`. For any log, you can specify the
log level, log message, and an optional array which will be interpolated with the log message.

Example:

```php
$this->logger->log(ExceptHandler::ALERT, "Replace {this} and {like} it!", ["this"=>"that", "like"=>"hate"]);
```

Will result in a log message like: `Replace that and hate it!`

You can also use level specific aliases to skip needing to specify the log level.

Example:

```php
$this->logger->debug('Simple Debug Message');
$this->logger->info('Info message - slightly higher level than debug!');
```