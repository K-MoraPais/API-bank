<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\Transfer;
use App\Models\Withdrawal;
use App\Models\Account;
use  App\Models\Token;
use DateTime;

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

    function createToken($originId, $destinationId, $email)
    {
        $token = new Token();
        $token->tokenDate = date('Y/m/d H:i:s');
        $token->origin = $originId;
        $token->destination = $destinationId;
        $token->email = $email;
        $token->save();
    }

    function checkToken($originId, $destinationId, $email)
    {

        $currentDate = date('Y/m/d H:i:s');
        $maxTimeStamp = strtotime(" $currentDate + 5 minutes");
        $maxTime = date('Y/m/d H:i:s', $maxTimeStamp);
        $test = Token::whereBetween('tokenDate', ["$currentDate", "$maxTime"])->where('origin', $originId)->where('destination', $destinationId)->get();
        //$test = Token::whereBetween('tokenDate', ['2021/08/13 13:36:14', '2021/08/13 13:41:36'])->get();
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
                            if (!$this->checkToken($request->input('origen'), $request->input('destino'), $request->input('email'))) {
                                $this->createToken($request->input('origen'), 0, $request->input('email'));
                                return $this->checkToken($request->input('origen'), $request->input('destino'), $request->input('email'));
                            }
                            return $this->checkToken($request->input('origen'), 0, $request->input('email'));
                        }

                        return "Email does not belong to an account";
                        // $this->createToken($request->input('origen'), 0);
                        // Account::where('accountId', $request->input('origen'))->update(['balance' => $accountBal - $request->input('monto')]);
                        // $currentBal = Account::where('accountId', $request->input('origen'))->select('balance')->get()[0]->balance;
                        // $Withdrawal->save();
                        //return $this->sendResponse($request->input('origen') . ' , ' . $currentBal, "Withdrawal successful", 200);
                    }
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
