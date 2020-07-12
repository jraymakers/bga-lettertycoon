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
 * material.inc.php
 *
 * LetterTycoon game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/

// $this->letters = array(
//     'A' => array( 'cost' => 8,  'count' => 9,  'type' => 'vowel' ),
//     'B' => array( 'cost' => 2,  'count' => 2,  'type' => 'consonant' ),
//     'C' => array( 'cost' => 3,  'count' => 2,  'type' => 'consonant' ),
//     'D' => array( 'cost' => 4,  'count' => 4,  'type' => 'consonant' ),
//     'E' => array( 'cost' => 10, 'count' => 12, 'type' => 'vowel'),
//     'F' => array( 'cost' => 3,  'count' => 2,  'type' => 'consonant' ),
//     'G' => array( 'cost' => 3,  'count' => 3,  'type' => 'consonant' ),
//     'H' => array( 'cost' => 5,  'count' => 4,  'type' => 'consonant' ),
//     'I' => array( 'cost' => 7,  'count' => 9,  'type' => 'vowel' ),
//     'J' => array( 'cost' => 2,  'count' => 1,  'type' => 'consonant' ),
//     'K' => array( 'cost' => 2,  'count' => 1,  'type' => 'consonant' ),
//     'L' => array( 'cost' => 4,  'count' => 4,  'type' => 'consonant' ),
//     'M' => array( 'cost' => 3,  'count' => 2,  'type' => 'consonant' ),
//     'N' => array( 'cost' => 7,  'count' => 6,  'type' => 'consonant' ),
//     'O' => array( 'cost' => 7,  'count' => 8,  'type' => 'vowel' ),
//     'P' => array( 'cost' => 3,  'count' => 2,  'type' => 'consonant' ),
//     'Q' => array( 'cost' => 2,  'count' => 1,  'type' => 'consonant' ),
//     'R' => array( 'cost' => 6,  'count' => 6,  'type' => 'consonant' ),
//     'S' => array( 'cost' => 6,  'count' => 6,  'type' => 'consonant' ),
//     'T' => array( 'cost' => 8,  'count' => 6,  'type' => 'consonant' ),
//     'U' => array( 'cost' => 3,  'count' => 4,  'type' => 'vowel' ),
//     'V' => array( 'cost' => 2,  'count' => 2,  'type' => 'consonant' ),
//     'W' => array( 'cost' => 3,  'count' => 2,  'type' => 'consonant' ),
//     'X' => array( 'cost' => 2,  'count' => 1,  'type' => 'consonant' ),
//     'Y' => array( 'cost' => 3,  'count' => 2,  'type' => 'consonant_or_vowel' ),
//     'Z' => array( 'cost' => 2,  'count' => 1,  'type' => 'consonant' ),
// );

$this->goals = array(
    2 => array( 'minimum' => 6, 'value' => 45 ),
    3 => array( 'minimum' => 5, 'value' => 34 ),
    4 => array( 'minimum' => 3, 'value' => 26 ),
    5 => array( 'minimum' => 3, 'value' => 21 ),
);

$this->letter_counts = array(
    'A' => 9,
    'B' => 2,
    'C' => 2,
    'D' => 4,
    'E' => 12,
    'F' => 2,
    'G' => 3,
    'H' => 4,
    'I' => 9,
    'J' => 1,
    'K' => 1,
    'L' => 4,
    'M' => 2,
    'N' => 6,
    'O' => 8,
    'P' => 2,
    'Q' => 1,
    'R' => 6,
    'S' => 6,
    'T' => 6,
    'U' => 4,
    'V' => 2,
    'W' => 2,
    'X' => 1,
    'Y' => 2,
    'Z' => 1,
);

$this->letter_types = array(
    'A' => 'vowel',
    'B' => 'consonant',
    'C' => 'consonant',
    'D' => 'consonant',
    'E' => 'vowel',
    'F' => 'consonant',
    'G' => 'consonant',
    'H' => 'consonant',
    'I' => 'vowel',
    'J' => 'consonant',
    'K' => 'consonant',
    'L' => 'consonant',
    'M' => 'consonant',
    'N' => 'consonant',
    'O' => 'vowel',
    'P' => 'consonant',
    'Q' => 'consonant',
    'R' => 'consonant',
    'S' => 'consonant',
    'T' => 'consonant',
    'U' => 'vowel',
    'V' => 'consonant',
    'W' => 'consonant',
    'X' => 'consonant',
    'Y' => 'consonant_or_vowel',
    'Z' => 'consonant',
);

$this->patent_costs = array(
    'A' => 8,
    'B' => 2,
    'C' => 3,
    'D' => 4,
    'E' => 10,
    'F' => 3,
    'G' => 3,
    'H' => 5,
    'I' => 7,
    'J' => 2,
    'K' => 2,
    'L' => 4,
    'M' => 3,
    'N' => 7,
    'O' => 7,
    'P' => 3,
    'Q' => 2,
    'R' => 6,
    'S' => 6,
    'T' => 8,
    'U' => 3,
    'V' => 2,
    'W' => 3,
    'X' => 2,
    'Y' => 3,
    'Z' => 2,
);
