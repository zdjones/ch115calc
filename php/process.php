<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
session_start();
ob_start();
#
# if submitted via Reset button - resets session and returns to begin page
#
if (isset($_GET['reset'])){
  session_destroy();
  session_start();
  session_unset();
  session_regenerate_id(true);
  header("Location: index.php"); /* Redirect browser */
  exit(0);
}
#
# submitted via begin button - resets session and moves to first question
#
elseif (isset($_GET['begin'])) { //if coming from Begin/Intro
  session_unset();                 //clear old session vars
  session_regenerate_id(true);     //new session id, clear old
  $_SESSION['current']='service';
  header("Location: index.php"); /* Redirect browser */
  exit(0);
}
#
# submitted from any input forms - runs validation and navigates appropriately
#
elseif (isset($_GET['submit'])) {
#
#  Entry Validation for all forms
#
//save submitted values to SESSION
foreach ($_GET as $name => $value) {
  $_SESSION[$name] = $value;
}
// clear previous error - new errors checked within each
unset($_SESSION['errors']);
## service basics - service.php
if ($_GET['submit']==='service') {
  #
  # service date Validation and modification for unix epoch
  #      - would like to rework this to consitently use dateTime object, currently saves back to string in session
  if ($_SESSION['branch']==='blank') {
    $_SESSION['errors']['branch']='Please select your branch of service';
  }
  if ($_SESSION['discharge']==='blank') {
    $_SESSION['errors']['discharge']='Please select the type of discharge you received';
  }
  if (isset($_GET['serviceStart']) && isset($_GET['serviceEnd'])) {
    service_Date_Validate('serviceStart'); //valid date, proper format, +10, -100 years
    service_Date_Validate('serviceEnd'); //valid date, proper format, +10, -100 years
    // if both valid dates, check if end before beginning
    if (empty($_SESSION['errors']['serviceStart']) && empty($_SESSION['errors']['serviceEnd']) ) {
      $serviceStart = new DateTime($_SESSION['serviceStart']);
      $serviceEnd = new DateTime($_SESSION['serviceEnd']);
      if ($serviceEnd <= $serviceStart) {
        $_SESSION['errors']['serviceStart'] = 'Ended before Began';
        $_SESSION['errors']['serviceEnd'] = 'Ended before Began';
      }
    }
  }
  if (empty($_SESSION['errors']['branch']) && empty($_SESSION['errors']['serviceStart']) && empty($_SESSION['errors']['serviceEnd']) && empty($_SESSION['errors']['discharge']) ) {
    $_SESSION['answered'][]='service';
    //Service eligibility
    $_SESSION['serviceDays'] = date_diff($serviceStart, $serviceEnd)->days;
    if ( is_Wartime($serviceStart, $serviceEnd) ) {
      $_SESSION['serviceEra'] = is_Wartime($serviceStart,$serviceEnd);
    } else {
    $_SESSION['serviceEra'] = 'Peacetime';
    }
    if (is_Eligible_Service($serviceStart,$serviceEnd)) {
      $_SESSION['eligibleService'] = is_Eligible_Service($serviceStart,$serviceEnd);
      $_SESSION['current']='vetReside';
    } else {
      $_SESSION['eligibleService'] = 'No';
      $_SESSION['current']='purpleHeart';
    }
  } // end of if no errors
} // end of if service
## service details - purpleHeart
elseif ($_GET['submit']==='purpleHeart') {
  if (isset($_SESSION['purpleHeart'])) {
      $_SESSION['answered'][]='purpleHeart';
      $serviceStart = new DateTime($_SESSION['serviceStart']);
      $serviceEnd = new DateTime($_SESSION['serviceEnd']);
      if (is_Eligible_Service($serviceStart,$serviceEnd)) {
          $_SESSION['eligibleService'] = is_Eligible_Service($serviceStart,$serviceEnd);
          $_SESSION['current']='vetReside';
      } else {
          $_SESSION['eligibleService'] = 'No';
          $_SESSION['current']='serviceDisability';
      }
    } else {$_SESSION['errors']['purpleHeart']='Please select YES or NO';}
}
## service details - serviceDisability
elseif ($_GET['submit']==='serviceDisability') {
  if (isset($_SESSION['serviceDisability'])) {
    $_SESSION['answered'][]='serviceDisability';
    $serviceStart = new DateTime($_SESSION['serviceStart']);
    $serviceEnd = new DateTime($_SESSION['serviceEnd']);
    if (is_Eligible_Service($serviceStart,$serviceEnd)) {
      $_SESSION['eligibleService'] = is_Eligible_Service($serviceStart,$serviceEnd);
      $_SESSION['current']='vetReside';
    } else {
      $_SESSION['eligibleService'] = 'No';
      $_SESSION['current']='kdsm';
    }
  } else {$_SESSION['errors']['serviceDisability']='Please select YES or NO';}
}
## service details - kdsm
elseif ($_GET['submit']==='kdsm') {
  if (isset($_SESSION['kdsm'])) {
    $_SESSION['answered'][]='kdsm';
    $serviceStart = new DateTime($_SESSION['serviceStart']);
    $serviceEnd = new DateTime($_SESSION['serviceEnd']);
    if (is_Eligible_Service($serviceStart,$serviceEnd)) {
      $_SESSION['eligibleService'] = is_Eligible_Service($serviceStart,$serviceEnd);
      $_SESSION['current']='vetReside';
    } else {
      $_SESSION['eligibleService'] = 'No';
      $_SESSION['current']='campaigns';
    }
  } else {$_SESSION['errors']['kdsm']='Please select YES or NO';}
}
## service details - campaigns
//have to update session var for campaigns outside loop, doesnt update if empty on later submits
elseif ($_GET['submit']==='campaigns') {
  $_SESSION['answered'][]='campaigns';
  $_SESSION['campaigns'] = $_GET['campaigns'];
  $serviceStart = new DateTime($_SESSION['serviceStart']);
  $serviceEnd = new DateTime($_SESSION['serviceEnd']);;
  if ( is_Wartime($serviceStart, $serviceEnd) ) {
    $_SESSION['serviceEra'] = is_Wartime($serviceStart,$serviceEnd);
  } else {
    $_SESSION['serviceEra'] = 'Peacetime';
  }
  if (is_Eligible_Service($serviceStart,$serviceEnd)) {
      $_SESSION['eligibleService'] = is_Eligible_Service($serviceStart,$serviceEnd);
      $_SESSION['current']='vetReside';
  } else {
      $_SESSION['eligibleService'] = 'No';
      $_SESSION['current']='vetReside'; //later change to results page showing ineligible.
  }
}
## service details - serviceDeath
elseif ($_GET['submit']==='serviceDeath') {}
## residence - currently
elseif ($_GET['submit']==='vetReside') {
  if (isset($_SESSION['vetReside'])) {
    $_SESSION['answered'][]='vetReside';
    if ($_SESSION['vetReside'] === 'Yes'){
      $_SESSION['eligibleResidence'] = 'Yes';
    } else {
      $_SESSION['eligibleResidence'] = 'No';
    }
    $_SESSION['current']='maritalStatus';
  } else {
    $_SESSION['errors']['vetReside'] = 'Please select YES or NO';
  }
}
## residence - prior to service
elseif ($_GET['submit']==='vetResidePrior') {}
## residence - 3 years continuous
elseif ($_GET['submit']==='vetReside3Years') {}
## family - marital status
elseif ($_GET['submit']==='maritalStatus') {
  if (isset($_SESSION['maritalStatus'])) {
    $_SESSION['answered'][]='maritalStatus';
    if ($_SESSION['maritalStatus']==='Married') {
      $_SESSION['current']='liveWithSpouse';
    } else {
      $_SESSION['current']='children';
    }
  } else {
    $_SESSION['errors']['maritalStatus'] = 'Please select MARRIED or SINGLE';
  }

}
## family - if married, live with spouse
elseif ($_GET['submit']==='liveWithSpouse') {
  if (isset($_SESSION['liveWithSpouse'])) {
    $_SESSION['answered'][]='liveWithSpouse';
    $_SESSION['current']='children';
  } else {
    $_SESSION['errors']['liveWithSpouse'] = 'Please select YES or NO';
  }
}
## family - children
elseif ($_GET['submit']==='children') {
  $_SESSION['children'] = filter_var($_GET['children'], FILTER_VALIDATE_INT,array('options'=>array('min_range'=>0)));
  if (empty($_SESSION['children']) && $_SESSION['children'] !== 0){
    $_SESSION['errors']['children'] = 'Please enter a valid number of children (eg 0,1,2...)';
    $_SESSION['children'] = $_GET['children'];
  } else {
    $_SESSION['answered'][]='children';
    $_SESSION['current']='earnedIncome';
  }
}
## finances - earned income
elseif ($_GET['submit']==='earnedIncome') {
  $_SESSION['earnedIncome'] = filter_var($_GET['earnedIncome'], FILTER_VALIDATE_INT,array('options'=>array('min_range'=>0)));
  if (empty($_SESSION['earnedIncome']) && $_SESSION['earnedIncome'] !== 0){
    $_SESSION['errors']['earnedIncome'] = 'Please enter a valid dollar amount (0 is OK)';
    $_SESSION['earnedIncome'] = $_GET['earnedIncome'];
  } else {
    $_SESSION['answered'][]='earnedIncome';
    $_SESSION['current']='otherIncome';
  }
}
## finances - other income
elseif ($_GET['submit']==='otherIncome') {
  $_SESSION['otherIncome'] = filter_var($_GET['otherIncome'], FILTER_VALIDATE_INT,array('options'=>array('min_range'=>0)));
  if (empty($_SESSION['otherIncome']) && $_SESSION['otherIncome'] !== 0){
    $_SESSION['errors']['otherIncome'] = 'Please enter a valid dollar amount (0 is OK)';
    $_SESSION['otherIncome'] = $_GET['otherIncome'];
  } else {
    $_SESSION['answered'][]='otherIncome';
    $_SESSION['current']='otherBenefits';
  }
}
## finances - other/REBA benefits
elseif ($_GET['submit']==='otherBenefits') {
  if ($_SESSION['maritalStatus']==='Married') {
    if (isset($_SESSION['otherBenefits']) && isset($_SESSION['spouseOtherBenefits'])) {
      $_SESSION['answered'][]='otherBenefits';
      $_SESSION['current']='payMedicareB';
    } else {
      if (!isset($_SESSION['otherBenefits'])) {
        $_SESSION['errors']['otherBenefits'] = 'Please select YES or NO';
      }
      if (!isset($_SESSION['spouseOtherBenefits'])) {
        $_SESSION['errors']['spouseOtherBenefits'] = 'Please select YES or NO for your SPOUSE';
      }
    }
  } elseif ($_SESSION['maritalStatus']==='Single') {
    if (isset($_SESSION['otherBenefits'])) {
      $_SESSION['answered'][]='otherBenefits';
      $_SESSION['current']='payMedicareB';
    } else {
      $_SESSION['errors']['otherBenefits'] = 'Please select YES or NO';
    }
  }
}
## finances - pay medicare B premiums
elseif ($_GET['submit']==='payMedicareB') {
  if ($_SESSION['maritalStatus']==='Married') {
    if (isset($_SESSION['payMedicareB']) && isset($_SESSION['spousePayMedicareB'])) {
      $_SESSION['answered'][]='payMedicareB';
      $_SESSION['current']='assetsMarried';
    } else {
      if (!isset($_SESSION['payMedicareB'])) {
        $_SESSION['errors']['payMedicareB'] = 'Please select YES or NO';
      }
      if (!isset($_SESSION['spousePayMedicareB'])) {
        $_SESSION['errors']['spousePayMedicareB'] = 'Please select YES or NO for your SPOUSE';
      }
    }
  } elseif ($_SESSION['maritalStatus']==='Single') {
    if (isset($_SESSION['payMedicareB'])) {
      $_SESSION['answered'][]='payMedicareB';
      $_SESSION['current']='assetsSingle';
    } else {
      $_SESSION['errors']['payMedicareB'] = 'Please select YES or NO';
    }
  }
}
## finances - assets if single
elseif ($_GET['submit']==='assetsSingle') {
  if (isset($_SESSION['assetsSingle'])) {
    $_SESSION['answered'][]='assetsSingle';
    if ($_SESSION['maritalStatus']==='Single' && $_SESSION['assetsSingle']==='Yes'){
      $_SESSION['eligibleAssets']='No'; // GO TO RESULTS ???? No, need to complete for spendown & medical
    } else {
      $_SESSION['eligibleAssets']='Yes';
    }
    $_SESSION['current']='shelterType';
  } else {
    $_SESSION['errors']['assetsSingle'] = 'Please select YES or NO';
  }
}
## finances - assets if married
elseif ($_GET['submit']==='assetsMarried') {
  if (isset($_SESSION['assetsMarried'])) {
    $_SESSION['answered'][]='assetsMarried';
    if ($_SESSION['maritalStatus']==='Married' && $_SESSION['assetsMarried']==='Yes'){
      $_SESSION['eligibleAssets']='No'; // GO TO RESULTS ???? No, need to complete for spendown & medical
    } else {
      $_SESSION['eligibleAssets']='Yes';
    }
    $_SESSION['current']='shelterType';
  } else {
    $_SESSION['errors']['assetsMarried'] = 'Please select YES or NO';
  }
}
## housing - shelter type
elseif ($_GET['submit']==='shelterType') {
  if (isset($_SESSION['shelterType'])) {
    $_SESSION['answered'][]='shelterType';
    if ($_SESSION['shelterType']==='Institutional') {
      $_SESSION['current']='results'; // GO TO RESULTS ??? do not need housing/heating costs because = 0
    } else {
      $_SESSION['current']='housingCost';
    }
  } else {
    $_SESSION['errors']['shelterType'] = 'Please select your type of housing';
  }

}
## housing - housing cost
elseif ($_GET['submit']==='housingCost') {
  $_SESSION['housingCost'] = filter_var($_GET['housingCost'], FILTER_VALIDATE_INT,array('options'=>array('min_range'=>0)));
  if (empty($_SESSION['housingCost']) && $_SESSION['housingCost'] !== 0){
    $_SESSION['errors']['housingCost'] = 'Please enter a valid dollar amount (0 is OK)';
    $_SESSION['housingCost'] = $_GET['housingCost'];
  } else {
    $_SESSION['answered'][]='housingCost';
    $_SESSION['current']='heatingCost';
  }
}
## housing - heating cost
elseif ($_GET['submit']==='heatingCost') {
  $_SESSION['heatingCost'] = filter_var($_GET['heatingCost'], FILTER_VALIDATE_INT,array('options'=>array('min_range'=>0)));
  if (empty($_SESSION['heatingCost']) && $_SESSION['heatingCost'] !== 0){
    $_SESSION['errors']['heatingCost'] = 'Please enter a valid dollar amount (0 is OK)';
    $_SESSION['heatingCost'] = $_GET['heatingCost'];
  } else {
    $_SESSION['answered'][]='heatingCost';
    $_SESSION['current']='results'; // GO TO RESULTS
  }
}

#
# determine if individual validations resulted in errors
#
$_SESSION['hazError'] = False;
foreach ($_SESSION['errors'] as $value) {
  if ($value){
    $_SESSION['hazError'] = True;
  }
}
#
#  NAVIGATION HANDLING
#
# NAVIGATION AFTER ALL VALIDATION AND CALCULATIONS
// modify index based on Back/Continut submission and presence of errors
// if (!$_SESSION['hazError'] && $_GET['submit']==='Continue') {
//   $_SESSION['index'] += 1;
// } elseif (!$_SESSION['hazError'] && $_GET['submit']==='Back') {
//   $_SESSION['index'] -= 1;
// }
//redirect to user page
session_write_close();
header("Location: index.php#spot"); /* Redirect browser */
exit(0);
// print('<pre>');
// echo 'Session ';
// print_r($_GET);
// print_r($_SESSION);
// print('</pre>');


ob_flush();
} // End of isset submit
#
#  FUNCTIONS
#
// date validation for PHP >=5.3
function service_Date_Validate($dateName){
  $date = DateTime::createFromFormat('m/d/y', $_SESSION[$dateName]);
  $date_errors = DateTime::getLastErrors();
  if ($date_errors['warning_count'] + $date_errors['error_count'] > 0) {
    $_SESSION['errors'][$dateName] = 'Invalid Date <br> (format as mm/dd/yy)';
    return;
  }
  $futureCutoff = new DateTime('+10 years');
  $pastCutoff = new DateTime('-100 years');
  //fix unix epoch problems with years befor 1970. Any date that comes up 10 years from now, will be set to 1900 version
  if ($date >= $futureCutoff) { $date->modify('-100 years');}
  if ($date > $futureCutoff || $date < $pastCutoff ){
    $_SESSION['errors'][$dateName] = 'Date outside reasonable date range';
  }
  $_SESSION[$dateName] = $date->format('m/d/y');
}
// check user service dates against MGL definition of veteran
function is_Wartime($serviceStart, $serviceEnd){
  //These dates reflect MGL c4 s7 cl43rd, not including campaigns or merchant marines, as of 6/11/2013
  // this array of wars is used by isWartime function
  $wars = array(
      'WWI' => array( begin => new DateTime('6-Apr-1917'), end => new DateTime('11-Nov-1918') ),
      'WWII' => array( begin => new DateTime('16-Sep-1940'), end => new DateTime('25-Jul-1947') ),
      'Korea' => array( begin => new DateTime('25-Jun-1950'), end => new DateTime('31-Jan-1955') ),
      'Vetnam II' => array( begin => new DateTime('5-Aug-1964'), end => new DateTime('7-May-1975') ),
      'Persian Gulf (currently ongoing according to MA)' => array( begin => new DateTime('2-Aug-1990'), end => new DateTime() ),
      'Korea Defense Service Medal' => array( begin => new DateTime('28-Jul-1954'), end => new DateTime(), extraInputName => 'kdsm', extraInputValue => 'Yes' ),
      'Lebanese Peacekeeping Force' => array( begin => new DateTime('25-Aug-1982'), end => new DateTime('1-Dec-1987'), extraInputName => 'campaigns', extraInputValue => 'Lcampaign' ),
      'Grenada Rescue Mission' => array( begin => new DateTime('25-Oct-1983'), end => new DateTime('15-Dec-1983'), extraInputName => 'campaigns', extraInputValue => 'Gcampaign' ),
      'Panamanian Intervention Force' => array( begin => new DateTime('20-Dec-1989'), end => new DateTime('31-Jan-1990'), extraInputName => 'campaigns', extraInputValue => 'Pcampaign' )
  );
  // loop through war date ranges looking for overlap with service
  foreach ($wars as $name => $property) {
    //echo $name." ".$wars[$name]['begin']->format('m/d/Y')."-".$wars[$name]['end']->format('m/d/Y')."<br>";
    if (empty($wars[$name]['extraInputName'])) { //No extra input needed to meet threshold
      if ( !( $serviceStart > $wars[$name]['end'] || $wars[$name]['begin'] > $serviceEnd) ) {
      // inverse of No overlap = overlap = wartime service
        return $name; //leave function because met wartime service threshold    }
      }
    }
    else { // extra input needed to meet threshold
      if (is_array($_SESSION[$wars[$name]['extraInputName']])) { // campaign checkboxes are in array
        foreach ($_SESSION[$wars[$name]['extraInputName']] as $value) {
          if ( $value === $wars[$name]['extraInputValue'] &&
            !( $serviceStart > $wars[$name]['end'] || $wars[$name]['begin'] > $serviceEnd) ) {
            // inverse of No overlap = overlap = wartime service
            return $name; //leave function because met wartime service threshold    }
          }
        }
      }
      else { //kdsm is not in array
        if ( $_SESSION[$wars[$name]['extraInputName']] === $wars[$name]['extraInputValue'] &&
          !( $serviceStart > $wars[$name]['end'] || $wars[$name]['begin'] > $serviceEnd) ) {
          // inverse of No overlap = overlap = wartime service
          return $name; //leave function because met wartime service threshold    }
        }
      }
    }
    // Did not meet threshold by three checks above = not wartime service.
  }
} // end of isWartime
// !!! Doesnt include disqualifiers yet
function is_Eligible_Service($serviceStart, $serviceEnd){
  $peacetimeMin = new DateInterval('P180D');
  $wartimeMin = new DateInterval('P90D');
  if ( $_SESSION['serviceDays'] >= $peacetimeMin->d ){
    Return '>=180 days Active Duty';
  }
  elseif ( is_Wartime($serviceStart,$serviceEnd) && ($_SESSION['serviceDays'] >= $wartimeMin->d) ){
    Return '>=90 days Active Duty, at least 1 during wartime';
  }
  elseif ( $_GET['purpleHeart']   === "Yes" ||
       $_GET['serviceDisability'] === "Yes" ||
       $_GET['serviceDeath']      === "Yes" ) {
    Return 'Purple Heart, Service-Connected Disbility, or Service Death';
  }
}
?>