IPv4VLSMCalculator - aims to automate Variable Length Subnet Masking(VLSM) calculation process using PHP. 
Provides essential functionality for rapid subnetting, supernetting, and some other userful features.

<ul>
<li><a href="#subnetting">Subnetting</a></li>
<li><a href="#supernetting">Supernetting</a></li>
<li><a href="#checkingip">Checking if IP is within subnet</a></li>
</ul>

<h3 id="subnetting">Subnetting</h3>
<b>Example:</b> You have been given the network address <u>192.168.1.0/24</u> to subnet. The network addressing requirements are displayed in the topology diagram below:
<img src="http://farm8.staticflickr.com/7196/6919992193_b56d0124aa.jpg" alt="network topology" /><br/>

Using <b>VLSMCalculator</b> calculator:
<pre><code>require_once 'IPv4VLSMCalculator.php';
$calculator = new IPv4VLSMCalculator('192.168.1.0', '24');
$subnets    = $calculator->subnetNetwork(array(
    'wan1'   => 2, 
    'wan2'   => 2, 
    'wan3'   => 2, 
    'large'  => 60, 
    'medium' => 20, 
    'small'  => 14));
    
echo sprintf('&lt;pre>%s&lt;/pre>', print_r($subnets, true));</code></pre>
Produces output:
<pre><code>Array
(
    [large] => Array
        (
            [subnet]    => <b>192.168.1.0</b>
            [cidr]      => <b>26</b>
            [mask]      => <b>255.255.255.192</b>
            [wildcart]  => 0.0.0.63
            [size]      => 62
            [firstHost] => 192.168.1.1
            [lastHost]  => 192.168.1.62
            [broadcast] => 192.168.1.63
        )

    [medium] => Array
        (
            [subnet]    => <b>192.168.1.64</b>
            [cidr]      => <b>27</b>
            [mask]      => <b>255.255.255.224</b>
            [wildcart]  => 0.0.0.31
            [size]      => 30
            [firstHost] => 192.168.1.65
            [lastHost]  => 192.168.1.94
            [broadcast] => 192.168.1.95
        )

    [small] => Array
        (
            [subnet]    => <b>192.168.1.96</b>
            [cidr]      => <b>28</b>
            [mask]      => <b>255.255.255.240</b>
            [wildcart]  => 0.0.0.15
            [size]      => 14
            [firstHost] => 192.168.1.97
            [lastHost]  => 192.168.1.110
            [broadcast] => 192.168.1.111
        )

    [wan3] => Array
        (
            [subnet]    => <b>192.168.1.112</b>
            [cidr]      => <b>30</b>
            [mask]      => <b>255.255.255.252</b>
            [wildcart]  => 0.0.0.3
            [size]      => 2
            [firstHost] => 192.168.1.113
            [lastHost]  => 192.168.1.114
            [broadcast] => 192.168.1.115
        )

    [wan2] => Array
        (
            [subnet]    => <b>192.168.1.116</b>
            [cidr]      => <b>30</b>
            [mask]      => <b>255.255.255.252</b>
            [wildcart]  => 0.0.0.3
            [size]      => 2
            [firstHost] => 192.168.1.117
            [lastHost]  => 192.168.1.118
            [broadcast] => 192.168.1.119
        )

    [wan1] => Array
        (
            [subnet]    => <b>192.168.1.120</b>
            [cidr]      => <b>30</b>
            [mask]      => <b>255.255.255.252</b>
            [wildcart]  => 0.0.0.3
            [size]      => 2
            [firstHost] => 192.168.1.121
            [lastHost]  => 192.168.1.122
            [broadcast] => 192.168.1.123
        )
)
</code></pre>

<h3 id="supernetting">Supernetting</h3>    
IPv4VLSMCalculator allows to summarize routes. Read more about <a href="http://en.wikipedia.org/wiki/Supernetwork">supernetwork</a>.
<pre><code>require_once 'IPv4VLSMCalculator.php';
$calculator   = new IPv4VLSMCalculator();
$supernetwork = $calculator->summarizeRoutes(array(
    '172.16.12.0',
    '172.16.13.0',
    '172.16.14.0',
    '172.16.15.0',
));
echo $supernetwork; // 172.16.12.0/22</code></pre>

<h3 id="checkingip">Checking if IP is within subnet</h3>
<pre><code>require_once 'IPv4VLSMCalculator.php';
$calculator   = new IPv4VLSMCalculator('192.168.1.0', '28');
$calculator->isIPAddressInNetwork('192.168.1.14'); // true
$calculator->isIPAddressInNetwork('192.168.1.17'); // false</code></pre>
