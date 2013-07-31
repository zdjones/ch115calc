<h4>How many of your children do you currently support?</h4>
<div class='span8 help-block'>
  Please include any of your children who:
  <ul>
    <li>Live with you, or you regularly pay child support for</li>
  <b>AND</b>
    <li>Are under the age of 19 (under 23 if in high school or college)</li>
  </ul>
</div>
<div class='input-append'>
	<input type="text" class='span1' name='applChildren' id='applChildren'
	value='<?php echo isset($_SESSION['applChildren'])?htmlspecialchars($_SESSION['applChildren']):'';?>'  >
	<span class='add-on'>Children</span>
</div>
<?php echo isset($_SESSION['errors']['applChildren'])?$_SESSION['errors']['applChildren']:''; ?>