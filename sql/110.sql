ALTER TABLE `games` ADD `faucet_policy` ENUM('on','off') NOT NULL DEFAULT 'on' AFTER `game_buyin_cap`;