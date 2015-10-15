<?php


class betting{
	const W_PERCENT_COMMISSION = 15;
	const P_PERCENT_COMMISSION = 12;
	const E_PERCENT_COMMISSION = 18;
	
	/*
	 * Main 
	 */
	public function run(){
		$this->get_input($result, $bets_list);

		//process product W
		if (isset($bets_list['W'])){
			$yields = $this->product_w_calculator($result[1], $bets_list['W']);
			echo "Win:$result[1]:".'$'."$yields\n";
		}
		
		//process product P
		if (isset($bets_list['P'])){
			$yields = $this->product_p_calculator($result, $bets_list['P']);
			foreach ($result as $win_no){
				echo 'Place:'.$win_no.':$'.$yields[$win_no]."\n";
			}
		}
		
		
		//process product E
		if (isset($bets_list['E'])){
			$yields = $this->product_e_calculator($result[1], $result[2], $bets_list['E']);
			echo "Exacta:$result[1],$result[2]:".'$'."$yields\n";
		}
		
	}
	
	/*
	 * Process production EXACTA
	 */
	private function product_e_calculator($no1 = 0, $no2 = 0, $bets_list = array()){
		$total_money_amount  = 0;
		$winner_money_amount = 0;
		$percent_commission  = self::E_PERCENT_COMMISSION;
		
		foreach ($bets_list as $bet){
			$total_money_amount += $bet[1];//total money amount
			
			$user_bet = explode(",", $bet[0]);
			if (($no1 == $user_bet[0]) && ($no2 == $user_bet[1])){//I win
				$winner_money_amount += $bet[1];
			}
		}
		
		$remaining = round(($total_money_amount * (100 - $percent_commission) / 100), 2);
		return round(($remaining / $winner_money_amount), 2);
	}
	
	/*
	 * Process production PLACE
	 */
	private function product_p_calculator($result = array(), $bets_list = array()){
		$total_money_amount  = 0;
		$winner_money_amount = array();
		$percent_commission  = self::P_PERCENT_COMMISSION;
		$yields              = array();
		
		foreach ($result as $no){
			$winner_money_amount[$no] = 0;
		}
		
		foreach ($bets_list as $bet){
			$total_money_amount += $bet[1];//total money amount
			if (in_array($bet[0], $result)){//I win
				$win_no = $bet[0];
				
				$winner_money_amount[$win_no] += $bet[1];
			}
		}
		
		$remaining = round(($total_money_amount * (100 - $percent_commission) / 100), 2);
		$one_third = round(($remaining / 3), 2);
		
		foreach ($result as $no){
			$yields[$no] = ($winner_money_amount[$no] > 0)? round(($one_third / $winner_money_amount[$no]), 2) : 0;
		}
		
		return $yields;
	}
	
	/*
	 * Process production WIN
	 */
	private function product_w_calculator($no1 = 0, $bets_list = array()){
		$total_money_amount  = 0;
		$winner_money_amount = 0;
		$percent_commission  = self::W_PERCENT_COMMISSION;
		
		foreach ($bets_list as $bet){
			$total_money_amount += $bet[1];//total money amount
			if ($bet[0] == $no1){//I win
				$winner_money_amount += $bet[1];
			}
		}
		
		$remaining = round(($total_money_amount * (100 - $percent_commission) / 100), 2);
		
		return round(($remaining / $winner_money_amount), 2);
		
	}
	
	/*
	 * Get bets list from INPUT
	 */
	private function get_input(&$result, &$bets_list){
		$stdin = STDIN;
		$callbacks = [
				//'line'    => function(&$line) { echo $line; },
				'feof'    => function(&$line_n) { if ($line_n > 0) echo "---------------Start Processing---------------\n";},
				'timeout' => function() { $this->show_help(); },
				];
		$bets = $this->non_block_process($stdin, $callbacks);
		
		if (empty($bets)){
			echo "---------------Error: Input data is invalid---------------\n";
			$this->show_help();
		}
		
		$result    = array();
		$bets_list = array();
		foreach ($bets as $bet){
			$line = explode(":", $bet);
			
			if (strtolower($line[0]) == 'result'){//this is result of race
				unset($line[0]);
				if (isset($line[1]) && isset($line[2]) && isset($line[3])){
					$result = $line;
				}
			}
			else{
				if (isset($line[1]) && isset($line[2]) && isset($line[3])){
					$product = strtoupper($line[1]);
					
					$bets_list[$product][] = array($line[2], $line[3]); 
				}
			}
		}
		if (empty($result) || empty($bets_list)){
			echo "---------------Error: Input data is invalid---------------\n";
			$this->show_help();
		}
		
	}
	
	/*
	 * Show help message
	 */
	private function show_help(){
		global $argv;
		
		echo <<<HELP

usage: /usr/bin/php $argv[0] < bets_list.txt  OR cat bets_list.txt | /usr/bin/php $argv[0]
		
Notice: The list of bets data in file bets_list.txt


HELP;
		exit(0);
	}
	
	/*
	 * Non block to read data from STREAM
	 */
	private function non_block_process(&$stream, array $callbacks = [], $timeoutSeconds = 3) {
		stream_set_blocking($stream, 0);
	
		$defaultCallbacks['line'] = function(&$line) {};
		$defaultCallbacks['timeout'] = $defaultCallbacks['feof'] = function() {};
	
		$callbacks = $callbacks + $defaultCallbacks;
	
		$timeoutStarted = false;
		$timeout = null;
	
		$stream_data = array();
		$line_n      = 0;
		while (1) {
			while (false !== ($line = fgets($stream))) {
				//$callbacks['line']($line);
				$line = trim($line);
				if (!empty($line)){
					$stream_data[] = $line;
					$line_n++;
				}
	
				if ($timeoutStarted) {
					$timeoutStarted = false;
					$timeout = null;
				}
			}
	
			if (feof($stream)) {
				$callbacks['feof']($line_n);
				break;
			}
	
			if (null === $timeout) {
				$timeout = time();
				$timeoutStarted = true;
				continue;
			}
	
			if (time() > $timeout + $timeoutSeconds) {
				$callbacks['timeout']();
				break;
			}
		};
		
		return $stream_data;
	}
}