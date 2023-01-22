<?php

$fee_sums = ['uluna' => 0, 'uusd' => 0];

$latest_failed = null;
$lost_tx = ['sum' => 0];
$lost_fees = ['uluna' => 0, 'uusd' => 0];
$walletfees = [];


function get($url, $add_headers = []) {
	$ch = curl_init();

	// set url
	curl_setopt($ch, CURLOPT_URL, $url);

	//return the transfer as a string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$headers = [
		'Accept: application/json'
	];
	foreach($add_headers as $key => $val) {
		$headers[] = $key . ': ' . $val;
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	// $output contains the output string
	$output = curl_exec($ch);

	// close curl resource to free up
	// system resources
	curl_close($ch);

	return $output;
}

function sort_losses($a, $b) {
	if(!isset($a['uluna'])) {
		$a['uluna'] = 0;
	}
	if(!isset($b['uluna'])) {
		$b['uluna'] = 0;
	}
	if($a['uluna'] > $b['uluna']) {
		return -1;
	} elseif($a['uluna'] < $b['uluna']) {
		return 1;
	} else {
		return 0;
	}
}

function checkTransactions($block) {
	global $walletfees, $lost_tx, $lost_fees, $latest_failed;

	$sends = [];
	$result = get('http://localhost:1317/cosmos/base/tendermint/v1beta1/blocks/' . $block);

	$json = json_decode($result, true);

	if(!isset($json['block']['data']['txs'])) {
		die("ERR\n");
	}

	$time = null;
	if(isset($json['block']['header']['time'])) {
		$time = $json['block']['header']['time'];
		$time = substr($time, 0, 26);
		try {
			$dt = new DateTime($time);
			$time = $dt->format('Y-m-d H:i:s');
			unset($dt);
		} catch(\Exception $e) {
			print "Unparsable time $time on block $block\n";
		}
	}

	$tx = $json['block']['data']['txs'];
	foreach($tx as $trans) {
		$hash = strtoupper(hash('sha256', base64_decode($trans)));
		$txdata = get('http://localhost:1317/cosmos/tx/v1beta1/txs/' . $hash);
		$txdata = json_decode($txdata, true);
		if(!isset($txdata['tx_response'])) {
			// invalid data
			continue;
		}

		$response = $txdata['tx_response'];
		$gas_used = (int)$response['gas_used'];
		$gas_wanted = (int)$response['gas_wanted'];
		$msg = $response['raw_log'];

		if($gas_used > $gas_wanted || substr($msg, 0, 11) === 'out of gas ') {
			$lost_tx['sum']++;
			$fees = $response['tx']['auth_info']['fee']['amount'];
			$msgs = $response['tx']['body']['messages'];
			$sender = null;
			$type = 'unknown';
			$latest_failed = $block;
			foreach($msgs as $msg) {
				$type = $msg['@type'];
				switch($type) {
					case '/cosmos.bank.v1beta1.MsgSend':
						$sender = $msg['from_address'];
						break 2;
					case '/terra.wasm.v1beta1.MsgExecuteContract':
						$sender = $msg['sender'];
						break 2;
					case '/cosmos.staking.v1beta1.MsgDelegate':
						$sender = $msg['delegator_address'];
						break 2;
					case '/cosmos.authz.v1beta1.MsgExec':
						$sender = $msg['grantee'];
						break 2;
					case '/cosmos.gov.v1beta1.MsgSubmitProposal':
						$sender = $msg['proposer'];
						break 2;
					case '/cosmos.distribution.v1beta1.MsgWithdrawDelegatorReward':
						$sender = $msg['delegator_address'];
						break 2;
					case '/ibc.applications.transfer.v1.MsgTransfer':
						$sender = $msg['sender'];
						break 2;
					default:
						var_dump($msg);
						exit;
				}
			}

			if(!isset($lost_tx[$type])) {
				$lost_tx[$type] = 0;
			}
			$lost_tx[$type]++;

			if(!$sender) {
				$sender = 'unknown';
			}

			if(!isset($walletfees[$sender])) {
				$walletfees[$sender] = ['tx' => 0];
			}

			foreach($fees as $fee) {
				$den = $fee['denom'];
				if(!isset($lost_fees[$den])) {
					$lost_fees[$den] = 0;
				}
				$lost_fees[$den] += round((int)($fee['amount']) / 1000000, 6);

				if(!isset($walletfees[$sender][$den])) {
					$walletfees[$sender][$den] = 0;
				}
				$walletfees[$sender][$den] += round((int)($fee['amount']) / 1000000, 6);
				$walletfees[$sender]['tx']++;
			}

		}
	}
	print "\r " . $time . " - $block: " . $lost_tx['sum'] . ' / ' . $lost_fees['uluna'] . ' / ' . $lost_fees['uusd'];
	
	if($block % 100 === 0) {
		uasort($walletfees, 'sort_losses');
		file_put_contents(__DIR__ . '/lost_fees.json', json_encode(['wallets' => $walletfees, 'sums' => $lost_fees, 'txs' => $lost_tx, 'latest_failed' => $latest_failed]));
	}

	return $sends;
}

$start = $argv[1];
$end = $argv[2];

print "\n";
for($b = $start; $b <= $end; $b++) {
	checkTransactions($b);
}

uasort($walletfees, 'sort_losses');
file_put_contents(__DIR__ . '/lost_fees.json', json_encode(['wallets' => $walletfees, 'sums' => $lost_fees, 'txs' => $lost_tx, 'latest_failed' => $latest_failed]));
