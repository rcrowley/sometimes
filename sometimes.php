<?php

#
# Sometimes
# Richard Crowley <r@rcrowley.org>
#
# A PHP templating system
#
# This work is licensed under a Creative Commons
# Attribution-Share Alike 3.0 Unported License
# <http://creativecommons.org/licenses/by-sa/3.0/>
#



# In addition to being able to toggle tags on/off with the conditionals,
# it might be nice to toggle an attribute, attributes or the tag name itself
# based on some conditionals - this probably involves a callback mechanism

# It would also be nice to be able to toggle between a tag and a string
# with the conditionals - see the <strong>/<span> awkwardness in the test

# Can cache the HTML output with <?= Sd('foo'); ?\> inside



$GLOBALS['SOMETIMES_TEMPLATEDIR'] = dirname(__FILE__) . '/../templates';



class Sometimes {

	# Generally speaking, Sometimes structures will be loaded from a file
	#   These files are structures something like
	#   <?php
	#   return html(...);
	public static function file($file) {
		return include "{$GLOBALS['SOMETIMES_TEMPLATEDIR']}/$file";
	}

	protected $name = '___';
	protected $attrs = array();
	protected $conditions = array();
	protected $content = array();

	# A Sometimes node has some attributes and some content
	#   The first argument is the name of the node ('h1', 'div', etc)
	#   The second is an array, intended to be func_get_args() from a wrapper
	#     Elements are dealt with based on their type
	#       Arrays are attributes
	#       SometimesCondition objects are conditions
	#       Sometimes objects are child nodes
	#       Anything else is a child text node
	public function __construct($name, $args) {
		$this->name = $name;
		foreach ($args as $arg) {
			if (is_array($arg)) {
				$this->attrs = array_merge($this->attrs, $arg);
			} else if ($arg instanceof SometimesCondition) {
				$this->conditions[$arg->key] = $arg->value;
			}
			#else if ($arg instanceof Sometimes) {  }
			else { $this->content[] = $arg; }
		}
	}

	# Make Sometimes objects deep-clonable
	public function __clone() {
		foreach ($this->content as $i => $c) {
			if ($c instanceof Sometimes) { $this->content[$i] = clone $c; }
		}
	}

	# Accessors for this node's attributes and conditions
	#   Fuck PHP for not letting me overload method names
	public function attr() {
		$args = func_get_args();
		switch (sizeof($args)) {
			case 1: return $this->attrs[$args[0]]; break;
			case 2: $this->attrs[$args[0]] = $args[1]; break;
		}
	}
	public function attrs() { return $this->attrs; }
	public function condition() {
		$args = func_get_args();
		switch (sizeof($args)) {
			case 1: return $this->conditions[$args[0]]; break;
			case 2: $this->conditions[$args[0]] = $args[1]; break;
		}
	}
	public function conditions() { return $this->conditions; }

	# Follow the XPath expression down the tree
	#   Not sure if this'll be necessary and the accessors are only
	#   really useful if this is implemented
	public function xpath($expr) { return null; }

	# Test output conditions
	protected function conditions_met($args, &$these = false) {
		$recursive = is_array($these);
		if (!$recursive) { $these = $this->conditions; }

		# Explicit conditions must be met or ignored
		if (is_array($args) && 1 == sizeof($args) && is_array($args[0])) {
			$args = $args[0];
		}
		foreach ($args as $arg) {
			if (is_array($arg)) {
				if (!$this->conditions_met($arg, $these)) { return false; }
			} else if ($arg instanceof SometimesCondition) {
				$k = $arg->key; $v = $arg->value;
				if (isset($these[$k])) {
					if ($these[$k] == $v) { unset($these[$k]); }
					else { return false; }
				}
			}
		}
		if ($recursive) { return true; }

		# Variables implied by conditions must be set and truthy/falsey
		foreach ($these as $k => $v) {
			$value = SometimesData::get($k);
			if ((bool)$value != $v) { return false; }
		}
		return true;

	}

	# Bind child SometimesData nodes
	public function bind() {
		foreach ($this->content as $c) {
			if ($c instanceof Sometimes) { $c->bind(); }
		}
	}

	# If this node passes the conditions, write it out
	public function out() {
		$conditions = func_get_args();
		if (!$this->conditions_met($conditions)) { return; }
		echo "<{$this->name}";
		foreach ($this->attrs as $k => $v) { echo " $k=\"$v\""; }
		if (sizeof($this->content)) {
			echo '>';
			foreach ($this->content as $c) {
				if ($c instanceof Sometimes) { echo $c->out($conditions); }
				else { echo $c; }
			}
			echo "</{$this->name}>";
		} else { echo ' />'; }
	}

}

# A late-binding data node
class SometimesData extends Sometimes {

	# Static members to implement a basic key/value store
	protected static $data = array();
	public static function get($k) {
		if (isset(self::$data[$k])) { return self::$data[$k]; }
		else { return null; }
	}
	public static function set($k, $v) {
		self::$data[$k] = $v;
	}
	public static function delete($k) { unset(self::$data[$k]); }

	# A SometimesData node has only a key that can be bound at any time
	# to a value, making it essentially the same as a text node
	protected $key;
	public function __construct($key) {
		$this->key = $key;
	}
	public function bind() {
		if ($this->key) {
			$this->content = array(SometimesData::get($this->key));
			$this->key = false;
		}
	}
	public function __toString() {
		if (!$this->conditions_met(func_get_args())) { return; }
		$this->bind();
		return implode($this->content);
	}
	public function out() {
		if (!$this->conditions_met(func_get_args())) { return; }
		$this->bind();
		echo implode($this->content);
	}

}

# Encapsulate a condition in a distinct way
class SometimesCondition {
	public $key = '___';
	public $value = true;
	public function __construct() {
		$args = func_get_args();
		if (!sizeof($args)) { return; }
		$this->key = $args[0];
		if (2 == sizeof($args)) { $this->value = (bool)$args[1]; }
	}
}



# We need a nil element that can act as an invisible parent
#   It prints nothing itself but controls the display of everything below
class Nil extends Sometimes {
	public function __construct($args) { parent::__construct('___', $args); }
	public function out() {
		$conditions = func_get_args();
		if (!$this->conditions_met($conditions)) { return; }
		foreach ($this->content as $c) {
			if ($c instanceof Sometimes) { echo $c->out($conditions); }
			else { echo $c; }
		}
	}
}



# The "new" keyword is stupid and doesn't mean anything in PHP, so we make
# a shortcut for creating Sometimes nodes
function S($name, $args) { return new Sometimes($name, $args); }

# A shortcut for including files
function Sf($file) { return Sometimes::file($file); }

# Layouts are a nested set of files
#   Layouts should call Sl() with no arguments to embed the $file
function Sl($file = false, $layout = 'layout.html.php') {
	if ($file) {
		SometimesData::set('_layout', Sf($file));
		return Sf($layout);
	} else { return SometimesData::get('_layout'); }
}

# A shortcut for output
#   Outputs Sometimes objects with all passed SometimesConditions objects
function Sout() {
	$args = func_get_args();
	$sometimes = array();
	$conditions = array();
	foreach ($args as $arg) {
		if ($arg instanceof Sometimes) { $sometimes[] = $arg; }
		else if ($arg instanceof SometimesCondition) { $conditions[] = $arg; }
	}
	foreach ($sometimes as $s) { $s->out($conditions); }
}

# A shortcut for getting/setting data
#   With two arguments, set some data
#     Sd('foo', 'bar');
#   With one argument, create a SometimesData node
#     $sometimes = Sd('foo');
function Sd() {
	$args = func_get_args();
	switch (sizeof($args)) {
		case 1: return new SometimesData($args[0]); break;
		case 2: SometimesData::set($args[0], $args[1]); break;
	}
}

# A shortcut for creating a condition
function Sc($name, $value = true) {
	return new SometimesCondition($name, $value);
}

# Control flow
function Snil() { return new Nil(func_get_args()); }
function Sif() { return new Nil(func_get_args()); }
function Sforeach() {
	$args = func_get_args();
	$arr = array();
	$sometimes = array();
	$keys = array();
	foreach ($args as $arg) {
		if (is_array($arg)) { $arr = array_merge($arr, $arg); }
		else if ($arg instanceof Sometimes) { $sometimes[] = $arg; }
		else if ($arg instanceof SometimesCondition) { $sometimes[] = $arg; }
		else { $keys[] = $arg; }
	}
	$sometimes = new Nil($sometimes);
	switch (sizeof($keys)) {
		case 0: $key = '_k'; $value = '_v'; break;
		case 1: $key = '_k'; $value = $keys[0]; break;
		default: $key = $keys[0]; $value = $keys[1]; break;
	}
	$out = array();
	foreach ($arr as $k => $v) {
		SometimesData::set($key, $k);
		SometimesData::set($value, $v);
		$s = clone $sometimes;
		$s->bind();
		$out[] = $s;
	}
	SometimesData::delete($key);
	SometimesData::delete($value);
	return new Nil($out);
}



# A test, if run from the command line via `php sometimes.php`
if ('cli' == php_sapi_name() && is_array($argv)
	&& preg_match('!/' . basename(__FILE__) . '$!', realpath($argv[0]))) {

	# For the test, use templates in this directory
	$GLOBALS['SOMETIMES_TEMPLATEDIR'] = dirname(__FILE__);

	# Set a variable expected by foo.html.php
	Sd('foo', 'bar');

	# Include and output foo.html.php under both possible conditions
	$doc = Sf('foo.html.php');
	Sout($doc, Sc('bold'));
	echo "\n\n";
	Sout($doc, Sc('bold', false));
	echo "\n\n";

	# Safely fail to include a non-existent file
	var_dump(Sf('does-not-exist.html.php'));

}
