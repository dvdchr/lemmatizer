<?php

    /**

        LEMMATIZER FOR INDONESIAN LANGUAGE

        @author         David Christiandy <david.christiandy@gmail.com>
                        Rolando <rolando_kai@hotmail.com>
                        Stephen Hadisurja <stephen.hadisurja@gmail.com>

        @version        0.3    [revision by David]

        @date           10 Jan 2013, 16:28

        @description    @todo LOW - explain what all this fuzz is about. bla
                        bla bla bla.. lorem ipsum dolor sit amet

        -------------------------------------------------------------------

        TODO:
        ----

        + rules ordering
        + recoding
        + backtracking
        + Lookup function
        + Disallowed Affix pairs for derivational affixes
        
        + check for more frequently used rules
        + lemma phrases handling


        CHANGELOG:
        ---------
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
            
            @const 
            @var string
        */
        const VOWEL = "[aiueo]";

        /*
            

            @const
            @var string
        */
        const CONSONANT = "[bcdfghjklmnpqrstvwxyz]";

        /*
            
            
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
        protected $original = null;

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
                    ["me"] => ""
                )

            @var array
        */
        public $recoding_tracker = array();


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
                    0 => "/^(be)$alpha+(an|lah)$/",
                    1 => "/^(me|di|pe|ter)$alpha+i$/"
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


        /**
            @todo MARKED FOR REWORK
            Ineffective Implementation; incorrectly handles suffix removal
            such as 'teriakan' that has -an and 'muliakan' that has -kan.
            Different approach will be used, by utilizing [$removed] properties.


            Checks whether the input word contains disallowed affix pairs/confixes;
            returns true if the word has disallowed pair

            @param string $word
            @return boolean
        */
        protected function has_disallowed_confix($word) {

            /*
                Loads normalized alphabet regex (including stripes) from class' [constant]; 
                for shorthand purposes.

                @var string 
            */
            $alpha = self::ALPHA;

            /*
                Regular expression for disallowed affix pairs:
                ber - i
                di - an
                ke - i and kan
                me - an
                ter - an
                per - an

                @var array list of strings
            */
            $patterns = array(
                0 => "/^ber$alpha+i$/",
                1 => "/^ke$alpha+(i|kan)$/",
                2 => "/^(di|me|per|ter)$alpha+an$/"
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

            /*
                Returns the suffix removal result
            */
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
                        $modification;

                        /*************************************************************************
                        **  "be-" PREFIX RULES
                        **   total rule: 5   
                        *************************************************************************/

                        if($prefix == "be") {

                            /*
                                RULE 1
                                input: berV...
                                output: ber - V... OR be - rV...
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
                                input: pem{b|f|V}...
                                output: pem-{b|f|V}...
                            */
                            else if(preg_match("/^pem[bfaiueo]/", $result)) {

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
                            else if(preg_match("", $result)) {

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
                                $modification = array("" => "");

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


                        // saves modification changes to prefix tracker
                        $this->complex_prefix_tracker[$prefix] = $modification;

                    }


                    /*

                        Updates the removed value holder. Since derivational prefix
                        is stackable (up to 2), the value is kept in an array fashion

                    */
                    if($this->removed['derivational_prefix']=='') {

                        $this->removed['derivational_prefix'] = array();

                    }

                    array_push($this->removed['derivational_prefix'], $match[0]);


                    // once the prefix is removed, we need to enter next iteration.
                    break;

                }

            }

        	return $result;

        }


        /**
        
            @todo   LOW - description will be available later! (once most of the things are up.)
                    name is still a jest, of course. we'll come up with something better!

            @debug  the implementation is still a debugging flow/procedure to test out things.

        */
        public function eat($word) {

            /*
            Serves as the 

            */
            $current_word = $word;

            if($temp = $this->lookup($word)) {

                return $temp;

            } else {

                /*
                    Checks the rule precedence; contains TRUE if derivational prefix
                    is performed first and false for otherwise

                    @var boolean
                */
                $order = $this->check_rule_precedence($word);

                /**

                    @todo HIGH - ordering of function executions.

                */

                // delete inflectional suffix
                $result = $this->delete_inflectional_suffix($word);

                // delete derivational suffix
                $result = $this->delete_derivational_suffix($result);

                // delete derivational prefix
                $result = $this->delete_derivational_prefix($result);

                return $result;
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
    $subject = "perasaannyapun";
    $lemmatizer = new Lemmatizer;

    $pat = "[a-z]+-?([a-z]*)";
    if(preg_match("/^mem(?!er)/", "memer", $match)) {
        echo var_dump($match) . "<br /><br />";
    }
    echo "Input: $subject<br />";
    echo 'Result: ' . $lemmatizer->eat($subject);
    $removed = $lemmatizer->getRemoved();
    foreach($removed as $key => $affix) {

        if($key == "derivational_prefix") {

            echo "<br />Removed $key : ";
            foreach($lemmatizer->complex_prefix_tracker as $array) {
                $value = reset($array);
                echo key($array);
                if($value) {
                    echo "  added: $value";
                }
            }

        } else if($affix!='') {

            echo "<br />Removed $key : " . $affix;

        }

    }

    echo "<br /><br />Recoding: ";
    echo var_dump($lemmatizer->recoding_tracker);