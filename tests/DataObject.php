<?php

namespace ErwanG\Tests;

use PHPUnit\Framework\TestCase;

final class DataObject extends TestCase
{

    protected function setUp()
    {
        \ErwanG\DataObject::setPDO(['dbname'=>'test_orm','host'=>'127.0.0.1','username'=>'root','password'=>'123456']);

        Author_Book::truncate(false);
        Book::truncate(false);
        Author::truncate(false);

        Editor::Drop(false);

        \ErwanG\DataObject::beginTransaction();
        \ErwanG\DataObject::exec('CREATE TABLE `Editor` (
        `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
        `name` varchar(250) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        //\ErwanG\DataObject::exec('ALTER TABLE `Editor` ADD PRIMARY KEY (`id`);');
        \ErwanG\DataObject::commit();

        $victorHugo = Author::create(['firstname'=>'Victor','lastname'=>'HUGO'])->store();
        $lesMiserables = Book::create(['title'=>'Les Misérables'])->store();
        $lesMiserables->author=[$victorHugo];
        $lesMiserables->store();
    }

    /*
    protected function tearDown()
    {
    }
    */

    /**
     * @expectedException Exception
     */
    public function testExec()
    {
       $this->expectException(Book::query('select * from badTable where title=?', ['Les Misérables']));
    }

    /*
    public function testWhere()
    {

    }
    */

    /*
    public function testOrder()
    {

    }
    */

    /*
    public function testBeginTransaction()
    {

    }
    */

    /*
    public function testTruncate()
    {

    }
    */

    /*
    public function testTableExists()
    {

    }
    */


    public function test__get()
    {
        $lesMiserables = Book::findFirst(['title'=>'Les Misérables']);
        $victorHugo=Author::findFirst(['firstname'=>'Victor','lastname'=>'Hugo']);
        $lesMiserables->author=[$victorHugo];
        $lesMiserables->store();
        $this->assertCount(1,$victorHugo->book);

        unset($lesMiserables);
        $lesMiserables = Book::findFirst(['title'=>'Les Misérables']);
        $this->assertCount(1,$victorHugo->book);

        $editor = Editor::findFirst(['name'=>'Albert Lacroix et Cie '],null,true);
        $lesMiserables->editor=$editor;
        $lesMiserables->store();

        $this->assertCount(1,$editor->book);


    }


    public function testIsEqualTo()
    {
        $lesMiserables = Book::findFirst(['title'=>'Les Misérables']);
        $lesMiserablesCopy = Book::get($lesMiserables->id);
        $this->assertTrue($lesMiserables->isEqualTo($lesMiserablesCopy));

    }

    /*
    public function testGet()
    {

    }
    */


    public function testCopy()
    {
        $lesMiserables = Book::findFirst(['title'=>'Les Misérables']);
        $count = Book::count();
        $lesMiserables->copy()->store();
        $this->assertEquals($count+1,Book::count());
    }

    public function testDelete()
    {
        $this->assertEmpty(Editor::findAll());
        Editor::create(['name'=>'Albert Lacroix et Cie '])->store();
        $this->assertCount(1,Editor::findAll());
        //$result = $editor->delete();
        //$this->assertEmpty(Editor::findAll());
    }

    /*
    public function testRollback()
    {

    }
    */

    public function testCount()
    {
        $victorHugo=Author::findFirst(['firstname'=>'Victor','lastname'=>'Hugo']);
        $this->assertEquals(1,Author_Book::count('author_id=?', [$victorHugo->id]));
        $this->assertEquals(1,Author::count(['id'=>$victorHugo->id,'birthdate'=>null]));
    }

    /*
    public function testStore()
    {

    }
    */

    /*
    public function testGetColumns()
    {

    }
    */

    /*
    public function testFindAll()
    {

    }
    */

    public function testDrop()
    {
        $this->assertTrue(Editor::tableExists());
        Editor::drop(false);
        $this->assertFalse(Editor::tableExists());
    }

    /*
    public function testDefineId()
    {

    }
    */

    public function testPopulate()
    {
        $charlesBaudelaire = Author::create();
        $data = ['firstname'=>'Charles','lastname'=>'BAUDELAIRE'];
        $charlesBaudelaire->populate($data);
        $this->assertEquals('Charles',$charlesBaudelaire->firstname);
        $this->assertEmpty($charlesBaudelaire->birthdate);

    }

    public function testFromJson()
    {
        $emileZola = Author::create();
        $object = new \stdClass();
        $object->firstname = 'Émile';
        $object->lastname='Zola';
        $emileZola->fromJson(json_encode($object));
        $emileZola->store();
        $this->assertEquals($emileZola->firstname,$object->firstname);
        $this->assertEquals($emileZola->lastname,$object->lastname);
        $this->assertEmpty($emileZola->birthdate);
    }
    
    /*
    public function testSetPDO()
    {

    }
    */

    /*
    public function testQuery()
    {

    }
    */

    /*
    public function testWhereFirst()
    {

    }
    */

    /*
    public function testFindLast()
    {

    }
    */


    /*
    public function testCommit()
    {

    }
    */

    /*
    public function testWhereLast()
    {

    }
    */

    /*
    public function testLimit()
    {

    }
    */

    public function testFindFirst()
    {

        $victorHugo = Author::findFirst(['firstname'=>'Victor','lastname'=>'HUGO']);
        $this->assertInstanceOf(Author::class,$victorHugo);
        $lesMiserables = Book::findFirst(['title'=>'Les Misérables']);
        $this->assertInstanceOf(Book::class,$lesMiserables);
        $this->assertNull(Book::findFirst(['title'=>'Les']));
        $this->assertInstanceOf(Book::class,Book::findLast(['title'=>'Les Misérables']));
    }

    /*
    public function testHasColumn()
    {

    }
    */

    /*
    public function testHasMany()
    {

    }
    */

    /*
    public function testCreate()
    {

    }
    */

    /*
    public function testGetTable()
    {

    }
    */

    /*
    public function testFind()
    {

    }
    */


    public function testGetModel()
    {
        $victorHugo = Author::findFirst(['firstname'=>'Victor','lastname'=>'HUGO']);
        $this->assertEquals('ErwanG\\Tests\\Author',$victorHugo->getModel());

    }
}
