<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * LetterTycoon implementation : © Jeff Raymakers <jephly@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * All options defined in this file should have a corresponding "game state labels"
 * with the same ID (see "initGameStateLabels" in lettertycoon.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(

    100 => array(
        'name' => totranslate('Challenge mode'),
        'values' => array(
            1 => array(
                'name' => totranslate('Players challenge'),
                'description' => totranslate('Played words may be challenged by other players. This follows the rules as written. It is suitable for real-time play.'),
                'tmdisplay' => totranslate('Players challenge'),
            ),
            2 => array(
                'name' => totranslate('Automatic challenge'),
                'description' => totranslate('Played words are checked and challenged automatically by the system. Retries may be allowed. This is a variant designed to speed up turn-based play.'),
                'tmdisplay' => totranslate('Automatic challenge'),
            ),
        ),
    ),

    101 => array(
        'name' => totranslate('Automatic challenge retries'),
        'values' => array(
            0 => array(
                'name' => totranslate('None'),
                'description' => totranslate('If automatically challenged, player gets no retries.'),
                'tmdisplay' => totranslate('Retries: None'),
            ),
            1 => array(
                'name' => '1', // no totranslate because Arabic numerals are universal
                'description' => totranslate('If automatically challenged, player gets 1 retry.'),
                'tmdisplay' => totranslate('Retries: 1'),
            ),
            2 => array(
                'name' => '2', // no totranslate because Arabic numerals are universal
                'description' => totranslate('If automatically challenged, player gets 2 retries.'),
                'tmdisplay' => totranslate('Retries: 2'),
            ),
            3 => array(
                'name' => '3', // no totranslate because Arabic numerals are universal
                'description' => totranslate('If automatically challenged, player gets 3 retries.'),
                'tmdisplay' => totranslate('Retries: 3'),
            ),
            4 => array(
                'name' => '4', // no totranslate because Arabic numerals are universal
                'description' => totranslate('If automatically challenged, player gets 4 retries.'),
                'tmdisplay' => totranslate('Retries: 4'),
            ),
            5 => array(
                'name' => '5', // no totranslate because Arabic numerals are universal
                'description' => totranslate('If automatically challenged, player gets 5 retries.'),
                'tmdisplay' => totranslate('Retries: 5'),
            ),
            -1 => array(
                'name' => totranslate('Unlimited'),
                'description' => totranslate('If automatically challenged, player gets unlimited retries.'),
                'tmdisplay' => totranslate('Retries: Unlimited'),
            ),
        ),
        'default' => 3,
        'displaycondition' => array(
            array(
                'type' => 'otheroption',
                'id' => 100,
                'value' => 2,
            ),
        ),
    ),

    102 => array(
        'name' => totranslate('Dictionary'),
        'values' => array(
            1 => array(
                'name' => totranslate('NWL 2018'),
                'description' => totranslate('NASPA (North American Scrabble Players Association) Word List 2018'),
                'tmdisplay' => totranslate('NWL 2018'),
            ),
            2 => array(
                'name' => totranslate('CSW 2019'),
                'description' => totranslate('CSW (Collins Scrabble Words) 2019'),
                'tmdisplay' => totranslate('CSW 2019'),
            ),
            3 => array(
                'name' => totranslate('12dicts'),
                'description' => totranslate('12dicts (2of12inf + 3of6game) (Contains fewer rare words; meant for casual or restrictive play.)'),
                'tmdisplay' => totranslate('12dicts'),
            ),
        ),
    ),

    103 => array(
        'name' => totranslate('Doubling ability behavior (B, J, and K patents, and Q letter)'),
        'values' => array(
            1 => array(
                'name' => totranslate('Doubling abilities multiply'),
                'description' => totranslate('Two doubling abilities applied to one word results in 4x earnings, three in 8x, etc. This follows the rules as written.'),
                'tmdisplay' => totranslate('Doubling abilities multiply'),
            ),
            2 => array(
                'name' => totranslate('Doubling abilities add'),
                'description' => totranslate('Two doubling abilities applied to one word results in 3x earnings, three in 4x, etc. This is a variant designed for more experienced players.'),
                'tmdisplay' => totranslate('Doubling abilities add'),
            ),
            3 => array(
                'name' => totranslate('One doubling ability maximum'),
                'description' => totranslate('At most one doubling ability is applied per word, for a maximum result of 2x earnings. This is a variant designed for two-player games between more experienced players.'),
                'tmdisplay' => totranslate('One doubling ability maximum'),
            )
        ),
    ),

);
