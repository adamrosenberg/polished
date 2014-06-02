<?php
	
	function oauth_session_exists() {
	  if((is_array($_SESSION)) && (array_key_exists('oauth', $_SESSION))) {
	    return TRUE;
	  } else {
	    return FALSE;
	  }
	}

	try {
	  // include the LinkedIn class
	  require_once('php/linkedin_3.2.0.class.php');
	  
	  // start the session
	  if(!session_start()) {
	    throw new LinkedInException('This script requires session support, which appears to be disabled according to session_start().');
	  }
	  
	  // display constants
	  $API_CONFIG = array(
	    'appKey'       => '752yf1ypeeawaj',
		'appSecret'    => 'vKTwFDoLPuG1jemt',
		'callbackUrl'  => NULL 
	  );
	  define('DEMO_GROUP', '4010474');
	  define('DEMO_GROUP_NAME', 'Simple LI Demo');
	  define('PORT_HTTP', '80');
	  define('PORT_HTTP_SSL', '443');

	  // set index
	  $_REQUEST[LINKEDIN::_GET_TYPE] = (isset($_REQUEST[LINKEDIN::_GET_TYPE])) ? $_REQUEST[LINKEDIN::_GET_TYPE] : '';
	  switch($_REQUEST[LINKEDIN::_GET_TYPE]) {
	    case 'initiate':
	      /**
	       * Handle user initiated LinkedIn connection, create the LinkedIn object.
	       */
	        
	      // check for the correct http protocol (i.e. is this script being served via http or https)
	      if($_SERVER['HTTPS'] == 'on') {
	        $protocol = 'https';
	      } else {
	        $protocol = 'http';
	      }
	      
	      // set the callback url
	      $API_CONFIG['callbackUrl'] = $protocol . '://' . $_SERVER['SERVER_NAME'] . ((($_SERVER['SERVER_PORT'] != PORT_HTTP) || ($_SERVER['SERVER_PORT'] != PORT_HTTP_SSL)) ? ':' . $_SERVER['SERVER_PORT'] : '') . $_SERVER['PHP_SELF'] . '?' . LINKEDIN::_GET_TYPE . '=initiate&' . LINKEDIN::_GET_RESPONSE . '=1';
	      $OBJ_linkedin = new LinkedIn($API_CONFIG);
	      
	      // check for response from LinkedIn
	      $_GET[LINKEDIN::_GET_RESPONSE] = (isset($_GET[LINKEDIN::_GET_RESPONSE])) ? $_GET[LINKEDIN::_GET_RESPONSE] : '';
	      if(!$_GET[LINKEDIN::_GET_RESPONSE]) {
	        // LinkedIn hasn't sent us a response, the user is initiating the connection
	        
	        // send a request for a LinkedIn access token
	        $response = $OBJ_linkedin->retrieveTokenRequest();
	        if($response['success'] === TRUE) {
	          // store the request token
	          $_SESSION['oauth']['linkedin']['request'] = $response['linkedin'];
	          
	          // redirect the user to the LinkedIn authentication/authorisation page to initiate validation.
	          header('Location: ' . LINKEDIN::_URL_AUTH . $response['linkedin']['oauth_token']);
	        } else {
	          // bad token request
	          echo "Request token retrieval failed:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre><br /><br />LINKEDIN OBJ:<br /><br /><pre>" . print_r($OBJ_linkedin, TRUE) . "</pre>";
	        }
	      } else {
	        // LinkedIn has sent a response, user has granted permission, take the temp access token, the user's secret and the verifier to request the user's real secret key
	        $response = $OBJ_linkedin->retrieveTokenAccess($_SESSION['oauth']['linkedin']['request']['oauth_token'], $_SESSION['oauth']['linkedin']['request']['oauth_token_secret'], $_GET['oauth_verifier']);
	        if($response['success'] === TRUE) {
	          // the request went through without an error, gather user's 'access' tokens
	          $_SESSION['oauth']['linkedin']['access'] = $response['linkedin'];
	          
	          // set the user as authorized for future quick reference
	          $_SESSION['oauth']['linkedin']['authorized'] = TRUE;
	            
	          // redirect the user back to the demo page
	          header('Location: ' . $_SERVER['PHP_SELF']);
	        } else {
	          // bad token access
	          echo "Access token retrieval failed:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre><br /><br />LINKEDIN OBJ:<br /><br /><pre>" . print_r($OBJ_linkedin, TRUE) . "</pre>";
	        }
	      }
	      break;

	    case 'revoke':
	      /**
	       * Handle authorization revocation.
	       */
	                    
	      // check the session
	      if(!oauth_session_exists()) {
	        throw new LinkedInException('This script requires session support, which doesn\'t appear to be working correctly.');
	      }
	      
	      $OBJ_linkedin = new LinkedIn($API_CONFIG);
	      $OBJ_linkedin->setTokenAccess($_SESSION['oauth']['linkedin']['access']);
	      $response = $OBJ_linkedin->revoke();
	      if($response['success'] === TRUE) {
	        // revocation successful, clear session
	        session_unset();
	        $_SESSION = array();
	        if(session_destroy()) {
	          // session destroyed
	          header('Location: ' . $_SERVER['PHP_SELF']);
	        } else {
	          // session not destroyed
	          echo "Error clearing user's session";
	        }
	      } else {
	        // revocation failed
	        echo "Error revoking user's token:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre><br /><br />LINKEDIN OBJ:<br /><br /><pre>" . print_r($OBJ_linkedin, TRUE) . "</pre>";
	      }
	      break;
	    default:
	      // nothing being passed back, display demo page
	      
	      // check PHP version
	      if(version_compare(PHP_VERSION, '5.0.0', '<')) {
	        throw new LinkedInException('You must be running version 5.x or greater of PHP to use this library.'); 
	      } 
	      
	      // check for cURL
	      if(extension_loaded('curl')) {
	        $curl_version = curl_version();
	        $curl_version = $curl_version['version'];
	      } else {
	        throw new LinkedInException('You must load the cURL extension to use this library.'); 
	      }
?>

<html>

<head>
	<meta charset="UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />	
	<title>Polished - Sign In With Linkedin</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />

	<!-- stylesheets -->
	<link rel="stylesheet" type="text/css" href="css/compiled/theme.css">

	<!-- javascript -->
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="js/bootstrap/bootstrap.min.js"></script>
	<script src="js/theme.js"></script>

	<!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
    <script>
	  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	  ga('create', 'UA-51291045-1', 'getpolished.us');
	  ga('send', 'pageview');

	</script>
</head>
<body id="signup">
	<div class="container">
		<div class="row header">
			<div class="col-md-12">
				<h3 class="logo">
					<a href="index.html">Polished - Linkedin</a>
				</h3>
				<h4>Sign in using your Linkedin account</h4>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<div class="wrapper clearfix">
					<div class="formy">
						<div class="row">
							<div class="col-md-12">
								<form role="form">
							  		<div class="form-group">
							    		<label for="email">Email address</label>
							    		<input type="email" class="form-control" id="email" />
							  		</div>
							  		<div class="form-group">
							    		<label for="password">Password</label>
							    		<input type="password" class="form-control" id="password" />
							  		</div>
							  		<div class="checkbox">
							    		<label>
							      			<input type="checkbox"> Remember me
							    		</label>
							  		</div>
							  		<div class="submit">
							  			<a href="index.html" class="button-clear">
								  			<span>Sign in with my Linkedin account</span>
								  		</a>
							  		</div>
							  		<div>
										<?php
									          $_SESSION['oauth']['linkedin']['authorized'] = (isset($_SESSION['oauth']['linkedin']['authorized'])) ? $_SESSION['oauth']['linkedin']['authorized'] : FALSE;
									          if($_SESSION['oauth']['linkedin']['authorized'] === TRUE) {
									            $OBJ_linkedin = new LinkedIn($API_CONFIG);
									            $OBJ_linkedin->setTokenAccess($_SESSION['oauth']['linkedin']['access']);
									          	$OBJ_linkedin->setResponseFormat(LINKEDIN::_RESPONSE_XML);
									            ?>
									            <!--ul>
									              <li><a href="#manage">Manage LinkedIn Authorization</a></li>
									              <li><a href="#application">Application Information</a></li>
									              <li><a href="#profile">Your Profile</a></li>
									              <li><a href="php/demo/network.php">Your Network</a>
									                <ul>
									                  <li><a href="php/demo/network.php#networkStats">Stats</a></li>
									                  <li><a href="php/demo/network.php#networkConnections">Your Connections</a></li>
									                  <li><a href="php/demo/network.php#networkInvite">Invite Others to Join your LinkedIn Network</a></li>
									                  <li><a href="php/demo/network.php#networkUpdates">Recent Connection Updates</a></li>
									              	  <li><a href="php/demo/network.php#peopleSearch">People Search</a></li>
									                </ul>
									              </li>
									              <li><a href="php/demo/company.php">Company API</a>
									                <ul>
									                  <li><a href="php/demo/company.php#companySpecific">Specific Company</a></li>
									                  <li><a href="php/demo/company.php#companyFollowed">Followed Companies</a></li>
									                  <li><a href="php/demo/company.php#companySuggested">Suggested Companies</a></li>
									                  <li><a href="php/demo/company.php#companySearch">Company Search</a></li>
									                </ul>
									              </li>
									              <li><a href="php/demo/jobs.php">Jobs API</a>
									                <ul>
									                  <li><a href="php/demo/jobs.php#jobsBookmarked">Bookmarked Jobs</a></li>
									                  <li><a href="php/demo/jobs.php#jobsSuggested">Suggested Jobs</a></li>
									                  <li><a href="php/demo/jobs.php#jobsSearch">Jobs Search</a></li>
									                </ul>
									              </li>
									              <li><a href="php/demo/content.php">Creating / Sharing Content</a>
									                <ul>
									                  <li><a href="php/demo/content.php#contentUpdate">Post Network Update</a></li>
									                  <li><a href="php/demo/content.php#contentShare">Post Share</a></li>
									                </ul>
									              </li>
									              <?php
									            	
									            	// check if the viewer is a member of the test group
									            	$response = $OBJ_linkedin->group(DEMO_GROUP, ':(relation-to-viewer:(membership-state))');
									              if($response['success'] === TRUE) {
									          		  $result         = new SimpleXMLElement($response['linkedin']);
									          		  $membership     = $result->{'relation-to-viewer'}->{'membership-state'}->code;
									          		  $in_demo_group  = (($membership == 'non-member') || ($membership == 'blocked')) ? FALSE : TRUE;
										              ?>
										            	<li><a href="php/demo/groups.php">Groups API</a>
										                <ul>
										                  <li><a href="php/demo/groups.php#groupsSuggested">Suggested Groups</a></li>
										                  <li><a href="php/demo/groups.php#groupMemberships">Group Memberships</a></li>
										                  <li><a href="php/demo/groups.php#manageGroup">Manage '<?php echo DEMO_GROUP_NAME;?>' Group Membership</a></li>
										                  <?php 
										                  if($in_demo_group) {
										                    ?>
											                  <li><a href="php/demo/groups.php#groupSettings">Group Settings</a></li>
											                  <li><a href="php/demo/groups.php#groupPosts">Group Posts</a></li>
											                  <li><a href="php/demo/groups.php#createPost">Create a Group Post</a></li>
												                <?php 
											                }
											                ?>
											              </ul>
											            </li>
											            <?php 
												  		  } else {
												  		    // request failed
									          			echo "Error retrieving group membership information: <br /><br />RESPONSE:<br /><br /><pre>" . print_r ($response, TRUE) . "</pre>";
												  		  }
											          ?>
									            </ul-->
									            <?php
									          } else {
									            ?>
									            <ul>
									              <li><a href="#manage">Manage LinkedIn Authorization</a></li>
									            </ul>
									            <?php
									          }
									          ?>
									          
									          <hr />
									          
									          <h4 id="manage">Manage LinkedIn Authorization:</h4>
									          <?php
									          if($_SESSION['oauth']['linkedin']['authorized'] === TRUE) {
									            // user is already connected
									            ?>
									            <form id="linkedin_revoke_form" action="<?php echo $_SERVER['PHP_SELF'];?>" method="get">
									              <input type="hidden" name="<?php echo LINKEDIN::_GET_TYPE;?>" id="<?php echo LINKEDIN::_GET_TYPE;?>" value="revoke" />
									              <input type="submit" value="Revoke Authorization" />
									            </form>
									            
									            <hr />
									          
									            <h2 id="application">Application Information:</h2>
									            
									            <ul>
									              <li>Application Key: 
									                <ul>
									                  <li><?php echo $OBJ_linkedin->getApplicationKey();?></li>
									                </ul>
									              </li>
									            </ul>
									            
									            <hr />
									            
									            <h2 id="profile">Your Profile:</h2>
									            
									            <?php
									            $response = $OBJ_linkedin->profile('~:(id,first-name,last-name,picture-url)');
									            if($response['success'] === TRUE) {
									              $response['linkedin'] = new SimpleXMLElement($response['linkedin']);
									              echo "<pre>" . print_r($response['linkedin'], TRUE) . "</pre>";
									            } else {
									              // request failed
									              echo "Error retrieving profile information:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response) . "</pre>";
									            } 
									          } else {
									            // user isn't connected
									            ?>
									            <form id="linkedin_connect_form" action="<?php echo $_SERVER['PHP_SELF'];?>" method="get">
									              <input type="hidden" name="<?php echo LINKEDIN::_GET_TYPE;?>" id="<?php echo LINKEDIN::_GET_TYPE;?>" value="initiate" />
									              <input  class="submit" type="submit" value="Connect to LinkedIn"/>
									            </form>
									            <?php
									          }
									          ?>
									</div>
								</form>
							</div>
						</div>						
					</div>
				</div>
				<div class="already-account">
					Don't have an account?
					<a href="signup.html">Create one here</a>
				</div>
				
			</div>
		</div>
	</div>
</body>
</html>
<?php
      break;
  }
} catch(LinkedInException $e) {
  // exception raised by library call
  echo $e->getMessage();
}

?>
