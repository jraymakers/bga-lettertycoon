{OVERALL_GAME_HEADER}

<div class="dark_area" id="current_player_hand_area">
    <div id="current_player_hand_area_header">
        <span class="player_area_name" style="color:#{CURRENT_PLAYER_COLOR}">{YOUR_HAND}</span>
        <a href="#" id="discard_button" class="bgabutton bgabutton_blue"></a>
    </div>
    <div id="current_player_hand"></div>
</div>

<div class="dark_area">
    <div id="word_area_header">
        <span class="area_label"">{WORD_AREA}</span>
        <a href="#" id="play_word_button" class="bgabutton bgabutton_blue">{PLAY_WORD}</a>
        <a href="#" id="clear_button" class="bgabutton bgabutton_gray">{CLEAR}</a>
    </div>
    <div id="main_word"></div>
    <div id="second_word"></div>
    <div id="word_area_footer">
        <a href="#" id="change_letter_type_button" class="bgabutton bgabutton_gray">{CHANGE_LETTER_TYPE}</a>
        <a href="#" id="start_second_word_button" class="bgabutton bgabutton_gray">{START_SECOND_WORD}</a>
        <a href="#" id="duplicate_letter_button" class="bgabutton bgabutton_gray">{DUPLICATE_LETTER}</a>
        <a href="#" id="add_an_s_button" class="bgabutton bgabutton_gray">{ADD_AN_S}</a>
    </div>
</div>

<div id="community_pool_and_game_cards_area">
    <div class="dark_area" id="community_pool_area">
        <div id="community_pool_area_header">
            <div class="area_label">{COMMUNITY_POOL}</div>
        </div>
        <div id="community_pool"></div>
    </div>

    <div class="dark_area" id="game_cards_area">
        <div id="game_cards_area_header">
            <div class="area_label">{GAME_CARDS}</div>
        </div>
        <div id="game_cards">
            <div class="card game_card" id="scoring_card"></div>
            <div class="card game_card" id="frequencies_card"></div>
            <div class="card game_card goal{PLAYER_COUNT}_card" id="goal_card"></div>
        </div>
    </div>
</div>

<div class="dark_area">
    <div id="available_patents_area_header">
        <div class="area_label">{AVAILABLE_PATENTS}</div>
    </div>
    <div id="available_patents"></div>
</div>

<div id="player_areas">

    <!-- BEGIN player -->
    <div class="dark_area player_patents_area" id="player_patents_area_{PLAYER_ID}">
        <div class="player_patents_area_header">
            <span class="player_area_name" style="color:#{PLAYER_COLOR}">{PLAYER_NAME}</span>
        </div>
        <div class="player_patents" id="player_patents_{PLAYER_ID}"></div>
    </div>
    <!-- END player -->

</div>

<script type="text/javascript">

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

var jstpl_card_tooltip='<div class="lettertycoon_tooltip_contents">\
<div class="lettertycoon_tooltip_body">\
<div class="lettertycoon_tooltip_body_left">\
<div>{CARD}</div>\
<div>{TYPE}</div>\
<div>{FREQUENCY}</div>\
<div>{PATENT_COST}</div>\
</div>\
<div class="lettertycoon_tooltip_body_right">\
<div>${letter}</div>\
<div>${letter_type}</div>\
<div>${letter_count}</div>\
<div>$${patent_cost}</div>\
</div>\
</div>\
<div class="lettertycoon_tooltip_footer_175">${text}</div>\
</div>';

var jstpl_patent_tooltip='<div class="lettertycoon_tooltip_contents">\
<div class="lettertycoon_tooltip_body">\
<div class="lettertycoon_tooltip_body_left">\
<div>{PATENT}</div>\
<div>{COST}</div>\
</div>\
<div class="lettertycoon_tooltip_body_right">\
<div>${letter}</div>\
<div>$${cost}</div>\
</div>\
</div>\
<div class="lettertycoon_tooltip_footer_160">${text}</div>\
</div>';

var jstpl_goal_card_tooltip='<div class="goal_card_tooltip_contents">\
<div class="goal_card_tooltip_header">${player_count} Player Goal</div>\
<div><b>$${value}</b> in patents owned by any player</div>\
<div>Requires a minimum of <b>${minimum}</b> patents</div>\
</div>';

</script>

{OVERALL_GAME_FOOTER}
