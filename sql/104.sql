ALTER TABLE `currency_accounts` ADD `is_sale_account` TINYINT(1) NOT NULL DEFAULT '0' AFTER `is_faucet`;
INSERT INTO `oracle_urls` (`oracle_url_id`, `format_id`, `url`) VALUES (NULL, 3, 'https://coinmarketcap.com/');
ALTER TABLE `user_games` ADD `display_currency_id` INT NULL DEFAULT NULL AFTER `betting_mode`;
ALTER TABLE `games` ADD `default_display_currency_id` INT NULL DEFAULT '1' AFTER `view_mode`;
UPDATE user_games SET display_currency_id=1;

CREATE TABLE `game_escrow_accounts` (
  `escrow_account_id` int(11) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `time_created` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `game_escrow_accounts`
  ADD PRIMARY KEY (`escrow_account_id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `account_id` (`account_id`);

ALTER TABLE `game_escrow_accounts`
  MODIFY `escrow_account_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;