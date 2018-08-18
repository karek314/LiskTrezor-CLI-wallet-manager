<?php
error_reporting(E_ALL ^ E_DEPRECATED);//ignore biginteger.php deprecate warnings
/*
Const values below taken from LISK-PHP repository under MIT LICENSE
https://github.com/karek314/lisk-php
*/
if (file_exists('bytebuffer/main.php')) {
	require_once('bytebuffer/main.php');
} else {
	shell_exec("git clone https://github.com/karek314/bytebuffer");
}
require_once('BigInteger.php');
const LISK_PHP_VER = 1.0;
const LISK_START = 1464109200;
const USER_AGENT = "LISK-PHP ".LISK_PHP_VER." via CURL (Linux, en-GB)";
const NETWORK_HASH = "ed14889723f24ecc54871d058d98ce91ff2f973192075c0155ba2b7b70ad2511"; //mainnet
const MINVERSION = ">=1.0.0";
const OS = "lisk-php-api";
const API_VERSION = "1.0.0";
const SEND_TRANSACTION_ENDPOINT = "/api/transactions";
const ACCOUNTS = "/api/accounts/";
const LSK_BASE = 100000000;
const SEND_FEE = 0.1 * LSK_BASE;
const DATA_FEE = 0.1 * LSK_BASE;
const SIG_FEE = 5 * LSK_BASE;
const DELEGATE_FEE = 25 * LSK_BASE;
const VOTE_FEE = 1 * LSK_BASE;
const MULTISIG_FEE = 5 * LSK_BASE;
const DAPP_FEE = 25 * LSK_BASE;
const SEND_TRANSACTION_FLAG = 0;
const SECOND_SIG_TRANSACTION_FLAG = 1;
const DELEGATE_TRANSACTION_FLAG = 2;
const VOTE_TRANSACTION_FLAG = 3;
const MULTISIG_TRANSACTION_FLAG = 4;
const DAPP_TRANSACTION_FLAG = 5;
const DAPP_IN_TRANSACTION_FLAG = 6;
const DAPP_OUT_TRANSACTION_FLAG = 7;
$config = json_decode(file_get_contents('config.json'),true);
$server = $config['server'];
$needPk = false;
echo "Server loaded from config: ".$server."\n";

$AccountsToIterate = 3;
if(isset($argv[1]) && isset($argv[2])){
	$password = $argv[1];
	$method = strtolower($argv[2]);
} else {
	help();
}
if ($method == "readaccounts") {
	if (isset($argv[3])) {
		if (is_numeric($argv[3])) {
			IterateThroughAccounts($argv[3],$password,$server,$needPk);
		} else {
			IterateThroughAccounts($AccountsToIterate,$password,$server,$needPk);
		}
	} else {
		IterateThroughAccounts($AccountsToIterate,$password,$server,$needPk);
	}
} else if ($method == "send") {
	if(isset($argv[3]) && isset($argv[4]) && isset($argv[5])){
		$fromAccount = $argv[3];
		$amount = (float)$argv[4];
		$Recipient = $argv[5];
		if (isset($argv[7])) {
			$data = $argv[7];
		} else {
			$data = false;
		}
		if (isset($argv[6])) {
			if ($argv[6] == "2ndpass") {
				$doubleSign = true;
			} else {
				$doubleSign = false;
			}
		} else {
			$doubleSign = false;
		}
		echo "\nGetting PublicKey for account id: ".$fromAccount;
		$PublicKey = GetTrezorPublicKeyForAccount("m/44'/134'/0'/0'/".$fromAccount."'",$password);
		echo "\nPublicKey: ".$PublicKey;
		echo "\nPreparing and signing transaction";
		$tx = CreateAndSignTransaction($password, $fromAccount, $PublicKey, $Recipient, $amount*LSK_BASE, $data, $type=SEND_TRANSACTION_FLAG, $doubleSign);
		echo "\nTransaciton ->";
		print_r($tx);
		echo "\nResponse from Lisk Core ->";
		print_r(SendTransaction(json_encode($tx),$server));
	} else {
		echo "\n\nParameters for sending\nAccountID Amount Recipient OptionalDataString\nExample: 0 1.25 34672832L 2ndpass(if you enabled additional signature, please pass string 2ndpass, if not, add nothing)\nWith data: 0 1.25 34672832L false TEST_STRING_DATA";
	}
} else if ($method == "vote") {
	if(isset($argv[3]) && isset($argv[4])){
		$fromAccount = $argv[3];
		$votes = $argv[4];
        $votes_count = substr_count($votes,'+')+substr_count($votes,'-');
        if ($votes_count > 1) {
            $votes = preg_split("/(\\+|-)/", $votes);
            $tvotes = array();
            foreach ($votes as $key => $value) {
                if (strlen($value)==64) {
                    if (strpos($argv[4], '+'.$value) !== false) {
                        $tvotes[] = '+'.$value;
                    } else if (strpos($argv[4], '-'.$value) !== false) {
                        $tvotes[] = '-'.$value;
                    }
                }
            }
            $votes = $tvotes;
        } else {
            $votes = array($votes);
        }
        $asset = array();
        $asset['votes'] = $votes;
		echo "\nGetting PublicKey for account id: ".$fromAccount;
		$PublicKey = GetTrezorPublicKeyForAccount("m/44'/134'/0'/0'/".$fromAccount."'",$password);
		echo "\nGetting account address: ".$fromAccount;
		$address = GetTrezorAddressForAccount("m/44'/134'/0'/0'/".$fromAccount."'",$password);
		echo "\nAddress: ".$address;
		echo "\nPublicKey: ".$PublicKey;
		echo "\nPreparing and signing transaction";
		if (isset($argv[5])) {
			if ($argv[5] == "2ndpass") {
				$doubleSign = true;
			} else {
				$doubleSign = false;
			}
		} else {
			$doubleSign = false;
		}
		$tx = CreateAndSignTransaction($password, $fromAccount, $PublicKey, "", 0, false, $type=VOTE_TRANSACTION_FLAG, $doubleSign, $asset, $address);
		echo "\nTransaciton ->";
		print_r($tx);
		echo "\nResponse from Lisk Core ->";
		print_r(SendTransaction(json_encode($tx),$server));
	} else {
		echo "\n\nParameters for voting\nAccountId publicKeys 2ndpass(if you enabled additional signature, please pass string 2ndpass, if not, add nothing)\n";
		echo '0 +b002f58531c074c7190714523eec08c48db8c7cfc0c943097db1a2e82ed87f84 2ndpass';
		echo "\n";
        echo 'Multiple votes (+publicKey-publicKey+publicKey and so on...)';
	}
} else if ($method == "2ndpass") {
	if(isset($argv[3])){
		$fromAccount = $argv[3];
		echo "\nGetting PublicKey for account id: ".$fromAccount;
		$PublicKey = GetTrezorPublicKeyForAccount("m/44'/134'/0'/0'/".$fromAccount."'",$password);
		echo "\nPublicKey: ".$PublicKey;
		echo "\nSecond signature will be added to Lisk account, using another privatekey from different account path. Mark double signing in any further transactions\nGetting second publicKey";
		$secondPath = "m/44'/134'/".(string)$fromAccount."'/0'/0'";
		echo "\nDoubleSignPath:".$secondPath;
		$SecondPublicKey = GetTrezorPublicKeyForAccount($secondPath,$password);
		echo "\nSecondPublicKey: ".$SecondPublicKey;
		$asset = array();
		$asset['signature']['publicKey'] = $SecondPublicKey;
		echo "\nPreparing and signing transaction";
		$tx = CreateAndSignTransaction($password, $fromAccount, $PublicKey, "", 0, false, $type=SECOND_SIG_TRANSACTION_FLAG, false, $asset);
		echo "\nTransaciton ->";
		print_r($tx);
		echo "\nResponse from Lisk Core ->";
		print_r(SendTransaction(json_encode($tx),$server));
	} else {
		echo "\n\nParameters for sending\nAccountID";
	}
} else {
	help();
}

die("\n\n");

function help(){
	die("\nLiskTrezor CLI Wallet\nFirst parm always trezor password, enter on host.\n\nUsage\nReadAccounts - with optional number to iterate, default=3\nSend - Sending LSK transfer transaction\nVote - vote using publicKeys\n2ndpass - register 2nd additional signature, private key as well derived form same device master seed\n\n");
}

function IterateThroughAccounts($AccountsToIterate,$password,$server,$needPk){
	for ($i=0; $i < $AccountsToIterate; $i++) {
		$accountPath = "m/44'/134'/0'/0'/".$i."'";
		$address = GetTrezorAddressForAccount($accountPath,$password);
		$json = AccountForAddress($address,$server);
		if (isset($json['data'])) {
			if (isset($json['data'][0])) {
				if (isset($json['data'][0]['balance'])) {
					$balance = $json['data'][0]['balance'];
					$balance = $balance/LSK_BASE;
				} else {
					$balance = 0;
				}
			} else {
				$balance = 0;
			}
		} else {
			$balance = 0;
		}
		if ($needPk) {
			$publicKey = GetTrezorPublicKeyForAccount($accountPath,$password);
			echo "\n[Account ID: ".$i."] ".$accountPath."\nAddress:".$address."\nPublicKey:".$publicKey."\nBalance:".$balance." LSK\n";
		} else {
			echo "\n[Account ID: ".$i."] ".$accountPath."\nAddress:".$address."\nBalance:".$balance." LSK\n";
		}
	}
}

/////////////////////
// Trezor specific //
/////////////////////

function GetTrezorAddressForAccount($accountPath,$password){
	$expect_code = "spawn trezorctl lisk_get_address --address \"".$accountPath."\"\nexpect \"Passphrase required: \"\nsleep 0.1\nsend \"".$password."\\r\"\nexpect \"Confirm your Passphrase: \"\nsleep 0.1\nsend \"".$password."\\r\"\ninteract";
	file_put_contents('tmp.sh', $expect_code);
	$address = str_replace(" ","",str_replace("\r","",str_replace("\n","",getLastLine(shell_exec("expect tmp.sh"),2))));
	exec("rm tmp.sh");
	return $address;
}

function GetTrezorPublicKeyForAccount($accountPath,$password){
	$expect_code = "spawn trezorctl lisk_get_public_key --address \"".$accountPath."\"\nexpect \"Passphrase required: \"\nsleep 0.1\nsend \"".$password."\\r\"\nexpect \"Confirm your Passphrase: \"\nsleep 0.1\nsend \"".$password."\\r\"\ninteract";
	file_put_contents('tmp.sh', $expect_code);
	$address = str_replace("public_key:","",str_replace(" ","",str_replace("\r","",str_replace("\n","",getLastLine(shell_exec("expect tmp.sh"),2)))));
	exec("rm tmp.sh");
	return $address;
}

function SignLiskTransactionWithTrezor($accountPath,$jsonTX,$password){
	file_put_contents('tx_tmp.json', $jsonTX);
	$expect_code = "spawn trezorctl lisk_sign_tx --address \"".$accountPath."\" --file tx_tmp.json\nexpect \"Passphrase required: \"\nsleep 0.1\nsend \"".$password."\\r\"\nexpect \"Confirm your Passphrase: \"\nsleep 0.1\nsend \"".$password."\\r\"\ninteract";
	file_put_contents('tmp.sh', $expect_code);
	$signature = str_replace("signature:","",str_replace(" ","",str_replace("\r","",str_replace("\n","",getLastLine(shell_exec("expect tmp.sh"),2)))));
	exec("rm tmp.sh");
	exec("rm tx_tmp.json");
	return $signature;
}

/*
Code below taken from LISK-PHP repository under MIT LICENSE
https://github.com/karek314/lisk-php
*/

//////////////////
// Transactions //
//////////////////
function CreateAndSignTransaction($password, $sendFromAccId, $senderPK, $recipientId, $amount, $data, $type=SEND_TRANSACTION_FLAG, $doubleSign=false, $asset=false, $senderAddress=""){
	if (!$asset) {
		$asset = new stdClass();
	}
	if ($type == SEND_TRANSACTION_FLAG) {
		$fee = SEND_FEE;
	} else if ($type == SECOND_SIG_TRANSACTION_FLAG) {
		$fee = SIG_FEE;
	} else if ($type == DELEGATE_TRANSACTION_FLAG) {
		$fee = DELEGATE_FEE;
	} else if ($type == VOTE_TRANSACTION_FLAG) {
		$fee = VOTE_FEE;
		$recipientId = $senderAddress;
	} else if ($type == MULTISIG_TRANSACTION_FLAG) {
		$fee = MULTISIG_FEE;
	} else if ($type == DAPP_TRANSACTION_FLAG) {
		$fee = DAPP_FEE;
	} else if ($type == DAPP_IN_TRANSACTION_FLAG){
		$fee = SEND_FEE;
	} else if ($type == DAPP_OUT_TRANSACTION_FLAG){
		$fee = SEND_FEE;
	}
	if ($data) {
		$asset = array();
		$asset['data'] = (string)$data;
	}
	$timeOffset = -10;
	$time_difference = GetCurrentLiskTimestamp()+$timeOffset;
	$transaction = array('type' => $type,
						 'amount' => (string)$amount,
						 'fee' => (string)$fee,
						 'recipientId' => $recipientId,
						 'timestamp' => (int)$time_difference,
						 'asset' => $asset
						);
	$transaction['senderPublicKey'] = $senderPK;
	$transactionString = json_encode($transaction);
	$accountPath = "m/44'/134'/0'/0'/".(string)$sendFromAccId."'";
	$transaction['signature'] = SignLiskTransactionWithTrezor($accountPath,$transactionString,$password);
	if ($doubleSign) {
		$transactionString = json_encode($transaction);
		$secondPath = "m/44'/134'/".(string)$fromAccount."'/0'/0'";
		echo "\nDoubleSignPath:".$secondPath;
		$SecondPublicKey = GetTrezorPublicKeyForAccount($secondPath,$password);
		echo "\nPK: ".$SecondPublicKey;
		$transaction['signSignature'] = SignLiskTransactionWithTrezor($secondPath,$transactionString,$password);
	}
	$transaction['id'] = getTxId($transaction);
	return $transaction;
}

////////////////
// Networking //
////////////////

function GetCurrentLiskTimestamp(){
	$current_timestamp = time();
	return $current_timestamp-LISK_START;
}

function SendTransaction($transaction_string,$server){
	$url = $server.SEND_TRANSACTION_ENDPOINT;
	return MainFunction("POST",$url,$transaction_string,true,true,30);
}

function AccountForAddress($address,$server){
	$url = $server.ACCOUNTS.'?address='.$address;
	return MainFunction("GET",$url,false,false,true,7);
}

function MainFunction($method,$url,$body=false,$jsonBody=true,$jsonResponse=true,$timeout=3){
  $ch = curl_init($url);                                       
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_USERAGENT, USER_AGENT);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,$timeout);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
  $headers =  array();
  if ($body) {  
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);                                                             
	  if ($jsonBody) {
		  $headers = array('Content-Type: application/json','Content-Length: ' . strlen($body)); 
    }
  }
  $port = parse_url($url)['port'];
  if (!$port) {
  	if (parse_url($url)['scheme']=='https') {
		  $port="443";
  	} else {
  		$port="80";
  	}
  }
  array_push($headers, "minVersion: ".MINVERSION);
  array_push($headers, "os: ".OS);
  array_push($headers, "version: ".API_VERSION);
  array_push($headers, "port: ".$port);
  array_push($headers, "Accept-Language: en-GB");
  array_push($headers, "nethash: ".NETWORK_HASH);
  array_push($headers, "broadhash: ".NETWORK_HASH);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $result = curl_exec($ch);
  if ($jsonResponse) {
  	$result = json_decode($result, true); 
  }
  return $result;
}

///////////
// Utils //
///////////

function getLastLine($string, $n = 1) {
    $lines = explode("\n", $string);
    $lines = array_slice($lines, -$n);
    return implode("\n", $lines);
}

function getTxId($transaction) {
	$bytes = getTxAssetBytes($transaction);
	$assetSize = $bytes['assetSize'];
	$assetBytes = $bytes['assetBytes'];
	$body = assignTransactionBuffer($transaction, $assetSize, $assetBytes,'');
	$hash = hash('sha256', $body);
	$byte_array = str_split($hash,2);
	$tmp = array();
	for ($i = 0; $i < 8; $i++) {
		$tmp[$i] = $byte_array[7 - $i];
	}
	$tmp = implode("",$tmp);
	$tmp = bchexdec($tmp);
	return $tmp;
}

function assignHexToBuffer($transactionBuffer, $hexValue) {
	$hexBuffer = str_split($hexValue,2);
	foreach ($hexBuffer as $key => $value) {
		$byte = bchexdec($value);
		$transactionBuffer->writeBytes([$byte]);
	}
	return $transactionBuffer;
}

function bchexdec($hex){
    $dec = 0;
    $len = strlen($hex);
    for ($i = 1; $i <= $len; $i++) {
        $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
    }
    return $dec;
}

function strtohex($string){
    $hex = '';
    for ($i=0; $i<strlen($string); $i++){
        $ord = ord($string[$i]);
        $hexCode = dechex($ord);
        $hex .= substr('0'.$hexCode, -2);
    }
    return strToUpper($hex);
}

function getTxAssetBytes($transaction){
	if ($transaction['type'] == SEND_TRANSACTION_FLAG) {
		if ($transaction['asset'] == new stdClass()) {
			$tmp = array('assetBytes' => null,
					 	 'assetSize' => 0);
		} else {
			$data = strtohex($transaction['asset']['data']);
			$hexBuffer = str_split($data,2);
			$byteBuffer = array();
			foreach ($hexBuffer as $key => $value) {
				$byte = bchexdec($value);
				$byteBuffer[] = $byte;
			}
			$tmp = array('assetBytes' => $byteBuffer,
					 	 'assetSize' => count($byteBuffer));
		}
	} else if ($transaction['type'] == SECOND_SIG_TRANSACTION_FLAG) {
		$hash = $transaction['asset']['signature']['publicKey'];
		$hexBuffer = str_split($hash,2);
		$byteBuffer = array();
		foreach ($hexBuffer as $key => $value) {
			$byte = bchexdec($value);
			$byteBuffer[] = $byte;
		}
		$tmp = array('assetBytes' => $byteBuffer,
					 'assetSize' => 32);
	} else if ($transaction['type'] == DELEGATE_TRANSACTION_FLAG) {
		$username = strtohex($transaction['asset']['delegate']['username']);
		$hexBuffer = str_split($username,2);
		$byteBuffer = array();
		foreach ($hexBuffer as $key => $value) {
			$byte = bchexdec($value);
			$byteBuffer[] = $byte;
		}
		$tmp = array('assetBytes' => $byteBuffer,
					 'assetSize' => count($byteBuffer));
	} else if ($transaction['type'] == VOTE_TRANSACTION_FLAG) {
		$votes = $transaction['asset']['votes'];
		$votes = implode('',$votes);
		//$votes = str_replace('+', '2B', $votes);
		//$votes = str_replace('-', '2D', $votes);
		$votes = strTohex($votes);
		$hexBuffer = str_split($votes,2);
		$byteBuffer = array();
		foreach ($hexBuffer as $key => $value) {
			$byte = bchexdec($value);
			$byteBuffer[] = $byte;
		}
		$tmp = array('assetBytes' => $byteBuffer,
					 'assetSize' => count($byteBuffer));
	}
	return $tmp;
}

function assignTransactionBuffer($transaction, $assetSize, $assetBytes, $options) {
		$transactionBuffer = BBStream::factory('');
		$transactionBuffer->isLittleEndian = false;
		$transactionBuffer->writeInt($transaction['type'], 8);//1
		$transactionBuffer->writeInt($transaction['timestamp']); //4
		$transactionBuffer = assignHexToBuffer($transactionBuffer, $transaction['senderPublicKey']);
		if (array_key_exists('requesterPublicKey', $transaction)) {//32
			assignHexToBuffer($transactionBuffer, $transaction['requesterPublicKey']);
		}
		if (array_key_exists('recipientId', $transaction)){ //8
			$recipient = $transaction['recipientId'];
			$recipient = substr($recipient, 0, -1);
			$recipient_bi = new Math_BigInteger($recipient);
			$bytes = unpack('C*',$recipient_bi->toBytes());
			$c = count($bytes);
			if ($c!=8) {
				for ($i = 0; $i < 8-$c; $i++) {
					$transactionBuffer->writeBytes([0]);
				}
				for ($i = 1; $i <= $c; $i++) {
					$transactionBuffer->writeBytes([$bytes[$i]]);
				}
			} else {
				for ($i = 1; $i <= 8; $i++) {
					$transactionBuffer->writeBytes([$bytes[$i]]);
				}
			}
		} else {
			for ($i = 0; $i <= 8; $i++) {
				$transactionBuffer->writeBytes([0]);
			}
		}
		$bytes = BBUtils::intToBytes($transaction['amount'],64);
		$bytes = array_reverse($bytes);
		$transactionBuffer->writeBytes($bytes);
		if (array_key_exists('data', $transaction)) {//64
			$transactionBuffer = assignHexToBuffer($transactionBuffer, $transaction['data']);//64
		}
		if ($assetSize > 0) {
			for ($i = 0; $i < $assetSize; $i++) {
				$transactionBuffer->writeBytes([$assetBytes[$i]]);
			}
		}
		if($options != 'multisignature') {
			if (array_key_exists('signature', $transaction)) {
				$transactionBuffer = assignHexToBuffer($transactionBuffer, $transaction['signature']);//64
			}
			if (array_key_exists('signSignature', $transaction)) {
				$transactionBuffer = assignHexToBuffer($transactionBuffer, $transaction['signSignature']);//64
			}
		}
		$transactionBuffer->rewind();
		$size = $transactionBuffer->size();
		$bytes = $transactionBuffer->readBytes($size);
		$string = call_user_func_array("pack", array_merge(array("C*"), $bytes));
		return $string;
}

?>