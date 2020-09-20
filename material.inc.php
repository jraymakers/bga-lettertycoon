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
 * This file is loaded in your game logic class constructor,
 * so these variables are available everywhere in your game logic code.
 *
 */

$this->goals = array(
    2 => array('minimum' => 6, 'value' => 45),
    3 => array('minimum' => 5, 'value' => 34),
    4 => array('minimum' => 3, 'value' => 26),
    5 => array('minimum' => 3, 'value' => 21),
);

$this->scores = array(
     3 => array('money' => 1, 'stock' => 0),
     4 => array('money' => 2, 'stock' => 0),
     5 => array('money' => 3, 'stock' => 0),
     6 => array('money' => 4, 'stock' => 1),
     7 => array('money' => 6, 'stock' => 1),
     8 => array('money' => 6, 'stock' => 2),
     9 => array('money' => 6, 'stock' => 3),
    10 => array('money' => 6, 'stock' => 4),
    11 => array('money' => 6, 'stock' => 5),
    12 => array('money' => 6, 'stock' => 6),
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
