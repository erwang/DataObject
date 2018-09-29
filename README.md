# DataObject
PHP ORM in one single file

# KORM
ORM in PHP


## Setup

The Connection class setup must be call first.

``` php
$connection = \KORM\Connection::setup('name','pdo_dsn', 'username', 'password');
```

A connection to a mysql database :

``` php
$connection = \KORM\Connection::setup('connectionName','mysql:host=localhost;dbname=database', 'username', 'password');
```

with options :

``` php
$connection = \KORM\Connection::setup('connectionName','mysql:host=localhost;dbname=database', 'username', 'password', array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
```

## Create a class

Each table in database requires a class with the same name :

``` php
class Table extends \KORM\Object{
}
```

_name class is converted to lower case_
For example, to store books :

``` php
class Book extends \KORM\Object{
}
```

The `Book` objects will be store in `book` table

## Define connection
``` php
Book::setConnection($connection);
``` 

## Get a row from id

``` php
$book = new Book($id);
```

will load in `$book` all the data in table `book` with id=1

## Create a row

``` php
$book = new Book();
```

## Store an object

``` php
$book = new Book($id);
$book->title='Les Misérables';
$book->store();
```

## Delete an object

``` php
$book = new Book($id);
$book->delete();
```

## Find objects

### Find one Object

``` php
$book = Book::findOne(['title'=>'Les Misérables']);
```

This return one Book (the first found)

### Find multiple Objects

``` php
$authors = Author::find(['nationality'=>'French']);
```

This will return an array with all french authors

If you need complex where clause, you can use where

``` php
$books = Book::where('pages>:nbpages',['nbpages'=>100]);
```
This will return an array with books with more than 100 pages

If you want more complex queries :

``` php
$books = Book::query('select book.* from book,author where author.id=:author_id and pages>:nbpages and author.id=book.author_id',['nbpages'=>100,'author_id'=>1]);
```
This will return an array with books with more than 100 pages from author with id 1

``` php
$books = Book::getAll();
```
This will return all books in table


## Relations

### One to many

``` php
//create a book
$lesMiserables = new Book();
$lesMiserables->title='Les Misérables';
$lesMiserables->store();

//create an author
$hugo=new Author();
$hugo->name='Victor Hugo';
$hugo->store();

//create a relation
$lesMiserables->author=$hugo;
$lesMiserables->store();

//get the book
$book = new Book($lesMiserables->id);
$author = $book->author; //return the object Author from table author
```

### many to many

``` php
//create tags
$tag1=new Tag();
$tag1->text='french';
$tag1->store();

$tag2=new Tag();
$tag2->text='Roman';
$tag2->store();
$lesMiserables->tag=[$tag1,$tag2];
$lesMiserables->store();

//find book from many
$booksWithFrenchTag = $tag1->book;
```

## Count

``` php
//get the number of books
Book::count();

//get the number of books from an author
Book::count(['author_id'=>$author->id]);
```

## Populate an object

``` php
//get data from an array
$post=['firstname'=>'Marcel','lastname'=>'Proust'];
//create a new author
$author=new Author();
$author->populate($post);
$author->store();
```

## Truncate table

``` php
//with foreign key check
Author::truncate();
//without foreign key check
Author::truncate(false);
```

## Drop table

``` php
//with foreign key check
Author::drop();
//without foreign key check
Author::drop(false);
```

