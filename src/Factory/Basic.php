<?php

class MedInTech_IoC_Factory_Basic implements MedInTech_IoC_Factory_Interface
{
  private $className;
  public function __construct($className)
  {
    $this->className = $className;
  }
  public function getClass()
  {
    return $this->className;
  }
}