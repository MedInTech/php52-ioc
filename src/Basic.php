<?php

class MedInTech_IoC_Basic implements MedInTech_IoC_Interface
{
  public    $forceCircularsAsNull  = false;
  public    $allowClassNameFactory = true;
  protected $circular_marker;
  protected $optional_marker;
  public function __construct()
  {
    $this->circular_marker = sha1('Circular dependency');
    $this->optional_marker = sha1('Optional parameter');
  }

  protected $map = array();
  public function get($key, array $overrides = array())
  {
    $subj = null;
    if (array_key_exists($key, $overrides))
      $subj = $overrides[$key];
    elseif (array_key_exists($key, $this->map) && $this->map[$key] !== $this->circular_marker)
      $subj = $this->map[$key];

    if ($this->allowClassNameFactory) {
      if (is_string($subj) && class_exists($subj)) {
        $subj = $this->create($subj, $overrides);
      }
    }

    if ($subj instanceof MedInTech_IoC_Factory_Interface) {
      $subj = $this->create($subj->getClass(), $overrides);
    }

    return $subj;
  }
  public function set($key, $service) { $this->map[$key] = $service; }
  public function remove($key) { unset($this->map[$key]); }
  public function has($key, array $overrides = array())
  {
    return array_key_exists($key, $overrides) ||
      (array_key_exists($key, $this->map) && $this->map[$key] !== $this->circular_marker);
  }
  public function all() { return $this->map; }
  public function keys() { return array_keys($this->map); }

  public function create($className, array $overrides = array())
  {
    if ($this->has($className, $overrides)) {
      $arg = $this->get($className, $overrides);

      return $arg;
    }

    $class = new ReflectionClass($className);
    if (!$class->isInstantiable()) {
      throw new MedInTech_IoC_Exception("$className can not be instantiated");
    }
    $constructor = $class->getConstructor();
    $args = empty($constructor) ? array() :
      $this->getArguments($constructor, $overrides);

    return $args ? $class->newInstanceArgs($args) : $class->newInstance();
  }
  public function call($object, $methodName = '__invoke', array $overrides = array())
  {
    $className = get_class($object);
    $class = new ReflectionClass($className);
    $method = $class->getMethod($methodName);
    $args = $this->getArguments($method, $overrides);

    return call_user_func_array(array($object, $methodName), $args);
  }

  // ArrayAccess
  public function offsetExists($offset) { return $this->has($offset); }
  public function offsetGet($offset) { return $this->get($offset); }
  public function offsetSet($offset, $value) { $this->set($offset, $value); }
  public function offsetUnset($offset) { $this->remove($offset); }

  protected function getArguments(ReflectionMethod $method, array $overrides = array())
  {
    $args = array();
    $params = $method->getParameters();
    foreach ($params as $index => $param) {
      $args[] = $this->getArgument($param, $index, $overrides);
    }
    while (end($args) === $this->optional_marker) {
      array_pop($args);
    }
    reset($args);
    foreach ($args as &$arg) {
      if ($arg === $this->optional_marker) $arg = null;
    }
    unset($arg);

    return $args;
  }

  protected function getArgument(ReflectionParameter $param, $index, array $overrides = array())
  {
    $arg = null;
    if (array_key_exists($index, $overrides)) { // by numeric index
      return $overrides[$index];
    }
    $name = $param->getName();
    if ($this->has($name, $overrides)) {
      return $this->get($name, $overrides);
    }
    $paramClass = $param->getClass();
    if ($paramClass) {
      $paramClassName = $paramClass->getName();
      if ($this->has($paramClassName, $overrides)) {
        return $this->get($paramClassName, $overrides);
      }
      // Try to create
      if (isset($this->map[$paramClassName]) && $this->map[$paramClassName] === $this->circular_marker) {
        if ($this->forceCircularsAsNull) {
          return null;
        }
        throw new MedInTech_IoC_Exception("Circular dependency of param({$name}) detected");
      }
      $this->map[$paramClassName] = $this->circular_marker;
      try {
        $arg = $this->create($paramClassName, $overrides);
      } catch (MedInTech_IoC_Exception $e) {
      }
    }

    if (is_null($arg)) {
      if ($param->isDefaultValueAvailable()) {
        $arg = $param->getDefaultValue();
      } elseif ($param->isOptional()) {
        $arg = $this->optional_marker;
      } else /*if (!$param->allowsNull())*/ {
        throw new MedInTech_IoC_Exception("Parameter $name cannot instantiate");
      }
    }

    return $arg;
  }
}