-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- LetterTycoon implementation : © Jeff Raymakers <jephly@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----


-- money: 0 to ?
-- stock: 0 to ?
-- challenge: 0 or 1
ALTER TABLE `player` ADD `money` smallint(5) unsigned NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `stock` smallint(5) unsigned NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `challenge` tinyint(1) unsigned NOT NULL DEFAULT '0';
-- HAND ORDER: This is one way we might save the hand order.
-- hand_order: semicolon separated list of card_ids (up to 7, each max 3 chars)
-- ALTER TABLE `player` ADD `hand_order` varchar(32) NOT NULL DEFAULT '';


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
-- letter_type: 'c' = consonant, 'v' = vowel, '_' = as defined (for setting value of Y)
-- card_id: id of card or 200+ if generated
CREATE TABLE IF NOT EXISTS `word` (
  `word_num` tinyint(1) unsigned NOT NULL,
  `word_pos` tinyint(2) unsigned NOT NULL,
  `letter` char(1) NOT NULL,
  `letter_origin` char(1) NOT NULL,
  `letter_type` char(1) NOT NULL,
  `card_id` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`word_num`, `word_pos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- HAND ORDER: This is another way we might save the hand order.
-- CREATE TABLE IF NOT EXISTS `hand_order` (
--   `player_id` int(10) unsigned NOT NULL,
--   `index` tinyint(1) unsigned NOT NULL,
--   `card_id` tinyint(3) unsigned NOT NULL,
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
