<?php

/**
 * Created by: Josh Gerlach.
 * Authors: Josh Gerlach
 */

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;

class CompaniesController extends Controller
{

    /**
     * Entry function from API route to call list of companies on ASX and return JSON file.
     * @return Response
     */
    public function index(){
        return response($this->getAllListedCompanies(), 200);
    }

    /**
     * Get a list of all companies listed on the ASX.
     * Updated Daily
     */
    function getAllListedCompanies()
    {
        //Load CSV file from asx.com.au
        $data = file_get_contents('http://www.asx.com.au/asx/research/ASXListedCompanies.csv');

        //Remove the first 2 lines in the CSV file (file info and blank space)
        $data = substr($data, strpos($data, "\n") + 2);
        //Make sure the start of the CSV file is not a new line
        $dataCSV = str_replace("\nCompany name,", "Company name", $data);
        //Get rid of any quotation marks
        $dataCSV = str_replace("\"", "", $dataCSV);
        //Remove all \r (return character)
        $dataCSV = str_replace("\r", "", $dataCSV);
        //Make sure this is a comma between Company name and SSX code
        $dataCSV = str_replace("nameASX", "name,ASX", $dataCSV);

        //Move all rows into an array
        $rows = explode("\n", $dataCSV);
        //New return array
        $array = array();

        //Process Rows and put information into array
        $this->processRow($array, $rows);

        //Wrap up the companies data into a nice array
        $data = array();
        $data["companies"] = $array;
        //Respond and send data as JSON String
        return $data;
    }

    /**
     * Loop through each row and process data, and place into array
     *
     * @param $array - Array to place processed data
     * @param $rows - List of rows to loop through and process
     */
    private function processRow(&$array, &$rows)
    {
        //Array to store all headings in
        $headings = array();
        //Row holder for current CSV Row
        $csvRow = array();
        //Index tracking for current row
        $index = 0;

        foreach ($rows as $row)
        {
            //If the row is blank move the to next
            if ($row == "")
            {
                continue;
            }

            //If this is the first time through the loop
            //The first row is the headings so capture them in a different array, increase index and continue
            if ($index == 0)
            {
                $headings = str_getcsv($row);
                $index++;
                continue;
            }

            //Load the selected row into csvRow
            $csvRow[0] = str_getcsv($row);

            //Create a new companyRow variable
            $companyRow = array();
            //For every heading, extract the corresponding value from current row and load into companyRow array
            for ($j = 0; $j < count($headings); $j++)
                $companyRow[$headings[$j]] = $csvRow[0][$j];

            //If there are more elements in the current row than there are headings, then assume that is extra company data
            //and add to the end of the last element, separated by a comma
            if (count($csvRow[0]) > count($headings))
            {
                for ($j = 3; $j < count($csvRow[0]); $j++)
                    $companyRow[$headings[2]] = $companyRow[$headings[2]] . ", " . $csvRow[0][$j];
            }

            //Add the companyRow to the holding Array (array)
            $array[$index - 1] = $companyRow;

            //Increment the index
            $index++;
        }
    }
}