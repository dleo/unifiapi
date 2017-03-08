<?php


namespace Unifi;

/**
 * Class Api
 *
 * @author David López <dleo.lopez@gmail.com>
 */
class Api
{

    /**
     * @var string
     */
    public $user = "";

    /**
     * @var string
     */
    public $password = "";

    /**
     * @var string
     */
    public $site = "default";

    /**
     * @var string
     */
    public $baseurl = "https://127.0.0.1:8443";

    /**
     * @var string
     */
    public $controller = "3.2.8";

    /**
     * @var bool
     */
    public $isLoggedin = false;

    /**
     * @var string
     */
    private $cookies = "";

    /**
     * @var bool
     */
    public $debug = false;

    /**
     * @param string $user
     * @param string $password
     * @param string $baseurl
     * @param string $site
     * @param string $controller
     */
    function __construct($user = "", $password = "", $baseurl = "", $site = "", $controller = "")
    {
        (empty($user)) ? $this->user = env('UNIFI_USER', $this->user) : $this->user = $user;
        (empty($password)) ? $this->password = env('UNIFI_PASSWORD', $this->password) : $this->password = $password;
        (empty($baseurl)) ? $this->baseurl = env('UNIFI_BASEURL', $this->baseurl) : $this->baseurl = $baseurl;
        (empty($site)) ? $this->site = env('UNIFI_SITE', $this->site) : $this->site = $site;
        if (empty($controller)) {
            $controller = $controller = env('UNIFI_CONTROLLER', $this->controller);
        }
        $this->controller = $this->getControllerVersion($controller);
    }

    /**
     *
     */
    function __destruct()
    {
        if ($this->isLoggedin) {
            $this->logout();
        }
    }

    /**
     * Login to unifi Controller
     *
     * @return bool
     */
    public function login()
    {
        $this->cookies = "";
        $ch = $this->getCurlObj();
        curl_setopt($ch, CURLOPT_HEADER, 1);
        if ($this->controller >= 4) {
            //Controller 4
            curl_setopt($ch, CURLOPT_REFERER, $this->baseurl . "/login");
            curl_setopt($ch, CURLOPT_URL, $this->baseurl . "/api/login");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("username" => $this->user, "password" => $this->password)) . ":");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        } else {
            //Controller 3
            curl_setopt($ch, CURLOPT_URL, $this->baseurl . "/login");
            curl_setopt($ch, CURLOPT_POSTFIELDS, "login=login&username=" . $this->user . "&password=" . $this->password);
        }
        $content = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = trim(substr($content, $header_size));
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        preg_match_all('|Set-Cookie: (.*);|U', substr($content, 0, $header_size), $results);
        if (isset($results[1])) {
            $this->cookies = implode(';', $results[1]);
            if (($code >= 200) && ($code < 400)) {
                if (strpos($this->cookies, "unifises") !== false) {
                    $this->isLoggedin = true;
                }
            }
        }

        return $this->isLoggedin;
    }

    /**
     * Logout from unifi Controller
     * @return bool
     */
    public function logout()
    {
        if (!$this->isLoggedin) return false;
        $return = false;
        $content = $this->execCurl($this->baseurl . "/logout");
        $this->isLoggedin = false;
        $this->cookies = "";

        return $return;
    }

    /**
     * Authorize a mac address
     * paramater <mac address>,<minutes until expires from now>
     *
     * @param $mac
     * @param $minutes
     * @return bool true on success
     */
    public function authorizeGuest($mac, $minutes)
    {
        $mac = strtolower($mac);
        if (!$this->isLoggedin) {
            return false;
        }
        $return = false;
        $content = $this->execCurl($this->baseurl . "/api/s/" . $this->site . "/cmd/stamgr", "json={'cmd':'authorize-guest', 'mac':'" . $mac . "', 'minutes':" . $minutes . "}");
        $contentDecoded = json_decode($content);
        if (isset($contentDecoded->meta->rc)) {
            if ($contentDecoded->meta->rc == "ok") {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Unauthorize a mac address
     * paramater <mac address>
     * @param $mac
     * @return bool true on success
     */
    public function unauthorizeGuest($mac)
    {
        $mac = strtolower($mac);
        if (!$this->isLoggedin) return false;
        $return = false;
        $content = $this->execCurl($this->baseurl . "/api/s/" . $this->site . "/cmd/stamgr", "json={'cmd':'unauthorize-guest', 'mac':'" . $mac . "'}");
        $content_decoded = json_decode($content);
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == "ok") {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * reconnect a client
     *
     * @param $mac
     * @return bool true on success
     */
    public function reconnectSta($mac)
    {
        $mac = strtolower($mac);
        if (!$this->isLoggedin) return false;
        $return = false;
        $content = $this->execCurl($this->baseurl . "/api/s/" . $this->site . "/cmd/stamgr", "json={'cmd':'kick-sta', 'mac':'" . $mac . "'}");
        $content_decoded = json_decode($content);
        if (isset($content_decoded->meta->rc)) {
            if ($content_decoded->meta->rc == "ok") {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Block a client
     *
     * @param $mac
     * @return bool true on success
     */
    public function blockSta($mac)
    {
        $mac = strtolower($mac);
        if (!$this->isLoggedin) return false;
        $return = false;
        $content = $this->execCurl($this->baseurl . "/api/s/" . $this->site . "/cmd/stamgr", "json={'cmd':'block-sta', 'mac':'" . $mac . "'}");
        $contentDecoded = json_decode($content);
        if (isset($contentDecoded->meta->rc)) {
            if ($contentDecoded->meta->rc == "ok") {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Unblock a client
     *
     * @param $mac
     * @return bool
     */
    public function unblockSta($mac)
    {
        $mac = strtolower($mac);
        if (!$this->isLoggedin) return false;
        $return = false;
        $content = $this->execCurl($this->baseurl . "/api/s/" . $this->site . "/cmd/stamgr", "json={'cmd':'unblock-sta', 'mac':'" . $mac . "'}");
        $contentDecoded = json_decode($content);
        if (isset($contentDecoded->meta->rc)) {
            if ($contentDecoded->meta->rc == "ok") {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Reboot an access point
     *
     * @param $mac
     * @return bool
     */
    public function restartAp($mac)
    {
        $mac = strtolower($mac);
        if (!$this->isLoggedin) return false;
        $return = false;
        $content = $this->execCurl($this->baseurl . "/api/s/" . $this->site . "/cmd/devmgr", "json={'cmd':'restart', 'mac':'" . $mac . "'}");
        $contentDecoded = json_decode($content);
        if (isset($contentDecoded->meta->rc)) {
            if ($contentDecoded->meta->rc == "ok") {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * List access point
     *
     * @return array of access point objects
     */
    public function listAps()
    {
        $return = array();
        if (!$this->isLoggedin) return $return;
        $return = array();
        $content = $this->execCurl($this->baseurl . "/api/s/" . $this->site . "/stat/device", "json={}");
        $contentDecoded = json_decode($content);
        if (isset($contentDecoded->meta->rc)) {
            if ($contentDecoded->meta->rc == "ok") {
                if (is_array($contentDecoded->data)) {
                    foreach ($contentDecoded->data as $guest) {
                        $return[] = $guest;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List guests
     * @return array of guest objects
     */
    public function listGuests()
    {
        $return = array();
        if (!$this->isLoggedin) return $return;
        $return = array();
        $content = $this->execCurl($this->baseurl . "/api/s/" . $this->site . "/stat/guest", "json={}");
        $contentDecoded = json_decode($content);
        if (isset($contentDecoded->meta->rc)) {
            if ($contentDecoded->meta->rc == "ok") {
                if (is_array($contentDecoded->data)) {
                    foreach ($contentDecoded->data as $guest) {
                        $return[] = $guest;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * List vouchers
     * @param string $createTime
     * @return array of voucher object
     */
    public function getVouchers($createTime = "")
    {
        $return = array();
        if (!$this->isLoggedin) return $return;
        $return = array();
        $json = "";
        if (trim($createTime) != "") {
            $json .= "'create_time':" . $createTime . "";
        }
        $content = $this->execCurl($this->baseurl . "/api/s/" . $this->site . "/stat/voucher", "json={" . $json . "}");
        $contentDecoded = json_decode($content);
        if (isset($contentDecoded->meta->rc)) {
            if ($contentDecoded->meta->rc == "ok") {
                if (is_array($contentDecoded->data)) {
                    foreach ($contentDecoded->data as $voucher) {
                        $return[] = $voucher;
                    }
                }
            }
        }

        return $return;
    }
    
    /**
     * Device Stat 
     * @param string $mac
     * @return array of stat device object
     */
    public function getStatDevice($mac)
    {
        $return = array();
                
        $content = $this->execCurl($this->baseurl . "/api/s/" . $this->site . "/stat/user/". $mac);
        $contentDecoded = json_decode($content);
        if (isset($contentDecoded->meta->rc)) {
            if ($contentDecoded->meta->rc == "ok") {
                if (is_array($contentDecoded->data)) {
                    foreach ($contentDecoded->data as $stat) {
                        $return[] = $stat;
                    }
                }
            }
        }
        
        return $return;
    }

    /**
     * All Stat 
     * @return array of stat device object
     */
    public function getStat()
    {
        $return = array();
        
        $content = $this->execCurl($this->baseurl . "/api/s/" . $this->site . "/stat/sta");
        $contentDecoded = json_decode($content);
        if (isset($contentDecoded->meta->rc)) {
            if ($contentDecoded->meta->rc == "ok") {
                if (is_array($contentDecoded->data)) {
                    foreach ($contentDecoded->data as $stat) {
                        $return[] = $stat;
                    }
                }
            }
        }
        
        return $return;
    }
    
    /**
     * Create a voucher
     * @param $minutes
     * @param int $numberOfVouchersToCreate
     * @param string $note
     * @param int $up
     * @param int $down
     * @param int $Mbytes
     * @return array of vouchers codes (Note: without the "-" in the middle)
     */
    public function createVoucher($minutes, $numberOfVouchersToCreate = 1, $note = "", $up = 0, $down = 0, $Mbytes = 0)
    {
        $return = array();
        if (!$this->isLoggedin) return $return;
        $json = "'cmd':'create-voucher','expire':" . $minutes . ",'n':" . $numberOfVouchersToCreate . "";
        if (trim($note) != "") {
            $json .= ",'note':'" . $note . "'";
        }
        if ($up > 0) {
            $json .= ",'up':" . $up . "";
        }
        if ($down > 0) {
            $json .= ", 'down':" . $down . "";
        }
        if ($Mbytes > 0) {
            $json .= ", 'bytes':" . $Mbytes . "";
        }
        $content = $this->execCurl($this->baseurl . "/api/s/" . $this->site . "/cmd/hotspot", "json={" . $json . "}");
        $contentDecoded = json_decode($content);
        if ($contentDecoded->meta->rc == "ok") {
            if (is_array($contentDecoded->data)) {
                $obj = $contentDecoded->data[0];
                foreach ($this->getVouchers($obj->createTime) as $voucher) {
                    $return[] = $voucher->code;
                }
            }
        }

        return $return;
    }

    /**
     * Exec curl to API Controller
     *
     * @param $url
     * @param string $data
     * @return mixed
     */
    private function execCurl($url, $data = "")
    {
        $ch = $this->getCurlObj();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (trim($data) != "") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_POST, false);
        }
        $content = curl_exec($ch);
        if ($this->debug == true) {
            print "---------------------\n<br>\n";
            print $url . "\n<br>\n";
            print $data . "\n<br>\n";
            print "---------------------\n<br>\n";
            print $content . "\n<br>\n";
            print "---------------------\n<br>\n";
        }
        curl_close($ch);

        return $content;
    }

    /**
     * @return resource
     */
    private function getCurlObj()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->debug == true) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }
        if ($this->cookies != "") {
            curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        }

        return $ch;
    }

    /**
     * Get the version of unifi controller
     *
     * @param $controller
     * @return mixed|>3 support for these API
     */
    private function getControllerVersion($controller)
    {
        if (strpos($controller, ".") !== false) {
            $conVer = explode(".", $controller);
            $controller = $conVer[0];

            return $controller;
        }

        return "3";
    }

}
