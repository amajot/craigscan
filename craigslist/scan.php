<?PHP

function craig_scan($searchTerms, $timeframe){
		$global_URL = "https://louisville.craigslist.org/search/sss?format=rss&query=#TERM#&sort=rel";

		$link = mysql_connect('localhost', 'root', '')
		or die('Could not connect: ' . mysql_error());
		mysql_select_db('hamscan') or die('Could not select database');

		date_default_timezone_set("America/New_York");
		echo "Running qrz hamScan at " . date("Y-m-d h:i:sa") . "\n";


			foreach($searchTerms as $term){
				$URL = str_replace ("#TERM#", $term, $global_URL);
				// Create DOM from URL or file
				$html = file_get_html($URL);

				//get list of items
				foreach($html->find('item') as $item) {

					//checking search term against item
					if(search_craigs($term, $item)){
						//get listing title
						$encodedTitle = $item->find('title',0)->innertext;	
						$listingTitle = mysql_real_escape_string((string) simplexml_load_string("<x>$encodedTitle</x>"));

						//get img URL
						$imgURL = mysql_real_escape_string($item->find('enc:enclosure',0)->resource);						

						//get listing URL
						$listingURL = mysql_real_escape_string($item->find('link',0)->innertext);

						//get listing ID
						$parsedURL = explode('/', str_replace('.html', "", $listingURL));
						$listingID = mysql_real_escape_string($parsedURL[count($parsedURL)-1]);

						//get description
						$encodedContent = $item->find('description',0)->innertext;					
						$listingDescription = mysql_real_escape_string((string) simplexml_load_string("<x>$encodedContent</x>"));

						//add to database if its new!
						if(!check_craigs($listingID)){
							echo "inserting ListingID: " . $listingID . "\n";
							$query = "INSERT INTO craigScan_list (id, search_term, title, img_url, description, url) values ($listingID, '$term', '$listingTitle', '$imgURL', '$listingDescription', '$listingURL')";
							$result = mysql_query($query) or die('Query failed: ' . mysql_error());
						}
						else if(check_craigs_update($listingID, $listingDescription)){//already exists, but was it updated?
							echo "updating ListingID: " . $listingID . "\n";
							$query = "UPDATE craigScan_list set description =  '$listingDescription', title = '$title' where id = $listingID";
							$result = mysql_query($query) or die('Query failed: ' . mysql_error());
						}
					}
				}
			}

		//query to see if there are any updates
		$query = "SELECT email_address, img_url, category, title, description, url FROM craigScan_list WHERE last_update >= DATE_SUB(NOW(),INTERVAL $timeframe HOUR)";

		$result = mysql_query($query) or die('Query failed: ' . mysql_error());
		$results = array();
		while($row = mysql_fetch_assoc($result))
		{
			$results[] = $row;
		}

		return extract_email_convert_to_array($results);
}

function extract_email_convert_to_array($results){
	$emailArray = new array();
	foreach($results as $result){
		$emailArray[$result[0]][] = $result;
	}
	return $emailArray;
}

//searching to see if a search term is inside the page
function search_craigs($needle, $haystack){
	if (stripos($haystack, $needle) !== false) {
		return true;
	}
	return false;
}

//check to see if a listing already exists
//returns boolean
function check_craigs($id){

// Performing SQL query
	$query = "SELECT * FROM craigScan_list WHERE id = $id";
	$result = mysql_query($query) or die('Query failed: ' . mysql_error());
	$found = false;
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$found = true;
	}
	return $found;

}

//check to see if a listing that already exists has updated contents
//returns boolean
function check_craigs_update($id, $description){

// Performing SQL query
	$query = "SELECT * FROM craigScan_list WHERE id = $id AND description = '$description'";
	$result = mysql_query($query) or die('Query failed: ' . mysql_error());
	$found = true;
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$found = false;
	}
	return $found;

}
