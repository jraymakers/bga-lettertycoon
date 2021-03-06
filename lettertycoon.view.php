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
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * The "build_page" method allows you to dynamically modify the HTML generated for the game interface.
 * In particular, you can set here the values of variables elements defined in lettertycoon_lettertycoon.tpl
 * (elements like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file).
 *
 */

require_once(APP_BASE_PATH."view/common/game.view.php");

class view_lettertycoon_lettertycoon extends game_view
{
    function getGameName() {
        return "lettertycoon";
    }    
    function build_page($viewArgs)
    {		
        // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count($players);

        /*********** Place your code below:  ************/

        global $g_user;
        $current_player_id = $g_user->get_id();
        if (array_key_exists($current_player_id, $players)) {
            $current_player_color = $players[$current_player_id]['player_color'];
        } else {
            $current_player_color = "000000";
        }

        $this->tpl['YOUR_HAND'] = self::_("Your Hand");
        $this->tpl['WORD_AREA'] = self::_("Word Area");
        $this->tpl['COMMUNITY_POOL'] = self::_("Community Pool");
        $this->tpl['DECK'] = self::_("Deck:");
        $this->tpl['DISCARD'] = self::_("Discard:");
        $this->tpl['GAME_CARDS'] = self::_("Game Cards");
        $this->tpl['LAST_ROUND'] = self::_("Last Round!");
        $this->tpl['AVAILABLE_PATENTS'] = self::_("Available Patents");

        $this->tpl['PLAYER_COUNT'] = $players_nbr;
        $this->tpl['CURRENT_PLAYER_ID'] = $current_player_id;
        $this->tpl['CURRENT_PLAYER_COLOR'] = $current_player_color;

        $this->page->begin_block('lettertycoon_lettertycoon', 'player');
        if (array_key_exists($current_player_id, $players)) {
            $this->page->insert_block('player', array(
                'PLAYER_ID' => $current_player_id,
                'PLAYER_NAME' => self::_("Your Patents"),
                'PLAYER_COLOR' => $current_player_color,
            ));
        }
        foreach ($players as $player_id => $player) {
            if ($player_id != $current_player_id) {
                $this->page->insert_block('player', array(
                    'PLAYER_ID' => $player_id,
                    'PLAYER_NAME' => $player['player_name'],
                    'PLAYER_COLOR' => $player['player_color'],
                ));
            }
        }

        /*********** Do not change anything below this line  ************/
    }
}


