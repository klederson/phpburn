# phpBurn 

[![Build Status](https://travis-ci.org/PhpBURN/phpburn.png?branch=master)](https://travis-ci.org/PhpBURN/phpburn)

Developed by the manteiner Klederson Bueno, [phpBurn][phpburn] is a FRAMEWORK for [PHP][phpnet] usage, initialy born as an ORM like [hibernate][hn] and [Nhibernate][nhb].

It allows you to create more and faster using [OO][woo] concepts and patterns with a log of time gain. Using resources for MVC (Model View Controller) [phpBurn][phpburn] will make your programming more easy, quick and fun.

Now you can found this project hosted both GitHub and SourceForge at git version control.

# Instalation
To install PhpBURN is quite easy first you need to fork or clone PhpBURN code from [PhpBURN Official Repository][PhpBURN Official Repository] and than you need to use it into your include path by doing in one of this ways:

You can also go to [PhpBURN Official Repository][PhpBURN Official Repository] and click in DOWNLOAD and choose zip or tar.gz version from that branch.

## OBS:
Please note master is equivalent to dev or in-development version and sometimes something can go wrong using it ( some bug or something ) we strongly recommed you to use the stable version in stable branch.

## php.ini

Should look something like this:
    include_path = ".:/php/includes:/Volumes/projects/includes/phpBurn/" 

or (Windows):
    include_path = ".;c:\php\includes;c:\Projects\includes\phpBurn"

## ini_set()
    ini_set('include_path',get_include_path().":/Volumes/projects/includes/phpBurn");

or (Windows):
    ini_set('include_path',get_include_path().":C:\Projects\includes\phpBurn");

## RAW ( NOT RECOMMENDED )
    require_once("/Volumes/projects/includes/phpBurn/app/phpBurn.php");

# Generate a application structure

Just enter in your shell and type:

    php YOURPHPBURNFOLDER/app/generator.generate.php

And then follow the steps and "voilà" now you have a brand new site working ( if you go to your browser and type http://localhost/mynewprojectfolder ) you sould see a wellcome page.

If you want to use only the **ORM** you should include and start your phpBurn ( see below )

# Usage (FULL - index.php - ALREADY INSTALLED when generator.php is used)
    <?php
        require_once("app/phpBurn.php");
    ?>
    <?php
    ob_start();
    ################################
    # Hooks
    ################################
    define('SYS_USE_FIREPHP',true,true);

    ################################
    # Including required files
    ################################
    require_once('app/phpBurn.php');
    require_once('config.php');

    ################################
    # Starting application
    ################################
    PhpBURN::startApplication();

    ################################
    # Sending a End of File
    ################################
    PhpBURN_Message::output('[!EOF!]');
    ?>

# Usage (ORM ONLY)
    ################################
    # Hooks
    ################################
    define('SYS_USE_FIREPHP',false,true);
    
    ################################
    # Including required files
    ################################
    require_once('app/phpBurn.php');
    require_once('config.php');
    
    ################################
    # Start PhpBURN needed resources
    ################################
    PhpBURN::enableAutoload();

# Documentation

* [General][documentation]
* [ORM/MODELS][ORMdocumentation]
* [VIEWS][VIEWdocumentation]
* [CONTROLLERS][CONTROLLERdocumentation]

# Official Links:

* [PhpBURN Official Repository][PhpBURN Official Repository]
* [PhpBURN Website][phpburn]

[phpburn]: http://www.phpburn.com/
[documentation]: http://www.phpburn.com/documentation/
[ORMdocumentation]: http://www.phpburn.com/documentation/orm
[CONTROLLERdocumentation]: http://www.phpburn.com/documentation/controller
[VIEWdocumentation]: http://www.phpburn.com/documentation/view
[phpnet]: http://www.php.net/
[PhpBURN Official Repository]: http://github.com/PhpBURN/phpburn
[hn]: http://www.hibernate.org/
[nhb]: http://www.hibernate.org/343.html
[woo]: http://en.wikipedia.org/wiki/Object_oriented
