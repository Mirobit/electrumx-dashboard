<?php

namespace App;

class Config {
	
	// ElectrumX Dashboard (EXD) password for login. You should additionally change the name of 
    // EXD folder to something unique, if accessible via the web. You can also limit the access 
    // to a specific IP with the option below.
	const PASSWORD = "SET-PASSWORD";
    // IP that can access EXD (by default only localhost (IPv4/v6) can access EXD).
    // If empty (""), any IP can access EXD. If "localhost", only localhost can access EXD. 
    // If specific IP (e.g. "84.12.32.297"), localhost and the specific IP can access EXD.
	const ACCESS_IP = "localhost";	
	
	
	// IP of ElectrumX RPC server
	const RPC_IP = "127.0.0.1";
    // Port of ElectrumX RPC server
    const RPC_PORT = "8000";
	
	
	// Uses ip-api.com to get country and isp of peers/sessions. API is limited to 150 requests per minutes.
	// Peer geo data is stored as long as the peers are connected. A page reload (main/peers/sessions) only 
    // causes an API request if new peers connected (older than 5 minutes) since the last load. Up to 
    // 100 ips/peers are checked per request.
	const PEERS_GEO = TRUE;
	// Maxmim of seconds to wait for response from ip-api.com
    const PEERS_GEO_TIMEOUT = 2;
}
?>