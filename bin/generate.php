#!/usr/bin/php
<?php

foreach (array(__DIR__ . '/../../autoload.php', __DIR__ . '/../vendor/autoload.php', __DIR__ . '/vendor/autoload.php') as $file) {
    if (file_exists($file)) {
        include($file);
        break;
    }
}

$command = new \Commando\Command();

$command->option('u')
    ->aka('user')
    ->describedAs('database user')
    ->require(true);

$command->option('d')
    ->aka('database')
    ->describedAs('database name')
    ->require(true);

$command->option('p')
    ->aka('password')
    ->describedAs('database password');

$command->option('t')
    ->aka('table')
    ->describedAs('database table')
    ->require(false);

$command->option('n')
    ->aka('namespace')
    ->describedAs('class namespace')
    ->require(false);


if(null==$command['p']){
    $password = Seld\CliPrompt\CliPrompt::hiddenPrompt('password database');
}else{
    $password = $command['p'];
}
$pdo = new PDO('mysql:dbname='.$command['d'].';host=127.0.0.1',$command['u'],$password);

if(null==$command['t']){
    $query = 'SHOW TABLES FROM `' . $command['d'] . '`;';
    $tables=[];
    foreach($pdo->query($query)->fetchAll() as $t){
        $tables[]=$t[0];
    }
}else {
    $tables = [$command['t']];
}
foreach($tables as $table) {
    $file = new Nette\PhpGenerator\PhpFile;
    if(!file_exists($table.'.php')) {
        $file->addComment('This file is auto-generated.');
        /** @var Nette\PhpGenerator\ClassType $class */
        $class = new Nette\PhpGenerator\ClassType($table);
        $class->setExtends('\ErwanG\DataObject');
        $properties = [];
        $query = 'SHOW COLUMNS FROM `' . $table . '`;';
        $columns = $pdo->query($query)->fetchAll();
        foreach ($columns as $column) {
            try {
                $class->getProperty($column['Field']);
            } catch (\Nette\InvalidArgumentException $e) {
                switch ($column) {
                    default:
                        $type = 'string';
                }
                $property = $class->addProperty($column['Field'])
                    ->setVisibility('public')
                    ->setComment('@var ' . $type);
            }
        }
        if (null !== $command['n']) {
            $namespace = $file->addNamespace($command['n']);
            $namespace->add($class);
        } else {
            $file->addNamespace($class);
        }

        file_put_contents($table . '.php', $file);
    }
}
