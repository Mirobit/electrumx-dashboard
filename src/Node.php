<?php

namespace App;

class Node {
	public $blockHeight;
    public $blockHeightDaemon;
    public $synced;
    public $blocksBehind;
	public $groupsC;
	public $peersC;
	public $sessionsC;
	public $subscriptionsC;
	public $txSentC;
    public $errorsC;
    public $peers;
	public $uptime;
	
	function __construct() {
		global $exd;
        
		$info = $exd->send('getinfo');
        
		$this->blockHeight = checkInt($info['db height']);
        $this->blockHeightDaemon = checkInt($info['daemon height']);
        if($this->blockHeight == $this->blockHeightDaemon){
            $this->synced = TRUE;
        }else{
            $this->synced = FALSE;
        }
        $this->blocksBehind = $this->blockHeightDaemon - $this->blockHeight;
        
        $this->groupsC = checkInt($info['groups']);
        $this->peersC = checkInt($info['peers']['total']);
        $this->requestsC = checkInt($info['request total']);
        $this->sessionsC = checkInt($info['sessions']['count']);
        $this->subscriberC = checkInt($info['sessions']['count with subs']);
        $this->txSentC = checkInt($info['txs sent']);
        $this->errorsC = checkInt($info['sessions']['errors']);
        $this->uptime = htmlspecialchars($info['uptime']);
	}
}