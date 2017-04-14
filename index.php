<?php
/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

// Load the Google API PHP Client Library.
require_once __DIR__ . '/vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/client_secrets.json');
$client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);


// If the user has already authorized this app then get an access token
// else redirect to ask the user to authorize access to Google Analytics.
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
  // Set the access token on the client.
  $client->setAccessToken($_SESSION['access_token']);

  // Create an authorized analytics service object.
  $analytics = new Google_Service_AnalyticsReporting($client);

//help url: https://ga-dev-tools.appspot.com/query-explorer/
//
//
echo '<pre>'; 
print_r( $_SESSION['access_token']); 
$url = 'https://www.googleapis.com/analytics/v3/data/ga?ids=ga%3A143110625&start-date=30daysAgo&end-date=yesterday&metrics=ga%3Asessions%2Cga%3AsessionDuration%2Cga%3Apageviews%2Cga%3Ascreenviews&dimensions=ga%3Asource%2Cga%3AadDestinationUrl%2Cga%3AmobileDeviceInfo%2Cga%3ApagePath&segment=gaid%3A%3A-15&access_token='.$_SESSION['access_token']['access_token'];
echo file_get_contents($url);

exit;
  // Call the Analytics Reporting API V4.
  $response = getReport($analytics);

	echo "printing report: ";
  // Print the response.
  printResults($response);
  

} else {
  $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/googleAPI/oauth2callback.php';
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}


function getReport($analytics) {

  // Replace with your view ID. E.g., XXXX.
  $VIEW_ID = "YOUR_VIEW_ID";

  // Create the DateRange object.
  $dateRange = new Google_Service_AnalyticsReporting_DateRange();
  $dateRange->setStartDate("17daysAgo");
  $dateRange->setEndDate("today");

  // Create the Metrics object.
  $sessions = new Google_Service_AnalyticsReporting_Metric();
  $sessions->setExpression("ga:pageviews");
  //$sessions->setExpression("ga:sessions,ga:cohortPageviewsPerUser");
  $sessions->setAlias("sessions_page");

  // Create the ReportRequest object.
  $request = new Google_Service_AnalyticsReporting_ReportRequest();
  $request->setViewId($VIEW_ID);
  $request->setDateRanges($dateRange);
  $request->setMetrics(array($sessions));

  $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
  $body->setReportRequests( array( $request) );
  return $analytics->reports->batchGet( $body );
}

function printResults($reports) {
  for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
    $report = $reports[ $reportIndex ];
    $header = $report->getColumnHeader();
    $dimensionHeaders = $header->getDimensions();
    $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
    $rows = $report->getData()->getRows();

    for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
      $row = $rows[ $rowIndex ];
      $dimensions = $row->getDimensions();
      $metrics = $row->getMetrics();
      for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
        print($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
      }

      for ($j = 0; $j < count( $metricHeaders ) && $j < count( $metrics ); $j++) {
        $entry = $metricHeaders[$j];
        $values = $metrics[$j];
        print("Metric type: " . $entry->getType() . "\n" );
        for ( $valueIndex = 0; $valueIndex < count( $values->getValues() ); $valueIndex++ ) {
          $value = $values->getValues()[ $valueIndex ];
          print($entry->getName() . ": " . $value . "\n");
        }
      }
    }
  }
}

