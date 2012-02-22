<?php
/**
 * IPv4VLSMCalculator - aims to automate Variable Length Subnet Masking(VLSM)
 * calculation process using PHP. Provides essential functionality for rapid
 * subnetting, supernetting, and some other userful features.
 *
 * @package IPv4VLSMCalculator
 * @author  Bernard Baltrusaitis <bernard@runawaylover.info>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0
 * @link    https://github.com/b-b3rn4rd/IPv4VLSMCalculator
 */
class IPv4VLSMCalculator
{
    /**
     * Network ip address
     *
     * @var string 
     */
    private $_networkAddress = null;

    /**
     * Network mask (CIDR)
     * 
     * @var int
     */
    private $_networkMask = null;

    /**
     * Sets initial network ip address and mask (CIDR)
     * <code>
     * $calculator = new IPv4VLSMCalculator('192.168.1.0', 24);
     * </code>
     *
     * @param string $networkAddress network address ip
     * @param int    $networkMask    network cidr mask
     */
    public function __construct($networkAddress = null, $networkMask = null)
    {
        if (!is_null($networkAddress)) {
            $this->setNetworkAddress($networkAddress);
        }

        if (!is_null($networkMask)) {
            $this->setNetworkMask($networkMask);
        }
    }

    /**
     * Get given network address
     * 
     * @return string network address
     */
    public function getNetworkAddress()
    {
        return $this->_networkAddress;
    }

    /**
     * Get given network CIDR
     * 
     * @return int network CIDR 
     */
    public function getNetworkMask()
    {
        return $this->_networkMask;
    }

    /**
     * Set given network address
     * 
     * @param string $networkAddress network address
     * 
     * @return IPv4Calculator
     * @throws InvalidArgumentException if $networkAddress is invalid ip address 
     */
    public function setNetworkAddress($networkAddress)
    {
        if (!$this->_isValidIPAddress($networkAddress)) {
            throw new InvalidArgumentException(
                sprintf('`%s` is invalid IPv4 address', $networkAddress));
        }
        
        $this->_networkAddress = $networkAddress;
        return $this;
    }

    /**
     * Set given network CIDR value
     * 
     * @param int $networkMask network CIDR
     * 
     * @return IPv4Calculator
     * @throws InvalidArgumentException if $networkMask is invalid 
     */
    public function setNetworkMask($networkMask)
    {
        if (!$this->_isValidCIDR($networkMask)) {
            throw new InvalidArgumentException(
                sprintf('`%s` is invalid CIDR', $networkMask));
        }
        
        $this->_networkMask = $networkMask;
        return $this;
    }

    /**
     * Subnets current network into smaller networks using VLSM
     * <code>
     * $calculator = new IPv4VLSMCalculator('192.168.1.0', 24);
     * $subnets    = $calculator->subnetNetwork(array(
     *     'subnetA' => 120,
     *     'subnetB' => 30,
     *     'subnetC' => 10,
     *     'link1'   => 2,
     *     'link2'   => 2
     * ));
     * </code>
     *
     * @param array $subnetsSizes array of subnets sizes
     * 
     * @return array array of subnets
     * @throws BadMethodCallException if network address or mask is not specified
     * @throws InvalidArgumentException if sum of $subnetsSizes is more than hosts available
     */
    public function subnetNetwork(array $subnetsSizes)
    {
        $return = array();

        if (!$this->_isNetworkSpecified()) {
            throw new BadMethodCallException(
                'Network address and/or mask are not specified');
        }

        $max = $this->_getSubnetUsableHostsCount(
            $this->getNetworkMask());
        $total = array_sum($subnetsSizes);
        if ($max < $total) {
            throw New InvalidArgumentException(
                sprintf('Can\'t subnet `%s`, `\%d` is not enough for `%d` hosts',
                    $this->getNetworkAddress(), $this->getNetworkMask(), $total));
        }

        arsort($subnetsSizes);
        $long = ip2long($this->getNetworkAddress());
        
        foreach ($subnetsSizes as $name => $size) {
            $subnet = $this->_createSubnet($size, $long);
            $long   = ip2long($subnet['subnet'])+$subnet['size']+2;
            $return[$name] = $subnet;
        }

        return $return;
    }
    
    /**
     * Checks if given ip address is in current network
     *
     * @param string $ipAddress ip address
     * 
     * @return boolean true if in range
     */
    public function isIPAddressInNetwork($ipAddress)
    {
        $first = $this->_getSubnetFirstUsableHost($this->getNetworkAddress(),
            $this->getNetworkMask());

        $last = $this->_getSubnetLastUsableHost($this->getNetworkAddress(),
            $this->getNetworkMask());
        
        $long = ip2long($ipAddress);
        
        return (ip2long($first) <= $long
            && $long <= ip2long($last));
    }

    /**
     * Summarizes given routes into supernetwork. Minimum 2 routes is required.
     * <code>
     * $routes = array(
     *     '172.16.168.0',
     *     '172.16.169.0',
     *     '172.16.170.0',
     *     '172.16.171.0',
     *     '172.16.172.0',
     *     '172.16.173.0',
     *     '172.16.174.0',
     *     '172.16.175.0'
     * );
     * $calculator   = new IPv4VLSMCalculator('192.168.1.0', 24);
     * $supernetwork = $calculator->summarizeRoutes($routes);
     * // 172.16.168.0/21
     * </code>
     *
     * @param array $routes array of routes
     *
     * @return string|false supernetwork ip address and mask CIDR
     * @throws InvalidArgumentException if $routes has invalid ip address
     */
    public function summarizeRoutes(array $routes)
    {
        $cidr   = 0;
        $routes = array_values($routes);
        
        if (2 > count($routes)) {
            return false;
        }

        $network = ip2long(array_shift($routes));
        
        foreach ($routes as $route) {
            if (!$this->_isValidIPAddress($route)) {
                throw new InvalidArgumentException(
                    sprintf('`%s` is invalid IPv4 address', $route));
            }
            
            $network = $network & ip2long($route);
        }

        $network = long2ip($network);
        $cidr    = $this->_countCommonBits(
            $this->_convertIPAddressToBinary($network),
            $this->_convertIPAddressToBinary($route));
        
        return sprintf('%s/%d', $network, $cidr);
    }

    /**
     * Finds smallest suitable CIDR for $networkSize
     *
     * @param int $networkSize subnet size
     *
     * @return int network mask (CIDR)
     */
    private function _findSmallestSuitableCIDR($networkSize)
    {
        $i = 0;
        do {
            ++$i;
        } while ((pow(2, $i)-2) < $networkSize);

        return $this->getNetworkMask() + (32-$this->getNetworkMask()-$i);
    }

    /**
     * Creates subnet using given $size and network address
     *
     * @param int $size              subnet size
     * @param int $subnetAddressLong network ip in int
     *
     * @todo refactor this method, to reduce 'CRAP' index
     * @return array subnet
     */
    private function _createSubnet($size, $subnetAddressLong)
    {
        $cidr   = $this->_findSmallestSuitableCIDR($size);
        $mask   = $this->_convertCIDRToNetworkMask($cidr);
        $subnet = long2ip($subnetAddressLong);

        return array(
            'subnet'      => $subnet,
            'cidr'        => $cidr,
            'mask'        => $mask,
            'wildcart'    => $this->_convertNetworkMaskToWildcart($mask),
            'size'        => $this->_getSubnetUsableHostsCount($cidr),
            'firstHost'   => $this->_getSubnetFirstUsableHost($subnet, $cidr),
            'lastHost'    => $this->_getSubnetLastUsableHost($subnet, $cidr),
            'broadcast'   => $this->_getSubnetBroadcastAddress($subnet, $cidr)
        );
    }

    /**
     * Checks if network address and mask is specified
     * 
     * @return boolean 
     */
    private function _isNetworkSpecified()
    {
        return ($this->getNetworkAddress() && $this->getNetworkMask());
    }

    /**
     * Converts given network mask into CIDR
     *
     * @param string $networkMask network mask ip address
     *
     * @return int CIDR
     */
    private function _convertNetworkMaskToCIDR($networkMask)
    {
        $binary = $this->_convertIPAddressToBinary($networkMask);
        return substr_count($binary, 1);
    }

    /**
     * Converts given CIDR into network mask
     *
     * @param int $cidr network CIDR
     *
     * @return string  network mask ip address
     */
    private function _convertCIDRToNetworkMask($cidr)
    {
        return long2ip(pow(2, 32)-1<<32-$cidr);
    }

    /**
     * Converts given ip address into its binary representation
     *
     * @param string $ipAddress ip address
     *
     * @return int    binary representation
     */
    private function _convertIPAddressToBinary($ipAddress)
    {
        return base_convert(ip2long($ipAddress), 10, 2);
    }

    /**
     * Converts given ip in binary into normal representation
     *
     * @param string $binary ip address in binary representation
     *
     * @return string ip address
     */
    private function _convertBinaryToIPAddress($binary)
    {
        return long2ip(base_convert($binary, 2, 10));
    }

    /**
     * Checks if given ip address is valid
     *
     * @param string $ipAddress ip address
     *
     * @return boolean true if valid
     */
    private function _isValidIPAddress($ipAddress)
    {
        return filter_var($ipAddress, FILTER_VALIDATE_IP);
    }

    /**
     * Checks if given CIDR is valid
     *
     * @param int $cidr CIDR
     *
     * @return boolean true if valid
     */
    private function _isValidCIDR($cidr)
    {
        return (is_numeric($cidr) && $cidr >=0 && $cidr <= 32);
    }
    
    /**
     * Converts given network mask into wildcart
     *
     * @param string $networkMask network mask
     * 
     * @return string network wildcart
     */
    private function _convertNetworkMaskToWildcart($networkMask)
    {
        return long2ip(pow(2, 32)-1 ^ ip2long($networkMask));
    }
    
    /**
     * Calculates hosts count by given network CIDR
     * 
     * @param int $cidr network CIDR
     * 
     * @see getSubnetUsableHostsCount() how to get usable hosts count
     * @return int host count
     */
    private function _getSubnetHostsCount($cidr)
    {
        return pow(2, 32-$cidr);
    }
    
    /**
     * Calculates usable hosts count by given network CIDR
     * 
     * @param int $cidr network CIDR
     * 
     * @return int usable hosts count
     */
    private function _getSubnetUsableHostsCount($cidr)
    {
        return $this->_getSubnetHostsCount($cidr)-2;
    }
    
    /**
     * Calculates first usable address in given subnet
     * 
     * @param string $network network ip address
     * @param int    $cidr    network CIDR
     * 
     * @return string first usable address
     */
    private function _getSubnetFirstUsableHost($network, $cidr)
    {
        $long = ip2long($network) & (pow(2, 32)-1<<32-$cidr);
        return long2ip($long+1);
    }
    
    /**
     * Calculates last usable address in given subnet
     *
     * @param string $network network ip address
     * @param int    $cidr    network CIDR
     * 
     * @return string last usable address
     */
    private function _getSubnetLastUsableHost($network, $cidr)
    {
        $size = $this->_getSubnetUsableHostsCount($cidr);
        $long = ip2long($network) & (pow(2, 32)-1<<32-$cidr);
        return long2ip($long+$size);
    }
    
    /**
     * Calculates given subnet address for given network
     *
     * @param string $network network address
     * @param int    $cidr    network CIDR
     * 
     * @return string broadcast address 
     */
    private function _getSubnetBroadcastAddress($network, $cidr)
    {
        $host = $this->_getSubnetLastUsableHost($network, $cidr);
        return long2ip(ip2long($host)+1);
    }

    /**
     * Calculates common bits between $binary1 and $binary2
     *
     * @param string $binary1 binary string
     * @param string $binary2 binary string
     * 
     * @todo find a better way of doing this
     * @return int common bits count
     */
    private function _countCommonBits($binary1, $binary2)
    {
        $return = 0;

        for ($i = 0 ; $i <= strlen($binary1) ; $i++) {
            if (!isset($binary2[$i]) || !isset($binary2[$i])) {
                return $return;
            }

            if ($binary1[$i] == $binary2[$i]) {
                $return++;
            } else {
                return $return;
            }
        }
    }
}
