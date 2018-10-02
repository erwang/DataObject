# ErwanG\DataObject


Class DataObject

    @package ErwanG

Database structure :

- each table must have a primary key named "id" with type varchar(20)

- foreign key must be tableName_id

- associative tables must be TableName1_TableName2 (where TableName1<TableName2 in alphabetic order)

Example:

```code

Book : id, title, editor_id

Author : id, firstName, lastName, birthDate

Author_Book : id, author_id, book_id

Editor : id, name

```

More examples in examples.php;


## __construct


DataObject constructor.


## get


return object with id

```php

$book = Book::get('ABC');

```

    @param $id

    @return mixed|null


## setPDO


must be call to set PDO connection

```

DataObject::setPDO(['dbname'=>'database','host'=>'127.0.0.1','username'=>'root','password'=>'123456']);

```

    @param $params must contain keys dbname, hostn username, password

    @return PDO


## _prepare


    @param $query

    @return \PDOStatement


## query


execute query and return result as DataObject

must be use for SELECT queries only

    @param $query

    @param array $params

    @return array


## exec


execute query and return result

    @param $query

    @param array $params

    @return \PDOStatement


## getTable


return name table

default value is class name

    @return string


## getColumns


return table columns

    @return mixed


## where


return DataObject from where query

    @param $where

    @param array $params

    @param null $order

    @param null $limit

    @return DataObject[]


## whereFirst


return first DataObject from where query

    @param $where

    @param array $params

    @param null $order

    @return DataObject|null


## whereLast


return last DataObject from where query

    @param $where

    @param array $params

    @param null $order

    @return DataObject|null


## find


return DataObject from associative array

```

Book:find(['type'=>'thriller");

```

    @param $params

    @param null $order

    @param null $limit

    @return array


## findFirst


return first DataObject from associative array

    @param $params

    @param $order

    @param bool $create if true, create object if not find

    @return DataObject|null


## findLast


return last DataObject from associative array

    @param $params

    @param $order

    @return DataObject|null


## findAll


return all object

    @param null $order

    @param null $limit

    @return array


## __get


    @param $name

    @return array

    @throws \Exception


## create


create a DataObject from associative array

```

$author = Author::create(['firstname'=>'Victor','lastname'=>'HUGO']);

```

    @param $params

    @return DataObject


## order


Set string ORDER BY...

    @param $order

    @return string


## limit


Set string LIMIT...

    @param $limit

    @return string


## count


return count from where query

    @param null $where

    @param $params

    @return int


## isInDatabase


return true if object is in database

    @return bool


## defineId


return an uniq id

    @return bool|string


## hasMany


return object with relation

```

$books = $victorHugo->hasMany(Book::class);

```

    @param string $class

    @return array

    @throws \Exception


## _getNamespace


    @return string


## _getClass


    @return string


## getModel


    @param null $separator

    @return mixed|string


## _getClassBetween


    @param $class

    @param bool $namespace

    @return string


## store


Store object in database

    @return $this

    @throws \Exception


## delete


delete object in database

    @return bool


## tableExists


return true if table exists

    @return boolean


## hasColumn


test column existence

    @param string $column column name

    @return boolean


## drop


drop table

    @param bool $foreignKeyCheck

    @return bool


## truncate


truncate table

    @example CLASS::truncate(false);

    @param bool $foreignKeyCheck

    @return bool


## populate


populate object with associative array

    @param $params

    @return $this


## isEqualTo


    @param Object $object

    @return bool


## copy


copy object without id param

    @return DataObject


## beginTransaction


begin transaction if none has been started


## commit


commit transaction


## rollback


roll back


