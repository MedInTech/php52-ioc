<?php

interface MedInTech_IoC_Interface extends ArrayAccess
{
  public function get($key, array $overrides = array());
  public function has($key, array $overrides = array());
  public function set($key, $service);
  public function unset($key);

  public function create($className, array $overrides = array());
  public function call($object, $method = '__invoke', array $overrides = array());
}