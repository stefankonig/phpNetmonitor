<?php
/**
 * Monitor.php
 *
 * Simple monitoring script that uses pushover to send a notification on statuschange
 * this file should be executed by a wrapperscript  that continually calls it. or Crontab if 1 time per minute is enough
 *
 * @package phpNetmonitor
 */
 
/**
 * @author Stefan Konig <github@seosepa.net>
 */
class Monitor
{
    /**
     * @var array $checkArray
     */
    private $checkArray = array();

    /**
     * @var string $onlineArray
     */
    private $onlineArray = '';

    /**
     * @var string $offlineArray
     */
    private $offlineArray = '';

    /**
     * @param array $offlineArray
     */
    public function setOfflineArray($offlineArray)
    {
        $this->offlineArray = json_encode($offlineArray);
        file_put_contents('/tmp/offline', $this->offlineArray);
    }

    /**
     * @return array
     */
    public function getOfflineArray()
    {
        if ($this->offlineArray == '') {
            return array();
        }
        return json_decode($this->offlineArray);
    }

    /**
     * @param array $onlineArray
     */
    public function setOnlineArray($onlineArray)
    {
        $this->onlineArray = json_encode($onlineArray);
        file_put_contents('/tmp/online', $this->onlineArray);
    }

    /**
     * @return array
     */
    public function getOnlineArray()
    {
        if ($this->onlineArray == '') {
            return array();
        }
        return json_decode($this->onlineArray);
    }

    /**
     * @param array $checkArray
     */
    public function setCheckArray($checkArray)
    {
        $this->checkArray = $checkArray;
    }

    /**
     * @return array
     */
    public function getCheckArray()
    {
        return $this->checkArray;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        // switchName => IP-Address
        $this->setCheckArray(
            array(
                'ACCESS01' => '172.16.6.11',
                'ACCESS02' => '172.16.6.12',
                'ACCESS03' => '172.16.6.13',
                'ACCESS04' => '172.16.6.14',
                'ACCESS05' => '172.16.6.15',
                'ACCESS06' => '172.16.6.16',
                'ACCESS07' => '172.16.6.17',
            )
        );

        if (file_exists('/tmp/online')) {

            $online            = file_get_contents('/tmp/online');
            $this->onlineArray = $online;
        }
        if (file_exists('/tmp/offline')) {

            $offline            = file_get_contents('/tmp/offline');
            $this->offlineArray = $offline;
        }
    }

    /**
     * check all servers for status
     */
    public function checkServers()
    {
        $online  = array();
        $offline = array();

        foreach ($this->getCheckArray() as $host => $ip) {
            $ping = $this->ping($ip);
            if ($ping) {
                $online[] = $host;
                echo "Host {$host} with ip {$ip} is online" . PHP_EOL;
            } else {
                $offline[] = $host;
                echo "Host {$host} with ip {$ip} is offline" . PHP_EOL;
            }
        }

        $wasOnline     = array_diff($this->getOnlineArray(), $online);
        $becameOnline  = array_diff($online, $this->getOnlineArray());
        $wasOffline    = array_diff($this->getOfflineArray(), $offline);
        $becameOffline = array_diff($offline, $this->getOfflineArray());

        foreach ($becameOnline as $onlineHost) {
            if (in_array($onlineHost, $wasOffline)) {
                $hostname = array_search($onlineHost, $this->getCheckArray());
                if ($hostname == '') {
                    $hostname = $onlineHost;
                }
                $this->alert("{$hostname} is now ONLINE");
            }
        }

        foreach ($becameOffline as $offlineHost) {
            if (in_array($offlineHost, $wasOnline)) {
                $hostname = array_search($offlineHost, $this->getCheckArray());
                if ($hostname == '') {
                    $hostname = $offlineHost;
                }
                $this->alert("{$hostname} is now OFFLINE");
            }
        }

        // save current statuses for next run
        $this->setOnlineArray($online);
        $this->setOfflineArray($offline);
    }

    /**
     * @param string $message
     */
    public function alert($message)
    {
        echo $message . PHP_EOL;

        $priority = 0;
        if (strpos($message, 'OFFLINE')) {
            $priority = 1;
        }

        curl_setopt_array(
            $ch = curl_init(),
            array(
                CURLOPT_URL        => "https://api.pushover.net/1/messages.json",
                CURLOPT_POSTFIELDS => array(
                    "token"     => "<INSERT OWN TOKEN>",
                    "user"      => "<INSERT OWN USER>",
                    "title"     => "Netmonitor",
                    "message"   => $message,
                    "priority"  => $priority,
                    "timestamp" => time(),
                )
            )
        );

        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * @param $host
     * @return boolean
     */
    public function ping($host)
    {
        exec("ping -c 2 " . $host, $output, $result);
        if (stripos(implode('', $output), '64 bytes') === false) {
            return false;
        } else {
            return true;
        }
    }
}

// standalone script. so init class and run immediately
$monitor = new Monitor();
$monitor->checkServers();
