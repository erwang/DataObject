<?php

namespace ErwanG\Tests;

class Editor extends \ErwanG\DataObject
{
	protected static $_autoincrement = true;

	/** @var string */
	public $id;

	/** @var string */
	public $name;
}
