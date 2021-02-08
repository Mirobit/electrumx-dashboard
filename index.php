<?php

namespace App;

// Error reporting
error_reporting(E_ALL); 
ini_set('ignore_repeated_errors', TRUE); 
ini_set('display_startup_errors',TRUE); 
ini_set('display_errors', TRUE);

// Security
ini_set('session.cookie_httponly', '1');
header('Referrer-Policy: same-origin');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src data: 'self'");

require_once 'src/Autoloader.php';
Autoloader::register();


// Check IP, deny access if not allowed
if(!(empty(Config::ACCESS_IP) OR $_SERVER['REMOTE_ADDR'] == "127.0.0.1" OR $_SERVER['REMOTE_ADDR'] == "::1" OR $_SERVER['REMOTE_ADDR'] == Config::ACCESS_IP)){
   	header('Location: login.html');
	exit; 
}

// Cronjob Rule Run
if(isset($_GET['job']) AND $_GET['job'] === substr(hash('sha256', Config::PASSWORD."654e8nm67"),0,24)){
    require_once 'src/Utility.php';
    $exd = new RPC(Config::RPC_IP, Config::RPC_PORT);
	Rule::run();
	exit;
}

// Start check user session
session_start();
$passToken = hash('sha256', Config::PASSWORD."5be81tz6");

// Active Session
if(isset($_SESSION['login']) AND $_SESSION['login'] === TRUE){
	// Nothing needs to be done
// Login Cookie available	
}elseif(isset($_COOKIE["Login"]) AND $_COOKIE["Login"] === $passToken){
		$_SESSION['login'] = TRUE;
		$_SESSION["csfrToken"] = hash('sha256', random_bytes(20));
// Password disabled
}elseif(Config::PASSWORD === "") {
	// Nothing needs to be done
// Login		
}elseif(!isset($_SESSION['login']) AND isset($_POST['password']) AND $_POST['password'] === Config::PASSWORD){
	ini_set('session.cookie_httponly', '1');
	$passHashed = hash('sha256', Config::PASSWORD);
		$_SESSION['login'] = TRUE;
		$_SESSION["csfrToken"] = hash('sha256', random_bytes(20));
		if(isset($_POST['stayloggedin'])){		
			setcookie("Login", $passToken, time()+2592000, "","",FALSE, TRUE);
		}
// Not logged in or invalid data
}else{
	header('Location: login.html');
	exit; 	
}

// Load ulitily and content creator functions
require_once 'src/Utility.php';
require_once 'src/Content.php';

// Globals
$error = "";
$message = "";

try {
    $exd = new RPC(Config::RPC_IP, Config::RPC_PORT);
} catch (\Exception $e) {
    $error = $e;
}


// Content
// Main Page
if(empty($_GET) OR $_GET['p'] == "main") {   
    try{
    $content = createMainContent();
    }catch(\Exception $e) {
       $error = "Server offline or incorrect RPC data";
    }
    $data = array('section' => 'main', 'title' => 'Home', 'content' => $content);   
   
// Sessions Page   
}elseif($_GET['p'] == "sessions") {
    
    // Check if command
    if(isset($_GET['c']) AND $_GET['t'] == $_SESSION["csfrToken"]){           
        // Disconnect Command
        if($_GET['c'] == "disconnect"){
            if(is_numeric($_GET['id'])){
                $ip = $match[1];
                try {
                    $result = $exd->send('disconnect', $_GET['id']);
                    // Sleep necessary otherwise essions is still returned by electrumx
                    sleep(1);
                    $message = "Session successfully closed";
                } catch (\Exception $e) {
                    $error = $e;
                }
            }else{
                $error = "Invalid Session ID";
            }
        // Add Hoster Command
        }elseif($_GET['c'] == "addhoster"){
            if(preg_match("/^[0-9a-zA-Z-,\. ]{3,40}$/", $_GET['n'])) {
                $hosterJson = file_get_contents('data/hoster.json');
                $hoster = json_decode($hosterJson);
                $hoster[] = $_GET['n'];
                file_put_contents('data/hoster.json',json_encode($hoster));
                updateHosted($_GET['n'], true);
                $message = "Hoster succesfully added";    
            }else{
                $error = "Invalid Hoster";
            }
        }
        // Apply rules          
        elseif($_GET['c'] == "run"){
            try{
                Rule::run();
            }catch(\Exception $e){
                $error = "Error while running rules";
            }
			if(empty($e)){
				$message = "Rules succesfully run. See log file for details";
			}
        }
    }
    // Content
    $content = createSessionsContent();
    
    // Create page specfic variables
    $data = array('section' => 'sessions', 'title' => 'Sessions', 'content' => $content);

// Hoster Page    
}elseif($_GET['p'] == "hoster"){    
    
    $hosterList = json_decode(file_get_contents('data/hoster.json'),true);

	if(isset($_GET['c']) AND $_GET['t'] == $_SESSION["csfrToken"]){
    // Remove Hoster Command
		if($_GET['c'] == "remove"){
			if(preg_match("/^[0-9a-zA-Z-,\. ]{3,40}$/", $_GET['n'])) {
				if(($key = array_search($_GET['n'], $hosterList)) != false) {
					unset($hosterList[$key]);
					file_put_contents('data/hoster.json',json_encode($hosterList)); 
					updateHosted($_GET['n'], false);
					$message = "Hoster succesfully removed";  
				}else{
					$error = "Hoster not found";    
				}            
			}else{
				$error = "Invalid Hoster";
			}
	   // Add Hoster Command
		}elseif($_GET['c'] == "add"){
			if(preg_match("/^[0-9a-zA-Z-,\. ]{3,40}$/", $_GET['n'])) {
				if(!in_array($_GET['n'], $hosterList)){
					$hosterList[] = $_GET['n'];
					file_put_contents('data/hoster.json',json_encode($hosterList));
					updateHosted($_GET['n'], true);
					$message = "Hoster succesfully added"; 
				}else{
					$error = "Hoster already in list";
				}
			}else{
				$error = "Invalid Hoster";
			}
		}
    }
	
    $content = json_decode(file_get_contents('data/hoster.json'), TRUE);
    // Sort Hoster ascending
    natcasesort($content);
    
    // Create page specfic variables
    $data = array('section' => 'hoster', 'title' => 'Hoster Manager', 'content' => $content);
    

// Rules Page
}elseif($_GET['p'] == "rules") {
    
	$editID = NULL;
    // Check if commands needs to be run   
    if(isset($_GET['c'])  AND $_GET['t'] == $_SESSION["csfrToken"]){      
		// Save new or edited rule    
        if($_GET['c'] == "save"){            
            $rule = new Rule();
            $response = $rule->save($_POST);
                if($response){
                    $message = "Rule succesfully saved";
                }else{
                    $error = "Invalid rule data";
                }
 		// Apply rules          
        }elseif($_GET['c'] == "run"){
            try{
                Rule::run();
            }catch(\Exception $e){
                $error = "Error while running rules";
            }
			if(empty($e)){
				$message = "Rules succesfully run. See log file for details";
			}
 		// Edit rule           
        }elseif($_GET['c'] == "edit"){
            if(ctype_digit($_GET['id'])){
                $editID = $_GET['id'];
            }else{
                $error = "Invalid rule ID";
            }
 		// Delete single rule or all          
        }elseif($_GET['c'] == "delete"){         
            if(isset($_GET['id']) AND ctype_digit($_GET['id'])){
                $reponse =  Rule::deleteByID($_GET['id']);
                if($reponse){
                    $message = "Rule succesfully deleted";                    
                }else{
                    $error = "Could not delete rule";   
               }
		    }elseif(!isset($_GET['id'])){
			   $reponse =  Rule::deleteAll();
                if($reponse){
                    $message = "Rules succesfully deleted";                    
                }else{
                    $error = "Could not delete rules";   
               }
            }else{
               $error = "Invalid rule ID";
            }
		// Reset rule counter
        }elseif($_GET['c'] == "resetc"){
			$reponse =  Rule::resetCounter();
			if($reponse){
                    $message = "Counter succesfully reseted";                    
            }else{
                    $error = "Could not reseted counter";   
            }			
		// Delete logfile
        }elseif($_GET['c'] == "dellog"){
			$reponse =  Rule::deleteLogfile();
			if($reponse){
                    $message = "Logfile succesfully deleted";                    
            }else{
                    $error = "Could not delete logfile";   
            }
		}
    }
    
	$content = createRulesContent($editID);
    
    $data = array('section' => 'rules', 'title' => 'Rules Manager', 'content' => $content); 
     
// Peers Page    
}elseif($_GET['p'] == "peers") {
	
	$content = createPeersContent();
    $data = array('section' => 'peers', 'title' => 'Peers', 'content' => $content);  
    
// About Page    
}elseif($_GET['p'] == "about") {
    $data = array('section' => 'about', 'title' => 'About'); 
	
}else{
	header('Location: index.php');
	exit; 	
}


// Create HTML output
if(isset($error)){
    $data['error'] = $error;
}
if(isset($message)){
    $data['message'] = $message;
}

$tmpl = new Template($data);
echo $tmpl->render();

?>