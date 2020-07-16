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
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.lettertycoon", ebg.core.gamegui, {
        constructor: function(){
            console.log('lettertycoon constructor');

            // constants
            this.cardWidth = 60;
            this.cardHeight = 80;
            this.patentWidth = 81;
            this.patentHeight = 41;

            // card stocks
            this.communityStock = null;
            this.handStock = null;
            this.wordStocks = []; // valid index: 1, 2

            // patent stocks
            this.availablePatents = null;
            this.patentStocksByPlayer = {}; // key: player_id
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
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );

            this.communityStock = new ebg.stock();
            this.communityStock.create( this, $('community_pool'), this.cardWidth, this.cardHeight );
            this.communityStock.image_items_per_row = 13;
            this.handStock = new ebg.stock();
            this.handStock.create( this, $('current_player_hand'), this.cardWidth, this.cardHeight );
            this.handStock.image_items_per_row = 13;
            this.wordStocks[1] = new ebg.stock();
            this.wordStocks[1].create( this, $('played_word_1'), this.cardWidth, this.cardHeight );
            this.wordStocks[1].image_items_per_row = 13;
            this.wordStocks[2] = new ebg.stock();
            this.wordStocks[2].create( this, $('played_word_2'), this.cardWidth, this.cardHeight );
            this.wordStocks[2].image_items_per_row = 13;

            for (var letter = 0, letters = 26; letter < letters; letter++) {
                this.communityStock.addItemType( letter, letter, g_gamethemeurl+'img/cards.jpg', letter );
                this.handStock.addItemType( letter, letter, g_gamethemeurl+'img/cards.jpg', letter );
                this.wordStocks[1].addItemType( letter, letter, g_gamethemeurl+'img/cards.jpg', letter );
                this.wordStocks[2].addItemType( letter, letter, g_gamethemeurl+'img/cards.jpg', letter );
            }
            
            this.availablePatents = new ebg.stock();
            this.availablePatents.create( this, $('available_patents'), this.patentWidth, this.patentHeight );
            this.availablePatents.image_items_per_row = 2;
            for (var letter = 0, letters = 26; letter < letters; letter++) {
                this.availablePatents.addItemType( letter, letter, g_gamethemeurl+'img/patents.jpg', letter );
            }

            var players = gamedatas.players;
            for (var player_id in players)
            {
                var patentStock = new ebg.stock();
                patentStock.create( this, $('player_area_patents_'+player_id), this.patentWidth, this.patentHeight );
                patentStock.image_items_per_row = 2;
                for (var letter = 0, letters = 26; letter < letters; letter++) {
                    patentStock.addItemType( letter, letter, g_gamethemeurl+'img/patents.jpg', letter );
                }
                this.patentStocksByPlayer[player_id] = patentStock;
            }

            var patent_owners = gamedatas.patent_owners;
            for (var letter in patent_owners) {
                var letter_index = this.getLetterIndex(letter);
                var owner = patent_owners[letter];
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
            
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        getLetterIndex: function (letter) {
            return letter.charCodeAt(0) - 65; // 'A'
        },


        ///////////////////////////////////////////////////
        //// Player's action
        
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
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
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
   });             
});
