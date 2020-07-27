
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- LetterTycoon implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

-- CREATE TABLE IF NOT EXISTS `card` (
--   `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `card_type` varchar(16) NOT NULL,
--   `card_type_arg` int(11) NOT NULL,
--   `card_location` varchar(16) NOT NULL,
--   `card_location_arg` int(11) NOT NULL,
--   PRIMARY KEY (`card_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';



-- money: 0 to ?
-- stock: 0 to ?
ALTER TABLE `player` ADD `money` smallint(5) unsigned NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `stock` smallint(5) unsigned NOT NULL DEFAULT '0';


-- card_id: 102 cards
-- card_type: 'A' to 'Z'
-- card_type_arg: unused (set to 0)
-- card_location: 'community', 'deck', 'discard', 'hand', or 'word'
-- card_location_arg:
--   if 'community' then unused
--   if 'deck' then location in deck (managed by Deck)
--   if 'discard' then ??? (managed by Deck)
--   if 'hand' then player_id (managed by Deck)
--   if 'word' then unused (card_id should be in word table)
CREATE TABLE IF NOT EXISTS `card` (
  `card_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` char(1) NOT NULL,
  `card_type_arg` tinyint(1) NOT NULL,
  `card_location` varchar(10) NOT NULL,
  `card_location_arg` int(10) unsigned NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


-- patent_id: 26 patents 'A' to 'Z'
-- owning_player_id: player_id or NULL
CREATE TABLE IF NOT EXISTS `patent` (
  `patent_id` char(1) NOT NULL,
  `owning_player_id` int(10) unsigned,
  PRIMARY KEY (`patent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


-- word_num: 1 or 2
-- word_pos: 1 to 12
-- letter: 'A' to 'Z'
-- letter_origin: 'c' = community, 'd' = duplicated, 'h' = hand, 's' = appended S
-- letter_type: 'c' = consonant, 'v' = vowel, NULL = as defined (for setting value of Y)
-- card_id: id of card or NULL if generated
CREATE TABLE IF NOT EXISTS `word` (
  `word_num` tinyint(1) unsigned NOT NULL,
  `word_pos` tinyint(2) unsigned NOT NULL,
  `letter` char(1) NOT NULL,
  `letter_origin` char(1) NOT NULL,
  `letter_type` char(1),
  `card_id` tinyint(3) unsigned,
  PRIMARY KEY (`word_num`, `word_pos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
