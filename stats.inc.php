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

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice
    ("Control Panel" / "Manage Game" / "Your Game")
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
    
    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress
    
    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players
    
*/

$stats_type = array(

    // Statistics global to table
    'table' => array(

        'turns_number' => array(
            'id'=> 10,
            'name' => totranslate('Number of turns'),
            'type' => 'int'
        ),

        // number of cards drawn to community
        'cards_drawn_to_community' => array(
            'id'=> 25,
            'name' => totranslate('Cards drawn to community pool'),
            'type' => 'int'
        ),

    ),
    
    // Statistics existing for each player
    'player' => array(

        // GENERAL

        'turns_number' => array(
            'id'=> 10,
            'name' => totranslate('Number of turns'),
            'type' => 'int'
        ),

        // CARDS

        // number of cards played from hand
        'cards_played_from_hand' => array(
            'id'=> 20,
            'name' => totranslate('Cards played from hand'),
            'type' => 'int'
        ),
        // number of cards played from community
        'cards_played_from_community' => array(
            'id'=> 21,
            'name' => totranslate('Cards played from community pool'),
            'type' => 'int'
        ),
        // number of cards discarded from hand
        'cards_discarded_from_hand' => array(
            'id'=> 22,
            'name' => totranslate('Cards discarded from hand'),
            'type' => 'int'
        ),
        // number of cards discarded from hand
        'cards_discarded_from_community' => array(
            'id'=> 23,
            'name' => totranslate('Cards discarded from community pool'),
            'type' => 'int'
        ),
        // number of cards drawn to hand
        'cards_drawn_to_hand' => array(
            'id'=> 24,
            'name' => totranslate('Cards drawn to hand'),
            'type' => 'int'
        ),

        // MONEY AND STOCK

        // money received from words
        'money_received_from_words' => array(
            'id'=> 30,
            'name' => totranslate('Money received from words'),
            'type' => 'int'
        ),
        // money received from royalties
        'money_received_from_royalties' => array(
            'id'=> 31,
            'name' => totranslate('Money received from royalties'),
            'type' => 'int'
        ),
        // money received from challenges
        'money_received_from_challenges' => array(
            'id'=> 32,
            'name' => totranslate('Money received from challenges'),
            'type' => 'int'
        ),
        // money paid for patents
        'money_paid_for_patents' => array(
            'id'=> 33,
            'name' => totranslate('Money paid for patents'),
            'type' => 'int'
        ),
        // money paid for challenges
        'money_paid_for_challenges' => array(
            'id'=> 34,
            'name' => totranslate('Money paid for challenges'),
            'type' => 'int'
        ),
        
        // stock received
        'stock_received' => array(
            'id'=> 35,
            'name' => totranslate('Stock received'),
            'type' => 'int'
        ),

        // PATENTS

        // number of times Q doubling ability used
        // number of times Y played as vowel
        // number of times Y played as consonant

        // number of times B patent ability used
        // number of times J patent ability used
        // number of times K patent ability used
        // number of times Q patent ability used
        // number of times V patent ability used
        // number of times X patent ability used
        // number of times Z patent ability used
        
        // WORDS

        // number of 3 letter words
        // number of 4 letter words
        // number of 5 letter words
        // number of 6 letter words
        // number of 7 letter words
        // number of 8 letter words
        // number of 9 letter words
        // number of 10 letter words
        // number of 11 letter words
        // number of 12 letter words
        // total number of words played
        // average word length (float)

        // LETTERS

        // number of A's played
        // number of B's played
        // number of C's played
        // number of D's played
        // number of E's played
        // number of F's played
        // number of G's played
        // number of H's played
        // number of I's played
        // number of J's played
        // number of K's played
        // number of L's played
        // number of M's played
        // number of N's played
        // number of O's played
        // number of P's played
        // number of Q's played
        // number of R's played
        // number of S's played
        // number of T's played
        // number of U's played
        // number of V's played
        // number of W's played
        // number of X's played
        // number of Y's played
        // number of Z's played

        // CHALLENGES

        // number of successful challenges made
        // number of failed challenges made

        // number of times challenged correctly by another player
        // number of times challenged incorrectly by another player

        // number of times challenged correctly by automatic challenge
        // number of times challenged incorretly by automatic challenge

        // number of retries used
    )

);
