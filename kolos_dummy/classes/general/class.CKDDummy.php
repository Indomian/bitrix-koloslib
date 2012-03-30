<?php
class CKDDummy{
	private $a;
	private $b;
	private $c;
	
	function __construct($a,$b){
		$this->a=$a;
		$this->b=$b;
	}

	function Sum(){
		$this->a+=$this->b;
		return $this;
	}

	function Sub(){
		$this->a-=$this->b;
		return $this;
	}

	function Mul(){
		$this->a*=$this->b;
		return $this;
	}

	function __toString(){
		print $this->a;
	}
}