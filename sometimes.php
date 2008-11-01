<?php

#
# Sometimes
# Richard Crowley <r@rcrowley.org>
#



# In addition to being able to toggle tags on/off with the conditionals,
# it might be nice to toggle an attribute, attributes or the tag name itself
# based on some conditionals - this probably involves a callback mechanism

# It would also be nice to be able to toggle between a tag and a string
# with the conditionals - see the <strong>/<span> awkwardness in the test

# We can create "automatic conditionals" set to true for every variable
# set with Sd(...)

# How to do looping?



$GLOBALS['SOMETIMES_TEMPLATEDIR'] = dirname(__FILE__) . '/../templates';




class Sometimes {

	# Generally speaking, Sometimes structures will be loaded from a file
	#   These files are structures something like
	#   <?php
	#   return html(...);
	public static function file($file) {
		return include "{$GLOBALS['SOMETIMES_TEMPLATEDIR']}/$file";
	}

	# For the typical use case of running a script that defines some output
	# and then plugging that output into a template, here's a key/value store
	#   It is also accessible by the Sd(...) shortcut
	protected static $data = array();
	public static function data($args) {
		switch (sizeof($args)) {
			case 1:
				if (isset(self::$data[$args[0]])) {
					return self::$data[$args[0]];
				} else { return null; }
			break;
			case 2: self::$data[$args[0]] = $args[1]; break;
		}
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
	protected function conditions_met($args, $these = false) {
		if (!$these) { $these = $this->conditions; }

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
					if ($these[$k] == $v) {
						unset($these[$k]);
					} else { return false; }
				}
			}
		}
		if ($these) { return true; }

		# Variables implied by conditions must be set and truthy/falsey
		foreach ($these as $k => $v) {
			if (isset(self::$data[$k]) && (bool)self::$data[$k] != $v) {
				return false;
			}
		}
		return true;

	}

	# If this node passes the conditions, write it out
	protected function out() {
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

# The root <html> element has some special sauce and a shortcut like other tags
class HTML extends Sometimes {
	public function __construct($args) {
		parent::__construct('html', $args);
		if (!isset($this->attrs['xmlns'])) {
			$this->attrs['xmlns'] = 'http://www.w3.org/1999/xhtml';
		}
		if (!isset($this->attrs['xml:lang'])) {
			$this->attrs['xml:lang'] = 'en';
		}
	}
	public function out() {
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" ',
			'"http://w3.org/TR/xhtml1/DTD/xhtml1.1.dtd">', "\n";
		parent::out(func_get_args());
	}
}

# We also need a nil element that can act as an invisible parent
#   It prints nothing itself but controls the display of everything below
#   TODO: Use this for looping as well as conditions
class Nil extends Sometimes {
	protected function out() {
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
		Sd('_layout', $file);
		return Sf($layout);
	} else { return Sf(Sd('_layout')); }
}

# A shortcut for output
#   Outputs Sometimes objects with all passed SometimesConditions objects
function Sout() {
	$args = func_get_args();
	$out = array();
	$conditions = array();
	foreach ($args as $arg) {
		if ($arg instanceof Sometimes) { $out[] = $arg; }
		else if ($arg instanceof SometimesCondition) { $conditions[] = $arg; }
	}
	foreach ($out as $o) { $o->out($conditions); }
}

# A shortcut for getting/setting data
#   Sd('foo', 'bar');
#   $foo = Sd('foo');
function Sd() { return Sometimes::data(func_get_args()); }

# A shortcut for creating a condition
function Sc($name, $value = true) {
	return new SometimesCondition($name, $value);
}

# Take the S(...) shortcut one step further
function html() { return new HTML(func_get_args()); }
function nil() { return new Nil(func_get_args()); }
function head(){$a=func_get_args();return new Sometimes('head',$a);}
function title(){$a=func_get_args();return new Sometimes('title',$a);}
function style(){$a=func_get_args();return new Sometimes('style',$a);}
function script(){$a=func_get_args();return new Sometimes('script',$a);}
function meta(){$a=func_get_args();return new Sometimes('meta',$a);}
function body(){$a=func_get_args();return new Sometimes('body',$a);}
function div(){$a=func_get_args();return new Sometimes('div',$a);}
function h1(){$a=func_get_args();return new Sometimes('h1',$a);}
function h2(){$a=func_get_args();return new Sometimes('h2',$a);}
function h3(){$a=func_get_args();return new Sometimes('h3',$a);}
function h4(){$a=func_get_args();return new Sometimes('h4',$a);}
function h5(){$a=func_get_args();return new Sometimes('h5',$a);}
function h6(){$a=func_get_args();return new Sometimes('h6',$a);}
function p(){$a=func_get_args();return new Sometimes('p',$a);}
function pre(){$a=func_get_args();return new Sometimes('pre',$a);}
function a(){$a=func_get_args();return new Sometimes('a',$a);}
function span(){$a=func_get_args();return new Sometimes('span',$a);}
function strong(){$a=func_get_args();return new Sometimes('strong',$a);}
function em(){$a=func_get_args();return new Sometimes('em',$a);}
function big(){$a=func_get_args();return new Sometimes('big',$a);}
function small(){$a=func_get_args();return new Sometimes('small',$a);}
function tt(){$a=func_get_args();return new Sometimes('tt',$a);}
function code(){$a=func_get_args();return new Sometimes('code',$a);}
function kbd(){$a=func_get_args();return new Sometimes('kbd',$a);}
function del(){$a=func_get_args();return new Sometimes('del',$a);}
function ul(){$a=func_get_args();return new Sometimes('ul',$a);}
function ol(){$a=func_get_args();return new Sometimes('ol',$a);}
function li(){$a=func_get_args();return new Sometimes('li',$a);}
function form(){$a=func_get_args();return new Sometimes('form',$a);}
function label(){$a=func_get_args();return new Sometimes('label',$a);}
function input(){$a=func_get_args();return new Sometimes('input',$a);}
function textarea(){$a=func_get_args();return new Sometimes('textarea',$a);}
function select(){$a=func_get_args();return new Sometimes('select',$a);}
function hr(){$a=func_get_args();return new Sometimes('hr',$a);}
function br(){$a=func_get_args();return new Sometimes('br',$a);}
function img(){$a=func_get_args();return new Sometimes('img',$a);}
function table(){$a=func_get_args();return new Sometimes('table',$a);}
function tr(){$a=func_get_args();return new Sometimes('tr',$a);}
function th(){$a=func_get_args();return new Sometimes('th',$a);}
function td(){$a=func_get_args();return new Sometimes('td',$a);}



# A test, if run from the command line via `php sometimes.php`
if ('cli' == php_sapi_name()) {

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
