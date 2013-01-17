<?php

require_once('lemmatizer.php');

$time = null;
$error = null;
$result = null;

if(isset($_GET['word']) && preg_match("/^[a-zA-Z]+-?[a-zA-Z]*$/", $_GET['word'])) {
    
    $subject = strtolower($_GET['word']);

    $start = microtime(true);

    $lemmatizer = new Lemmatizer();
    $result = $lemmatizer->eat($subject);

    $time = round((microtime(true)-$start), 5);
    if($lemmatizer->error) $error = $lemmatizer->error;
    unset($lemmatizer);
}

?>

<!doctype html>
<html>

<head>
    <title>Indonesian Lemmatizer</title>
    <link rel="stylesheet" type="text/css" href="css/normalize.css">
    <link rel="stylesheet" type="text/css" href="css/global.css">
</head>

<body>
    <div class="list" id="top">&nbsp;</div>

    <div class="container">
        <div class="box">
            <div class="ribbon-wrapper">
                <div class="ribbon">DEMO</div>
            </div>
            <span class= "hero">Indonesian Lemmatizer</span>
            <p>Returns an Indonesian word to its lemma form / dictionary entry.</p>
            <br />
            <form action="index.php#result" method="GET">
                <input type="text" class="input" name="word" placeholder="Type a valid word.." />&emsp;
                <input type="submit" class="btn" value="Lemmatize" /><span class="icon">s</span>
            </form>

            <?php if($result!=null): ?>
            <br /><br />
            <a href="#result" class="scroll">see result for '<?php echo $subject?>'</a>
            <?php endif ?>
        </div>

        <?php if($result!=null): ?>
            <div class="box result">
                <a id="result">&nbsp;</a><br />
                <?php if($error): ?>
                    SORRY, LEMMA NOT FOUND <span style="font-size: 15pt">:(</span>
                <?php else: ?>
                    LEMMA
                <?php endif ?>
                <br /><span style="font-size: 28pt"><?php echo $result ?></span>
                <br /><br /><br /><hr />
                lemmatizing '<?php echo $subject ?>' took <?php echo $time ?> seconds.
                <br /><br />
                <a href="#top" class="scroll">try another word?</a>
            </div>
        <?php endif ?>
    </div>
    <script src="js/jquery-1.9.0.min.js"></script>
    <script>
        $(function($) {
            
            $(".scroll").click(function(event){     
                event.preventDefault();
                $('html,body').animate({scrollTop:$(this.hash).offset().top}, 500);
            });

        });
    </script>
</body>

</html>