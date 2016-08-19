<?php

	function getReceivedSize($self) {
	/*Checks a returned packet to see if it returned CMD_PREPARE_DATA,
	 indicating that data packets are to be sent

	 Returns the amount of bytes that are going to be sent*/
	$u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr( $self->data_recv, 0, 8 ) );
	$command = hexdec( $u['h2'].$u['h1'] );

	if ( $command == CMD_PREPARE_DATA ) {
		$u = unpack('H2h1/H2h2/H2h3/H2h4', substr( $self->data_recv, 8, 4 ) );
		$size = hexdec($u['h4'].$u['h3'].$u['h2'].$u['h1']);
		return $size;
	} else
		return FALSE;
	}

    function zkdevicename($self) {
        $command = CMD_DEVICE;
        $command_string = '~DeviceName';
        $chksum = 0;
        $session_id = $self->session_id;
        
        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr( $self->data_recv, 0, 8) );
        $reply_id = hexdec( $u['h8'].$u['h7'] );
        
        $buf = $self->createHeader($command, $chksum, $session_id, $reply_id, $command_string);
        
        socket_sendto($self->zkclient, $buf, strlen($buf), 0, $self->ip, $self->port);
        
        try {
            socket_recvfrom($self->zkclient, $self->data_recv, 1024, 0, $self->ip, $self->port);
            
            $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6', substr( $self->data_recv, 0, 8 ) );
            
            $self->session_id =  hexdec( $u['h6'].$u['h5'] );
            return substr( $self->data_recv, 8 );
        } catch(ErrorException $e) {
            return FALSE;
        } catch(exception $e) {
            return False;
        }
    }
    
    function zkdevicemac($self) {
    	$command = CMD_DEVICE;
    	$command_string = 'MAC';
    	$chksum = 0;
    	$session_id = $self->session_id;
    
    	$u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr( $self->data_recv, 0, 8) );
    	$reply_id = hexdec( $u['h8'].$u['h7'] );
    
    	$buf = $self->createHeader($command, $chksum, $session_id, $reply_id, $command_string);
    
    	socket_sendto($self->zkclient, $buf, strlen($buf), 0, $self->ip, $self->port);
    
    	try {
    		socket_recvfrom($self->zkclient, $self->data_recv, 1024, 0, $self->ip, $self->port);
    
    		$u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6', substr( $self->data_recv, 0, 8 ) );
    
    		$self->session_id =  hexdec( $u['h6'].$u['h5'] );
    		return substr( $self->data_recv, 8 );
    	} catch(ErrorException $e) {
    		return FALSE;
    	} catch(exception $e) {
    		return False;
    	}
    }
    
    function zkgetfreesizes($self) {
    	$command = CMD_GET_FREE_SIZES;
    	$command_string = '';
    	$chksum = 0;
    	$session_id = $self->session_id;
    
    	$u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr( $self->data_recv, 0, 8) );
    	$reply_id = hexdec( $u['h8'].$u['h7'] );
    
    	$buf = $self->createHeader($command, $chksum, $session_id, $reply_id, $command_string);
    
    	socket_sendto($self->zkclient, $buf, strlen($buf), 0, $self->ip, $self->port);
    
    	socket_recvfrom($self->zkclient, $self->data_recv, 1024, 0, $self->ip, $self->port);
    
    	$fs=array();
    	$tmp=$self->data_recv;
    	$free_sizes_info=$self->reverse_hex(bin2hex($tmp));
    	if (!$free_sizes_info) {
    		$fs = false;
    	} else {
    		if (DEVICE_GENERAL_INFO_STRING_LENGTH > strlen($free_sizes_info)) {
    			$free_sizes_info = '000000000000000000000000' . $free_sizes_info;
    		}
    
    		$fs['att_logs_available']  = hexdec(substr($free_sizes_info, 27, 5));
    		$fs['templates_available'] = hexdec(substr($free_sizes_info, 44, 4));
    		$fs['att_logs_capacity']   = hexdec(substr($free_sizes_info, 51, 5));
    		$fs['templates_capacity']  = hexdec(substr($free_sizes_info, 60, 4));
    		$fs['passwords_stored']    = hexdec(substr($free_sizes_info, 76, 4));
    		$fs['admins_stored']       = hexdec(substr($free_sizes_info, 84, 4));
    		$fs['att_logs_stored']     = hexdec(substr($free_sizes_info, 116, 4));
    		$fs['templates_stored']    = hexdec(substr($free_sizes_info, 132, 4));
    		$fs['users_stored']        = hexdec(substr($free_sizes_info, 148, 4));
    	}
    
    	return $fs;
    
    }
    function zkenabledevice($self) {
        $command = CMD_ENABLEDEVICE;
        $command_string = '';
        $chksum = 0;
        $session_id = $self->session_id;
        
        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr( $self->data_recv, 0, 8) );
        $reply_id = hexdec( $u['h8'].$u['h7'] );
        
        $buf = $self->createHeader($command, $chksum, $session_id, $reply_id, $command_string);
        
        socket_sendto($self->zkclient, $buf, strlen($buf), 0, $self->ip, $self->port);
        
        try {
            socket_recvfrom($self->zkclient, $self->data_recv, 1024, 0, $self->ip, $self->port);
            
            $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6', substr( $self->data_recv, 0, 8 ) );
            
            $self->session_id =  hexdec( $u['h6'].$u['h5'] );
            return substr( $self->data_recv, 8 );
        } catch(ErrorException $e) {
            return FALSE;
        } catch(exception $e) {
            return False;
        }
    }
    
    function zkdisabledevice($self) {
        $command = CMD_DISABLEDEVICE;
        $command_string = chr(0).chr(0);
        $chksum = 0;
        $session_id = $self->session_id;
        
        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr( $self->data_recv, 0, 8) );
        $reply_id = hexdec( $u['h8'].$u['h7'] );
        
        $buf = $self->createHeader($command, $chksum, $session_id, $reply_id, $command_string);
        
        socket_sendto($self->zkclient, $buf, strlen($buf), 0, $self->ip, $self->port);
        
        try {
            socket_recvfrom($self->zkclient, $self->data_recv, 1024, 0, $self->ip, $self->port);
            
            $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6', substr( $self->data_recv, 0, 8 ) );
            
            $self->session_id =  hexdec( $u['h6'].$u['h5'] );
            return substr( $self->data_recv, 8 );
        } catch(ErrorException $e) {
            return FALSE;
        } catch(exception $e) {
            return False;
        }
    }
?>


