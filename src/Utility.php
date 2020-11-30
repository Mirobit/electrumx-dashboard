<?php

namespace App;

function checkInt($int){
	if(!is_numeric($int)){
		$int = 0;
	}	
	return $int;
}

function getCleanIP($ip){
	$ip = checkIpPort($ip);
	$ip = preg_replace("/:[0-9]{1,5}$/", "", $ip);
	return $ip;
}

function checkIfIpv6($ip){
	if(preg_match("/]|:/",$ip)){
		return true;
	}else{
		return false;
	}
}

function getHostsData($hosts){
    $hostsData['tor'] = false;
    $hostsData['ssl'] = false;
    $hostsData['tcp'] = false;
    foreach($hosts as $key => $host){
        if(isset($host['tcp_port'])){
            $hostsData['tcp'] = true;
        }
        if(isset($host['ssl_port'])){
            $hostsData['ssl'] = true;
        }
        if(!strpos($key, ".onion") === false){
            $hostsData['tor'] = true;
        }
    }
    return $hostsData;
}

function checkIpPort($ip){
	if(preg_match("/^\[{0,1}[0-9a-z:\.]{7,39}\]{0,1}:[0-9]{1,5}$/", $ip)) {
		return $ip;
	}else{
		return "unknown";
	}
}

function checkIp($ip){
	if(preg_match("/^\[{0,1}[0-9a-z:\.]{7,39}\]{0,1}$/", $ip)) {
		return $ip;
	}else{
		return "unknown";
	}
}

function checkBool($bool){
	if(is_bool($bool)){
		return $bool;
	}else{
		return false;
	}
}

function checkArray($array){
	foreach ($array as $key => $value){
		if(!preg_match("/^[a-z\*]{2,11}$/",$key) OR !is_int($value)){
			unset($array[$key]);
		}
	}
	return $array;
}

function checkCountryCode($countryCode){
	if(preg_match("/^[A-Z]{2}$/", $countryCode)){
		return $countryCode;
	}else{
		return "UN";
	}
}

function checkString($string){
	$string = substr($string,0,50);
	if(preg_match("/^[0-9a-zA-Z- ()\/\.,&]{2,50}$/",$string)){
		return $string;
	}else{
		return "Unknown";
	}
}

function checkHosted($hoster){
	$hosterList = json_decode(file_get_contents('data/hoster.json'), true);
	if (in_array($hoster, $hosterList) OR preg_match("/server/i",$hoster)){
		return true;
	}else{
		return false;
	}
}

function updateHosted($hoster, $hosted){
  $peers = file_get_contents('data/geodatasessions.inc');
	$peers = unserialize($peers);
    foreach($peers as &$peer){
        if ($peer[5] == $hoster){
            $peer[6] = $hosted;
        }
    }
    file_put_contents('data/geodatasessions.inc',serialize($peers)); 
}	

function bytesToMb($size, int $round = 2){
	$size = round(checkInt($size) / 1000000, $round);
	return $size;
}

function getDateTime($timestamp){
	$date = date("Y-m-d H:i:s",$timestamp);	
	return $date;
}


// Creates chart and legend (list)
function getTopClients($sessions){
  $clients = [];
  $chartLabels = "";
  $chartValue = ""; 
    
  foreach($sessions as $session){
      if(isset($clients[$session->client])){
        $clients[$session->client]['count']++;
      }else{
          $clients[$session->client]['count'] = 1;
      }
  }
    
	$peerCount = count($sessions);
  $clientCount = count($clients);
  arsort($clients);
  $clients = array_slice($clients,0,9);    
  if($clientCount > 9){
    $clients['Other']['count'] = $clientCount-9;
  }
 
  foreach($clients as $cName => &$client){
    $chartLabels .= '"'.$cName.'",';
    $chartValue .= $client['count'].',';
    $client['share'] = round($client['count']/$peerCount,2)*100;
    if($client['share'] === 0) $client['share'] = 1;
  }
    
  $chartData['labels'] = rtrim($chartLabels, ",");
  $chartData['values'] = rtrim($chartValue, ",");
  $chartData['legend'] = $clients;

  return $chartData;
}


function getMostPop(array $connections, bool $sessionsB = true){
    $clientCountAr = [];
    $countryCountAr = [];
    $ispCountAr = [];
    $ipCountAr = [];
    $result = [];
    $result['sslc'] = 0;
    $result['torc'] = 0;
    $result['tcpc'] = 0;
    $result['subscribersc'] = 0;
		$result['txsc'] = 0;
		$result['peersgc'] = 0;
    
    foreach($connections as $connection){
        // Count ssl connections
        if($connection->ssl){
            $result['sslc']++;
        }
        if($sessionsB){
            // Count subscribers
            if($connection->subscriptionsC > 0){
                $result['subscribersc']++;
            }  
            // Count txs send
            $result['txsc'] += $connection->txsC;
        }else{
					// Count good peers
					if($connection->status === "good") {
						$result['peersgc']++;
					}
					if($connection->tor){
						$result['torc']++;
					}
					if($connection->tcp){
						$result['tcpc']++;
					}	
				}
        
        // Group different ips
         if(array_key_exists($connection->ip, $ipCountAr)){
            $ipCountAr[$connection->ip]++;
        }else{
            $ipCountAr[$connection->ip] = 1;
        }
        
        // Count Clients
        if(array_key_exists($connection->client, $clientCountAr)){
            $clientCountAr[$connection->client]++;
        }else{
            $clientCountAr[$connection->client] = 1;
        }
        
        if(($sessionsB AND CONFIG::SESSIONS_GEO) or (!$sessionsB AND CONFIG::PEERS_GEO)){
            // Check if unknown or new
            if($connection->countryCode === "UN" OR $connection->countryCode === "NX") continue;
            // Count Countries
            if(array_key_exists($connection->countryCode, $countryCountAr)){
                $countryCountAr[$connection->countryCode]++;
            }else{
                $countryCountAr[$connection->countryCode] = 1;
            }
            
            // Count ISPs
            if(array_key_exists($connection->isp, $ispCountAr)){
                $ispCountAr[$connection->isp]++;
            }else{
                $ispCountAr[$connection->isp] = 1;
            }            
        }
    }
    
    // Count different IPs
    $result['ips'] = count($ipCountAr);
    
    // Select most popular client
    arsort($clientCountAr);
    $result['mpCli'] = key($clientCountAr);
    $result['mpCliC'] = reset($clientCountAr);
    
    if(($sessionsB AND CONFIG::SESSIONS_GEO) or (!$sessionsB AND CONFIG::PEERS_GEO)){
        // Select most popular clountry
        arsort($countryCountAr);
        $result['mpCou'] = key($countryCountAr);
        $result['mpCouC'] = reset($countryCountAr);
        
        // Select most popular isp
        arsort($ispCountAr);
        $result['mpIsp'] = substr(key($ispCountAr),0,8);
        $result['mpIspC'] = reset($ispCountAr);
    }
    
    return $result;
}



function getSessionsData(bool $geo = CONFIG::SESSIONS_GEO) {
	global $exd;
  $sessionsData = [];
  $sessionsRPC = $exd->send('sessions');
  
  if($geo){
    $geoData = getSessionsGeoData($sessionsRPC);
    $sessionsData = $geoData;
  }else{
    $sessionsData['totaltraffic'] = 0;
    $sessionsData['totaltrafficin'] = 0;
    $sessionsData['totaltrafficout'] = 0;
    foreach($sessionsRPC as $session){
      $sessionObj = new Session($session);
      $sessionsData['totaltraffic'] += $sessionObj->traffic;
      $sessionsData['totaltrafficin'] += $sessionObj->trafficIn;
      $sessionsData['totaltrafficout'] += $sessionObj->trafficOut;
      $sessionsData['sessions'][] = $sessionObj;
    }
  }

	return $sessionsData;
}

function getPeersData(bool $geo = CONFIG::PEERS_GEO) {
  global $exd;
  $peersData = [];
  $peersRPC = $exd->send('peers');
  
  if($geo){
    $geoData = getPeersGeoData($peersRPC);
    $peersData['peers'] = $geoData['peers'];
  }else{
    foreach($peersRPC as $peer){       
      if(!empty($peer['ip_addr'])){
          $peersData['peers'][] = new Peer($peer);
      }
    }
  }

	return $peersData;
}


// For sessions (geo)
function getSessionsGeoData($sessionsRPC){
	$countryList = [];
	$hosterC = 0;
  $privateC = 0;
  $newSessionsC = 0;
  $noGeoData = false;
  $sessionData['totaltraffic'] = 0;
  $sessionData['totaltrafficin'] = 0;
  $sessionData['totaltrafficout'] = 0;

	// Check if session file exists and enabled
	if (file_exists('data/geodatasessions.inc')){
		// Loads serialized stored sessions from disk
		$serializedSessions = file_get_contents('data/geodatasessions.inc');
		$arraySessions = unserialize($serializedSessions);
		// Check if client was restarted and IDs reassigned
		$oldestSessionId = reset($sessionsRPC)[0];
		$oldestSessionIp = getCleanIP(reset($sessionsRPC)[2]);
		$delete = false;
		// Checks if we know about the oldest sessions, if not we assume that we don't known any session
		foreach($arraySessions as $key => $session){
			if($oldestSessionIp == $session[0]){
				$delete = true;
				// Either ElectrumX was restarted or sessions reconnected. Since session is the oldest, all other sessions we known disconnected
				if($oldestSessionId != $key){
					$delete = false;
				}
				break;
			}
			// For removing old sessions that disconnected. Value of all sessions that are still conected will be changed to 1 later. All sessions with 0 at the end of the function will be deleted
			$arraySessions[$key][7] = 0;
		}
		// Oldest session hasn't shown up -> Node isn't connected to any of the previously stored sessions
		if(!$delete){
			unset($arraySessions);
			$noGeoData = true;
		}
	}else{
		$noGeoData = true;
	}
	
	// Find sessions that we don't have geo data for and that are "older" than 10 minutes
  // First interation through all sessions is used to collect ips for geo api call. This way the batch functionality can be used
  $ips = [];
  $ipData = [];

	foreach($sessionsRPC as $session){
		$tempIP = getCleanIP($session[2]);
    // Older than 10 minutes
		if ($session[14] > 600 AND ($noGeoData OR !in_array($tempIP,array_column($arraySessions,0)))){
			$ips[] = $tempIP;
		}
	}
	
	if(!empty($ips)){
    $apiData = getIpData($ips);
    $ipData = $apiData['geojson'];
    $sessionData['api'] = $apiData['api'];
  }

  // 2nd interation through sessions to create final sessions list for output
	foreach($sessionsRPC as $session){
		// Creates new session object
		$sessionObj = new Session($session);

		// Checks if session is new or if we can read data from disk (geodatasessions.inc)
		if($noGeoData OR !in_array($sessionObj->ip,array_column($arraySessions,0))){ 
      $index = array_search($sessionObj->ip, array_column($ipData, 'query'));
			if(isset($ipData[0]) AND $sessionObj->age > 600 AND is_numeric($index)){  
        $ipInfo = $ipData[$index];            
				$countryCode = checkCountryCode($ipInfo['countryCode']);
				$country = checkString($ipInfo['country']);
				$city = checkString($ipInfo['country']);
				$isp = checkString($ipInfo['isp']);         
				$hosted = checkHosted($isp);
                
				// Adds the new session to the save list
				$arraySessions[$sessionObj->id] = array($sessionObj->ip, $countryCode, $country, $city, $isp, $hosted, 1);
                
			  // Only counted for sessions older than 10 minutes
				$newSessionsC++;       
			}elseif($sessionObj->age > 600){
				// If IP-Api.com call failed we set all data to Unknown and don't store the data
				$countryCode = "UN";
				$country = "Unknown";
				$city = "Unknown";
				$isp = "Unknown";         
				$hosted = false;
        // Only counted for sessions older than 10 minutes
        $newSessionsC++;                
			}else{
				// If session is younger than 10 minutes
				$countryCode = "NX";
				$country = "New";
				$city = "New";
				$isp = "New";         
				$hosted = false;                
                
            }

		}else{
			$id = $sessionObj->id;
			// Nodes that we know about but reconnected
			if(!isset($arraySessions[$id])){
				$id = array_search($sessionObj->ip, array_column($arraySessions,0));
				$id = array_keys($arraySessions)[$id];
			}
			$countryCode = $arraySessions[$id][1];
			$country = $arraySessions[$id][2];
			$city = $arraySessions[$id][3];
			$isp = $arraySessions[$id][4];
			$hosted = $arraySessions[$id][5];
			$arraySessions[$id][6] = 1;
		}

    // Counts the countries
    if($countryCode !== "UN" AND $countryCode !== "NX") {
      if(isset($countryList[$country])){     
        $countryList[$country]['count']++;
      }else{
        $countryList[$country]['code'] = $countryCode;
        $countryList[$country]['count'] = 1;
      }
    }

		// Adds country data to session object
		$sessionObj->countryCode = $countryCode;
		$sessionObj->country = $country;
		$sessionObj->city = $city;
		$sessionObj->isp = $isp;
		$sessionObj->hosted = $hosted;
		if($hosted){
			$hosterC++;
		}else{
			$privateC++;
		}
		// Adds traffic of each sessions to total traffic (in MB)
		$sessionData['totaltraffic'] += $sessionObj->traffic;
		$sessionData['totaltrafficin'] += $sessionObj->trafficIn;
		$sessionData['totaltrafficout'] += $sessionObj->trafficOut;
	
		// Adds sessions to final sessions array
    $sessionData['sessions'][] = $sessionObj;
  }
  
  $sessionData['hosterc'] = $hosterC;
  $sessionData['privatec'] = $privateC;
  $sessionData['newsessionsc'] = $newSessionsC;
  $sessionData['countrylist'] = $countryList;

  // Removes all sessions that the node is not connected to anymore.
  foreach($arraySessions as $key => $session){
    if($session[6] == 0){
      unset($arraySessions[$key]);
    }
  }

  // Write update session data to file
  $newSerializeSessions = serialize($arraySessions);
  file_put_contents('data/geodatasessions.inc', $newSerializeSessions);
  
  return $sessionData;
}

// For Peers
function getPeersGeoData($peersRPC){
	$noGeoData = false;
	
	// Check if peer file exists and enabled
	if (file_exists('data/geodatapeers.inc')){
		// Loads serialized stored peers from disk
		$serializedPeers = file_get_contents('data/geodatapeers.inc');
		$arrayPeers = unserialize($serializedPeers);		
	}else{
		$noGeoData = true;
	}
	
	// Find hosts that we don't have geo data for
  // First interation through all peers is used to collect ips for geo api call
	$ips = [];
	foreach($peersRPC as $peer){
		$tempIp = $peer['ip_addr'];
		if($noGeoData OR !isset($arrayPeers[$tempIp])){
    	// Don't query API for tor hosts
    	if(!empty($peer['ip_addr'])){
    		$ips[] = $tempIp;
      }
		}
	}
	
  // Get inforamtion from ip-api.com
	if(!empty($ips)){
    $apiData = getIpData($ips);
    $ipData = $apiData['geojson'];
    $peerData['api'] = $apiData['api'];
	}
    
  // 2nd interation through peers to create final peer list for output
	foreach($peersRPC as $peer){
  	// Exlucde tor nodes
    if(empty($peer['ip_addr'])){
    	continue;
    }
		// Creates new peer object
		$peerObj = new Peer($peer);

    // Check if peer is new or if we can read data from disk (geodatapeers.inc)
		if(!isset($arrayPeers[$peerObj->ip])){
      // Check if tor node or not
			$ipInfo = $ipData[array_search($peerObj->ip, array_column($ipData, 'query'))];
			$countryCode = checkCountryCode($ipInfo['countryCode']);
			$country = checkString($ipInfo['country']);
			$city = checkString($ipInfo['country']);
			$isp = checkString($ipInfo['isp']);         
			// Adds the new peer to the save list		
			$arrayPeers[$peerObj->ip] = array($peerObj->ip, $countryCode, $country, $city, $isp, 1);          
		}else{
			$countryCode = $arrayPeers[$peerObj->ip][1];
			$country = $arrayPeers[$peerObj->ip][2];
			$city = $arrayPeers[$peerObj->ip][3];
			$isp = $arrayPeers[$peerObj->ip][4];
			$arrayPeers[$peerObj->ip][5] = 1;
		}          

		// Adds country data to peer object
		$peerObj->countryCode = $countryCode;
		$peerObj->country = $country;
		$peerObj->city = $city;
		$peerObj->isp = $isp;
	
		// Adds peer to peer array
    $peerData['peers'][] = $peerObj;
  }

  // Removes all peers that the node is not connected to anymore.
  foreach($arrayPeers as $key => &$peer){
      if($peer[5] == 0){
          unset($arrayPeers[$key]);
      }else{
          $peer[5] = 0; 
      }
  }

  // Save geodata
  $newSerializePeers = serialize($arrayPeers);
  file_put_contents('data/geodatapeers.inc', $newSerializePeers);
  
  return $peerData;
}

function getIpData($ips){
	global $error;
  $apiData['api']['callc'] = 0;

  // ip-api.com allows 15 requests with 100 ips per minute. The limit here is lower since
  // new peers could connect within a minute a trigger additional calls.
  $numOfIps = count($ips);
	if($numOfIps > 1200) $numOfIps = 1200;


  $apiData['api']['ipc'] = $numOfIps;
	$j = 0;
	// A mamxium of 100 Ips can be checked per API call (limit by ip-api.com)
	$m = 100;
	// Creates Postvar data with a maximum of 100 IPs per request
	while($j < $numOfIps){
		if($numOfIps-$j < 100){
			$m=$numOfIps-$j;
		}
		for($i = 0; $i < $m; $i++){
			$postvars[$j][] =  array("query" => $ips[$i+$j]);
		}
		$j += $i;
	}	
	// Curl
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_URL,'http://ip-api.com/batch?fields=query,country,countryCode,city,isp,status');
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , CONFIG::GEO_TIMEOUT); 
	curl_setopt($ch, CURLOPT_TIMEOUT, CONFIG::GEO_TIMEOUT+1);
	
	// One call for each 100 ips
	$apiData['geojson'] = [];
	foreach($postvars as $postvar){
    $apiData['api']['callc']++;
		$postvarJson = json_encode($postvar);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $postvarJson);
		$result = json_decode(curl_exec($ch),true);
		if(empty($result)){
			$error = "Geo API (ip-api.com) Timeout";
			$result = [];
		}
		$apiData['geojson'] = array_merge($apiData['geojson'], $result);
  }

	return $apiData;
}

function createMapJs(int $peerCount, array $countryList){
    
    // Sorting country list
    function compare($a, $b)
    {
        return $b['count'] - $a['count'];
    }
    uasort($countryList, "App\compare");

    $i = 0;
	$jqvData = 'var peerData = {';
    $mapDesc = [];

    // Creates map Legend. Top 9 countries + Others
	foreach($countryList as $countryName => $country){
		$jqvData .= "\"".strtolower($country['code'])."\":".$country['count'].",";
        
        if($i<9){
            $mapDesc[$countryName] = $country;           
            $i++;
        }else{
            if(isset($mapDesc['Other']['count'])){
                $mapDesc['Other']['count']++;
            }else{
                $mapDesc['Other']['count'] = 1;
            }
        }
	}
	
	foreach($mapDesc as &$country){
		$country['share'] = round($country['count']/$peerCount,2)*100;
	}
    
    $jqvData = rtrim($jqvData, ",");
    $jqvData .= '};';
    
	// Writes data file for JVQMap
	$map['data'] = $jqvData;
	$map['desc'] = $mapDesc;
	
  return $map;
}

?>