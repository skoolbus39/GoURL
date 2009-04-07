<?php /* index.php ( lilURL implementation ) */

require_once 'includes/lilurl.php'; // <- lilURL class file


$lilurl = new lilURL();
$msg = '';

// if the form has been submitted
if ( isset($_POST['theURL']) )
{
	//First, build the $longurl by combining theURL and the GA stuff
	
	//Start by gathering all the GA items
	if ( isset($_POST['gaSource']) )
	{
		$gaTags = 'utm_source='.($_POST['gaSource']);
		$gaTags = $gaTags.'&utm_medium='.($_POST['gaMedium']);
		$gaTags = $gaTags.'&utm_term='.($_POST['gaTerm']);
		$gaTags = $gaTags.'&utm_content='.($_POST['gaContent']);
		$gaTags = $gaTags.'&utm_campaign='.($_POST['gaName']);
	}
	//http://www.unl.edu/?utm_source=source&utm_medium=medium&utm_term=term&utm_content=content&utm_campaign=name
	if (strpbrk($_POST['theURL'], '?')) //if the URL already contains a '?' then add GA stuff with '&' 
	{ 
		$longurl = $_POST['theURL'].'&'.$gaTags;
	}
	else // we don't have a '?', so use one in the URL
	{
		$longurl = $_POST['theURL'].'?'.$gaTags;
	}
	
	//escape bad characters from the user's url
	$longurl = trim(mysql_escape_string($longurl));

	// set the protocol to not ok by default
	$protocol_ok = false;
	
	// if there's a list of allowed protocols, 
	// check to make sure that the user's url uses one of them
	if ( count($allowed_protocols) )
	{
		foreach ( $allowed_protocols as $ap )
		{
			if ( strtolower(substr($longurl, 0, strlen($ap))) == strtolower($ap) )
			{
				$protocol_ok = true;
				break;
			}
		}
	}
	else // if there's no protocol list, screw all that
	{
		$protocol_ok = true;
	}
		
	// add the url to the database
	if ( $protocol_ok && $lilurl->add_url($longurl) )
	{
		if ( REWRITE ) // mod_rewrite style link
		{
			$url = 'http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF']).'/'.$lilurl->get_id($longurl);
		}
		else // regular GET style link
		{
			$url = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?id='.$lilurl->get_id($longurl);
		}

		$msg = '<p class="success">Your Go URL is: <a href="'.$url.'">'.$url.'</a></p>';
	}
	elseif ( !$protocol_ok )
	{
		$msg = '<p class="error">Your URL must begin with <code>http:</code>, <code>https:</code> or <code>mailto:</code>.</p>';
	}
	else
	{
		$msg = '<p class="error">Creation of your Go URL failed for some reason.</p>';
	}
}
else // if the form hasn't been submitted, look for an id to redirect to
{
	if ( isSet($_GET['id']) ) // check GET first
	{
		$id = mysql_escape_string($_GET['id']);
	}
	elseif ( REWRITE ) // check the URI if we're using mod_rewrite
	{
		$explodo = explode('/', $_SERVER['REQUEST_URI']);
		$id = mysql_escape_string($explodo[count($explodo)-1]);
	}
	else // otherwise, just make it empty
	{
		$id = '';
	}
	
	// if the id isn't empty and it's not this file, redirect to it's url
	if ( $id != '' && $id != basename($_SERVER['PHP_SELF']) && $id != 'index.php?login')
	{
		$location = $lilurl->get_url($id);
		
		if ( $location != -1 )
		{
			header('Location: '.$location);
		}
		else
		{
			$msg = '<p class="error">'.$id.' - Sorry, but that Go URL isn\'t in our database.</p>';
		}
	}
}

// print the form

?>
<script type="text/javascript" charset="utf-8">
$(document).ready(function () {
	$('.hint').hide();
	$('input').focus(function() {
		$('.hint').hide();
		$(this).siblings('.hint').show();
	});
	$('#gaSource'|'#gaMedium'|'#gaName').change(function() {
		if ($(this) != "") {
			$('#gaSource').parent('.element').parent('li').addClass('required');
			$('#gaMedium').parent('.element').parent('li').addClass('required');
			$('#gaName').parent('.element').parent('li').addClass('required');
		}
	});
});
</script>
<?php echo $msg; ?>
<form action="<?php echo $_SERVER['PHP_SELF']?>" method="post">
<p class="required">Indicates a required field.</p>
<fieldset>
	<legend>Basic Option</legend>
	<ol>
		<li class="required">
			<label for="theURL" class="element">Long URL</label>
			<div class="element"><input name="theURL" id="theURL" type="text" />
			</div>
		</li>
	</ol>
</fieldset>
<p class="submit"><input type="submit" id="submit1" name="submit" value="Create URL" /></p>
<p class="clear">This is a test.</p>
<fieldset>
	<legend>Custom Alias</legend>
	<p>If you would like to control the URL, then enter the alias you would like to use.</p>
	<?php if ($cas_client->isLoggedIn()) : ?>
	<ol>
		<li>
			<label for="theAlias" class="element">Alias</label>
			<div class="element"><input name="theAlias" id="theAlias" type="text" />			
			<span class="hint"><span class="hintPointer">&nbsp;</span>ex: go.unl.edu/<strong>admissions</strong></span>			
			</div>
		</li>
	</ol>
    <?php else: ?>
	<ol>
		<li>
			<label for="theAlias" class="element">Alias</label>
			<div class="element"><input name="theAlias" id="theAlias" type="text" disabled="disabled"/>			
			</div>
		</li>
	</ol>
    <p class="attention"><a href="?login">Please login to use this feature.</a></p>
    <?php endif; ?>
</fieldset>
<fieldset>
	<legend>Google Analytics Campaign Tagging</legend>
	<p>Add your campaign information here and it will be automatically added to your URL when redirected.</p>
	<ol>
		<li>
			<label for="gaSource" class="element">Source</label>
			<div class="element"><input name="gaSource" id="gaSource" type="text" />			
				<span class="hint"><span class="hintPointer">&nbsp;</span>referrer: google, facebook, twitter</span>
			</div>
		</li>	
		<li>
			<label for="gaMedium" class="element">Medium</label>
			<div class="element"><input name="gaMedium" id="gaMedium" type="text" />			
				<span class="hint"><span class="hintPointer">&nbsp;</span>marketing medium: email, web, banner</span>
			</div>
		</li>
		<li>
			<label for="gaTerm" class="element">Term</label>
			<div class="element"><input name="gaTerm" id="gaTerm" type="text" />		
				<span class="hint"><span class="hintPointer">&nbsp;</span>identify the keywords</span>
			</div>
		</li>
		<li>
			<label for="gaContent" class="element">Content</label>
			<div class="element"><input name="gaContent" id="gaContent" type="text" />			
				<span class="hint"><span class="hintPointer">&nbsp;</span>use to differentiate ads (A/B testing)</span>
				</div>
		</li>
		<li>
			<label for="gaName" class="element">Name</label>
			<div class="element"><input name="gaName" id="gaName" type="text" />			
				<span class="hint"><span class="hintPointer">&nbsp;</span>product, promo code, or slogan</span>
			</div>
		</li>	
	</ol>
</fieldset>
<p class="submit"><input type="submit" id="submit" name="submit" value="Create URL" /></p>
</form>
