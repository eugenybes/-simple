<?php
// 2012-11-07 BES
// simple MVC engine
// [Request + User Model] <--> [Controller <--> [Model <--> DB]] <--> View
//

error_reporting(E_ALL);

//---------------------------------------------------------------------
//--------------------------- Classes --------------------------------
//---------------------------------------------------------------------

//--------------------------- Request_Data-----------------------------

class Request_Data
{
    var $method; // GET, POST
    var $param; // input vars
function __construct() {
    if ( is_array ( $_GET ) ) {
        $this->method = 'GET';
        foreach ( $_GET as $name => $value )
            $this->param[$name] = mysql_real_escape_string ( $value );
    }

    if ( is_array ( $_POST ) ) {
        $this->method = 'POST';
        foreach ( $_POST as $name => $value )
            $this->param[$name] = mysql_real_escape_string ( $value );
    }
return true;
}
function __destruct() {
return true;
}
} // END class Request_Data

//--------------------------------User_Model---------------------------------

class User_Model
{
    var $user_data; //
    var $session_data; //
function __construct() {
    foreach ( $SERVER as $name => $value )
        $this->user_data[$name] = $value;
    if ( is_array ( $_SESSION ) ) {
        foreach ( $_SESSION as $name => $value )
            $this->session_data[$name] = $value;
    }
return true;
}
} // END class User_Model

//-----------------------------------MySQL_Data_Model--------------------------------

class MySQL_Data_Model
{
//    var $host; //
//    var $user; //
//    var $pass; //
//    var $connect; //
//    var $newconnect; //
    var $db; //
    var $sql_select_query; //
    var $tb ; //
    var $fields ; //
    var $where ; //
    var $order ; //
    var $limit ; //
    var $result; //
    var $result_table; //
    var $data; //

function __construct() {
/*
    if ( !$GLOBALS[$connectID] || $this->newconnect ) {
        $this->connect = mysql_connect( $this->host, $this->user, $this->pass );
        $GLOBALS[$connectID] = $this->connect;
    }
    if ( $GLOBALS[$db] != $this->db ) {
        @mysql_select_db ( $this->db );
        $GLOBALS[$db] = $this->db;
     }
*/
return true;
}

function send_select_query ( ) {
    @mysql_select_db ( $this->db ) or die ( 'cant connect to db '.$this->db );
    $this->result = @mysql_query ( $this->sql_select_query );
    for ( $this->result_table = array(); $row = @mysql_fetch_assoc($this->result); $this->result_table[] = $row );
//echo '<hr>DB = ' . $this->db . '<br>Query = ' . $this->sql_select_query . '<br>Error =  ' . mysql_error ( ). '<hr>';

return true;
}

function get_sql_select_query ( ) {
	$where = '';
	$order = '';
	$limit = '';
	$fields = implode ( ',' , $this->fields );
	$tables = implode ( ',' , $this->tb );
	if(isset($this->where)) $where = ' WHERE ' . implode ( ' AND ' , $this->where );
	if(isset($this->order)) $order = ' ORDER BY ' . implode ( ' AND ' , $this->order );
	if(isset($this->limit)) $limit = ' LIMIT '.$this->limit;
$this->sql_select_query = "SELECT $fields FROM $tables $where $order $limit";
echo $this->sql_select_query . '<br>';
return true;
}

function data_select ( ) {
	if ( empty ( $this->sql_select_query ) )
		$this->get_sql_select_query();
	$this->send_select_query ();
return true;
}

} // END class MySQL_Data_Model


//-----------------------------------Data_Block_News----------------------------------

class Data_Block_News extends MySQL_Data_Model
{
    var $db = 'site_news' ; // 
    var $tb = array ( 'dayjust'  ); // 
    var $fields = array ( 'uin_publish' , 'title' , 'lid', 'uin_source'  ); //
    var $where = array ( 'date = "2012-11-07" ' ); //
    var $order = array ( 'date DESC ' ); //
    var $limit = '0,5'; //
    var $pattern = '<div class = "title">
<a href="?id=$uin_publish">$title</a>
</div>
<div class="lid">$lid</div>
<div class="source">$source_title</div>';
    var $css = '.title {font-size: 24px;} 
.source {font-style: italic;}';

function add_source ( ) {
	foreach ( $this->result_table as $key => $val ) {
		if($val['uin_source'] > 0) {
			$source = new Data_Block_News;
			$source->tb = array ( 'source' ); // 
			$source->fields = array ( 'source_title' ); //
			$source->where = array ( 'uin_source = '.$val['uin_source'] ); //
			$source->order = NULL; //
			$source->limit = NULL; //
			$source->data_select ();
			$this->result_table[$key]['source_title'] = $source->result_table[0]['source_title'];
		}
	}
return true;
}
} // END class Data_Model

//-----------------------------------Data_Block_Source----------------------------------

class Data_Block_Source extends MySQL_Data_Model
{
    var $db = 'site_news' ; // 
    var $tb = array ( 'source' ); // 
    var $fields = array ( 'source_title' ); //
    var $where = array ( 'uin_source = 0'); //

} // END class Data_Block_Source



//-----------------------------------function View----------------------------------

function View ( $data , $pattern ) {
	$pattern = str_replace('"','\"',$pattern);
	$body = '';
	foreach ( $data as $key => $val ) {
		foreach ( $val as $key2 => $val2 ) {
			$$key2 = $val2 ;
		}
		eval ('$body .= "'.$pattern.'";');
	}
return $body;
}

//-----------------------------------------------------------------------------
//-----------------------------------Controll----------------------------------
//-----------------------------------------------------------------------------

mysql_connect ( '192.168.2.6' , 'admin' , 'ujkjdjkjvrf' );

$today_publications = new Data_Block_News;
$today_publications->data_select ();
$today_publications->add_source ();

$yeastoday_publications = new Data_Block_News;
$yeastoday_publications->where = array ( 'date = "2012-11-06" ' ); //
$yeastoday_publications->data_select ();
$yeastoday_publications->add_source ();
$yeastoday_publications->pattern = '<div class = "title_little"><a href="?id=$uin_publish">$title</a></div><div class="source">$source_title</div>';
$yeastoday_publications->css = '.title_little {font-size: 14px; text-decoration: underline;} ';

$one_publications = new Data_Block_News;
$one_publications->sql_select_query = ' SELECT uin_publish, title, lid, source_title FROM dayjust, source WHERE uin_publish = 58764 AND dayjust.uin_source = source.uin_source'; //
$one_publications->data_select ();





echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>TEST</title>
<style>
' . $today_publications->css . '
' . $yeastoday_publications->css . '
</style>
</head>
<body>
<h1>publications today</h1>
' . View ( $today_publications->result_table , $today_publications->pattern) . '
<h1>publications yeastoday</h1>
' . View ( $yeastoday_publications->result_table , $yeastoday_publications->pattern) . '</body>
<h1>one publications</h1>
' . View ( $one_publications->result_table , $one_publications->pattern) . '</body>

</body>
</html>';

mysql_close ( );