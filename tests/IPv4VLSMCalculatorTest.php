<?php

require_once '../IPv4VLSMCalculator.php';

class IPv4VLSMCalculatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * IPv4VLSMCalculator instance
     * 
     * @var IPv4VLSMCalculator 
     */
    protected $_calculator = null;
    
    public function setUp()
    {
        $this->_calculator = new IPv4VLSMCalculator('172.16.0.0', '16');
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorThrowsExceptionIfAddressIsInvalid()
    {
        new IPv4VLSMCalculator('192.168.1.256', 24);
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorThrowsExceptionIfMaskIsInvalid()
    {
        new IPv4VLSMCalculator('192.168.1.0', -24);
    }

    public function testConstructorNotThrowsExceptionIfNothingIsSpecified()
    {
        $this->assertInstanceOf('IPv4VLSMCalculator', new IPv4VLSMCalculator());
    }

    public function testgetNetworkAddressReturnsNetwork()
    {
        $this->assertEquals('172.16.0.0', $this->_calculator->getNetworkAddress());
    }
    
    public function testgetNetworkMaskReturnsNetworkMask()
    {
        $this->assertEquals('16', $this->_calculator->getNetworkMask());
    }
    
    public function testsetNetworkAddressReturnsSelf()
    {
        $this->assertInstanceOf('IPv4VLSMCalculator', 
            $this->_calculator->setNetworkAddress('172.16.0.0'));
    }
    
    public function testsetNetworkMaskReturnsSelf()
    {
        $this->assertInstanceOf('IPv4VLSMCalculator', 
            $this->_calculator->setNetworkMask('16'));
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testsubnetNetworkThrowsBadMethodCallExceptionIfNetworkIsNotSpecified()
    {
        $calculator = new IPv4VLSMCalculator();
        $calculator->subnetNetwork(array());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testsubnetNetworkThrowsInvalidArgumentExceptionIfNotEnoughtHosts()
    {
        $this->_calculator->subnetNetwork(array(65535));
    }

    public function testsubnetNetworkReturnOrderedNetworksBySize()
    {
        $networks = $this->_calculator
            ->subnetNetwork(array('medium' => 20, 'small' => 10,'large' => 30));

        $names = array_keys($networks);

        $this->assertEquals('large',  $names[0]);
        $this->assertEquals('medium', $names[1]);
        $this->assertEquals('small',  $names[2]);
    }

    public function testsubnetNetworkReturnEachSubnetWithEssentialData()
    {
        $networks = $this->_calculator
            ->subnetNetwork(array('medium' => 20, 'small' => 10,'large' => 30));

        $network = current($networks);

        $this->assertArrayHasKey('subnet',    $network);
        $this->assertArrayHasKey('cidr',      $network);
        $this->assertArrayHasKey('mask',      $network);
        $this->assertArrayHasKey('wildcart',  $network);
        $this->assertArrayHasKey('size',      $network);
        $this->assertArrayHasKey('firstHost', $network);
        $this->assertArrayHasKey('lastHost',  $network);
        $this->assertArrayHasKey('broadcast', $network);
    }

    public function testsubnetNetworkCalculatesEachSubnetNetwork()
    {
        $expected = array(
            0 => '172.16.0.0',
            1 => '172.16.1.0',
            2 => '172.16.1.64',
            3 => '172.16.1.128'
        );
        $networks = $this->_calculator
            ->subnetNetwork(array(254, 62, 32, 15));

        foreach($networks as $i => $actual) {
            $this->assertEquals($expected[$i], $actual['subnet']);
        }
    }

    public function testsubnetNetworkCalculatesEachSubnetCIDR()
    {
        $expected = array(
            0 => '24',
            1 => '26',
            2 => '26',
            3 => '27'
        );
        $networks = $this->_calculator
            ->subnetNetwork(array(254, 62, 32, 15));

        foreach($networks as $i => $actual) {
            $this->assertEquals($expected[$i], $actual['cidr']);
        }
    }

    public function testsubnetNetworkCalculatesEachSubnetNetworkMask()
    {
        $expected = array(
            0 => '255.255.255.0',
            1 => '255.255.255.192',
            2 => '255.255.255.192',
            3 => '255.255.255.224'
        );
        $networks = $this->_calculator
            ->subnetNetwork(array(254, 62, 32, 15));

        foreach($networks as $i => $actual) {
            $this->assertEquals($expected[$i], $actual['mask']);
        }
    }

    public function testsubnetNetworkCalculatesEachSubnetWildcart()
    {
        $expected = array(
            0 => '0.0.0.255',
            1 => '0.0.0.63',
            2 => '0.0.0.63',
            3 => '0.0.0.31'
        );
        $networks = $this->_calculator
            ->subnetNetwork(array(254, 62, 32, 15));

        foreach($networks as $i => $actual) {
            $this->assertEquals($expected[$i], $actual['wildcart']);
        }
    }

    public function testsubnetNetworkCalculatesEachSubnetSize()
    {
        $expected = array(
            0 => '254',
            1 => '62',
            2 => '62',
            3 => '30'
        );
        $networks = $this->_calculator
            ->subnetNetwork(array(254, 62, 32, 15));

        foreach($networks as $i => $actual) {
            $this->assertEquals($expected[$i], $actual['size']);
        }
    }

    public function testsubnetNetworkCalculatesEachFirstHost()
    {
        $expected = array(
            0 => '172.16.0.1',
            1 => '172.16.1.1',
            2 => '172.16.1.65',
            3 => '172.16.1.129'
        );
        $networks = $this->_calculator
            ->subnetNetwork(array(254, 62, 32, 15));

        foreach($networks as $i => $actual) {
            $this->assertEquals($expected[$i], $actual['firstHost']);
        }
    }

    public function testsubnetNetworkCalculatesEachLastHost()
    {
        $expected = array(
            0 => '172.16.0.254',
            1 => '172.16.1.62',
            2 => '172.16.1.126',
            3 => '172.16.1.158'
        );
        $networks = $this->_calculator
            ->subnetNetwork(array(254, 62, 32, 15));

        foreach($networks as $i => $actual) {
            $this->assertEquals($expected[$i], $actual['lastHost']);
        }
    }

    public function testsubnetNetworkCalculatesEachBroadcast()
    {
        $expected = array(
            0 => '172.16.0.255',
            1 => '172.16.1.63',
            2 => '172.16.1.127',
            3 => '172.16.1.159'
        );
        $networks = $this->_calculator
            ->subnetNetwork(array(254, 62, 32, 15));

        foreach($networks as $i => $actual) {
            $this->assertEquals($expected[$i], $actual['broadcast']);
        }
    }

    public function testisIPAddressInNetworkReturnsTrueIfIPisInNetwork()
    {
        $actual = $this->_calculator->isIPAddressInNetwork('172.16.0.1');
        $this->assertTrue($actual);

        $actual = $this->_calculator->isIPAddressInNetwork('172.16.254.254');
        $this->assertTrue($actual);
    }

    public function testisIPAddressInNetworkReturnsFalseIfIPisNotInNetwork()
    {
        $this->_calculator->setNetworkMask(28);
        $actual = $this->_calculator->isIPAddressInNetwork('172.16.0.15');
        $this->assertFalse($actual);
        
        $this->_calculator->setNetworkMask(30);
        $actual = $this->_calculator->isIPAddressInNetwork('172.16.0.4');
        $this->assertFalse($actual);
    }

    public function testsummarizeRoutesReturnFalseIfLessThanTwoRoutesGiven()
    {
        $this->assertFalse($this->_calculator->summarizeRoutes(array(1)));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testsummarizeRoutesThrowsInvalidArgumentExceptionIfInvalidRouteGiven()
    {
        $this->_calculator->summarizeRoutes(array('172.16.0.0','172.16.0.256'));
    }
    
    public function testsummarizeRoutesSummarizeBClassRoutes()
    {
        $routes = $this->_calculator->summarizeRoutes(array(
            '172.16.12.0',
            '172.16.13.0',
            '172.16.14.0',
            '172.16.15.0',
        ));
        $this->assertEquals('172.16.12.0/22', $routes);    
    }
    
    public function testsummarizeRoutesSummarizeCClassRoutes()
    {
        $routes = $this->_calculator->summarizeRoutes(array(
            '192.168.32.0',
            '192.168.63.0',
        ));
        $this->assertEquals('192.168.32.0/19', $routes);    
    }    

    public function tearDown()
    {
        $this->_calculator = null;
    }
}