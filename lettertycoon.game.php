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

require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );

class LetterTycoon extends Table
{
    function __construct( )
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

                // game options
                'challenge_mode' => 100,
                'automatic_challenge_retries' => 101,
            )
        );

        $this->cards = self::getNew( 'module.common.deck' );
        $this->cards->init( 'card' );
        $this->cards->autoreshuffle = true;
    }
    
    protected function getGameName( )
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
    protected function setupNewGame( $players, $options = array() )
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
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( ',', $values );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );

        self::setGameStateInitialValue('last_round', 0);
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // initialize card table
        $cards = array();
        foreach( $this->letter_counts as $letter => $count )
        {
            $cards[] = array( 'type' => $letter, 'type_arg' => 0, 'nbr' => $count );
        }
        $this->cards->createCards( $cards, 'deck' );
        $this->cards->shuffle( 'deck' );

        // initialize patent table
        $sql = 'INSERT INTO patent (patent_id, owning_player_id) VALUES ';
        $values = array();
        foreach ( $this->patent_costs as $letter => $cost )
        {
            $values[] = "('$letter', NULL)";
        }
        $sql .= implode( ',', $values );
        self::DbQuery( $sql );

        // deal 7 cards to each player
        $players = self::loadPlayersBasicInfos();
        foreach( $players as $player_id => $player )
        {
            $this->cards->pickCards( 7, 'deck', $player_id );
        }

        // deal 3 cards to the community pool
        $this->cards->pickCardsForLocation( 3, 'deck', 'community' );

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
        $result['players'] = self::getCollectionFromDb( $sql );
  
        $result['community'] = $this->cards->getCardsInLocation( 'community' );

        $result['hand'] = $this->cards->getCardsInLocation( 'hand', $current_player_id );

        $result['patent_owners'] = self::getPatentOwners();

        $result['main_word'] = self::getWordObjects(1);

        $result['second_word'] = self::getWordObjects(2);
  
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
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

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
        self::DbQuery( $sql );
    }

    function updatePlayerCounters($player_id, $money_change, $stock_change, $patents_value_change)
    {
        $sql = "UPDATE player SET
                    `money` = `money` + $money_change,
                    `stock` = `stock` + $stock_change,
                    player_score_aux = player_score_aux + $patents_value_change,
                    player_score = player_score + $money_change + $stock_change + $patents_value_change
                WHERE player_id = $player_id";
        self::DbQuery( $sql );
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
        for ( $word_pos = 0; $word_pos < $length; $word_pos++ )
        {
            $letter = $letters[$word_pos];
            $letter_origin = $letter_origins[$word_pos];
            $letter_type = $letter_types[$word_pos];
            $card_id = $card_ids[$word_pos];
            $values[] = "($word_num, $word_pos, '$letter', '$letter_origin', '$letter_type', $card_id)";
        }
        $sql .= implode( ',', $values );
        self::DbQuery( $sql );
    }

    function clearWord()
    {
        $sql = 'DELETE FROM word ';
        self::DbQuery( $sql );
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

    function stringFromWordObjects($word_objects)
    {
        $letters = '';
        foreach ( $word_objects as $word_object )
        {
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
        while ( $low <= $high )
        {
            $mid = floor( ($low + $high) / 2 );
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
        self::notifyAllPlayers('automaticChallengeRejectedWordTryAgain',
            clienttranslate('Automatic challenge rejected "${rejected_word}", ${player_name} may try again'),
            array(
                'player_id' => self::getActivePlayerId(),
                'player_name' => self::getActivePlayerName(),
                'rejected_word' => $rejected_word_string
            )
        );
    }

    function isVowel($word_object)
    {
        $letter_type = $word_object['letter_type'];
        if ($letter_type === '_') {
            $letter = $word_object['letter'];
            return $this->letter_types[$letter] === 'vowel';
        } else {
            return $letter_type === 'v';
        }
    }

    function countVowels($word_objects)
    {
        $count = 0;
        foreach ($word_objects as $word_object) {
            if (self::isVowel($word_object)) {
                $count += 1;
            }
        }
        return $count;
    }

    function scoreWord($word_objects, $scoring_patents_owned)
    {
        $word_string = self::stringFromWordObjects($word_objects);
        $word_len = strlen($word_string);
        $word_scores = $this->scores[$word_len];
        $money = $word_scores['money'];
        $stock = $word_scores['stock'];
        if (strpos($word_string, 'Q') !== FALSE) {
            $money *= 2;
            $stock *= 2;
        }
        if ($scoring_patents_owned['B']) {
            if (self::isVowel($word_objects[0]) && self::isVowel($word_objects[$word_len - 1])) {
                $money *= 2;
                $stock *= 2;
            }
        }
        if ($scoring_patents_owned['J']) {
            $numVowels = self::countVowels($word_objects);
            if ($numVowels > $word_len / 2) {
                $money *= 2;
                $stock *= 2;
            }
        }
        if ($scoring_patents_owned['K']) {
            $numVowels = self::countVowels($word_objects);
            if ($numVowels === 1) {
                $money *= 2;
                $stock *= 2;
            }
        }
        return array( 'money' => $money, 'stock' => $stock );
    }

    function countRoyaltiesForWord($word_objects, $patent_owners, &$royalties_by_player)
    {
        $active_player_id = self::getActivePlayerId();
        foreach ($word_objects as $word_object) {
            $letter_origin = $word_object['letter_origin'];
            if ($letter_origin == 'c' || $letter_origin == 'h') {
                $letter = $word_object['letter'];
                $patent_owner = $patent_owners[$letter];
                if (isset($patent_owner) && $patent_owner != $active_player_id) {
                    if (array_key_exists($patent_owner, $royalties_by_player)) {
                        $royalties_by_player[$patent_owner] += 1;
                    } else {
                        $royalties_by_player[$patent_owner] = 1;
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
        if (self::getGameStateValue('last_round') == 1)
        {
            $active_player_id = self::getActivePlayerId();
            $next_player_table = self::getNextPlayerTable();
            // is the next player the first player?
            return $next_player_table[$active_player_id] == $next_player_table[0];
        }
        return FALSE;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in lettertycoon.action.php)
    */

    // state: playerMayReplaceCard

    function replaceCard()
    {
        // Note: player can replace card from community pool!
        self::checkAction('replaceCard');
        // TODO: implement (Q patent power)
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

        // TODO: check args for validity (play word)
        // - for each word (main and second):
        //   - does it have letters, letter_orgins, letter_types, and card_ids?
        //   - are they all the same length?
        //   - does letters contain only capital letters?
        //   - does letter_origins only contain valid chars?
        //   - does letter_types only contain valid chars?
        //   - do the cards in card_ids match the info in letters, letter_origins, and letter_types?
        //     - do the card letters match "letters"?
        //     - do the card origins match "letter_origins"?
        //     - do all Ys (and only Ys) have defined letter_types?
        //     (or do we allow undefined for Y if there are no relevant powers?)

        // TODO: check rules (play word)
        // - does each word contain at least three letters?
        // - is there at least one card from the players hand (in each word, if there are two)?
        // - if there are non-null letter types:
        //   - do these correspond to Ys? (or is this checked above?)
        // - if there are two words:
        //   - does the player own the V patent?
        //   - does each word have at least one card from the player's hand?
        // - if there is a duplicated letter:
        //   - does the player own the X patent?
        //   - is there only one duplicated letter?
        //   - was the duplicated letter played as a card somewhere else (in either word)?
        //   - was a total of at least three factory cards played (possibly across both words)?
        // - if there is an appended S:
        //   - does the player own the Z patent?
        //   - is there only one duplicated S?
        //   - does it appear at the end of a word?

        // self::dump('playWord: main_word', $main_word);

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
            $message = clienttranslate('${player_name} played "${main_word.letters}" and "${second_word.letters}"');
        } else {
            $message = clienttranslate('${player_name} played "${main_word.letters}"');
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
        // TODO: implement (players challenge)
    }

    function acceptWord()
    {
        self::checkAction('acceptWord');
        // TODO: implement (players challenge)
    }

    // state: playerMayBuyPatent

    function buyPatent($letter_index)
    {
        self::checkAction('buyPatent');

        if ($letter_index < 0 || $letter_index > 25) {
            throw new BgaVisibleSystemException( "letter_index out of range: $letter_index" );
        }

        $letter = chr(65 + $letter_index);
        $cost = $this->patent_costs[$letter];

        if (self::isPatentOwned($letter)) {
            throw new BgaUserException(self::_('You may not buy a patent that is already owned by another player.'));
        }

        if (!self::wordContainsCardWithLetter($letter)) {
            throw new BgaUserException(self::_('You may only buy a patent that matches a card a word played this turn.'));
        }

        $active_player_id = self::getActivePlayerId();

        $player_money = self::getPlayerMoney($active_player_id);

        if ($player_money < $cost) {
            throw new BgaUserException(self::_('You do not have enough money to buy that patent.'));
        }

        self::setPatentOwner($letter, $active_player_id);

        self::updatePlayerCounters($active_player_id, -$cost, 0, $cost);

        self::notifyAllPlayers('playerBoughtPatent',
            clienttranslate('${player_name} bought the "${letter}" patent for $${cost}'),
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
        foreach( $cards as $card )
        {
            if( $card['location'] != 'hand' || $card['location_arg'] != $active_player_id )
            {
                throw new BgaUserException( self::_('You cannot discard a card that is not in your hand.') );
            }
        }
        
        // discard the cards
        $this->cards->moveCards($card_ids, 'discard');

        // notify the active player to discard the specific cards
        self::notifyPlayer($active_player_id, 'activePlayerDiscardedCards', '', array(
            'card_ids' => $card_ids
        ));

        // notify all players about the number of cards discarded
        self::notifyAllPlayers('playerDiscardedNumberOfCards',
            clienttranslate('${player_name} discarded ${num_cards} card(s)'),
            array(
                'player_name' => self::getActivePlayerName(),
                'num_cards' => count($card_ids)
            )
        );
        
        $this->gamestate->nextState('discardCards');
    }

    function skipDiscardCards()
    {
        self::checkAction('skipDiscardCards');
        $this->gamestate->nextState('skip');
    }

    // state: playerMustDiscardCard

    function discardCard()
    {
        self::checkAction('discardCard');
        // TODO: implement (challenge)
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
        $this->gamestate->nextState('noReplaceCardOption');
    }

    function stReplaceCard()
    {
        // Note: player can replace card from community pool!
        // TODO: implement (Q patent power)
        $this->gamestate->nextState();
    }

    function stPlayWord()
    {
        // TODO: implement (challenge modes)
        $this->gamestate->nextState('automaticChallengeVariant');
    }

    function stAutomaticChallenge()
    {
        $main_word_objects = self::getWordObjects(1);
        $second_word_objects = self::getWordObjects(2);

        $main_word_string = self::stringFromWordObjects($main_word_objects);
        if (!self::isWordInDictionary($main_word_string)) {
            self::returnAllWordCards($main_word_objects, $second_word_objects);
            self::notifyAutomaticChallengeRejectedWord($main_word_string);
            $this->gamestate->nextState('wordRejectedTryAgain');
            return;
        }

        if (count($second_word_objects) > 0) {
            $second_word_string = self::stringFromWordObjects($second_word_objects);
            if (!self::isWordInDictionary($second_word_string)) {
                self::returnAllWordCards($main_word_objects, $second_word_objects);
                self::notifyAutomaticChallengeRejectedWord($second_word_string);
                $this->gamestate->nextState('wordRejectedTryAgain');
                return;
            }
        }
        
        $this->gamestate->nextState('wordAccepted');
    }

    function stPlayersMayChallenge()
    {
        // TODO: implement (players challenge)
        $this->gamestate->nextState('scoreWord');
    }

    function stResolveChallenge()
    {
        // TODO: implement (players challenge)
        $this->gamestate->nextState('wordAccepted');
    }

    function stChallengeFailed()
    {
        // TODO: implement (players challenge)
        $this->gamestate->nextState();
    }

    function stChallengeSucceeded()
    {
        // TODO: implement (players challenge)
        $this->gamestate->nextState();
    }

    function stScoreWord()
    {
        $active_player_id = self::getActivePlayerId();

        $main_word_objects = self::getWordObjects(1);
        $second_word_objects = self::getWordObjects(2);
        $second_word_played = count($second_word_objects) > 0;

        $patent_owners = self::getPatentOwners();
        $scoring_patents_owned = array(
            'B' => $patent_owners['B'] === $active_player_id,
            'J' => $patent_owners['J'] === $active_player_id,
            'K' => $patent_owners['K'] === $active_player_id,
        );

        if ($second_word_played && $scoring_patents_owned['B'] && $scoring_patents_owned['J']) {
            // special case: need to determine which word to use B and J patents with
            $money = 0;
            $stock = 0;
            for ($b = 1; $b <= 2; $b++) {
                for ($j = 1; $j <= 2; $j++) {
                    $scoring_patents_owned_1 = array(
                        'B' => $b === 1 ? $scoring_patents_owned['B'] : FALSE,
                        'J' => $j === 1 ? $scoring_patents_owned['J'] : FALSE,
                        'K' => $scoring_patents_owned['K']
                    );
                    $scoring_patents_owned_2 = array(
                        'B' => $b === 2 ? $scoring_patents_owned['B'] : FALSE,
                        'J' => $j === 2 ? $scoring_patents_owned['J'] : FALSE,
                        'K' => $scoring_patents_owned['K']
                    );
                    $main_word_score = self::scoreWord($main_word_objects, $scoring_patents_owned_1);
                    $option_money = $main_word_score['money'];
                    $option_stock = $main_word_score['stock'];
                    $second_word_score = self::scoreWord($second_word_objects, $scoring_patents_owned_2);
                    $option_money += $second_word_score['money'];
                    $option_stock += $second_word_score['stock'];
                    if ($option_money >= $money && $option_stock >= $stock) {
                        $money = $option_money;
                        $stock = $option_stock;
                    }
                }
            }
        } else {
            $main_word_score = self::scoreWord($main_word_objects, $scoring_patents_owned);
            $money = $main_word_score['money'];
            $stock = $main_word_score['stock'];
            if ($second_word_played) {
                $second_word_score = self::scoreWord($second_word_objects, $scoring_patents_owned);
                $money += $second_word_score['money'];
                $stock += $second_word_score['stock'];
            }
        }

        self::updatePlayerCounters($active_player_id, $money, $stock, 0);

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

        $patent_owners = self::getPatentOwners();

        $royalties_by_player = array();

        self::countRoyaltiesForWord($main_word_objects, $patent_owners, $royalties_by_player);
        self::countRoyaltiesForWord($second_word_objects, $patent_owners, $royalties_by_player);

        $players = self::loadPlayersBasicInfos();

        foreach ($royalties_by_player as $player_id => $royalties) {
            self::updatePlayerCounters($player_id, $royalties, 0, 0);

            self::notifyAllPlayers('playerReceivedRoyalties',
            clienttranslate('${player_name} received $${royalties} in royalties'),
                array(
                    'player_id' => $player_id,
                    'player_name' => $players[$player_id]['player_name'],
                    'royalties' => $royalties
                )
            );
        }

        // TODO: skip playerMayBuyPatent if no purchases are possible?

        $this->gamestate->nextState();
    }

    // TODO: maybe not needed?
    function stPayRoyalties()
    {
        // TODO: implement (if needed)
        $this->gamestate->nextState();
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
        $num_community_cards = $this->cards->countCardsInLocation( 'community' );

        if ($num_community_cards < 3) {
            $new_community_cards = $this->cards->pickCardsForLocation( 3 - $num_community_cards, 'deck', 'community' );

            self::notifyAllPlayers('communityReceivedCards', '', array(
                'new_cards' => $new_community_cards
            ));
        }

        // clear word table
        self::clearWord();

        // discard all word cards
        $this->cards->moveAllCardsInLocation( 'word', 'discard' );

        self::notifyAllPlayers('wordDiscarded', '', array());

        $this->gamestate->nextState();
    }

    // TODO: maybe not needed?
    function stDiscardCards()
    {
        // TODO: implement (if needed)
        $this->gamestate->nextState();
    }

    function stRefillHand()
    {
        $active_player_id = self::getActivePlayerId();
        $num_cards = $this->cards->countCardsInLocation( 'hand', $active_player_id );

        if ($num_cards < 7) {
            $new_cards = $this->cards->pickCards( 7 - $num_cards, 'deck', $active_player_id );

            self::notifyPlayer($active_player_id, 'activePlayerReceivedCards', '', array(
                'new_cards' => $new_cards
            ));
        }

        $this->gamestate->nextState();
    }

    function stEndTurn()
    {
        if (self::isLastTurnOfLastRound()) {
            $this->gamestate->nextState('endGame');
        } else {
            $player_id = self::activeNextPlayer();
            self::giveExtraTime( $player_id );

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

    function zombieTurn( $state, $active_player )
    {
        $statename = $state['name'];
        
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                    break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
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
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
