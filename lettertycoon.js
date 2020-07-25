/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * LetterTycoon implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * lettertycoon.js
 *
 * LetterTycoon user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
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

            // ui state
            this.mainWordOrigins = [];
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
                this.patentStocksByPlayer[player_id] = this.createPatentStock('player_area_patents_'+player_id);
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

            dojo.connect(this.communityStock, 'onChangeSelection', this, 'onCommunitySelectionChanged');
            dojo.connect(this.handStock, 'onChangeSelection', this, 'onHandSelectionChanged');
            dojo.connect($('clear_button'), 'onclick', this, 'onClearButtonClicked');
            dojo.connect($('discard_button'), 'onclick', this, 'onDiscardButtonClicked');
            
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
                    }
                    break;
                
                case 'playerMayDiscardCards':
                    if (this.isCurrentPlayerActive()) {
                        this.handStock.setSelectionMode(2);
                        this.updateDiscardButton();
                        dojo.addClass('discard_button', 'show');
                    }
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
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods

        getLetterIndex: function (letter) {
            return letter.charCodeAt(0) - 65; // 'A'
        },

        createCardStock: function (element_id) {
            var cardStock = new ebg.stock();
            cardStock.create( this, $(element_id), this.cardWidth, this.cardHeight );
            cardStock.image_items_per_row = 13;
            for (var letter = 0, letters = 26; letter < letters; letter++) {
                cardStock.addItemType( letter, letter, g_gamethemeurl+'img/cards_small.jpg', letter );
            }
            cardStock.onItemCreate = dojo.hitch(this, 'createCard' ); 
            cardStock.setSelectionMode(0);
            return cardStock;
        },

        createCard: function (card_div, card_type_id, card_id) {
            dojo.addClass(card_div, 'card');
        },

        createPatentStock: function (element_id) {
            var patentStock = new ebg.stock();
            patentStock.create( this, $(element_id), this.patentWidth, this.patentHeight );
            patentStock.image_items_per_row = 2;
            for (var letter = 0, letters = 26; letter < letters; letter++) {
                patentStock.addItemType( letter, letter, g_gamethemeurl+'img/patents_small.jpg', letter );
            }
            patentStock.setSelectionMode(0);
            return patentStock;
        },

        updateClearButton: function () {
            var items = this.mainWordStock.getAllItems();
            if (items.length > 0) {
                if (!dojo.hasClass('clear_button', 'show')) {
                    dojo.addClass('clear_button', 'show');
                }
            } else {
                if (dojo.hasClass('clear_button', 'show')) {
                    dojo.removeClass('clear_button', 'show');
                }
            }
        },

        updateDiscardButton: function () {
            var selectedItems = this.handStock.getSelectedItems();
            dojo.place('<span>'+this.getDiscardButtonLabel(selectedItems.length)+'</span>', 'discard_button', 'only');
            if (selectedItems.length > 0) {
                if (dojo.hasClass('discard_button', 'disabled')) {
                    dojo.removeClass('discard_button', 'disabled');
                }
            } else {
                if (!dojo.hasClass('discard_button', 'disabled')) {
                    dojo.addClass('discard_button', 'disabled');
                }
            }
        },

        getDiscardButtonLabel: function (numSelectedCards) {
            if (numSelectedCards > 0) {
                return dojo.string.substitute(_('Discard selected cards (${n})'), { n: numSelectedCards });
            } else {
                return _('Select cards to discard');
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

        action_skipPlayWord: function () {
            this.sendAction('skipPlayWord');
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
        
        onSkipPlayWord: function () {
            console.log('skip play word');
            this.action_skipPlayWord();
        },

        onSkipDiscardCards: function () {
            console.log('skip discard cards');
            this.action_skipDiscardCards();
        },

       onCommunitySelectionChanged: function () {
            console.log('community selection changed');

            var items = this.communityStock.getSelectedItems();

            console.log(items);

            switch (this.currentState) {

                case 'playerMayPlayWord':
                    {
                        if (items.length === 1) {
                            var item = items[0];
                            var elementId = this.communityStock.getItemDivId(item.id);
                            this.mainWordStock.addToStockWithId(item.type, item.id, $(elementId));
                            this.communityStock.removeFromStockById(item.id);
                            this.mainWordOrigins.push('community');
                            this.updateClearButton();
                        }
                    }
                    break;

            }
        },

        onHandSelectionChanged: function () {
            console.log('hand selection changed');

            var items = this.handStock.getSelectedItems();

            console.log(items);

            switch (this.currentState) {

                case 'playerMayPlayWord':
                    {
                        if (items.length === 1) {
                            var item = items[0];
                            var elementId = this.handStock.getItemDivId(item.id);
                            this.mainWordStock.addToStockWithId(item.type, item.id, $(elementId));
                            this.handStock.removeFromStockById(item.id);
                            this.mainWordOrigins.push('hand');
                            this.updateClearButton();
                        }
                    }
                    break;

                case 'playerMayDiscardCards':
                    this.updateDiscardButton();
                    break;

            }
        },

        onClearButtonClicked: function () {
            console.log('clear button clicked');

            var items = this.mainWordStock.getAllItems();

            console.log(items);

            for (var i in items) {
                var item = items[i];
                var elementId = this.mainWordStock.getItemDivId(item.id);
                if (this.mainWordOrigins[i] === 'community') {
                    this.communityStock.addToStockWithId(item.type, item.id, $(elementId));
                } else if (this.mainWordOrigins[i] === 'hand') {
                    this.handStock.addToStockWithId(item.type, item.id, $(elementId));
                }
            }
            this.mainWordStock.removeAll();
            this.mainWordOrigins = [];
            this.updateClearButton();
        },

        onDiscardButtonClicked: function () {
            console.log('discard button clicked');

            var items = this.handStock.getSelectedItems();

            console.log(items);

            this.action_discardCards(items.map(item => item.id));
        },

        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/lettertycoon/lettertycoon/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        
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
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 

            dojo.subscribe('activePlayerDiscardedCards', this, 'notif_activePlayerDiscardedCards');
            this.notifqueue.setSynchronous( 'activePlayerDiscardedCards', 2000 );
            
            dojo.subscribe('activePlayerReceivedCards', this, 'notif_activePlayerReceivedCards');

            dojo.subscribe('playerDiscardedNumberOfCards', this, 'notif_playerDiscardedNumberOfCards');
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */

        notif_activePlayerDiscardedCards: function (notif) {
            console.log('active player discarded cards');
            console.log(notif);
            for (var i in notif.args.card_ids) {
                var card_id = notif.args.card_ids[i];
                this.handStock.removeFromStockById(card_id, undefined, true);
            }
            this.handStock.updateDisplay();
        },

        notif_activePlayerReceivedCards: function (notif) {
            console.log('active player received cards');
            console.log(notif);
            for (var i in notif.args.new_cards) {
                var new_card = notif.args.new_cards[i];
                this.handStock.addToStockWithId(this.getLetterIndex(new_card.type), new_card.id,
                    $('current_player_area_header'));
            }
        },

        notif_playerDiscardedNumberOfCards: function (notif) {
            console.log('player discarded number of cards');
            console.log(notif);
        }
   });             
});
