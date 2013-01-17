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
        // parse to words
        $file_contents = str_replace(PHP_EOL, ' ', $file_contents);
        $file_contents = preg_replace('/[A-Z ]+, (KOMPAS|Kompas).com (-|–) /', '', $file_contents);
        $file_contents = preg_replace('/([^a-zA-Z]+) ?- ?([^a-zA-Z]+)/', ' ', $file_contents);
        $file_contents = preg_replace('/[^a-zA-Z\- \r\n]+/', ' ', $file_contents);
        $file_contents = preg_replace('/([a-zA-Z]+) - ([a-zA-Z]+)/', '$1-$2', $file_contents);
        $file_contents = preg_replace('/\s[a-zA-Z]{1,3}\s/', ' ', $file_contents);
        $file_contents = preg_replace('/^[a-zA-Z]{1,3}\s/', '', $file_contents);
        $file_contents = preg_replace('/\s[a-zA-Z]{1,3}$/', '', $file_contents);
        $file_contents = preg_replace('/([a-zA-Z]+- |--| -|- | - )/', ' ', $file_contents);
        $file_contents = preg_replace('/^\s{1,}/', '', $file_contents);
        $file_contents = preg_replace('/\s{1,}$/', '', $file_contents);
        $file_contents = preg_replace('/\s{2,}/', ' ', $file_contents);
        
        $file_contents = strtolower($file_contents);

        $words = explode(" ", $file_contents);
        foreach($words as $word){

            $query_string .= "('". $word ."', ". strlen($word) .", '$category-$file_number'),";
        }

        //var_dump($words);

        // replace last comma with semicolon
        $query_string = preg_replace('/,$/', ';', $query_string);

        mysql_query($query_string, $con) or die("<br /><br />ERROR");

    }

    echo "<br />";

}

echo "<hr />Parsing took " . round((microtime(true) - $start),5) . " seconds.";

mysql_close($con);
?>