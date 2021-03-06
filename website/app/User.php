<?php

/**
 * Created by: Laravel Framwork.
 * Authors: Paul Davidson and Josh Gerlach
 */

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use League\Flysystem\Exception;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'admin', 'avatar', 'balance', 'portfolio'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Establish relationship between User and Trade Accounts
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tradingAccounts() {
        return $this->hasMany('App\TradeAccount');
    }

    /**
     * Check if the User is an admin
     * @return mixed - Boolean. True: is admin, False: is not admin
     */
    public function isAdmin()
    {
        return $this->admin;
    }

    /**
     * Get detailed Friend status information between Users (User IDs passed)
     * @param $authid - The User making the call
     * @param $id - The User to get Friend information
     * @return int - Status of Friendship
     */
    public function isFriend($authid, $id)
    {
        /**
         * 0 - not friends
         * 1 - are friends
         * 2 - same user
         * 3 - no user found
         * 4 - friend request pending
         * 5 - waiting for friend to accept
         *
         * 10 - Error, reached the end of the function with no return
         */

        //Get User who's profile is being used id
        $user = DB::table('users')->find($id);

        //If the User is null, User does not exist, so return 3
        if ($user == null)
            return  3;

        //If the Profile ID and the User who requested the isFriend are the same,
        //User is viewing own profile, return 2
        if ($authid == $id)
            return 2;

        //Check if there are any friend links in the friends table
        $friends = DB::table('friends')
            ->where([['to', $authid], ['from', $id]])
            ->orWhere([['from', $authid], ['to', $id]])
            ->first();

        //If there is no friends row, they are not friends, return 0
        if ($friends == null)
            return 0;


        //Check the pending attribute
        if ($friends->pending)
        {
            //If the request was sent to the viewing User, means they have a friend request waiting, return 5
            if ($friends->to == $authid)
                return 5;
            //Otherwise, they are the waiting on the other user to accept, so return 4
            else
                return 4;
        }
        //A check, at this point both Users exist, have a Friends row, but pending is false,
        //So they must be friends, return 1
        else if (!$friends->pending)
            return 1;


        return 10;
    }

    /**
     * Get Pending Friend Requests and Unseen Friend Request Accepts
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getFriendRequests()
    {
        //Get user ID
        $id = $this->getAuthIdentifier();

        try {
            //Get friend requests the user has pending, add the name of the user who sent the request
            $friendRequests = Friend::
                where([['to', $id], ['pending', true]])
                ->orwhere(
                    [
                        ['from', $id],
                        ['pending', false],
                        ['accept_view', false]
                    ]
                )
                ->join('users', 'users.id', 'friends.from')
                ->join('users as usrs', 'usrs.id', 'friends.to')
                ->select('friends.*', 'users.name as name_from', 'usrs.name as name_to')
                ->get();
        }
        catch (\Exception $exception)
        {
            return response("Error getting Friend Requests", 400);
        }

        return $friendRequests;
    }

    /**
     * Get Friends list of selected User (by User ID)
     * @param $id - ID of user
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getFriendList($id)
    {
        try {
            //Get friend requests the user has received and not pending
            $friendsTo = Friend::
            where([['from', $id], ['pending', false]])
                ->join('users', 'to', '=', 'users.id')
                ->select('users.id', 'users.name')
                ->get();

            //Get friend requests sent and not pending
            $friendsFrom = Friend::
            where([['to', $id], ['pending', false]])
                ->join('users', 'from', '=', 'users.id')
                ->select('users.id', 'users.name')
                ->get();
        }
        catch (\Exception $exception)
        {
            return response($exception, 400);
        }

        //Combine Friends into single array
        $friends = array();

        //Add Friends that User sent requests to and accepted
        if ($friendsTo != null)
        {
            foreach ($friendsTo as $friend)
            {
                array_push($friends, $friend);
            }
        }

        //Add Friends that User received requests from and accepted
        if ($friendsFrom != null)
        {
            foreach ($friendsFrom as $friend)
            {
                array_push($friends, $friend);
            }
        }

        //Return complete friends list
        return $friends;
    }

    /**
     * Check if the current user if friends with a User (passed User ID)
     * @param $id - ID of User to check against to see if Friends
     * @return bool - True: friends, false: not friends
     */
    public function checkIfFriends($id)
    {
        //Get the current User ID
        $authid = $this->getAuthIdentifier();

        //Check if there are any friend links in the friends table
        $friends = DB::table('friends')
            ->where([['to', $authid], ['from', $id]])
            ->orWhere([['from', $authid], ['to', $id]])
            ->first();

        //If a value is returned from the database, then they are friends
        if ($friends != null)
            return true;

        //If there is no such row, they are not friends and return false
        return false;
    }

    /**
     * Get all current users unread messages
     * @return mixed - Eloquent Model list of unread messages
     */
    public function getUnreadMessages()
    {
        //Get user ID
        $id = $this->getAuthIdentifier();

        //Get unread messages from database, add the name of the user who sent the message
        $unreadMessages = Message::
            where([['to', '=', $id], ['read', '=', false]])
            ->join('users', 'users.id', 'messages.from')
            ->select('messages.*', 'users.name')
            ->get();

        //Return list of messages
        return $unreadMessages;
    }


    /**
     * Wrapper function to get all the notifications the user has
     * Returns unread messages and unaccepted friend requests
     * @return mixed
     */
    public function getNotifications()
    {
        //Get list of all unread messages for user
        $unreadMessages = $this->getUnreadMessages();
        //Get list of all pending friend requests
        $pendingFriendRequests = $this->getFriendRequests();

        //Merge the friend requests and the unread messages into one array
        $data = $unreadMessages->merge($pendingFriendRequests);

        //Return the merged data
        return $data;
    }

    /**
     * Update the users portfolio.
     * Add the value of all the trade accounts the user has with their balance, this is the portfolio value
     *
     */
    public function updatePortfolio() {

        //Set the base portfolio level as the current balance
        $portfolioValue = $this->balance;

        //Loop through each trade account and add the total growth (in dollars) to the portfolio value
        foreach ($this->tradingAccounts as $tradingAccount)
        {
            //Get the trade accounts current stock holdings stats
            $groupedTransactions = $tradingAccount->getCurrentStock();

            //Convert from a comma separated string to a float
            $totalStockValue = $groupedTransactions["stats"]["total_stock_value"];
            $totalStockValue = str_replace(",", "", $totalStockValue);
            $totalStockValue = floatval($totalStockValue);

            //Add the value of this stock to the portfolio value
            $portfolioValue += $totalStockValue;
        }

        //Set the new calculated portfolio value and save to the database
        $this->portfolio = $portfolioValue;
        $this->save();
    }

    /**
     * Get User position in leaderboard
     * @param $id - ID of the User that you want to get the position of
     * @return int|string - Position in leaderboard, N/A string if not ranked
     */
    public function getLeaderBoardPosition($id)
    {
        //Get list of all Users, ordered by their portfolio worth
        $users = User::orderBy('portfolio', 'desc')->get();

        //Loop through each user, when the selected user is hit, return the tracked position
        $position = 1;
        foreach ($users as $user)
        {
            if ($user->id == $id)
                return $position;
            $position++;
        }

        //Return N/A by default, if the user is not ranked
        return "N/A";
    }
}
