<?php

    /**

        LEMMATIZER FOR INDONESIAN LANGUAGE

        @author         David Christiandy <david.christiandy@gmail.com>
                        Rolando <rolando_kai@hotmail.com>
                        Stephen Hadisurja <stephen.hadisurja@gmail.com>

        @version        0.4a-debug   [revision by David]

        @date           12 Jan 2013, 02:39

        @description    @todo LOW - explain what all this fuzz is about. bla
                        bla bla bla.. lorem ipsum dolor sit amet

        -------------------------------------------------------------------

        TODO:
        ----

        + suffix backtracking
        + Lookup function
            > database lookup
            > hyphenated words
            > [improvement] check lemma that consists of more that 1 word


        CHANGELOG:
        ---------
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
            Serves as a container for the original input word

            @var string
        */
        protected $original;

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

        /**
            Serves as the error indicator if a termination condition occurs.
            The conditions are:
                > 'disallowed_confix':  the identified prefix forms a disallowed
                                        affix combination with suffix that was 
                                        removed in previous steps.
                > 'duplicate_prefix':   the identified prefix is identical to
                                        previously removd prefix.
            
            @todo LOW - what else?

            @var string
        */
        protected $error;

        /*
        ********************************************************************************
        ***     METHODS
        ********************************************************************************
        */

        /**
            Checks the input word against the dictionary; returns the word if found,
            or returns false if not found

            @debug 	the function is still in debug mode.

            @param string $word
            @return mixed
        */
        protected function lookup($word) {

            /**
                NOTE
                This is still a test mode. Later on, the conditional
                should contain a successful dictionary lookup.
            */
            if(false) {

                return $word;

            } else {

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
                be - lah
                be - an
                me - i
                di - i
                pe - i
                ter - i

                @var array list of strings
            */
            $patterns = array(
                    0 => "/^(be)[^r]{$alpha}([^k]an|lah)$/",
                    1 => "/^(me|di|pe|ter){$alpha}i$/"
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
        protected function has_disallowed_confix() {

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
                pe - an

                @var array list of strings
            */
            $patterns = array(
                0 => "/^bei$/",
                1 => "/^(k|s)e(i|kan)$/",
                2 => "/^(di|me|te)an$/"
            );

            /*

                Checks whether the identified derivational prefix and suffix matches the 
                affix pairs above; returns true if pattern is found, and false if not found

            */
            if($this->removed["derivational_prefix"]!="" && $this->removed["derivational_suffix"]!="") {
                foreach($this->removed["derivational_prefix"] as $prefix => $detail) {

                    foreach($patterns as $pattern) {

                        if(preg_match($pattern, $prefix . $this->removed["derivational_suffix"])) return true;

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
                Regular expression for Particle suffixes:
                (-kah, -lah, -tah, -pun)

                @var string
            */
            $particle = "/([klt]ah|pun)$/";

            /*
                Regular expression for Possessive Pronoun suffixes:
                (-ku, -mu, -nya)

                @var string
            */
            $possessive_pronoun = "/([km]u|nya)$/";

            /*

                Checks whether the input word contains inflectional suffix, with
                additional handling for Particle endings; because inflectional suffix
                can be stacked, e.g. "mobilnyapun"

            */
            if(preg_match($particle, $result, $match)) {

                $result = preg_replace($particle, '', $result);

                // Updates the removed value holder
                $this->removed['particle'] = $match[0];

                /**

                    @discussion	    dictionary lookup is not performed in this part,
                                    because we are following Arifin (2009)'s algorithm which 
                                    will initiate suffix backtracking [in case of overstemming]

                **/

            } 

            if(preg_match($possessive_pronoun, $result, $match)) {

                $result = preg_replace($possessive_pronoun, '', $result);

                // Updates the removed value holder
                $this->removed['possessive_pronoun'] = $match[0];

            }

            echo "<br />Inflectional suffix removal output: $result";
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

                $result = preg_replace($derivational_suffix, '', $result);

                // Updates the removed value holder
                $this->removed['derivational_suffix'] = $match[0];

            }


            /*
                
                Performs check whether the removed prefix and suffix forms 
                a disallowed pair

            */
            if($this->has_disallowed_confix()) {

                $error = "disallowed_confix";
                return false;
                
            }

            echo "<br />Derivational suffix removal output: $result";
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


            foreach($patterns as $key => $pattern) {

                if(preg_match($pattern, $result, $match)) {

                    // saves the detected prefix's type
                    $type = ($key=='plain') ? true : false;

                    // saves matching prefix for later usage
                    $prefix = $match[0];


                    /*
                        
                        Performs check whether identified prefix is identical with other
                        removed prefixes; returns false and updates the class error flag 
                        if found

                    */
                    if($this->removed["derivational_prefix"]!="" && in_array($prefix, $this->removed["derivational_prefix"])) {

                        $this->error = "duplicate_prefix";
                        return false;

                    }

                    if(strlen($result)< 4) {
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

                        $result = preg_replace($pattern, '', $result);

                        // save modification changes to prefix tracker
                        $this->complex_prefix_tracker[$prefix] = array($prefix => "");

                    } else {

                        /**

                            @todo     HIGH -  deletion process for complex derivational prefix:

                                            > Rules Table for complex removal (a LOT to do!)
                                            > Place [lookup] function
                                            > Structure removal history for recoding

                    	*/

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

                        }
                        

                        /*************************************************************************
                        **  "te-" PREFIX RULES
                        **  total rule: 4
                        *************************************************************************/
                        
                        else if($prefix == "te") {

                            /*
                                RULE 6
                                input: terV...
                                output: ter-V... | te-rV...
                            */
                            if(preg_match("/^ter$vowel/", $result)) {

                                $result = preg_replace("/^ter/", "", $result);

                                // save prefix changes
                                $this->complex_prefix_tracker[$prefix] = array("ter" => "");

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
                                $this->complex_prefix_tracker[$prefix] = array("ter" => "");

                            }

                            /*
                                RULE 8
                                input: terCP...
                                output: ter-CP...
                            */
                            else if(preg_match("/^ter$consonant(?!er)/", $result)) {

                                $result = preg_replace("/^ter/", "", $result);

                                // save prefix changes
                                $this->complex_prefix_tracker[$prefix] = array("ter" => "");

                            }

                            /*
                                RULE 9
                                input: teC1erC2...
                                output: te-C1erC2... where C1!='r'
                            */
                            else if(preg_match("/^ter[bcdfghjklmnpqstvwxyz]er$consonant/", $result)) {

                                $result = preg_replace("/^te/", "", $result);

                                // save prefix changes
                                $this->complex_prefix_tracker[$prefix] = array("te" => "");

                            }

                        }


                        /*************************************************************************
                        **  "me-" PREFIX RULES
                        **  total rule: 10
                        *************************************************************************/

                        else if($prefix == "me") {

                            /*
                                RULE 10
                                input: me{l|r|w|y}V...
                                output: me-{l|r|w|y}V...
                            */
                            if(preg_match("/^me[lrwy]$vowel/", $result)) {

                                $result = preg_replace("/^me/", "", $result);

                                // save prefix changes
                                $modification = array("me" => "");

                            }

                            /*
                                RULE 11
                                input: mem{b|f|v}...
                                output: mem-{b|f|v}...
                            */
                            else if(preg_match("/^mem[bfv]/", $result)) {

                                $result = preg_replace("/^mem/", "", $result);

                                // save prefix changes
                                $modification = array("mem" => "");

                            }

                            /*
                                RULE 12
                                input: mempe...
                                output: mem-pe..
                            */
                            else if(preg_match("/^mempe/", $result)) {

                                $result = preg_replace("/^mem/", "", $result);

                                // save prefix changes
                                $modification = array("mem" => "");

                            }

                            /*
                                RULE 13
                                input: mem{rV|V}...
                                output:me-m{rV|V}... | me-p{rV|V}...
                            */
                            else if(preg_match("/^mem(r?)$vowel/", $result, $match)) {

                                $result = preg_replace("/^me/", "", $result);

                                // save prefix changes
                                $modification = array("mem$match[1]" => "");

                                // save recoding path
                                $this->recoding_tracker[$prefix] = array("mem" => "p");

                            }

                            /*
                                RULE 14
                                input: men{c|d|j|s|z}...
                                output:men-{c|dj|s|z}...
                            */
                            else if(preg_match("/^men[cdsjz]/", $result)) {

                                $result = preg_replace("/^men/", "", $result);

                                // save prefix changes
                                $modification = array("men" => "");

                            }

                            /*
                                RULE 15
                                input: menV...
                                output:me-nV... | me-tV...
                            */
                            else if(preg_match("/^men$vowel/", $result)) {

                                $result = preg_replace("/^me/", "", $result);

                                // save prefix changes
                                $modification = array("me" => "");

                                // save recoding path
                                $this->recoding_tracker[$prefix] = array("men" => "t");

                            }

                            /*
                                RULE 16
                                input: meng{g|h|q|k}...
                                output: meng-{g|h|q|k}...
                            */
                            else if(preg_match("/^meng[ghqk]/", $result)) {

                                $result = preg_replace("/^meng/", "", $result);

                                // save prefix changes
                                $modification = array("meng" => "");

                            }

                            /*
                                RULE 17
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
                                RULE 18
                                input: menyV...
                                output: meny-sV...
                            */
                            else if(preg_match("/^meny$vowel/", $result)) {

                                $result = preg_replace("/^meny/", "s", $result);

                                // save prefix changes
                                $modification = array("meny" => "s");

                            }

                            /*
                                RULE 19
                                input: mempA...
                                output: mem-pA... where A!='e'
                            */
                            else if(preg_match("/^memp[abcdfghijklmnopqrstuvwxyz]/", $result)) {

                                $result = preg_replace("/^mem/", "", $result);

                                // save prefix changes
                                $modification = array("mem" => "");

                            }

                        }


                        /*************************************************************************
                        **  "pe-" PREFIX RULES
                        **  total rule: 16
                        *************************************************************************/

                        else if($prefix == "pe") {

                            /*
                                RULE 20
                                input: pe{w|y}V...
                                output: pe-{w|y}V...
                            */
                            if(preg_match("/^pe[wy]$vowel/", $result)) {

                                $result = preg_replace("/^pe/", "", $result);

                                // save prefix changes
                                $modification = array("pe" => "");

                            }

                            /*
                                RULE 21
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
                                RULE 22 
                                input: perCAP...
                                output: per-CAP... where C!='r' and P!='er'
                            */
                            else if(preg_match("/^per[bcdfghjklmnpqstvwxyz](?!er)/", $result)) {

                                $result = preg_replace("/^per/", "", $result);

                                // save prefix changes
                                $modification = array("per" => "");

                            }

                            /*
                                RULE 23
                                input: perCAerV...
                                output: per-CAerV... where C!= 'r'
                            */
                            else if(preg_match("/^per[bcdfghjklmnpqstvwxyz][a-z]er$vowel/", $result)) {

                                $result = preg_replace("/^per/", "", $result);

                                // save prefix changes
                                $modification = array("per" => "");

                            }

                            /*
                                RULE 24
                                input: pem{b|f|v}...
                                output: pem-{b|f|v}...
                            */
                            else if(preg_match("/^pem[bfv]/", $result)) {

                                $result = preg_replace("/^pem/", "", $result);

                                // save prefix changes
                                $modification = array("pem" => "");

                            }

                            /*
                                RULE 25
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
                                RULE 26
                                input: pen{c|d|j|z}...
                                output: pen-{c|d|j|z}...
                            */
                            else if(preg_match("/^pen[cdjz]/", $result)) {

                                $result = preg_replace("/^pen/", "", $result);

                                // save prefix changes
                                $modification = array("pen" => "");

                            }

                            /*
                                RULE 27
                                input: penV...
                                output: pe-nV... | pe-tV... 
                            */
                            else if(preg_match("/^pen$vowel/", $result)) {

                                $result = preg_replace("/^pe/", "", $result);

                                // save prefix changes
                                $modification = array("pe" => "");

                                // save recoding path
                                $this->recoding_tracker[$prefix] = array("pen" => "t");

                            }

                            /*
                                RULE 28
                                input: pengC...
                                output: peng-C...
                            */
                            else if(preg_match("/^peng$consonant/", $result)) {

                                $result = preg_replace("/^peng/", "", $result);

                                // save prefix changes
                                $modification = array("peng" => "");

                            }

                            /*
                                RULE 29
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
                                RULE 30
                                input: penyV...
                                output: peny-sV...
                            */
                            else if(preg_match("/^peny$vowel/", $result)) {

                                $result = preg_replace("/^peny/", "s", $result);

                                // save prefix changes
                                $modification = array("peny" => "s");

                            }

                            /*
                                RULE 31
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
                                RULE 32
                                input: peCerV...
                                output: per-CerV... where C!={r|w|y|l|m|n}
                            */
                            else if(preg_match("/^pe[bcdfghjkpqstvxz]er$vowel/", $result)) {

                                $result = preg_replace("/^pe/", "", $result);

                                // save prefix changes
                                $modification = array("pe" => "");

                            }

                            /*
                                RULE 33
                                input: peCP...
                                output: pe-CP... where C!={r|w|y|l|m|n} and P!='er'
                            */
                            else if(preg_match("/^pe[bcdfghjkpqstvxz](?!er)/", $result)) {

                                $result = preg_replace("/^pe/", "", $result);

                                // save prefix changes
                                $modification = array("pe" => "");

                            }

                            /*
                                RULE 34
                                input: terC1erC2...
                                output: ter-C1erC2... where C1!='r'
                            */
                            else if(preg_match("/^ter[bcdfghjklmnpqstvwxyz]er$consonant/", $result)) {

                                $result = preg_replace("/^ter/", "", $result);

                                // save prefix changes
                                $modification = array("ter", "");

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

                        }


                        if($modification!=null) {

                            // saves modification changes to prefix tracker
                            $this->complex_prefix_tracker[$prefix] = $modification;

                        } else {

                            break;
                            echo "modification is null!<br />";
                        
                        }
                        

                    }


                    /*

                        Updates the removed value holder. Since derivational prefix
                        is stackable (up to 2), the value is kept in an array fashion

                    */
                    if($this->removed['derivational_prefix']=='') {

                        $this->removed['derivational_prefix'] = array();

                    }

                    array_push($this->removed['derivational_prefix'], $prefix);


                    /*
                        
                        Performs check whether the removed prefix and suffix forms 
                        a disallowed pair

                    */
                    if($this->has_disallowed_confix()) {

                        $error = "disallowed_confix";
                        return false;

                    }

                    echo "<br />Derivational prefix removal output: $result";

                    // once the prefix is removed, we need to enter next iteration.
                    return $result;

                }

            }

            echo "<br />No derivational prefix removal found: $result";
            // if no prefix found, return original word instead
        	return $result;

        }

        /**
            Performs recoding on input word 
            (provided there are recoding paths available)
            
            @todo implementation documentation

            @param string $word
            @return mixed
        */
        protected function recode($word) {

            echo "<br />start of recoding..";
            /*
                Holds the value after suffix removal process

                @var string
            */
            $result = $word;      


            $prefixes = array_reverse($this->complex_prefix_tracker);

            foreach($prefixes as $prefix => $changes) {

                $recode = $this->recoding_tracker[$prefix];

                $prefix_added = reset($changes);
                $prefix_removed = key($changes);


                if($prefix_added!="") {

                    $result = preg_replace("/^$prefix_added/", $prefix_removed, $result);

                }
                else {

                    $result = $prefix_removed . $result;
                }

                echo "<br /><br />Detected prefix = removed: $prefix_removed, added: $prefix_added";            

                if($recode!="") {

                    $temp;

                    foreach($recode as $raw_removed => $added) {

                        $removed = preg_replace("/[0-9]+/", "", $raw_removed);
                        $temp = preg_replace("/^$removed/", ($added) ? $added : "", $result);
                        echo "<br />Performing recoding = removed: $removed, added: $added => result: $temp";

                        if($this->lookup($temp)) {
                        
                            $this->complex_prefix_tracker[$prefix] = array($removed => $added);
                            return $temp;
                        
                        }

                    }

                    $result = $temp;

                    

                } else {

                    echo "<br />No recoding path found for this prefix. Continue..";
                }

            }

            echo "<br />No recoding path found for this word...";

            return $word;

        }


        /**
        
            @todo   LOW - description will be available later! (once most of the things are up.)
                    name is still a jest, of course. we'll come up with something better!

            @debug  the implementation is still a debugging flow/procedure to test out things.

        */
        public function eat($word) {

            /*
                Serves as the container for prefix/suffix removal results
                
                @var string
            */
            $result = $this->original = $word;

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

                */
                if($steps) {

                    echo "Rule Order: 5,6,3,4,7";
                    $steps = array(5,6,3,4,7);

                } else {

                    echo "Rule Order: 3,4,5,6,7";
                    $steps = array(3,4,5,6,7);

                }

                foreach($steps as $step) {

                    echo "<br /><br />Executing rule $step...";

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
                            $temp = $result;

                            // the iteration is done for maximum three times
                            for($i=0; $i<3; $i++) {
                                
                                /*
                                    Temporary variable; holds the value before the word
                                    undergoes derivation prefix removal. Used for comparison,
                                    whether 

                                    @var string
                                */
                                $previous = $temp;

                                // delete derivational prefix
                                $temp = $this->delete_derivational_prefix($temp);

                                // if there are any errors, or no prefix was removed, then quit loop
                                if($temp==false || $this->error) break 3;

                                else if($temp == $previous) break;

                                // performs lookup for every iteration
                                else if($check = $this->lookup($temp)) return $check;

                            }

                            echo "<br />End of derivational prefix loop";

                            break;

                        // STEP 6: perform recoding
                        case 6:
                            $temp = $this->recode($result);
                            break;

                        // STEP 7: perform suffix backtracking
                        case 7:
                            // @todo  backtracking

                    }

                    if($step!=5) {

                       if($temp) echo "<br />Perform checking procedure... temp: $temp";
                        else echo "<br />temp = false! error: $this->error";

                        // if the suffix removal returned error, then exit immediately.
                        if($temp==false || $this->error) return false;

                        // performs database lookup; returns the word if found
                        else if($check = $this->lookup($temp)) return $check;  
                    }
                    

                    // if the removal is success, proceed to next step
                    $result = $temp;

                }

                echo "<br /><br />Executing rule 8 (no lookup found)<br />";
                /*
                    
                    STEP 8: if the dictionary lookup still fails, return original word.
                    since the word was returned to its original form, removal histories
                    are considered 'undone'; for better semantics

                */
                $this->recoding_tracker = array();
                $this->complex_prefix_tracker = array();
                $this->removed = array();
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
    }
    // end of class Lemmatizer



    /**

        TESTING / DEBUG LINES

    **/
    $subject = "percobaannyalah";
    $lemmatizer = new Lemmatizer;

    if(isset($_GET['word']) && preg_match("/^[a-z]+-?[a-z]*$/", $_GET['word'])) $subject = $_GET['word'];
 
    echo "<form action='#' method='GET'><input type='text' name='word' /><input type='submit' value='Lemmatize' /></form><hr />";
    echo "Input: <strong>$subject</strong><br />";
    $result = $lemmatizer->eat($subject);
    echo '<br /><br />Result: ' . $result ? $result : "false";
    $removed = $lemmatizer->getRemoved();
    foreach($removed as $key => $affix) {

        if($key == "derivational_prefix") {

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