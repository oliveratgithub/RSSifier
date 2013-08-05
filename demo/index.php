<?php
// Include the RSSifier file
include('../rssifier/rssifier.php');

// Handle data sent via HTML Input Form
if (isset($_POST['preview']) || isset($_POST['generate']))
{
	$RSSify->ValidateFormVars($_POST, array('preview', 'generate'));
	$redirectURL = $RSSify->OutputFeedURL(AppBaseURL, $_POST);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>RSSifier Demo Site</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="RSS XML-Feed Generator">
  <meta name="author" content="github@raduner.ch">
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/bootstrap-responsive.min.css" rel="stylesheet">
	<link href="css/style.css" rel="stylesheet">
  <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
  <!--[if lt IE 9]>
    <script src="js/html5shiv.js"></script>
  <![endif]-->

  <!-- Fav and touch icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="img/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="img/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="img/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="img/apple-touch-icon-57-precomposed.png">
    <link rel="shortcut icon" href="img/favicon.png">
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/scripts.js"></script>
</head>

<body>
<div class="container-fluid">
	<div class="row-fluid">
		<div class="span12">
			<div class="navbar">
				<div class="navbar-inner">
					<div class="container-fluid">
						<a data-target=".navbar-responsive-collapse" data-toggle="collapse" class="btn btn-navbar"><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></a> <a href="#" class="brand">RSSifier Demo</a>
						<div class="nav-collapse collapse navbar-responsive-collapse">
							<ul class="nav">
								<li class="active">
									<a href="index.php">Home</a>
								</li>
							</ul>
							<ul class="nav pull-right">
								<li>
									<a href="https://github.com/oliveratgithub/RSSifier">RSSifier @ Github</a>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span12">
			<?php
			// Just some (working) Demo Data...
			$predefinedValues = array(
								 'sourceURL' => 'http://www.heiden.ch/de/aktuelles/aktuellesinformationen/',
								 'baseURL' => 'http://www.heiden.ch',
								 'DOMType' => 'table',
								 'elementOccurrence' => 1,
								 'elementSelector' => ''
								);
			
			// Render the HTML Input Form
			echo (!empty($_POST)) ? $RSSify->GenericInputForm($_POST) : $RSSify->GenericInputForm($predefinedValues);
			
			// PREVIEW HTML
			if (isset($_POST['preview']) && $ErrorHandler->areThereAnyErrors()<=0)
			{ 
				echo '<div class="hero-unit">';
				$RSSify->RSS($_POST['sourceURL'], $_POST['baseURL'], $_POST['DOMtype'], $_POST['elementOccurrence']);
				echo '</div>';
			}
			// GENERATE FEED
			elseif (isset($_POST['generate']) && $ErrorHandler->areThereAnyErrors()<=0)
			{
				echo sprintf('
			<div class="alert alert-success">
				 <button type="button" class="close" data-dismiss="alert">×</button>
				<h4>
					%1$s
				</h4>
				<p><a href="%2$s" target="_blank">%2$s</a></p>
			</div>',
				'Et voilà - the URL to your new RSS-Feed',
				$redirectURL);
			}
			?>
		</div>
	</div>
<?php
// Show Errors, if any
if ($ErrorHandler->areThereAnyErrors()) {
	foreach($ErrorHandler->outputErrors() as $error) { ?>
	<div class="alert alert-error">
		<button type="button" class="close" data-dismiss="alert">×</button>
		<strong><?php echo $error; ?></strong>
	</div>
<?php }
} ?>
</div>
</body>
</html>