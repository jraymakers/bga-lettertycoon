/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * LetterTycoon implementation : © Jeff Raymakers <jephly@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

define([
    'dojo','dojo/_base/declare',
    'ebg/core/gamegui',
    'ebg/counter',
    'ebg/stock'
],
function (dojo, declare) {
    return declare('bgagame.lettertycoon', ebg.core.gamegui, {
        constructor: function () {
            // console.log('lettertycoon constructor');

            // constants
            this.cardWidth = 120;
            this.cardHeight = 165;
            this.patentWidth = 120;
            this.patentHeight = 79;

            this.addedSCardId = 205;
            this.duplicateCardId = 208;

            // card stocks
            this.communityStock = null;
            this.handStock = null;
            this.wordStock = []; // index: 1 or 2

            // patent stocks
            this.availablePatents = null;
            this.patentStocksByPlayer = {}; // key: player_id

            // patent owners
            this.patentOwners = {}; // key: letter, value: player_id

            // per-player counters
            this.playerMoney = {}; // key: player_id
            this.playerStock = {}; // key: player_id
            this.playerPatentsValue = {}; // key: player_id

            // word info
            
            // origins: values: 'c' = community, 'd' = duplicated, 'h' = hand, 's' = appended S
            // types: values: 'c' = consonant, 'v' = vowel, '_' = as defined
            this.wordInfo = [
                null, // unused (index 0)
                { origins: [], types: [] }, // main word (index 1)
                { origins: [], types: [] } // second word (index 2)
            ];
            
            this.secondWordStarted = false;

            // button visibility

            this.startSecondWordButtonVisible = false;
            this.duplicateLetterButtonVisible = false;
            this.toggleYButtonVisible = false;
            this.addAnSButtonVisible = false;

            // hand order

            this.handOrderList = []; // list of card ids
            this.handOrderMap = {}; // card id -> index

            this.reorderMode = false;
            this.reorderSourceItem = null;
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        setup: function (gamedatas) {
            // console.log('Starting game setup');

            this.communityStock = this.createCardStock('lettertycoon_community_pool');
            this.handStock = this.createCardStock('lettertycoon_current_player_hand');
            this.handStock.order_items = false;
            this.wordStock[1] = this.createCardStock('lettertycoon_main_word');
            this.wordStock[1].order_items = false;
            this.wordStock[2] = this.createCardStock('lettertycoon_second_word');
            this.wordStock[2].order_items = false;
            
            this.availablePatents = this.createPatentStock('lettertycoon_available_patents');

            this.goal = gamedatas.goal;
            this.scores = gamedatas.scores;
            this.letter_counts = gamedatas.letter_counts;
            this.letter_types = gamedatas.letter_types;
            this.patent_costs = gamedatas.patent_costs;
            this.patent_text = gamedatas.patent_text;

            var player_count = 0;
            var players = gamedatas.players;
            for (var player_id in players) {
                var player = players[player_id];
                player_count++;
                dojo.place(
                    this.format_block('jstpl_player_board_info', { player_id: player_id }),
                    $('player_board_'+player_id)
                );
                this.playerMoney[player_id] =
                    this.createCounter('lettertycoon_player_board_coins_counter_'+player_id, player.money);
                this.playerStock[player_id] =
                    this.createCounter('lettertycoon_player_board_stock_counter_'+player_id, player.stock);
                this.playerPatentsValue[player_id] =
                    this.createCounter('lettertycoon_player_board_patents_counter_'+player_id, player.patents_value);
                if (player.order !== '1') {
                    dojo.style($('lettertycoon_player_board_zeppelin_'+player_id), 'display', 'none');
                }
                this.patentStocksByPlayer[player_id] = this.createPatentStock('lettertycoon_player_patents_'+player_id);
            }

            this.patentOwners = gamedatas.patent_owners;
            for (var letter in this.patentOwners) {
                var letter_index = this.getLetterIndex(letter);
                var owner = this.patentOwners[letter];
                if (owner) {
                    this.patentStocksByPlayer[owner].addToStockWithId(letter_index, letter_index);
                } else {
                    this.availablePatents.addToStockWithId(letter_index, letter_index);
                }
            }

            var community = gamedatas.community;
            for (var card_id in community) {
                var card = community[card_id];
                this.communityStock.addToStockWithId(this.getLetterIndex(card.type), card.id);
            }

            // HAND ORDER: If the hand order was saved on the backend, we might do something like this:
            // this.handOrderList = gamedatas.hand_order;
            // for (var i = 0, l = this.handOrderList.length; i < l; i++) {
                // var id = this.handOrderList[i];
                // var card = hand[id];
                // ...
            // }

            var hand = gamedatas.hand;
            var handInOrder = [];
            for (var card_id in hand) {
                var card = hand[card_id];
                handInOrder.push(card);
            }
            handInOrder.sort((a,b) => {
                if (a.type < b.type) {
                    return -1;
                } else if (a.type > b.type) {
                    return 1;
                } else {
                    if (a.id < b.id) {
                        return -1;
                    } else if (a.id > b.id) {
                        return 1;
                    } else {
                        return 0;
                    }
                }
            });

            this.handOrderList = [];
            for (var card of handInOrder) {
                this.handOrderList.push(card.id);
                this.handStock.addToStockWithId(this.getLetterIndex(card.type), card.id);
            }
            this.updateHandOrderMap();

            var comparePlayerHandCards = dojo.hitch(this, function (a, b) {
                var aIndex = this.handOrderMap[a.id];
                var bIndex = this.handOrderMap[b.id];
                return aIndex > bIndex ? 1 : aIndex < bIndex ? -1 : 0;
            });
            this.handStock.sortItems = function () {
                this.items.sort(comparePlayerHandCards);
            };

            var main_word = gamedatas.main_word;
            for (var i in main_word) {
                var part = main_word[i];
                this.wordStock[1].addToStockWithId(this.getLetterIndex(part.letter), part.card_id);
                this.wordInfo[1].origins[i] = part.letter_origin;
                this.wordInfo[1].types[i] = part.letter_type;
            }

            var second_word = gamedatas.second_word;
            if (second_word.length > 0) {
                dojo.addClass('lettertycoon_second_word', 'show');
            }
            for (var i in second_word) {
                var part = second_word[i];
                this.wordStock[2].addToStockWithId(this.getLetterIndex(part.letter), part.card_id);
                this.wordInfo[2].origins[i] = part.letter_origin;
                this.wordInfo[2].types[i] = part.letter_type;
            }

            dojo.connect(this.communityStock, 'onChangeSelection', this, 'onCommunitySelectionChanged');
            dojo.connect(this.handStock, 'onChangeSelection', this, 'onHandSelectionChanged');
            dojo.connect(this.wordStock[1], 'onChangeSelection', this, 'onWord1SelectionChanged');
            dojo.connect(this.wordStock[2], 'onChangeSelection', this, 'onWord2SelectionChanged');
            dojo.connect(this.availablePatents, 'onChangeSelection', this, 'onPatentSelectionChanged');

            var score = _('Score (Money + Stock + Value of Patents)');
            var scoreHtml = '<span>'+score+'</span>';
            this.addTooltipHtmlToClass('player_score_value', scoreHtml);
            this.addTooltipHtmlToClass('fa-star', scoreHtml);
            this.addTooltipHtmlToClass('lettertycoon_player_board_coins', '<span>'+_('Money')+'</span>');
            this.addTooltipHtmlToClass('lettertycoon_player_board_stock', '<span>'+_('Stock')+'</span>');
            this.addTooltipHtmlToClass('lettertycoon_player_board_patents', '<span>'+_('Value of Patents')+'</span>');
            this.addTooltipHtmlToClass('lettertycoon_player_board_zeppelin', '<span>'+_('Start Player')+'</span>');

            this.addTooltipHtml('lettertycoon_scoring_card', this.createScoringTooltipContents());

            this.addTooltipHtml('lettertycoon_frequencies_card', this.createLetterFrequencyTooltipContents());

            this.addTooltipHtml('lettertycoon_goal_card', this.format_block('jstpl_goal_card_tooltip', {
                x_player_goal:
                    dojo.string.substitute(_('${player_count} Player Goal'), { player_count: player_count }),
                goal_text_patents_value:
                    dojo.string.substitute(_('<b>${value}</b> in patents owned by any player'), { value: this.goal.value }),
                goal_text_minimum_patents:
                    dojo.string.substitute(_('Requires a minimum of <b>${minimum}</b> patents'), { minimum: this.goal.minimum }),
            }));
            
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            // console.log('Ending game setup');
        },
        

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function (stateName, args) {
            // console.log('Entering state: '+stateName);
            this.currentState = stateName;
            
            switch (stateName) {
                case 'playerMayReplaceCard':
                    if (this.isCurrentPlayerActive()) {
                        if (this.reorderMode) {
                            this.savedSelectionMode = 1;
                        } else {
                            this.handStock.setSelectionMode(1);
                        }
                        this.communityStock.setSelectionMode(1);
                    }
                    break;

                case 'playerMayPlayWord':
                    if (this.isCurrentPlayerActive()) {
                        if (this.reorderMode) {
                            this.savedSelectionMode = 1;
                        } else {
                            this.handStock.setSelectionMode(1);
                        }
                        this.communityStock.setSelectionMode(1);
                        this.wordStock[1].setSelectionMode(1);
                        this.wordStock[2].setSelectionMode(1);
                    }
                    break;
                
                case 'playerMayBuyPatent':
                    if (this.isCurrentPlayerActive()) {
                        this.availablePatents.setSelectionMode(1);
                        this.highlightPurchasablePatents();
                    }
                    break;

                case 'playerMayDiscardCards':
                    if (this.isCurrentPlayerActive()) {
                        if (this.reorderMode) {
                            this.savedSelectionMode = 2;
                        } else {
                            this.handStock.setSelectionMode(2);
                        }
                    }
                    break;
                
                case 'playerMustDiscardCard':
                    if (this.isCurrentPlayerActive()) {
                        if (this.reorderMode) {
                            this.savedSelectionMode = 1;
                        } else {
                            this.handStock.setSelectionMode(1);
                        }
                    }
                    break;

                case 'endTurn':
                    this.wordInfo[1] = { origins: [], types: [] };
                    this.wordInfo[2] = { origins: [], types: [] };
                    this.secondWordStarted = false;
                    break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function(stateName) {
            // console.log('Leaving state: '+stateName);
            this.currentState = null;
            
            switch (stateName) {
                case 'playerMayReplaceCard':
                    if (this.isCurrentPlayerActive()) {
                        if (this.reorderMode) {
                            this.savedSelectionMode = 0;
                        } else {
                            this.handStock.setSelectionMode(0);
                        }
                        this.communityStock.setSelectionMode(0);
                    }
                    break;
                
                case 'playerMayPlayWord':
                    if (this.isCurrentPlayerActive()) {
                        if (this.reorderMode) {
                            this.savedSelectionMode = 0;
                        } else {
                            this.handStock.setSelectionMode(0);
                        }
                        this.communityStock.setSelectionMode(0);
                        this.wordStock[1].setSelectionMode(0);
                        this.wordStock[2].setSelectionMode(0);
                        this.startSecondWordButtonVisible = false;
                        this.duplicateLetterButtonVisible = false;
                        this.toggleYButtonVisible = false;
                        this.addAnSButtonVisible = false;
                    }
                    break;
                
                case 'playerMayBuyPatent':
                    if (this.isCurrentPlayerActive()) {
                        this.clearPurchasablePatentHighlights();
                        this.availablePatents.setSelectionMode(0);
                    }
                    break;

                case 'playerMayDiscardCards':
                    if (this.isCurrentPlayerActive()) {
                        if (this.reorderMode) {
                            this.savedSelectionMode = 0;
                        } else {
                            this.handStock.setSelectionMode(0);
                        }
                    }
                    break;
                
                case 'playerMustDiscardCard':
                    if (this.isCurrentPlayerActive()) {
                        if (this.reorderMode) {
                            this.savedSelectionMode = 0;
                        } else {
                            this.handStock.setSelectionMode(0);
                        }
                    }
                    break;
            }
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function (stateName, args) {
            // console.log('onUpdateActionButtons: '+stateName);
            
            if (this.isCurrentPlayerActive()) {
                switch (stateName) {
                    case 'playerMayReplaceCard':
                        this.addActionButton('lettertycoon_replaceSelectedCard_button', _('Replace selected card'), 'onReplaceSelectedCardButtonClicked', null, false, 'blue');
                        this.addActionButton('lettertycoon_skipReplaceCard_button', _('Skip replacing a card'), 'onSkipReplaceCard', null, false, 'gray');
                        this.updateReplaceSelectedCardButton();
                        break;
                    
                    case 'playerMayPlayWord':
                        this.startSecondWordButtonVisible = this.playerOwnsPatent('V');
                        this.duplicateLetterButtonVisible = this.playerOwnsPatent('X');
                        this.toggleYButtonVisible = this.handOrCommunityContainsY() && this.vowelsCanAffectPlayerScore();
                        this.addAnSButtonVisible = this.playerOwnsPatent('Z');
                        this.addActionButton('lettertycoon_playWord_button', _('Play word(s)'), 'onPlayWordButtonClicked', null, false, 'blue');
                        if (this.startSecondWordButtonVisible) {
                            this.addActionButton('lettertycoon_secondWord_button', this.getSecondWordButtonLabel(), 'onSecondWordButtonClicked', null, false, 'gray');
                        }
                        if (this.duplicateLetterButtonVisible) {
                            this.addActionButton('lettertycoon_duplicateLetter_button', _('Duplicate letter'), 'onDuplicateLetterButtonClicked', null, false, 'gray');
                        }
                        if (this.toggleYButtonVisible) {
                            this.addActionButton('lettertycoon_toggleY_button', _('Toggle ‘Y’'), 'onToggleYButtonClicked', null, false, 'gray');
                        }
                        if (this.addAnSButtonVisible) {
                            this.addActionButton('lettertycoon_addAnS_button', _('Add an ‘S’'), 'onAddAnSButtonClicked', null, false, 'gray');
                        }
                        this.addActionButton('lettertycoon_undoLastLetter_button', _('Undo last letter'), 'onUndoLastLetterButtonClicked', null, false, 'gray');
                        this.addActionButton('lettertycoon_resetWordArea_button', _('Reset word area'), 'onResetWordAreaButtonClicked', null, false, 'gray');
                        this.addActionButton('lettertycoon_skipPlayWord_button', _('Skip playing word(s)'), 'onSkipPlayWord', null, false, 'gray');
                        this.updateWordAreaButtons();
                        break;
                    
                    case 'playersMayChallenge':
                        this.addActionButton('lettertycoon_challengeWord_button', _('Challenge word'), 'onChallengeWord', null, false, 'red');
                        this.addActionButton('lettertycoon_acceptWord_button', _('Accept word'), 'onAcceptWord', null, false, 'gray');
                        break;
                    
                    case 'playerMayBuyPatent':
                        this.addActionButton('lettertycoon_buySelectedPatent_button',
                            this.getBuySelectedPatentLabel(this.availablePatents.getSelectedItems()),
                            'onBuySelectedPatentButtonClicked', null, false, 'blue');
                        this.addActionButton('lettertycoon_skipBuyPatent_button', _('Skip buying a patent'), 'onSkipBuyPatent', null, false, 'gray');
                        this.updateBuySelectedPatentButton();
                        break;
                    
                    case 'playerMayDiscardCards':
                        this.addActionButton('lettertycoon_discardSelectedCards_button', this.getDiscardSelectedCardsButtonLabel(0), 'onDiscardSelectedCardsButtonClicked', null, false, 'blue');
                        this.addActionButton('lettertycoon_skipDiscardCards_button', _('Skip discarding cards'), 'onSkipDiscardCards', null, false, 'gray');
                        this.updateDiscardSelectedCardsButton();
                        break;
                    
                    case 'playerMustDiscardCard':
                        this.addActionButton('lettertycoon_discardSelectedCard_button', _('Discard selected card'), 'onDiscardSelectedCardButtonClicked', null, false, 'blue');
                        this.updateDiscardSelectedCardButton();
                        break;
                }
            }

            switch (stateName) {
                case 'playerMayReplaceCard':
                case 'playerMayPlayWord':
                case 'playersMayChallenge':
                case 'playerMayBuyPatent':
                case 'playerMayDiscardCards':
                case 'playerMustDiscardCard':
                    this.addReorderHandButton();
                    break;
            }
            
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        getPlayerIdString: function () {
            return '' + this.player_id; // convert to string;
        },

        getLetterIndex: function (letter) {
            return letter.charCodeAt(0) - 65; // 65 = 'A'
        },

        getLetterFromIndex: function (letterIndex) {
            return String.fromCharCode(65 + letterIndex); // 65 = 'A'
        },

        getItemIds: function (items) {
            var ids = [];
            for (var i in items) {
                var item = items[i];
                ids.push(item.id);
            }
            return ids;
        },

        getLetterListFromItems: function (items) {
            var letters = [];
            for (var i in items) {
                var item = items[i];
                letters.push(this.getLetterFromIndex(item.type));
            }
            return letters;
        },

        createCardStock: function (element_id) {
            var cardStock = new ebg.stock();
            cardStock.create(this, $(element_id), this.cardWidth, this.cardHeight);
            cardStock.image_items_per_row = 13;
            for (var letter = 0, letters = 26; letter < letters; letter++) {
                cardStock.addItemType(letter, letter, g_gamethemeurl+'img/cards.jpg', letter);
            }
            cardStock.onItemCreate = dojo.hitch(this, 'createCard'); 
            cardStock.setSelectionMode(0);
            cardStock.setSelectionAppearance('class');
            return cardStock;
        },

        createCard: function (element, type, id) {
            dojo.addClass(element, 'lettertycoon_card');

            if (/205$/.test(id)) { // 205 = added S card id
                dojo.addClass(element, 'added_s');
            }
            if (/208$/.test(id)) { // 208 = duplicate card id
                dojo.addClass(element, 'duplicate');
            }

            var letter = this.getLetterFromIndex(type);
            var letter_count = this.letter_counts[letter];
            var letter_type_raw = this.letter_types[letter];
            var letter_type_display =
                letter_type_raw === 'vowel'
                    ? _('vowel')
                    : letter_type_raw === 'consonant'
                        ? _('consonant')
                        : letter_type_raw === 'consonant_or_vowel'
                            ? _('consonant/vowel')
                            : '';
            var patent_cost = this.patent_costs[letter];
            var text =
                letter === 'Q'
                    ? _('Words with ‘Q’ earn double.')
                    : letter === 'Y'
                        ? _('Player decides whether each ‘Y’ is treated as a consonant or a vowel for patent abilities.')
                        : '';

            this.addTooltipHtml(id, this.format_block('jstpl_card_tooltip', {
                letter: letter,
                letter_count: letter_count,
                letter_type: letter_type_display,
                patent_cost: patent_cost,
                text: text
            }));
        },

        createPatentStock: function (element_id) {
            var patentStock = new ebg.stock();
            patentStock.create(this, $(element_id), this.patentWidth, this.patentHeight);
            patentStock.image_items_per_row = 2;
            for (var letter = 0, letters = 26; letter < letters; letter++) {
                patentStock.addItemType(letter, letter, g_gamethemeurl+'img/patents.jpg', letter);
            }
            patentStock.onItemCreate = dojo.hitch(this, 'createPatent'); 
            patentStock.setSelectionMode(0);
            patentStock.setSelectionAppearance('class');
            return patentStock;
        },

        createPatent: function (element, type, id) {
            dojo.addClass(element, 'lettertycoon_patent');

            var letter = this.getLetterFromIndex(type);
            var patent_cost = this.patent_costs[letter];
            var patent_text = this.patent_text[letter];

            this.addTooltipHtml(id, this.format_block('jstpl_patent_tooltip', {
                letter: letter,
                cost: patent_cost,
                text: patent_text
            }));
        },

        createCounter: function (element_id, value) {
            var counter = new ebg.counter();
            counter.create(element_id);
            counter.setValue(value);
            return counter;
        },

        createScoringTooltipContents: function () {
            var html = '<div class="lettertycoon_scoring_card_tooltip_contents">'; // contents

            html += '<div class="lettertycoon_scoring_card_tooltip_header">'+_('Scoring')+'</div>';

            html += '<div class="lettertycoon_scoring_card_tooltip_columns">';

            var lengthColumn = '<div class="lettertycoon_scoring_card_tooltip_column_length">';
            var moneyColumn = '<div class="lettertycoon_scoring_card_tooltip_column_money">';
            var stockColumn = '<div class="lettertycoon_scoring_card_tooltip_column_stock">';

            lengthColumn += '<div class="lettertycoon_scoring_card_tooltip_column_header">' + _('Word') + '</div>';
            moneyColumn += '<div class="lettertycoon_scoring_card_tooltip_column_header">' + _('Money') + '</div>';
            stockColumn += '<div class="lettertycoon_scoring_card_tooltip_column_header">' + _('Stock') + '</div>';

            for (var length = 3; length <= 12; length++) {
                var score = this.scores[length];
                var money = score.money;
                var stock = score.stock;
                var lengthText = dojo.string.substitute(_('${length} letters'), { length: length} );
                lengthColumn += '<div class="lettertycoon_scoring_card_tooltip_item_length">' + lengthText + '</div>';
                moneyColumn += '<div class="lettertycoon_scoring_card_tooltip_item_money">$' + money + '</div>';
                stockColumn += '<div class="lettertycoon_scoring_card_tooltip_item_stock">' + (stock > 0 ? stock : '-') + '</div>';
            }

            lengthColumn += '</div>';
            moneyColumn += '</div>';
            stockColumn += '</div>';

            html += lengthColumn + moneyColumn + stockColumn;

            html += '</div>'; // columns

            html += '</div>'; // contents

            return html;
        },

        createLetterFrequencyTooltipContents: function () {
            var html = '<div class="lettertycoon_frequencies_card_tooltip_contents">';

            html += '<div class="lettertycoon_frequencies_card_tooltip_header">'+_('Letter Frequency')+'</div>';

            html += '<div class="lettertycoon_frequencies_card_tooltip_columns">';
            html += this.createLetterFrequencyTooltipColumnPair(0, 7);
            html += this.createLetterFrequencyTooltipColumnPair(7, 13);
            html += this.createLetterFrequencyTooltipColumnPair(13, 20);
            html += this.createLetterFrequencyTooltipColumnPair(20, 26);
            html += '</div>'; // columns

            html += '</div>'; // contents

            return html;
        },

        createLetterFrequencyTooltipColumnPair: function (start, end) {
            var left = '<div class="lettertycoon_frequencies_card_tooltip_column_left">';
            var right = '<div class="lettertycoon_frequencies_card_tooltip_column_right">';
            for (var i = start; i < end; i++) {
                var letter = this.getLetterFromIndex(i);
                var count = this.letter_counts[letter];
                left += '<div class="lettertycoon_frequencies_card_tooltip_letter">'+letter+'</div>';
                right += '<div class="lettertycoon_frequencies_card_tooltip_count">'+count+'</div>';
            }
            left += '</div>';
            right += '</div>';
            return left + right;
        },

        setClassIf: function (condition, id, cls) {
            if ($(id)) {
                if (condition) {
                    dojo.addClass(id, cls);
                } else {
                    dojo.removeClass(id, cls);
                }
            }
        },

        updateWordAreaButtons: function () {
            var mainWordItems = this.wordStock[1].getAllItems();
            var mainWordSelectedItems = this.wordStock[1].getSelectedItems();
            var secondWordItems = this.wordStock[2].getAllItems();
            var secondWordSelectedItems = this.wordStock[2].getSelectedItems();

            this.setClassIf(this.secondWordStarted || secondWordItems.length > 0, 'lettertycoon_second_word', 'show');

            this.setClassIf(
                mainWordItems.length < 3 || (this.secondWordStarted && secondWordItems.length < 3),
                'lettertycoon_playWord_button', 'disabled'
            );

            if (this.startSecondWordButtonVisible) {
                this.setClassIf(
                    mainWordItems.length < 3,
                    'lettertycoon_secondWord_button', 'disabled'
                );
                dojo.place('<span>'+this.getSecondWordButtonLabel()+'</span>',
                    'lettertycoon_secondWord_button', 'only');
            }

            if (this.duplicateLetterButtonVisible) {
                this.setClassIf(
                    this.duplicatePlayed()
                        || (mainWordSelectedItems.length === 0 && secondWordSelectedItems.length === 0)
                        || this.itemsContainsId(mainWordSelectedItems, 205) // can't duplicate generated S
                        || this.itemsContainsId(secondWordSelectedItems, 205)
                        || this.currentWordComplete(),
                    'lettertycoon_duplicateLetter_button', 'disabled'
                );
            }

            if (this.toggleYButtonVisible) {
                this.updateYTypes(1);
                this.updateYTypes(2);
                this.setClassIf(
                    !(this.itemsContainsLetter(mainWordSelectedItems, 'Y') || this.itemsContainsLetter(secondWordSelectedItems, 'Y')),
                    'lettertycoon_toggleY_button', 'disabled'
                );
            }

            if (this.addAnSButtonVisible) {
                this.setClassIf(
                    this.addAnSPlayed()
                        || mainWordItems.length < 2 || (this.secondWordStarted && secondWordItems.length < 2),
                    'lettertycoon_addAnS_button', 'disabled'
                );
            }

            this.setClassIf(
                mainWordItems.length < 1,
                'lettertycoon_undoLastLetter_button', 'disabled'
            );

            this.setClassIf(
                mainWordItems.length < 1,
                'lettertycoon_resetWordArea_button', 'disabled'
            );
        },

        updateYTypes: function (word /* 1 or 2 */) {
            var typeY = this.getLetterIndex('Y');
            var wordStock = this.wordStock[word];
            var wordInfo = this.wordInfo[word];
            var items = wordStock.getAllItems();
            for (var i = 0, l = items.length; i < l; i++) {
                var item = items[i];
                if (item.type === typeY) {
                    var elementId = wordStock.getItemDivId(item.id);
                    var letterType = wordInfo.types[i];
                    this.setClassIf(letterType === 'c', elementId, 'consonant');
                    this.setClassIf(letterType === 'v', elementId, 'vowel');
                }
            }
        },

        handOrCommunityContainsY: function () {
            return this.itemsContainsLetter(this.handStock.getAllItems(), 'Y')
                || this.itemsContainsLetter(this.communityStock.getAllItems(), 'Y');
        },

        playerOwnsPatent: function (letter) {
            return this.patentOwners[letter] === this.getPlayerIdString();
        },

        updateReplaceSelectedCardButton: function () {
            var selectedHandItems = this.handStock.getSelectedItems();
            var selectedCommunityItems = this.communityStock.getSelectedItems();
            this.setClassIf(
                selectedHandItems.length === 0 && selectedCommunityItems.length === 0,
                'lettertycoon_replaceSelectedCard_button', 'disabled');
        },

        updateBuySelectedPatentButton: function () {
            var selectedPatents = this.availablePatents.getSelectedItems();
            dojo.place('<span>'+this.getBuySelectedPatentLabel(selectedPatents)+'</span>',
                'lettertycoon_buySelectedPatent_button', 'only');
            this.setClassIf(
                selectedPatents.length === 0,
                'lettertycoon_buySelectedPatent_button', 'disabled');
        },

        updateDiscardSelectedCardsButton: function () {
            var selectedItems = this.handStock.getSelectedItems();
            dojo.place('<span>'+this.getDiscardSelectedCardsButtonLabel(selectedItems.length)+'</span>',
                'lettertycoon_discardSelectedCards_button', 'only');
            this.setClassIf(selectedItems.length === 0, 'lettertycoon_discardSelectedCards_button', 'disabled');
        },

        updateDiscardSelectedCardButton: function () {
            var selectedHandItems = this.handStock.getSelectedItems();
            this.setClassIf(
                selectedHandItems.length === 0,
                'lettertycoon_discardSelectedCard_button', 'disabled');
        },

        addReorderHandButton: function () {
            if (!this.isSpectator) {
                this.addActionButton('lettertycoon_reorderHand_button', this.getReorderHandButtonLabel(), 'onReorderHandButtonClicked', null, false, 'gray');
            }
        },

        updateReorderHandButton: function () {
            dojo.place('<span>'+this.getReorderHandButtonLabel()+'</span>', 'lettertycoon_reorderHand_button', 'only');
        },

        setPlayerAreaMessageReorderSelect: function () {
            this.setPlayerAreaMessage(_('Select a card to move'));
        },

        setPlayerAreaMessageReorderMove: function () {
            this.setPlayerAreaMessage(_('Click another card to move selected card to its location'));
        },

        setPlayerAreaMessage: function (message) {
            dojo.place('<span>'+message+'</span>', 'lettertycoon_player_area_message', 'only');
        },

        getBuySelectedPatentLabel: function (selectedPatents) {
            if (selectedPatents.length === 1) {
                var selectedPatent = selectedPatents[0];
                return dojo.string.substitute(_('Buy selected patent (${letter})'), {
                    letter: this.getLetterFromIndex(selectedPatent.type)
                });
            } else {
                return _('Buy selected patent');
            }
        },

        getSecondWordButtonLabel: function () {
            if (this.secondWordStarted) {
                return _('Reset second word');
            } else {
                return _('Start second word');
            }
        },

        getDiscardSelectedCardsButtonLabel: function (numSelectedCards) {
            if (numSelectedCards > 0) {
                return dojo.string.substitute(_('Discard selected cards (${n})'), { n: numSelectedCards });
            } else {
                return _('Discard selected cards');
            }
        },

        getReorderHandButtonLabel: function () {
            return this.reorderMode ? _('Stop reordering hand') : _('Reorder hand');
        },

        discardSelectedCard: function (fromStock) {
            var items = fromStock.getSelectedItems();
            if (items.length === 1) {
                var item = items[0];
                this.action_discardCard(item.id);
            }
        },

        replaceSelectedCard: function () {
            var handItems = this.handStock.getSelectedItems();
            if (handItems.length === 1) {
                var handItem = handItems[0];
                this.action_replaceCard(handItem.id);
            } else {
                var communityItems = this.communityStock.getSelectedItems();
                if (communityItems.length === 1) {
                    var communityItem = communityItems[0];
                    this.action_replaceCard(communityItem.id);
                }
            }
        },

        playSelectedCard: function (fromStock, origin, word /* 1 or 2 */) {
            if (this.currentWordComplete()) {
                return;
            }
            var wordStock = this.wordStock[word];
            var wordInfo = this.wordInfo[word];
            var items = fromStock.getSelectedItems();
            if (items.length === 1) {
                var item = items[0];
                var elementId = fromStock.getItemDivId(item.id);
                wordStock.addToStockWithId(item.type, item.id, $(elementId));
                fromStock.removeFromStockById(item.id);
                if (fromStock === this.handStock) {
                    this.removeFromHandOrderList(item.id);
                    this.updateHandOrderMap();
                }
                wordInfo.origins.push(origin);
                wordInfo.types.push(item.type === 24 ? 'v' : '_'); // Y defaults to vowel
                this.updateWordAreaButtons();
            }
        },

        duplicateLetter: function (fromStock, word /* 1 or 2 */) {
            if (this.currentWordComplete()) {
                return;
            }
            var wordStock = this.wordStock[word];
            var wordInfo = this.wordInfo[word];
            var items = fromStock.getSelectedItems();
            if (items.length === 1) {
                var item = items[0];
                var elementId = fromStock.getItemDivId(item.id);
                wordStock.addToStockWithId(item.type, this.duplicateCardId, $(elementId));
                wordInfo.origins.push('d');
                wordInfo.types.push(item.type === 24 ? 'v' : '_'); // Y defaults to vowel
                this.updateWordAreaButtons();
            }
        },

        addAnS: function (word /* 1 or 2 */) {
            if (this.currentWordComplete()) {
                return;
            }
            var wordStock = this.wordStock[word];
            var wordInfo = this.wordInfo[word];
            wordStock.addToStockWithId(this.getLetterIndex('S'), this.addedSCardId);
            wordInfo.origins.push('s');
            wordInfo.types.push('_');
            this.updateWordAreaButtons();
        },

        toggleSelectedY: function (word /* 1 or 2 */) {
            var typeY = this.getLetterIndex('Y');
            var wordStock = this.wordStock[word];
            var wordInfo = this.wordInfo[word];
            var items = wordStock.getAllItems();
            for (var i = 0, l = items.length; i < l; i++) {
                var item = items[i];
                if (wordStock.isSelected(item.id) && item.type === typeY) {
                    var currentType = wordInfo.types[i];
                    if (currentType === 'c') {
                        wordInfo.types[i] = 'v';
                    }
                    if (currentType === 'v') {
                        wordInfo.types[i] = 'c';
                    }
                }
            }
            this.updateWordAreaButtons();
        }, 

        wordContainsDuplicate: function (word /* 1 or 2 */) {
            var items = this.wordStock[word].getAllItems();
            for (var i = 0, l = items.length; i < l; i++) {
                var item = items[i];
                if (item.id === this.duplicateCardId) {
                    return true;
                }
            }
            return false;
        },

        wordHasAddedS: function (word /* 1 or 2 */) {
            var origins = this.wordInfo[word].origins;
            return origins[origins.length - 1] === 's';
        },

        itemsContainsLetter: function (items, letter) {
            var type = this.getLetterIndex(letter);
            for (var i = 0, l = items.length; i < l; i++) {
                var item = items[i];
                if (item.type === type) {
                    return true;
                }
            }
            return false;
        },

        itemsContainsId: function (items, id) {
            for (var i = 0, l = items.length; i < l; i++) {
                var item = items[i];
                if (item.id == id) {
                    return true;
                }
            }
            return false;
        },

        getItemsWithLetter: function (items, letter) {
            var result = [];
            var type = this.getLetterIndex(letter);
            for (var i = 0, l = items.length; i < l; i++) {
                var item = items[i];
                if (item.type === type) {
                    result.push(item);
                }
            }
            return result;
        },

        currentWordComplete: function () {
            return (!this.secondWordStarted && this.wordHasAddedS(1))
                 || (this.secondWordStarted && this.wordHasAddedS(2));
        },

        duplicatePlayed: function () {
            return this.wordContainsDuplicate(1) || this.wordContainsDuplicate(2);
        },

        addAnSPlayed: function () {
            return this.wordHasAddedS(1) || this.wordHasAddedS(2);
        },

        vowelsCanAffectPlayerScore: function () {
            return this.playerOwnsPatent('B') || this.playerOwnsPatent('J') || this.playerOwnsPatent('K');
        },

        unselectAllItems: function (stock) {
            var selectedItems = stock.getSelectedItems();
            for (var i = 0, l = selectedItems.length; i < l; i++) {
                stock.unselectItem(selectedItems[i].id);
            }
        },

        playWordFromPlayer: function (player_id, word /* 1 or 2 */, word_args) {
            var wordStock = this.wordStock[word];
            var wordInfo = this.wordInfo[word];
  
            var letters = word_args.letters;
            var letter_origins = word_args.letter_origins;
            var letter_types = word_args.letter_types;
            var card_ids = word_args.card_ids;

            for (var i in card_ids) {
                var card_id = card_ids[i];
                var letter = letters[i];
                var letter_origin = letter_origins[i];
                var elementId = undefined;
                if (letter_origin === 'c') {
                    elementId = this.communityStock.getItemDivId(card_id);
                }
                if (letter_origin === 'h') {
                    if (player_id === this.getPlayerIdString()) {
                        elementId = this.handStock.getItemDivId(card_id);
                    } else {
                        elementId = $('overall_player_board_'+player_id);
                    }
                }
                wordStock.addToStockWithId(this.getLetterIndex(letter), card_id, elementId);
                if (letter_origin === 'c') {
                    this.communityStock.removeFromStockById(card_id);
                }
                if (letter_origin === 'h') {
                    if (player_id === this.getPlayerIdString()) {
                        this.handStock.removeFromStockById(card_id);
                        this.removeFromHandOrderList(card_id);
                    }
                }
            }
            this.updateHandOrderMap();

            wordInfo.origins = letter_origins.split('');
            wordInfo.types = letter_types.split('');
        },

        maybeUndoLastLetter: function (word /* 1 or 2 */) {
            var wordStock = this.wordStock[word];
            var wordInfo = this.wordInfo[word];
            var wordItems = wordStock.getAllItems();
            if (wordItems.length > 0) {
                var index = wordItems.length - 1;
                var item = wordItems[index];
                var elementId = wordStock.getItemDivId(item.id);
                var origin = wordInfo.origins[index];
                if (origin === 'c') {
                    this.communityStock.addToStockWithId(item.type, item.id, $(elementId));
                } else if (origin === 'h') {
                    this.handStock.addToStockWithId(item.type, item.id, $(elementId));
                    this.handOrderList.push(item.id);
                    this.updateHandOrderMap();
                }
                wordStock.removeFromStockById(item.id);
                wordInfo.origins.pop();
                wordInfo.types.pop();
                this.updateWordAreaButtons();
                return true;
            }
            return false;
        },

        undoLastLetter: function () {
            if (this.secondWordStarted) {
                if (!this.maybeUndoLastLetter(2)) {
                    this.secondWordStarted = false;
                    this.maybeUndoLastLetter(1);
                }
            } else {
                this.maybeUndoLastLetter(1);
            }
        },

        clearWord: function (word /* 1 or 2 */, active_player_id) {
            var wordStock = this.wordStock[word];
            var wordInfo = this.wordInfo[word];
            var wordItems = wordStock.getAllItems();
            for (var i in wordItems) {
                var item = wordItems[i];
                var elementId = wordStock.getItemDivId(item.id);
                if (wordInfo.origins[i] === 'c') {
                    this.communityStock.addToStockWithId(item.type, item.id, $(elementId));
                } else if (wordInfo.origins[i] === 'h' && this.getPlayerIdString() === active_player_id) {
                    this.handStock.addToStockWithId(item.type, item.id, $(elementId));
                    this.handOrderList.push(item.id);
                }
            }
            this.updateHandOrderMap();
            wordStock.removeAll();
            wordInfo.origins = [];
            wordInfo.types = [];
        },

        clearWordArea: function (active_player_id) {
            this.clearWord(1, active_player_id);
            this.clearWord(2, active_player_id);
            this.secondWordStarted = false;
            this.updateWordAreaButtons();
        },

        markPurchasableLetters: function (word /* 1 or 2 */, purchasable) {
            var playerMoney = this.playerMoney[this.getActivePlayerId()].getValue();
            var wordStock = this.wordStock[word];
            var wordInfo = this.wordInfo[word];
            var items = wordStock.getAllItems();
            for (var i in items) {
                var item = items[i];
                var letter = this.getLetterFromIndex(item.type);
                if (!this.patentOwners[letter]) {
                    var origin = wordInfo.origins[i];
                    if (origin === 'c' || origin === 'h') {
                        if (this.patent_costs[letter] <= playerMoney) {
                            purchasable[letter] = true;
                        }
                    }
                }
            }
        },

        highlightPurchasablePatents: function () {
            var purchasable = {};
            this.markPurchasableLetters(1, purchasable);
            this.markPurchasableLetters(2, purchasable);

            var items = this.availablePatents.getAllItems();
            for (var i in items) {
                var item = items[i];
                var elementId = this.availablePatents.getItemDivId(item.id);
                var letter = this.getLetterFromIndex(item.type);
                if (!purchasable[letter]) {
                    dojo.addClass(elementId, 'unpurchasable');
                }
            }
        },

        clearPurchasablePatentHighlights: function () {
            var items = this.availablePatents.getAllItems();
            for (var i in items) {
                var item = items[i];
                var elementId = this.availablePatents.getItemDivId(item.id);
                dojo.removeClass(elementId, 'unpurchasable');
            }
        },

        buySelectedPatent: function () {
            var items = this.availablePatents.getSelectedItems();
            if (items.length === 1) {
                var item = items[0];
                this.action_buyPatent(item.type);
            }
        },

        removeFromHandOrderList: function (cardId) {
            var newHandOrderList = [];
            for (var i = 0, l = this.handOrderList.length; i < l; i++) {
                var id = this.handOrderList[i];
                if (id != cardId) {
                    newHandOrderList.push(id);
                }
            }
            this.handOrderList = newHandOrderList;
        },

        updateHandOrderMap: function () {
            this.handOrderMap = {};
            for (var i = 0, l = this.handOrderList.length; i < l; i++) {
                var id = this.handOrderList[i];
                this.handOrderMap[id] = i;
            }
        },

        sendAction: function (action, args) {
            var params = {};
            if (args) {
                for (var key in args) {
                    params[key] = args[key];
                }
            }
            params.lock = true;
            this.ajaxcall('/lettertycoon/lettertycoon/'+action+'.html', params, this, function (result) { });
        },

        toNumberList: function (ids) {
            return ids.join(';');
        },

        getWordParamsForPlayWord: function (word /* 1 or 2 */) {
            var wordStock = this.wordStock[word];
            var wordInfo = this.wordInfo[word];
            var items = wordStock.getAllItems();
            if (items.length > 0) {
                return {
                    letters: this.getLetterListFromItems(items),
                    letterOrigins: wordInfo.origins,
                    letterTypes: wordInfo.types,
                    cardIds: this.getItemIds(items),
                };
            } else {
                return null;
            }
        },

        action_playWord: function (mainWordParams, secondWordParams) {
            var args = {
                main_word_letters: mainWordParams.letters.join(''),
                main_word_letter_origins: mainWordParams.letterOrigins.join(''),
                main_word_letter_types: mainWordParams.letterTypes.join(''),
                main_word_card_ids: this.toNumberList(mainWordParams.cardIds)
            };
            if (secondWordParams) {
                args.second_word_letters = secondWordParams.letters.join(''),
                args.second_word_letter_origins = secondWordParams.letterOrigins.join(''),
                args.second_word_letter_types = secondWordParams.letterTypes.join(''),
                args.second_word_card_ids = this.toNumberList(secondWordParams.cardIds)
            }
            this.sendAction('playWord', args);
        },

        action_discardCard: function (cardId) {
            this.sendAction('discardCard', {
                card_id: cardId
            });
        },

        action_replaceCard: function (cardId) {
            this.sendAction('replaceCard', {
                card_id: cardId
            });
        },

        action_skipReplaceCard: function () {
            this.sendAction('skipReplaceCard');
        },

        action_skipPlayWord: function () {
            this.sendAction('skipPlayWord');
        },

        action_challengeWord: function () {
            this.sendAction('challengeWord');
        },

        action_acceptWord: function () {
            this.sendAction('acceptWord');
        },

        action_buyPatent: function (letterIndex) {
            this.sendAction('buyPatent', {
                letter_index: letterIndex
            });
        },

        action_skipBuyPatent: function () {
            this.sendAction('skipBuyPatent');
        },

        action_discardCards: function (cardIds) {
            this.sendAction('discardCards', {
                card_ids: this.toNumberList(cardIds)
            });
        },

        action_skipDiscardCards: function () {
            this.sendAction('skipDiscardCards');
        },

        // HAND ORDER: If we were saving hand orer on the backend, we might use an action like this:
        // action_setHandOrder: function (cardIds) {
        //     this.sendAction('setHandOrder', {
        //         card_ids: this.toNumberList(cardIds)
        //     });
        // },

        ///////////////////////////////////////////////////
        //// Player's action

        onSkipReplaceCard: function (evt) {
            // console.log('skip replace card');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.action_skipReplaceCard();
        },
        
        onSkipPlayWord: function (evt) {
            // console.log('skip play word');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.confirmationDialog(
                _('If you skip playing word(s), your only action this turn will be to discard and redraw cards.'),
                dojo.hitch(this, function () {
                    this.clearWordArea(this.getPlayerIdString());
                    this.action_skipPlayWord();
                })
            );
        },

        onChallengeWord: function (evt) {
            // console.log('challenge word');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.action_challengeWord();
        },

        onAcceptWord: function (evt) {
            // console.log('accept word');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.action_acceptWord();
        },

        onSkipBuyPatent: function (evt) {
            // console.log('skip buy patent');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.action_skipBuyPatent();
        },

        onSkipDiscardCards: function (evt) {
            // console.log('skip discard cards');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.action_skipDiscardCards();
        },

        onCommunitySelectionChanged: function () {
            // console.log('community selection changed');

            switch (this.currentState) {

                case 'playerMayReplaceCard':
                    this.unselectAllItems(this.handStock);
                    this.updateReplaceSelectedCardButton();
                    
                    break;

                case 'playerMayPlayWord':
                    this.playSelectedCard(this.communityStock, 'c', this.secondWordStarted ? 2 : 1);
                    break;

            }
        },

        onHandSelectionChanged: function () {
            // console.log('hand selection changed');

            if (this.reorderMode) {
                var selectedItems = this.handStock.getSelectedItems();
                if (selectedItems.length === 1) {
                    this.reorderSourceItem = selectedItems[0];
                    this.setPlayerAreaMessageReorderMove();
                } else if (selectedItems.length === 2) {
                    var source = this.reorderSourceItem;
                    var item0 = selectedItems[0];
                    var item1 = selectedItems[1];
                    var target = source === item0 ? item1 : item0;
                    this.unselectAllItems(this.handStock);

                    var sourceIndex = this.handOrderMap[source.id];
                    var targetIndex = this.handOrderMap[target.id];

                    if (false) { // swap
                        this.handOrderList[sourceIndex] = target.id;
                        this.handOrderList[targetIndex] = source.id;
                    } else { // insert/cycle
                        if (sourceIndex < targetIndex) {
                            // add source id after target
                            this.handOrderList.splice(targetIndex + 1, 0, source.id);
                            // remove source
                            this.handOrderList.splice(sourceIndex, 1);
                        } else {
                            // remove source
                            this.handOrderList.splice(sourceIndex, 1);
                            // add source id before target
                            this.handOrderList.splice(targetIndex, 0, source.id);
                        }
                    }
                    this.updateHandOrderMap();
                    this.handStock.changeItemsWeight({});

                    this.setPlayerAreaMessageReorderSelect();

                    // HAND ORDER: If we were saving hand order on the backend, we'd might send an update here.
                    // this.action_setHandOrder(this.handOrderList);
                }
            } else {

                switch (this.currentState) {

                    case 'playerMayReplaceCard':
                        this.unselectAllItems(this.communityStock);
                        this.updateReplaceSelectedCardButton();
                        break;

                    case 'playerMayPlayWord':
                        this.playSelectedCard(this.handStock, 'h', this.secondWordStarted ? 2 : 1);
                        break;

                    case 'playerMayDiscardCards':
                        this.updateDiscardSelectedCardsButton();
                        break;
                    
                    case 'playerMustDiscardCard':
                        this.updateDiscardSelectedCardButton();
                        break;
                }

            }
        },

        onWord1SelectionChanged: function () {
            // console.log('word 1 selection changed');

            switch (this.currentState) {

                case 'playerMayPlayWord':
                    this.unselectAllItems(this.wordStock[2]);
                    this.updateWordAreaButtons();
                    break;

            }
        },

        onWord2SelectionChanged: function () {
            // console.log('word 2 selection changed');

            switch (this.currentState) {

                case 'playerMayPlayWord':
                    this.unselectAllItems(this.wordStock[1]);
                    this.updateWordAreaButtons();
                    break;

            }
        },

        onPatentSelectionChanged: function () {
            // console.log('patent selection changed');

            switch (this.currentState) {

                case 'playerMayBuyPatent':
                    this.updateBuySelectedPatentButton();
                    break;

            }
        },

        onReplaceSelectedCardButtonClicked: function (evt) {
            // console.log('discard selected card button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.replaceSelectedCard();
        },

        onPlayWordButtonClicked: function (evt) {
            // console.log('play word button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            var mainWordParams = this.getWordParamsForPlayWord(1);
            var secondWordParams = this.getWordParamsForPlayWord(2);

            this.action_playWord(mainWordParams, secondWordParams);
        },

        onToggleYButtonClicked: function (evt) {
            // console.log('toggle Y button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            var mainWordSelectedItems = this.wordStock[1].getSelectedItems();
            if (mainWordSelectedItems.length > 0) {
                this.toggleSelectedY(1);
            } else {
                var secondWordSelectedItems = this.wordStock[2].getSelectedItems();
                if (secondWordSelectedItems.length > 0) {
                    this.toggleSelectedY(2);
                }
            }
        },

        onSecondWordButtonClicked: function (evt) {
            // console.log('start second word button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            if (this.secondWordStarted) {
                this.clearWord(2, this.getPlayerIdString());
                this.secondWordStarted = false;
                this.updateWordAreaButtons();
            } else {
                this.secondWordStarted = true;
                this.updateWordAreaButtons();
            }
        },

        onDuplicateLetterButtonClicked: function (evt) {
            // console.log('duplicate letter button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            var secondWordLetterSelected = this.secondWordStarted && this.wordStock[2].getSelectedItems().length > 0;
            var wordStock = this.wordStock[secondWordLetterSelected ? 2 : 1];
            this.duplicateLetter(wordStock, this.secondWordStarted ? 2 : 1);
        },

        onAddAnSButtonClicked: function (evt) {
            // console.log('add an S button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.addAnS(this.secondWordStarted ? 2 : 1);
        },

        onUndoLastLetterButtonClicked: function (evt) {
            // console.log('undo last letter button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.undoLastLetter();
        },

        onResetWordAreaButtonClicked: function (evt) {
            // console.log('reset word area button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.clearWordArea(this.getPlayerIdString());
        },

        onBuySelectedPatentButtonClicked: function (evt) {
            // console.log('buy selected patent button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.buySelectedPatent();
        },

        onDiscardSelectedCardsButtonClicked: function (evt) {
            // console.log('discard selected cards button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            var items = this.handStock.getSelectedItems();

            var ids = this.getItemIds(items);

            this.action_discardCards(ids);
        },

        onDiscardSelectedCardButtonClicked: function (evt) {
            // console.log('discard selected card button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.discardSelectedCard(this.handStock);
        },

        onReorderHandButtonClicked: function () {
            this.reorderMode = !this.reorderMode;
            if (this.reorderMode) {
                this.unselectAllItems(this.handStock);
                this.savedSelectionMode = this.handStock.selectable;
                this.handStock.setSelectionMode(2);
                this.setPlayerAreaMessageReorderSelect();
            } else {
                this.unselectAllItems(this.handStock);
                this.reorderSourceItem = null;
                this.handStock.setSelectionMode(this.savedSelectionMode);
                this.setPlayerAreaMessage('');
            }
            if (this.isCurrentPlayerActive()) {
                switch (this.currentState) {
                    case 'playerMayReplaceCard':
                        this.updateReplaceSelectedCardButton();
                        break;
                    case 'playerMayDiscardCards':
                        this.updateDiscardSelectedCardsButton();
                        break;
                    case 'playerMustDiscardCard':
                        this.updateDiscardSelectedCardButton();
                        break;
                }
            }
            this.updateReorderHandButton();
        },
        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your lettertycoon.game.php file.
        
        */
        setupNotifications: function () {
            // console.log('notifications subscriptions setup');

            dojo.subscribe('playerReplacedCardFromCommunity', this, 'notif_playerReplacedCardFromCommunity');
            this.notifqueue.setSynchronous('playerReplacedCardFromCommunity', 1000);
            dojo.subscribe('activePlayerReplacedCardFromHand', this, 'notif_activePlayerReplacedCardFromHand');
            this.notifqueue.setSynchronous('activePlayerReplacedCardFromHand', 1000);

            dojo.subscribe('playerPlayedWord', this, 'notif_playerPlayedWord');
            this.notifqueue.setSynchronous('playerPlayedWord', 1000);

            this.notifqueue.setSynchronous('playerChallenged', 1000);
            this.notifqueue.setSynchronous('playerChallengeSucceeded', 1000);

            dojo.subscribe('playerMustDiscard', this, 'notif_playerMustDiscard');

            this.notifqueue.setSynchronous('playerChallengeFailed', 1000);

            dojo.subscribe('challengerPaidPenalty', this, 'notif_challengerPaidPenalty');
            dojo.subscribe('playerReceivedPayment', this, 'notif_playerReceivedPayment');

            dojo.subscribe('automaticChallengeRejectedWord', this, 'notif_automaticChallengeRejectedWord');
            this.notifqueue.setSynchronous('automaticChallengeRejectedWord', 1000);

            dojo.subscribe('playerReceivedMoneyAndStock', this, 'notif_playerReceivedMoneyAndStock');
            this.notifqueue.setSynchronous('playerReceivedMoneyAndStock', 1000);

            dojo.subscribe('playerReceivedRoyalties', this, 'notif_playerReceivedRoyalties');

            dojo.subscribe('playerBoughtPatent', this, 'notif_playerBoughtPatent');

            dojo.subscribe('communityReceivedCards', this, 'notif_communityReceivedCards');
            this.notifqueue.setSynchronous('communityReceivedCards', 1000);

            dojo.subscribe('wordDiscarded', this, 'notif_wordDiscarded');

            dojo.subscribe('activePlayerDiscardedCards', this, 'notif_activePlayerDiscardedCards');
            this.notifqueue.setSynchronous('activePlayerDiscardedCards', 1000);
            
            dojo.subscribe('activePlayerReceivedCards', this, 'notif_activePlayerReceivedCards');
        },

        notif_playerReplacedCardFromCommunity: function (notif) {
            // console.log('player replaced card from community');
            // console.log(notif);
            var card_id = notif.args.card_id;
            this.communityStock.removeFromStockById(card_id);
        },

        notif_activePlayerReplacedCardFromHand: function (notif) {
            // console.log('active player replaced card from hand');
            // console.log(notif);
            var card_id = notif.args.card_id;
            this.handStock.removeFromStockById(card_id);
            this.removeFromHandOrderList(card_id);
            this.updateHandOrderMap();
        },

        notif_playerPlayedWord: function (notif) {
            // console.log('player played word');
            // console.log(notif);
            var player_id = notif.args.player_id;
            if (this.wordStock[1].count() === 0) {
                var main_word_args = notif.args.main_word;
                var second_word_args = notif.args.second_word;
                this.playWordFromPlayer(player_id, 1, main_word_args);
                if (second_word_args) {
                    dojo.addClass('lettertycoon_second_word', 'show');
                    this.playWordFromPlayer(player_id, 2, second_word_args);
                }
            }
        },

        notif_playerMustDiscard: function (notif) {
            // console.log('player must discard');
            // console.log(notif);
            var player_id = notif.args.player_id;
            this.clearWordArea(player_id);
        },

        notif_challengerPaidPenalty: function (notif) {
            // console.log('challenger paid penalty');
            // console.log(notif);
            var player_id = notif.args.player_id;
            this.playerMoney[player_id].incValue(-1);
            this.scoreCtrl[player_id].incValue(-1);
        },

        notif_playerReceivedPayment: function (notif) {
            // console.log('player received payment');
            // console.log(notif);
            var player_id = notif.args.player_id;
            this.playerMoney[player_id].incValue(1);
            this.scoreCtrl[player_id].incValue(1);
        },

        notif_automaticChallengeRejectedWord: function (notif) {
            // console.log('automatic challenge rejected word');
            // console.log(notif);
            var player_id = notif.args.player_id;
            this.clearWordArea(player_id);
        },

        notif_playerReceivedMoneyAndStock: function (notif) {
            // console.log('player received money and stock');
            // console.log(notif);
            var player_id = notif.args.player_id;
            var money = notif.args.money;
            var stock = notif.args.stock;
            this.playerMoney[player_id].incValue(money);
            this.playerStock[player_id].incValue(stock);
            this.scoreCtrl[player_id].incValue(money + stock);
        },

        notif_playerReceivedRoyalties: function (notif) {
            // console.log('player received royalties');
            // console.log(notif);
            var player_id = notif.args.player_id;
            var royalties = notif.args.royalties;
            this.playerMoney[player_id].incValue(royalties);
            this.scoreCtrl[player_id].incValue(royalties);
        },

        notif_playerBoughtPatent: function (notif) {
            // console.log('player bought patent');
            // console.log(notif);
            var player_id = notif.args.player_id;
            var letter = notif.args.letter;
            var cost = notif.args.cost;
            var letterIndex = this.getLetterIndex(letter);
            // move patent to player area from available patents
            this.patentStocksByPlayer[player_id].addToStockWithId(letterIndex, letterIndex,
                this.availablePatents.getItemDivId(letterIndex));
            this.availablePatents.removeFromStockById(letterIndex);
            // update counters
            this.playerMoney[player_id].incValue(-cost);
            this.playerPatentsValue[player_id].incValue(cost);
            // update patent owners
            this.patentOwners[letter] = player_id;
        },

        notif_communityReceivedCards: function (notif) {
            // console.log('community received cards');
            // console.log(notif);
            var new_cards = notif.args.new_cards;
            for (var i in new_cards) {
                var new_card = new_cards[i];
                this.communityStock.addToStockWithId(this.getLetterIndex(new_card.type), new_card.id,
                    $('lettertycoon_community_pool_area_header'));
            }
        },

        notif_wordDiscarded: function (notif) {
            // console.log('word discarded');
            // console.log(notif);
            this.wordStock[1].removeAll();
            this.wordStock[2].removeAll();
            dojo.removeClass('lettertycoon_second_word', 'show');
        },

        notif_activePlayerDiscardedCards: function (notif) {
            // console.log('active player discarded cards');
            // console.log(notif);
            var card_ids = notif.args.card_ids;
            for (var i in card_ids) {
                var card_id = card_ids[i];
                this.handStock.removeFromStockById(card_id, undefined, true);
                this.removeFromHandOrderList(card_id);
            }
            this.updateHandOrderMap();
            this.handStock.updateDisplay();
        },

        notif_activePlayerReceivedCards: function (notif) {
            // console.log('active player received cards');
            // console.log(notif);
            var new_cards = notif.args.new_cards;
            for (var i in new_cards) {
                var new_card = new_cards[i];
                this.handStock.addToStockWithId(this.getLetterIndex(new_card.type), new_card.id,
                    $('lettertycoon_current_player_hand_area_header'));
                this.handOrderList.push(new_card.id);
            }
            this.updateHandOrderMap();
        }

   });             
});
