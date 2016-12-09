<?PHP
include "simple_html_dom.php";
include "craigslist/scan.php";

$timeframe = 1; //hours


// Get a list of all search term files
$searchTermFiles = array();
foreach (glob("searches/*.terms") as $file) {
  $searchTermFiles[] = $file;
}

//get a list of all blacklisted files
$blacklistTermsFiles = array();
foreach (glob("searches/*.blacklist") as $file) {
  $blacklistTermsFiles[] = $file;
}

//ingest search terms from files into arrays whose keys are email addresses:
$searches_to_perform = array();
foreach($searchTermFiles as $file){
	$searches_to_perform[parse_email_from_filename($file)] = file($file, FILE_IGNORE_NEW_LINES);
} 

//ingest blacklist terms from files into arrays whose keys are email addresses:
$blacklisted_terms = array();
foreach($blacklistTermsFiles as $file){
	$blacklisted_terms[parse_email_from_filename($file)] = file($file, FILE_IGNORE_NEW_LINES);
} 

//iterate through each site's scan file and pass search terms into it
$craigResults = craig_scan($searches_to_perform, $blacklisted_terms, $timeframe);

//generate email report
if(sizeof($craigResults) > 0){
	send_email($craigResults);
}

function parse_email_from_filename($filename){
	$return = str_replace ("searches/", "", $filename);
	$return = str_replace (".blacklist", "", $return);
	return str_replace (".terms", "", $return);
}


function send_email($craigResults){
	foreach($craigResults as $email => $result){
		$to = $email;

	$subject = 'Craigslist Scan Results!';

	$headers = "From: noreply@craigslistings.com" . "\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
	$message = generate_email_body($result);

	mail($to, $subject, $message, $headers);
	}
}

function generate_email_body($craigResults){
	$message = '<html><body>';
	$message .= "<table border=1px>";
	$message .= "<tr>";
	$message .= "<td>Pic</td>";
	$message .= "<td>Description</td>";
	$message .= "</tr>";

    foreach($craigResults as $line){
		$message .= "<tr>";
		//link image
		$message .= "<td><img src='" . $line['img_url'] . "' style='width:100px;height:100px;'></td><td>";
		//link listingURL
		$message .= "<br/><a href='" . $line['url'] . "'>LINK</a><br/><br/>";
		//description
		$message .= "" . $line['description'] . "<br/></td>";	

		$message .= "</tr>";
	}
	$message .= "</table>";
	$message .= "</html></body>";
	return $message;
}
