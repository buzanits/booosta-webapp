# Webapp module for the Booosta PHP Framework

This module provides the webapp funtionallity for the Booosta PHP Framework. This is the "heart" of the
Booosta Framework, which in mainly designed for writing web applications.

Booosta allows to develop PHP web applications quick. It is mainly designed for small web applications.
It does not provide a strict MVC distinction. Although the MVC concepts influence the framework. Templates,
data objects can be seen as the Vs and Ms of MVC.

Up to version 3 Booosta was available at Sourceforge: https://sourceforge.net/projects/booosta/ From version
4 on it resides on Github and is available from Packagist under booosta/booosta .

## Installation

For installation instructions see https://github.com/buzanits/booosta-installer/README.md

## Create your application

Unlike other frameworks, where you write code and the framework creates the database for you, Booosta works
the opposite way. You create the database with all its tables and Booosta writes the code for you. Of course
it cannot anticipate all the strange things you want to do within your application. So it only creates the
code for the basic CRUD functionallity (**C**reate, **R**ead, **U**pdate, **D**elete).

So if you have a database table with data and you only want to do these operations on this data, the framework
does literally all the programming work for you!

### Create the database

The data should have some special structure. Future releases of Booosta will support several DBMS, but here
we only show how to work with _mysql_ or _mariadb_. A nice and easy way to manipulate mysql or mariadb databases
is [phpMyAdmin](https://www.phpmyadmin.net).

* Using the InnoDB engine for the data tables is recommended
* Your data tables should have a unique identifier. It's recommended to call it **id**
  This should be an integer, auto increment and primary key
* If you have fields that hold the primary key of of foreign table, you should define a foreign key on that field
* If you want to work with mysql views, you should add the comment "PK" to the primary key field.
  This is because Booosta needs this to be sure that it is the primary key, what it cannot find out in a view.
  
Here is an example of a table holding songs on a CD made with phpMyAdmin:

![table-song](https://user-images.githubusercontent.com/9774538/168874874-579af4a9-15fb-49c5-8d6c-d6581a19a77c.png)

![table-song1](https://user-images.githubusercontent.com/9774538/168875028-0e397abb-b863-4fb8-85d2-8d600581f075.png)

Notice the foreign key in the second screen shot.



