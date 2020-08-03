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
            console.log('lettertycoon constructor');

            // constants
            this.cardWidth = 120;
            this.cardHeight = 165;
            this.patentWidth = 120;
            this.patentHeight = 79;

            // card stocks
            this.communityStock = null;
            this.handStock = null;
            this.mainWordStock = null;
            this.extraWordStock = null;

            // patent stocks
            this.availablePatents = null;
            this.patentStocksByPlayer = {}; // key: player_id

            // per-player counters
            this.playerMoney = {}; // key: player_id
            this.playerStock = {}; // key: player_id
            this.playerPatentsValue = {}; // key: player_id

            // ui state
            this.mainWordOrigins = []; // values: 'c' = community, 'd' = duplicated, 'h' = hand, 's' = appended S
            this.mainWordTypes = []; // values: 'c' = consonant, 'v' = vowel, '_' = as defined
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
            console.log('Starting game setup');

            this.communityStock = this.createCardStock('community_pool');
            this.handStock = this.createCardStock('current_player_hand');
            this.mainWordStock = this.createCardStock('main_word');
            this.mainWordStock.order_items = false;
            // TODO: create this when needed?
            // this.extraWordStock = this.createCardStock('extra_word');
            
            this.availablePatents = this.createPatentStock('available_patents');

            var players = gamedatas.players;
            for (var player_id in players) {
                var player = players[player_id];
                dojo.place(
                    this.format_block('jstpl_player_board_info', { player_id: player_id }),
                    $('player_board_'+player_id)
                );
                this.playerMoney[player_id] =
                    this.createCounter('player_board_coins_counter_'+player_id, player.money);
                this.playerStock[player_id] =
                    this.createCounter('player_board_stock_counter_'+player_id, player.stock);
                this.playerPatentsValue[player_id] =
                    this.createCounter('player_board_patents_counter_'+player_id, player.patents_value);
                if (player.order !== '1') {
                    dojo.style($('player_board_zeppelin_'+player_id), 'display', 'none');
                }
                this.patentStocksByPlayer[player_id] = this.createPatentStock('player_patents_'+player_id);
            }

            var patent_owners = gamedatas.patent_owners;
            for (var letter in patent_owners) {
                var letter_index = this.getLetterIndex(letter);
                var owner = patent_owners[letter];
                if (owner) {
                    this.patentStocksByPlayer[owner].addToStock(letter_index);
                } else {
                    this.availablePatents.addToStock(letter_index);
                }
            }

            var community = gamedatas.community;
            for (var card_id in community) {
                var card = community[card_id];
                this.communityStock.addToStockWithId(this.getLetterIndex(card.type), card.id);
            }

            var hand = gamedatas.hand;
            for (var card_id in hand) {
                var card = hand[card_id];
                this.handStock.addToStockWithId(this.getLetterIndex(card.type), card.id);
            }

            var main_word = gamedatas.main_word;
            for (var i in main_word) {
                var part = main_word[i];
                this.mainWordStock.addToStockWithId(this.getLetterIndex(part.letter), part.card_id);
            }

            dojo.connect(this.communityStock, 'onChangeSelection', this, 'onCommunitySelectionChanged');
            dojo.connect(this.handStock, 'onChangeSelection', this, 'onHandSelectionChanged');
            dojo.connect($('play_word_button'), 'onclick', this, 'onPlayWordButtonClicked');
            dojo.connect($('clear_button'), 'onclick', this, 'onClearButtonClicked');
            dojo.connect($('discard_button'), 'onclick', this, 'onDiscardButtonClicked');

            var score = _('Score (Money + Stock + Value of Patents)');
            var scoreHtml = '<span>'+score+'</span>';
            this.addTooltipHtmlToClass('player_score_value', scoreHtml);
            this.addTooltipHtmlToClass('fa-star', scoreHtml);
            this.addTooltipHtmlToClass('player_board_coins', '<span>'+_('Money')+'</span>');
            this.addTooltipHtmlToClass('player_board_stock', '<span>'+_('Stock')+'</span>');
            this.addTooltipHtmlToClass('player_board_patents', '<span>'+_('Value of Patents')+'</span>');
            this.addTooltipHtmlToClass('player_board_zeppelin', '<span>'+_('Start Player')+'</span>');
            
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log('Ending game setup');
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function (stateName, args) {
            console.log('Entering state: '+stateName);
            this.currentState = stateName;
            
            switch (stateName) {
                case 'playerMayPlayWord':
                    if (this.isCurrentPlayerActive()) {
                        this.handStock.setSelectionMode(1);
                        this.communityStock.setSelectionMode(1);
                        this.updateWordAreaButtons();
                    }
                    break;

                case 'playerMayDiscardCards':
                    if (this.isCurrentPlayerActive()) {
                        this.handStock.setSelectionMode(2);
                        this.updateDiscardButton();
                        dojo.addClass('discard_button', 'show');
                    }
                    break;

                case 'endTurn':
                    this.mainWordOrigins = [];
                    this.mainWordTypes = [];
                    break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName ) {
            console.log('Leaving state: '+stateName);
            this.currentState = null;
            
            switch (stateName) {
                case 'playerMayPlayWord':
                    if (this.isCurrentPlayerActive()) {
                        this.handStock.setSelectionMode(0);
                        this.communityStock.setSelectionMode(0);
                        this.hideWordAreaButtons();
                    }
                    break;
                
                

                case 'playerMayDiscardCards':
                    if (this.isCurrentPlayerActive()) {
                        this.handStock.setSelectionMode(0);
                        dojo.removeClass('discard_button', 'show');
                    }
                    break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function (stateName, args) {
            console.log('onUpdateActionButtons: '+stateName);
                      
            if (this.isCurrentPlayerActive()) {            
                switch (stateName) {
                    case 'playerMayPlayWord':
                        this.addActionButton('skipPlayWord_button', _('Skip playing a word'), 'onSkipPlayWord', null, false, 'gray'); 
                        break;
                    
                    case 'playerMayDiscardCards':
                        this.addActionButton('skipDiscardCards_button', _('Skip discarding cards'), 'onSkipDiscardCards', null, false, 'gray'); 
                        break;
                    
                    case 'playerMayBuyPatent':
                        this.addActionButton('skipBuyPatent_button', _('Skip buying a patent'), 'onSkipBuyPatent', null, false, 'gray'); 
                        break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods

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
            cardStock.create( this, $(element_id), this.cardWidth, this.cardHeight );
            cardStock.image_items_per_row = 13;
            for (var letter = 0, letters = 26; letter < letters; letter++) {
                cardStock.addItemType( letter, letter, g_gamethemeurl+'img/cards_small.jpg', letter );
            }
            cardStock.onItemCreate = dojo.hitch(this, 'createCard'); 
            cardStock.setSelectionMode(0);
            return cardStock;
        },

        createCard: function (element, type, id) {
            dojo.addClass(element, 'card');
        },

        createPatentStock: function (element_id) {
            var patentStock = new ebg.stock();
            patentStock.create( this, $(element_id), this.patentWidth, this.patentHeight );
            patentStock.image_items_per_row = 2;
            for (var letter = 0, letters = 26; letter < letters; letter++) {
                patentStock.addItemType( letter, letter, g_gamethemeurl+'img/patents_small.jpg', letter );
            }
            patentStock.onItemCreate = dojo.hitch(this, 'createPatent'); 
            patentStock.setSelectionMode(0);
            return patentStock;
        },

        createPatent: function (element, type, id) {
            dojo.addClass(element, 'patent');
        },

        createCounter: function (element_id, value) {
            var counter = new ebg.counter();
            counter.create(element_id);
            counter.setValue(value);
            return counter;
        },

        setClassIf: function (condition, id, cls) {
            if (condition) {
                if (!dojo.hasClass(id, cls)) {
                    dojo.addClass(id, cls);
                }
            } else {
                if (dojo.hasClass(id, cls)) {
                    dojo.removeClass(id, cls);
                }
            }
        },

        updateWordAreaButtons: function () {
            var items = this.mainWordStock.getAllItems();
            this.setClassIf(items.length > 0, 'play_word_button', 'show');
            this.setClassIf(items.length < 3, 'play_word_button', 'disabled');
            this.setClassIf(items.length > 0, 'clear_button', 'show');
        },

        hideWordAreaButtons: function () {
            dojo.removeClass('play_word_button', 'show');
            dojo.removeClass('clear_button', 'show');
        },

        updateDiscardButton: function () {
            var selectedItems = this.handStock.getSelectedItems();
            dojo.place('<span>'+this.getDiscardButtonLabel(selectedItems.length)+'</span>', 'discard_button', 'only');
            this.setClassIf(selectedItems.length === 0, 'discard_button', 'disabled');
        },

        getDiscardButtonLabel: function (numSelectedCards) {
            if (numSelectedCards > 0) {
                return dojo.string.substitute(_('Discard selected cards (${n})'), { n: numSelectedCards });
            } else {
                return _('Select cards to discard');
            }
        },

        playSelectedCard: function (fromStock, origin, wordStock, wordOrigins, wordTypes) {
            var items = fromStock.getSelectedItems();
            if (items.length === 1) {
                var item = items[0];
                var elementId = fromStock.getItemDivId(item.id);
                wordStock.addToStockWithId(item.type, item.id, $(elementId));
                fromStock.removeFromStockById(item.id);
                wordOrigins.push(origin);
                wordTypes.push(item.type === 24 ? 'v' : '_'); // Y defaults to vowel
                this.updateWordAreaButtons();
            }
        },

        clearWordArea: function () {
            var items = this.mainWordStock.getAllItems();
            for (var i in items) {
                var item = items[i];
                var elementId = this.mainWordStock.getItemDivId(item.id);
                if (this.mainWordOrigins[i] === 'c') {
                    this.communityStock.addToStockWithId(item.type, item.id, $(elementId));
                } else if (this.mainWordOrigins[i] === 'h') {
                    this.handStock.addToStockWithId(item.type, item.id, $(elementId));
                }
            }
            this.mainWordStock.removeAll();
            this.mainWordOrigins = [];
            this.mainWordTypes = [];
            // todo: extra word
            this.updateWordAreaButtons();
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

        action_playWord: function (mainWord, extraWord) {
            var args = {
                main_word_letters: mainWord.letters.join(''),
                main_word_letter_origins: mainWord.letterOrigins.join(''),
                main_word_letter_types: mainWord.letterTypes.join(''),
                main_word_card_ids: this.toNumberList(mainWord.cardIds)
            };
            // todo: add extra word args
            this.sendAction('playWord', args);
        },

        action_skipPlayWord: function () {
            this.sendAction('skipPlayWord');
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

        ///////////////////////////////////////////////////
        //// Player's action
        
        onSkipPlayWord: function (evt) {
            console.log('skip play word');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.action_skipPlayWord();
        },

        onSkipBuyPatent: function (evt) {
            console.log('skip buy patent');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.action_skipBuyPatent();
        },

        onSkipDiscardCards: function (evt) {
            console.log('skip discard cards');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.action_skipDiscardCards();
        },

        onCommunitySelectionChanged: function () {
            console.log('community selection changed');

            switch (this.currentState) {

                case 'playerMayPlayWord':
                    this.playSelectedCard(this.communityStock, 'c',
                        this.mainWordStock, this.mainWordOrigins, this.mainWordTypes);
                    break;

            }
        },

        onHandSelectionChanged: function () {
            console.log('hand selection changed');

            switch (this.currentState) {

                case 'playerMayPlayWord':
                    this.playSelectedCard(this.handStock, 'h',
                        this.mainWordStock, this.mainWordOrigins, this.mainWordTypes);
                    break;

                case 'playerMayDiscardCards':
                    this.updateDiscardButton();
                    break;

            }
        },

        onPlayWordButtonClicked: function (evt) {
            console.log('play word button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            var items = this.mainWordStock.getAllItems();

            console.log(items);

            var mainWord = {
                letters: this.getLetterListFromItems(items),
                letterOrigins: this.mainWordOrigins,
                letterTypes: this.mainWordTypes,
                cardIds: this.getItemIds(items),
            };

            this.action_playWord(mainWord);
        },

        onClearButtonClicked: function (evt) {
            console.log('clear button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.clearWordArea();
        },

        onDiscardButtonClicked: function (evt) {
            console.log('discard button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            var items = this.handStock.getSelectedItems();

            console.log(items);

            var ids = this.getItemIds(items);

            this.action_discardCards(ids);
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
            console.log('notifications subscriptions setup');

            dojo.subscribe('playerPlayedWord', this, 'notif_playerPlayedWord');
            this.notifqueue.setSynchronous( 'playerPlayedWord', 3000 );

            dojo.subscribe('automaticChallengeRejectedWordTryAgain', this, 'notif_automaticChallengeRejectedWordTryAgain');
            this.notifqueue.setSynchronous( 'automaticChallengeRejectedWordTryAgain', 2000 );

            dojo.subscribe('playerReceivedMoneyAndStock', this, 'notif_playerReceivedMoneyAndStock');

            dojo.subscribe('communityReceivedCards', this, 'notif_communityReceivedCards');
            this.notifqueue.setSynchronous( 'communityReceivedCards', 2000 );

            dojo.subscribe('wordDiscarded', this, 'notif_wordDiscarded');

            dojo.subscribe('activePlayerDiscardedCards', this, 'notif_activePlayerDiscardedCards');
            this.notifqueue.setSynchronous( 'activePlayerDiscardedCards', 2000 );
            
            dojo.subscribe('activePlayerReceivedCards', this, 'notif_activePlayerReceivedCards');

            dojo.subscribe('playerDiscardedNumberOfCards', this, 'notif_playerDiscardedNumberOfCards');
        },  

        notif_playerPlayedWord: function (notif) {
            console.log('player played word');
            console.log(notif);
            var mainWordItems = this.mainWordStock.getAllItems();
            if (mainWordItems.length === 0) {
                var player_id = notif.args.player_id;
                var main_word = notif.args.main_word;
                var letters = main_word.letters;
                var letter_origins = main_word.letter_origins;
                var letter_types = main_word.letter_types;
                var card_ids = main_word.card_ids;
                for (var i in card_ids) {
                    var card_id = card_ids[i];
                    var letter = letters[i];
                    var letter_origin = letter_origins[i];
                    var elementId = undefined;
                    if (letter_origin === 'c') {
                        elementId = this.communityStock.getItemDivId(card_id);
                    }
                    if (letter_origin === 'h') {
                        elementId = $('overall_player_board_'+player_id);
                    }
                    this.mainWordStock.addToStockWithId(this.getLetterIndex(letter), card_id, elementId);
                    if (letter_origin === 'c') {
                        this.communityStock.removeFromStockById(card_id);
                    }
                }
                this.mainWordOrigins = letter_origins.split('');
                this.mainWordTypes = letter_types.split('');
            }
            // todo: extra word
        },

        notif_automaticChallengeRejectedWordTryAgain: function (notif) {
            console.log('automatic challenge rejected word try again');
            console.log(notif);
            this.clearWordArea();
        },

        notif_playerReceivedMoneyAndStock: function (notif) {
            console.log('player received money and stock');
            console.log(notif);
            var player_id = notif.args.player_id;
            var money = notif.args.money;
            var stock = notif.args.stock;
            this.playerMoney[player_id].incValue(money);
            this.playerStock[player_id].incValue(stock);
            this.scoreCtrl[player_id].incValue(money + stock);
        },

        notif_communityReceivedCards: function (notif) {
            console.log('community received cards');
            console.log(notif);
            var new_cards = notif.args.new_cards;
            for (var i in new_cards) {
                var new_card = new_cards[i];
                this.communityStock.addToStockWithId(this.getLetterIndex(new_card.type), new_card.id,
                    $('community_pool_area_header'));
            }
        },

        notif_wordDiscarded: function (notif) {
            console.log('word discarded');
            console.log(notif);
            this.mainWordStock.removeAll();
        },

        notif_activePlayerDiscardedCards: function (notif) {
            console.log('active player discarded cards');
            console.log(notif);
            var card_ids = notif.args.card_ids;
            for (var i in card_ids) {
                var card_id = card_ids[i];
                this.handStock.removeFromStockById(card_id, undefined, true);
            }
            this.handStock.updateDisplay();
        },

        notif_activePlayerReceivedCards: function (notif) {
            console.log('active player received cards');
            console.log(notif);
            var new_cards = notif.args.new_cards;
            for (var i in new_cards) {
                var new_card = new_cards[i];
                this.handStock.addToStockWithId(this.getLetterIndex(new_card.type), new_card.id,
                    $('current_player_hand_area_header'));
            }
        },

        notif_playerDiscardedNumberOfCards: function (notif) {
            console.log('player discarded number of cards');
            console.log(notif);
        }
   });             
});
