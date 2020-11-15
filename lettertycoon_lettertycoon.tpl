{OVERALL_GAME_HEADER}

<div class="lettertycoon_area" id="lettertycoon_current_player_hand_area">
    <div id="lettertycoon_current_player_hand_area_header" class="lettertycoon_area_header">
        <span class="lettertycoon_player_area_name" style="color:#{CURRENT_PLAYER_COLOR}">{YOUR_HAND}</span>
        <span id="lettertycoon_player_area_message"></span>
    </div>
    <div id="lettertycoon_current_player_hand"></div>
</div>

<div class="lettertycoon_area">
    <div class="lettertycoon_area_header">
        <span class="lettertycoon_area_label"">{WORD_AREA}</span>
    </div>
    <div id="lettertycoon_main_word"></div>
    <div id="lettertycoon_second_word"></div>
</div>

<div id="lettertycoon_community_pool_and_game_cards_area">
    <div class="lettertycoon_area" id="lettertycoon_community_pool_area">
        <div id="lettertycoon_community_pool_area_header" class="lettertycoon_area_header">
            <div class="lettertycoon_area_label">{COMMUNITY_POOL}</div>
            <div class="lettertycoon_deck_info">
                {DECK} <span id="lettertycoon_deck_counter"></span>
                /
                {DISCARD} <span id="lettertycoon_discard_counter"></span>
            </div>
        </div>
        <div id="lettertycoon_community_pool"></div>
    </div>

    <div class="lettertycoon_area" id="lettertycoon_game_cards_area">
        <div class="lettertycoon_area_header">
            <div class="lettertycoon_area_label">{GAME_CARDS}</div>
        </div>
        <div id="lettertycoon_game_cards">
            <div class="lettertycoon_card lettertycoon_game_card" id="lettertycoon_scoring_card"></div>
            <div class="lettertycoon_card lettertycoon_game_card" id="lettertycoon_frequencies_card"></div>
            <div class="lettertycoon_card lettertycoon_game_card lettertycoon_goal{PLAYER_COUNT}_card" id="lettertycoon_goal_card"></div>
        </div>
    </div>
</div>

<div class="lettertycoon_area">
    <div class="lettertycoon_area_header">
        <div class="lettertycoon_area_label">{AVAILABLE_PATENTS}</div>
    </div>
    <div id="lettertycoon_available_patents"></div>
</div>

<div id="lettertycoon_player_areas">

    <!-- BEGIN player -->
    <div class="lettertycoon_area player_patents_area" id="lettertycoon_player_patents_area_{PLAYER_ID}">
        <div class="lettertycoon_area_header">
            <span class="lettertycoon_player_area_name" style="color:#{PLAYER_COLOR}">{PLAYER_NAME}</span>
        </div>
        <div class="lettertycoon_player_patents" id="lettertycoon_player_patents_{PLAYER_ID}"></div>
    </div>
    <!-- END player -->

</div>

<script type="text/javascript">

var jstpl_player_board_info='<div class="lettertycoon_player_board_info">\
<span class="lettertycoon_player_board_info_item lettertycoon_player_board_coins" id="lettertycoon_player_board_coins_${player_id}">\
<span class="lettertycoon_player_board_counter" id="lettertycoon_player_board_coins_counter_${player_id}">0</span>\
<span class="lettertycoon_coin_icon"></span>\
</span>\
<span class="lettertycoon_player_board_info_item lettertycoon_player_board_stock" id="lettertycoon_player_board_stock_${player_id}">\
<span class="lettertycoon_player_board_counter" id="lettertycoon_player_board_stock_counter_${player_id}">0</span>\
<span class="lettertycoon_stock_icon"></span>\
</span>\
<span class="lettertycoon_player_board_info_item lettertycoon_player_board_patents" id="lettertycoon_player_board_patents_${player_id}">\
<span class="lettertycoon_player_board_counter" id="lettertycoon_player_board_patents_counter_${player_id}">0</span>\
<span class="lettertycoon_patents_icon"></span>\
</span>\
<span class="lettertycoon_player_board_info_item lettertycoon_player_board_zeppelin" id="lettertycoon_player_board_zeppelin_${player_id}">\
<span class="lettertycoon_zeppelin_icon"></span>\
</span>\
</div>';

var jstpl_player_board_patent_list='<div class="lettertycoon_player_board_patent_list">\
<span class="lettertycoon_player_board_patent_list_label">${patents_label}</span>\
<span class="lettertycoon_player_board_patent_list_value" id="lettertycoon_player_board_patent_list_${player_id}"></span>\
</div>';

var jstpl_card_tooltip='<div class="lettertycoon_tooltip_contents">\
<div class="lettertycoon_tooltip_body">\
<div class="lettertycoon_tooltip_body_left">\
<div class="lettertycoon_tooltip_item">${card_label}</div>\
<div class="lettertycoon_tooltip_item">${type_label}</div>\
<div class="lettertycoon_tooltip_item">${frequency_label}</div>\
<div class="lettertycoon_tooltip_item">${patent_cost_label}</div>\
</div>\
<div class="lettertycoon_tooltip_body_right">\
<div class="lettertycoon_tooltip_item">${letter}</div>\
<div class="lettertycoon_tooltip_item">${letter_type}</div>\
<div class="lettertycoon_tooltip_item">${letter_count}</div>\
<div class="lettertycoon_tooltip_item">$${patent_cost}</div>\
</div>\
</div>\
<div class="lettertycoon_tooltip_footer_175">${text}</div>\
</div>';

var jstpl_patent_tooltip='<div class="lettertycoon_tooltip_contents">\
<div class="lettertycoon_tooltip_body">\
<div class="lettertycoon_tooltip_body_left">\
<div class="lettertycoon_tooltip_item">${patent_label}</div>\
<div class="lettertycoon_tooltip_item">${cost_label}</div>\
</div>\
<div class="lettertycoon_tooltip_body_right">\
<div class="lettertycoon_tooltip_item">${letter}</div>\
<div class="lettertycoon_tooltip_item">$${cost}</div>\
</div>\
</div>\
<div class="lettertycoon_tooltip_footer_160">${text}</div>\
</div>';

var jstpl_goal_card_tooltip='<div class="lettertycoon_goal_card_tooltip_contents">\
<div class="lettertycoon_goal_card_tooltip_header">${x_player_goal}</div>\
<div>${goal_text_patents_value}</div>\
<div>${goal_text_minimum_patents}</div>\
</div>';

</script>

{OVERALL_GAME_FOOTER}
