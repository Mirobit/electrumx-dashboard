<?php

namespace App;

class Peer{	
	public $host; // string
    public $ip; // string
    public $ipv6; // bool
	public $tor; // bool
	public $status; // string
	public $client; // string
    public $ssl; // bool
    public $tcp; // boll
	public $country; // string
	public $countryCode; // string
	public $isp; // string
	
	function __construct($peer) {
		$this->host = htmlspecialchars($peer['host']);      
		$this->ip = checkIp($peer['ip_addr']);
		$this->ipv6 = checkIfIpv6($this->ip);
		$this->status = htmlspecialchars($peer['status']);
        $this->client = htmlspecialchars($peer['features']['server_version']);     
        $hostsData = getHostsData($peer['features']['hosts']);
        $this->tor = $hostsData['tor'];
		$this->ssl = $hostsData['ssl'];
        $this->tcp = $hostsData['tcp'];	 
	}			
}
?>