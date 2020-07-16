{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- LetterTycoon implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    lettertycoon_lettertycoon.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->


<div class="whiteblock">
    <div class="area_label">{AVAILABLE_PATENTS}</div>
    <div id="available_patents"></div>
</div>

<div class="whiteblock">
    <div class="area_label">{COMMUNITY_POOL}</div>
    <div id="community_pool"></div>
</div>

<div class="whiteblock">
    <div class="area_label">{TABLE}</div>
    <div id="played_word_1"></div>
    <div id="played_word_2"></div>
</div>

<div class="whiteblock" id="current_player_area">
    <div class="player_area_name" style="color:#{CURRENT_PLAYER_COLOR}">{YOU}</div>
    <div id="current_player_hand"></div>
    <div class="player_area_patents" id="player_area_patents_{CURRENT_PLAYER_ID}"></div>
</div>

<div id="player_areas">

    <!-- BEGIN player -->
    <div class="whiteblock other_player_area" id="other_player_area_{PLAYER_ID}">
        <div class="player_area_name" style="color:#{PLAYER_COLOR}">{PLAYER_NAME}</div>
        <div class="player_area_patents" id="player_area_patents_{PLAYER_ID}"></div>
    </div>
    <!-- END player -->

</div>

<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

</script>  

{OVERALL_GAME_FOOTER}
