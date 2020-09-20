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
            this.wordStock[1] = this.createCardStock('main_word');
            this.wordStock[1].order_items = false;
            this.wordStock[2] = this.createCardStock('second_word');
            this.wordStock[2].order_items = false;
            
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

            var hand = gamedatas.hand;
            for (var card_id in hand) {
                var card = hand[card_id];
                this.handStock.addToStockWithId(this.getLetterIndex(card.type), card.id);
            }

            var main_word = gamedatas.main_word;
            for (var i in main_word) {
                var part = main_word[i];
                this.wordStock[1].addToStockWithId(this.getLetterIndex(part.letter), part.card_id);
                this.wordInfo[1].origins[i] = part.letter_origin;
                this.wordInfo[1].types[i] = part.letter_type;
            }

            var second_word = gamedatas.second_word;
            if (second_word.length > 0) {
                dojo.addClass('second_word', 'show');
            }
            for (var i in second_word) {
                var part = second_word[i];
                this.wordStock[2].addToStockWithId(this.getLetterIndex(part.letter), part.card_id);
                this.wordInfo[2].origins[i] = part.letter_origin;
                this.wordInfo[2].types[i] = part.letter_type;
            }

            this.patent_costs = gamedatas.patent_costs;

            dojo.connect(this.communityStock, 'onChangeSelection', this, 'onCommunitySelectionChanged');
            dojo.connect(this.handStock, 'onChangeSelection', this, 'onHandSelectionChanged');
            dojo.connect(this.wordStock[1], 'onChangeSelection', this, 'onWord1SelectionChanged');
            dojo.connect(this.wordStock[2], 'onChangeSelection', this, 'onWord2SelectionChanged');
            dojo.connect(this.availablePatents, 'onChangeSelection', this, 'onPatentSelectionChanged');
            dojo.connect($('play_word_button'), 'onclick', this, 'onPlayWordButtonClicked');
            dojo.connect($('change_letter_type_button'), 'onclick', this, 'onChangeLetterTypeButtonClicked');
            dojo.connect($('start_second_word_button'), 'onclick', this, 'onStartSecondWordButtonClicked');
            dojo.connect($('duplicate_letter_button'), 'onclick', this, 'onDuplicateLetterButtonClicked');
            dojo.connect($('add_an_s_button'), 'onclick', this, 'onAddAnSButtonClicked');
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
                case 'playerMayReplaceCard':
                    if (this.isCurrentPlayerActive()) {
                        this.handStock.setSelectionMode(1);
                        this.communityStock.setSelectionMode(1);
                    }
                    break;

                case 'playerMayPlayWord':
                    if (this.isCurrentPlayerActive()) {
                        this.handStock.setSelectionMode(1);
                        this.communityStock.setSelectionMode(1);
                        this.wordStock[1].setSelectionMode(1);
                        this.wordStock[2].setSelectionMode(1);
                        this.showWordAreaButtons();
                        this.updateWordAreaButtons();
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
                        this.handStock.setSelectionMode(2);
                        this.updateDiscardButton();
                        dojo.addClass('discard_button', 'show');
                    }
                    break;
                
                case 'playerMustDiscardCard':
                    if (this.isCurrentPlayerActive()) {
                        this.handStock.setSelectionMode(1);
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
        onLeavingState: function( stateName ) {
            console.log('Leaving state: '+stateName);
            this.currentState = null;
            
            switch (stateName) {
                case 'playerMayReplaceCard':
                    if (this.isCurrentPlayerActive()) {
                        this.handStock.setSelectionMode(0);
                        this.communityStock.setSelectionMode(0);
                    }
                    break;
                
                case 'playerMayPlayWord':
                    if (this.isCurrentPlayerActive()) {
                        this.handStock.setSelectionMode(0);
                        this.communityStock.setSelectionMode(0);
                        this.wordStock[1].setSelectionMode(0);
                        this.wordStock[2].setSelectionMode(0);
                        this.hideWordAreaButtons();
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
                        this.handStock.setSelectionMode(0);
                        dojo.removeClass('discard_button', 'show');
                    }
                    break;
                
                case 'playerMustDiscardCard':
                    if (this.isCurrentPlayerActive()) {
                        this.handStock.setSelectionMode(0);
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
                    case 'playerMayReplaceCard':
                        this.addActionButton('skipReplaceCard_button', _('Skip replacing a card'), 'onSkipReplaceCard', null, false, 'gray'); 
                        break;
                    
                    case 'playerMayPlayWord':
                        this.addActionButton('skipPlayWord_button', _('Skip playing a word'), 'onSkipPlayWord', null, false, 'gray'); 
                        break;
                    
                    case 'playersMayChallenge':
                        this.addActionButton('challengeWord_button', _('Challenge word'), 'onChallengeWord', null, false, 'red');
                        this.addActionButton('acceptWord_button', _('Accept word'), 'onAcceptWord', null, false, 'gray');
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
            cardStock.create( this, $(element_id), this.cardWidth, this.cardHeight );
            cardStock.image_items_per_row = 13;
            for (var letter = 0, letters = 26; letter < letters; letter++) {
                cardStock.addItemType( letter, letter, g_gamethemeurl+'img/cards_small.jpg', letter );
            }
            cardStock.onItemCreate = dojo.hitch(this, 'createCard'); 
            cardStock.setSelectionMode(0);
            cardStock.setSelectionAppearance('class');
            return cardStock;
        },

        createCard: function (element, type, id) {
            dojo.addClass(element, 'card');
            if (/205$/.test(id)) { // 205 = added S card id
                dojo.addClass(element, 'added_s');
            }
            if (/208$/.test(id)) { // 208 = duplicate card id
                dojo.addClass(element, 'duplicate');
            }
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
            patentStock.setSelectionAppearance('class');
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
            var mainWordItems = this.wordStock[1].getAllItems();
            var mainWordSelectedItems = this.wordStock[1].getSelectedItems();
            var secondWordItems = this.wordStock[2].getAllItems();
            var secondWordSelectedItems = this.wordStock[2].getSelectedItems();

            this.setClassIf(
                mainWordItems.length < 3 || (this.secondWordStarted && secondWordItems.length < 3),
                'play_word_button', 'disabled'
            );

            this.setClassIf(
                mainWordItems.length < 1,
                'clear_button', 'disabled'
            );

            // only show change letter type button if player owns a relevant patent
            if (this.vowelsCanAffectPlayerScore()) {
                this.setClassIf(
                    !(this.itemsContainsLetter(mainWordSelectedItems, 'Y') || this.itemsContainsLetter(secondWordSelectedItems, 'Y')),
                    'change_letter_type_button', 'disabled'
                );
            }

            if (this.patentOwners['V'] === this.getPlayerIdString()) {
                this.setClassIf(
                    mainWordItems.length < 3 || this.secondWordStarted,
                    'start_second_word_button', 'disabled'
                );
            }

            if (this.patentOwners['X'] === this.getPlayerIdString()) {
                this.setClassIf(
                    this.duplicatePlayed()
                        || (mainWordSelectedItems.length === 0 && secondWordSelectedItems.length === 0)
                        || this.itemsContainsLetter(mainWordSelectedItems, 'S')
                        || this.itemsContainsLetter(secondWordSelectedItems, 'S')
                        || this.currentWordComplete(),
                    'duplicate_letter_button', 'disabled'
                );
            }

            if (this.patentOwners['Z'] === this.getPlayerIdString()) {
                this.setClassIf(
                    this.addAnSPlayed()
                        || mainWordItems.length < 2 || (this.secondWordStarted && secondWordItems.length < 2),
                    'add_an_s_button', 'disabled'
                );
            }

            this.setClassIf(this.secondWordStarted || secondWordItems.length > 0, 'second_word', 'show');
            
            // only show Y types if player owns a relevant patent
            if (this.vowelsCanAffectPlayerScore()) {
                this.updateYTypes(1);
                this.updateYTypes(2);
            }
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

        showWordAreaButtons: function () {
            dojo.addClass('play_word_button', 'show');
            dojo.addClass('clear_button', 'show');
            this.setClassIf(this.vowelsCanAffectPlayerScore(), 'change_letter_type_button', 'show');
            this.setClassIf(this.patentOwners['V'] === this.getPlayerIdString(), 'start_second_word_button', 'show');
            this.setClassIf(this.patentOwners['X'] === this.getPlayerIdString(), 'duplicate_letter_button', 'show');
            this.setClassIf(this.patentOwners['Z'] === this.getPlayerIdString(), 'add_an_s_button', 'show');
        },

        hideWordAreaButtons: function () {
            dojo.removeClass('play_word_button', 'show');
            dojo.removeClass('clear_button', 'show');
            dojo.removeClass('change_letter_type_button', 'show');
            dojo.removeClass('start_second_word_button', 'show');
            dojo.removeClass('duplicate_letter_button', 'show');
            dojo.removeClass('add_an_s_button', 'show');
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

        discardCard: function (fromStock) {
            var items = fromStock.getSelectedItems();
            if (items.length === 1) {
                var item = items[0];
                this.action_discardCard(item.id);
            }
        },

        replaceCard: function (fromStock) {
            var items = fromStock.getSelectedItems();
            if (items.length === 1) {
                var item = items[0];
                this.action_replaceCard(item.id);
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

        changeLetterType: function (stock, word /* 1 or 2 */) {
            var typeY = this.getLetterIndex('Y');
            var wordStock = this.wordStock[word];
            var wordInfo = this.wordInfo[word];
            var items = stock.getAllItems();
            for (var i = 0, l = items.length; i < l; i++) {
                var item = items[i];
                if (stock.isSelected(item.id) && item.type === typeY) {
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
            return this.patentOwners['B'] === this.getPlayerIdString()
                || this.patentOwners['J'] === this.getPlayerIdString()
                || this.patentOwners['K'] === this.getPlayerIdString();
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
                    elementId = $('overall_player_board_'+player_id);
                }
                wordStock.addToStockWithId(this.getLetterIndex(letter), card_id, elementId);
                if (letter_origin === 'c') {
                    this.communityStock.removeFromStockById(card_id);
                }
            }

            wordInfo.origins = letter_origins.split('');
            wordInfo.types = letter_types.split('');
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
                }
            }
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
            console.log(items);
            if (items.length === 1) {
                var item = items[0];
                this.action_buyPatent(item.type);
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

        ///////////////////////////////////////////////////
        //// Player's action

        onSkipReplaceCard: function (evt) {
            console.log('skip replace card');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.action_skipReplaceCard();
        },
        
        onSkipPlayWord: function (evt) {
            console.log('skip play word');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.action_skipPlayWord();
        },

        onChallengeWord: function (evt) {
            console.log('challenge word');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.action_challengeWord();
        },

        onAcceptWord: function (evt) {
            console.log('accept word');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.action_acceptWord();
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

                case 'playerMayReplaceCard':
                    this.replaceCard(this.communityStock);
                    break;

                case 'playerMayPlayWord':
                    this.playSelectedCard(this.communityStock, 'c', this.secondWordStarted ? 2 : 1);
                    break;

            }
        },

        onHandSelectionChanged: function () {
            console.log('hand selection changed');

            switch (this.currentState) {

                case 'playerMayReplaceCard':
                    this.replaceCard(this.handStock);
                    break;

                case 'playerMayPlayWord':
                    this.playSelectedCard(this.handStock, 'h', this.secondWordStarted ? 2 : 1);
                    break;

                case 'playerMayDiscardCards':
                    this.updateDiscardButton();
                    break;
                
                case 'playerMustDiscardCard':
                    this.discardCard(this.handStock);
                    break;
            }
        },

        onWord1SelectionChanged: function () {
            console.log('word 1 selection changed');

            switch (this.currentState) {

                case 'playerMayPlayWord':
                    this.unselectAllItems(this.wordStock[2]);
                    this.updateWordAreaButtons();
                    break;

            }
        },

        onWord2SelectionChanged: function () {
            console.log('word 2 selection changed');

            switch (this.currentState) {

                case 'playerMayPlayWord':
                    this.unselectAllItems(this.wordStock[1]);
                    this.updateWordAreaButtons();
                    break;

            }
        },

        onPatentSelectionChanged: function () {
            console.log('patent selection changed');

            switch (this.currentState) {

                case 'playerMayBuyPatent':
                    this.buySelectedPatent();
                    break;

            }
        },

        onPlayWordButtonClicked: function (evt) {
            console.log('play word button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            var mainWordParams = this.getWordParamsForPlayWord(1);
            var secondWordParams = this.getWordParamsForPlayWord(2);

            this.action_playWord(mainWordParams, secondWordParams);
        },

        onChangeLetterTypeButtonClicked: function (evt) {
            console.log('change letter type button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            var secondWordLetterSelected = this.secondWordStarted && this.wordStock[2].getSelectedItems().length > 0;
            var wordStock = this.wordStock[secondWordLetterSelected ? 2 : 1];
            this.changeLetterType(wordStock, this.secondWordStarted ? 2 : 1);
        },

        onStartSecondWordButtonClicked: function (evt) {
            console.log('start second word button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.secondWordStarted = true;
            this.updateWordAreaButtons();
        },

        onDuplicateLetterButtonClicked: function (evt) {
            console.log('duplicate letter button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            var secondWordLetterSelected = this.secondWordStarted && this.wordStock[2].getSelectedItems().length > 0;
            var wordStock = this.wordStock[secondWordLetterSelected ? 2 : 1];
            this.duplicateLetter(wordStock, this.secondWordStarted ? 2 : 1);
        },

        onAddAnSButtonClicked: function (evt) {
            console.log('add an S button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.addAnS(this.secondWordStarted ? 2 : 1);
        },

        onClearButtonClicked: function (evt) {
            console.log('clear button clicked');

            evt.preventDefault();
            dojo.stopEvent(evt);

            this.clearWordArea(this.getPlayerIdString());
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

            dojo.subscribe('playerReplacedCardFromCommunity', this, 'notif_playerReplacedCardFromCommunity');
            this.notifqueue.setSynchronous( 'playerReplacedCardFromCommunity', 2000 );
            dojo.subscribe('activePlayerReplacedCardFromHand', this, 'notif_activePlayerReplacedCardFromHand');
            this.notifqueue.setSynchronous( 'activePlayerReplacedCardFromHand', 2000 );

            dojo.subscribe('playerPlayedWord', this, 'notif_playerPlayedWord');
            this.notifqueue.setSynchronous( 'playerPlayedWord', 3000 );

            this.notifqueue.setSynchronous( 'playerChallenged', 2000 );
            this.notifqueue.setSynchronous( 'playerChallengeSucceeded', 2000 );

            dojo.subscribe('playerMustDiscard', this, 'notif_playerMustDiscard');

            this.notifqueue.setSynchronous( 'playerChallengeFailed', 2000 );

            dojo.subscribe('challengerPaidPenalty', this, 'notif_challengerPaidPenalty');
            dojo.subscribe('playerReceivedPayment', this, 'notif_playerReceivedPayment');

            dojo.subscribe('automaticChallengeRejectedWord', this, 'notif_automaticChallengeRejectedWord');
            this.notifqueue.setSynchronous( 'automaticChallengeRejectedWord', 2000 );

            dojo.subscribe('playerReceivedMoneyAndStock', this, 'notif_playerReceivedMoneyAndStock');
            this.notifqueue.setSynchronous( 'playerReceivedMoneyAndStock', 2000 );

            dojo.subscribe('playerReceivedRoyalties', this, 'notif_playerReceivedRoyalties');

            dojo.subscribe('playerBoughtPatent', this, 'notif_playerBoughtPatent');

            dojo.subscribe('communityReceivedCards', this, 'notif_communityReceivedCards');
            this.notifqueue.setSynchronous( 'communityReceivedCards', 2000 );

            dojo.subscribe('wordDiscarded', this, 'notif_wordDiscarded');

            dojo.subscribe('activePlayerDiscardedCards', this, 'notif_activePlayerDiscardedCards');
            this.notifqueue.setSynchronous( 'activePlayerDiscardedCards', 2000 );
            
            dojo.subscribe('activePlayerReceivedCards', this, 'notif_activePlayerReceivedCards');
        },

        notif_playerReplacedCardFromCommunity: function (notif) {
            console.log('player replaced card from community');
            console.log(notif);
            var card_id = notif.args.card_id;
            this.communityStock.removeFromStockById(card_id);
        },

        notif_activePlayerReplacedCardFromHand: function (notif) {
            console.log('active player replaced card from hand');
            console.log(notif);
            var card_id = notif.args.card_id;
            this.handStock.removeFromStockById(card_id);
        },

        notif_playerPlayedWord: function (notif) {
            console.log('player played word');
            console.log(notif);
            var player_id = notif.args.player_id;
            if (player_id !== this.getPlayerIdString()) {
                var main_word_args = notif.args.main_word;
                var second_word_args = notif.args.second_word;
                this.playWordFromPlayer(player_id, 1, main_word_args);
                if (second_word_args) {
                    dojo.addClass('second_word', 'show');
                    this.playWordFromPlayer(player_id, 2, second_word_args);
                }
            }
        },

        notif_playerMustDiscard: function (notif) {
            console.log('player must discard');
            console.log(notif);
            var player_id = notif.args.player_id;
            this.clearWordArea(player_id);
        },

        notif_challengerPaidPenalty: function (notif) {
            console.log('challenger paid penalty');
            console.log(notif);
            var player_id = notif.args.player_id;
            this.playerMoney[player_id].incValue(-1);
        },

        notif_playerReceivedPayment: function (notif) {
            console.log('player received payment');
            console.log(notif);
            var player_id = notif.args.player_id;
            this.playerMoney[player_id].incValue(1);
        },

        notif_automaticChallengeRejectedWord: function (notif) {
            console.log('automatic challenge rejected word');
            console.log(notif);
            var player_id = notif.args.player_id;
            this.clearWordArea(player_id);
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

        notif_playerReceivedRoyalties: function (notif) {
            console.log('player received royalties');
            console.log(notif);
            var player_id = notif.args.player_id;
            var royalties = notif.args.royalties;
            this.playerMoney[player_id].incValue(royalties);
        },

        notif_playerBoughtPatent: function (notif) {
            console.log('player bought patent');
            console.log(notif);
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
            this.wordStock[1].removeAll();
            this.wordStock[2].removeAll();
            dojo.removeClass('second_word', 'show');
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
        }

   });             
});
