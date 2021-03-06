Jacwright / REST Server (Extended)
============================

The backbone of this project is this repository, [jacwright/RestServer](https://github.com/jacwright/RestServer).

## Table of Contents
* [Supports](#supports)
    * [Request methods](#request-methods)
    * [Adapters](#adapters)
* [Installation](#installation)
    * [.htaccess](#htaccess)
    * [Using composer](#using-composer)
    * [Directly usage](#directly-usage)
* [Configuration](#configuration)
    * [The server](#the-server)
    * [Setting up the database](#setting-up-the-database)
        * [Using composer](#using-composer-1)
        * [Directly usage](#directly-usage-1)
    * [Setting up PHPUnit](#setting-up-phpunit)
* [Whats a Resource?](#whats-a-resource)
* [Looking at a Controller](#looking-at-a-controller)
    * [The init() method](#the-init-method)
    * [A method inside Controller](#a-method-inside-controller)
    * [Method annotations](#method-annotations)
    * [Response structure-checking](#response-structure-checking)
* [Looking at a Model](#looking-at-a-model)
    * [The init() method](#the-init-method-1)
* [Database](#database)
    * [Helpers](#helpers)
    * [Example usage](#example-usage)


-----------------------------------------------------------------

## Supports
This will tell you what the project currently supports in terms of HTTP request methods available and what types of databases you can use.

#### Request methods
Available methods are
* GET
* POST
* ~~PUT~~
* ~~DELETE~~
* ~~PATCH~~

#### Adapters
* MySQLi
* ~~PDO~~
* ~~PostGreSQL~~
* ~~Oracle~~

## Installation
> **NOTE** that you'll see me use _http://api.local/_ in the examples below.. This is simply a virtual host set up on my own local machine and I advise you to do the same, but the name could be totally different, of course.


#### .htaccess
> **NOTE** that you might have to tweak with .htaccess to get it right, unless you think e.g. `http://api.local/index.php/users` would be attractive.

If you `$ git clone`'d the project then you'll already have an _.htaccess_ in the root of the project.. If you didn't then you would have to add it yourself.. here's how mine look like though..

```
RewriteEngine on
RewriteBase /

RewriteCond %{REQUEST_URI} !v1/(.*)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule .* http://%{HTTP_HOST}/v1/ [QSA]
RewriteRule ^v1/(.*)/?$ index.php?segments=$1 [QSA,L]
```

So what it does is that it takes anything and redirects you to `/v1/SEGMENTS_IF_YOU_HAVE_ANY` no matter what. You might want to have your own base url, but for me I would just stick with `v1` for as long as possible since a REST API is not supposed to change that often anyway, if you consider its users.

#### Using Composer
> **NOTE** Make sure that you got Composer installed globally on your system, otherwise check it out **[here](https://getcomposer.org/doc/00-intro.md#globally)** how to install it.

**5 steps**
1. Run `$ composer require dawaa/jacwright-restserver-extended` to download this project into your vendor/ directory.
2. Add ./_config.php_ at the root of your project, leave it empty for now.
    * See [Configuration - Setting up the database](#setting-up-the-database) later...
3. Again at the root of your project, add the following to the _./index.php_ file to quickly determine if things seems to be working.
    ```php
    // index.php
    <?php
    // Require the autoload.php generated by Composer.
    require_once __DIR__ . '/vendor/autoload.php';

    // Get our Server and instantiate it
    $server = \DAwaa\Core\Server();

    // Start it
    $server->start();
    ```
    Should result in step 5.

4. Go to your local api, **my** virtual host is _http://api.local/_. It should redirect you and append **/v1/** to your url.
5. You should now see the JSON output below.
    ```json
    {
        "meta": {
            "href": "http:\/\/api.local\/v1\/"
        },
        "status": 404,
        "message": "No resource given"
    }
    ```

Awesome it's working, now the server acknowledged our response but since we didn't specify a resource it couldn't give us back anything of "real" value here. But that's okay.

#### Directly usage
> **NOTE** that you should have GIT installed on your local machine.

> **NOTE** that you need Composer globally installed on your system, if you don't. Check it out **[here](https://getcomposer.org/doc/00-intro.md#globally)** how to do it.

**5 steps**
1. Git clone the latest code from the repo, to your webroot or project location.
    ```bash
    $ git clone https://github.com/dawaa/jacwright-RestServer-extended
    ```
2. Run Composer to get all the goodies you'll need
    ```bash
    $ composer install
    ```
3. Add _./src/config.php_, leave it empty for now.
    * See [Configuration - Setting up the database](#setting-up-the-database) later...
4. Go to your local api, **my** virtual host is _http://api.local/_. It should redirect you and append **/v1/** to your url.
5. You should now see the JSON output below.
    ```json
    {
        "meta": {
            "href": "http:\/\/api.local\/v1\/"
        },
        "status": 404,
        "message": "No resource given"
    }
    ```

Aaand it's working, awesome. Now the server acknowledged our response but since we didn't specify a resource it couldn't give us back anything of "real" value here. But that's okay.

## Configuration
In this section we will cover how you will set up the connection between your database and the REST server, as well as how you could set up the PHPUnit environment in case you want to add unit test(s) yourself.

> **NOTE** that we will only go with default settings here. This means that you _can_ configure where the config.php file is located or change where you want your Resources/ directory to be placed.. But this will be covered later.

#### The server
In case you wouldn't fancy the directory name Resources/ or even having it in the root of your project, you're in luck. Because you can change that.
```php
// index.php
<?php
// Require the autoload.php generated by Composer.
require_once __DIR__ . '/vendor/autoload.php';

// Get our Server and instantiate it
$server = \DAwaa\Core\Server();

// Configure the server Resources/ directory
$server->configure(
    array(
        'resourcesPath' => __DIR__ . '/relative/path/to/resources/newNameForResources',
        // 'configPath'    => __DIR__ . '/relative/path/to/config/whateverNameYouWant.php' // ignoring this for now.
    )
);

// Start it
$server->start();
```

The above would look for the resources e.g. "Users" in the new path given and under the parent directory newNameForResources/.

You can also define a new path and name for the _config.php_ file. Like the following:
```php
// index.php
<?php
// Require the autoload.php generated by Composer.
require_once __DIR__ . '/vendor/autoload.php';

// Get our Server and instantiate it
$server = \DAwaa\Core\Server();

// Configure the server Resources/ directory
$server->configure(
    array(
        'resourcesPath' => __DIR__ . '/relative/path/to/resources/newNameForResources',
        'configPath'    => __DIR__ . '/relative/path/to/config/whateverNameYouWant.php'
    )
);

// Start it
$server->start();
```

#### Setting up the Database
##### Using composer
> This scenario expects that you ran `$ composer require dawaa/jacwright-restserver-extended` in your project directory.

By default the library would expect to find your _config.php_ file lying in the root of your project. Like this:
```php
// ./config.php
<?php
$config = array(
    'db_user' => 'DATABASE_USERNAME', // required
    'db_pass' => 'DATABASE_PASSWORD', // required
    'db_name' => 'DATABASE_NAME',     // required
    'db_host' => 'DATABASE_HOST',     // required
    'db_type' => 'MYSQL',             // required, also 'MYSQL' is just one of other adapters.
    'db_port' => 3306                 // required - by default it's already 3306 though.
    'charset' => 'utf8'               // by default it's utf8
);

// Note the return here, muy importante amigo!
return $config;
```

##### Directly usage
> This scenario expects that you ran `$ git clone <REPO URL>` of this project and got the source code at the top of your fingertips.

By default the library would expect to find the _config.php_ file lying under the _./src/_ directory, like this:
```php
// ./src/config.php
<?php
$config = array(
    'db_user' => 'DATABASE_USERNAME', // required
    'db_pass' => 'DATABASE_PASSWORD', // required
    'db_name' => 'DATABASE_NAME',     // required
    'db_host' => 'DATABASE_HOST',     // required
    'db_type' => 'MYSQL',             // required, also 'MYSQL' is just one of other adapters.
    'db_port' => 3306                 // required - by default it's already 3306 though.
    'charset' => 'utf8'               // by default it's utf8
);

// Note the return here, muy importante amigo!
return $config;
```

#### Setting up PHPUnit
> **NOTE** this expects that you'll have PHPUnit installed globally.. otherwise you could make use of the locally installed one. To run the local version, run `$ ./vendor/bin/phpunit`.

> **NOTE** that I only cover the directly usage version here.. which means that you should've done a `$ git clone` of this project.

Before you can start running the unit tests you'd have to change one file, more specifically this one, _./phpunit.xml_. Which should be found in the root of the library.

Below you'll see a couple of `<var>` tags inside `<php>` tags.
These are important that you match them accordingly to be the same as your database set up and as well where you decide to point to this project on your local machine.

> **NOTE** It's only the value="" you'll be editing here, let the rest be.. Unless you know what you're doing.

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<phpunit bootstrap="./test/Bootstrap.php">
    <php>
        <var name="DB_USER" value="DATABASE_USER" />
        <var name="DB_PASS" value="DATABASE_PASSWORD" />
        <var name="DB_NAME" value="DATABASE_NAME" />
        <var name="DB_HOST" value="DATABASE_HOST" />
        <var name="DB_TYPE" value="MYSQL" />

        <var name="RESTAPI_ADDR" value="http://api.local/v1/" />
    </php>
</phpunit>
```


## What's a Resource?
> **NOTE** that the paths mentioned below will be in scenario that you `$ git clone`'d the project.

A resource is an interface of something which we provide in an isolated matter. For each directory within **./Resources** we have a resource, a collection of something. Let's look at a structure representation of this.

```bash
jacwright-restserver-extended/
|
|- Resources/
|  `- Users/      <-- this right here represents a Resource.
|
|- src/
|- tests/
|- vendor/
|... etc
```

Looking closer at our resource we will find something similar to the following
```bash
jacwright-restserver-extended/Resources/
`- Users/
   |- Model/
   |  `- UsersModel.php
   `- Users.php             <-- http://api.local/v1/users - will point to this file.
```

And to access the Users resource we would go to e.g. in my case, `http://api.local/v1/users`.

## Looking at a Controller
I mentioned Resources above and that once you hit the specific url (`http://api.local/v1/users`) it would point you to the file _./Resources/Users/Users.php_. This file is the Controller.

To continue further with the Users resource as our example, let's take a look at its content. Mind you that some of the code will be psuedo code.

> Mind you that some of the code will be psuedo code.

```php
// ./Resources/Users/Users.php
<?php
namespace Resources\Users;

use DAwaa\Core\Controller;

class Users extends Controller {

    /**
     * @url GET /
     */
    public function fetchAll() {
        $users = $this->model->fetchUsers();
        return $this->respondWith( $users );
    }

}
```

**So what's going on here?**

This is a very basic endpoint in our REST api, with some psuedo code `$this->model->fetchUsers();` we fetch all users in a database and later return it with `$this->respondWith();` once a users hits the root url of this resource.. which the annotation `@url GET /` already might've given away.

So we can expect that if a users goes to `http://api.local/v1/users/` the function `fetchAll()` in the Users controller will be run.

Let's add another GET endpoint that should return a single user's activity.

```php
// ./Resources/Users/Users.php
<?php
namespace Resources\Users;

use DAwaa\Core\Controller;

class Users extends Controller {

    /**
     * @url GET /
     */
    public function fetchAll() { // .. code }

    /**
     * @url GET /activity/$userId
     */
    public function fetchUserActivity($userId) {
        $activity = $this->model->fetchActivity( $userId );
        return $this->respondWith( $activity );
    }
}
```

Cool we can add a param-capture, which is just what we did in the `@url` annotation in the function comment. The param set will be passed to function so we make use of it by adding an argument to the function `fetchUserActivity()`. Aaand again we made use of psuedo code with the `$this->model->fetchActivity()`, don't worry I will go through it later.

One more thing i'd like to demonstrate is, how we can refactor the `fetchAll()` function to be more extensive.

```php
// ./Resources/Users/Users.php
<?php
namespace Resources\Users;

use DAwaa\Core\Controller;

class Users extends Controller {

    /**
     * @url GET /$userId
     */
    public function fetchUsers($userId) { // renamed from fetchAll -> fetchUsers
        if ( $userId === null ) {
            $response = $this->model->fetchUsers();
        } else {
            $response = $this->model->fetchUser( $userId );
        }

        return $this->respondWith( $response );
    }

    /**
     * @url GET /activity/$userId
     */
    public function fetchUserActivity($userId) { // .. code }
}
```
Please note that above is again a very basic way of doing things but shows that you can get quite creative with it.

Now we can fetch all users by hitting the endpoint
```
http://api.local/v1/users/
```

Or single out a user by adding their ID
```
http://api.local/v1/users/123
```

#### The init() method
Sometimes we might want to set up a few things before the code is actually run.. For this you can use the `#init()` method within the Controller. Like so:

```php
// ./Resources/Users/Users.php
<?php
namespace Resources\Users;

use DAwaa\Core\Controller;

class Users extends Controller {

    public function init() {
        // .. code here will be run before anything else
    }

    /**
     * @url GET /$userId
     */
    public function fetchUsers($userId) {
        if ( $userId === null ) {
            $response = $this->model->fetchUsers();
        } else {
            $response = $this->model->fetchUser( $userId );
        }

        return $this->respondWith( $response );
    }

}
```

#### A method inside Controller
Documentation will be added later...

#### Method annotations
Methods inside Controllers can be extended by adding annotations to their comments.

```php
// ./Resources/Users/Users.php
<?php
namespace Resources\Users;

use DAwaa\Core\Controller;

class Users extends Controller {

    /**
     * @url GET /$userId
     */
    public function fetchUsers($userId) {
        if ( $userId === null ) {
            $response = $this->model->fetchUsers();
        } else {
            $response = $this->model->fetchUser( $userId );
        }

        return $this->respondWith( $response );
    }

}
```
Since we are passing multiple entities of a User, we can consider that to be a "Collection". A collection in the JSON response will end up in a meta key called "items" which it's value is an array, that holds all entities retrieved.

Here's a retracted example:
```json
{
    "meta": {
        "href": "http:\/\/api.local\/v1\/users\/"
    },
    "status": 200,
    "message": null,
    "errors": [],
    "error": null,
    "items": [
        {
            "meta": {
                "href": "http:\/\/api.local\/v1\/users\/1"
            },
            "id": "1",
            "username": "firstUser",
            "password": "asecretpassword",
            "email": "first.user@example.com"
        },
        {
            "meta": {
                "href": "http:\/\/api.local\/v1\/users\/2"
            },
            "id": "2",
            "username": "anotheruser",
            "password": "amuchmoremysteriouspassword",
            "email": "second.user@example.com"
        }
    ]
}
```

Now how to extend this and what does that even mean?
I will add one more annotation to the function `fetchUsers()`.

```php
// ./Resources/Users/Users.php
<?php
namespace Resources\Users;

use DAwaa\Core\Controller;

class Users extends Controller {

    /**
     * @url GET /
     * @expand addExtra addExtraUnnecessaryInformation
     */
    public function fetchUsers() {
        $users = $this->model->fetchUsers();
        return $this->respondWith( $users );
    }

    public function addExtraUnnecessaryInformation() {
        $unnecessaryInformation = array(
            'isANerd' => true
        );

        return $unnecessaryInformation;
    }
}
```

This will change our JSON response to look like this:
```json
{
    "meta": {
        "href": "http:\/\/api.local\/v1\/users\/"
    },
    "status": 200,
    "message": null,
    "errors": [],
    "error": null,
    "items": [
        {
            "meta": {
                "href": "http:\/\/api.local\/v1\/users\/1"
            },
            "id": "1",
            "username": "firstUser",
            "password": "asecretpassword",
            "email": "first.user@example.com",
            "addExtra": {
                "href": "http:\/\/api.local\/v1\/users\/?expand=addExtra"
            }
        },
        {
            "meta": {
                "href": "http:\/\/api.local\/v1\/users\/2"
            },
            "id": "2",
            "username": "anotheruser",
            "password": "amuchmoremysteriouspassword",
            "email": "second.user@example.com",
            "addExtra": {
                "href": "http:\/\/api.local\/v1\/users\/?expand=addExtra"
            }
        }
    ]
}
```

Noticed another meta key was added to the bottom of our response? With an URL attached to it? The URL tells us how we can expand this annotation to give us more information. Let's try it out:

```
http://api.local/v1/users/?expand=addExtra
```

And the result looks like

```json
{
    "meta": {
        "href": "http:\/\/api.local\/v1\/users\/"
    },
    "status": 200,
    "message": null,
    "errors": [],
    "error": null,
    "items": [
        {
            "meta": {
                "href": "http:\/\/api.local\/v1\/users\/1"
            },
            "id": "1",
            "username": "firstUser",
            "password": "asecretpassword",
            "email": "first.user@example.com",
            "addExtra": {
                "href": "http:\/\/api.local\/v1\/users\/?expand=addExtra",
                "isANerd": true
            }
        },
        {
            "meta": {
                "href": "http:\/\/api.local\/v1\/users\/2"
            },
            "id": "2",
            "username": "anotheruser",
            "password": "amuchmoremysteriouspassword",
            "email": "second.user@example.com",
            "addExtra": {
                "href": "http:\/\/api.local\/v1\/users\/?expand=addExtra",
                "isANerd": true
            }
        }
    ]
}
```

Cool so what we returned as an array was added to our response by also adding the query parameter `?expand=addExtra`. See how does could allow us to do some cool things with it?

We will add one more that brings could bring us some more valuable information instead.
This time we will add two more annotations to that very same function, `fetchUsers()`.

```php
// ./Resources/Users/Users.php
<?php
namespace Resources\Users;

use DAwaa\Core\Controller;

class Users extends Controller {

    /**
     * @url GET /
     * @expand addExtra addExtraUnnecessaryInformation
     * @expand friends getUsersFriends
     * @unique id
     */
    public function fetchUsers() {
        $users = $this->model->fetchUsers();
        return $this->respondWith( $users );
    }

    public function addExtraUnnecessaryInformation() {
        $unnecessaryInformation = array(
            'isANerd' => true
        );

        return $unnecessaryInformation;
    }
}
```

Two more annotations were added. First another `@expand`annotation. Then a new one, `@unique id`.. this annotation will look at the response returned from the back-end and find that unique key and pass its value to the function `getUsersFriends()`. So it's always up to you to make sure that there is a unique value between the returned items of a collection so that you can differentiate them.

If we would try to hit the url again we would expect to see another new meta key called "friends" like we did with "addExtra", right?

Try it.

You won't see anything because that function doesn't exist yet in the Controller, we would have to create it at least for it to show.
So let's do that and also implement some more psuedo code!

```php
// ./Resources/Users/Users.php
<?php
namespace Resources\Users;

use DAwaa\Core\Controller;

class Users extends Controller {

    /**
     * @url GET /
     * @expand addExtra addExtraUnnecessaryInformation
     * @expand friends getUsersFriends
     * @unique id
     */
    public function fetchUsers() {
        $users = $this->model->fetchUsers();
        return $this->respondWith( $users );
    }

    public function getUsersFriends($userId) {
        $response = array();
        $friends  = $this->model->getFriendsById( $userId );

        // Beautify the response somewhat.
        foreach ( $friends as $friend ) {
            $friendId = $friend[ 'id' ];
            $response[ $friendId ] = $friend;
        }

        return $response;
    }

    public function addExtraUnnecessaryInformation() {
        $unnecessaryInformation = array(
            'isANerd' => true
        );

        return $unnecessaryInformation;
    }
}
```

Good! Now how does the response look like now?

```json
{
    "meta": {
        "href": "http:\/\/api.local\/v1\/users\/"
    },
    "status": 200,
    "message": null,
    "errors": [],
    "error": null,
    "items": [
        {
            "meta": {
                "href": "http:\/\/api.local\/v1\/users\/1"
            },
            "id": "1",
            "username": "firstUser",
            "password": "asecretpassword",
            "email": "first.user@example.com",
            "addExtra": {
                "href": "http:\/\/api.local\/v1\/users\/?expand=addExtra"
            },
            "friends": {
                "href": "http:\/\/api.local\/v1\/users\/?expand=friends"
            }
        },
        {
            "meta": {
                "href": "http:\/\/api.local\/v1\/users\/2"
            },
            "id": "2",
            "username": "anotheruser",
            "password": "amuchmoremysteriouspassword",
            "email": "second.user@example.com",
            "addExtra": {
                "href": "http:\/\/api.local\/v1\/users\/?expand=addExtra"
            },
            "friends": {
                "href": "http:\/\/api.local\/v1\/users\/?expand=friends"
            }
        }
    ]
}
```

Coooooool.... and I guess you didn't know you could expand two at the same time? Well you can!

Try hitting the url like this `http://api.local/v1/users/?expand=addExtra,friends`, order doesn't matter fyi.

Result:

```json
{
    "meta": {
        "href": "http:\/\/api.local\/v1\/users\/"
    },
    "status": 200,
    "message": null,
    "errors": [],
    "error": null,
    "items": [
        {
            "meta": {
                "href": "http:\/\/api.local\/v1\/users\/1"
            },
            "id": "1",
            "username": "firstUser",
            "password": "asecretpassword",
            "email": "first.user@example.com",
            "addExtra": {
                "href": "http:\/\/api.local\/v1\/users\/?expand=addExtra",
                "isANerd": true
            },
            "friends": {
                "href": "http:\/\/api.local\/v1\/users\/?expand=friends",
                "2": {
                    "id": 2,
                    "username": "anotheruser"
                },
                "3": {
                    "id": 3,
                    "username": "aThirdUser"
                }
            }
        },
        {
            "meta": {
                "href": "http:\/\/api.local\/v1\/users\/2"
            },
            "id": "2",
            "username": "anotheruser",
            "password": "amuchmoremysteriouspassword",
            "email": "second.user@example.com",
            "addExtra": {
                "href": "http:\/\/api.local\/v1\/users\/?expand=addExtra",
                "isANerd": true
            },
            "friends": {
                "href": "http:\/\/api.local\/v1\/users\/?expand=friends",
                "1": {
                    "id": 1,
                    "username": "firstUser"
                }
            }
        }
    ]
}
```

#### Response structure-checking
Documentation will be added later...

## Looking at a Model
I'm sure you've noticed earlier from reading the documentation that I've used `$this->model->SOME_METHOD()` a few times. So how does it all work?

Again I will use the resource Users as our example here.

By default the library will look for these two files, under the Model/ directory of that current Resource/
* _./Resources/Model/Users.php_
* _./Resources/Model/UsersModel.php_

But if you'd like to point your controller to use another Model, then you could add the following annotation `@model` to your Controller / Resource class. Which is _./Resources/Users/Users.php_.

A retracted version of the Controller..
```php
// ./Resources/Users/Users.php
<?php
namespace Resources\Users;

use DAwaa\Core\Controller;

/**
 * @model \Provide\Full\Namespace\To\Other\Class
 */
class Users extends Controller {
    // .. code
}
```

**An actual look inside a Model**
```php
// ./Resources/Users/Model/UsersModel.php
<?php
namespace Resources\Users\Model;

use DAwaa\Core\Model;

class UsersModel extends Model {

    public function fetchUsers() {
        // You can either directly write your queries like this
        $users = $this->query( 'select * from users' )->result_array();

        // Or make use of static variables inside classes to allow for some
        // sort of namespaceing to easier structure bigger applications (imho..)
        $sql   = Statements\Select::$all;
        $users = $this->query( $sql )->result_array();

        return $users;
    }

}
```

I'll show you how the directory structure would look to achieve above namespaceing, which I fancy quite a lot..

```bash
jacwright-restserver-extended/Resources/
`- Users/
   |- Model/
   |  |- Statements/          # example structure and files..
   |  |  |- Select.php
   |  |  |- Delete.php
   |  |  `- Update.php
   |  `- UsersModel.php
   `- Users.php
```

Also let's see how the _Statements/Select.php_ would look like..

```php
<?php
namespace Resources\Users\Model\Statements;

class Select {
    public static $all =
        "
        select
            *
        from
            users
        ";
}
```

So once a Model has been found by the library it will add it to the context of our Controller under `$this->model`.. So all public methods defined in your Model will be available under the earlier mentioned property of the Controller.

#### The init() method
Sometimes we might want to set up a few things before the code is actually run.. For this you can use the `#init()` method within the Controller. Like so:

```php
// ./Resources/Users/Model/UsersModel.php
<?php
namespace Resources\Users\Model;

use DAwaa\Core\Model;

class UsersModel extends Model {

    public function init() {
        // .. code here will be run before anything else
    }

    public function fetchUsers() {
        // You can either directly write your queries like this
        $users = $this->query( 'select * from users' )->result_array();

        // Or make use of static variables inside classes to allow for some
        // sort of namespaceing to easier structure bigger applications (imho..)
        $sql   = Statements\Select::$all;
        $users = $this->query( $sql )->result_array();

        return $users;
    }

}
```

## Database
Within both a Controller and a Model you'll have database helpers available to you under `$this` context. And the actual instance of the database handler could be retrieved like this  `$this->dbh`.

#### Helpers
- Adapter#**query**($query_string, **array** $bindings = null)
    An easier way for us to query the database, this also allows us to bind arguments in our query. The bindings must be an array passed.
    Returns instance of the Database class
- Adapter#**row**()
    Returns single object
- Adapter#**row_array**()
    Returns single array
- Adapter#**row_result**()
    Returns the value of the result
- Adapter#**result**()
    Returns an array of objects with our results
- Adapter#**result_array**()
    Returns a multidimensional array with our results

#### Example usage
For the examples I will be querying and var_dump() the results for each helper method.

**Note** I won't be including all properties, only a few, this is merely to show what the different results look like.

**query() combined with row() and bindings**
```php
$userId = '1234';
$sql    = 'select * from users where id = :0';
$user   = $this->query( $sql, [ $userId ] )->row();

var_dump( $user );
=>
// object(stdClass) {
//     ["id"] => int(1234)
//     ["username"] => string(4) "Test"
// }
var_dump ( $user->username );
=>
// Test

```

**query() combined with row_array() and bindings**
```php
$userId = '1234';
$sql    = 'select * from users where id = :0';
$user   = $this->query( $sql, [ $userId ] )->row_array();

var_dump( $user );
=>
// array(2) {
//     ["id"] => int(1234)
//     ["username"] => string(4) "Test"
// }
var_dump ( $user["username"] );
=>
// Test
```

**query() combined with row_result() and bindings**
```php
$userId   = '1234';
$sql      = 'select username from users where id = :0';
$username = $this->query( $sql, [ $userId ] )->row_result();

var_dump( $username );
=>
// Test
```

**query() combined with result()**
```php
$sql   = 'select * from users limit 2';
$users = $this->query( $sql )->result();

var_dump( $users );
=>
// array(2) {
//    [0] => object(stdClass) {
//        ["id"] => string(4) "1234"
//        ["username"] => string(4) "Test"
//    }
//    [1] => object(stdClass) {
//        ["id"] => string(5) "12345"
//        ["username"] => string(5) "Jesus"
//    }
// }

var_dump( $users[0]->id );
=>
// 1234
```

**query() combined with result_array()**
```php
$sql = 'select * from users limit 2';
$users = $this->query( $sql )->result_array();

var_dump( $users );
=>
// array(2) {
//    [0] => array(2) {
//        ["id"] => string(4) "1234"
//        ["username"] => string(4) "Test"
//    }
//    [1] => array(2) {
//        ["id"] => string(5) "12345"
//        ["username"] => string(5) "Jesus"
//    }
// }

var_dump( $users[0]["id"] );
=>
// 1234
```
