<?php

class DateTimeHolder
{
  /**
   * @var DateTime
   */
  private $dt;
  private $q;
  public function __construct(DateTime $dt, $q = 17)
  {
    $this->setDt($dt);
    $this->q = $q;
  }
  public function setDt(DateTime $dt)
  {
    $this->dt = $dt;
  }
  public function getDate()
  {
    return $this->dt;
  }
}