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
        'q_doubling_used' => array(
            'id'=> 40,
            'name' => totranslate('‘Q’ doubling ability used'),
            'type' => 'int'
        ),
        // number of times Y played as vowel
        'y_played_as_vowel' => array(
            'id'=> 41,
            'name' => totranslate('‘Y’ played as vowel'),
            'type' => 'int'
        ),
        // number of times Y played as consonant
        'y_played_as_consonant' => array(
            'id'=> 42,
            'name' => totranslate('‘Y’ played as consonant'),
            'type' => 'int'
        ),

        // number of times B patent ability used
        'b_patent_ability_used' => array(
            'id'=> 43,
            'name' => totranslate('‘B’ patent ability used'),
            'type' => 'int'
        ),
        // number of times J patent ability used
        'j_patent_ability_used' => array(
            'id'=> 44,
            'name' => totranslate('‘J’ patent ability used'),
            'type' => 'int'
        ),
        // number of times K patent ability used
        'k_patent_ability_used' => array(
            'id'=> 45,
            'name' => totranslate('‘K’ patent ability used'),
            'type' => 'int'
        ),
        // number of times Q patent ability used
        'q_patent_ability_used' => array(
            'id'=> 46,
            'name' => totranslate('‘Q’ patent ability used'),
            'type' => 'int'
        ),
        // number of times V patent ability used
        'v_patent_ability_used' => array(
            'id'=> 47,
            'name' => totranslate('‘V’ patent ability used'),
            'type' => 'int'
        ),
        // number of times X patent ability used
        'x_patent_ability_used' => array(
            'id'=> 48,
            'name' => totranslate('‘X’ patent ability used'),
            'type' => 'int'
        ),
        // number of times Z patent ability used
        'z_patent_ability_used' => array(
            'id'=> 49,
            'name' => totranslate('‘Z’ patent ability used'),
            'type' => 'int'
        ),
        
        // WORDS

        // number of 3 letter words
        'words_played_length_3' => array(
            'id'=> 103,
            'name' => totranslate('3 letter words played'),
            'type' => 'int'
        ),
        // number of 4 letter words
        'words_played_length_4' => array(
            'id'=> 104,
            'name' => totranslate('4 letter words played'),
            'type' => 'int'
        ),
        // number of 5 letter words
        'words_played_length_5' => array(
            'id'=> 105,
            'name' => totranslate('5 letter words played'),
            'type' => 'int'
        ),
        // number of 6 letter words
        'words_played_length_6' => array(
            'id'=> 106,
            'name' => totranslate('6 letter words played'),
            'type' => 'int'
        ),
        // number of 7 letter words
        'words_played_length_7' => array(
            'id'=> 107,
            'name' => totranslate('7 letter words played'),
            'type' => 'int'
        ),
        // number of 8 letter words
        'words_played_length_8' => array(
            'id'=> 108,
            'name' => totranslate('8 letter words played'),
            'type' => 'int'
        ),
        // number of 9 letter words
        'words_played_length_9' => array(
            'id'=> 109,
            'name' => totranslate('9 letter words played'),
            'type' => 'int'
        ),
        // number of 10 letter words
        'words_played_length_10' => array(
            'id'=> 110,
            'name' => totranslate('10 letter words played'),
            'type' => 'int'
        ),
        // number of 11 letter words
        'words_played_length_11' => array(
            'id'=> 111,
            'name' => totranslate('11 letter words played'),
            'type' => 'int'
        ),
        // number of 12 letter words
        'words_played_length_12' => array(
            'id'=> 112,
            'name' => totranslate('12 letter words played'),
            'type' => 'int'
        ),
        // total number of letters played
        'letters_played_total' => array(
            'id'=> 120,
            'name' => totranslate('Total letters played'),
            'type' => 'int'
        ),
        // total number of words played
        'words_played_total' => array(
            'id'=> 121,
            'name' => totranslate('Total words played'),
            'type' => 'int'
        ),
        // average word length (float)
        'word_length_average' => array(
            'id'=> 122,
            'name' => totranslate('Average word length'),
            'type' => 'float'
        ),

        // LETTERS

        // number of A's played
        'letters_played_A' => array(
            'id'=> 200,
            'name' => totranslate('‘A’s played'),
            'type' => 'int'
        ),
        // number of B's played
        'letters_played_B' => array(
            'id'=> 201,
            'name' => totranslate('‘B’s played'),
            'type' => 'int'
        ),
        // number of C's played
        'letters_played_C' => array(
            'id'=> 202,
            'name' => totranslate('‘C’s played'),
            'type' => 'int'
        ),
        // number of D's played
        'letters_played_D' => array(
            'id'=> 203,
            'name' => totranslate('‘D’s played'),
            'type' => 'int'
        ),
        // number of E's played
        'letters_played_E' => array(
            'id'=> 204,
            'name' => totranslate('‘E’s played'),
            'type' => 'int'
        ),
        // number of F's played
        'letters_played_F' => array(
            'id'=> 205,
            'name' => totranslate('‘F’s played'),
            'type' => 'int'
        ),
        // number of G's played
        'letters_played_G' => array(
            'id'=> 206,
            'name' => totranslate('‘G’s played'),
            'type' => 'int'
        ),
        // number of H's played
        'letters_played_H' => array(
            'id'=> 207,
            'name' => totranslate('‘H’s played'),
            'type' => 'int'
        ),
        // number of I's played
        'letters_played_I' => array(
            'id'=> 208,
            'name' => totranslate('‘I’s played'),
            'type' => 'int'
        ),
        // number of J's played
        'letters_played_J' => array(
            'id'=> 209,
            'name' => totranslate('‘J’s played'),
            'type' => 'int'
        ),
        // number of K's played
        'letters_played_K' => array(
            'id'=> 210,
            'name' => totranslate('‘K’s played'),
            'type' => 'int'
        ),
        // number of L's played
        'letters_played_L' => array(
            'id'=> 211,
            'name' => totranslate('‘L’s played'),
            'type' => 'int'
        ),
        // number of M's played
        'letters_played_M' => array(
            'id'=> 212,
            'name' => totranslate('‘M’s played'),
            'type' => 'int'
        ),
        // number of N's played
        'letters_played_N' => array(
            'id'=> 213,
            'name' => totranslate('‘N’s played'),
            'type' => 'int'
        ),
        // number of O's played
        'letters_played_O' => array(
            'id'=> 214,
            'name' => totranslate('‘O’s played'),
            'type' => 'int'
        ),
        // number of P's played
        'letters_played_P' => array(
            'id'=> 215,
            'name' => totranslate('‘P’s played'),
            'type' => 'int'
        ),
        // number of Q's played
        'letters_played_Q' => array(
            'id'=> 216,
            'name' => totranslate('‘Q’s played'),
            'type' => 'int'
        ),
        // number of R's played
        'letters_played_R' => array(
            'id'=> 217,
            'name' => totranslate('‘R’s played'),
            'type' => 'int'
        ),
        // number of S's played
        'letters_played_S' => array(
            'id'=> 218,
            'name' => totranslate('‘S’s played'),
            'type' => 'int'
        ),
        // number of T's played
        'letters_played_T' => array(
            'id'=> 219,
            'name' => totranslate('‘T’s played'),
            'type' => 'int'
        ),
        // number of U's played
        'letters_played_U' => array(
            'id'=> 220,
            'name' => totranslate('‘U’s played'),
            'type' => 'int'
        ),
        // number of V's played
        'letters_played_V' => array(
            'id'=> 221,
            'name' => totranslate('‘V’s played'),
            'type' => 'int'
        ),
        // number of W's played
        'letters_played_W' => array(
            'id'=> 222,
            'name' => totranslate('‘W’s played'),
            'type' => 'int'
        ),
        // number of X's played
        'letters_played_X' => array(
            'id'=> 223,
            'name' => totranslate('‘X’s played'),
            'type' => 'int'
        ),
        // number of Y's played
        'letters_played_Y' => array(
            'id'=> 224,
            'name' => totranslate('‘Y’s played'),
            'type' => 'int'
        ),
        // number of Z's played
        'letters_played_Z' => array(
            'id'=> 225,
            'name' => totranslate('‘Z’s played'),
            'type' => 'int'
        ),

        // CHALLENGES

        // number of successful challenges made
        'successful_challenges_initiated' => array(
            'id'=> 300,
            'name' => totranslate('Successful challenges initiated'),
            'type' => 'int'
        ),
        // number of failed challenges made
        'failed_challenges_initiated' => array(
            'id'=> 301,
            'name' => totranslate('Failed challenges initiated'),
            'type' => 'int'
        ),

        // number of times challenged correctly by another player
        'correct_challenges_received' => array(
            'id'=> 302,
            'name' => totranslate('Correct challenges received (by other players)'),
            'type' => 'int'
        ),
        // number of times challenged incorrectly by another player
        'incorrect_challenges_received' => array(
            'id'=> 303,
            'name' => totranslate('Incorrect challenges received (by other players)'),
            'type' => 'int'
        ),

        // number of times a word was rejected by automatic challenge
        'words_rejected_by_automatic_challenge' => array(
            'id'=> 310,
            'name' => totranslate('Words rejected by automatic challenge'),
            'type' => 'int'
        ),

        // number of retries used
        'retries_used' => array(
            'id'=> 311,
            'name' => totranslate('Retries used'),
            'type' => 'int'
        ),
        // number of times player ran out of retries
        'ran_out_of_retries' => array(
            'id'=> 312,
            'name' => totranslate('Ran out of retries'),
            'type' => 'int'
        ),
    )

);
