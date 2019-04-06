<?php
class DailyCryptoMarketsGameDefinition {
	public $app;
	public $game_def;
	public $events_per_round;
	public $game_def_base_txt;
	
	public function __construct(&$app) {
		$this->app = $app;
		
		$this->currency_name_to_code = array(
			'Bitcoin'=>'BTC',
			'Bitcoin Cash'=>'BCHABC',
			'Dash'=>'DASH',
			'Ethereum'=>'ETH',
			'Ethereum Classic'=>'ETC',
			'Litecoin'=>'LTC',
			'Monero'=>'XMR',
			'NEM'=>'XEM',
			'Ripple'=>'XRP',
			'EOS'=>'EOS'
		);
		
		$this->game_def_base_txt = '{
			"blockchain_identifier": "stakechain",
			"option_group": "10 significant cryptocurrencies",
			"protocol_version": 0,
			"name": "Daily Crypto Markets",
			"url_identifier": "daily-crypto-markets",
			"module": "DailyCryptoMarkets",
			"category_id": null,
			"decimal_places": 4,
			"event_type_name": "duel",
			"event_type_name_plural": "duels",
			"event_rule": "game_definition",
			"event_winning_rule": "game_definition",
			"event_entity_type_id": 0,
			"events_per_round": 1,
			"inflation": "exponential",
			"exponential_inflation_rate": 0,
			"pos_reward": 0,
			"round_length": 50,
			"maturity": 0,
			"payout_weight": "coin_round",
			"final_round": null,
			"buyin_policy": "none",
			"game_buyin_cap": 0,
			"sellout_policy": "off",
			"sellout_confirmations": 0,
			"coin_name": "dollars",
			"coin_name_plural": "dollars",
			"coin_abbreviation": "USDF",
			"escrow_address": "686MN7cpRyScmxidujqTPhQnDn1FqQu8hu",
			"genesis_tx_hash": "50e7b5db4d98d127ac222557b7724454",
			"genesis_amount": 1000000000000,
			"game_starting_block": 281501,
			"game_winning_rule": "none",
			"game_winning_field": "",
			"game_winning_inflation": 0,
			"default_vote_effectiveness_function": "linear_decrease",
			"default_effectiveness_param1": 0.1,
			"default_max_voting_fraction": 1,
			"default_option_max_width": 200,
			"default_payout_block_delay": 0,
			"default_payout_rule": "binary",
			"view_mode": "default",
			"escrow_amounts": {
				"stakes": 1000000
			}
		}';
		
		$this->game_def = json_decode($this->game_def_base_txt);
	}
	
	public function load_currencies(&$game) {
		$this->currencies = array();
		$this->name2currency_index = [];
		
		$member_q = "SELECT *, en.entity_id AS entity_id FROM option_group_memberships m JOIN entities en ON m.entity_id=en.entity_id JOIN currencies c ON en.entity_name=c.name WHERE m.option_group_id='".$game->db_game['option_group_id']."' ORDER BY m.membership_id ASC;";
		$member_r = $this->app->run_query($member_q);
		$currency_index = 0;
		
		while ($db_member = $member_r->fetch()) {
			array_push($this->currencies, $db_member);
			$this->name2currency_index[$db_member['name']] = $currency_index;
			$currency_index++;
		}
	}
	
	public function get_readable_number($range_length, $some_number) {
		$log10 = floor(log10($range_length));
		$roundto = pow(10, $log10);
		$readable_number = ceil($some_number/$roundto)*$roundto;
		return $readable_number;
	}
	
	public function events_starting_between_rounds(&$game, $from_round, $to_round, $round_length, $chain_starting_block) {
		if (empty($this->currencies)) $this->load_currencies($game);
		$btc_currency = $this->app->get_currency_by_abbreviation("BTC");
		$btc_to_usd = $this->app->currency_price_at_time($btc_currency['currency_id'], 1, time());
		
		$events_per_cycle = count($this->currencies);
		$from_round_offset = ($from_round-1)%$events_per_cycle;
		
		$events = array();
		
		for ($event_i=$from_round-1; $event_i<$to_round; $event_i++) {
			$currency_i = ($from_round_offset+$event_i)%$events_per_cycle;
			
			if ($currency_i == 0) $price_usd = $btc_to_usd['price'];
			else {
				$price_btc = $this->app->currency_price_at_time($this->currencies[$currency_i]['currency_id'], $btc_currency['currency_id'], time());
				$price_usd = $price_btc['price']*$btc_to_usd['price'];
			}
			$price_max_target = $price_usd*1.4;
			$round_price_target = $this->get_readable_number($price_max_target, $price_max_target);
			
			$event_name = $this->currencies[$currency_i]['entity_name']." up to $".$this->app->format_bignum($round_price_target);
			
			$possible_outcomes = [array("title" => "Buy ".$this->currencies[$currency_i]['entity_name'], "entity_id" => $this->currencies[$currency_i]['entity_id']), array("title" => "Sell ".$this->currencies[$currency_i]['entity_name'], "entity_id" => $this->currencies[$currency_i]['entity_id'])];
			
			$event = array(
				"event_index" => $event_i+1,
				"event_starting_block" => $chain_starting_block + $event_i*$round_length,
				"event_final_block" => $chain_starting_block + ($event_i+$events_per_cycle)*$round_length - 1,
				"event_payout_block" => $chain_starting_block + ($event_i+$events_per_cycle*4)*$round_length - 1,
				"event_name" => $event_name,
				"option_name" => "position",
				"option_name_plural" => "positions",
				"payout_rule" => "linear",
				"outcome_index" => null,
				"track_min_price" => 0,
				"track_max_price" => $round_price_target,
				"track_name_short" => $this->currencies[$currency_i]['abbreviation'],
				"possible_outcomes" => $possible_outcomes
			);
			array_push($events, $event);
		}
		
		return $events;
	}
	
	public function set_event_outcome(&$game, &$coin_rpc, $payout_event) {
		return "";
	}
	
	public function regular_actions(&$game) {
		if (empty($this->currencies)) $this->load_currencies($game);
		$btc_currency = $this->app->get_currency_by_abbreviation("BTC");
		$start_q = "INSERT INTO currency_prices (cached_url_id, currency_id, reference_currency_id, price, time_added) VALUES ";
		
		for ($i=1; $i<count($this->currencies); $i++) {
			$currency_price = $this->app->currency_price_at_time($this->currencies[$i]['currency_id'], $btc_currency['currency_id'], time());
			
			if ($currency_price) $last_price_time = max(time()-(3600*24*2), $currency_price['time_added']);
			else {
				$mining_block_id = $game->last_block_id()+1;
				$this_round = $game->block_to_round($mining_block_id);
				$round_firstblock = ($this_round-1)*$game->db_game['round_length']+1;
				$start_block = $game->blockchain->fetch_block_by_id($round_firstblock);
				$last_price_time = $start_block['time_mined'];
			}
			
			$q = $start_q;
			$modulo = 0;
			
			$code = $this->currency_name_to_code[$this->currencies[$i]['name']];
			$poloniex_url = "https://poloniex.com/public?command=returnTradeHistory&currencyPair=BTC_".$code."&start=".$last_price_time."&end=".time();
			$poloniex_response = $this->app->async_fetch_url($poloniex_url, true);
			$cached_url = $this->app->cached_url_info($poloniex_url);
			$poloniex_trades = json_decode($poloniex_response['cached_result'], true);
			
			for ($j=count($poloniex_trades)-1; $j>=0; $j--) {
				$trade = $poloniex_trades[$j];
				$trade_date = new DateTime($trade['date'], new DateTimeZone('UTC'));
				$trade_time = $trade_date->format('U');
				
				if ($trade['type'] == "buy") {
					if ($modulo == 1000) {
						$q = substr($q, 0, strlen($q)-2).";";
						$this->app->run_query($q);
						$modulo = 0;
						$q = $start_q;
					}
					else $modulo++;
					
					$q .= "('".$cached_url['cached_url_id']."', '".$this->currencies[$i]['currency_id']."', '".$btc_currency['currency_id']."', '".$trade['rate']."', '".$trade_time."'), ";
				}
			}
			
			if ($modulo > 0) {
				$q = substr($q, 0, strlen($q)-2).";";
				$this->app->run_query($q);
				$modulo = 0;
			}
		}
	}
}
?>