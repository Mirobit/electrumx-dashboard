<?php

namespace App;

function createMainContent(){
	$sessionsData = getSessionsData();
	$content['node'] = new Node();
	if(Config::SESSIONS_GEO){
		$content['map'] = createMapJs($content['node']->sessionsC, $sessionsData['countrylist']);
	}
  $content['geo'] = Config::SESSIONS_GEO;
  if(isset($sessionsData['api'])) $content['api'] = $sessionsData['api'];
  $content['chartdata'] = getTopClients($sessionsData['sessions']);
	$content['totaltrafficin'] = $sessionsData['totaltrafficin'];
	$content['totaltrafficout'] = $sessionsData['totaltrafficout'];

  return $content;
}

function createSessionsContent(){
  $sessionsData = getSessionsData();
  $content['details'] = getMostPop($sessionsData['sessions']);
  $content['sessions'] = $sessionsData['sessions'];
  $content['sessionsc'] = count($sessionsData['sessions']);
	$content['details']['sslp'] = round($content['details']['sslc']/$content['sessionsc'],2)*100;
	$content['totaltraffic'] = $sessionsData['totaltraffic'];
	$content['totaltrafficin'] = $sessionsData['totaltrafficin'];
	$content['totaltrafficout'] = $sessionsData['totaltrafficout'];
	$content['totaltrafficinper'] = round($sessionsData['totaltrafficin']/$sessionsData['totaltraffic'],2)*100;
  $content['geo'] = Config::SESSIONS_GEO;
  if(isset($sessionsData['api'])) $content['api'] = $sessionsData['api'];

  return $content;
}

function createPeersContent(){
    $peersData = getPeersData();
    $content['details'] = getMostPop($peersData['peers'], false);
    $content['peers'] = $peersData['peers'];
    $content['peersc'] = count($peersData['peers']);
    $content['details']['sslp'] = round($content['details']['sslc']/$content['peersc'],2)*100;
    $content['details']['tcpp'] = round($content['details']['tcpc']/$content['peersc'],2)*100;
    $content['details']['torp'] = round($content['details']['torc']/$content['peersc'],2)*100;
    $content['geo'] = Config::PEERS_GEO;
    if(isset($peersData['api'])) $content['api'] = $peersData['api'];

    return $content;
}

function createBlocksContent(){
    global $exd;

    $content = [];
	$content["totalTx"] = 0;
	$content["totalFees"] = 0;
	$content["totalSize"] = 0;
	$content["segwitCount"] = 0;

	$blockHash = $bitcoind->getbestblockhash();

	for($i = 0; $i < Config::DISPLAY_BLOCKS; $i++){
		$block = $bitcoind->getblock($blockHash);
		$content["blocks"][$block["height"]]["hash"] = $block["hash"];
		$content["blocks"][$block["height"]]["size"] = round($block["size"]/1000,2);
		$content["totalSize"] += $block["size"];
		$content["blocks"][$block["height"]]["versionhex"] = $block["versionHex"];
		$content["blocks"][$block["height"]]["voting"] = getVoting($block["versionHex"]);
		$content["blocks"][$block["height"]]["time"] = getDateTime($block["time"]);
		$content["blocks"][$block["height"]]["mediantime"] = getDateTime($block["mediantime"]);
		$content["blocks"][$block["height"]]["timeago"] = round((time() - $block["time"])/60);
		$content["blocks"][$block["height"]]["coinbasetx"] = $block["tx"][0];
		$coinbaseTx = $bitcoind->getrawtransaction($block["tx"][0], 1);
		if($coinbaseTx["vout"][0]["value"] != 0){
			$content["blocks"][$block["height"]]["fees"] = round($coinbaseTx["vout"][0]["value"] - 12.5, 4);
		}else{
			$content["blocks"][$block["height"]]["fees"] = round($coinbaseTx["vout"][1]["value"] - 12.5, 4);
		}
		$content["totalFees"] += $content["blocks"][$block["height"]]["fees"];
		$content["blocks"][$block["height"]]["txcount"] = count($block["tx"]);
		$content["totalTx"] += $content["blocks"][$block["height"]]["txcount"];
		if(substr($block["versionHex"], -1)){
			$content["segwitCount"]++;
		}
		$blockHash = $block["previousblockhash"];
	}
	$content["avgTxSize"] = round(($content["totalSize"]/($content["totalTx"]))/1000,2);
	$content["avgSize"] = round($content["totalSize"]/(Config::DISPLAY_BLOCKS*1000),2);
	$content["totalSize"] = round($content["totalSize"]/1000000,2);
	$content["avgFee"] = round($content["totalFees"]/Config::DISPLAY_BLOCKS,2);
	$content["totalFees"] = round($content["totalFees"],2);
	$content["segwitPer"] = ($content["segwitCount"]/Config::DISPLAY_BLOCKS)*100;
	$content["numberOfBlocks"] = Config::DISPLAY_BLOCKS;
	$content["timeframe"] = round(end($content["blocks"])["timeago"]/60,0);

	return $content;
}

/**
 * @param null $editID
 * @return mixed
 */
function createRulesContent($editID = NULL){

	$rulesContent['rules'] = Rule::getRules();
	$rulesContent['jobToken'] = substr(hash('sha256', CONFIG::PASSWORD."ebe8d532"),0,24);
	$rulesContent['editRule'] = new Rule();

	if (file_exists('data/rules.log')){
		$log = file_get_contents('data/rules.log');
	}else{
		$log = "No logs available";
	}
	$rulesContent['log'] = $log;


	if(!is_null($editID)){
		$response = Rule::getByID($_GET['id']);
		if($response != FALSE){
			$rulesContent['editRule'] = $response;
		// TODO: Return repsonse to controller
		}else{
			$error = "Couldn't find Rule!";
		}
	}

	return $rulesContent;
}

?>
