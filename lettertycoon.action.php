<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * LetterTycoon implementation : © Jeff Raymakers <jephly@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *    
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/lettertycoon/lettertycoon/myAction.html", ...)
 *
 */

class action_lettertycoon extends APP_GameAction
{ 
    // Constructor: please do not modify
    public function __default()
    {
        if( self::isArg( 'notifwindow') )
        {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
        }
        else
        {
            $this->view = "lettertycoon_lettertycoon";
            self::trace( "Complete reinitialization of board game" );
        }
    }

    // utilities

    function parseNumberList($number_list_arg)
    {
        // Removing last ';' if exists
        if (substr( $number_list_arg, -1 ) == ';' ) {
            $number_list_arg = substr( $number_list_arg, 0, -1 );
        }

        if( $number_list_arg == '' ) {
            return array();
        } else {
            return explode( ';', $number_list_arg );
        }
    }
  
    // state: playerMayReplaceCard

    public function replaceCard()
    {
        self::setAjaxMode();

        self::ajaxResponse();
    }

    public function skipReplaceCard()
    {
        self::setAjaxMode();

        self::ajaxResponse();
    }

    // state: playerMayPlayWord

    public function playWord()
    {
        self::setAjaxMode();
        $main_word = array(
            'letters' => self::getArg('main_word_letters', AT_alphanum, true),
            'letter_origins' => self::getArg('main_word_letter_origins', AT_alphanum, true),
            'letter_types' => self::getArg('main_word_letter_types', AT_alphanum, true),
            'card_ids' => self::parseNumberList(self::getArg('main_word_card_ids', AT_numberlist, true)) // NULL?
        );
        // todo: optional extra word args
        $this->game->playWord($main_word);
        self::ajaxResponse();
    }

    public function skipPlayWord()
    {
        self::setAjaxMode();
        $this->game->skipPlayWord();
        self::ajaxResponse();
    }

    // state: playersMayChallenge

    public function challengeWord()
    {
        self::setAjaxMode();

        self::ajaxResponse();
    }

    public function acceptWord()
    {
        self::setAjaxMode();

        self::ajaxResponse();
    }

    // state: playerMayBuyPatent

    public function buyPatent()
    {
        self::setAjaxMode();

        self::ajaxResponse();
    }

    public function skipBuyPatent()
    {
        self::setAjaxMode();
        $this->game->skipBuyPatent();
        self::ajaxResponse();
    }

    // state: playerMayDiscardCards

    public function discardCards()
    {
        self::setAjaxMode();
        $card_ids = self::parseNumberList(self::getArg('card_ids', AT_numberlist, true));
        $this->game->discardCards($card_ids);
        self::ajaxResponse();
    }

    public function skipDiscardCards()
    {
        self::setAjaxMode();
        $this->game->skipDiscardCards();
        self::ajaxResponse();
    }

    // state: playerMustDiscardCard

    public function discardCard()
    {
        self::setAjaxMode();

        self::ajaxResponse();
    }

}
