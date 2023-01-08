## About the contract

This is a smart contract for distribution of lost funds from the tax back in September 

## Distribution logic


## Airdrop whitelist
This whitelist is provided by StrathCole.


## Usage

You can interact with this smart contract using this **CLASSIC MAINNET** address:
```
TBD
```
The contract has been instantiated without the `--set-signer-as-admin` flag, making it immutable (the contract cannot be migrated).

## ExecuteMsg

### Distribute
The following messages need to be sent to distribute the funds from the contract.

To distribute LUNC:
```
{"distribute":{"denom":"uluna"}}
```

## Query

### Get Config
To get the current configuration of the contract:
```
{"config":{}}
```
