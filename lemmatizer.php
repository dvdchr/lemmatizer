<?php

    /**

        LEMMATIZER FOR INDONESIAN LANGUAGE

        @author         David Christiandy <david.christiandy@gmail.com>
                        Rolando <rolando_kai@hotmail.com>
                        Stephen Hadisurja <stephen.hadisurja@gmail.com>

        @version        0.6-debug   [revision by David]

        @date           15 Jan 2013, 03:02

        @description    @todo LOW - explain what all this fuzz is about. bla
                        bla bla bla.. lorem ipsum dolor sit amet

        -------------------------------------------------------------------
    
        FAILED CASES:
        ------------
        + berupa (overstemming)
        + menyatakan, penyanyi (incomplete rule) DONE (rule addition)


        CHANGELOG:
        ---------
        VERSION 0.6 - 15 Jan 2013 03:02
        + removed TODO List for now
        + added 'FAILED CASES' section for better accuracy improvement
        + added few comments here and there
        + optimized lookup function
        + optimized query calls
        + added prefix stacking/combination constraint
        + modified recoding path order for rule 16 & 28
        + modified rule 19 & 31 for lemmas like 'nyanyi', 'nyata'

        VERSION 0.5 - 12 Jan 2013 23:18
        + [0.5a] hotfixed + reenabled disallowed prefix

        + temporary disabled disallowed prefix
        + added lookup function
        + added implementation docs for recoding function
        + changed $original property to $found; also changed its role
        + added backtracking feature
        + modified main function parameter; added backtrack step

        VERSION 0.4-debug - 12 Jan 2013
        + [0.4a] hotfix for recoding function; enabled multiple recoding sources, and 
          optimized algorithm

        + changed file name to "lemmatizer"
        + (-debug) tag added to version because of debugging prints (will be removed on release version)
        + added no0b interface for easier testing
        + updated TODO list
        + added debugging prints for tracking issues / problems.
        + implemented recoding function
        + optimized main function implementation
        + implemented rules ordering via SWITCH CASE
        + added checking procedure for duplicate derivational prefix
        + minor modification on derivational prefix removal; return false if prefix not found
        + correctly implemented disallowed affix pair checker
        + added error variable to flag errors

        VERSION 0.3 - 10 Jan 2013
        + updated TODO list
        + added rules for handling 'pe-' prefix variants
        + updated TODO list
        + slight source documentation update
        + variable $prefix to store the detected prefix; this was done to prevent variable overloading at rule 13 and 17
        + rules for handling 'be-', 'me-', and 'te-' prefix variants
        + constants to store repetitive regex patterns such as vowel, consonant, alphabet (VOWEL, CONSONANT, ALPHA respectively)
        + variable $type to easily control what kind of prefix we're handling
        + added variable for tracking complex prefix transformations (for later backtracking step)
        + added variable for tracking recoding path possibility (for recoding step)

        VERSION 0.2 - 05 Jan 2013
        + [0.2c] added plain derivational prefix removal
        + [0.2c] added comments that separate properties and methods
        + [0.2c] added a few basic logic to main public function 'eat'
        + [0.2c] removed disallowed affix pairs function, for reengineering
        + [0.2c] reconfigured functions that use pattern arrays
        + [0.2c] revamped $pattern and $pattern2 into an array

        + [0.2b] priorities for each todo 
        + [0.2b] todo anchor for incomplete derivational suffix procedure

        + rule precedence check (check_rule)
        + derivational suffix removal function (delete_derivational_suffix)
        + property that holds removed suffixes/prefixes ($removed)
        + wrapped things into a class: Lemmatizer
        + DEBUG FUNCTIONS:  eat, getRemoved
          (look for @debug tags!)

        VERSION 0.1 - 04 Jan 2013
        + inflectional suffix removal function (delete_inflectional_suffix)

    **/


    /**
        Le grandiose. The Class. This is what it's all about!
        @todo LOW - (Seriously, do some neat description here.)

    **/
    class Lemmatizer {

        /*
        ********************************************************************************
        ***     ATTRIBUTES
        ********************************************************************************
        */

        /*
            Regular expression for vowels

            @const 
            @var string
        */
        const VOWEL = "[aiueo]";

        /*
            Regular expression for consonants

            @const
            @var string
        */
        const CONSONANT = "[bcdfghjklmnpqrstvwxyz]";

        /*
            Regular expression for alphabet, including a stripe in between
            for pluralized or repetitive form

            @const
            @var string
        */
        const ALPHA = "[a-z]+-?[a-z]*";

        /*
            Holds the removed suffixes/prefixes for backtracking procedure

            @var array list of strings
        */
        protected $removed = array(
            'particle' => '',
            'possessive_pronoun' => '',
            'derivational_suffix' => '',
            'derivational_prefix' => ''
        );

        /*
            Serves as a container for successful dictionary lookup

            @var string
        */
        protected $found = null;

        /*
            Tracks all the changes made to the word; The array is indexed by
            the general prefix form, such as di,ke,se,be,me,pe,te.

            for example, the word 'menapak' undergoes transformation men-tapak.
            the variable's structure would be:
            ["me"] => (
                    ["men"] => "t"
                )

            @var array
        */
        public $complex_prefix_tracker = array();

        /*
            Saves recoding path for corresponding rules; the array is indexed by
            the general prefix form.

            same as prefix tracker variable; this variable is structured as:
            ["me"] => (
                    ["me"] => "n"
                )

            @var array
        */
        public $recoding_tracker = array();

        /*
            Serves as the error indicator if a termination condition occurs.
            The conditions are:
                > 'disallowed_pairs':   the identified prefix forms a disallowed
                                        affix combination with suffix that was 
                                        removed in previous steps.
                > 'lemma_not_found'     the lemmatizer fails to detect input word.

            @var string
        */
        public $error = null;

        /*
            Records how many lookups performed

            @var integer
        */
        public $total_lookup = 0;


        /*
            Saves connection string to MySQL.
            host: localhost
            user: root
            pass: <none>
            
            @var PDO Connection
        */
        protected $database;


        private $time;

        /*
        ********************************************************************************
        ***     METHODS
        ********************************************************************************
        */

        /*
            
            Opens new database connection on instance construction

        */
        public function __construct() {

            $this->database = new PDO("mysql:host=localhost;dbname=lemmatizer", "root", "");

            $this->time = microtime(true);

        }

        /*
            Checks the input word against the dictionary; returns the word if found,
            or returns false if not found

            @param string $word
            @return mixed
        */
        protected function lookup($word) {

            // If the input word's length is smaller then 3, don't bother.
            if(strlen($word)<3) return false;

            echo "<br /><u>Dictionary Lookup</u>...";

            /*
                Saves input word for further processing

                @var string
            */
            $check = $word;

            /*
                Saves query result from PDO Query

                @var string
            */
            $query_string;


            /*
                
                Checks for repeated form that represents pluralized form;
                for example 'buku-buku'

            */
            if(preg_match("/^([a-z]+)-([a-z]+)$/", $check, $match)) {

                if($match[1] == $match[2]) $check = $match[1];

            }


            /*
                    
                    Attempts to programatically split joined words, in order to produce lemmas with
                    more than one word. The split method only works when the first word contains 2 syllables.

            */
            if(strlen($word) <= 6) {

                // executes lemma from database
                $query_string = "'$check'";
            
            }
            else {

               // regex string for a valid Indonesian syllable
                $syllable = "([bcdfghjklmnpqrstvwxyz]|sy)?([aiueo])(?U)([bcdfghjklmnpqrstvwxyz]|ng)?";
                
                // regex string for identifying the two words.
                $reg = "/^(?<first>aneka|({$syllable}{$syllable}))(?<second>{$syllable}{$syllable}(?U)({$syllable})*)$/";
                
                if(preg_match($reg, $word, $match)) {

                    echo "<br />Split result: " . $match['first'] . " " . $match['second'];

                    // Performs query via PDO
                    $query_string = "'".$match['first']." ".$match['second']."' OR lemma LIKE '$check'";

                } else {

                    $query_string = "'$check'";

                }
            }
            
            $query = $this->database->query("SELECT * FROM dictionary WHERE lemma LIKE $query_string LIMIT 1");

            // updates total dictionary lookup counter
            $this->total_lookup++;

            if($row = $query->fetch()) {

                // updates class property
                $this->found = $row['lemma'];

                echo " <strong>Lookup Success!</strong>";

                // returns result to function caller
                return $this->found;

            } else {

                echo " failed.";

                return false;
            }

        }


        /*
            Checks input word for rule precedence; If the input word has a confix:
            be - lah, be - an, me - i, di - i, pe - i, te - i
            Then, derivational prefix removal will be performed first

            @param string $word
            @return boolean
        */
        protected function check_rule_precedence($word) {

            /*
                Loads normalized alphabet regex (including stripes) from class' [constant]; 
                for shorthand purposes.

                @var string 
            */
            $alpha = self::ALPHA;

            /*
                Regular expression for affix pairs:
                ber - lah
                ber - an
                me - i
                di - i
                pe - i
                ter - i

                @var array list of strings
            */
            $patterns = array(
                    0 => "/^be{$alpha}([^k]an|lah)$/",
                    1 => "/^(me|di|pe|te){$alpha}(i|an)$/",
                    2 => "/^(k|s)e{$alpha}(i|kan)$/"
                );

            /*

                Checks whether the input word matches the affix pairs above;
                returns true if pattern is found, and false if not found

            */
            foreach($patterns as $pattern) {

                if(preg_match($pattern, $word)) return true;

            }

            return false;

        }


        /*
            Checks whether the input word contains disallowed affix pairs/confixes;
            returns true if the word has disallowed pair

            @return boolean
        */
        protected function has_disallowed_pairs() {

            /*
                Loads normalized alphabet regex (including stripes) from class' [constant]; 
                for shorthand purposes.

                @var string 
            */
            $alpha = self::ALPHA;

            /*
                Regular expression for disallowed affix pairs:
                be - i
                ke - i and kan
                se - i and kan
                di - an
                te - an

                @var array list of strings
            */
            $patterns = array(
                0 => "/^bei$/",
                1 => "/^(k|s)e(i|kan)$/",
                2 => "/^(di|me|te)[^krwylp]an$/"
            );

            /*

                Checks whether the identified derivational prefix and suffix matches the 
                affix pairs above; returns true if pattern is found, and false if not found

            */
            if($this->removed["derivational_prefix"]!="" && $this->removed["derivational_suffix"]!="") {

                $prefix = reset($this->removed["derivational_prefix"]);

                foreach($patterns as $pattern) {

                    if(preg_match($pattern, $prefix . $this->removed["derivational_suffix"])) {
                        $this->error = "disallowed_prefix";
                        return true;
                    }
                }
            }
            
            // no disallowed pairs found, good to go    
            return false;

        }


        /*
            Attempts to remove inflectional suffixes: 
            (particles) -kah, -lah, -tah, -pun and (possessive pronoun) -ku, -mu, -nya 
            from input word; Returns original value if no inflectional suffix found

            @param string $word
            @return string
        */
        protected function delete_inflectional_suffix($word) {

            /*
                Holds the value after suffix removal process

                @var string
            */
            $result = $word;

            /*
                Regular expression for Particle suffixes: (-kah, -lah, -tah, -pun)
                and Possessive Pronoun suffixes (-ku, -mu, -nya)

                @var array
            */
            $patterns = array(
                    'particle' => "/([klt]ah|pun)$/",
                    'possessive_pronoun' => "/([km]u|nya)$/"
                );

            /*

                Checks whether the input word contains inflectional suffix, with
                additional handling for Particle endings; because inflectional suffix
                can be stacked, e.g. "mobilnyapun"

            */
            foreach($patterns as $key => $pattern) {

                if(preg_match($pattern, $result, $match)) {

                    $result = preg_replace($pattern, '', $result);

                    // Updates the removed value holder
                    $this->removed[$key] = $match[0];

                    // Perform database lookup
                    $check = $this->lookup($result);

                    // If a lemma is successfully found, return it.
                    if($check) return $check;

                } 

            }

            echo "<br />Inflectional suffix Removal output: <strong>$result</strong>";

            // returns the suffix removal result
            return $result;

        }


        /*
            Attempts to remove derivational suffixes -i, -kan, -an from input word;
            Returns original value if no derivational suffix found

            @param string $word
            @return string
        */
        protected function delete_derivational_suffix($word) {

            /*
                Holds the value after suffix removal process

                @var string
            */
            $result = $word;

            /*
                Regular expression for derivational suffixes: -i, -kan, an

                @var string
            */
            $derivational_suffix = "/(i|k?an)$/";

            /*

                Checks whether input word contains derivational suffix; before
                stripping the suffix, an additional check for disallowed affix pair
                is performed

            */
            if(preg_match($derivational_suffix, $result, $match)) {

                // Removes the derivational suffix from given word
                $result = preg_replace($derivational_suffix, '', $result);

                // Updates the removed value holder
                $this->removed['derivational_suffix'] = $match[0];

                // Perform database lookup
                $check = $this->lookup($result);

                // If a lemma is successfully found, return it.
                if($check) return $check;

            }

            echo "<br />Derivational Suffix Removal output: <strong>$result</strong>";
            return $result;

        }


        /*
            Attempts to remove derivational prefixes di-, ke-, se-, be-, pe-,
            me-, pe- from input word. Generally, derivational prefix is divided to
            2 different group: 
                plain (di-, ke-, se-) and 
                complex (be-,me-,pe-,te-)

            Complex prefixes need transformation rules for certain cases in order to
            correctly lemmatize the input word.

            @param string $word
            @return mixed
        */
        protected function delete_derivational_prefix($word) {

            /*
                Loads normalized vowel regex from class' [constant]; for shorthand purposes.

                @var string 
            */
            $vowel = self::VOWEL;

            /*
                Loads normalized consonant regex from class' [constant]; for shorthand purposes.

                @var string 
            */
            $consonant = self::CONSONANT;

            /*
                Loads normalized alphabet regex (including stripes) from class' [constant]; 
                for shorthand purposes.

                @var string 
            */
            $alpha = self::ALPHA;

            /*
                Holds the value after suffix removal process

                @var string
            */
            $result = $word;

            /*
                Records what type of prefix is removed; plain or complex,
                in boolean form with [TRUE for plain]

                @var boolean
            */
            $type;

            /*
                Records what the matching prefix is for later use

                @var string
            */
            $prefix;

            /*  
                Regular expressions for plain and complex derivational prefixes

                @var array list of strings
            */
            $patterns = array(
                    'plain' => "/^(di|(k|s)e)/",
                    'complex' => "/^(b|m|p|t)e/"
                );

            /*
                
                A check is performed; if the input word has less than four characters,
                then the prefix removal process will be skipped.

            */
            if(strlen($result)< 4) {
                return $result;
            }


            foreach($patterns as $key => $pattern) {

                if(preg_match($pattern, $result, $match)) {

                    // saves the detected prefix's type
                    $type = ($key=='plain') ? true : false;

                    // saves matching prefix for later usage
                    $prefix = $match[0];


                    /*
                        
                        Performs check whether identified prefix is identical with the
                        previously removed prefixes; the prefix removal process will be
                        terminated here if duplicate prefix detected.

                    */
                    if($this->removed["derivational_prefix"]!="" && in_array($prefix, $this->removed["derivational_prefix"])) {

                        return $result;

                    }


                    /*

                        Initializes recoding variable for found prefix; if the corresponding
                        rule does not have recoding path, then the value will be empty string

                    */
                    $this->recoding_tracker[$match[0]] = "";

                    /*

                        If the prefix belongs to the 'plain' group, then immediate removal is done;
                        However if the prefix belongs to complex group, transformation rules must apply

                    */
                    if($type) {

                        $array = $this->removed['derivational_prefix'];

                        $result = preg_replace($pattern, '', $result);

                        // save modification changes to prefix tracker
                        $this->complex_prefix_tracker[$prefix] = array($prefix => "");

                    } else {

                        /*
                            Temporary single-member array, used to hold complex prefix transformations
                            to be pushed to the tracker.

                            @var array
                        */
                        $modification = null;

                        /*************************************************************************
                        **  "be-" PREFIX RULES
                        **   total rule: 5   
                        *************************************************************************/

                        if($prefix == "be") {

                            /*
                                
                                If a prefix has been removed before, these rules check for
                                combination, if it is an allowed type of combination or not.

                            */
                            if($this->removed['derivational_prefix']!="") {

                                // Get the array value of first index
                                $array = reset($this->complex_prefix_tracker);

                                // Get the first index of modification value
                                $added = reset($array);

                                // pp: Previous Prefix; Get the key (removed part) of modification value
                                $pp = key($array);
                                
                                /*

                                    Allowed combinations:
                                    diber-,
                                    keber-,
                                    member-,
                                    pember
                
                                */
                                if($pp!='mem' && $pp!='pem' && $pp!= 'di' && $pp!='ke') return $result;

                            }

                            /*
                                RULE 1
                                input: berV...
                                output: ber - V... | be - rV...
                            */
                            if(preg_match("/^ber$vowel/", $result)) {

                                $result = preg_replace("/^ber/", "", $result);

                                // save prefix changes
                                $modification = array("ber" => "");

                                // save recoding path
                                $this->recoding_tracker[$prefix] = array("be" => "");

                            }

                            /*
                                RULE 2
                                input: berCAP... where C!='r' and P!='er'
                                output: ber-CAP...
                            */
                            else if(preg_match("/^ber[bcdfghjklmnpqstvwxyz][a-z](?!er)/", $result)) {

                                $result = preg_replace("/^ber/", "", $result);

                                // save prefix changes
                                $modification = array("ber" => "");
                            }

                            /*
                                RULE 3
                                input: berCAerV... where C!= 'r' 
                                output: ber-CAerV
                            */
                            else if(preg_match("/^ber[bcdfghjklmnpqstvwxyz][a-z]er$vowel/", $result)) {

                                $result = preg_replace("/^ber/", "", $result);

                                //save prefix changes
                                $modification = array("ber" => "");

                            }

                            /*
                                RULE 4
                                input: belajar
                                output: bel - ajar
                            */
                            else if(preg_match("/^belajar$/", $result)) {

                                $result = preg_replace("/^bel/", "", $result);

                                // save prefix changes
                                $modification = array("bel" => "");

                            }

                            /*
                                RULE 5
                                input: beC1erC2... where C1!= 'r' or 'l'
                                output: be-C1erC2
                            */
                            else if(preg_match("/^be[bcdfghjkmnpqstvwxyz]er$consonant/", $result)) {

                                $result = preg_replace("/^be/", "", $result);

                                // save prefix changes
                                $modification = array("be" => "");

                            }

                            /* 

                                In this case, the rule is unsuccessful, therefore the
                                original input word will be returned. The previously
                                initialized recoding chars will also be unset. 

                            */
                            else {

                                unset($this->recoding_tracker[$prefix]);

                                return $word;
                            }

                        }
                        

                        /*************************************************************************
                        **  "te-" PREFIX RULES
                        **  total rule: 5
                        *************************************************************************/
                        
                        else if($prefix == "te") {

                            /*
                                
                                If a prefix has been removed before, these rules check for
                                combination, if it is an allowed type of combination or not.

                            */
                            if($this->removed['derivational_prefix']!="") {

                                // Get the array value of first index
                                $array = reset($this->complex_prefix_tracker);

                                // Get the first index of modification value
                                $added = reset($array);

                                // pp: Previous Prefix; Get the key (removed part) of modification value
                                $pp = key($array);
                                
                                /*
                                
                                    Allowed combinations:
                                    ke-,
                                    men- (special for tawa),
                                    pen- (special for tawa)
                
                                */
                                if($pp!='ke' && (($pp=='men' || $pp=='pen') && !preg_match('/tawa/', $result))) return $result;

                            } else echo "EMPTY PREFIX BRO>>";

                            /*
                                RULE 6
                                input: terV...
                                output: ter-V... | te-rV...
                            */
                            if(preg_match("/^ter$vowel/", $result)) {

                                $result = preg_replace("/^ter/", "", $result);

                                // save prefix changes
                                $modification = array("ter" => "");

                                // save recoding path
                                $this->recoding_tracker[$prefix] = array("te" => "");

                            }

                            /*
                                RULE 7
                                input: terCerV...
                                output: ter-CerV... where C!='r'
                            */
                            else if(preg_match("/^ter[bcdfghjklmnpqstvwxyz]er$vowel/", $result)) {

                                $result = preg_replace("/^ter/", "", $result);

                                // save prefix changes
                                $modification = array("ter" => "");

                            }

                            /*
                                RULE 8
                                input: terCP...
                                output: ter-CP...
                            */
                            else if(preg_match("/^ter$consonant(?!er)/", $result)) {

                                $result = preg_replace("/^ter/", "", $result);

                                // save prefix changes
                                $modification = array("ter" => "");

                            }

                            /*
                                RULE 9
                                input: teC1erC2...
                                output: te-C1erC2... where C1!='r'
                            */
                            else if(preg_match("/^ter[bcdfghjklmnpqstvwxyz]er$consonant/", $result)) {

                                $result = preg_replace("/^te/", "", $result);

                                // save prefix changes
                                $modification = array("te" => "");

                            }

                            /*
                                RULE 10
                                input: terC1erC2...
                                output: ter-C1erC2... where C1!='r'
                            */
                            else if(preg_match("/^ter[bcdfghjklmnpqstvwxyz]er$consonant/", $result)) {

                                $result = preg_replace("/^ter/", "", $result);

                                // save prefix changes
                                $modification = array("ter", "");

                            }

                            /* 

                                In this case, the rule is unsuccessful, therefore the
                                original input word will be returned. The previously
                                initialized recoding chars will also be unset. 

                            */
                            else {

                                unset($this->recoding_tracker[$prefix]);
                                
                                return $word;
                            }

                        }


                        /*************************************************************************
                        **  "me-" PREFIX RULES
                        **  total rule: 10
                        *************************************************************************/

                        else if($prefix == "me") {

                            /*
                                
                                This prefix cannot be a second-level prefix. If there is
                                already a removed prefix, immediately return input word.

                            */
                            if($this->removed['derivational_prefix']!="") return $result;

                            /*
                                RULE 11
                                input: me{l|r|w|y}V...
                                output: me-{l|r|w|y}V...
                            */
                            if(preg_match("/^me[lrwy]$vowel/", $result)) {

                                $result = preg_replace("/^me/", "", $result);

                                // save prefix changes
                                $modification = array("me" => "");

                            }

                            /*
                                RULE 12
                                input: mem{b|f|v}...
                                output: mem-{b|f|v}...
                            */
                            else if(preg_match("/^mem[bfv]/", $result)) {

                                $result = preg_replace("/^mem/", "", $result);

                                // save prefix changes
                                $modification = array("mem" => "");

                            }

                            /*
                                RULE 13
                                input: mempe...
                                output: mem-pe..
                            */
                            else if(preg_match("/^mempe/", $result)) {

                                $result = preg_replace("/^mem/", "", $result);

                                // save prefix changes
                                $modification = array("mem" => "");

                            }

                            /*
                                RULE 14
                                input: mem{rV|V}...
                                output:me-m{rV|V}... | me-p{rV|V}...
                            */
                            else if(preg_match("/^mem(r?)$vowel/", $result, $match)) {

                                $result = preg_replace("/^me/", "", $result);

                                // save prefix changes
                                $modification = array("me$match[1]" => "");

                                // save recoding path
                                $this->recoding_tracker[$prefix] = array("mem" => "p");

                            }

                            /*
                                RULE 15
                                input: men{c|d|j|s|z}...
                                output:men-{c|dj|s|z}...
                            */
                            else if(preg_match("/^men[cdsjz]/", $result)) {

                                $result = preg_replace("/^men/", "", $result);

                                // save prefix changes
                                $modification = array("men" => "");

                            }

                            /*
                                RULE 16
                                input: menV...
                                output:me-tV... | me-nV...
                            */
                            else if(preg_match("/^men$vowel/", $result)) {

                                $result = preg_replace("/^men/", "t", $result);

                                // save prefix changes
                                $modification = array("men" => "t");

                                // save recoding path
                                $this->recoding_tracker[$prefix] = array("me" => "");

                            }

                            /*
                                RULE 17
                                input: meng{g|h|q|k}...
                                output: meng-{g|h|q|k}...
                            */
                            else if(preg_match("/^meng[ghqk]/", $result)) {

                                $result = preg_replace("/^meng/", "", $result);

                                // save prefix changes
                                $modification = array("meng" => "");

                            }

                            /*
                                RULE 18
                                input: mengV...
                                output: meng-V... | meng-kV... | mengV-... if V='e'
                            */
                            else if(preg_match("/^meng($vowel)/", $result, $match)) {

                                if($match[1] == 'e') {

                                    $result = preg_replace("/^menge/", "", $result);

                                    // save prefix changes
                                    $modification = array("menge" => "");

                                    $this->recoding_tracker[$prefix] = array("meng1" => "");
                                    $this->recoding_tracker[$prefix]["meng2"] = "k";

                                } else {

                                    $result = preg_replace("/^meng/", "", $result);

                                    // save prefix changes
                                    $modification = array("meng" => "");

                                    // save recoding path
                                    $this->recoding_tracker[$prefix] = array("meng" => "k");    
                                }
                                
                            }

                            /*
                                RULE 19
                                input: menyV...
                                output: meny-sV... | me-nyV...
                            */
                            else if(preg_match("/^meny$vowel/", $result)) {

                                $result = preg_replace("/^meny/", "s", $result);

                                // save prefix changes
                                $modification = array("meny" => "s");

                                // save recoding path
                                $this->recoding_tracker[$prefix] = array("me" => "");

                            }

                            /*
                                RULE 20
                                input: mempA...
                                output: mem-pA... where A!='e'
                            */
                            else if(preg_match("/^memp[abcdfghijklmnopqrstuvwxyz]/", $result)) {

                                $result = preg_replace("/^mem/", "", $result);

                                // save prefix changes
                                $modification = array("mem" => "");

                            }

                            /* 

                                In this case, the rule is unsuccessful, therefore the
                                original input word will be returned. The previously
                                initialized recoding chars will also be unset. 

                            */
                            else {

                                unset($this->recoding_tracker[$prefix]);
                                
                                return $word;
                            }

                        }


                        /*************************************************************************
                        **  "pe-" PREFIX RULES
                        **  total rule: 15
                        *************************************************************************/

                        else if($prefix == "pe") {

                            /*
                                
                                If a prefix has been removed before, these rules check for
                                combination, if it is an allowed type of combination or not.

                            */
                            if($this->removed['derivational_prefix']!="") {

                                // Get the array value of first index
                                $array = reset($this->complex_prefix_tracker);

                                // Get the first index of modification value
                                $added = reset($array);

                                // pp: Previous Prefix; Get the key (removed part) of modification value
                                $pp = key($array);
                                
                                /*

                                    Allowed combinations:
                                    di-,
                                    peN-,
                                    mem-.
                
                                */
                                if($pp!='di' && !preg_match('/^pe/', $pp) && $pp!= 'mem') return $result;

                            }

                            /*
                                RULE 21
                                input: pe{w|y}V...
                                output: pe-{w|y}V...
                            */
                            if(preg_match("/^pe[wy]$vowel/", $result)) {

                                $result = preg_replace("/^pe/", "", $result);

                                // save prefix changes
                                $modification = array("pe" => "");

                            }

                            /*
                                RULE 22
                                input: perV...
                                output: per-V... | pe-rV...
                            */
                            else if(preg_match("/^per$vowel/", $result)) {

                                $result = preg_replace("/^per/", "", $result);

                                // save prefix changes
                                $modification = array("per" => "");

                                // save recoding path
                                $this->recoding_tracker[$prefix] = array("pe" => "");

                            }

                            /*
                                RULE 23
                                input: perCAP...
                                output: per-CAP... where C!='r' and P!='er'
                            */
                            else if(preg_match("/^per[bcdfghjklmnpqstvwxyz](?!er)/", $result)) {

                                $result = preg_replace("/^per/", "", $result);

                                // save prefix changes
                                $modification = array("per" => "");

                            }

                            /*
                                RULE 24
                                input: perCAerV...
                                output: per-CAerV... where C!= 'r'
                            */
                            else if(preg_match("/^per[bcdfghjklmnpqstvwxyz][a-z]er$vowel/", $result)) {

                                $result = preg_replace("/^per/", "", $result);

                                // save prefix changes
                                $modification = array("per" => "");

                            }

                            /*
                                RULE 25
                                input: pem{b|f|v}...
                                output: pem-{b|f|v}...
                            */
                            else if(preg_match("/^pem[bfv]/", $result)) {

                                $result = preg_replace("/^pem/", "", $result);

                                // save prefix changes
                                $modification = array("pem" => "");

                            }

                            /*
                                RULE 26
                                input: pem{rV|V}...
                                output: pe-m{rV|V}... | pe-p{rV|V}...
                            */
                            else if(preg_match("/^pem(r?)$vowel/", $result)) {

                                $result = preg_replace("/^pe/", "", $result);

                                // save prefix changes
                                $modification = array("pe" => "");

                                // save recoding path
                                $this->recoding_tracker[$prefix] = array("pem" => "p");
                         
                            }

                            /*
                                RULE 27
                                input: pen{c|d|j|z}...
                                output: pen-{c|d|j|z}...
                            */
                            else if(preg_match("/^pen[cdjz]/", $result)) {

                                $result = preg_replace("/^pen/", "", $result);

                                // save prefix changes
                                $modification = array("pen" => "");

                            }

                            /*
                                RULE 28
                                input: penV...
                                output: pe-tV... | pe-nV... 
                            */
                            else if(preg_match("/^pen$vowel/", $result)) {

                                $result = preg_replace("/^pen/", "t", $result);

                                // save prefix changes
                                $modification = array("pen" => "t");

                                // save recoding path
                                $this->recoding_tracker[$prefix] = array("pe" => "");

                            }

                            /*
                                RULE 29
                                input: pengC...
                                output: peng-C...
                            */
                            else if(preg_match("/^peng$consonant/", $result)) {

                                $result = preg_replace("/^peng/", "", $result);

                                // save prefix changes
                                $modification = array("peng" => "");

                            }

                            /*
                                RULE 30
                                input: pengV...
                                output: peng-V | peng-kV... | pengV-... if V='e'
                            */
                            else if(preg_match("/^peng($vowel)/", $result, $match)) {

                                if($match[1] == 'e') {

                                    $result = preg_replace("/^penge/", "", $result);

                                    // save prefix changes
                                    $modification = array("penge" => "");

                                    $this->recoding_tracker[$prefix] = array("peng1" => "");
                                    $this->recoding_tracker[$prefix]["peng2"] = "k";

                                } else {

                                    $result = preg_replace("/^peng/", "", $result);

                                    // save prefix changes
                                    $modification = array("peng" => "");

                                    // save recoding path
                                    $this->recoding_tracker[$prefix] = array("peng" => "k");    
                                }

                            }

                            /*
                                RULE 31
                                input: penyV...
                                output: peny-sV... | pe-nyV...
                            */
                            else if(preg_match("/^peny$vowel/", $result)) {

                                $result = preg_replace("/^peny/", "s", $result);

                                // save prefix changes
                                $modification = array("peny" => "s");

                                // save recoding path
                                $this->recoding_tracker[$prefix] = array("pe" => "");

                            }

                            /*
                                RULE 32
                                input: pelV...
                                output: pe-lV... | pel-V if 'pelajar'
                            */
                            else if(preg_match("/^pel$vowel/", $result)) {

                                if($result == "pelajar") {

                                    $result = preg_replace("/^pel/", "", $result);

                                    // save prefix changes
                                    $modification = array("pel" => "");

                                } else {

                                    $result = preg_replace("/^pe/", "", $result);

                                    // save prefix changes
                                    $modification = array("pe" => "");

                                }

                            }

                            /*
                                RULE 33
                                input: peCerV...
                                output: per-CerV... where C!={r|w|y|l|m|n}
                            */
                            else if(preg_match("/^pe[bcdfghjkpqstvxz]er$vowel/", $result)) {

                                $result = preg_replace("/^pe/", "", $result);

                                // save prefix changes
                                $modification = array("pe" => "");

                            }

                            /*
                                RULE 34
                                input: peCP...
                                output: pe-CP... where C!={r|w|y|l|m|n} and P!='er'
                            */
                            else if(preg_match("/^pe[bcdfghjkpqstvxz](?!er)/", $result)) {

                                $result = preg_replace("/^pe/", "", $result);

                                // save prefix changes
                                $modification = array("pe" => "");

                            }

                            /*
                                RULE 35
                                input: peC1erC2...
                                output: pe-C1erC2... where C1!={r|w|y|l|m|n}
                            */
                            else if(preg_match("/^ter[bcdfghjkpqstvxz]er$consonant/", $result)) {

                                $result = preg_replace("/^pe/", "", $result);

                                // save prefix changes
                                $modification = array("pe", "");

                            }

                            /* 

                                In this case, the rule is unsuccessful, therefore the
                                original input word will be returned. The previously
                                initialized recoding chars will also be unset. 

                            */
                            else {

                                unset($this->recoding_tracker[$prefix]);
                                
                                return $word;
                            }


                        }

                        /*
                            
                            Moves the temporary saved modification to prefix tracker
                            attribute (provided it's not null); If there is no modification
                            detected, then the this process is terminated.

                        */
                        if($modification!=null) {

                            // saves modification changes to prefix tracker
                            $this->complex_prefix_tracker[$prefix] = $modification;

                        } else {

                            // If there is no changes made, return original word.
                            return $result;

                        }
                        
                    }

                    /*
                        
                        Updates the removed value holder. Since derivational prefix
                        is stackable (up to 2), the value is kept in an array fashion

                    */
                    if($this->removed['derivational_prefix']=='') {

                        $this->removed['derivational_prefix'] = array();

                    }

                    // Adds the detected prefix type to the removed affix tracker.
                    array_push($this->removed['derivational_prefix'], $prefix);


                    echo "<br />Derivational prefix removal output: $result";

                    // Performs dictionary lookup
                    $this->lookup($result);

                    // once the prefix is removed, we need to enter next iteration.
                    return $result;

                }

            }

            echo "<br />No derivational prefix removal found: $result";
            // if no prefix found, return original word instead
            return $result;

        }

        /*
            Performs recoding on input word 
            (provided there are recoding paths available)

            @param string $word
            @return mixed
        */
        protected function recode($word) {

            echo "<br />Entering recoding with <strong>$word</strong>...";
            /*
                Holds the value after suffix removal process

                @var string
            */
            $result = $word;      

            /*
                Holds the reversed version of prefix tracker; because it is used
                to return previously removed prefixes.
                
                @var array
            */
            $prefixes = array_reverse($this->complex_prefix_tracker);


            /*
                
                For each iteration, check whether the prefix has recoding path(s).
                If recoding path is found, then it will be applied

            */
            foreach($prefixes as $prefix => $changes) {

                /* 

                    Checks whether the current prefix has available recoding path,
                    stored in a variable

                    @var array
                */
                $recode = $this->recoding_tracker[$prefix];

                /* 
                    fetch the added value when removing this prefix

                    @var string
                */
                $prefix_added = reset($changes);

                /* 
                    fetch the removed value when removing this prefix

                    @var string
                */
                $prefix_removed = key($changes);


                /*

                    If something was added in the process of current prefix's removal,
                    then it will be removed; and replaced with the removed value.

                */
                if($prefix_added!="") {

                    // replace the added value with the removed value
                    $result = preg_replace("/^$prefix_added/", $prefix_removed, $result);

                }
                else {

                    // prepend the removed value to current word
                    $result = $prefix_removed . $result;
                }

                echo "<br />Detected prefix = removed: $prefix_removed, added: $prefix_added";            

                /*

                    If a recoding path is available, then it will be checked whether
                    there are more than one path. For every path, the word is configured
                    with the recoding path, and checked against the database.

                */ 
                if($recode!="") {

                    /*
                        Temporary variable for storing word changes; used for checking
                        and lookup

                        @var string
                    */
                    $temp;

                    foreach($recode as $raw_removed => $added) {

                        /*
                            There are some cases where the recoding path is more than
                            one, and both have identical removed value; because this
                            can cause duplicate array keys (which will lead to overwriting),
                            some rules are appended with numbers. Before the removed value
                            is stored, it removes any number appended in the value
    
                            @var string
                        */
                        $removed = preg_replace("/[0-9]+/", "", $raw_removed);

                        // Attempts to apply recoding path.
                        $temp = preg_replace("/^$removed/", ($added) ? $added : "", $result);

                        echo "<br />Performing recoding = removed: $removed, added: $added => result: $temp";

                        /*
                            
                            Performs dictionary lookup. If found, this will return the lookup result,
                            and updates class' property: $found

                        */
                        if($this->lookup($temp)) {
                            
                            // updates the prefix tracker value
                            $this->complex_prefix_tracker[$prefix] = array($removed => $added);

                            // returns the result
                            return $temp;
                        
                        }

                    }

                    // updates result variable for next iteration
                    $result = $temp;

                    

                } else {

                    echo "<br />No recoding path found for this prefix. Continue..";
                }

            }

            echo "<br />No recoding path found for this word...";

            // If recoding is unsuccessful or does not exist, return initial word
            return $word;

        }


        /**
        
            @todo   LOW - description will be available later! (once most of the things are up.)
                    name is still a jest, of course. we'll come up with something better!
        
            @todo   LOW - implementation documentation for BACKTRACKING procedure (case 7)

        */
        public function eat($word, $backtrack_step = false) {

            /*
                Serves as the container for prefix/suffix removal results
                
                @var string
            */
            $result = $word;

            /*
                Serves as the temporary variable; holds string if process works
                without error and holds FALSE if there is an detected error.

                @var mixed
            */
            $temp = $this->lookup($word);

            /*
                
                STEP 1: perform dictionary lookup on input word

            */
            if($temp) {

                return $temp;

            } else {

                /*
                    Checks the rule precedence; contains TRUE if derivational prefix
                    is performed first and false for otherwise

                    @var mixed
                */
                $steps = $this->check_rule_precedence($word);

                /*

                    STEP 2: function ordering based on rule precedence result
                    identifies whether this is a backtrack step or not;
                    if this is main step then perform rule precedence check

                */
                if($backtrack_step) {

                    $steps = array(5,6);

                } else {

                    if($steps) {

                    echo "<br />Rule Order: 5,6,3,4,7";
                    $steps = array(5,6,3,4,7);

                    } else {

                        echo "<br />Rule Order: 3,4,5,6,7";
                        $steps = array(3,4,5,6,7);

                    }

                }
                
                if(!$backtrack_step) {
                    echo "<br />Preparation took <strong>" . round((microtime(true) - $this->time),4) . " seconds.</strong>";
                    $this->time = microtime(true);
                }

                foreach($steps as $step) {

                    echo "<br /><br />Running Step $step...";

                    switch($step) {

                        // STEP 3: delete inflectional suffix
                        case 3:
                            $temp = $this->delete_inflectional_suffix($result);
                            break;

                        // STEP 4: delete derivational suffix
                        case 4:
                            $temp = $this->delete_derivational_suffix($result);
                            break;

                        // STEP 5: delete derivational prefix
                        case 5:
                            // records to variable to $temp for continued processing
                            echo "<br />Entered prefix removal with: $result";
                            $temp = $result;

                            // the iteration is done for maximum three times
                            for($i=0; $i<2; $i++) {
                                
                                echo "<br />entering loop {$i}";
                                /*
                                    Temporary variable; holds the value before the word
                                    undergoes derivation prefix removal. Used for comparison,
                                    whether 

                                    @var string
                                */
                                $previous = $temp;

                                // delete derivational prefix
                                $temp = $this->delete_derivational_prefix($temp);

                                /*

                                    Checks for disallowed affix combination,
                                    Checks if the lemma is already found,
                                    Checks if the no prefix was removed, or the amount of prefixes removed are already 2.

                                */
                                if(($i==0 && $this->has_disallowed_pairs())
                                    || $this->found
                                    || $temp == $previous
                                    || count($this->removed['derivational_prefix'])>2) 
                                {
                                    break;
                                }
                            }
                            break;

                        // STEP 6: perform recoding
                        case 6:
                            $temp = $this->recode($result);
                            break;

                        /**
                            
                            @todo implementation docs for backtracking

                        */
                        // STEP 7: perform suffix backtracking
                        case 7:

                            echo "<br />Entering backtrack procedure for: $temp<br />";

                            $prefixes = array_reverse($this->complex_prefix_tracker);

                            foreach($prefixes as $prefix => $changes) {

                                $prefix_added = reset($changes);
                                $prefix_removed = key($changes);

                                echo "appended $prefix_removed-...<br />";

                                if($prefix_added!="") {

                                    $temp = preg_replace("/^$prefix_added/", $prefix_removed, $temp);

                                }
                                else {

                                    $temp = $prefix_removed . $temp;
                                }
                            }

                            echo "exited prefix return";

                            $this->removed["derivational_prefix"] = "";
                            $this->complex_prefix_tracker = array();
                            $backtrack = $this->eat($temp, true);

                            if($this->found) break;

                            // return derivational suffix
                            if(!$this->found && $this->removed['derivational_suffix']!="") {

                                echo "<br /><strong>BACKTRACK: RESTORING derivational_suffix..</strong>";

                                if($this->removed['derivational_suffix'] == "kan") {

                                    $temp = $temp . "k";
                                    $this->removed["derivational_prefix"] = "";
                                    $this->complex_prefix_tracker = array();
                                    $backtrack = $this->eat($temp, true);

                                    if($this->found) break;

                                    $temp = $temp . "an";

                                }
                                else {

                                    $temp = $temp . $this->removed["derivational_suffix"];
                                    
                                }
                                
                                $this->removed["derivational_prefix"] = "";
                                $this->complex_prefix_tracker = array();
                                $backtrack = $this->eat($temp, true);

                            }

                            // return possessive pronoun
                            if(!$this->found && $this->removed["possessive_pronoun"]!="") {

                                echo "<br /><strong>BACKTRACK: RESTORING possessive_pronoun</strong>";

                                $temp = $temp . $this->removed["possessive_pronoun"];
                                $this->removed["derivational_prefix"] = "";
                                $this->complex_prefix_tracker = array();
                                $backtrack = $this->eat($temp, true);
                                
                                if($this->found) break;

                            }

                            // return particle
                            if(!$this->found && $this->removed["particle"]!="") {

                                echo "<br /><strong>BACKTRACK: RESTORING particle..</strong>";

                                $temp = $temp . $this->removed["particle"];
                                $this->removed["derivational_prefix"] = "";
                                $this->complex_prefix_tracker = array();
                                $backtrack = $this->eat($temp, true);
                                
                                if($this->found) break;

                            }

                            echo "<br /><strong>end of suffix backtrack</strong>";

                    }

                    // time tracking
                    if(!$backtrack_step) {
                        echo "<br />Step $step took <strong>" . round((microtime(true) - $this->time),4) . " seconds.</strong>";
                        $this->time = microtime(true);
                    }

                    /*

                         If the lookup already succeeded from previous result, 
                         then directly return the result

                    */
                    if($this->found) return $this->found;

                    // if the removal is success, proceed to next step
                    $result = $temp;

                }

                echo "<br /><br />Executing rule 8 (no lookup found)";
                /*
                    
                    STEP 8: if the dictionary lookup still fails, return original word.
                    since the word was returned to its original form, removal histories
                    are considered 'undone'; for better semantics

                */
                if(!$backtrack_step) if(!$this->error) $this->error = "lemma_not_found";
                return $word;

            }

        }


        /**

            @debug  basically this gives access to the world about what prefixes/suffixes 
                    have been removed. Will be removed later!

        **/
        public function getRemoved() {

            return $this->removed;

        }

        /*

            Closes database connection on instance destruction.
        
        */
        public function __destruct() {

            $this->database = null;

        }
    }
    // end of class Lemmatizer



    /**

        TESTING / DEBUG LINES

    **/

    $start = microtime(true);
    $subject = "pertanggungjawabannyalah";
    $lemmatizer = new Lemmatizer;

    if(isset($_GET['word']) && preg_match("/^[a-zA-Z]+-?[a-zA-Z]*$/", $_GET['word'])) $subject = strtolower($_GET['word']);
 
    echo "<form action='#' method='GET'><input type='text' name='word' /><input type='submit' value='Lemmatize' /></form><hr />";
    echo "Input: <strong>$subject</strong>";
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

    echo "Lemmatization took " . round((microtime(true) - $start),4) . " seconds.";
