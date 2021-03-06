{{--/**--}}
{{--* Created by: Paul Davidson.--}}
{{--* Authors: Paul Davidson, Josh Gerlach and Abnezer Yhannes--}}
{{--*/--}}

@include('layouts.header')

@section('title','Pineapple')

<head>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.bundle.min.js"></script>

    <style type="text/css">
        .stock-table-data {
            width: 50%;
        }
    </style>

    {{ !date_default_timezone_set('Australia/Melbourne') }}
</head>

<body class="background">

@include('layouts.navbar')

    @section('charter')
        <div class="bg">
        <div class="stock content-box">
            <div class="col-xs-12" style="padding-left: 0">
                <h2 style='float:left; font-family: "Raleway", sans-serif;'>{{ $stock->stock_name }} &nbsp; ({{$stock->market}})</h2>
                <h4 style="font-family: 'Raleway', sans-serif; float:right;">{{ date('d/m/y') }}</h4>
            </div>

            <!--Current 1stock quick stats-->
            <div id="current-stock-price" style="padding: 0;  margin-bottom: 5%; width: 50%">
                <br />
                <div id="stock-current-price" class="col-xs-12s" style="font-size: 200%; font-weight: bold; ">${{number_format($currentDataArray["curr_price"]["price"],2)}}AUD</div>
                <div class="col-xs-12" style="padding-left: 0; color: green;">
                    <text id="stock-movement">{{number_format($currentDataArray["curr_price"]["amount"],2)}}</text>
                    <text id="stock-movement-percentage">&nbsp;({{$currentDataArray["curr_price"]["percentage"]}})</text>
                </div>
            </div>
        </div>
        </div>

            <div class="col-xs-12 col-md-10 col-md-offset-1">
            {{--Only Users who are signed in can purchase and sell stocks--}}
            @if(Auth::check() && count(Auth::user()->tradingAccounts) > 0)

              <!-- added a model for the buying stocks button-->
                <button type="button" class="btn button btn-lg " data-toggle="modal" data-target="#userBuyForm">Buy Stocks</button>
                <!-- model layout-->
                <div id="userBuyForm" class="modal fade" role="dialog">
                    <div  class=" modal-content modal-dialog" >
                        <div class="modal-header bg">
                            <div class="heading"> <h3>Buy Stock</h3></div>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -80px">
                                    <span aria-hidden="true" style="font-size: 200%">&times;</span>
                                </button>

                            <div class="text-right">
                                <h4>{{$stock->stock_symbol}}</h4>
                            </div>
                        </div>


                        {{--Get the list of Users Trade Accounts and put into a selection box--}}
                        <div class="modal-body" style="padding-top: 30px">
                            <div style="margin-bottom: 10%">
                                <h4>Stock Price: {{$stock->current_price}}</h4>
                                <h4>Broker Cost: $50.00</h4>
                                <h4>Fee: <lable id="buyPercentageFee">1.00</lable>%</h4>
                            </div>
                            <h4> Account:</h4>
                            <select class="form-control">
                                @foreach(Auth::user()->tradingAccounts as $tradeAccount)
                                    <option value="{{$tradeAccount->id}}" >{{$tradeAccount->name}}</option>
                                @endforeach
                            </select><br/>

                            <h4>Quantity:</h4>
                            <input class="form-control" id="stockQuantity" type="number" value="1" name="quantity" />
                        </div>
                        <div class="modal-footer">
                            <h4 class="text-left">Total Price: $<lable id="buyStockTotal">{{$stock->current_price}}</lable></h4>
                            <button id="buyButton" name="buyButton" class="btn btn-md button" >Buy</button>
                        </div>
                        {{--User messages--}}
                        <div id="buyError" class="alert alert-danger" style="display: none">There was an error</div>
                        <div id="buySuccess" class="alert alert-success" style="display: none">Stock successfully purchased</div>

                        <!-- modal layout Finished-->
                        <script>
                            //Needed to have user info calls on the server side
                            $.ajaxSetup({
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                }
                            });

                            //Constant that has the current value of the current stock
                            var curr_value = {{$currentDataArray["curr_price"]["price"] }};

                            //Set Broker Fee
                            const brokerFee = 50.00;

                            //Set Buy percentage Fee
                            const buyFee = 1.00;
                            //Set Buy Percentage Fee Mass (after threshold
                            const buyFeeMass = 0.75;

                            //Set Fee Threshold
                            const buyThreshold = 1000;

                            //Get list of all the Users Trade Accounts
                            var tradeAccounts = JSON.parse(htmlDecode("{{Auth::user()->tradingAccounts}}"));
                            //Get the stock ID of the current stock
                            var stock_id = parseInt("{{$stock->id}}");

                            function calculateBuyTotal()
                            {
                                //Get the quantity in the field
                                var stockQTY = $('#stockQuantity').val();
                                var stockQTY = parseInt(stockQTY) || 0;

                                //If the stock is less than 1, set to 1 and return
                                if (stockQTY < 1 || isNaN(stockQTY))
                                {
                                    //Update the cost for the user
                                    $('#buyStockTotal').text((curr_value * stockQTY).toFixed(2));

                                    //Disable the Buy Button
                                    $('#buyButton').addClass('disabled');

                                    return;
                                }

                                //Start with just the cost of purchasing the Stock with Quantity
                                var buyTotalCost = (curr_value * stockQTY);

                                //Determine Purchase threshold and adjust fee accordingly
                                if (stockQTY < buyThreshold)
                                {
                                    buyTotalCost += buyTotalCost * (buyFee / 100);
                                    $('#buyPercentageFee').text(buyFee.toFixed(2));
                                }
                                else
                                {
                                    buyTotalCost += buyTotalCost * (buyFeeMass / 100);
                                    $('#buyPercentageFee').text(buyFeeMass.toFixed(2));
                                }

                                //Add Broker Fee
                                buyTotalCost += brokerFee;

                                //Update the cost for the user
                                $('#buyStockTotal').text(parseFloat(buyTotalCost).toFixed(2));

                                //Check if the total cost including fees is affordable by user
                                //if not then disable the buy button
                                //Otherwise enable it
                                if (parseFloat(buyTotalCost) > {{Auth::user()->balance}})
                                {
                                    $('#buyButton').addClass('disabled');
                                }
                                else
                                    $('#buyButton').removeClass('disabled');
                            }


                            //When User updates the quantity, update the cost
                            $('#stockQuantity').on('input', function() {

                                $('#buyError').css('display', 'none');
                                $('#buySuccess').css('display', 'none');

                                calculateBuyTotal();
                            });

                            //When user buys, do some client side checking then send relevant info to the server for processing
                            $('#buyButton').click(function() {
                                //Data to be sent to server
                                var postData = {};

                                //The selected Trade account holder
                                var selectedTradeAccount = {};

                                //Get the id of the selected Trade Account
                                var selectedValue = $('select').val();

                                //Find the selected Trade Account to check and send relevant info in the POST data
                                for (var i = 0; i < tradeAccounts.length; i++)
                                {
                                    if (tradeAccounts[i]["id"] == selectedValue)
                                    {
                                        postData["TradeAccountId"] = tradeAccounts[i]["id"];
                                        selectedTradeAccount = tradeAccounts[i];
                                        break;
                                    }
                                }

                                //Make sure that the Trade account is valid (selected, not null)
                                if (postData["TradeAccountId"] == undefined || postData["TradeAccountId"] == null)
                                {
                                    $('#buyError').text("There is an error with the Trade Account that you selected");
                                    $('#buyError').css('display', 'block');
                                    return;
                                }
                                //Check that the User balance is enough to cover the purchase, show error if not
                                else if ({{Auth::user()->balance}} < parseFloat($('#buyStockTotal').text()))
                                {
                                    $('#buyError').text("You don't have enough balance to purchase this quantity");
                                    $('#buyError').css('display', 'block');
                                    return;
                                }

                                //Make sure there is no error being displayed
                                $('#buyError').css('display', 'none');

                                //Put stock ID into the Data bundle
                                postData["stock_id"] = stock_id;
                                //Put the quantity that is to be purchased in the Data bundle
                                postData["quantity"] = parseInt($('#stockQuantity').val());

                                //AJAX to the API to add the new purchase
                                $.post("{{ url('api/addBuyTransaction') }}", postData)
                                //If all went well, show success message
                                    .done(function(data) {
                                        $('#buySuccess').css('display', 'block');
                                        $('#buyButton').addClass('disabled');
                                        location.reload();

                                    })

                                    //If there are any errors, or the request fails, log it and show an error
                                    .fail(function(error){
                                        console.log(error["responseText"]);
                                        $('#buyError').text(jsonError["message"]);
                                        $('#buyError').css('display', 'block');
                                    })
                                ;

                            });
                        </script>
                        <!-- closing div for modal-->
                    </div>
                </div>

            {{--Sell User From--}}
              <!-- added a model for the buying stocks button-->
                  <button type="button" class="btn sell-button btn-lg" data-toggle="modal" data-target="#userSellForm">Sell Stock</button>
                  <!-- model layout-->
            <div id="userSellForm" class="modal fade" role="dialog">
                <div  class=" modal-content modal-dialog" >
                    <div class="modal-header orange-bg">
                        <div class="heading"> <h3>Sell Stock</h3></div>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -68px">
                                <span aria-hidden="true" style="font-size: 200%">&times;</span>
                            </button>

                        <div class="text-right">
                            <h4>{{$stock->stock_symbol}}</h4>
                        </div>
                    </div>
                {{--Get the list of Users Trade Accounts and put into a selection box--}}
                    <div class="modal-body" style="padding-top: 30px">
                        <h4>Stock Price: {{$stock->current_price}}</h4>
                        <h4>Broker Cost: $50.00</h4>
                        <h4>Fee: <lable id="sellPercentageFee">0.25</lable>%</h4>
                        <br/>
                        <h4> Stock:</h4>
                    <select id="sellTradeAccounts" class="form-control">
                    @foreach(Auth::user()->tradingAccounts as $tradeAccount)
                        <option value="{{$tradeAccount->id}}" >{{$tradeAccount->name}}</option>
                    @endforeach
                </select>
                        <br/>
                        <p>Stock Held: <text id="sellStockHeld"></text></p>
                <input id="sellStockQuantity" class="form-control" type="number" value="1" name="quantity" />

                    </div>
                    <div class="modal-footer">
                        <h4 class="text-left">Sell Total: $<lable id="sellStockTotal">{{$stock->current_price}}</lable></h4>
                        <button id="sellButton" name="sellButton" class="btn btn-md sell-button" >Sell</button>
                    </div>
                    {{--User messages--}}
                    <div id="sellError" class="alert alert-danger" style="display: none">There was an error</div>
                    <div id="sellSuccess" class="alert alert-success" style="display: none"><label id="sellSuccessMessage">Stock successfully sold</label></div>
                <script>

                    $(document).ready(function() {

                        calculateBuyTotal();
                        calculateSellTotal();
                    });

                    //Cache for the Stock Held values to save constantly calling API, stored by Trade Account ID
                    var tradeAccountStocks = {};

                    //Sell Fee Threshold
                    const sellThreshold = 1000;

                    //Standard Sell Percentage Fee
                    const sellFee = (0.25 / 100);

                    //Threshold Sell Percentage
                    const sellFeeMass = (0.1875 / 100);

                    //Listener on the Sell Trade Account Selector, gets the Stock Held count and displays it to user
                    $('#sellTradeAccounts').change(function () {

                        //Blank the Stock Held while loading
                        $('#sellStockHeld').text('');

                        //Get the newly selected Trade Account ID
                        var tradeAccountId = parseInt($('#sellTradeAccounts').val());

                        //If the Trade account has been selected before, if not get it from the API, otherwise pull from cache
                        if (tradeAccountStocks[tradeAccountId] == undefined)
                            getStockCount(parseInt($('#sellTradeAccounts').val()));
                        else
                            $('#sellStockHeld').text(tradeAccountStocks[tradeAccountId]);

                    });

                    //Update the values shown to the user based on the value in the sell quantity input
                    function calculateSellTotal() {
                        var stockQTY = parseInt($('#sellStockQuantity').val() || 0);
                        var stockHeld = parseFloat($('#sellStockHeld').text());

                        //If the selected stock quantity is greater than that of stock held
                        //disable sell button and return
                        //Check if Stock quantity is less than 0, also if stockQTY is NaN
                        if (stockQTY > stockHeld || stockQTY <= 0)
                        {
                            $('#sellStockTotal').text('0.00');
                            $('#sellButton').addClass('disabled');
                            return;
                        }

                        //Enable sell button
                        $('#sellButton').removeClass('disabled');

                        //Get the total value of the sale, without the Fee and Broker Fee
                        var totalSell = {{$stock->current_price}} * stockQTY;

                        //Set Fee
                        if (stockQTY < sellThreshold)
                        {
                            totalSell -= totalSell * sellFee;
                        }
                        else
                        {
                            totalSell -= totalSell * sellFeeMass;
                        }

                        //Subtract Broker Fee
                        totalSell -= brokerFee;

                        //Set the Sell Total Stock lable to the total Sell Value
                        $('#sellStockTotal').text(parseFloat(totalSell).toFixed(2));

                        //If the sale would be in the positive, total colour to Green
                        //Otherwise set to red
                        if (totalSell > 0)
                            $('#sellStockTotal').css('color', 'green');
                        else
                            $('#sellStockTotal').css('color', 'red');
                    }

                    //When User updates the quantity, update the cost
                    $('#sellStockQuantity').on('input', function() {

                        $('#sellError').css('display', 'none');
                        $('#sellSuccess').css('display', 'none');

                        calculateSellTotal();

                    });

                    //AJAX call to get the stock held count for the current stock view on the selected Trade Account
                    function getStockCount(tradeAccountId) {
                        var postData = {};

                        //Put stock ID into the Data bundle
                        postData["stock_id"] = stock_id;
                        postData["trade_account_id"] = tradeAccountId;

                        $.post("{{ url('api/getTradeAccountStockQuantity') }}", postData)
                        //If all good, then change the Stock Held text and make sure the error message is gone
                            .done(function (data) {
                                $('#sellStockHeld').text(data);
                                $('#sellError').css('display', 'none');
                                tradeAccountStocks[tradeAccountId] = data;
                            })
                            //If there is an error, display error message to user
                            .fail(function (error) {
                                $('#sellError').css('display', 'block').text('An error occurred getting Trade Account details');
                            })
                        ;
                    }
                    //Load up the Stocks Held on the first item in the Selection Box
                    getStockCount(parseInt($('#sellTradeAccounts').val()));

                    //When user clicks sell button, do some client side validation then post to the server to process
                    $('#sellButton').click(function () {
                        //Get the quantity the user want to sell from the input box
                        var stockQuantityToSell = parseInt($('#sellStockQuantity').val());
                        //Get the stock held by the currently selected trading account
                        //TODO: might change this to the tradeAccountStockCounter holder
                        var stockHeld = parseInt($('#sellStockHeld').text());

                        //If the quantity to sell is not valid number or is below 1, show an error message and return
                        if (isNaN(stockQuantityToSell) || stockHeld < 1)
                        {
                            $('#sellError').css('display', 'block').text("Sell quantity must be above 1");
                            return;
                        }

                        //If the stock held is not a number, then display message and return
                        if (isNaN(stockHeld) )
                        {
                            $('#sellError').css('display', 'block').text("Sell quantity must be above 1");
                            return;
                        }

                        //If the quantity to sell is below the stock held, show an error and return
                        if (stockHeld < stockQuantityToSell)
                        {
                            $('#sellError').css('display', 'block').text("Sell quantity must be equal or lower than Stock Held");
                            return;
                        }

                        var postData = {};

                        //Get the currently selected Trade Account and put into postData
                        postData["trade_account_id"] = parseInt($('#sellTradeAccounts').val());
                        //The current stock ID, put into postData
                        postData["stock_id"] = stock_id;
                        //Add the quantity to be sold to the postData
                        postData["quantity"] = stockQuantityToSell;

                        console.log(postData);

                        //AJAX to the API to add the new sell
                        $.post("{{ url('api/addSellTransaction') }}", postData)
                        //If all good, hide the error message and show success message
                            .done(function (data) {
                                $('#sellSuccess').css('display', 'block').text('Sale Successful');
                                $('#sellError').css('display', 'none');
                                $('#sellButton').addClass('disabled');

                                window.setTimeout(function () {
                                    location.reload();
                                }, 1500);
                            })
                            //If there is an error, display error message to user
                            .fail(function (error) {
                                console.log(error);
                                var errorJSON = JSON.parse(error["responseText"]);
                                $('#sellError').css('display', 'block').text(errorJSON["message"]);
                                $('#sellSuccess').css('display', 'none');
                            })
                        ;

                    });

                </script>
                </div>
            </div>

            @else
                {{--Otherwise show a message that the user must have a least one trade account to trade stocks--}}
                    <strong>Please create a trade account to trade stocks: <a href="{{url('/dashboard')}}">Dashboard</a> </strong>
            @endif
            <!--Table to show quick stats about stock-->
            <!--In full screen mode the table is divided into two, side by side. when on mobile they are stacked-->
            <!--<div id="stock-stats-table" style="margin-bottom: 10%;">-->
            <div class="table-responsive col-xs-12" style="margin-bottom: 3%; padding-top:3%; border: none">
                <table class="col-xs-12 col-md-6 table-hover">

                    {{--Loop through the first half of the current data array and populate the left side of the table--}}
                    @for($i = 0; $i < count($currentDataArray["curr_price"]["extraData"])/2; $i++)

                        <tr>
                            <td class="col-xs-6" style="padding: 0px">{{$currentDataArray["curr_price"]["extraData"][$i]["title"]}}</td>
                            <td class="col-xs-6" style="padding: 0px">{{$currentDataArray["curr_price"]["extraData"][$i]["value"]}}</td>
                        </tr>

                    @endfor

                </table>

                <table class=" col-xs-12 col-md-6 table-hover">

                    {{--Loop through the second half of the current data array and populate the right side of the table--}}
                    @for($i = count($currentDataArray["curr_price"]["extraData"])/2; $i < count($currentDataArray["curr_price"]["extraData"]); $i++)
                        <tr>
                            <td class="col-xs-6" style="padding: 0px">{{$currentDataArray["curr_price"]["extraData"][$i]["title"]}}</td>
                            <td class="col-xs-6" style="padding: 0px">{{$currentDataArray["curr_price"]["extraData"][$i]["value"]}}</td>
                        </tr>
                    @endfor
                </table>
            </div>

        <!--<h4 style='font-family: "Raleway", sans-serif;'>{{ $stock->stock_symbol }}.AX</h4>-->


            <div class="col-xs-1 col-md-2"></div>
            <div class="col-xs-10 col-md-8" style="margin: auto; margin-bottom: 5%; margin-top: 2%">
                <!--style="width: 500px;">-->
                <canvas id='chart'></canvas>
            </div>
            <div class="col-xs-1 col-md-2"></div>
        </div>
        <div id='data'>
        </div>
    @show



    <script>

        //Convenience function to add a 0 to the front of an integer, used for date formatting
        function addZero(i)
        {
            if (i < 10) {
                i = '0' + i;
            }
            return i;
        }

        //Convenience function to convert the year that a getYear function returns, converts it to 20xx
        function fixYear(i)
        {
            return (i - 100) + 2000;
        }

        //Array that stores data to be shown in Stock Daily Graph
        var dataIn = [];

        {{--Get Stock Lastest History from DB and convert to JSON--}}
        var stockHistoriesString = htmlDecode("{{$stock->getLatestHistory()}}");
        var stockHistoriesJSON = JSON.parse(stockHistoriesString);

//      Loop through the stockHistoriesJSON and convert time into ChartJS format, and add time and average to ChartJS data
        $.each(stockHistoriesJSON, function (index, value) {

            var date = new Date();
            date.setTime(value["timestamp"] * 1000);

            //2017/04/18 14:31:21
            //Make string that ChartJS is expecting
            var time = fixYear(date.getYear()) + '/' + addZero(date.getMonth() + 1) + '/' + addZero(date.getDay()) + ' ' +
                    date.getHours() + ':' +  addZero(date.getMinutes()) + ':' + addZero(date.getSeconds());

            //Pull the average stock value from the history row
            var average = value["average"].toFixed(2);

            //Add data to ChartJS JSON array
            dataIn.push({x: time, y: average});
        });

        //Populate the graph inside the selected canvas using the DataIn array and some settings
        var ctx = document.getElementById('chart');
        var stockValue = new Chart(ctx, {
            type: 'line',
            data: {
                datasets: [{
                    data: dataIn,
                    // pointStyle: "line",
                    lineTension: 0,
                    fill: false,
                    borderColor: '#039BE5',
                    borderWidth: 2
                }]
            },
            options: {
                animation: {
                    // easing: 'easeInOutBounce'
                },
                legend: {
                    display: false
                },
                elements: {
                    point: { radius: 0 }
                },
                scales: {
                    xAxes: [{
                        type: 'time',
                        time: {
                            unit: 'hour',
                            displayFormats: {
                                hour: 'hh:mm a'
                            },
                            {{--min: '{{date("Y/m/d")}} 10:00',--}}
//                            max: dateToday.getYear() ' 16:00'
                        },
                        gridLines : {
                            display : false
                        }
                    }]
                }
            }
        });
    </script>

    <script type="application/javascript">
        //Method to turn HTML encoded entities into their string representation
        function htmlDecode(input){
            var e = document.createElement('div');
            e.innerHTML = input;
            return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
        }

    </script>

</div>
<br/>
@include('layouts.footer')
</body>

</html>
