<?php
namespace RestInPeace;

/**
 * Trait HasAccessors
 *
 * This trait provides accessor methods for the class that uses it.
 *
 * @package Bobanum\RestInPeace
 */
trait HasAccessors {

	/**
	 * Magic method to get the value of a property.
	 *
	 * This method is called when accessing a property that is not defined
	 * in the class. It allows for dynamic retrieval of properties.
	 *
	 * @param string $name The name of the property being accessed.
	 * @return mixed The value of the property, if it exists.
	 */
	public function __get($name) {
		$get_name = 'get_' . $name;
		if (method_exists($this, $get_name)) {
			return $this->$get_name();
		}
	}
	/**
	 * Magic method to set the value of a property.
	 *
	 * @param string $name The name of the property.
	 * @param mixed $value The value to set.
	 */
	public function __set($name, $value) {
		$set_name = 'set_' . $name;
		if (method_exists($this, $set_name)) {
			return $this->$set_name($value);
		}
	}
}
