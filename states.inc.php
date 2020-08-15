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
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with 'game' type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by 'st' (ex: 'stMyGameStateName').
   _ possibleactions: array that specify possible player actions on this step. It allows you to use 'checkAction'
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in 'nextState' PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on 'onEnteringState' or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!


$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        'name' => 'gameSetup',
        'description' => '',
        'type' => 'manager',
        'action' => 'stGameSetup',
        'transitions' => array( '' => 5 )
    ),
    
    5 => array(
        'name' => 'startTurn',
        'description' => '',
        'type' => 'game',
        'action' => 'stStartTurn',
        'transitions' => array( 'hasReplaceCardOption' => 20, 'noReplaceCardOption' => 30 )
    ),

    20 => array(
        'name' => 'playerMayReplaceCard',
        'description' => clienttranslate('${actplayer} may replace a card'),
        'descriptionmyturn' => clienttranslate('${you} may replace a card'),
        'type' => 'activeplayer',
        'possibleactions' => array( 'replaceCard', 'skipReplaceCard' ),
        'transitions' => array( 'replaceCard' => 21, 'skip' => 30 )
    ),

    21 => array(
        'name' => 'replaceCard',
        'description' => '',
        'type' => 'game',
        'action' => 'stReplaceCard',
        'transitions' => array( '' => 30 )
    ),

    30 => array(
        'name' => 'playerMayPlayWord',
        'description' => clienttranslate('${actplayer} may play a word'),
        'descriptionmyturn' => clienttranslate('${you} may play a word'),
        'type' => 'activeplayer',
        'possibleactions' => array( 'playWord', 'skipPlayWord' ),
        'transitions' => array( 'playWord' => 31, 'skip' => 60 )
    ),

    31 => array(
        'name' => 'playWord',
        'description' => '',
        'type' => 'game',
        'action' => 'stPlayWord',
        'transitions' => array( 'playersChallengeVariant' => 40, 'automaticChallengeVariant' => 35 )
    ),

    35 => array(
        'name' => 'automaticChallenge',
        'description' => '',
        'type' => 'game',
        'action' => 'stAutomaticChallenge',
        'transitions' => array( 'wordAccepted' => 45, 'wordRejectedTryAgain' => 30, 'wordRejectedNoRetries' => 70 )
    ),

    40 => array(
        'name' => 'playersMayChallenge',
        'description' => clienttranslate('Other players may challenge or accept the played word'),
        'descriptionmyturn' => clienttranslate('${you} may challenge or accept the played word'),
        'type' => 'multipleactiveplayer',
        'action' => 'stPlayersMayChallenge',
        'possibleactions' => array( 'challengeWord', 'acceptWord' ),
        'transitions' => array( 'resolveChallenge' => 41, 'scoreWord' => 45 )
    ),

    41 => array(
        'name' => 'resolveChallenge',
        'description' => '',
        'type' => 'game',
        'action' => 'stResolveChallenge',
        'transitions' => array( 'wordAccepted' => 42, 'wordRejected' => 43 )
    ),

    42 => array(
        'name' => 'challengeFailed',
        'description' => '',
        'type' => 'game',
        'action' => 'stChallengeFailed',
        'transitions' => array( '' => 45 )
    ),

    43 => array(
        'name' => 'challengeSucceeded',
        'description' => '',
        'type' => 'game',
        'action' => 'stChallengeSucceeded',
        'transitions' => array( '' => 70 )
    ),

    45 => array(
        'name' => 'scoreWord',
        'description' => '',
        'type' => 'game',
        'action' => 'stScoreWord',
        'transitions' => array( '' => 46 )
    ),

    46 => array(
        'name' => 'payRoyalties',
        'description' => '',
        'type' => 'game',
        'action' => 'stPayRoyalties',
        'transitions' => array( '' => 50 )
    ),

    50 => array(
        'name' => 'playerMayBuyPatent',
        'description' => clienttranslate('${actplayer} may buy a patent'),
        'descriptionmyturn' => clienttranslate('${you} may buy a patent'),
        'type' => 'activeplayer',
        'possibleactions' => array( 'buyPatent', 'skipBuyPatent' ),
        'transitions' => array( 'buyPatent' => 51, 'skip' => 52 )
    ),

    // TODO: maybe not needed?
    51 => array(
        'name' => 'buyPatent',
        'description' => '',
        'type' => 'game',
        'action' => 'stBuyPatent',
        'transitions' => array( '' => 52 )
    ),

    52 => array(
        'name' => 'refillCommunityPool',
        'description' => '',
        'type' => 'game',
        'action' => 'stRefillCommunityPool',
        'transitions' => array( '' => 60 )
    ),

    60 => array(
        'name' => 'playerMayDiscardCards',
        'description' => clienttranslate('${actplayer} may discard cards'),
        'descriptionmyturn' => clienttranslate('${you} may discard cards'),
        'type' => 'activeplayer',
        'possibleactions' => array( 'discardCards', 'skipDiscardCards' ),
        'transitions' => array( 'discardCards' => 75, 'skip' => 76 )
    ),

    70 => array(
        'name' => 'playerMustDiscardCard',
        'description' => clienttranslate('${actplayer} must discard a card'),
        'descriptionmyturn' => clienttranslate('${you} must discard a card'),
        'type' => 'activeplayer',
        'possibleactions' => array( 'discardCard' ),
        'transitions' => array( 'discardCard' => 75 )
    ),

    // TODO: maybe not needed?
    75 => array(
        'name' => 'discardCards',
        'description' => '',
        'type' => 'game',
        'action' => 'stDiscardCards',
        'transitions' => array( '' => 76 )
    ),

    76 => array(
        'name' => 'refillHand',
        'description' => '',
        'type' => 'game',
        'action' => 'stRefillHand',
        'transitions' => array( '' => 77 )
    ),

    77 => array(
        'name' => 'endTurn',
        'description' => '',
        'type' => 'game',
        'action' => 'stEndTurn',
        'transitions' => array( 'nextTurn' => 5, 'endGame' => 99 ),
        'updateGameProgression' => true
    ),
   
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        'name' => 'gameEnd',
        'description' => clienttranslate('End of game'),
        'type' => 'manager',
        'action' => 'stGameEnd',
        'args' => 'argGameEnd'
    )

);
