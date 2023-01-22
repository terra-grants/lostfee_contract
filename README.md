# Lost Fees Reimbursement
This is the code for the reimbursement of lost fee's during the period of Sept 21 - Sept 28th.
On September 21st, the 1.2% tax burn activated on Luna Classic.  Transactions on testnet were successful, but transactions on columbus-5 had gas computations straddling the border of success and fail.  Thus, some transactions failed and the tax was levied while the transaction did not go through.  

Dedicated work by StrathCole have identified the transactions that failed from Sept 21 to Sept 28th (when the station wallet was fixed).  The following shows the number of transactions that failed.

"txs" : {

      "/cosmos.authz.v1beta1.MsgExec" : 4,
      "/cosmos.bank.v1beta1.MsgSend" : 10460,
      "/cosmos.distribution.v1beta1.MsgWithdrawDelegatorReward" : 1,
      "/cosmos.gov.v1beta1.MsgSubmitProposal" : 6,
      "/cosmos.staking.v1beta1.MsgDelegate" : 2,
      "/ibc.applications.transfer.v1.MsgTransfer" : 1,
      "/terra.wasm.v1beta1.MsgExecuteContract" : 453,
      "sum" : 10927
   }

The total amount of incorrect taxes charged is 295M LUNC.

Distribution will be in the lost_fee.json 
Please note this does not apply to validator delegations.
