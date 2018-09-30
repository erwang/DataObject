<?php
declare(strict_types=1);


use ErwanG\Tests\Author;
use \ErwanG\Tests\Book;

final class DataObjectOld extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        \ErwanG\DataObject::setPDO(['dbname'=>'test_orm','host'=>'127.0.0.1','username'=>'root','password'=>'123456']);
        Author::create(['firstname'=>'Victor','lastname'=>'HUGO'])->store();
        Book::create(['title'=>'Les Misérables'])->store();
    }

    protected function tearDown()
    {
        Author::truncate(false);
        Book::truncate(false);
    }

    public function testFindFirst(): void
    {
        $victorHugo = Author::findFirst(['firstname'=>'Victor','lastname'=>'HUGO']);
        $this->assertInstanceOf(\ErwanG\Tests\Author::class,$victorHugo);
        $lesMiserables = Book::findFirst(['title'=>'Les Misérables']);
        $this->assertInstanceOf(\ErwanG\Tests\Book::class,$lesMiserables);
    }


}