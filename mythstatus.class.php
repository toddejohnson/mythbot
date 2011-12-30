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
class mythstat{
  private $url = "http://localhost:6544/xml";
  private $xml;
  private $fetchtime;
  private $kState = array(
    '-1' => 'Error',
    0 => 'None',
    1 => 'Watching Live TV',
    2 => 'Watching Pre Recorded',
    3 => 'Watching Recording',
    4 => 'Recording Only',
    5 => 'Changing State'
    );
  private $RecStatus = array(
    '-8' => 'Tuner Busy',
    '-7' => 'Low Disk Space',
    '-6' => 'Cancelled',
    '-5' => 'Deleted',
    '-4' => 'Aborted',
    '-3' => 'Recorded',
    '-2' => 'Recording',
    '-1' => 'Will Record',
    0  => 'Unknown',
    1  => 'Dont Record',
    2  => 'Previous Recording',
    3  => 'Current Recording',
    4  => 'Earlier Showing',
    5  => 'Too Many Recordings',
    6  => 'Not Listed',
    7  => 'Conflict',
    8  => 'Later Showing',
    9  => 'Repeat',
    10 => 'Inactive',
    11 => 'Never Record'
    );
  private $JobStatus = array(
    0 => 'Unknown',
    1 => 'Queued',
    2 => 'Pending',
    3 => 'Starting',
    4 => 'Running',
    5 => 'Stopping',
    6 => 'Paused',
    7 => 'Retrying',
    8 => 'Erroring',
    9 => 'Aborting',
    256 => 'Done (Invalid Status!)',
    272 => 'Finished',
    288 => 'Aborted',
    304 => 'Errored',
    320 => 'Cancelled',
    );
  private $JobCmds = array(
    0 => 'Run',
    1 => 'Pause',
    2 => 'Resume',
    4 => 'Stop',
    8 => 'Restart'
    );
  private $JobFlags = array(
    0 => 'No Flags',
    1 => 'Use Cutlist',
    2 => 'Live Rec',
    4 => 'External'
    );
  private $JobLists = array(
    1 => "All",
    2 => "Done",
    4 => "Not Done",
    8 => "Error",
    16 => "Recent"
    );
  private $JobTypes = array(
    0 => 'None',
    255 => 'System',
    1 => 'Transcode',
    2 => 'CommFlag',
    65280 => 'UserJob',
    256 => 'UserJob1',
    512 => 'UserJob2',
    1024 => 'UserJob3',
    2048 => 'UserJob4'
    );
   
  public function __construct(){
    $this->reload();
  }
  public function reload(){
    if (function_exists('file_get_contents'))
      $status = file_get_contents($this->url);
    else
      $status = implode("\n", file($this->url));
    $this->xml = simplexml_load_string($status);

    $this->fetchtime = strtotime((string)$this->xml['ISODate']);
  }
  public function dumpXML(){
    var_dump($this->xml);
  }
  public function getDTM(){
    return $this->xml['date'] .' '. $this->xml['time'];
  }
  public function getVersion(){
    return (string) $this->xml['version'];
  }
  public function getEncoders(){
    $count = (int) $this->xml->Encoders['count'];
    $enc = array();
    foreach($this->xml->Encoders->Encoder as $val){
      $tmp=NULL;
      $tmp->id = (int) $val['id'];
      $tmp->local = (bool) $val['local'];
      $tmp->connected = (bool) $val['connected'];
      $tmp->state = $this->kState[(int) $val['state']];
      $tmp->stateNum = (int) $val['state'];
      $tmp->hostname = (string) $val['hostname'];
      if($tmp->stateNum>0){
        $tmp->Program->Description = trim((string) $val->Program);
        $tmp->Program->Flags = (int) $val->Program['programFlags'];
        $tmp->Program->Title = (string) $val->Program['title'];
        $tmp->Program->programId = (string) $val->Program['programId'];
        $tmp->Program->catType = (string) $val->Program['catType'];
        $tmp->Program->category = (string) $val->Program['category'];
        $tmp->Program->seriesId = (string) $val->Program['seriesId'];
        $tmp->Program->endTime = (string) $val->Program['endTime'];
        $tmp->Program->airdate = (string) $val->Program['airdate'];
        $tmp->Program->lastModified = (string) $val->Program['lastModified'];
        $tmp->Program->subTitle = (string) $val->Program['subTitle'];
        $tmp->Program->stars = (int) $val->Program['stars'];
        $tmp->Program->repeat = (bool) $val->Program['repeat'];
        $tmp->Program->fileSize = (string) $val->Program['fileSize'];
        $tmp->Program->startTime = (string) $val->Program['startTime'];
        $tmp->Program->hostname = (string) $val->Program['hostname'];
        $tmp->Program->Channel->chanFilters = (string) $val->Program->Channel['chanFilters'];
        $tmp->Program->Channel->channelName = (string) $val->Program->Channel['channelName'];
        $tmp->Program->Channel->chanNum = (int) $val->Program->Channel['chanNum'];
        $tmp->Program->Channel->sourceId = (int) $val->Program->Channel['sourceId'];
        $tmp->Program->Channel->commFree = (bool) $val->Program->Channel['commFree'];
        $tmp->Program->Channel->chanId = (int) $val->Program->Channel['chanId'];
        $tmp->Program->Channel->callSign = (string) $val->Program->Channel['callSign'];
        $tmp->Program->Recording->dupInType = (int) $val->Program->Recording['dupInType'];
        $tmp->Program->Recording->dupMethod = (int) $val->Program->Recording['dupMethod'];
        $tmp->Program->Recording->recGroup = (string) $val->Program->Recording['recGroup'];
        $tmp->Program->Recording->encoderId = (int) $val->Program->Recording['encoderId'];
        $tmp->Program->Recording->recEndTs = (string) $val->Program->Recording['recEndTs'];
        $tmp->Program->Recording->recStatus = $this->RecStatus[(int) $val->Program->Recording['recStatus']];
        $tmp->Program->Recording->recStatusNum = (int) $val->Program->Recording['recStatus'];
        $tmp->Program->Recording->recordId = (int) $val->Program->Recording['recordId'];
        $tmp->Program->Recording->recProfile = (string) $val->Program->Recording['recProfile'];
        $tmp->Program->Recording->recType = (int) $val->Program->Recording['recType'];
        $tmp->Program->Recording->playGroup = (string) $val->Program->Recording['playGroup'];
        $tmp->Program->Recording->recPriority = (int) $val->Program->Recording['recPriority'];
        $tmp->Program->Recording->recStartTs = (string) $val->Program->Recording['recStartTs'];
      }
      $enc[]=$tmp;
    }
    return $enc;
  }
  public function getUpcoming(){
    $count = (int) $this->xml->Scheduled['count'];
    $prog = array();
    foreach($this->xml->Scheduled->Program as $val){
      $tmp=NULL;
      $tmp->Description = trim((string) $val);
      $tmp->Flags = (int) $val['programFlags'];
      $tmp->Title = (string) $val['title'];
      $tmp->programId = (string) $val['programId'];
      $tmp->catType = (string) $val['catType'];
      $tmp->category = (string) $val['category'];
      $tmp->seriesId = (string) $val['seriesId'];
      $tmp->endTime = (string) $val['endTime'];
      $tmp->airdate = (string) $val['airdate'];
      $tmp->lastModified = (string) $val['lastModified'];
      $tmp->subTitle = (string) $val['subTitle'];
      $tmp->stars = (int) $val['stars'];
      $tmp->repeat = (bool) $val['repeat'];
      $tmp->fileSize = (string) $val['fileSize'];
      $tmp->startTime = (string) $val['startTime'];
      $tmp->hostname = (string) $val['hostname'];
      $tmp->Channel->chanFilters = (string) $val->Channel['chanFilters'];
      $tmp->Channel->channelName = (string) $val->Channel['channelName'];
      $tmp->Channel->chanNum = (int) $val->Channel['chanNum'];
      $tmp->Channel->sourceId = (int) $val->Channel['sourceId'];
      $tmp->Channel->commFree = (bool) $val->Channel['commFree'];
      $tmp->Channel->chanId = (int) $val->Channel['chanId'];
      $tmp->Channel->callSign = (string) $val->Channel['callSign'];
      $tmp->Recording->dupInType = (int) $val->Recording['dupInType'];
      $tmp->Recording->dupMethod = (int) $val->Recording['dupMethod'];
      $tmp->Recording->recGroup = (string) $val->Recording['recGroup'];
      $tmp->Recording->encoderId = (int) $val->Recording['encoderId'];
      $tmp->Recording->recEndTs = (string) $val->Recording['recEndTs'];
      $tmp->Recording->recStatus = $this->RecStatus[(int) $val->Recording['recStatus']];
      $tmp->Recording->recStatusNum = (int) $val->Recording['recStatus'];
      $tmp->Recording->recordId = (int) $val->Recording['recordId'];
      $tmp->Recording->recProfile = (string) $val->Recording['recProfile'];
      $tmp->Recording->recType = (int) $val->Recording['recType'];
      $tmp->Recording->playGroup = (string) $val->Recording['playGroup'];
      $tmp->Recording->recPriority = (int) $val->Recording['recPriority'];
      $tmp->Recording->recStartTs = (string) $val->Recording['recStartTs'];
      $prog[] = $tmp;
    }
    return $prog;
  }
  public function getJobs(){
    $count = (int) $this->xml->JobQueue['count'];
    $job = array();
    foreach($this->xml->JobQueue->Job as $val){
      $tmp=NULL;
      $tmp->Description = trim((string) $val);
      $tmp->schedTime = (string) $val['schedTime'];
      $tmp->statusNum = (int) $val['status'];
      $tmp->status = $this->JobStatus[(int) $val['status']];
      $tmp->flagsNum = (int) $val['flags'];
      $tmp->flags = $this->JobFlags[(int) $val['flags']];
      $tmp->cmdsNum = (int) $val['cmds'];
      $tmp->cmds = $this->JobCmds[(int) $val['cmds']];
      $tmp->typeNum = (int) $val['type'];
      $tmp->type = $this->JobTypes[(int) $val['type']];
      $tmp->chanId = (int) $val['chanId'];
      $tmp->args = (string) $val['args'];
      $tmp->id = (int) $val['id'];
      $tmp->insertTime = (string) $val['insertTime'];
      $tmp->statusTime = (string) $val['statusTime'];
      $tmp->startTime = (string) $val['startTime'];
      $tmp->hostname = (string) $val['hostname'];
      $tmp->startTs = (string) $val['startTs'];
      $tmp->Program->Description = trim((string) $val->Program);
      $tmp->Program->Flags = (int) $val->Program['programFlags'];
      $tmp->Program->Title = (string) $val->Program['title'];
      $tmp->Program->programId = (string) $val->Program['programId'];
      $tmp->Program->catType = (string) $val->Program['catType'];
      $tmp->Program->category = (string) $val->Program['category'];
      $tmp->Program->seriesId = (string) $val->Program['seriesId'];
      $tmp->Program->endTime = (string) $val->Program['endTime'];
      $tmp->Program->airdate = (string) $val->Program['airdate'];
      $tmp->Program->lastModified = (string) $val->Program['lastModified'];
      $tmp->Program->subTitle = (string) $val->Program['subTitle'];
      $tmp->Program->stars = (int) $val->Program['stars'];
      $tmp->Program->repeat = (bool) $val->Program['repeat'];
      $tmp->Program->fileSize = (string) $val->Program['fileSize'];
      $tmp->Program->startTime = (string) $val->Program['startTime'];
      $tmp->Program->hostname = (string) $val->Program['hostname'];
      $tmp->Program->Channel->chanFilters = (string) $val->Program->Channel['chanFilters'];
      $tmp->Program->Channel->channelName = (string) $val->Program->Channel['channelName'];
      $tmp->Program->Channel->chanNum = (int) $val->Program->Channel['chanNum'];
      $tmp->Program->Channel->sourceId = (int) $val->Program->Channel['sourceId'];
      $tmp->Program->Channel->commFree = (bool) $val->Program->Channel['commFree'];
      $tmp->Program->Channel->chanId = (int) $val->Program->Channel['chanId'];
      $tmp->Program->Channel->callSign = (string) $val->Program->Channel['callSign'];
      $tmp->Program->Recording->dupInType = (int) $val->Program->Recording['dupInType'];
      $tmp->Program->Recording->dupMethod = (int) $val->Program->Recording['dupMethod'];
      $tmp->Program->Recording->recGroup = (string) $val->Program->Recording['recGroup'];
      $tmp->Program->Recording->encoderId = (int) $val->Program->Recording['encoderId'];
      $tmp->Program->Recording->recEndTs = (string) $val->Program->Recording['recEndTs'];
      $tmp->Program->Recording->recStatus = $this->RecStatus[(int) $val->Program->Recording['recStatus']];
      $tmp->Program->Recording->recStatusNum = (int) $val->Program->Recording['recStatus'];
      $tmp->Program->Recording->recordId = (int) $val->Program->Recording['recordId'];
      $tmp->Program->Recording->recProfile = (string) $val->Program->Recording['recProfile'];
      $tmp->Program->Recording->recType = (int) $val->Program->Recording['recType'];
      $tmp->Program->Recording->playGroup = (string) $val->Program->Recording['playGroup'];
      $tmp->Program->Recording->recPriority = (int) $val->Program->Recording['recPriority'];
      $tmp->Program->Recording->recStartTs = (string) $val->Program->Recording['recStartTs'];

      $job[] = $tmp;
    }
    return $job;
  }
  public function getMachineInfo(){
    $m=$this->xml->MachineInfo;
    $r->Storage->drive_total_free=(int) $m->Storage['drive_total_free'];
    $r->Storage->drive_total_used=(int) $m->Storage['drive_total_used'];
    $r->Storage->drive_1_used=(int) $m->Storage['drive_1_used'];
    $r->Storage->drive_1_dirs=(string) $m->Storage['drive_1_dirs'];
    $r->Storage->fsids=(string) $m->Storage['fsids'];
    $r->Storage->drive_total_total=(int) $m->Storage['drive_total_total'];
    $r->Storage->drive_1_total=(int) $m->Storage['drive_1_total'];
    $r->Storage->drive_total_dirs=(string) $m->Storage['drive_total_dirs'];
    $r->Storage->drive_1_free=(int) $m->Storage['drive_1_free'];
    $r->Load->avg1=(float) $m->Load['avg1'];
    $r->Load->avg2=(float) $m->Load['avg2'];
    $r->Load->avg3=(float) $m->Load['avg3'];
    $r->Guide->Description=trim((string) $m->Guide);
    $r->Guide->guideDays=(int) $m->Guide['guideDays'];
    $r->Guide->status=(string) $m->Guide['status'];
    $r->Guide->next=(string) $m->Guide['next'];
    $r->Guide->end=(string) $m->Guide['end'];
    $r->Guide->guideThru=(string) $m->Guide['guideThru'];
    $r->Guide->start=(string) $m->Guide['start'];
    return $r;
  }
}

?>
