<?php
$vtuber_htmlpath="src/Vtuber.html";

$HtmlData=home($vtuber_htmlpath);

echo $HtmlData;


function home($path){
  $data = array(
    "{{Title}}"=>"this is title",
    "{{Now}}"=>"2021/10/07/12/10"
  );
  return replace($data,$path);
}

function htmldata($filepath){
  $htmldata=file_get_contents($filepath);
  return $htmldata;
}

function replace($data,$htmlpath){
  $htmldata=file_get_contents($htmlpath);
  foreach($data as $key=>$value){
    $htmldata=str_replace($key,$value,$htmldata);
  }
  return $htmldata;
}

?>
