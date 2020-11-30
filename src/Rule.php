<?php

namespace App;

class Rule {
    
    public $id, $date, $uses, $action, $trigger, $threshold, $clientVer, $global, $gTrigger, $gThreshold;
    
    // Create new rule
    private function create($data) {
        if(isset($data['id']) && ctype_digit($data['id'])){
            $this->id = $data['id'];
        }    
        
        $this->date = date("Y-m-d H:i:s",time());
        $this->uses = 0;
        
        if(!in_array($data['action'], array('disconnect','notice',), true)){
            return false;
        }
        $this->action = $data['action'];
        
        if(!in_array($data['trigger'], array('client','traffic','trafficin','trafficout'), true)){
            return false;
        }
        $this->trigger = $data['trigger'];
        
        if($this->trigger != "client" AND isset($data['threshold']) AND ctype_digit($data['threshold'])){
            $this->threshold = $data['threshold'];
        }elseif($this->trigger != "client" AND isset($data['threshold'])){
            return false;
        }
        
        if($this->trigger == "client" AND preg_match("/^[0-9]\.[0-9]\.[0-9]$/", $data['threshold'], $match)){ 
                $this->clientVer = $match;  
        }elseif($this->trigger == "client"){
            return false;
        }
        
        if(isset($data['global']) && $data['global'] !== "on"){
            return false;
        }
        
        if(!isset($data['global'])){
            $this->global = false;
            return true;
        }       
        $this->global = true;
        
        if(!in_array($data['gTrigger'], array('traffic','sessioncount'), true)){
            return false;
        }
        $this->gTrigger = $data['gTrigger'];
        
        if(!ctype_digit($data['gThreshold'])){
            return false;
        }       
        $this->gThreshold = $data['gThreshold'];
            
        return true;      
    }

    // Save a new rule
    public function save(array $data) {
        $rules = self::getRules();
        if(!$this->create($data)){
            return false;
        }
        // Check if rule exists or if new
        if(empty($this->id)){ 
            end($rules);
            $this->id = key($rules)+1;
        }
        $rules[$this->id] = $this;
        
        file_put_contents('data/rules.inc',serialize($rules)); 
        return true;
    }
	
// Stactic functions
	
// Runs all rules
    /**
     * @return bool
     */
    public static function run() {
		global $exd; 

        $data = self::getData();
		
		if(empty($data['sessions'])){
            return false;
        }
		
        $logging = "";
        
		// Checks every rule
        foreach($data['rules'] as &$rule) {
			// Checks if global trigger set and if triggered
            if($rule->global) {
                $gResult = false;
                switch($rule->gTrigger) {
                    case "traffic":
                        if($data['global']['traffic'] > $rule->gThreshold) $gResult = true;
                        break;
                    case "peercount":
						if($data['global']['connections'] > $rule->gThreshold) $gResult = true;
                        break;                     
                }
                if(!$gResult) {
                    continue;
                }
            }           
			// If global trigger is active or no global trigger set, every peer is checked
            foreach($data['sessions'] as $session) {
                $result = false;
                switch($rule->trigger) {
                    case "client":
                        if($session->version == $rule->clientVer) $result = true;
                        break;     
                    case "traffic":
                        if($session->traffic > $rule->threshold) $result = true;
                        break;
                    case "trafficin":
                        if($session->trafficIn > $rule->threshold) $result = true;
                        break;
                    case "trafficout":
                        if($session->trafficOut > $rule->threshold) $result = true;
                        break;               
                }
                
                if(!$result) {
                    continue;
                }
                
                $logTime = date("Y-m-d H:i:s",time());

                switch($rule->action) {                   
                    case "ban":
                    case "disconnect":
						try{
							$bitcoind->disconnectnode($session->ip);
							$logging .= $logTime.": Disconnected (".$rule->trigger."): ".$session->ip." (".$session->client.") - (".$rule->id.")\r\n";
						}catch(\Exception $e) {
							$logging .= $logTime.": Error disconnecting ".$session->ip." (".$rule->id.")\r\n";
						}
                        break;
                    case "notice":
                        $logging .= $logTime.": Notice (".$rule->trigger."): ".$session->ip." (".$session->client.") - (".$rule->id.")\r\n";
                        break;                        
                }
                $rule->uses++;
            }                       
        }
		
        if (file_exists('data/rules.log')){
            $logging .= file_get_contents('data/rules.log');
        }
        if(!empty($logging)){
            file_put_contents('data/rules.log', $logging);
        }
		file_put_contents('data/rules.inc',serialize($data['rules']));
    }
	
	// Gets information needed for rule run
	private static function getData(){	
		$node = new Node();
		
		$data['sessions'] = getSessionsData(false)['sessions'];
		$data['rules'] = self::getRules();
		$data['global']['connections'] = $node->sessionsC;
		$data['global']['traffic'] = $node->traffic;
		
		return $data;
	
	}

    // Delete a single rule/all
    public static function deleteByID(int $id) { 
		$rules = self::getRules(); 
        if(array_key_exists($id, $rules)) {
            unset($rules[$id]);
            file_put_contents('data/rules.inc', serialize($rules)); 
            $result = true;
        }else{
			$result = false;
		}
        return $result;
    }
	
    // Delete a single rule/all
    public static function deleteAll() { 
		return unlink('data/rules.inc');
    }

    
    // Return a single rule
    public static function getByID(int $id) {
        $rules = self::getRules(); 
        if(array_key_exists($id, $rules)) {
            $rule = $rules[$id];
            return $rule;
        }         
        return false;
    }

    // Return all rules
    public static function getRules() {
        if (file_exists('data/rules.inc')){
            $rules = unserialize(file_get_contents('data/rules.inc')); 
        }else{
            $rules = array();
        }       
        return $rules;
    }
	
	// Resets the counter for rule uses
	public static function resetCounter(){
		$rules = self::getRules();
		foreach($rules as &$rule){
			$rule->uses = 0;
		}
		file_put_contents('data/rules.inc',serialize($rules));
		return true;
	}

    // Delete Logfile
	public static function deleteLogfile(){
		return unlink('data/rules.log');
	}
}
?>