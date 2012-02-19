Class Central
=============

Stanford has started offering a bunch of free online courses starting October 2011. Initially 3 courses were offered. But starting in 2012 more than a dozen courses have been announced. So decided to build a place to summarise all these courses.

[Class Central](http://www.class-central.com) was built using

* [Symfony 2](http://www.symfony.com)
* [Doctrine](http://www.doctrine-project.org/)
* [Twitter Bootstrap](http://twitter.github.com/bootstrap/)

## Extras

The [extras](https://github.com/dhawalhshah/class-central/tree/master/extras) folder in the repository contains the complete dump of the database as well as the schema. Everything you see on the site can be found in the dump.

## Installation

The instructions below will you get a copy of Class Central running on your local machine.

### Preparation

You need to have a Apache/MySQL/Php environment setup. Easiest way to do that is use [XAMPP](http://www.apachefriends.org/en/xampp.html).

### Steps

1. Clone the repository into the www folder. A folder named class-central should be created. 
2. Import the [extras/cc_db.sql](https://github.com/dhawalhshah/class-central/tree/master/extras/cc_db.sql) file into a MySQL database. 
3. Inside [app/config](https://github.com/dhawalhshah/class-central/tree/master/app/config) rename parameters.template.ini to pararameters.ini
4. Set the following config variables in parameters.ini: <code>database_name, database_user, database_password</code>
5. Download vendors from the command line by executing the following command:
  <code>php bin/vendors install</code>
6. Visit <code>http://localhost/class-central/web/app_dev.php/</code> to view the dev version of class-central homepage. Replace app_dev.php with app.php to see the production version.

