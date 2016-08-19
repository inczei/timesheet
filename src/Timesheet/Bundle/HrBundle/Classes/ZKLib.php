<?php    
namespace Timesheet\Bundle\HrBundle\Classes;

class ZKLib {
        public $ip;
        public $port;
        public $zkclient;
        
        public $data_recv = '';
        public $session_id = 0;
        public $userdata = array();
        public $attendancedata = array();
        public $freesizesdata = array();
        
        
        public function __construct($ip, $port) {
        	$this->ip = $ip;
            $this->port = $port;
            
            $this->zkclient = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            
            $timeout = array('sec'=>60,'usec'=>60000);
            socket_set_option($this->zkclient,SOL_SOCKET,SO_RCVTIMEO,$timeout);
        
            include_once("zkconst.php");
            include_once("zkconnect.php");
            include_once("zkversion.php");
            include_once("zkos.php");
            include_once("zkplatform.php");
            include_once("zkworkcode.php");
            include_once("zkssr.php");
            include_once("zkpin.php");
            include_once("zkface.php");
            include_once("zkserialnumber.php");
            include_once("zkdevice.php");
            include_once("zkuser.php");
            include_once("zkattendance.php");
            include_once("zktime.php");
        }
        
        
        function reverse_hex($hexstr) {
        	$tmp = '';
        
        	for ($i=strlen($hexstr); $i>=0; $i--) {
        		$tmp .= substr($hexstr, $i, 2);
        		$i--;
        	}
        
        	return $tmp;
        }
        
        
        function createChkSum($p) {
            /*This function calculates the chksum of the packet to be sent to the 
            time clock
            
            Copied from zkemsdk.c*/
            
            $l = count($p);
            $chksum = 0;
            $i = $l;
            $j = 1;
            while ($i > 1) {
                $u = unpack('S', pack('C2', $p['c'.$j], $p['c'.($j+1)] ) );
                
                $chksum += $u[1];
                
                if ( $chksum > USHRT_MAX )
                    $chksum -= USHRT_MAX;
                $i-=2;
                $j+=2;
            }
            
            if ($i)
                $chksum = $chksum + $p['c'.strval(count($p))];
            
            while ($chksum > USHRT_MAX)
                $chksum -= USHRT_MAX;
            
            if ( $chksum > 0 ) 
                $chksum = -($chksum);
            else
                $chksum = abs($chksum);
                
            $chksum -= 1;
            while ($chksum < 0)
                $chksum += USHRT_MAX;
            
            return pack('S', $chksum);
        }

        function createHeader($command, $chksum, $session_id, $reply_id, $command_string) {
            /*This function puts a the parts that make up a packet together and 
            packs them into a byte string*/
            $buf = pack('SSSS', $command, $chksum, $session_id, $reply_id).$command_string;
            
            $buf = unpack('C'.(8+strlen($command_string)).'c', $buf);
            
            $u = unpack('S', $this->createChkSum($buf));
            
            if ( is_array( $u ) ) {
                while( list( $key ) = each( $u ) ) {
                    $u = $u[$key];
                    break;
                }
            }
            $chksum = $u;
            
            $reply_id += 1;
            
            if ($reply_id >= USHRT_MAX)
                $reply_id -= USHRT_MAX;
            
            $buf = pack('SSSS', $command, $chksum, $session_id, $reply_id);
            
            return $buf.$command_string;
        
        }
    
        function checkValid($reply) {
            /*Checks a returned packet to see if it returned CMD_ACK_OK,
            indicating success*/
            $u = unpack('H2h1/H2h2', substr($reply, 0, 8) );
            
            $command = hexdec( $u['h2'].$u['h1'] );
            if ($command == CMD_ACK_OK)
                return TRUE;
            else
                return FALSE;
        }
        
        public function connect() {
            return zkconnect($this);
        }
        
        public function disconnect() {
            return zkdisconnect($this);
        }
        
        public function version() {
            return zkversion($this);
        }
        
        
        public function osversion() {
            return zkos($this);
        }
        /*
        public function extendFormat() {
            return zkextendfmt($this);
        }
        
        public function extendOPLog(index=0) {
            return zkextendoplog($this, index);
        }
        */
        
        public function platform() {
            return zkplatform($this);
        }
        
        public function fmVersion() {
            return zkplatformVersion($this);
        }
        
        public function workCode() {
            return zkworkcode($this);
        }
        
        public function ssr() {
            return zkssr($this);
        }
        
        public function pinWidth() {
            return zkpinwidth($this);
        }
         
        public function faceFunctionOn() {
            return zkfaceon($this);
        }
        
        public function serialNumber() {
            return zkserialnumber($this);
        }
        
        public function deviceName() {
            return zkdevicename($this);
        }
        
        public function deviceMAC() {
        	return zkdevicemac($this);
        }
        
        public function disableDevice() {
            return zkdisabledevice($this);
        }
        
        public function enableDevice() {
            return zkenabledevice($this);
        }
        
        public function getUser() {
            return zkgetuser($this);
        }
        
		public function setUser($uid, $userid=null, $name=null, $password=null, $role=null) {
        	if (is_array($uid)) {
   				$ret=false;
   				if (count($uid)) {
	   				foreach ($uid as $u) {
			            $r=zksetuser($this, $u['uid'], $u['userid'], $u['name'], $u['password'], $u['role']);
	   					$ret=$ret&&$r;
	   				}
   				}
   				return $ret;
        	} else {
	            return zksetuser($this, $uid, $userid, $name, $password, $role);
        	}
        }
        
        public function deleteUser($uid) {
        	if (is_array($uid)) {
        		$ret=false;
        		if (count($uid)) {
	        		foreach ($uid as $id) {
	        			$r=zkdeleteuser($this, $id);
	        			$ret=$ret&&$r;
	        		}
        		}
        		return $ret;
        	} else {
        		return zkdeleteuser($this, $uid);
        	}
        }
        
        public function clearUser() {
            return zkclearuser($this);
        }
        
        public function clearAdmin() {
            return zkclearadmin($this);
        }
        
        public function getAttendance() {
            return zkgetattendance($this);
        }
        
        public function clearAttendance() {
            return zkclearattendance($this);
        }
        
        public function setTime($t) {
            return zksettime($this, $t);
        }
        
        public function getTime() {
            return zkgettime($this);
        }
        
        public function getFreeSizes() {
        	return zkgetfreesizes($this);
        }
        
    }
