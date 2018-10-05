<?php

include('../vendor/autoload.php');

use ErwanG\Tests\Author;
use ErwanG\Tests\Book;
use ErwanG\Tests\Editor;
use ErwanG\Tests\Author_Book;

\ErwanG\DataObject::setPDO(['dbname' => 'test_orm', 'host' => '127.0.0.1', 'username' => 'root', 'password' => '123456']);
/*
\ErwanG\DataObject::beginTransaction();
\ErwanG\DataObject::exec('
CREATE TABLE `Author` (
  `id` varchar(20) NOT NULL,
  `firstname` varchar(250) DEFAULT NULL,
  `lastname` varchar(250) DEFAULT NULL,
  `birthdate` DATE DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `Author_Book` (
  `id` varchar(20) NOT NULL,
  `book_id` varchar(20) DEFAULT NULL,
  `author_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `book` (
  `id` varchar(20) NOT NULL,
  `title` varchar(250) DEFAULT NULL,
  `editor_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `Editor` (
  `id` varchar(20) NOT NULL,
  `name` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


ALTER TABLE `Author`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `Author_Book`
  ADD PRIMARY KEY (`id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `author_id` (`author_id`);

ALTER TABLE `book`
  ADD PRIMARY KEY (`id`),
  ADD KEY `editor_id` (`editor_id`);

ALTER TABLE `Editor`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `Author_Book`
  ADD CONSTRAINT `Author_Book_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `book` (`id`),
  ADD CONSTRAINT `Author_Book_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `Author` (`id`);

ALTER TABLE `book`
  ADD CONSTRAINT `book_ibfk_1` FOREIGN KEY (`editor_id`) REFERENCES `Editor` (`id`);
');
\ErwanG\DataObject::commit();
*/

//empty tables

Author::truncate(false);
Book::truncate(false);
Editor::truncate(false);
Author_Book::truncate(false);


//create a new Author
$victorHugo = Author::create(['firstname' => 'Victor', 'lastname' => 'HUGO']);
//store this Author in database
$victorHugo->store();
//create a book and store it (chaining)
$lesMiserables = Book::create(['title' => 'Les Misérables'])->store();
//create a Editor
$albertLacroixEditor = Editor::create(['name' => 'Albert Lacroix et Cie '])->store();

//define Les Miserables editor
$lesMiserables->editor = $albertLacroixEditor;
$lesMiserables->store();
//or
$lesMiserables->editor_id = $albertLacroixEditor->id;
$lesMiserables->store();

//one book can be write by more than one author
$lesMiserables->author = [$victorHugo];
$lesMiserables->store();

// find all books from Victor Hugo
$books = $victorHugo->book;
//or
$books = $victorHugo->hasMany(Book::class);

//define birthdate
$victorHugo->birthdate = '1802-02-26';
$victorHugo->store();

//get number of books written by Victor Hugo
$count = Author_Book::count( ['author_id'=>$victorHugo->id]);

$data = ['firstname'=>'Émile','lastname'=>'ZOLA','birthdate'=>'1840-04-02'];
$emileZola=Author::create();
//populate object with associative array
$emileZola->populate($data)->store();

