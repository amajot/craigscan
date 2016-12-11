<?PHP


function craig_scan($searchTerms, $blacklisted_terms, $timeframe){

	$local_craig_areas = array();
	$local_craig_areas[] = "louisville";
	$local_craig_areas[] = "evansville";
	$local_craig_areas[] = "bloomington";
	$local_craig_areas[] = "cincinnati";
	$local_craig_areas[] = "indianapolis";
	$local_craig_areas[] = "terrehaute";
	$local_craig_areas[] = "evansville";
	$local_craig_areas[] = "owensboro";
	$local_craig_areas[] = "lexington";
	$local_craig_areas[] = "bgky";
	$local_craig_areas[] = "westky";
	$local_craig_areas[] = "eastky";


		$global_URL = "https://#CRAIG_AREA#.craigslist.org/search/sss?format=rss&query=#TERM#&sort=rel";

		$link = mysql_connect('localhost', 'root', '')
		or die('Could not connect: ' . mysql_error());
		mysql_select_db('craigscan') or die('Could not select database');

		date_default_timezone_set("America/New_York");
		echo "Running craigscan at " . date("Y-m-d h:i:sa") . "\n";

		foreach($local_craig_areas as $area){ //checking all local craigs
			$craig_URL = str_replace ("#CRAIG_AREA#", urlencode($area), $global_URL);

			foreach($searchTerms as $user_email => $terms){
				foreach($terms as $term){
					$URL = str_replace ("#TERM#", urlencode($term), $craig_URL);
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
							if(isset($item->find('enc:enclosure',0)->resource)){
								$imgURL = mysql_real_escape_string($item->find('enc:enclosure',0)->resource);
							}
							else{
								$imgURL = null;
							}
													

							//get listing URL
							$listingURL = mysql_real_escape_string($item->find('dc:source',0)->innertext);

							//get listing ID
							$parsedURL = explode('/', str_replace('.html', "", $listingURL));
							$listingID = mysql_real_escape_string($parsedURL[count($parsedURL)-1]);

							//get description
							$encodedContent = $item->find('description',0)->innertext;					
							$listingDescription = mysql_real_escape_string((string) simplexml_load_string("<x>$encodedContent</x>"));


							//check for blacklisted term inside the description:
							if(check_blacklist($listingDescription, $blacklisted_terms, $user_email)){						
								//add to database if its new!
								if(!check_craigs($listingID)){
									echo "inserting ListingID: " . $listingID . "\n";
									$query = "INSERT INTO craigScan_list (id, email_address, search_term, title, img_url, description, url) values ($listingID, '$user_email', '$term', '$listingTitle', '$imgURL', '$listingDescription', '$listingURL')";
									$result = mysql_query($query) or die('Insert Query failed: ' . mysql_error());
								}
								else if(check_craigs_update($listingID, $listingDescription)){//already exists, but was it updated?
									echo "updating ListingID: " . $listingID . "\n";
									$query = "UPDATE craigScan_list set description =  '$listingDescription', title = '$title' where id = $listingID";
									$result = mysql_query($query) or die('Update Query failed: ' . mysql_error());
								}
							}
						}
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

function check_blacklist($description, $blacklisted_terms, $user_email){
	if (isset($blacklisted_terms[$user_email])){
		foreach($blacklisted_terms[$user_email] as $bl_term){
				if(strpos($description, $bl_term)){
					return false;
				}
		}
	}
	
	return true;
}

function extract_email_convert_to_array($results){
	$emailArray = array();
	foreach($results as $result){
		$emailArray[$result['email_address']][] = $result;
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
