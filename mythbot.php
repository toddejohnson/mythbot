#!/usr/bin/php
<?php
/*
Copyright 2009 Todd E. Johnson todd@toddejohnson.net

This file is part of MythBot.  

MythBot is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/
// activate full error reporting
//error_reporting(E_ALL & E_STRICT);

require_once 'XMPPHP/XMPP.php';
require_once 'mythstatus.class.php';
require_once 'config.php';

#Use XMPPHP_Log::LEVEL_VERBOSE to get more logging for error reports
#If this doesn't work, are you running 64-bit PHP with < 5.2.6?
$conn = new XMPPHP_XMPP($config['server'], $config['port'], $config['username'], $config['password'], $config['resource'], $config['domain'], $printlog=true, $loglevel=XMPPHP_Log::LEVEL_INFO);
$conn->autoSubscribe();
$vcard_request = array();

try{
  $conn->connect();
  while(!$conn->isDisconnected()){
    $payloads = $conn->processUntil(array('message', 'presence', 'end_stream', 'session_start', 'vcard'), 30);
    foreach($payloads as $event){
      $pl = $event[1];
      switch($event[0]){
        case 'message': 
          print "---------------------------------------------------------------------------------\n";
          print "Message from: {$pl['from']}\n";
          if(isset($pl['subject'])) print "Subject: {$pl['subject']}\n";
          print $pl['body'] . "\n";
          print "---------------------------------------------------------------------------------\n";
          $cmd = explode(' ', strtolower($pl['body']));
          if($cmd[0] == 'help'){
            $conn->message($pl['from'], $body="help\ntuners\nupcoming\njobs\ninfo", $type=$pl['type']);
          }elseif($cmd[0] == 'tuners'){
            $mst=new mythstat();
            $obj=$mst->getEncoders();
            if(count($obj)>0){
              $arry=array();
              foreach($obj as $var){
                $str="Tuner {$var->id} {$var->state}";
                if($var->stateNum>0){
                  $str.=": '{$var->Program->Title}' on {$var->Program->Channel->callSign}";
                  $str.=" ends at ". format_dtm($var->Program->endTime,'g:i a') .".";
                }
                $arry[]=$str;
              }
              $body=implode("\n",$arry);
            }else{
              $body="No Tuners";
            }
            $conn->message($pl['from'], $body, $type=$pl['type']);
          }elseif($cmd[0] == 'upcoming'){
            $mst=new mythstat();
            $obj=$mst->getUpcoming();
            if(count($obj)>0){
              $arry=array();
              foreach($obj as $var){
                $str=format_dtm($var->startTime,'D n/j g:i a') ." - Tuner {$var->Recording->encoderId}";
                $str.=" - {$var->Channel->callSign} - {$var->Title}";
                $str.=" - {$var->subTitle}";
                $arry[]=$str;
              }
              $body=implode("\n",$arry);
            }else{
              $body="No Tuners";
            }
            $conn->message($pl['from'], $body, $type=$pl['type']);
          }elseif($cmd[0] == 'jobs'){
            $mst=new mythstat();
            $obj=$mst->getJobs();
            if(count($obj)>0){
              $arry=array();
              foreach($obj as $var){
                $str=format_dtm($var->schedTime,'D n/j g:i a') ." - Tuner {$var->Program->Recording->encoderId}";
                $str.=" - {$var->Program->Channel->callSign} - {$var->Program->Title}";
                $str.=" - {$var->type} - {$var->status}";
                $str.="\n{$var->Description}";
                $arry[]=$str;
              }
              $body=implode("\n",$arry);
            }else{
              $body="No Jobs";
            }
            $conn->message($pl['from'], $body, $type=$pl['type']);
          }elseif($cmd[0] == 'info'){
            $mst=new mythstat();
            $obj=$mst->getMachineInfo();
            if($cmd[1] == 'storage'){
              $percent=floor($obj->Storage->drive_total_free/$obj->Storage->drive_total_total*100);
              $body="Storage {$obj->Storage->drive_total_free}MB($percent%) Free.";
            }elseif($cmd[1] == 'load'){
              $body="Load {$obj->Load->avg1}, {$obj->Load->avg2}, {$obj->Load->avg3}.";
            }elseif($cmd[1] == 'guide'){
              $body="Last mythfilldatabase ". format_dtm($obj->Guide->start) ." to ". format_dtm($obj->Guide->end) ."\n";
              $body.="{$obj->Guide->status}\n";
              $days=ceil((strtotime($obj->Guide->guideThru)-time())/(60*60*24));
              $body.="Next:". format_dtm($obj->Guide->next) ."  Data until:". format_dtm($obj->Guide->guideThru) ." ($days days)";
            }else{
              $body="info storage\ninfo load\ninfo guide";
            }
            $conn->message($pl['from'], $body, $type=$pl['type']);
          }
        break;
        case 'presence':
          print "Presence: {$pl['from']} [{$pl['show']}] {$pl['status']}\n";
        break;
        case 'session_start':
          print "Session Start\n";
          $conn->getRoster();
          $conn->presence($status='');
        break;
        case 'vcard':
          // check to see who requested this vcard
          $deliver = array_keys($vcard_request, $pl['from']);
          // work through the array to generate a message
          print_r($pl);
          $msg = '';
          foreach($pl as $key => $item){
            $msg .= "$key: ";
            if(is_array($item)){
              $msg .= "\n";
              foreach($item as $subkey => $subitem){
                $msg .= "  $subkey: $subitem\n";
              }
            }else{
              $msg .= "$item\n";
            }
          }
          // deliver the vcard msg to everyone that requested that vcard
          foreach($deliver as $sendjid){
            // remove the note on requests as we send out the message
            unset($vcard_request[$sendjid]);
            $conn->message($sendjid, $msg, 'chat');
          }
        break;
      }
    }
  }
}catch(XMPPHP_Exception $e){
    die($e->getMessage());
}
function format_date($date,$format="n/j/Y"){
	return date($format,strtotime($date));
}
function format_dtm($date,$format="n/j/Y g:i a"){
	return date($format,strtotime($date));
}
