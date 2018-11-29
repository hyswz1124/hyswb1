<?php
/**
 * 生成mysql数据字典
 */
header ( "Content-type: text/html; charset=utf-8" );

// 配置数据库
$table = $_REQUEST['table'];
$dbserver = "101.132.110.155";
$dbusername = "root1";
$dbpassword = 'qaz13067972109';
$database = 'purse';
// 其他配置
$title = '详细数据';
//$title1 = '禁止涉密 禁止传播';

$mysql_conn = @mysql_connect ( "$dbserver", "$dbusername", "$dbpassword" ) or die ( "Mysql connect is error." );
mysql_select_db ( $database, $mysql_conn );
mysql_query ( 'SET NAMES utf8', $mysql_conn );
$table_result = mysql_query ( 'show tables', $mysql_conn );
// 取得所有的表名
while ( $row = mysql_fetch_array ( $table_result ) ) {
    $tables [] ['TABLE_NAME'] = $row [0];
}

// 循环取得所有表的备注及表中列消息
foreach ( $tables as $k => $v ) {
    $sql = 'SELECT * FROM ';
    $sql .= 'INFORMATION_SCHEMA.TABLES ';
    $sql .= 'WHERE ';
    $sql .= "table_name = '{$v['TABLE_NAME']}'  AND table_schema = '{$database}'";
    $table_result = mysql_query ( $sql, $mysql_conn );
    while ( $t = mysql_fetch_array ( $table_result ) ) {
        $tables [$k] ['TABLE_COMMENT'] = $t ['TABLE_COMMENT'];
    }

    $sql = 'SELECT * FROM ';
    $sql .= 'INFORMATION_SCHEMA.COLUMNS ';
    $sql .= 'WHERE ';
    $sql .= "table_name = '{$v['TABLE_NAME']}' AND table_schema = '{$database}'";

    $fields = array ();
    $field_result = mysql_query ( $sql, $mysql_conn );
    while ( $t = mysql_fetch_array ( $field_result ) ) {
        $fields [] = $t;
    }
    $tables [$k] ['COLUMN'] = $fields;
}

$sql = 'SELECT * FROM ' . $table;
$data = array ();
$data_result = mysql_query ( $sql, $mysql_conn );
while ( $t = mysql_fetch_array ( $data_result ) ) {
    $data [] = $t;
}
//var_dump($data);exit;

$html = '';
// 循环所有表
foreach ( $tables as $k => $v ) {
    if($v['TABLE_NAME'] == $table){
        // $html .= '<p><h2>'. $v['TABLE_COMMENT'] . '&nbsp;</h2>';
        $html .= '<table  border="1" cellspacing="0" cellpadding="0" align="center">';
        $html .= '<caption>' . $v ['TABLE_NAME'] . '  ' . $v ['TABLE_COMMENT'] . '</caption>';
        $html .= '<tbody>';
//        $html .= '<tbody><tr><th>字段名</th><th>数据类型</th><th>默认值</th>
//        <th>允许非空</th>
//        <th>自动递增</th><th>备注</th></tr>';
        $html .= '<tr>';
//        $sql = 'SELECT * FROM '.$table;
//        $fields = array ();
//        $field_result = mysql_query ( $sql, $mysql_conn );
//        while ( $t = mysql_fetch_array ( $field_result ) ) {
//            $fields [] = $t;
//        }
        foreach ( $v ['COLUMN'] as $f ) {
            $html .= '<th>' . $f ['COLUMN_NAME'] . '</th>';
//            $html .= '<th class="c2">' . $f ['COLUMN_TYPE'] . '</th>';
//            $html .= '<th class="c3">&nbsp;' . $f ['COLUMN_DEFAULT'] . '</th>';
//            $html .= '<th class="c4">&nbsp;' . $f ['IS_NULLABLE'] . '</th>';
//            $html .= '<th class="c5">' . ($f ['EXTRA'] == 'auto_increment' ? '是' : '&nbsp;') . '</th>';
//            $html .= '<th class="c6">&nbsp;' . $f ['COLUMN_COMMENT'] . '</th>';
            $html .= '</th>';

        }
        $html .='</tr>';

        foreach ( $data as $da ) {
            $html .= '<tr>';

            foreach ( $v ['COLUMN'] as $ff ) {
                $html .= '<th>' . $da[$ff ['COLUMN_NAME']]. '</th>';
                $html .= '</th>';
            }
            $html .='</tr>';

        }


        $html .= '</tbody></table></p>';
    }



}
mysql_close ( $mysql_conn );

// 输出
echo '<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>' . $title . '</title>
<style>
body,td,th {font-family:"宋体"; font-size:12px;}
table{border-collapse:collapse;border:1px solid #CCC;background:#6089D4;}
table caption{text-align:left; background-color:#fff; line-height:2em; font-size:14px; font-weight:bold; }
table th{text-align:left; font-weight:bold;height:26px; line-height:25px; font-size:16px; border:3px solid #fff; color:#ffffff; padding:5px;}
table td{height:25px; font-size:12px; border:3px solid #fff; background-color:#f0f0f0; padding:5px;}
.c1{ width: 150px;}
.c2{ width: 130px;}
.c3{ width: 70px;}
.c4{ width: 80px;}
.c5{ width: 80px;}
.c6{ width: 300px;}
</style>
</head>
<body>';
echo '<h1 style="text-align:center;">' . $title . '</h1>';
//echo '<h1 style="text-align:center; color: red">' . $title1 . '</h1>';
echo $html;
echo '</body></html>';