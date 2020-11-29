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
	if(preg_match("/^[0-9a-zA-Z- \.,&]{2,50}$/",$string)){
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
  }
    
  $chartData['labels'] = rtrim($chartLabels, ",");
  $chartData['values'] = rtrim($chartValue, ",");
  $chartData['legend'] = $clients;

  return $chartData;
}


function getMostPop(array $connections, bool $sessionsB = true){
    $clCountAr = [];
    $ctCountAr = [];
    $htCountAr = [];
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
            // Sum all txs send
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
        
        // Count different IPs
         if(array_key_exists($connection->ip, $ipCountAr)){
            $ipCountAr[$connection->ip]++;
        }else{
            $ipCountAr[$connection->ip] = 1;
        }
        
        // Count Client 1
        if(array_key_exists($connection->client, $clCountAr)){
            $clCountAr[$connection->client]++;
        }else{
            $clCountAr[$connection->client] = 1;
        }
        
        if(CONFIG::PEERS_GEO){
            // Count Country 1
            if(array_key_exists($connection->countryCode, $ctCountAr)){
                $ctCountAr[$connection->countryCode]++;
            }else{
                $ctCountAr[$connection->countryCode] = 1;
            }
            
            // Count ISP 1
            if(array_key_exists($connection->isp, $htCountAr)){
                $htCountAr[$connection->isp]++;
            }else{
                $htCountAr[$connection->isp] = 1;
            }            
        }
    }
    
    // Count different IPs
    $result['ips'] = count($ipCountAr);
    
    // Count Client 2
    arsort($clCountAr);
    $result['mpCli'] = key($clCountAr);
    $result['mpCliC'] = reset($clCountAr);
    
    if(CONFIG::PEERS_GEO){
        // Count Country 2
        arsort($ctCountAr);
        $result['mpCou'] = key($ctCountAr);
        $result['mpCouC'] = reset($ctCountAr);
        
        // Count ISP 2
        arsort($htCountAr);
        $result['mpIsp'] = substr(key($htCountAr),0,8);
        $result['mpIspC'] = reset($htCountAr);
    }
    
    return $result;
}



function getSessionsData(bool $geo = NULL) {
	global $exd;
	$sessionsData['totaltraffic'] = 0;
	$sessionsData['totaltrafficin'] = 0;
	$sessionsData['totaltrafficout'] = 0;

	$sessionsRPC = $exd->send('sessions');
	foreach($sessionsRPC as $session){
		$sessionObj = new Session($session);
		$sessionsData['totaltraffic'] += $sessionObj->traffic;
		$sessionsData['totaltrafficin'] += $sessionObj->trafficIn;
		$sessionsData['totaltrafficout'] += $sessionObj->trafficOut;
		$sessionsData['sessions'][] = $sessionObj;
	}

	return $sessionsData;
}

function getPeersData(bool $geo = NULL) {
	global $exd;

	$peersRPC = $exd->send('peers');
	foreach($peersRPC as $peer){       
		if(!empty($peer['ip_addr'])){
				$peerObj = new Peer($peer);
				$peersData['peers'][] = $peerObj;
		}
	}
	return $peersData;
}

// 	// If not set, use config setting
// 	if(is_null($geo)){
// 		$geo = CONFIG::PEERS_GEO;
// 	}


// For sessions (geo)
function createSessionsGeo($peerinfo){
	global $countryList;
	global $hosterC;
	global $privateC;
	
	$noGeoData = false;
	
	// Check if peer file exists and enabled
	if (file_exists('data/geodatasessions.inc')){
		// Loads serialized stored peers from disk
		$serializedPeers = file_get_contents('data/geodatasessions.inc');
		$arrayPeers = unserialize($serializedPeers);
		// Check if client was restarted and IDs reassigned
		$oldestPeerId = reset($peerinfo)[0];
		$oldestPeerIp = getCleanIP(reset($peerinfo)[2]);
		$delete = false;
		// Checks if we know about the oldest peer, if not we assume that we don't known any peer
		foreach($arrayPeers as $key => $peer){
			if($oldestPeerIp == $peer[0]){
				$delete = true;
				// Either bitcoind was restarted or peer reconnected. Since peer is the oldest, all other peers we known disconnected
				if($oldestPeerId != $key){
					$delete = false;
				}
				break;
			}
			// For removing old peers that disconnected. Value of all peers that are still conected will be changed to 1 later. All peers with 0 at the end of the function will be deleted.
			$arrayPeers[$key][7] = 0;
		}
		// Oldest peer hasn't shown up -> Node isn't connected to any of the previously stored peers
		if(!$delete){
			unset($arrayPeers);
			$noGeoData = true;
		}
	}else{
		$noGeoData = true;
	}
	
	// Find Ips that we don't have geo data for and that are "older" than 2 minutes
    // First interation through all peers is used to collect ips for geo api call. This way the batch functionality can be used
	$ips = [];
	foreach($peerinfo as &$peer){
		$tempIP = getCleanIP($peer[2]);
        // Older than 5 minutes (180s)
		if ($peer[12] > 300 AND ($noGeoData OR !in_array($tempIP,array_column($arrayPeers,0)))){
			$ips[] = $tempIP;
		}
	}
	unset($peer);
	
	if(!empty($ips)){
		$ipData = getIpData($ips);
	}
    // 2nd interation through peers to create final peer list for output
	foreach($peerinfo as $session){
		// Creates new peer object
		$peerObj = new Session($session);

		// Checks if peer is new or if we can read data from disk (geodatapeers.inc)
		if($noGeoData OR !in_array($peerObj->ip,array_column($arrayPeers,0))){       
			if(isset($ipData[0]) AND $peerObj->age > 300){              
				$countryInfo = $ipData[array_search($peerObj->ip, array_column($ipData, 'query'))];
				$countryCode = checkCountryCode($countryInfo['countryCode']);
				$country = checkString($countryInfo['country']);
				$region = checkString($countryInfo['regionName']);
				$city = checkString($countryInfo['country']);
				$isp = checkString($countryInfo['isp']);         
				$hosted = checkHosted($isp);
                
				// Adds the new peer to the save list
				$arrayPeers[$peerObj->id] = array($peerObj->ip, $countryCode, $country, $region, $city, $isp, $hosted, 1);
                
                // Only counted for peers older than 5 minutes
                $newSessionsC++;
                
			}elseif($peerObj->age > 300){
				// If IP-Api.com call failed we set all data to Unknown and don't store the data
				$countryCode = "UN";
				$country = "Unknown";
				$region = "Unknown";
				$city = "Unknown";
				$isp = "Unknown";         
				$hosted = false;
                // Only counted for peers older than 5 minutes
                $newSessionsC++;                
			}else{
				// If peer is younger than 3 minutes
				$countryCode = "NX";
				$country = "New";
				$region = "New";
				$city = "New";
				$isp = "New";         
				$hosted = false;                
                
            }

		}else{
			$id = $peerObj->id;
			// Nodes that we know about but reconnected
			if(!isset($arrayPeers[$id])){
				$id = array_search($peerObj->ip, array_column($arrayPeers,0));
				$id = array_keys($arrayPeers)[$id];
			}
			$countryCode = $arrayPeers[$id][1];
			$country = $arrayPeers[$id][2];
			$region = $arrayPeers[$id][3];
			$city = $arrayPeers[$id][4];
			$isp = $arrayPeers[$id][5];
			$hosted = $arrayPeers[$id][6];
			$arrayPeers[$id][7] = 1;
		}

		// Counts the countries
		if(isset($countryList[$country])){       
			$countryList[$country]['count']++;
		}else{
            $countryList[$country]['code'] = $countryCode;
			$countryList[$country]['count'] = 1;
        }		

		// Adds country data to peer object
		$peerObj->countryCode = $countryCode;
		$peerObj->country = $country;
		$peerObj->region = $region;
		$peerObj->city = $city;
		$peerObj->isp = $isp;
		$peerObj->hosted = $hosted;
		if($hosted){
			$hosterC++;
		}else{
			$privateC++;
		}
		// Adds traffic of each peer to total traffic (in MB)
		$traffic += $peerObj->traffic;
		$trafficIn += $peerObj->trafficIn;
		$trafficOut += $peerObj->trafficOut;
	
		// Adds peer to peer array
		$peers[] = $peerObj;

	}

    // Removes all peers that the node is not connected to anymore.
    foreach($arrayPeers as $key => $peer){
        if($peer[7] == 0){
            unset($arrayPeers[$key]);
        }
    }

    $newSerializePeers = serialize($arrayPeers);
    file_put_contents('data/geodatasessions.inc', $newSerializePeers);
    
    return $peers;
}

// For Peers/servers
function createPeersGeo($peersRPC){
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
    // First interation through all peers is used to collect hosts for geo api call. This way the batch functionality can be used
	$ips = [];
	foreach($peersRPC as &$peer){
		$tempIp = $peer['ip_addr'];
		if($noGeoData OR !isset($arrayPeers[$tempIp])){
            // Don't query API for tor hosts
            if(!empty($peer['ip_addr'])){
                $ips[] = $tempIp;
            }
		}
	}
	unset($peer);
	
    // Get inforamtion from ip-api.com
	if(!empty($ips)){
		$ipData = getIpData($ips);
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
            $countryInfo = $ipData[array_search($peerObj->ip, array_column($ipData, 'query'))];
            $countryCode = checkCountryCode($countryInfo['countryCode']);
            $country = checkString($countryInfo['country']);
            $region = checkString($countryInfo['regionName']);
            $city = checkString($countryInfo['country']);
            $isp = checkString($countryInfo['isp']);         
            // Adds the new peer to the save list		
            $arrayPeers[$peerObj->ip] = array($peerObj->ip, $countryCode, $country, $region, $city, $isp, 1);          
        }else{
			$countryCode = $arrayPeers[$peerObj->ip][1];
			$country = $arrayPeers[$peerObj->ip][2];
			$region = $arrayPeers[$peerObj->ip][3];
			$city = $arrayPeers[$peerObj->ip][4];
			$isp = $arrayPeers[$peerObj->ip][5];
            $arrayPeers[$peerObj->ip][6] = 1;
		}          

		// Adds country data to peer object
		$peerObj->countryCode = $countryCode;
		$peerObj->country = $country;
		$peerObj->region = $region;
		$peerObj->city = $city;
		$peerObj->isp = $isp;
	
		// Adds peer to peer array
		$peers[] = $peerObj;
	}

    // Removes all peers that the node is not connected to anymore.
    foreach($arrayPeers as $key => &$peer){
        if($peer[6] == 0){
            unset($arrayPeers[$key]);
        }else{
           $peer[6] = 0; 
        }
    }

    // Save geodata
    $newSerializePeers = serialize($arrayPeers);
    file_put_contents('data/geodatapeers.inc', $newSerializePeers);
    
    return $peers;
}

function getIpData($ips){
	global $error;
	
	$numOfIps = count($ips);
	// A maximum of 2000 IPs can be checked (theoratical limit by ip-api.com is 15000 per min (150 x 100) if batch requests are used)
	if($numOfIps > 2000){
		$numOfIps = 2000;
	}	
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
	curl_setopt($ch, CURLOPT_URL,'http://ip-api.com/batch?fields=query,country,countryCode,regionName,city,isp,status');
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , CONFIG::PEERS_GEO_TIMEOUT); 
	curl_setopt($ch, CURLOPT_TIMEOUT, CONFIG::PEERS_GEO_TIMEOUT+1);
	
	// One call for each 100 ips
	$geojsonraw = [];
	foreach($postvars as $postvar){
		$postvarJson = json_encode($postvar);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $postvarJson);
		$result = json_decode(curl_exec($ch),true);
		if(empty($result)){
			$error = "Geo API (ip-api.com) Timeout";
			$result = [];
		}
		$geojsonraw = array_merge($geojsonraw, $result);
	}
	return $geojsonraw;
}

function createMapJs(int $peerCount){
	global $countryList;
    
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