<?php

require('lemmatizer-debug.php');

/**

    TESTING / DEBUG LINES

**/

$subject = "pertanggungjawabannyalah";
$lemmatizer = new Lemmatizer;

if(isset($_GET['word']) && preg_match("/^[a-zA-Z]+-?[a-zA-Z]*$/", $_GET['word'])) $subject = strtolower($_GET['word']);

echo "<form action='#' method='GET'><input type='text' name='word' /><input type='submit' value='Lemmatize' /></form><hr />";
echo "Input: <strong>$subject</strong><br />";

$start = microtime(true);
$result = $lemmatizer->eat($subject);
$removed = $lemmatizer->getRemoved();
echo "<br /><br />";
foreach($removed as $key => $affix) {

    if($key == "derivational_prefix" && $affix!='') {

        echo "<br />Removed $key : ";
        foreach($lemmatizer->complex_prefix_tracker as $array) {
            $value = reset($array);
            echo key($array) . ",";
            if($value) {
                echo "  added: $value";
            }
        }

    } else if($affix!='') {

        echo "<br />Removed $key : " . $affix;

    }

}

$result = (!$lemmatizer->error) ? $result : "$result ($lemmatizer->error)";
echo "<br /><br /><hr />Result: <strong>$result</strong><br />Total query performed: $lemmatizer->total_lookup<br />";

echo "Lemmatization took " . round((microtime(true) - $start),5) . " seconds.";
