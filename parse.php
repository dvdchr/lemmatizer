<?php

/*
    
    CONFIGURABLES

*/

// Set maximum execution time
ini_set('max_execution_time', 3600); 

// How many articles / category
$_AMOUNT = 5;

// What categories to be tested
$_CATEGORIES = array(
    0 => "bisnis",
    1 => "edukasi",
    2 => "regional",
    3 => "olahraga",
    4 => "sains"
    );

// Show result after each process
$_DEBUG = false;


/*
    
    CONNECTION STRING

*/

$con = mysql_connect("localhost","root","");
if (!$con) {
    die('Could not connect: ' . mysql_error());
}

mysql_select_db("lemmatizer", $con);

$start = microtime(true);
echo "PARSED DATA: <br />";
foreach($_CATEGORIES as $category) {

    echo "<strong>$category</strong><br />";

    for($i=1; $i<=$_AMOUNT; $i++) {

        $query_string = "INSERT INTO `word` (`value`, `length`, `source`) VALUES ";

        // formats 1 to 001, 12 to 012, etc
        $file_number = str_pad($i, 3, "0", STR_PAD_LEFT);
        $file_name = "test_data/$category/$file_number.txt";
        
        // fetch file
        $file_contents = file_get_contents($file_name, FILE_USE_INCLUDE_PATH);
        echo "$category/$file_number.txt</br>";

        /*
            
            DATA FORMATTING

        */
        $file_contents = strtolower($file_contents);
        if($_DEBUG) {
            echo "<strong>original:</strong><br />";
            echo var_dump($file_contents) . "<br /><br />";
        }
        //$file_contents = str_replace(PHP_EOL, ' ', $file_contents);
        $file_contents = preg_replace('/[a-z ]+, kompas.com [^\s] /', '', $file_contents);
        if($_DEBUG) {    
            echo "<strong>Removed KOMPAS part</strong><br />";
            echo var_dump($file_contents) . "<br /><br />";
        }
        // $file_contents = preg_replace('/([^a-zA-Z]+) ?- ?([^a-zA-Z]+)/', '', $file_contents);
        $file_contents = preg_replace('/[^a-z\- ]+/', ' ', $file_contents);
        if($_DEBUG) {
            echo "<strong>Removed non-alphabet chars</strong><br />";
            echo var_dump($file_contents) . "<br /><br />";
        }

        $file_contents = preg_replace('/([a-z]+) - ([a-z]+)/', '$1-$2', $file_contents);
        if($_DEBUG) {
            echo "<strong>Join separated hyphenated words</strong><br />";
            echo var_dump($file_contents) . "<br /><br />";
        }

        $file_contents = preg_replace('/([a-zA-Z]+- |--| -|- | - )/', ' ', $file_contents);
        if($_DEBUG) {
            echo "<strong>Remove additional stripe character</strong><br />";
            echo var_dump($file_contents) . "<br /><br />";
        }

        $file_contents = preg_replace('/^[a-z]{1,3}\s/', '', $file_contents);
        if($_DEBUG) {
            echo "<strong>Remove words less than 3 chars - At the Start</strong><br />";
            echo var_dump($file_contents) . "<br /><br />";
        }

        $file_contents = preg_replace('/\s[a-z]{1,3}\s/', '  ', $file_contents);
        if($_DEBUG) {
            echo "<strong>Removed words less than 3 chars - First Round</strong><br />";
            echo var_dump($file_contents) . "<br /><br />";
        }

        $file_contents = preg_replace('/\s[a-z]{1,3}\s/', '  ', $file_contents);
        if($_DEBUG) {
            echo "<strong>Removed words less than 3 chars - Second Round</strong><br />";
            echo var_dump($file_contents) . "<br /><br />";
        }

        $file_contents = preg_replace('/^\s{1,}/', '', $file_contents);
        if($_DEBUG) {
            echo "<strong>Removed whitespace character at the start</strong><br />";
            echo var_dump($file_contents) . "<br /><br />";
        }

        $file_contents = preg_replace('/\s{1,}$/', '', $file_contents);
        if($_DEBUG) {
            echo "<strong>Removed whitespace character at the end</strong><br />";
            echo var_dump($file_contents) . "<br /><br />";
        }

        $file_contents = preg_replace('/\s{2,}/', ' ', $file_contents);
        if($_DEBUG) {
            echo "<strong>Remove multiple whitespaces</strong><br />";
            echo var_dump($file_contents) . "<br /><br />";
        }

        $words = explode(" ", $file_contents);
        if($_DEBUG) echo "<strong>Final Result</strong><br />";
        echo var_dump($words) . "<br /><br />";

        foreach($words as $word){

            $query_string .= "('". $word ."', ". strlen($word) .", '$category-$file_number'),";
        }

        // replace last comma with semicolon
        $query_string = preg_replace('/,$/', ';', $query_string);

        mysql_query($query_string, $con) or die("<br /><br />ERROR");

    }

    echo "<br />";

}

echo "<hr />Parsing took " . round((microtime(true) - $start),5) . " seconds.";

mysql_close($con);
?>