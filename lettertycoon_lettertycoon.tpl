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


<div class="dark_area">
    <div class="area_label">{AVAILABLE_PATENTS}</div>
    <div id="available_patents"></div>
</div>

<div class="dark_area">
    <div class="area_label">{COMMUNITY_POOL}</div>
    <div id="community_pool"></div>
</div>

<div class="dark_area">
    <div id="word_area_header">
        <span class="area_label"">{WORD_AREA}</span>
        <a href="#" id="play_word_button" class="bgabutton bgabutton_blue">{PLAY_WORD}</a>
        <a href="#" id="clear_button" class="bgabutton bgabutton_gray">{CLEAR}</a>
    </div>
    <div id="main_word"></div>
    <div id="extra_word"></div>
</div>

<div class="dark_area" id="current_player_area">
    <div id="current_player_area_header">
        <span class="player_area_name" style="color:#{CURRENT_PLAYER_COLOR}">{YOU}</span>
        <a href="#" id="discard_button" class="bgabutton bgabutton_blue"></a>
    </div>
    <div id="current_player_hand"></div>
    <div class="player_area_patents" id="player_area_patents_{CURRENT_PLAYER_ID}"></div>
</div>

<div id="player_areas">

    <!-- BEGIN player -->
    <div class="dark_area other_player_area" id="other_player_area_{PLAYER_ID}">
        <div class="other_player_area_header">
            <span class="player_area_name" style="color:#{PLAYER_COLOR}">{PLAYER_NAME}</span>
        </div>
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

var jstpl_player_board_info='<div class="player_board_info">\
<span class="player_board_info_item player_board_coins" id="player_board_coins_${player_id}">\
<span class="player_board_counter" id="player_board_coins_counter_${player_id}">0</span>\
<span class="coin_icon"></span>\
</span>\
<span class="player_board_info_item player_board_stock" id="player_board_stock_${player_id}">\
<span class="player_board_counter" id="player_board_stock_counter_${player_id}">0</span>\
<span class="stock_icon"></span>\
</span>\
<span class="player_board_info_item player_board_patents" id="player_board_patents_${player_id}">\
<span class="player_board_counter" id="player_board_patents_counter_${player_id}">0</span>\
<span class="patents_icon"></span>\
</span>\
<span class="player_board_info_item player_board_zeppelin" id="player_board_zeppelin_${player_id}">\
<span class="zeppelin_icon"></span>\
</span>\
</div>';

</script>  

{OVERALL_GAME_FOOTER}
