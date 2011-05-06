SwiftRiver Sweeper v0.3.0
==============
 
Sweeper is an intelligent media curation tool with filters for managing real-time feeds of information.  You can find out more at - http://swiftly.org

Find us on Github - [http://github.com/ushahidi/Swiftriver](http://github.com/ushahidi/Swiftriver)

**NOTE**: This is still a beta software product. We recommend not using it in critical scenarios without further consultation. Contact us at support@swiftly.org with additional questions or feedback.

RELEASE NOTES
-------------
This is the development branch of Sweeper for the upcoming V0.3 release.

Some of the jazzy new features you can expect from this upcoming release are:

* Tag-based navigation of content
* Content grouping
* Dashboard Analytics


DEVELOPER NOTES
---------------

If you are a developer interested in contributing to Sweeper then there are a few things to note
before you jump in and start coding. First be sure to read this overview - http://goo.gl/mlPff

The **master** branch will always be the latest stable release of the app so unless you
like living life dangerously this is the best place to start:

    $ git clone git://github.com/ushahidi/Sweeper.git

With the app code checked out you still need to perform the following steps before you can
start local development:

    $ cd Sweeper
    $ git submodule init
    $ git submodule update

Why do we do this? Well, Sweeper is just one app that runs on the [Swiftriver](http://github.com/ushahidi/Swiftriver)
framework. To allow this to all happen, the framework has its own repository and is brought into
this project using the [**git-submodule**](http://chrisjean.com/2009/04/20/git-submodules-adding-using-removing-and-updating/)
facility.

Once you have done this, you will have all the code required to run and change Sweeper!


SYSTEM REQUIREMENTS
-------------------

* Apache 1.3 or greater
* PHP 5.3 or higher
* PHP Pear Extensions
* MySQL 4.0 or higher
	
Further details on this release can be found at - [http://wiki.ushahidi.com](http://wiki.ushahidi.com)

SUPPORTED BROWSERS
------------------
* FireFox
* Safari
* IE7+
* Chrome

DOCUMENTATION
-------------

Documentation - [http://wiki.ushahidi.com](http://wiki.ushahidi.com)

Installation - [http://wiki.ushahidi.com/doku.php?id=install_s](http://wiki.ushahidi.com/doku.php?id=install_s)