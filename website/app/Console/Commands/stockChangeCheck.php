<?php

/**
 * Created by: Paul Davidson.
 * Authors: Paul Davidson and Josh Gerlach
 */

namespace App\Console\Commands;

use App\Stock;
use App\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\User as User;
use App\StockHistory as History;
use phpDocumentor\Reflection\Types\Array_;
use Mail;
use App\Mail\StockChanged;


class stockChangeCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stockChange:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for a change in a stock and mail all appropriate users';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $users = User::all();
        $setPercentage = 2;

        $currencyConverver = new \CurrencyConverter;
        $exchangeRate = $currencyConverver->USDtoAUD(1.00);

        foreach ($users as $user) {

            $buySell = Array();
            $emailArray = Array();

            // Portfolio Value will be the ranking
            $user->portfolio = $user->balance;
            $user->save();

            foreach ($user->tradingAccounts()->get() as $tradeAccount) {
                foreach ($tradeAccount->transactions()->get() as $transaction) {
                    $buySell[$transaction->stock()->first()->id] = 0;
                }
                foreach ($tradeAccount->transactions()->get() as $transaction) {
                    $buySell[$transaction->stock()->first()->id] += $transaction->bought;
                    $buySell[$transaction->stock()->first()->id] -= $transaction->sold;
                }
                foreach ($buySell as $key => $value) {
                    // Check if the user has the stock
                    if ($value > 0) {
                        //Get stock from Database
                        $stock = Stock::where('id', $key)->first();
                        //Get Stock name
                        $stock_name = $stock->stock_name;
                        //Get Stock symbol/code
                        $stock_symbol = $stock->stock_symbol;

                        $currentPrice = $stock->current_price;

                        //Get the latest price of the selected Stock
                        $currentCompanyInfo = new \CurrentCompanyStockInformation;
                        $stockInfo = $currentCompanyInfo->currentDetails($stock_symbol, $stock->market);

                        //If the latest Stock information is not null, set as current price and update the database
                        if ($stockInfo != null)
                        {
                            print_r("stockInfo not null, got latest price");
                            //If the Stock is not ASX stock, convert from US to AUD
                            if($stock->market != 'ASX')
                                $currentPrice = $stockInfo["curr_price"]["price"] * $exchangeRate;
                            else
                                //otherwise, just set the price that is already in AUD
                                $currentPrice = $stockInfo["curr_price"]["price"];

                            //Set the current price in the database and save
                            $stock->current_price = $currentPrice;
                            $stock->save();
                        }

                        // Append to the Portfolio Value
                        $user->portfolio += $currentPrice * $value;
                        $user->save();

                        // Buy Value
                        $buyPrice = Transaction::where('stock_id', $key)->where('trade_account_id', $tradeAccount->id)->orderBy('id', 'desc')->first()->price;
                        // Growth Percentage
                        $growth = ($currentPrice - $buyPrice) / $buyPrice * 100;
                        // If growth is greater than percentage, add to email array
                        if ($growth >= $setPercentage || $growth <= -1 * $setPercentage) {
                            array_push($emailArray, array("trading_account" => $tradeAccount->name,"stock_name" => $stock_name, "stock_symbol" => $stock_symbol, "growth" => $growth . "%"));
                        }
                    }
                }
            }

            print_r($emailArray);

            if (count($emailArray) > 0) {
                $content = [
                    'title' => 'Your stocks are starting to change significantly.',
                    'info' => $emailArray,
                    'name' => $user->name
                ];

                Mail::to($user->email)->send(new StockChanged($content));
            }

        }

    }
}
