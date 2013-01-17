<?php

$start = microtime(true);

// Set maximum execution time
ini_set('max_execution_time', 3600); 

// Unique words only?
$unique = true;

require("lemmatizer.php");

$ready = microtime(true);
$data_count = 0;

$db = new PDO("mysql:host=localhost;dbname=lemmatizer", "root", "");
$q = $db->query("SELECT * FROM word " . (($unique) ? "GROUP BY `value`":""));

$query_string = "";

$rows = $q->fetchAll();
$last = count($rows)-1;

foreach($rows as $key => $row) {

    $source = $row['source'];
    $input = $row['value'];
    
    $error = "";
    $obj = new Lemmatizer;
    $output = $obj->eat($input);
    if($obj->error) {
        $error = $obj->error;
    }
    $diff = round((microtime(true) - $start),5);
    unset($obj);

    if($data_count == 0) {

        $query_string = "INSERT INTO result (`input`, `output`, `process_time`, `issue`, `source`) VALUES ";
    }

    $query_string .= "('$input', '$output', '$diff', '$error', '$source'),";
    $data_count++;

    if($data_count == 750 || $key == $last) {

        var_dump($query_string);
        $query_string = preg_replace('/,$/', ';', $query_string);
        $db->exec($query_string);
        $data_count = 0;

    }
    
}

echo "Lemmatization took " . round((microtime(true) - $ready),5) . " seconds.";