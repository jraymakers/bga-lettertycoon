<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * LetterTycoon implementation : © Jeff Raymakers <jephly@gmail.com>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

require_once(APP_GAMEMODULE_PATH.'module/table/table.game.php');

class LetterTycoon extends Table
{
    function __construct()
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels(
            array(
                // globals
                'last_round' => 10, // 0 is no, 1 is yes
                'retries_left' => 11,

                // game options
                'challenge_mode' => 100,
                'automatic_challenge_retries' => 101,
            )
        );

        $this->cards = self::getNew('module.common.deck');
        $this->cards->init('card');
        $this->cards->autoreshuffle = true;
    }
    
    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "lettertycoon";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = array())
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','"
                .addslashes($player['player_name'])."','"
                .addslashes($player['player_avatar'])."')";
        }
        $sql .= implode(',', $values);
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values

        self::setGameStateInitialValue('last_round', 0);
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)

        self::initStat('table', 'turns_number', 0);

        self::initStat('table', 'cards_drawn_to_community', 0);

        self::initStat('player', 'turns_number', 0);

        self::initStat('player', 'cards_played_from_hand', 0);
        self::initStat('player', 'cards_played_from_community', 0);
        self::initStat('player', 'cards_discarded_from_hand', 0);
        self::initStat('player', 'cards_discarded_from_community', 0);
        self::initStat('player', 'cards_drawn_to_hand', 0);

        self::initStat('player', 'money_received_from_words', 0);
        self::initStat('player', 'money_received_from_royalties', 0);
        self::initStat('player', 'money_received_from_challenges', 0);
        self::initStat('player', 'money_paid_for_patents', 0);
        self::initStat('player', 'money_paid_for_challenges', 0);

        self::initStat('player', 'stock_received', 0);

        self::initStat('player', 'q_doubling_used', 0);
        self::initStat('player', 'y_played_as_vowel', 0);
        self::initStat('player', 'y_played_as_consonant', 0);
        self::initStat('player', 'b_patent_ability_used', 0);
        self::initStat('player', 'j_patent_ability_used', 0);
        self::initStat('player', 'k_patent_ability_used', 0);
        self::initStat('player', 'q_patent_ability_used', 0);
        self::initStat('player', 'v_patent_ability_used', 0);
        self::initStat('player', 'x_patent_ability_used', 0);
        self::initStat('player', 'z_patent_ability_used', 0);

        // initialize card table
        $cards = array();
        foreach ($this->letter_counts as $letter => $count) {
            $cards[] = array('type' => $letter, 'type_arg' => 0, 'nbr' => $count);
        }
        $this->cards->createCards($cards, 'deck');
        $this->cards->shuffle('deck');

        // initialize patent table
        $sql = 'INSERT INTO patent (patent_id, owning_player_id) VALUES ';
        $values = array();
        foreach ($this->patent_costs as $letter => $cost) {
            $values[] = "('$letter', NULL)";
        }
        $sql .= implode(',', $values);
        self::DbQuery($sql);

        // deal 7 cards to each player
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $this->cards->pickCards(7, 'deck', $player_id);
            self::incStat(7, 'cards_drawn_to_hand', $player_id);
        }

        // deal 3 cards to the community pool
        $this->cards->pickCardsForLocation(3, 'deck', 'community');
        self::incStat(3, 'cards_drawn_to_community');

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = 'SELECT player_id as `id`, player_no as `order`,
                player_score as `score`, player_score_aux as `patents_value`,
                `money`, `stock` FROM player ';
        $result['players'] = self::getCollectionFromDb($sql);
  
        $result['community'] = $this->cards->getCardsInLocation('community');

        $result['hand'] = $this->cards->getCardsInLocation('hand', $current_player_id);

        $result['patent_owners'] = self::getPatentOwners();

        $result['main_word'] = self::getWordObjects(1);

        $result['second_word'] = self::getWordObjects(2);

        $result['goal'] = $this->goals[self::getPlayersNumber()];
        $result['scores'] = $this->scores;
        $result['letter_counts'] = $this->letter_counts;
        $result['letter_types'] = $this->letter_types;
        $result['patent_costs'] = $this->patent_costs;
        $result['patent_text'] = $this->patent_text;
        
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        $goal = $this->goals[self::getPlayersNumber()];
        $goal_value = intval($goal['value']);

        $max_player_patents_value = intval(self::getMaxPlayerPatentsValue());

        $fraction_towards_goal = min($max_player_patents_value / $goal_value, 1.0);

        if (self::getGameStateValue('last_round') == 1) {
            // If it's the last round, return 100%.
            return 100;
        } else {
            // If it's not the last round, max out at 95%.
            return round(95 * $fraction_towards_goal);
        }
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    function getPlayerName($player_id)
    {
        $players = self::loadPlayersBasicInfos();
        return $players[$player_id]['player_name'];
    }

    function getOtherPlayerIds($player_id)
    {
        $sql = "SELECT player_id FROM player WHERE player_id != '$player_id' ";
        return self::getObjectListFromDB($sql, true);
    }

    function getPlayersChallenge()
    {
        $sql = "SELECT player_id, challenge FROM player ";
        return self::getCollectionFromDB($sql, true);
    }

    function getPlayerMoney($player_id)
    {
        $sql = "SELECT `money` FROM player WHERE player_id = '$player_id' ";
        return self::getUniqueValueFromDB($sql);
    }

    function getPlayerPatentsValue($player_id)
    {
        $sql = "SELECT player_score_aux FROM player WHERE player_id = '$player_id' ";
        return self::getUniqueValueFromDB($sql);
    }

    function getMaxPlayerPatentsValue()
    {
        $sql = "SELECT MAX(player_score_aux) FROM player ";
        return self::getUniqueValueFromDB($sql);
    }

    function getPlayerPatentsCount($player_id)
    {
        $sql = "SELECT COUNT(patent_id) FROM patent WHERE owning_player_id = '$player_id' ";
        return self::getUniqueValueFromDB($sql);
    }

    function getPatentOwners()
    {
        $sql = "SELECT patent_id, owning_player_id FROM patent ";
        return self::getCollectionFromDB($sql, true);
    }

    function isPatentOwned($patent_id)
    {
        $sql = "SELECT EXISTS(SELECT 1 FROM patent
                WHERE patent_id = '$patent_id' AND owning_player_id IS NOT NULL
                LIMIT 1) ";
        return self::getUniqueValueFromDB($sql);
    }

    function setPatentOwner($patent_id, $player_id)
    {
        $sql = "UPDATE patent SET owning_player_id = $player_id WHERE patent_id = '$patent_id' ";
        self::DbQuery($sql);
    }

    function updatePlayerCounters($player_id, $money_change, $stock_change, $patents_value_change)
    {
        $sql = "UPDATE player SET
                    `money` = `money` + $money_change,
                    `stock` = `stock` + $stock_change,
                    player_score_aux = player_score_aux + $patents_value_change,
                    player_score = player_score + $money_change + $stock_change + $patents_value_change
                WHERE player_id = $player_id ";
        self::DbQuery($sql);
    }

    function setPlayerChallenge($player_id, $challenge /* 0 or 1 */)
    {
        $sql = "UPDATE player SET challenge = $challenge WHERE player_id = $player_id ";
        self::DbQuery($sql);
    }

    function clearPlayersChallenge()
    {
        $sql = "UPDATE player SET challenge = 0 ";
        self::DbQuery($sql);
    }

    function saveWord($word_num /* 1 or 2 */, $word_args)
    {
        $letters = $word_args['letters'];
        $letter_origins = $word_args['letter_origins'];
        $letter_types = $word_args['letter_types'];
        $card_ids = $word_args['card_ids'];

        $length = strlen($letters);

        $sql = 'INSERT INTO word (word_num, word_pos, letter, letter_origin, letter_type, card_id) VALUES ';
        $values = array();
        for ($word_pos = 0; $word_pos < $length; $word_pos++) {
            $letter = $letters[$word_pos];
            $letter_origin = $letter_origins[$word_pos];
            $letter_type = $letter_types[$word_pos];
            $card_id = $card_ids[$word_pos];
            $values[] = "($word_num, $word_pos, '$letter', '$letter_origin', '$letter_type', $card_id)";
        }
        $sql .= implode(',', $values);
        self::DbQuery($sql);
    }

    function clearWord()
    {
        $sql = 'DELETE FROM word ';
        self::DbQuery($sql);
    }

    // $word_num = 1 for main word, 2 for second word
    function getWordObjects($word_num)
    {
        $sql = "SELECT letter, letter_origin, letter_type, card_id
                FROM word WHERE word_num = $word_num ORDER BY word_pos ";
        return self::getObjectListFromDb($sql);
    }

    function wordContainsCardWithLetter($letter)
    {
        $sql = "SELECT EXISTS(SELECT 1 FROM word
                WHERE letter = '$letter' AND card_id IS NOT NULL
                LIMIT 1) ";
        return self::getUniqueValueFromDB($sql);
    }

    function checkCard($card_id, $expected_card_type, $expected_card_location, $expected_card_location_arg, $context)
    {
        $card = $this->cards->getCard($card_id);
        if (!isset($card)) {
            throw new BgaVisibleSystemException("card $card_id not found ($context)");
        }
        if ($card['type'] !== $expected_card_type) {
            throw new BgaVisibleSystemException("card $card_id has type '${$card['type']}' but expected '$expected_card_type' ($context)");
        }
        if ($card['location'] !== $expected_card_location) {
            throw new BgaVisibleSystemException("card $card_id has location '${$card['location']} 'but expected '$expected_card_location' ($context)");
        }
        if ($card['location_arg'] != $expected_card_location_arg) {
            throw new BgaVisibleSystemException("card $card_id has location_arg '${$card['location_arg']}' but expected '$expected_card_location_arg' ($context)");
        }
    }

    function checkWord($word_args, $active_player_id)
    {
        $letters = $word_args['letters'];
        $letter_origins = $word_args['letter_origins'];
        $letter_types = $word_args['letter_types'];
        $card_ids = $word_args['card_ids'];

        $length = strlen($letters);

        if ($length < 3) {
            throw new BgaVisibleSystemException("word length is less than 3 ('$letters')");
        }

        if (strlen($letter_origins) !== $length) {
            throw new BgaVisibleSystemException("letter_origins length invalid: '$letter_origins' ('$letters')");
        }

        if (strlen($letter_types) !== $length) {
            throw new BgaVisibleSystemException("letter_types length invalid: '$letter_types' ('$letters')");
        }

        if (count($card_ids) !== $length) {
            throw new BgaVisibleSystemException("card_ids length invalid: {${count($card_ids)}} ($length)");
        }

        $count_h = 0;
        $count_c = 0;
        $count_d = 0;
        $count_s = 0;
        $duplicated_letter = NULL;

        for ($i = 0; $i < $length; $i++) {
            $letter = $letters[$i];
            $letter_origin = $letter_origins[$i];
            $letter_type = $letter_types[$i];
            $card_id = $card_ids[$i];

            // check letter
            if (ord($letter) < ord('A') || ord('Z') < ord($letter)) {
                throw new BgaVisibleSystemException("letter $i ('$letter') not between 'A' and 'Z' ('$letters')");
            }

            // checl letter origin and card_id
            if ($letter_origin === 'h') {
                self::checkCard($card_id, $letter, 'hand', $active_player_id, "'$letters', '$letter_origins', $i");
                $count_h++;
            } else if ($letter_origin === 'c') {
                self::checkCard($card_id, $letter, 'community', 0, "'$letters', '$letter_origins', $i");
                $count_c++;
            } else if ($letter_origin === 'd') {
                if ($card_id !== '208') {
                    throw new BgaVisibleSystemException("letter_origin $i is 'd' but card_id ('$card_id') is not '208' ('$letters', '$letter_origins')");
                }
                $count_d++;
                $duplicated_letter = $letter;
            } else if ($letter_origin === 's') {
                if ($card_id !== '205') {
                    throw new BgaVisibleSystemException("letter_origin $i is 's' but card_id ('$card_id') is not '205' ('$letters', '$letter_origins')");
                }
                if ($i !== $length - 1) {
                    throw new BgaVisibleSystemException("letter_origin $i is 's' but not at end of word ('$letters', '$letter_origins')");
                }
                $count_s++;
            } else {
                throw new BgaVisibleSystemException("letter_origin $i ('$letter_origin') is invalid ('$letters', '$letter_origins')");
            }

            // check letter type
            $expected_letter_type = $this->letter_types[$letter];
            if ($letter_type === '_') {
                if ($expected_letter_type === 'consonant_or_vowel') {
                    throw new BgaVisibleSystemException("letter_type $i is '_' but expected 'c' or 'v' ('$letters', '$letter_types')");
                }
            } else if ($letter_type === 'v') {
                if ($expected_letter_type === 'consonant') {
                    throw new BgaVisibleSystemException("letter_type $i is 'v' but '$letter' is a consonant ('$letters', '$letter_types')");
                }
            } else if ($letter_type === 'c') {
                if ($expected_letter_type === 'vowel') {
                    throw new BgaVisibleSystemException("letter_type $i is 'c' but '$letter' is a vowel ('$letters', '$letter_types')");
                }
            } else {
                throw new BgaVisibleSystemException("letter_type $i ('$letter_type') is invalid ('$letters', '$letter_types')");
            }
        }

        if ($count_h < 1) {
            throw new BgaUserException(self::_('At least one letter in each word must come from a card in your hand.'));
        }

        return array(
            'card_count' => $count_h + $count_c,
            'duplicate_count' => $count_d,
            'appended_s_count' => $count_s,
            'duplicated_letter' => $duplicated_letter
        );
    }

    function stringFromWordObjects($word_objects)
    {
        $letters = '';
        foreach ($word_objects as $word_object) {
            $letters .= $word_object['letter'];
        }
        return $letters;
    }

    function loadWordList($word_length)
    {
        if (3 <= $word_length && $word_length <= 12) {
            $wordlist_filename = "$word_length-letter-words.txt";
            $words = file(__DIR__ . "/modules/$wordlist_filename", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            return $words;
        } else {
            self::warn("no wordlist for length: ($word_length) ");
            return array();
        }
    }

    function isWordInList($word, $list)
    {
        $low = 0;
        $high = count($list) - 1;
        while ($low <= $high) {
            $mid = floor(($low + $high) / 2);
            $comp = strcmp($list[$mid], $word);
            if ($comp < 0) $low = $mid + 1;
            elseif ($comp > 0) $high = $mid - 1;
            else return TRUE;
        }
        return FALSE;
    }

    function isWordInDictionary($word_string)
    {
        // load appropriate word list
        $wordlist = self::loadWordList(strlen($word_string));

        // is the word in the word list?
        return self::isWordInList(strtolower($word_string), $wordlist);
    }

    function getRejectedWordIfAny($main_word_objects, $second_word_objects)
    {
        $main_word_string = self::stringFromWordObjects($main_word_objects);
        if (!self::isWordInDictionary($main_word_string)) {
            return $main_word_string;
        }

        if (count($second_word_objects) > 0) {
            $second_word_string = self::stringFromWordObjects($second_word_objects);
            if (!self::isWordInDictionary($second_word_string)) {
                return $second_word_string;
            }
        }
        
        return NULL;
    }

    function getChallengerId()
    {
        $active_player_id = self::getActivePlayerId();
        $challenge_by_player = self::getPlayersChallenge();

        $player_id = self::getPlayerAfter($active_player_id);
        while ($player_id != $active_player_id) {
            if ($challenge_by_player[$player_id] == 1) {
                return $player_id;
            }
            $player_id = self::getPlayerAfter($player_id);
        }

        return NULL;
    }

    function refillCommunityPool()
    {
        $num_community_cards = $this->cards->countCardsInLocation('community');

        if ($num_community_cards < 3) {
            $num_cards_to_draw = 3 - $num_community_cards;
            $new_community_cards = $this->cards->pickCardsForLocation($num_cards_to_draw, 'deck', 'community');
            self::incStat($num_cards_to_draw, 'cards_drawn_to_community');

            self::notifyAllPlayers('communityReceivedCards', '', array(
                'new_cards' => $new_community_cards
            ));
        }
    }

    function refillHand()
    {
        $active_player_id = self::getActivePlayerId();
        $num_cards = $this->cards->countCardsInLocation('hand', $active_player_id);

        if ($num_cards < 7) {
            $num_cards_to_draw = 7 - $num_cards;
            $new_cards = $this->cards->pickCards($num_cards_to_draw, 'deck', $active_player_id);
            self::incStat($num_cards_to_draw, 'cards_drawn_to_hand', $active_player_id);

            self::notifyPlayer($active_player_id, 'activePlayerReceivedCards', '', array(
                'new_cards' => $new_cards
            ));
        }
    }

    function returnCardsForWord($word_objects)
    {
        // move word cards back to community and hand
        $community_cards = array();
        $hand_cards = array();

        foreach ($word_objects as $word_object) {
            $letter_origin = $word_object['letter_origin'];
            if ($letter_origin == 'c') {
                $community_cards[] = $word_object['card_id'];
            } elseif ($letter_origin = 'h') {
                $hand_cards[] = $word_object['card_id'];
            }
        }
        
        $this->cards->moveCards($community_cards, 'community');
        $this->cards->moveCards($hand_cards, 'hand', self::getActivePlayerId());
    }

    function returnAllWordCards($main_word_objects, $second_word_objects)
    {
        self::returnCardsForWord($main_word_objects);
        self::returnCardsForWord($second_word_objects);
        self::clearWord();
    }

    function notifyAutomaticChallengeRejectedWord($rejected_word_string)
    {
        self::notifyAllPlayers('automaticChallengeRejectedWord',
            clienttranslate('Automatic challenge rejected ‘${rejected_word}’'),
            array(
                'player_id' => self::getActivePlayerId(),
                'rejected_word' => $rejected_word_string
            )
        );
    }

    function notifyPlayerMayTryAgain()
    {
        self::notifyAllPlayers('playerMayTryAgain',
            clienttranslate('${player_name} may try again'),
            array(
                'player_id' => self::getActivePlayerId(),
                'player_name' => self::getActivePlayerName()
            )
        );
    }

    function notifyPlayerMayTryAgainRetriesLeft($retries_left)
    {
        self::notifyAllPlayers('playerMayTryAgainRetriesLeft',
            clienttranslate('${player_name} may try again (retries left: ${retries_left})'),
            array(
                'player_id' => self::getActivePlayerId(),
                'player_name' => self::getActivePlayerName(),
                'retries_left' => $retries_left
            )
        );
    }

    function notifyPlayerMustDiscardNoRetries()
    {
        self::notifyAllPlayers('playerMustDiscardNoRetries',
            clienttranslate('${player_name} ran out of retries and must discard a card'),
            array(
                'player_id' => self::getActivePlayerId(),
                'player_name' => self::getActivePlayerName()
            )
        );
    }

    function notifyPlayerChallenged($challenger_id)
    {
        self::notifyAllPlayers('playerChallenged',
            clienttranslate('${player_name} challenged!'),
            array(
                'player_id' => $challenger_id,
                'player_name' => self::getPlayerName($challenger_id)
            )
        );
    }

    function notifyPlayerChallengeSucceeded($rejected_word_string)
    {
        self::notifyAllPlayers('playerChallengeSucceeded',
            clienttranslate('Challenge successful! ‘${rejected_word}’ rejected'),
            array(
                'rejected_word' => $rejected_word_string
            )
        );
    }

    function notifyPlayerChallengeFailed()
    {
        self::notifyAllPlayers('playerChallengeFailed',
            clienttranslate('Challenge failed!'),
            array()
        );
    }

    function notifyPlayerMustDiscard()
    {
        self::notifyAllPlayers('playerMustDiscard',
            clienttranslate('${player_name} must discard a card for being challenged correctly'),
            array(
                'player_id' => self::getActivePlayerId(),
                'player_name' => self::getActivePlayerName()
            )
        );
    }

    function notifyChallengerPaidPenalty($challenger_id)
    {
        self::notifyAllPlayers('challengerPaidPenalty',
            clienttranslate('${player_name} paid $1 for challenging incorrectly'),
            array(
                'player_id' => $challenger_id,
                'player_name' => self::getPlayerName($challenger_id)
            )
        );
    }

    function notifyPlayerReceivedPayment()
    {
        self::notifyAllPlayers('playerReceivedPayment',
            clienttranslate('${player_name} received $1 for being challenged incorrectly'),
            array(
                'player_id' => self::getActivePlayerId(),
                'player_name' => self::getActivePlayerName()
            )
        );
    }

    function getScoringInfoForWord($word_objects)
    {
        $word_len = 0;
        $contains_Q = FALSE;
        $is_vowel = array();
        $num_vowels = 0;
        foreach ($word_objects as $word_object) {
            $word_len++;
            $letter = $word_object['letter'];
            if ($letter === 'Q') {
                $contains_Q = TRUE;
            }
            $letter_type = $word_object['letter_type'];
            if ($letter_type === '_') {
                $current_is_vowel = $this->letter_types[$letter] === 'vowel';
            } else {
                $current_is_vowel = $letter_type === 'v';
            }
            $is_vowel[] = $current_is_vowel;
            if ($current_is_vowel) {
                $num_vowels += 1;
            }
        }
        return array(
            'length' => $word_len,
            'B' => $is_vowel[0] && $is_vowel[$word_len - 1],
            'J' => $num_vowels >= $word_len / 2,
            'K' => $num_vowels === 1,
            'Q' => $contains_Q,
        );
    }

    function countRoyaltiesAndPurchasablePatentsForWord(
        $word_objects, $patent_owners, $player_money, &$royalties_by_player, &$purchasable_patents
    ) {
        $active_player_id = self::getActivePlayerId();
        foreach ($word_objects as $word_object) {
            $letter_origin = $word_object['letter_origin'];
            if ($letter_origin == 'c' || $letter_origin == 'h') {
                $letter = $word_object['letter'];
                $patent_owner = $patent_owners[$letter];
                if (isset($patent_owner)) {
                    if ($patent_owner != $active_player_id) {
                        if (array_key_exists($patent_owner, $royalties_by_player)) {
                            $royalties_by_player[$patent_owner] += 1;
                        } else {
                            $royalties_by_player[$patent_owner] = 1;
                        }
                    }
                } else {
                    if ($this->patent_costs[$letter] <= $player_money) {
                        $purchasable_patents[$letter] = TRUE;
                    }
                }
            }
        }
    }

    function playerMeetsGoal($player_id)
    {
        $goal = $this->goals[self::getPlayersNumber()];
        $goal_minimum = $goal['minimum'];
        $goal_value = $goal['value'];

        $player_patents_count = self::getPlayerPatentsCount($player_id);
        $player_patents_value = self::getPlayerPatentsValue($player_id);

        return $player_patents_count >= $goal_minimum && $player_patents_value >= $goal_value;
    }

    function isLastTurnOfLastRound()
    {
        // is this the last round?
        if (self::getGameStateValue('last_round') == 1) {
            $active_player_id = self::getActivePlayerId();
            $next_player_table = self::getNextPlayerTable();
            // is the next player the first player?
            return $next_player_table[$active_player_id] == $next_player_table[0];
        }
        return FALSE;
    }

    function recordWordStats($word_objects)
    {
        $active_player_id = self::getActivePlayerId();
        foreach ($word_objects as $word_object) {
            $letter = $word_object['letter'];
            if ($letter == 'Y') {
                $letter_type = $word_object['letter_type'];
                if ($letter_type == 'v') {
                    self::incStat(1, 'y_played_as_vowel', $active_player_id);
                } else if ($letter_type == 'c') {
                    self::incStat(1, 'y_played_as_consonant', $active_player_id);
                }
            }
            $letter_origin = $word_object['letter_origin'];
            if ($letter_origin == 'c') {
                self::incStat(1, 'cards_played_from_community', $active_player_id);
            } else if ($letter_origin == 'h') {
                self::incStat(1, 'cards_played_from_hand', $active_player_id);
            } else if ($letter_origin == 'd') {
                self::incStat(1, 'x_patent_ability_used', $active_player_id);
            } else if ($letter_origin == 's') {
                self::incStat(1, 'z_patent_ability_used', $active_player_id);
            }
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in lettertycoon.action.php)
    */

    // state: playerMayReplaceCard

    function replaceCard($card_id)
    {
        self::checkAction('replaceCard');

        $active_player_id = self::getActivePlayerId();
        $card = $this->cards->getCard($card_id);

        if (!($card['location'] == 'hand' && $card['location_arg'] == $active_player_id || $card['location'] == 'community')) {
            throw new BgaUserException(self::_('You cannot replace a card that is not in your hand or the community pool.'));
        }

        $this->cards->moveCard($card_id, 'discard');

        self::incStat(1, 'q_patent_ability_used', $active_player_id);

        if ($card['location'] == 'community') {
            self::incStat(1, 'cards_discarded_from_community', $active_player_id);
            self::notifyAllPlayers('playerReplacedCardFromCommunity',
                clienttranslate('${player_name} replaced a card from the community pool'),
                array(
                    'player_name' => self::getActivePlayerName(),
                    'card_id' => $card_id
                )
            );
        } else {
            self::incStat(1, 'cards_discarded_from_hand', $active_player_id);
            self::notifyPlayer($active_player_id, 'activePlayerReplacedCardFromHand', '', array(
                'card_id' => $card_id
            ));

            self::notifyAllPlayers('playerReplacedCardFromHand',
                clienttranslate('${player_name} replaced a card from their hand'),
                array(
                    'player_name' => self::getActivePlayerName()
                )
            );
        }

        $this->gamestate->nextState('replaceCard');
    }

    function skipReplaceCard()
    {
        self::checkAction('skipReplaceCard');
        $this->gamestate->nextState('skip');
    }

    // state: playerMayPlayWord

    function playWord($main_word, $second_word)
    {
        self::checkAction('playWord');

        $active_player_id = self::getActivePlayerId();
        $patent_owners = self::getPatentOwners();

        $check_main_word_result = self::checkWord($main_word, $active_player_id);
        $card_count = $check_main_word_result['card_count'];
        $duplicate_count = $check_main_word_result['duplicate_count'];
        $appended_s_count = $check_main_word_result['appended_s_count'];
        $duplicated_letter = $check_main_word_result['duplicated_letter'];

        if (isset($second_word)) {
            if ($patent_owners['V'] != $active_player_id) {
                throw new BgaVisibleSystemException("second word played but active player does not own V patent");
            }
            $check_second_word_result = self::checkWord($second_word, $active_player_id);
            $card_count += $check_second_word_result['card_count'];
            $duplicate_count += $check_second_word_result['duplicate_count'];
            $appended_s_count += $check_second_word_result['appended_s_count'];
            if (isset($check_second_word_result['duplicated_letter'])) {
                $duplicated_letter = $check_second_word_result['duplicated_letter'];
            }
        }

        if ($duplicate_count > 0) {
            if ($patent_owners['X'] != $active_player_id) {
                throw new BgaVisibleSystemException("word contains duplicate letter but active player does not own X patent");
            }
            if ($duplicate_count > 1) {
                throw new BgaVisibleSystemException("word contains more than one duplicate letter");
            }
            $duplicated_letter_count = substr_count($main_word['letters'], $duplicated_letter);
            if (isset($second_word)) {
                $duplicated_letter_count += substr_count($second_word['letters'], $duplicated_letter);
            }
            if ($duplicated_letter_count < 2) {
                throw new BgaVisibleSystemException("word contains duplicate letter but no matching original");
            }
            if ($card_count < 3) {
                throw new BgaUserException(self::_('You must use at least three (real) cards when using the duplicate letter (X patent) ability.'));
            }
        }

        if ($appended_s_count > 0) {
            if ($patent_owners['Z'] != $active_player_id) {
                throw new BgaVisibleSystemException("word contains added 'S' but active player does not own Z patent");
            }
            if ($appended_s_count > 1) {
                throw new BgaVisibleSystemException("word contains more than one added 'S'");
            }
        }

        // clear word table first, just in case
        self::clearWord();

        self::saveWord(1, $main_word);
        if (isset($second_word)) {
            self::saveWord(2, $second_word);
        }

        // move word cards from community or hand to word
        $this->cards->moveCards($main_word['card_ids'], 'word');
        if (isset($second_word)) {
            $this->cards->moveCards($second_word['card_ids'], 'word');
        }

        if (isset($second_word)) {
            $message = clienttranslate('${player_name} played ‘${main_word.letters}’ and ‘${second_word.letters}’');
        } else {
            $message = clienttranslate('${player_name} played ‘${main_word.letters}’');
        }
        
        self::notifyAllPlayers('playerPlayedWord',
            $message,
            array(
                'player_id' => self::getActivePlayerId(),
                'player_name' => self::getActivePlayerName(),
                'main_word' => $main_word,
                'second_word' => $second_word
            )
        );

        $this->gamestate->nextState('playWord');
    }

    function skipPlayWord()
    {
        self::checkAction('skipPlayWord');
        $this->gamestate->nextState('skip');
    }

    // state: playersMayChallenge

    function challengeWord()
    {
        self::checkAction('challengeWord');
        $current_player_id = self::getCurrentPlayerId();
        self::setPlayerChallenge($current_player_id, 1);
        $this->gamestate->setPlayerNonMultiactive($current_player_id, 'resolveChallenge');
    }

    function acceptWord()
    {
        self::checkAction('acceptWord');
        $current_player_id = self::getCurrentPlayerId();
        $this->gamestate->setPlayerNonMultiactive($current_player_id, 'resolveChallenge');
    }

    // state: playerMayBuyPatent

    function buyPatent($letter_index)
    {
        self::checkAction('buyPatent');

        if ($letter_index < 0 || $letter_index > 25) {
            throw new BgaVisibleSystemException("letter_index out of range: $letter_index");
        }

        $letter = chr(65 + $letter_index);
        $cost = $this->patent_costs[$letter];

        if (self::isPatentOwned($letter)) {
            throw new BgaUserException(self::_('You may not buy a patent that is already owned by another player.'));
        }

        if (!self::wordContainsCardWithLetter($letter)) {
            throw new BgaUserException(self::_('You may only buy a patent that matches a card in a word played this turn.'));
        }

        $active_player_id = self::getActivePlayerId();

        $player_money = self::getPlayerMoney($active_player_id);

        if ($player_money < $cost) {
            throw new BgaUserException(self::_('You do not have enough money to buy that patent.'));
        }

        self::setPatentOwner($letter, $active_player_id);

        self::updatePlayerCounters($active_player_id, -$cost, 0, $cost);
        self::incStat($cost, 'money_paid_for_patents', $active_player_id);

        self::notifyAllPlayers('playerBoughtPatent',
            clienttranslate('${player_name} bought the ‘${letter}’ patent for $${cost}'),
            array(
                'player_id' => $active_player_id,
                'player_name' => self::getActivePlayerName(),
                'letter' => $letter,
                'cost' => $cost
            )
        );

        $this->gamestate->nextState('buyPatent');
    }

    function skipBuyPatent()
    {
        self::checkAction('skipBuyPatent');
        $this->gamestate->nextState('skip');
    }

    // state: playerMayDiscardCards

    function discardCards($card_ids)
    {
        self::checkAction('discardCards');

        $active_player_id = self::getActivePlayerId();

        // ensure cards are in active player's hand
        $cards = $this->cards->getCards($card_ids);
        foreach ($cards as $card) {
            if($card['location'] != 'hand' || $card['location_arg'] != $active_player_id) {
                throw new BgaUserException(self::_('You cannot discard a card that is not in your hand.'));
            }
        }

        $num_cards = count($card_ids);
        
        // discard the cards
        $this->cards->moveCards($card_ids, 'discard');
        self::incStat($num_cards, 'cards_discarded_from_hand', $active_player_id);

        // notify the active player to discard the specific cards
        self::notifyPlayer($active_player_id, 'activePlayerDiscardedCards', '', array(
            'card_ids' => $card_ids
        ));

        // notify all players about the number of cards discarded
        self::notifyAllPlayers('playerDiscardedNumberOfCards',
            clienttranslate('${player_name} discarded ${num_cards} card(s)'),
            array(
                'player_name' => self::getActivePlayerName(),
                'num_cards' => $num_cards
            )
        );
        
        $this->gamestate->nextState('done');
    }

    function skipDiscardCards()
    {
        self::checkAction('skipDiscardCards');
        $this->gamestate->nextState('done');
    }

    // state: playerMustDiscardCard

    function discardCard($card_id)
    {
        self::checkAction('discardCard');
        
        $active_player_id = self::getActivePlayerId();

        // ensure card is in active player's hand
        $card = $this->cards->getCard($card_id);

        if ($card['location'] != 'hand' || $card['location_arg'] != $active_player_id) {
            throw new BgaUserException(self::_('You cannot discard a card that is not in your hand.'));
        }
        
        // discard the card
        $this->cards->moveCard($card_id, 'discard');
        self::incStat(1, 'cards_discarded_from_hand', $active_player_id);

        // notify the active player to discard the specific cards
        self::notifyPlayer($active_player_id, 'activePlayerDiscardedCards', '', array(
            'card_ids' => array($card_id)
        ));

        // notify all players about the number of cards discarded (e.g. 1)
        self::notifyAllPlayers('playerDiscardedNumberOfCards',
            clienttranslate('${player_name} discarded ${num_cards} card(s)'),
            array(
                'player_name' => self::getActivePlayerName(),
                'num_cards' => 1
            )
        );

        $this->gamestate->nextState('done');
    }
    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    function stStartTurn()
    {
        $active_player_id = self::getActivePlayerId();
        $patent_owners = self::getPatentOwners();

        self::incStat(1, 'turns_number');
        self::incStat(1, 'turns_number', $active_player_id);

        if (self::getGameStateValue('challenge_mode') == 2) { // automatic challenge
            self::setGameStateValue('retries_left', intval(self::getGameStateValue('automatic_challenge_retries')));
        } else {
            // no retries allowed in players challenge mode
            self::setGameStateValue('retries_left', 0);
        }
        

        if ($patent_owners['Q'] == $active_player_id) {
            $this->gamestate->nextState('hasReplaceCardOption');
        } else {
            $this->gamestate->nextState('noReplaceCardOption');
        }
    }

    function stReplaceCard()
    {
        self::refillCommunityPool();
        self::refillHand();
        $this->gamestate->nextState();
    }

    function stPlayWord()
    {
        if (self::getGameStateValue('challenge_mode') == 2) { // automatic challenge
            $this->gamestate->nextState('automaticChallengeVariant');
        } else { // players challenge (default)
            $this->gamestate->nextState('playersChallengeVariant');
        }
    }

    function stAutomaticChallenge()
    {
        $main_word_objects = self::getWordObjects(1);
        $second_word_objects = self::getWordObjects(2);

        $rejected_word_string = self::getRejectedWordIfAny($main_word_objects, $second_word_objects);

        if (isset($rejected_word_string)) {
            self::returnAllWordCards($main_word_objects, $second_word_objects);
            self::notifyAutomaticChallengeRejectedWord($rejected_word_string);

            $retries_left = intval(self::getGameStateValue('retries_left'));
            if ($retries_left > 0) {
                self::setGameStateValue('retries_left', $retries_left - 1);
                self::notifyPlayerMayTryAgainRetriesLeft($retries_left);
                $this->gamestate->nextState('wordRejectedTryAgain');
            } else if ($retries_left === 0) {
                self::notifyPlayerMustDiscardNoRetries();
                $this->gamestate->nextState('wordRejectedNoRetries');
            } else { // unlimited
                self::notifyPlayerMayTryAgain();
                $this->gamestate->nextState('wordRejectedTryAgain');
            }
            
        } else {
            $this->gamestate->nextState('wordAccepted');
        }
    }

    function stPlayersMayChallenge()
    {
        self::clearPlayersChallenge();

        $active_player_id = self::getActivePlayerId();
        $other_player_ids = self::getOtherPlayerIds($active_player_id);

        $this->gamestate->setPlayersMultiactive($other_player_ids, 'resolveChallenge', true);
    }

    function stResolveChallenge()
    {
        $challenger_id = self::getChallengerId();
        if (isset($challenger_id)) {
            self::notifyPlayerChallenged($challenger_id);

            $main_word_objects = self::getWordObjects(1);
            $second_word_objects = self::getWordObjects(2);

            $rejected_word_string = self::getRejectedWordIfAny($main_word_objects, $second_word_objects);

            if (isset($rejected_word_string)) {
                self::notifyPlayerChallengeSucceeded($rejected_word_string);
                self::returnAllWordCards($main_word_objects, $second_word_objects);
                self::notifyPlayerMustDiscard();
                $this->gamestate->nextState('wordRejected');
            } else {
                self::notifyPlayerChallengeFailed();
                $challenger_money = self::getPlayerMoney($challenger_id);
                if ($challenger_money > 0) {
                    self::updatePlayerCounters($challenger_id, -1, 0, 0);
                    self::incStat(1, 'money_paid_for_challenges', $challenger_id);
                    self::notifyChallengerPaidPenalty($challenger_id);
                }
                $active_player_id = self::getActivePlayerId();
                self::updatePlayerCounters($active_player_id, 1, 0, 0);
                self::incStat(1, 'money_received_from_challenges', $active_player_id);
                self::notifyPlayerReceivedPayment();
                $this->gamestate->nextState('scoreWord');
            }
        } else {
            // no challenge
            $this->gamestate->nextState('scoreWord');
        }
    }

    function stScoreWord()
    {
        $active_player_id = self::getActivePlayerId();

        $patent_owners = self::getPatentOwners();
        $scoring_patents_owned = array(
            'B' => $patent_owners['B'] == $active_player_id,
            'J' => $patent_owners['J'] == $active_player_id,
            'K' => $patent_owners['K'] == $active_player_id,
        );

        $q_uses = 0;
        $patents_used = array(
            'B' => FALSE,
            'J' => FALSE,
            'K' => FALSE,
        );

        $main_word_objects = self::getWordObjects(1);
        self::recordWordStats($main_word_objects);

        $main_word_scoring_info = self::getScoringInfoForWord($main_word_objects);
        $main_word_length = $main_word_scoring_info['length'];

        $main_word_scores = $this->scores[$main_word_length];
        $main_word_money = $main_word_scores['money'];
        $main_word_stock = $main_word_scores['stock'];

        if ($main_word_scoring_info['Q']) {
            $main_word_money *= 2;
            $main_word_stock *= 2;
            $q_uses++;
        }

        $second_word_objects = self::getWordObjects(2);
        $second_word_played = count($second_word_objects) > 0;        

        if ($second_word_played) {
            self::incStat(1, 'v_patent_ability_used', $active_player_id);
            self::recordWordStats($second_word_objects);

            $second_word_scoring_info = self::getScoringInfoForWord($second_word_objects);
            $second_word_length = $second_word_scoring_info['length'];

            $second_word_scores = $this->scores[$second_word_length];
            $second_word_money = $second_word_scores['money'];
            $second_word_stock = $second_word_scores['stock'];

            if ($second_word_scoring_info['Q']) {
                $second_word_money *= 2;
                $second_word_stock *= 2;
                $q_uses++;
            }

            $contested_doublers = 0;

            foreach ($scoring_patents_owned as $letter => $owned) {
                if ($owned) {
                    if ($main_word_scoring_info[$letter] && $second_word_scoring_info[$letter]) {
                        $contested_doublers++;
                        $patents_used[$letter] = TRUE;
                    } else if ($main_word_scoring_info[$letter]) {
                        $main_word_money *= 2;
                        $main_word_stock *= 2;
                        $patents_used[$letter] = TRUE;
                    } else if ($second_word_scoring_info[$letter]) {
                        $second_word_money *= 2;
                        $second_word_stock *= 2;
                        $patents_used[$letter] = TRUE;
                    }
                }
            }

            if ($contested_doublers > 0) {
                if ($main_word_money >= $second_word_money && $main_word_stock >= $second_word_stock) {
                    while ($contested_doublers > 0) {
                        $main_word_money *= 2;
                        $main_word_stock *= 2;
                        $contested_doublers--;
                    }
                } else {
                    while ($contested_doublers > 0) {
                        $second_word_money *= 2;
                        $second_word_stock *= 2;
                        $contested_doublers--;
                    }
                }
            }

            $money = $main_word_money + $second_word_money;
            $stock = $main_word_stock + $second_word_stock;

        } else {

            foreach ($scoring_patents_owned as $letter => $owned) {
                if ($owned && $main_word_scoring_info[$letter]) {
                    $main_word_money *= 2;
                    $main_word_stock *= 2;
                    $patents_used[$letter] = TRUE;
                }
            }

            $money = $main_word_money;
            $stock = $main_word_stock;
        }

        // Q & scoring patent stats
        if ($q_uses > 0) {
            self::incStat($q_uses, 'q_doubling_used', $active_player_id);
        }
        if ($patents_used['B']) {
            self::incStat(1, 'b_patent_ability_used', $active_player_id);
        }
        if ($patents_used['J']) {
            self::incStat(1, 'j_patent_ability_used', $active_player_id);
        }
        if ($patents_used['K']) {
            self::incStat(1, 'k_patent_ability_used', $active_player_id);
        }

        self::updatePlayerCounters($active_player_id, $money, $stock, 0);
        self::incStat($money, 'money_received_from_words', $active_player_id);
        self::incStat($stock, 'stock_received', $active_player_id);

        if ($stock > 0) {
            $message = clienttranslate('${player_name} received $${money} and ${stock} stock');
        } else {
            $message = clienttranslate('${player_name} received $${money}');
        }

        // notify
        self::notifyAllPlayers('playerReceivedMoneyAndStock',
            $message,
            array(
                'player_id' => $active_player_id,
                'player_name' => self::getActivePlayerName(),
                'money' => $money,
                'stock' => $stock
            )
        );

        // pay royalties
        
        $player_money = self::getPlayerMoney($active_player_id);

        $royalties_by_player = array();
        $purchasable_patents = array();

        self::countRoyaltiesAndPurchasablePatentsForWord(
            $main_word_objects, $patent_owners, $player_money, $royalties_by_player, $purchasable_patents
        );
        self::countRoyaltiesAndPurchasablePatentsForWord(
            $second_word_objects, $patent_owners, $player_money, $royalties_by_player, $purchasable_patents
        );

        $players = self::loadPlayersBasicInfos();

        foreach ($royalties_by_player as $player_id => $royalties) {
            self::updatePlayerCounters($player_id, $royalties, 0, 0);
            self::incStat($royalties, 'money_received_from_royalties', $player_id);

            self::notifyAllPlayers('playerReceivedRoyalties',
            clienttranslate('${player_name} received $${royalties} in royalties'),
                array(
                    'player_id' => $player_id,
                    'player_name' => $players[$player_id]['player_name'],
                    'royalties' => $royalties
                )
            );
        }

        if (count($purchasable_patents) > 0) {
            $this->gamestate->nextState('patentsAvailable');
        } else {
            $this->gamestate->nextState('noPatentsAvailable');
        }
    }

    function stBuyPatent()
    {
        $active_player_id = self::getActivePlayerId();

        // check if the last round was triggered
        if (self::playerMeetsGoal($active_player_id))
        {
            self::setGameStateValue('last_round', 1);

            self::notifyAllPlayers('playerTriggeredLastRound',
                clienttranslate('${player_name} triggered the last round!'),
                array(
                    'player_id' => $active_player_id,
                    'player_name' => self::getActivePlayerName()
                )
            );
        }

        $this->gamestate->nextState();
    }

    function stRefillCommunityPool()
    {
        // refill community pool if needed
        self::refillCommunityPool();

        // clear word table
        self::clearWord();

        // discard all word cards
        $this->cards->moveAllCardsInLocation('word', 'discard');

        self::notifyAllPlayers('wordDiscarded', '', array());

        $num_cards = $this->cards->countCardsInLocation('hand', self::getActivePlayerId());
        if ($num_cards > 0) {
            $this->gamestate->nextState('discardCards');
        } else {
            $this->gamestate->nextState('refillHand');
        }
    }

    function stRefillHand()
    {
        self::refillHand();

        $this->gamestate->nextState();
    }

    function stEndTurn()
    {
        if (self::isLastTurnOfLastRound()) {
            $this->gamestate->nextState('endGame');
        } else {
            $player_id = self::activeNextPlayer();
            self::giveExtraTime($player_id);

            $this->gamestate->nextState('nextTurn');
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn($state, $active_player)
    {
        $statename = $state['name'];
        
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            
            return;
        }

        throw new feException("Zombie mode not supported at this game state: ".$statename);
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if ($from_version <= 1404301345)
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB($sql);
//        }
//        if ($from_version <= 1405061421)
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB($sql);
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
