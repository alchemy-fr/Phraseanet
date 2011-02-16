<?php

class twitpic
{
  /* 
   * variable declarations
   */
  var $post_url='http://twitpic.com/api/upload';
  var $post_tweet_url='http://twitpic.com/api/uploadAndPost';
  var $url='';
  var $post_data='';
  var $result='';
  var $tweet='';
  var $return='';
 
/*
* @param1 is the array of data which is to be uploaded
* @param2 if passed true will display result in the XML format, default is false
* @param3 if passed true will update status twitter,default is false
*/
 
  function __construct($data,$return=false,$tweet=false)
  {
    $this->post_data=$data;
    if(empty($this->post_data) || !is_array($this->post_data)) //validates the data
      $this->throw_error(0);
    $this->display=$return;
    $this->tweet=$tweet;
 
  }
 
  function post()
  {
    $this->url=($this->tweet)?$this->post_tweet_url:$this->post_url; //assigns URL for curl request based on the nature of request by user
    $this->makeCurl();
  }
  private function makeCurl()
  {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($curl, CURLOPT_URL, $this->url);
    curl_setopt($curl, CURLOPT_POST, 3);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $this->post_data);
    $this->result = curl_exec($curl);
    curl_close($curl);
    if($this->display)
    {
      header ("content-type: text/xml");
      echo $this->result ;
    }
 
  }
  private function throw_error($code) //handles few errors, you can add more
 
  {
    switch($code)
    {
      case 0:
        echo 'Think, you forgot to pass the data';
        break;
      default:
        echo 'Something just broke !!';
        break;
    }
    exit;
  }
} //class ends here
 
?>