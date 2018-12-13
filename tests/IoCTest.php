<?php

use PHPUnit\Framework\TestCase;

class IoCTest extends TestCase
{
  /** @var MedInTech_IoC_Interface */
  private $IoC;
  protected function setUp()
  {
    $this->IoC = new MedInTech_IoC_Basic();
  }

  public function testSetGet()
  {
    $this->IoC['zero'] = '0';
    $this->assertEquals('0', $this->IoC['zero']);
    $this->IoC['now'] = new MedInTech_IoC_Factory_Basic('DateTime');
    $this->assertInstanceOf('DateTime', $this->IoC['now']);
    $this->assertInstanceOf('DateTime', $this->IoC->create('now'));
  }

  public function testCreate()
  {
    /** @var DateTime $dt */
    $dt = $this->IoC->create('DateTime');
    $this->assertTrue(abs($dt->getTimestamp() - time()) < 2);

    $this->IoC['nowDate'] = 'DateTime';
    $dt = $this->IoC->create('nowDate');
    $this->assertTrue(abs($dt->getTimestamp() - time()) < 2);

    $dt = $this->IoC->create('DateTime', array(
      '2018-09-01 00:00:00'
    ));
    $this->assertEquals('2018-09-01T00:00:00+0000', $dt->format(DateTime::ISO8601));
    $dt = $this->IoC->create('DateTime', array(
      'time' => '2018-09-01 00:00:00'
    ));
    $this->assertEquals('2018-09-01T00:00:00+0000', $dt->format(DateTime::ISO8601));

    /** @var DateTimeHolder $dth */
    $dth = $this->IoC->create('DateTimeHolder');
    $this->assertTrue(abs($dth->getDate()->getTimestamp() - time()) < 2);

    $this->IoC['DateTime'] = $dt;
    // reuse service
    $dt = $this->IoC->create('DateTime');
    $this->assertEquals('2018-09-01T00:00:00+0000', $dt->format(DateTime::ISO8601));

    $dth = $this->IoC->create('DateTimeHolder');
    $this->assertEquals('2018-09-01T00:00:00+0000', $dth->getDate()->format(DateTime::ISO8601));
    $dth = $this->IoC->create('DateTimeHolder', array(
      'DateTime' => new DateTime('2018-09-01T00:30:00'),
    ));
    $this->assertEquals('2018-09-01T00:30:00+0000', $dth->getDate()->format(DateTime::ISO8601));
    $dth = $this->IoC->create('DateTimeHolder', array(
      new DateTime('2018-09-01T00:30:00'),
    ));
    $this->assertEquals('2018-09-01T00:30:00+0000', $dth->getDate()->format(DateTime::ISO8601));
    $dth = $this->IoC->create('DateTimeHolder', array(
      'dt' => new DateTime('2018-09-01T00:30:00'),
    ));
    $this->assertEquals('2018-09-01T00:30:00+0000', $dth->getDate()->format(DateTime::ISO8601));
  }

  public function testCirculars()
  {
    try {
      $this->IoC->create('Circular');
      $this->assertFalse(true, 'Exception must be thrown');
    } catch (MedInTech_IoC_Exception $ex) {
      $this->assertTrue(true);
    }

    /**
     * @noinspection PhpUndefinedFieldInspection
     * Exclusive IoC\Basic feature, need to export into interface if will be useful
     */
    $this->IoC->forceCircularsAsNull = true;
    $this->IoC->create('Circular');
  }

  /**
   * @expectedException MedInTech_IoC_Exception
   */
  public function testUninstantiable()
  {
    $this->IoC->create('Uninstantiable');
  }
}