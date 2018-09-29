<?php

namespace ErwanG;

use PDO;

class DataObject
{
    private static $_pdo;
    protected static $_updateTableStructure=false;
    public $id;

    /**
     * DataObject constructor.
     */
    protected function __construct()
    {
    }

    public static function updateStructure(){
        self::$_updateTableStructure=true;
    }
    public static function get($id)
    {
        $class=get_called_class();
        $query = 'SELECT * FROM `' . $class::getTable() . '` WHERE id' . ' = ?';
        $objects = self::query($query,[$id]);
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
     * @param $params
     * @return PDO
     */
    public static function setPDO($params)
    {
        try{
            self::$_pdo = new PDO('mysql:dbname='.$params['dbname'].';host='.$params['host'],$params['username'],$params['password']);
            self::$_pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        }catch(Exception $e){
            die('Error: '.$e->getMessage());
        }

        self::$_pdo->exec('SET NAMES \'utf8\'');
        return self::$_pdo;
    }

    /**
     * @param $query
     * @return \PDOStatement
     */
    private static function _prepare($query)
    {
        static $queries=[];
        if(!isset($queries[$query])){
            $queries[$query]=self::_getPdo()->prepare($query);
        }
        return $queries[$query];
    }

    /**
     * @param $query
     * @param array $params
     * @return array
     */
    public static function query($query,$params=[])
    {
        $stmt = self::_prepare($query);
        try {
            $stmt->execute($params);
        }catch(\Exception $e){
            var_dump($e->getMessage());
            var_dump($query);
        }
        return $stmt->fetchAll(PDO::FETCH_CLASS,get_called_class());
    }
    /**
     * @return string
     */
    public static function getTable()
    {
        $class = get_called_class();
        if(isset($class::$_table))
        {
            return $class::$_table;
        }
        $array = explode('\\',$class);
        return end($array);
    }

    /**
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
     * @param $where
     * @param array $params
     * @param null $order
     * @param null $limit
     * @return array
     */
    public static function where($where,$params=[],$order=null,$limit=null)
    {
        if($where===''){
            $query = 'SELECT * FROM `' . self::getTable() . '` '. self::order($order) . self::limit($limit);
        }else{
            $query = 'SELECT * FROM `' . self::getTable() . '` WHERE ' . $where . self::order($order) . self::limit($limit);
        }
        self::createTable();
        return self::query($query, $params);
    }

    /**
     * @param $where
     * @param array $params
     * @param null $order
     * @return DataObject|null
     */
    public static function whereFirst($where,$params=[],$order=null)
    {
        $result = self::where($where,$params,$order,'0,1');
        return isset($result[0]) ? $result[0] : null;
    }

    /**
     * @param $where
     * @param array $params
     * @param null $order
     * @return DataObject|null
     */
    public static function whereLast($where,$params=[],$order=null)
    {
        $result = self::where($where,$params,$order,'0,1');
        return count($result)>0 ? end($result) : null;
    }

    /**
     * @param $params
     * @param null $order
     * @param null $limit
     * @return array
     */
    public static function find($params, $order=null,$limit=null)
    {
        $where = [];
        $p = [];
        foreach ($params as $k => $v) {
            if($v===null){
                $where[] = '`' . $k . '` is null';
            }else {
                $where[] = '`' . $k . '`=?';
                $p[] = $v;
            }
        }
        return self::where(implode(' and ', $where), $p, $order, $limit);
    }

    /**
     * @param $params
     * @param $order
     * @param bool $create
     * @return DataObject|null
     */
    public static function findFirst($params,$order=null,$create=false)
    {
        $result = self::find($params,$order,'0,1');
        if(isset($result[0])){
            return $result[0];
        }
        if($create){
            return self::create($params);
        }
        return null;
    }

    /**
     * @param $params
     * @param $order
     * @return DataObject|null
     */
    public function findLast($params,$order)
    {
        $result = self::find($params,$order);
        return count($result)>0 ? end($result) : null;
    }

    /**
     * @param null $order
     * @param null $limit
     * @return array
     */
    public static function findAll($order=null,$limit=null)
    {
        return self::query('SELECT * FROM `'. self::getTable() .'`'. self::order($order) . self::limit($limit));
    }

    public function __get($name){
        $class='\\Models\\'.ucfirst($name);
        if(class_exists($class)){
            $key=$name.'_id';
            return $class::get($this->$key);
        }
    }

    /**
     * @param $params
     * @return DataObject
     */
    public static function create($params=[])
    {
        $class = get_called_class();
        $object = new $class();
        $object->id=null;
        foreach ($params as $key=>$value) {
            if($key!='id' and substr($key,0,1)!='_'){
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
    public static function order($order) {
        return (null !== $order) ? (' ORDER BY ' . $order) : '';
    }

    /**
     * Set string LIMIT...
     * @param $limit
     * @return string
     */
    public static function limit($limit) {
        return (null !== $limit) ? (' LIMIT ' . $limit) : '';
    }

    /**
     * @param null $where
     * @param $params
     * @return int
     */
    public static function count($where=null,$params=null)
    {
        if(is_array($where)){
            $params= $where;
            $where=[];
            $p=[];
            foreach ($params as $k => $v) {
                if($v===null){
                    $where[] = '`' . $k . '` is null';
                }else {
                    $where[] = '`' . $k . '`=?';
                    $p[] = $v;
                }
            }
            $params=$p;
            $where = implode(' and ', $where);
        }
        if (null !== $where) { $where = ' WHERE '. $where; }
        else { $where = ''; }

        return (int) self::query(
            'SELECT count(*) as nb FROM `'. self::getTable() .'`'. $where,
            $params
        )[0]->nb;
    }

    /**
     * @return bool
     */
    protected function isInDatabase()
    {
        if(!isset($this->id)){
            return false;
        }
        if(null==$this->id){
            return false;
        }
        return null!== self::get($this->id);
    }

    /**
     * @return bool|string
     */
    public static function defineId()
    {
        $length = 8;
        do {
            $id = substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
        }while(null!=self::get($id));
        return $id;

    }

    /**
     * return object with relation
     * @param string $class
     * @return array
     */
    public function hasMany($class) {
        if (!class_exists($class)) {
            throw new \Exception('Class does not exists : ' . $class);
        }
        if ($class::hasColumn(self::_getTable() . '_id')) {
            return $class::find([self::_getTable() . '_id' => $this->id]);
        }elseif($class::hasColumn(self::_getTable() . '_id')){
        //} elseif (class_exists($this->_getClassBetween($class, true))) {
            $classBetween = $this->_getClassBetween($class, true);
            $items = $classBetween::find([self::_getTable() . '_id' => $this->id]);
            $array = [];
            foreach ($items as $item) {
                $field = $class::_getTable() . '_id';
                $array[] = new $class($item->$field);
            }
            return $array;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    protected function _getNamespace() {
        $thisClass = get_class($this);
        return implode('\\', array_slice(explode('\\', $thisClass), 0, -1));
    }

    /**
     * @return string
     */
    protected function _getClass() {
        return isset($this->_className) ? $this->_className : get_class($this);
    }

    public function getModel($separator=null){
        if(null==$separator) {
            return $this->_getClass();
        }else{
            return str_replace('\\',$separator,$this->_getClass());
        }
    }
    /**
     * @param $class
     * @param bool $namespace
     * @return string
     */
    protected function _getClassBetween($class, $namespace = false) {
        $thisClass = $this->_getClass();
        $nsp = $this->_getNamespace();
        $array = [join('', array_slice(explode('\\', $class), -1)), join('', array_slice(explode('\\', $thisClass), -1))];
        sort($array);
        if ($namespace) {
            return $nsp . '\\' . implode('_', $array);
        } else {
            return implode('_', $array);
        }
    }

    /**
     * Store object in database
     * @return $this
     * @throws \Exception
     */
    public function store($transaction=true) {
        $vars = get_object_vars($this);
        /**
         * table creation or update
         */
        if (self::$_updateTableStructure) {
            self::createTable();
            //create fields
            $this->_createColumns();
        }
        /**
         * convert attributes to database data
         */
        foreach ($vars as $key => $value) {
            if (substr($key, 0, 1) == '_' or $key == 'id') {
                unset($vars[$key]);
            }
            if (is_object($value)) {
                if(!$value->isInDatabase()){
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
            $keys[]='id';
            if($transaction) {
                self::beginTransaction();
            }
            $this->id=self::defineId();
            $vars[]=$this->id;
            $query = 'insert into `' . self::getTable() . '`(`' . implode('`, `', $keys) . '`) values (?' . str_repeat(', ?', sizeof($vars) - 1) . ')';
            $statement = self::_prepare($query);
            $result = $statement->execute(array_values($vars));
            if($transaction) {
                self::commit();
            }
            if (!$result) {
                throw new \Exception($statement->errorInfo()[2]);
            }
        } else {
            $query =  'update `' . self::getTable() . '` set `' . implode('` = ?, `', array_keys($vars)) . '` = ? '
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
                if($classBetween::tableExists()) {
                    $items = $classBetween::find([$this->getTable() . '_id' => $this->id]);
                }else{
                    $items=[];
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
        }
        else {
            $keys = ['id'];
            $params[] = $this->id;
        }
        $stmt = self::_prepare('DELETE FROM `' . self::getTable() . '` WHERE `' . implode('` = ? AND `', $keys) . '` = ?');
        $result = $stmt->execute($params);
        return $result;
    }



    /**
     * return true if table exists
     * @return boolean
     */
    public static function tableExists() {
        $query = 'show tables like "' . self::getTable() . '"';
        return count(self::query($query)) > 0;
    }
    /**
     * create table
     */
    public static function createTable()
    {

        if(!self::tableExists()){
            $stmt = self::_prepare('create table `'.self::getTable().'`(id varchar(20) PRIMARY KEY NOT NULL)');
            return ($stmt->execute());
        };
        return true;
    }


    private static function _getColumnType($value)
    {
        $type = 'longtext';
        switch (gettype($value)) {
            case 'boolean':
                $type = 'tinyint(1)';
                break;
            case 'integer':
                $type = 'int(11)';
                break;
            case 'double':
                $type = 'float';
                break;
            case 'object':
                $type='varchar(20)';
                break;
            case 'string':
                if (\DateTime::createFromFormat('Y-m-d', $value)) {
                    $type = 'date';
                } elseif (\DateTime::createFromFormat('H:i:s', $value) or \DateTime::createFromFormat('H:i', $value)) {
                    $type = 'time';
                } elseif (\DateTime::createFromFormat('Y-m-d H:i:s', $value) or \DateTime::createFromFormat('Y-m-d H:i', $value)) {
                    $type = 'datetime';
                } elseif (is_numeric($value)) {
                    if (intval($value) == $value) {
                        return self::_getColumnType(intval($value));
                    } else {
                        return self::_getColumnType(floatval($value));
                    }
                } elseif (strlen($value) > 250) {
                    $type = 'longtext';
                } else {
                    $type = 'varchar(250)';
                }
        }
        return $type;
    }

    /**
     * create columns
     */
    private function _createColumns()
    {
        $columns= self::getColumns();
        $columnsTable=[];
        foreach($columns as $column){
            $columnsTable[$column['Field']]=$column;
        }
        unset($columns);
        foreach ($this as $key=>$value) {
            if($key!=='id') {
                if(is_object($value)){
                    $key.='_id';
                }
                if (!isset($columnsTable[$key])) {
                    $query = 'ALTER TABLE `' . self::getTable() . '` ADD `' . $key . '` ' . self::_getColumnType($value);
                } else {
                    $query = 'ALTER TABLE `' . self::getTable() . '` CHANGE `' . $key . '` `' . $key . '` ' . self::_getColumnType($value);
                }
                try {
                    $stmt = self::_prepare($query);
                    $stmt->execute();
                }catch (Exception $e){
                    var_dump($columnsTable);
                    var_dump($this);
                    var_dump($key);
                    var_dump($value);
                    var_dump($query);
                    echo $e->getMessage();
                    throw $e;
                }

                if(is_object($value)){
                    $class=get_class($value);
                    $query = 'ALTER TABLE `'.self::getTable().'` ADD FOREIGN KEY (`'.$key.'`) REFERENCES `'.$class::getTable().'`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;' ;
                    $stmt = self::_prepare($query);
                    try {
                        $stmt->execute();
                    }catch(\Exception $e){
                        var_dump($e->getMessage());
                        var_dump($query);
                        exit();
                    }
                }
            }
        }
    }

    /**
     * test column existence
     * @param string $column column name
     * @param string $table table name
     * @return boolean
     */
    public static function hasColumn($column) {
        if (self::tableExists()) {
            $query = 'show columns from `' . self::getTable() . '` where Field=?';
            return count(self::query($query,[$column])) > 0;
        } else {
            return false;
        }
    }

    /**
     * drop table
     * @param bool $foreignKeyCheck
     * @return bool
     */
    public static function drop($foreignKeyCheck=false)
    {
        if(self::tableExists(Author_Book::class)) {
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
     * @param bool $foreignKeyCheck
     * @return bool
     */
    public function truncate($foreignKeyCheck=false)
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

    /**
     * @param Object $object
     * @return bool
     */
    public function isEqualTo(Object $object){
        $vars = get_object_vars($this);
        if(get_class($object)!=get_class($this)){
            return false;
        }
        foreach($vars as$key=>$value){
            if(!isset($object->$key) or $this->$key!==$object->$key){
                return false;
            }
        }
        return true;
    }

    /**
     * @return DataObject
     */
    public function copy(){
        $class = $this->_getClass();
        $vars = get_object_vars($this);
        unset($vars['id']);
        return $class::create($vars);
    }

    public static function beginTransaction(){
        if(!self::_getPDO()->inTransaction()) {
            self::_getPDO()->beginTransaction();
        }
    }

    public static function commit(){
        if(self::_getPDO()->inTransaction()) {
            self::_getPDO()->commit();
        }
    }

    public static function rollback()
    {
        if(self::_getPDO()->inTransaction()){
            self::_getPDO()->rollBack();
        }
    }
}