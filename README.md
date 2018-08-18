# LiskTrezor CLI Wallet Manager

This is very simple, very tricky but secure, wallet manager for Trezor Lisk hardware wallet.

## Requirements
-PHP 7.0 and higher<br>
-Python3 and pip3<br>

## Setup
```sh
bash setup.sh
```

## Config
In config.json you can configure lisk core server, it's predefined with testnet server.

## Usage
Tested with MacOS and Ubuntu, will rather work with Windows too.<br> If asked by trezor, always confirm password entering via host.<br>
Connect Trezor<br>
Display help
```sh
php ltcli.php

Server loaded from config: http://77.55.220.191:7000

LiskTrezor CLI Wallet
First parameter always trezor password, enter on host.

Usage
ReadAccounts - with optional number to iterate, default=3
Send - Sending LSK transfer transaction
Vote - vote using publicKeys
2ndpass - register 2nd additional signature, private key as well derived form same device master seed
```

### ReadAccounts
Listing accounts (default 3 accounts)
```sh
php ltcli.php YOUR_PASSWORD_ENTERED_ON_HOST ReadAccounts
```
Listing more accounts (example 8)
```sh
php ltcli.php YOUR_PASSWORD_ENTERED_ON_HOST ReadAccounts 8
```
For each account address retrieval you need to click "Host" on Trezor device.

### Send
```sh
php ltcli.php YOUR_PASSWORD_ENTERED_ON_HOST Send        
Server loaded from config: http://77.55.220.191:7000


Parameters for sending
AccountID Amount Recipient OptionalDataString
Example: 0 1.25 34672832L 2ndpass(if you enabled additional signature, please pass string 2ndpass, if not, add nothing)
With data: 0 1.25 34672832L false TEST_STRING_DATA
```

Sending simple transaction with one signature account
```sh
php ltcli.php YOUR_PASSWORD_ENTERED_ON_HOST Send 0 1.55 34672832L
```
Double signed transaction (enabled 2nd pass)
```sh
php ltcli.php YOUR_PASSWORD_ENTERED_ON_HOST Send 0 1.55 34672832L 2ndpass
```

### Vote
```sh
php ltcli.php vg817982j3nbhrau2i Vote        
Server loaded from config: http://77.55.220.191:7000


Parameters for voting
AccountId publicKeys 2ndpass(if you enabled additional signature, please pass string 2ndpass, if not, add nothing)
0 +b002f58531c074c7190714523eec08c48db8c7cfc0c943097db1a2e82ed87f84 2ndpass
Multiple votes (+publicKey-publicKey+publicKey and so on...)
```

Sending vote transaction with one signature account
```sh
php ltcli.php vg817982j3nbhrau2i Vote 0 +473c354cdf627b82e9113e02a337486dd3afc5615eb71ffd311c5a0beda37b8c
```
Double signed vote transaction (enabled 2nd pass)
```sh
php ltcli.php vg817982j3nbhrau2i Vote 0 +473c354cdf627b82e9113e02a337486dd3afc5615eb71ffd311c5a0beda37b8c 2ndpass
```

### 2ndpass
Adding second signature - last parameter is account id. (Please note that right now Trezor firmware has mistake and you won't be able to send anything from account with 2ndpass, but once it's fixed it will work fine)
```sh
php ltcli.php vg817982j3nbhrau2i 2ndpass 1
```
While normal accounts are retrieved with path
```sh
m/44'/134'/0'/0'/0'
m/44'/134'/0'/0'/1'
m/44'/134'/0'/0'/2'
```
etc... respectively, second signature will be used from
```sh
m/44'/134'/1'/0'/0'
m/44'/134'/2'/0'/0'
m/44'/134'/3'/0'/0'
```
It lifts security from 2^126 to 2^127 in case of Lisk. (ed25519)
