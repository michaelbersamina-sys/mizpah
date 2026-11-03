<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Manila');
function durationToMinutes($duration) {
    $duration=strtolower(trim((string)$duration));
    if(preg_match('/(\d+(?:\.\d+)?)\s*(?:hours?|hrs?)/i',$duration,$m)) return (int)round((float)$m[1]*60);
    if(preg_match('/(\d+)\s*(?:minutes?|mins?)/i',$duration,$m)) return (int)$m[1];
    if(is_numeric($duration)) { $n=(float)$duration; return $n>3?(int)$n:(int)round($n*60); }
    return 60;
}
function operatingMinutes($time,$openingHour) {
    if(!preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/',trim((string)$time),$m)) return null;
    $h=(int)$m[1];$min=(int)$m[2];if($h>23||$min>59)return null;if($h<$openingHour)$h+=24;return $h*60+$min;
}
$date=$_GET['date']??'';$time=$_GET['time']??'';$duration=$_GET['duration']??'1 Hour';
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||!strtotime($date)||trim($time)===''){echo json_encode([]);exit;}
$opening=((int)date('N',strtotime($date))>=6)?13:15;$start=operatingMinutes($time,$opening);$minutes=durationToMinutes($duration);
if($start===null||$minutes<=0||$start+$minutes>27*60){echo json_encode([]);exit;}
$therapists=[];$tr=mysqli_query($conn,"SELECT id,name FROM therapists WHERE status='Active' ORDER BY name ASC");if(!$tr){echo json_encode([]);exit;}
while($t=mysqli_fetch_assoc($tr))$therapists[(string)$t['id']]=['id'=>(int)$t['id'],'name'=>$t['name'],'available'=>true];
$stmt=mysqli_prepare($conn,"SELECT b.booking_time,b.duration,b.therapist_id,bt.therapist_id AS assigned_therapist_id FROM bookings b LEFT JOIN booking_therapists bt ON bt.booking_id=b.id WHERE b.booking_date=? AND b.status NOT IN ('Cancelled','Completed')");
if(!$stmt){echo json_encode(array_values($therapists));exit;}
mysqli_stmt_bind_param($stmt,'s',$date);mysqli_stmt_execute($stmt);$res=mysqli_stmt_get_result($stmt);
while($b=mysqli_fetch_assoc($res)){
  $bs=operatingMinutes($b['booking_time'],$opening);$bd=durationToMinutes($b['duration']??'1 Hour');if($bs===null||!($start<$bs+$bd&&$bs<$start+$minutes))continue;
  foreach(['therapist_id','assigned_therapist_id'] as $key){$id=(string)($b[$key]??'');if($id!==''&&isset($therapists[$id]))$therapists[$id]['available']=false;}
}
mysqli_stmt_close($stmt);echo json_encode(array_values($therapists));
