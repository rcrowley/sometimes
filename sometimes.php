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



class Sometimes {

	# Generally speaking, Sometimes structures will be loaded from a file
	public static function load($file) { return null; }

	# For the typical use case of running a script that defines some output
	# and then plugging that output into a template, here's a key/value store
	#   It is also accessible by the Sd(...) shortcut
	protected static $data = array();
	public static function data($args) {
		switch (sizeof($args)) {
			case 1: return self::$data[$args[0]]; break;
			case 2: self::$data[$args[0]] = $args[1]; break;
		}
	}

	protected $name = '___';
	protected $attrs = array();
	protected $conditions = array();
	protected $content = array();

	# A Sometimes node has some attributes and some content
	#   The first argument is the name of the node ('h1', 'div', etc)
	#   The second and third, if arrays, are attributes and conditions
	#   Remaining arguments are child Sometimes elements or string content
	public function __construct($args) {
		$this->name = array_shift($args);
		$arg = array_shift($args);
		if (is_array($arg)) {
			$this->attrs = $arg;
			$arg = array_shift($args);
			if (is_array($arg)) { $this->conditions = $arg; }
			else if ($arg) { $this->content[] = $arg; }
		} else if ($arg) { $this->content[] = $arg; }
		while ($arg = array_shift($args)) { $this->content[] = $arg; }
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

	# If this node passes the conditions, write it out
	public function out($conditions = array()) {
		foreach ($conditions as $k => $v) {
			if (isset($this->conditions[$k]) && $this->conditions[$k] != $v) {
				return;
			}
		}
		echo "<{$this->name}";
		foreach ($this->attrs as $k => $v) {
			echo " $k=\"", htmlentities($v), '"';
		}
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

# The root <html> element has some special sauce and a shortcut like other tags
class HTML extends Sometimes {
	public function __construct($args) {
		array_unshift($args, 'html');
		parent::__construct($args);
		if (!isset($this->attrs['xmlns'])) {
			$this->attrs['xmlns'] = 'http://www.w3.org/1999/xhtml';
		}
		if (!isset($this->attrs['xml:lang'])) {
			$this->attrs['xml:lang'] = 'en';
		}
	}
	public function out($conditions = array()) {
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" ',
			'"http://w3.org/TR/xhtml1/DTD/xhtml1.1.dtd">', "\n";
		parent::out($conditions);
	}
}



# The "new" keyword is stupid and doesn't mean anything in PHP, so we make
# a shortcut for creating Sometimes nodes
function S() { return new Sometimes(func_get_args()); }

# A shortcut for getting/setting data
#   Sd('foo', 'bar');
#   $foo = Sd('foo');
function Sd() { return Sometimes::data(func_get_args()); }

# Take the S(...) shortcut one step further and define shortcuts for every
# (common) HTML tag
function html() { return new HTML(func_get_args()); }
function head(){$a=func_get_args();array_unshift($a,'head');return new Sometimes($a);}
function title(){$a=func_get_args();array_unshift($a,'title');return new Sometimes($a);}
function style(){$a=func_get_args();array_unshift($a,'style');return new Sometimes($a);}
function script(){$a=func_get_args();array_unshift($a,'script');return new Sometimes($a);}
function meta(){$a=func_get_args();array_unshift($a,'meta');return new Sometimes($a);}
function body(){$a=func_get_args();array_unshift($a,'body');return new Sometimes($a);}
function div(){$a=func_get_args();array_unshift($a,'div');return new Sometimes($a);}
function h1(){$a=func_get_args();array_unshift($a,'h1');return new Sometimes($a);}
function h2(){$a=func_get_args();array_unshift($a,'h2');return new Sometimes($a);}
function h3(){$a=func_get_args();array_unshift($a,'h3');return new Sometimes($a);}
function h4(){$a=func_get_args();array_unshift($a,'h4');return new Sometimes($a);}
function h5(){$a=func_get_args();array_unshift($a,'h5');return new Sometimes($a);}
function h6(){$a=func_get_args();array_unshift($a,'h6');return new Sometimes($a);}
function p(){$a=func_get_args();array_unshift($a,'p');return new Sometimes($a);}
function pre(){$a=func_get_args();array_unshift($a,'pre');return new Sometimes($a);}
function span(){$a=func_get_args();array_unshift($a,'span');return new Sometimes($a);}
function strong(){$a=func_get_args();array_unshift($a,'strong');return new Sometimes($a);}
function em(){$a=func_get_args();array_unshift($a,'em');return new Sometimes($a);}
function big(){$a=func_get_args();array_unshift($a,'big');return new Sometimes($a);}
function small(){$a=func_get_args();array_unshift($a,'small');return new Sometimes($a);}
function tt(){$a=func_get_args();array_unshift($a,'tt');return new Sometimes($a);}
function code(){$a=func_get_args();array_unshift($a,'code');return new Sometimes($a);}
function kbd(){$a=func_get_args();array_unshift($a,'kbd');return new Sometimes($a);}
function del(){$a=func_get_args();array_unshift($a,'del');return new Sometimes($a);}
function ul(){$a=func_get_args();array_unshift($a,'ul');return new Sometimes($a);}
function ol(){$a=func_get_args();array_unshift($a,'ol');return new Sometimes($a);}
function li(){$a=func_get_args();array_unshift($a,'li');return new Sometimes($a);}
function hr(){$a=func_get_args();array_unshift($a,'hr');return new Sometimes($a);}
function img(){$a=func_get_args();array_unshift($a,'img');return new Sometimes($a);}
function table(){$a=func_get_args();array_unshift($a,'table');return new Sometimes($a);}
function tr(){$a=func_get_args();array_unshift($a,'tr');return new Sometimes($a);}
function th(){$a=func_get_args();array_unshift($a,'th');return new Sometimes($a);}
function td(){$a=func_get_args();array_unshift($a,'td');return new Sometimes($a);}



# A test, if run from the command line via `php sometimes.php`
if ('cli' == php_sapi_name()) {

	Sd('foo', 'bar');

	$doc = html(
		S('div', array('id' => 'everything', 'class' => 'foo'),
			p(
				'This is a ',
				S('strong', array(), array('bold' => true),
					'bold'
				),
				S('span', array(), array('bold' => false),
					'plain'
				),
				' sentence.'
			),
			p(Sd('foo'))
		)
	);
	$doc->out(array('bold' => true));
	echo "\n\n";
	$doc->out(array('bold' => false));
	echo "\n\n";

}
