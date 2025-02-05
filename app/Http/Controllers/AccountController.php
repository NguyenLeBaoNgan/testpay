<?php

namespace App\Http\Controllers;

use App\Models\Account;

use App\DTOs\AccountDTO;



class AccountController extends Controller
{
    public function index(){
       return Account::all();
    }
    public function store(AccountDTO $accountDTO)
    {
        $account = Account::create([
            "user_id" => auth()->id(),
            "account_number" => $accountDTO->account_number,
            "account_name" => $accountDTO->account_name,
            "account_type" => $accountDTO->account_type,
            // "bank_name" => $accountDTO->bank_name,
            // "e_wallet_provider" => $accountDTO->e_wallet_provider,
            "is_default" => $accountDTO->is_default
        ]);

        if ($account->is_default) {
            Account::where('user_id', auth()->id())
                ->where('id', '!=', $account->id)
                ->update(['is_default' => false]);
        }

        return response()->json(['success' => true, 'account' => $account]);
    }


    public function listAccounts()
    {
        $accounts = Account::where('user_id', auth()->id())->get();
        return response()->json(['success' => true, 'accounts' => $accounts]);
    }

    public function update(AccountDTO $accountDTO, string $accountId)
    {
        $account = Account::where('id', $accountId)->where('user_id', auth()->id())->firstOrFail();

        $account->update($accountDTO->toArray());

        if ($accountDTO->is_default) {
            Account::where('user_id', auth()->id())
                ->where('id', '!=', $account->id)
                ->update(['is_default' => false]);
        }

        return response()->json(['success' => true, 'account' => $account]);
    }


    public function destroy(string $accountId)
    {
        $account = Account::where('id', $accountId)->where('user_id', auth()->id())->firstOrFail();

        $account->delete();

        return response()->json(['success' => true, 'message' => 'Account deleted successfully']);
    }


    public function setDefaultAccount(string $accountId)
    {
        $account = Account::where('id', $accountId)->where('user_id', auth()->id())->firstOrFail();

        $account->update(['is_default' => true]);

        Account::where('user_id', auth()->id())
            ->where('id', '!=', $account->id)
            ->update(['is_default' => false]);

        return response()->json(['success' => true, 'message' => 'Default account updated successfully']);
    }
}
