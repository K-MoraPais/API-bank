<?php

namespace App\Http\Controllers;

date_default_timezone_set("America/Argentina/Buenos_Aires");

use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\Transfer;
use App\Models\Withdrawal;
use App\Models\Account;
use  App\Models\Token;
use App\Mail\Mailer;
use Illuminate\Support\Facades\Mail;


class ApiController extends Controller
{
    function sendResponse($result, $message, $code)
    {
        $response = [
            "success" => true,
            "data" => $result,
            "message" => $message
        ];
        return response()->json($response, $code);
    }

    function createToken($transactionID, $accountID)
    {
        $token = new Token();
        $randNum = rand(111111, 999999);
        $currentDate = date('Y/m/d H:i:s');
        $expTimeStamp = strtotime(" $currentDate + 5 minutes");
        $tokenExp = date('Y/m/d H:i:s', $expTimeStamp);

        $token->tokenDate = $currentDate;
        $token->expiration = $tokenExp;
        $token->value = $randNum;
        $token->transactionID = $transactionID;
        $token->accountID = $accountID;
        Mail::to('kevinmorapais532@gmail.com')->send(new Mailer($token));
        $token->save();
    }

    function checkToken($originId, $destinationId, $email, $amount)
    {
        if ($destinationId) {
            // $test = Token::where('expiration', '>', date('Y/m/d H:i:s'))->where('origin', $originId)->where('destination', 0)->get();
        } else {
            $test = Token::where('expiration', '>', date('Y/m/d H:i:s'))->join('withdrawals', 'withdrawalId', '=', 'transactionID')->join('accounts', 'accounts.accountId', '=', 'tokens.accountID')->select('tokens.*')->get();
        }
        return $test;
    }

    function handler(Request $request)
    {

        $requestType = $request->input('tipo');
        switch ($requestType) {
            case 'crear':
                if (Account::where('email', $request->input('email'))->select('balance')->exists()) {
                    return $this->sendResponse("", "Email is already in use", 404);
                }
                $Account = new Account();
                $Account->email = $request->input('email');
                $Account->balance = 0;
                $Account->save();
                $newAccountId = Account::where('email', $request->input('email'))->select('accountId')->get()[0]->accountId;
                return $this->sendResponse($newAccountId, "Account created", 201);
                break;
            case 'deposito':
                try {
                    $Deposit = new Deposit();
                    $Deposit->destino = $request->input('destino');
                    $Deposit->monto = $request->input('monto');
                    if (!Account::where('accountId', $request->input('destino'))->exists()) {
                        return $this->sendResponse("Error", "Account does not exist", 404);
                    } else {
                        $accountBal =  Account::where('accountId', $request->input('destino'))->select('balance')->get()[0]->balance;
                        Account::where('accountId', $request->input('destino'))->update(['balance' => $accountBal + $request->input('monto')]);
                        $Deposit->save();
                        $currentBal = Account::where('accountId', $request->input('destino'))->select('balance')->get()[0]->balance;
                        return $this->sendResponse($request->input('destino') . ' , ' . $currentBal, "Normal deposit", 200);
                    }
                } catch (\Exception $e) {
                    return $e;
                }
                break;
            case 'retiro':
                try {
                    $Withdrawal = new Withdrawal();
                    $Withdrawal->origen = $request->input('origen');
                    $Withdrawal->monto = $request->input('monto');
                    $Withdrawal->date = date('Y/m/d H:i:s');
                    if (!Account::where('accountId', $request->input('origen'))->exists()) {
                        return $this->sendResponse("Error", "Origin does not exist", 404);
                    }
                    $accountBal =  Account::where('accountId', $request->input('origen'))->select('balance')->get()[0]->balance;
                    if ($accountBal < $request->input('monto')) {
                        return $this->sendResponse("Error", "Withdrawal amount exceeds account balance", 404);
                    }
                    if ($request->input('monto') > 1000) {
                        if (Account::where('email', $request->input('email'))->exists()) {
                            if ($request->input('token')) {
                                return "WIP";
                            }
                            //Here
                            if (count($this->checkToken($request->input('origen'), null, $request->input('email'), $request->input('monto'))) == 0) {
                                $Withdrawal->state = 0;
                                $Withdrawal->save();
                                $latestWithdrawal = Withdrawal::orderBy('date', 'desc')->limit(1)->get();
                                $account = Account::where('email', $request->input('email'))->get();
                                $this->createToken($latestWithdrawal[0]->withdrawalId, $account[0]->accountId);
                                return $this->checkToken($request->input('origen'), null, $request->input('email'), $request->input('monto'));
                                //$this->checkToken($request->input('origen'), $request->input('destino'), $request->input('email'));
                            }
                            return "a";
                            //$this->checkToken($request->input('origen'), 0, $request->input('email'));
                        }
                        return "Email does not belong to an account";
                    }
                    Account::where('accountId', $request->input('origen'))->update(['balance' => $accountBal - $request->input('monto')]);
                    $currentBal = Account::where('accountId', $request->input('origen'))->select('balance')->get()[0]->balance;
                    $Withdrawal->save();
                    return $this->sendResponse($request->input('origen') . ' , ' . $currentBal, "Withdrawal successful", 200);
                } catch (\Exception $e) {
                    return $e;
                }
                break;
            case 'transferir':
                try {
                    $Transfer = new Transfer();
                    $Transfer->destino = $request->input('destino');
                    $Transfer->origen = $request->input('origen');
                    $Transfer->monto = $request->input('monto');
                    if (Account::where('accountId', $request->input('origen'))->doesntExist() || Account::where('accountId', $request->input('destino'))->doesntExist()) {
                        return $this->sendResponse("Error", "Account does not exist", 404);
                    } else {
                        $originAccountBal =  Account::where('accountId', $request->input('origen'))->select('balance')->get()[0]->balance;
                        $targetAccountBal = Account::where('accountId', $request->input('destino'))->select('balance')->get()[0]->balance;
                        if ($originAccountBal < $request->input('monto')) {
                            return $this->sendResponse("Error", "Transfer amount exceeds account balance", 404);
                        }
                        Account::where('accountId', $request->input('origen'))->update(['balance' => $originAccountBal - $request->input('monto')]);
                        Account::where('accountId', $request->input('destino'))->update(['balance' => $targetAccountBal + $request->input('monto')]);
                        $accountOne = (object)[
                            'id' => $request->input('origen'),
                            'balance' => $originAccountBal - $request->input('monto')
                        ];
                        $accountTwo = (object)[
                            'id' => $request->input('destino'),
                            'balance' => $targetAccountBal + $request->input('monto')
                        ];
                        $dataBody = [$accountOne, $accountTwo];
                        return $this->sendResponse($dataBody, "Transfer successful", 200);
                    }
                    $Transfer->save();
                    return "Transfer";
                } catch (\Illuminate\Database\QueryException $e) {
                    return $e;
                }
                break;
        }
    }

    public function balance($id)
    {
        $Deposit = Deposit::where('idDeposit', $id)
            ->select('idDeposit', 'nombre', 'img')
            ->get();
        return $this->sendResponse($Deposit, "Deposit obtenida correctamente", 200);
    }
}
