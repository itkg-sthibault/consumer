<?php

namespace Itkg;

use Itkg\Log\Handler\EchoHandler;

/**
 * Classe pour les test phpunit pour la classe Service
 *
 * @author Pascal DENIS <pascal.denis@businessdecision.com>
 * @author Benoit de JACOBET <benoit.dejacobet@businessdecision.com>
 * @author Clément GUINET <clement.guinet@businessdecision.com>
 *
 * @package \Itkg
 * 
 */
class MonitoringTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Itkg\Mock\Service
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {         
      $this->object = new \Itkg\Monitoring();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers Itkg\Monitoring::getStart
     * @covers Itkg\Monitoring::setStart
     */
    public function testStart()
    {
        $this->assertNull($this->object->getStart());
        $start = microtime(true);
        $this->object->setStart($start);
        $this->assertEquals($start, $this->object->getStart());
    }

    /**
     * @covers Itkg\Monitoring::getEnd
     * @covers Itkg\Monitoring::setEnd
     */
    public function testEnd()
    {
        $this->assertNull($this->object->getEnd());
        $end = microtime(true);
        $this->object->setEnd($end);
        $this->assertEquals($end, $this->object->getEnd());
    }
    
    /**
     * @covers Itkg\Monitoring::getDuration
     * @covers Itkg\Monitoring::setDuration
     */
    public function testDuration()
    {
        $this->assertNull($this->object->getDuration());
        $this->object->setDuration(2);
        $this->assertEquals(2, $this->object->getDuration());
    }
    
    /**
     * @covers Itkg\Monitoring::addLogger
     */
    public function testAddLogger()
    {
        $logger = \Itkg\Log\Factory::getLogger(array(array('handler' => new EchoHandler())));
        $this->object->addLogger($logger, "test");
        $attr = \PHPUnit_Framework_Assert::readAttribute($this->object, 'loggers');
        $this->assertEquals($logger, $attr["test"]);
    }
    
     /**
     * @covers Itkg\Monitoring::logReport
     */
    public function testLogReport()
    {
        try {
            $logger = \Itkg\Log\Factory::getLogger(array(array('handler' => new EchoHandler())));
            $this->object->addLogger($logger, "test");
            $this->object->logReport();
        } catch(\Exception $e) {
            $this->fail($e->getMessage());
        }  
    }
    /**
     * @covers Itkg\Monitoring::log
     */
    public function testLog()
    {
        try {
            $logger = \Itkg\Log\Factory::getLogger(array(array('handler' => new EchoHandler())));
            $this->object->addLogger($logger, "test");
            $this->object->log("test");
        } catch(\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

        /**
     * @covers Itkg\Monitoring::getTest
     */
    public function testGetTest()
    {
        $logger = \Itkg\Log\Factory::getLogger(array(array('handler' => new EchoHandler())));
        $tests = $this->object->getTests();
        $this->assertInternalType("array", $tests);
    }
       
    /**
     * @covers Itkg\Monitoring::getReportForTest
     */
    public function testReportForTest()
    {
        \Itkg\Monitoring::getReportForTest($this->object);
    }
    
    /**
     * @covers Itkg\Monitoring::getException
     * @covers Itkg\Monitoring::setException
     */
    public function testException()
    {
        $exception = new \Exception('mon exception');
        
        $this->assertNull($this->object->getException());
        $this->object->setException($exception);
        $this->assertEquals($exception, $this->object->getException());
    }
    
    /**
     * @covers Itkg\Monitoring::addService
     * @covers Itkg\Monitoring::clear
     * @covers Itkg\Monitoring::getTests
     * @covers Itkg\Monitoring::getDuration
     * @covers Itkg\Monitoring::getService
     * @covers Itkg\Monitoring::isWorking
     * @covers Itkg\Monitoring::getException
     */
    public function testAddService()
    {
        \Itkg\Log::$config['DEFAULT_HANDLER'] = new \Itkg\Log\Handler\EchoHandler();
        \Itkg\Monitoring::clear();
        $this->assertEquals(array(), \Itkg\Monitoring::getTests());
        $service = new \Itkg\Mock\Service();
        $configuration = new \Itkg\Mock\Service\Configuration();
        $configuration->setIdentifier('IDENTIFIER');
        $service->setConfiguration($configuration);
        $this->object->addService($service, 'monitor');
        $tests = \Itkg\Monitoring::getTests();
        $this->assertEquals($tests[0], $this->object);
        $this->assertEquals('IDENTIFIER', $this->object->getIdentifier());
        $duration = $this->object->getEnd() - $this->object->getStart();
        $this->assertEquals($duration, $this->object->getDuration());
        $this->assertEquals($service, $this->object->getService());
        $this->assertTrue($this->object->isWorking());
    }
    
    /**
     * @covers Itkg\Monitoring::addTest
     * @covers Itkg\Monitoring::clear
     * @covers Itkg\Monitoring\Test::getMonitoring
     * @covers Itkg\Monitoring\Test::getIdentifier
     */
    public function testAddTest()
    {
        \Itkg\Monitoring::clear();
        $this->assertEquals(array(), \Itkg\Monitoring::getTests());
        $test = new \Itkg\Monitoring\EnvironnementTest('identifier', 'TEST');
        $tests = \Itkg\Monitoring::getTests();
        $this->assertEquals(1, sizeof($tests));
        $this->object = $test->getMonitoring();
        $this->assertEquals('identifier', $this->object->getIdentifier());
        $duration = $this->object->getEnd() - $this->object->getStart();
        
        $this->assertEquals($duration, $this->object->getDuration());
        $this->assertEquals($test, $this->object->getTest());
        $this->assertEquals($duration, $this->object->getDuration());
        $this->assertFalse($this->object->isWorking());
        $this->assertNotNull($this->object->getException());
    }
}