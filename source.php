<?php

    /**

        LEMMATIZER FOR INDONESIAN LANGUAGE

        @author         David Christiandy <david.christiandy@gmail.com>
                        Rolando <rolando_kai@hotmail.com>
                        Stephen Hadisurja <stephen.hadisurja@gmail.com>

        @version        0.3-unfinished    [revision by David]

        @date           09 Jan 2013, 16:25

        @description    @todo LOW - explain what all this fuzz is about. bla
                        bla bla bla.. lorem ipsum dolor sit amet

        -------------------------------------------------------------------

        TODO:
        ----

        + Lookup function
        + Disallowed Affix pairs for derivational affixes
        + Derivational Prefix removal (version 0.3, ETA: 07 Jan 2013)
        + ...(TBA)


        CHANGELOG:
        ---------
        VERSION 0.3 - unfinished
        + added variable for tracking complex prefix transformations (for later backtracking step)
        + added variable for tracking recoding path possibility (for recoding step)
        + ONGOING - complex derivational prefix removal

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


        const VOWEL = "[aiueo]";
        const CONSONANT = "[bcdfghjklmnpqrstvwxyz]";
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

        protected $complex_prefix_tracker = array();

        protected $recoding_tracker = array();


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
            @return string OR boolean(false)
        */
        protected function lookup($word) {

            /**
                NOTE
                This is still a test mode. Later on, the conditional
                should contain a successful dictionary lookup
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
                    0 => "/^(be)[a-z]+-?[a-z]*(an|lah)$/",
                    1 => "/^(me|di|pe|ter)\w+i$/"
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
                0 => "/^ber\w+i$/",
                1 => "/^ke\w+(i|kan)$/",
                2 => "/^(di|me|per|ter)\w+an$/"
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


        /**
            @todo   LOW - description about what this function does.

            @param string $word
            @return string or boolean(false)
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

            $patterns = array(
                    'plain' => "/^(di|(k|s)e)/",
                    'complex' => "/^(b|m|p|t)e/"
                );

            foreach($patterns as $key => $pattern) {

                if(preg_match($pattern, $result, $match)) {

                    // saves the detected prefix's type
                    $type = ($key=='plain') ? true : false;

                    /*



                    */
                    if($type) {

                        $result = preg_replace($pattern, '', $result);

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

                        /*
                            Initializes recoding tracker with empty string. If a corresponding
                            rule does not have a recoding path, then it will be kept as empty string
                        */
                        array_push($this->recoding_tracker, array($match[0] => ""));

                        /*************************************************************************
                        **   "be-" PREFIX RULES
                        *************************************************************************/

                        if($match[0] == "be") {

                            /*
                                RULE 1
                                input: berV...
                                output: ber - V... OR be - rV...
                            */
                            if(preg_match("/^ber$vowel/", $result)) {

                                $result = preg_replace("/^ber/", "", $result);

                                // save prefix changes
                                $modification = array("ber" => "");

                                // saves recoding path
                                $this->recoding_tracker[$match[0]] = "/^be/";

                            }

                            /*
                                RULE 2
                                input:
                                output:
                            */
                            else if(preg_match(""))


                        }
                        

                        /*************************************************************************
                        **   "me-" PREFIX RULES
                        *************************************************************************/

                        else if($match[0] == "me") {

                            // TODO

                        }


                        /*************************************************************************
                        **   "pe-" PREFIX RULES
                        *************************************************************************/

                        else if($match[0] == "pe") {

                            // TODO

                        }


                        /*************************************************************************
                        **   "te-" PREFIX RULES
                        *************************************************************************/
                        
                        else if($match[0] == "te") {

                            // TODO

                        }


                        // saves modification changes to prefix tracker
                        array_push($this->complex_prefix_tracker, $modification);

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

                    @todo MEDIUM - ordering of function executions.

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
    $subject = "diberikannya";
    $lemmatizer = new Lemmatizer;

    $pat = "gi";
    if(preg_match("/^ber[aiueo]$pat/", "beragi", $match)) {
        echo "match: $match[0] <br />";
    }
    echo "Input: $subject<br />";
    echo 'Result: ' . $lemmatizer->eat($subject);
    $removed = $lemmatizer->getRemoved();
    foreach($removed as $key => $affix) {

        if($key == "derivational_prefix") {

            echo "<br />Removed $key : ";
            foreach($affix as $prefix) {
                echo $prefix;
            }

        } else if($affix!='') {

            echo "<br />Removed $key : " . $affix;

        }

    }