<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * LetterTycoon implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * lettertycoon.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
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
                // game options
                'challenge_mode' => 100,
                'automatic_challenge_retries' => 101,
            )
        );

        $this->cards = self::getNew( 'module.common.deck' );
        $this->cards->init( 'card' );
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
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here

        // initialize card table
        $cards = array();
        foreach( $this->letter_counts as $letter => $count )
        {
            $cards[] = array( 'type' => $letter, 'type_arg' => 0, 'nbr' => $count );
        }
        $this->cards->createCards( $cards, 'deck' );
        $this->cards->shuffle( 'deck' );
        $this->cards->autoreshuffle = true;

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
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).

        $result['community'] = $this->cards->getCardsInLocation( 'community' );

        $result['hand'] = $this->cards->getCardsInLocation( 'hand', $current_player_id );

        $sql = 'SELECT patent_id, owning_player_id FROM patent ';
        $result['patent_owners'] = self::getCollectionFromDb( $sql, true );

        $sql = 'SELECT letter, letter_origin, letter_type, card_id
                FROM word
                WHERE word_num = 1
                ORDER BY word_pos ';
        $result['main_word'] = self::getObjectListFromDb( $sql );

        // todo: extra word
  
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

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function clearWord()
    {
        $sql = 'DELETE FROM word ';
        self::DbQuery( $sql );
    }

    // $word_num = 1 for main word, 2 for extra word
    function getWordObjects($word_num)
    {
        $sql = "SELECT letter, letter_origin, letter_type, card_id
                FROM word WHERE word_num = $word_num ORDER BY word_pos ";
        return self::getObjectListFromDb($sql);
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
        // todo
    }

    function skipReplaceCard()
    {
        self::checkAction('skipReplaceCard');
        $this->gamestate->nextState('skip');
    }

    // state: playerMayPlayWord

    function playWord($main_word)
    {
        self::checkAction('playWord');

        // todo: check args for validity
        // - for each word (main and extra):
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

        // todo: check rules
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

        // clear word table first?
        self::clearWord();

        $main_letters = $main_word['letters'];
        $main_letter_origins = $main_word['letter_origins'];
        $main_letter_types = $main_word['letter_types'];
        $main_card_ids = $main_word['card_ids'];

        $main_length = strlen($main_letters);

        // save main word
        $sql = 'INSERT INTO word (word_num, word_pos, letter, letter_origin, letter_type, card_id) VALUES ';
        $values = array();
        for ( $i = 0; $i < $main_length; $i++ )
        {
            $letter = $main_letters[$i];
            $letter_origin = $main_letter_origins[$i];
            $letter_type = $main_letter_types[$i];
            $card_id = $main_card_ids[$i]; // NULL?
            $values[] = "(1, $i, '$letter', '$letter_origin', '$letter_type', $card_id)";
        }
        $sql .= implode( ',', $values );
        self::DbQuery( $sql );

        // todo: extra word

        // move main word cards from community or hand to word
        $this->cards->moveCards($main_card_ids, 'word');

        self::notifyAllPlayers('playerPlayedWord',
            clienttranslate('${player_name} played "${main_word.letters}"'),
            array(
                'player_id' => self::getActivePlayerId(),
                'player_name' => self::getActivePlayerName(),
                'main_word' => $main_word
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
        // todo
    }

    function acceptWord()
    {
        self::checkAction('acceptWord');
        // todo
    }

    // state: playerMayBuyPatent

    function buyPatent()
    {
        self::checkAction('buyPatent');
        // todo
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

        // todo: notify all players about the number of cards discarded
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
        // todo
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
        // todo
        $this->gamestate->nextState();
    }

    function stPlayWord()
    {
        // todo
        $this->gamestate->nextState('automaticChallengeVariant');
    }

    function stAutomaticChallenge()
    {
        // get main word objects
        $main_word_objects = self::getWordObjects(1);

        // get main word string
        $main_word = self::stringFromWordObjects($main_word_objects);

        // load appropriate word list
        $main_word_wordlist = self::loadWordList(strlen($main_word));

        // is the main word in the word list?
        $mainWordInList = self::isWordInList(strtolower($main_word), $main_word_wordlist);

        if ($mainWordInList)
        {
            $this->gamestate->nextState('wordAccepted');
        }
        else
        {
            // todo: support limited number of retries

            $active_player_id = self::getActivePlayerId();
            
            // move word cards back to community and hand and clear word
            $community_cards = array();
            $hand_cards = array();
            foreach ( $main_word_objects as $main_word_object)
            {
                $letter_origin = $main_word_object['letter_origin'];
                if ($letter_origin == 'c')
                {
                    $community_cards[] = $main_word_object['card_id'];
                }
                elseif ($letter_origin = 'h')
                {
                    $hand_cards[] = $main_word_object['card_id'];
                }
            }
            $this->cards->moveCards($community_cards, 'community');
            $this->cards->moveCards($hand_cards, 'hand', $active_player_id);
            self::clearWord();

            self::notifyAllPlayers('automaticChallengeRejectedWordTryAgain',
                clienttranslate('Automatic challenge rejected "${main_word}", ${player_name} may try again'),
                array(
                    'player_id' => $active_player_id,
                    'player_name' => self::getActivePlayerName(),
                    'main_word' => $main_word
                )
            );

            $this->gamestate->nextState('wordRejectedTryAgain');
        }
    }

    function stPlayersMayChallenge()
    {
        // todo
        $this->gamestate->nextState('scoreWord');
    }

    function stResolveChallenge()
    {
        // todo
        $this->gamestate->nextState('wordAccepted');
    }

    function stChallengeFailed()
    {
        // todo
        $this->gamestate->nextState();
    }

    function stChallengeSucceeded()
    {
        // todo
        $this->gamestate->nextState();
    }

    function stScoreWord()
    {
        $active_player_id = self::getActivePlayerId();

        // get main word objects
        $main_word_objects = self::getWordObjects(1);

        // get main word string
        $main_word = self::stringFromWordObjects($main_word_objects);

        // get main word length
        $main_word_len = strlen($main_word);

        $main_word_scores = $this->scores[$main_word_len];
        $money = $main_word_scores['money'];
        $stock = $main_word_scores['stock'];

        // todo: extra word

        // update player money, stock, and score
        $sql = "UPDATE player SET
                    `money` = `money` + $money,
                    `stock` = `stock` + $stock,
                    player_score = $money + $stock + player_score_aux
                WHERE player_id = $active_player_id";
        self::DbQuery( $sql );

        // notify
        self::notifyAllPlayers('playerReceivedMoneyAndStock',
            // todo?: omit stock if zero?
            clienttranslate('${player_name} received ${money} coins and ${stock} stock'),
            array(
                'player_id' => self::getActivePlayerId(),
                'player_name' => self::getActivePlayerName(),
                'money' => $money,
                'stock' => $stock
            )
        );

        $this->gamestate->nextState();
    }

    function stPayRoyalties()
    {
        // todo
        $this->gamestate->nextState();
    }

    function stBuyPatent()
    {
        // todo
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

    // maybe not needed?
    function stDiscardCards()
    {
        // todo
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
        // todo: check for end of game
        
        $player_id = self::activeNextPlayer();
        self::giveExtraTime( $player_id );

        $this->gamestate->nextState('nextTurn');
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
