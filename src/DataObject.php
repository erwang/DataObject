<?php

namespace ErwanG;

use PDO;

/**
 * Class DataObject
 * @package ErwanG
 * Database structure :
 * - each table must have a primary key named "id" with type varchar(20)
 * - foreign key must be tableName_id
 * - associative tables must be TableName1_TableName2 (where TableName1<TableName2 in alphabetic order)
 * Example:
 * ```code
 * Book : id, title, editor_id
 * Author : id, firstName, lastName, birthDate
 * Author_Book : id, author_id, book_id
 * Editor : id, name
 * ```
 * More examples in examples.php;
 */
class DataObject
{
    private static $_pdo;
    public $id;
    protected static $_autoincrement=false;
    protected static $_classSeparator='_';


    /**
     * DataObject constructor.
     */
    protected function __construct()
    {
    }

    /**
     * set separator between linked class
     * default value is "_"
     * @param $separator
     */
    public static function setSeparator($separator)
    {
        self::$_classSeparator=$separator;
    }


    /**
     * return object with id
     * ```php
     * $book = Book::get('ABC');
     * ```
     * @param $id
     * @return mixed|null
     */
    public static function get($id)
    {
		if(null==$id){
			return null;
		}
        $class = get_called_class();
        $query = 'SELECT * FROM `' . $class::getTable() . '` WHERE id' . ' = ?';
        $objects = self::query($query, [$id]);
        return isset($objects[0]) ? $objects[0] : null;
    }

    /**
     * @return PDO
     */
    private static function _getPDO()
    {
        return self::$_pdo;
    }

    /**
     * must be call to set PDO connection
     * ```
     * DataObject::setPDO(['dbname'=>'database','host'=>'127.0.0.1','username'=>'root','password'=>'123456']);
     * ```
     * @param $params must contain keys dbname, hostn username, password
     * @return PDO
     */
    public static function setPDO($params)
    {
        try {
            self::$_pdo = new PDO('mysql:dbname=' . $params['dbname'] . ';host=' . $params['host'], $params['username'], $params['password']);
            self::$_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }

        self::$_pdo->exec('SET NAMES \'utf8\'');
        return self::$_pdo;
    }

    /**
     * @param $query
     * @return \PDOStatement
     */
    protected static function _prepare($query)
    {
        static $queries = [];
        if (!isset($queries[$query])) {
            $queries[$query] = self::_getPdo()->prepare($query);
        }
        return $queries[$query];
    }

    /**
     * execute query and return result as DataObject
     * must be use for SELECT queries only
     * @param $query
     * @param array $params
     * @return array
     */
    public static function query($query, $params = [])
    {
        $stmt = self::_prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
    }

    /**
     * execute query and return result
     * @param $query
     * @param array $params
     * @return \PDOStatement
     */
    public static function exec($query, $params = [])
    {
        $stmt = self::_prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * return name table
     * default value is class name
     * @return string
     */
    public static function getTable()
    {
        $class = get_called_class();
        if (isset($class::$_table)) {
            return $class::$_table;
        }
        $array = explode('\\', $class);
        return end($array);
    }

    /**
     * return table columns
     * @return mixed
     */
    public static function getColumns()
    {
        $table = self::getTable();
        $statement = 'SHOW COLUMNS FROM `' . $table . '`';
        try {
            $q = self::_getPdo()->prepare($statement);
            $q->execute();
            return $q->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            print($statement);
            exit($e->getMessage());
        }
    }

    /**
     * return DataObject from where query
     * @param $where
     * @param array $params
     * @param null $order
     * @param null $limit
     * @return DataObject[]
     */
    public static function where($where, $params = [], $order = null, $limit = null)
    {
        if ($where === '') {
            $query = 'SELECT * FROM `' . self::getTable() . '` ' . self::order($order) . self::limit($limit);
        } else {
            $query = 'SELECT * FROM `' . self::getTable() . '` WHERE ' . $where . self::order($order) . self::limit($limit);
        }
        return self::query($query, $params);
    }

    /**
     * return first DataObject from where query
     * @param $where
     * @param array $params
     * @param null $order
     * @return DataObject|null
     */
    public static function whereFirst($where, $params = [], $order = null)
    {
        $result = self::where($where, $params, $order, '0,1');
        return isset($result[0]) ? $result[0] : null;
    }

    /**
     * return last DataObject from where query
     * @param $where
     * @param array $params
     * @param null $order
     * @return DataObject|null
     */
    public static function whereLast($where, $params = [], $order = null)
    {
        $result = self::where($where, $params, $order, '0,1');
        return count($result) > 0 ? end($result) : null;
    }

    private static function _createWhereFromParams($params,&$values)
    {
        $where = [];
        $p = [];
        foreach ($params as $k => $v) {
            if ($v === null) {
                $where[] = '`' . $k . '` is null';
            } else {
                $where[] = '`' . $k . '`=?';
                $p[] = $v;
            }
        }
        $values = $p;
        return implode(' and ', $where);

    }

    /**
     * return DataObject from associative array
     * ```
     * Book:find(['type'=>'thriller");
     * ```
     * @param $params
     * @param null $order
     * @param null $limit
     * @return array
     */
    public static function find($params, $order = null, $limit = null)
    {
        return self::where(self::_createWhereFromParams($params,$p), $p, $order, $limit);
    }

    /**
     * return first DataObject from associative array
     * @param $params
     * @param $order
     * @param bool $create if true, create object if not find
     * @return DataObject|null
     */
    public static function findFirst($params, $order = null, $create = false)
    {
        $result = self::find($params, $order, '0,1');
        if (isset($result[0])) {
            return $result[0];
        }
        if ($create) {
            return self::create($params);
        }
        return null;
    }

    /**
     * return last DataObject from associative array
     * @param $params
     * @param $order
     * @return DataObject|null
     */
    public function findLast($params, $order=null)
    {
        $result = self::find($params, $order);
        return count($result) > 0 ? end($result) : null;
    }

    /**
     * return all object
     * @param null $order
     * @param null $limit
     * @return array
     */
    public static function findAll($order = null, $limit = null)
    {
        return self::query('SELECT * FROM `' . self::getTable() . '`' . self::order($order) . self::limit($limit));
    }

    /**
     * @param $name
     * @return array
     * @throws \Exception
     */
    public function __get($name)
    {
        $class = '\\'.$this->_getNamespace() . '\\' . ucfirst($name);
        if (class_exists($class)) {
            $key = $name . '_id';
            if (self::hasColumn($key)) {
                return $class::get($this->$key);
            } else {
                return $this->hasMany($class);
            }
        }
        return null;
    }

    /**
     * create a DataObject from associative array
     * ```
     * $author = Author::create(['firstname'=>'Victor','lastname'=>'HUGO']);
     * ```
     * @param $params
     * @return DataObject
     */
    public static function create($params = [])
    {
        $class = get_called_class();
        $object = new $class();
        $object->id = null;
        foreach ($params as $key => $value) {
            if ($key != 'id' and substr($key, 0, 1) != '_') {
                $object->$key = $value;
            }
        }
        return $object;
    }

    /**
     * Set string ORDER BY...
     * @param $order
     * @return string
     */
    public static function order($order)
    {
        return (null !== $order) ? (' ORDER BY ' . $order) : '';
    }

    /**
     * Set string LIMIT...
     * @param $limit
     * @return string
     */
    public static function limit($limit)
    {
        return (null !== $limit) ? (' LIMIT ' . $limit) : '';
    }

    /**
     * return count from where query
     * @param null $where
     * @param $params
     * @return int
     */
    public static function count($where = null, $params = null)
    {
        if (is_array($where)) {
            $where = self::_createWhereFromParams($where,$params);
        }
        if (null !== $where) {
            $where = ' WHERE ' . $where;
        } else {
            $where = '';
        }

        return (int)self::query(
            'SELECT count(*) as nb FROM `' . self::getTable() . '`' . $where,
            $params
        )[0]->nb;
    }

    /**
     * return true if object is in database
     * @return bool
     */
    protected function isInDatabase()
    {
        if (!isset($this->id)) {
            return false;
        }
        if (null == $this->id) {
            return false;
        }
        return null !== self::get($this->id);
    }

    /**
     * return an uniq id
     * @return bool|string
     */
    public static function defineId($length=8)
    {

        $class = get_called_class();
        if($class::$_autoincrement) {
            $status = self::query('SHOW TABLE STATUS WHERE name=?',[self::getTable()]);
            $id = $status[0]->Auto_increment;
        }else {
            do {
                $id = substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
            } while (null != self::get($id));
        }
        return $id;
    }

    /**
     * return object with relation
     * ```
     * $books = $victorHugo->hasMany(Book::class);
     * ```
     * @param string $class
     * @return array
     * @throws \Exception
     */
    public function hasMany($class)
    {
        if (!class_exists($class)) {
            throw new \Exception('Class does not exists : ' . $class);
        }
        if ($class::hasColumn(self::getTable() . '_id')) {
            return $class::find([self::getTable() . '_id' => $this->id]);
            //}elseif($class::hasColumn(self::getTable() . '_id')){
        } elseif (class_exists($this->_getClassBetween($class, true))) {
            $classBetween = $this->_getClassBetween($class, true);
            $items = $classBetween::find([self::getTable() . '_id' => $this->id]);
            $array = [];
            foreach ($items as $item) {
                $field = $class::getTable() . '_id';
                $array[] = $class::get($item->$field);
            }
            return $array;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    protected function _getNamespace()
    {
        $thisClass = get_class($this);
        return implode('\\', array_slice(explode('\\', $thisClass), 0, -1));
    }

    /**
     * @return string
     */
    protected function _getClass()
    {
        return isset($this->_className) ? $this->_className : get_class($this);
    }

    /**
     * @param null $separator
     * @return mixed|string
     */
    public function getModel($separator = null)
    {
        if (null == $separator) {
            return $this->_getClass();
        } else {
            return str_replace('\\', $separator, $this->_getClass());
        }
    }

    /**
     * @param $class
     * @param bool $namespace
     * @return string
     */
    protected function _getClassBetween($class, $namespace = false)
    {
        $thisClass = $this->_getClass();
        $nsp = $this->_getNamespace();
        $array = [join('', array_slice(explode('\\', $class), -1)), join('', array_slice(explode('\\', $thisClass), -1))];
        sort($array);
        if ($namespace) {
            return $nsp . '\\' . implode(self::$_classSeparator, $array);
        } else {
            return implode(self::$_classSeparator, $array);
        }
    }

    /**
     * Store object in database
     * @return $this
     * @throws \Exception
     */
    public function store($transaction = true)
    {
        $vars = get_object_vars($this);
        /**
         * convert attributes to database data
         */
        foreach ($vars as $key => $value) {
            if (substr($key, 0, 1) == '_' or $key == 'id') {
                unset($vars[$key]);
            }
            if (is_object($value)) {
                if (!$value->isInDatabase()) {
                    $value->store();
                }
                $vars[$key . '_id'] = $value->id;
                unset($vars[$key]);
            }
            if (is_array($value)) {
                unset($vars[$key]);
            }
        }
        /**
         * create and execute query
         */
        if (!$this->isInDatabase()) {
            $keys = array_keys($vars);
            $keys[] = 'id';
            if ($transaction) {
                self::beginTransaction();
            }
            $this->id = self::defineId();
            $vars[] = $this->id;
            $query = 'insert into `' . self::getTable() . '`(`' . implode('`, `', $keys) . '`) values (?' . str_repeat(', ?', sizeof($vars) - 1) . ')';
            $statement = self::_prepare($query);
            $result = $statement->execute(array_values($vars));
            if ($transaction) {
                self::commit();
            }
            if (!$result) {
                throw new \Exception($statement->errorInfo()[2]);
            }
        } else {
            $query = 'update `' . self::getTable() . '` set `' . implode('` = ?, `', array_keys($vars)) . '` = ? '
                . 'where `id` = ?';
            $statement = self::_prepare($query);
            $vars['id'] = $this->id;
            $result = $statement->execute(array_values($vars));
            if (!$result) {
                throw new \Exception($statement->errorInfo()[2]);
            }
        }
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (is_array($value)) {
                $classBetween = $this->_getClassBetween(ucfirst($key), true);
                if ($classBetween::tableExists()) {
                    $items = $classBetween::find([$this->getTable() . '_id' => $this->id]);
                } else {
                    $items = [];
                }
                foreach ($items as $item) {
                    $item->delete();
                }
                foreach ($value as $v) {
                    $item = new $classBetween();
                    $field1 = $this->getTable();
                    $item->$field1 = $this;
                    $field2 = $key;
                    $item->$field2 = $v;
                    $item->store();
                }
                unset($vars[$key]);
            }
        }
        return $this;
    }

    /**
     * delete object in database
     * @return bool
     */
    public function delete()
    {
        $keys = [];
        $params = [];
        if (empty($this->id)) {
            foreach ($this as $k => $v) {
                if (null != $v) {
                    $keys[] = $k;
                    $params[] = $v;
                }
            }
        } else {
            $keys = ['id'];
            $params[] = $this->id;
        }
        $stmt = self::_prepare('DELETE FROM `' . self::getTable() . '` WHERE `' . implode('` = ? AND `', $keys) . '` = ?');
        $result = $stmt->execute($params);
        //var_dump('DELETE FROM `' . self::getTable() . '` WHERE `' . implode('` = ? AND `', $keys) . '` = ?');
        return $result;
    }


    /**
     * return true if table exists
     * @return boolean
     */
    public static function tableExists()
    {
        $query = 'show tables like "' . self::getTable() . '"';
        return count(self::query($query)) > 0;
    }

    /**
     * test column existence
     * @param string $column column name
     * @return boolean
     */
    public static function hasColumn($column)
    {
        if (self::tableExists()) {
            $query = 'show columns from `' . self::getTable() . '` where Field=?';
            return count(self::query($query, [$column])) > 0;
        } else {
            return false;
        }
    }

    /**
     * drop table
     * @param bool $foreignKeyCheck
     * @return bool
     */
    public static function drop($foreignKeyCheck = false)
    {
        if (self::tableExists(Author_Book::class)) {
            if (!$foreignKeyCheck) {
                self::_getPDO()->exec('SET FOREIGN_KEY_CHECKS = 0;');
            }
            $return = self::_getPDO()->exec('drop table `' . self::getTable() . '`;');
            if (!$foreignKeyCheck) {
                self::_getPDO()->exec('SET FOREIGN_KEY_CHECKS = 1;');
            }
            return $return;
        }
    }

    /**
     * truncate table
     * @example CLASS::truncate(false);
     * @param bool $foreignKeyCheck
     * @return bool
     */
    public static function truncate($foreignKeyCheck = false)
    {
        if (!$foreignKeyCheck) {
            self::_getPDO()->exec('SET FOREIGN_KEY_CHECKS = 0;');
        }
        $return = self::_getPDO()->exec('truncate `' . self::getTable() . '`;');
        if (!$foreignKeyCheck) {
            self::_getPDO()->exec('SET FOREIGN_KEY_CHECKS = 1;');
        }
        return $return;
    }

    /**
     * populate object with associative array
     * @param $params
     * @return $this
     */
    public function populate($params)
    {
        $columns = self::getColumns();
        foreach ($columns as $column) {
            if (isset($params[$column['Field']])) {
                $c = $column['Field'];
                $this->$c = $params[$column['Field']];
            }
        }
        return $this;
    }


    public function fromJson($json)
    {
        $object = json_decode($json);
        $vars = get_object_vars($object);
        $columns = self::getColumns();
        foreach ($columns as $column) {
            $field = $column['Field'];
            if (isset($object->$field)) {
                $this->$field = $object->$field;
            }
        }
    }
    /**
     * @param Object $object
     * @return bool
     */
    public function isEqualTo(Object $object)
    {
        $vars = get_object_vars($this);
        if (get_class($object) != get_class($this)) {
            return false;
        }
        foreach ($vars as $key => $value) {
            if (!property_exists($object, $key) or $this->$key !== $object->$key) {
                return false;
            }
        }
        return true;
    }

    /**
     * copy object without id param
     * @return DataObject
     */
    public function copy()
    {
        $class = $this->_getClass();
        $vars = get_object_vars($this);
        unset($vars['id']);
        return $class::create($vars);
    }

    /**
     * begin transaction if none has been started
     */
    public static function beginTransaction()
    {
        if (!self::_getPDO()->inTransaction()) {
            self::_getPDO()->beginTransaction();
        }
    }

    /**
     * commit transaction
     */
    public static function commit()
    {
        if (self::_getPDO()->inTransaction()) {
            self::_getPDO()->commit();
        }
    }

    /**
     * roll back
     */
    public static function rollback()
    {
        if (self::_getPDO()->inTransaction()) {
            self::_getPDO()->rollBack();
        }
    }
}
