<?php
include $_SERVER['DOCUMENT_ROOT'].'/shared/shared.php';

IllaUser::requireLogin();

Page::Init();

includeWrapper::includeOnce( Page::getRootPath().'/community/account/inc_editinfos.php' );

$server = ( isset( $_GET['server'] ) && $_GET['server'] == '1' ? 'devserver' : 'illarionserver');
$charid = ( isset( $_GET['charid'] )  && is_numeric($_GET['charid']) ? (int)$_GET['charid'] : false );
if (!$charid)
{
	exit('Error - Character ID was not transfered correctly.');
}

$pgSQL =& Database::getPostgreSQL( $server );

$query = 'SELECT chr_race, chr_sex'
	.PHP_EOL.' FROM chars'
	.PHP_EOL.' WHERE chr_playerid = '.$pgSQL->Quote( $charid )
	.PHP_EOL.' AND chr_accid = '.$pgSQL->Quote( IllaUser::$ID )
	;
$pgSQL->setQuery( $query );
list( $race, $sex ) = $pgSQL->loadRow();

if ($race === null || $race === false)
{
	Messages::add( 'Character not found', 'error' );
	includeWrapper::includeOnce( Page::getRootPath().'/community/account/us_charlist.php' );
	exit();
}

$query = 'SELECT COUNT(*)'
.PHP_EOL.' FROM player'
.PHP_EOL.'WHERE ply_playerid = '.$pgSQL->Quote( $charid )
.PHP_EOL.' AND ply_strength != 0'
;
$pgSQL->setQuery( $query );
if ($pgSQL->loadResult())
{
	exit('Error - Values already set');
}

$query = 'SELECT *'
.PHP_EOL.' FROM "'.$server.'"."raceattr"'
.PHP_EOL.' WHERE "id" IN ( -1, '.$pgSQL->Quote( $race ).' )'
.PHP_EOL.' ORDER BY "id" DESC'
;
$pgSQL->setQuery( $query, 0, 1 );
$limits = $pgSQL->loadAssocRow();

$limits['curr_agility'] = $limits['minagility'];
$limits['curr_strength'] = $limits['minstrength'];
$limits['curr_constitution'] = $limits['minconstitution'];
$limits['curr_dexterity'] = $limits['mindexterity'];
$limits['curr_perception'] = $limits['minperception'];
$limits['curr_willpower'] = $limits['minwillpower'];
$limits['curr_intelligence'] = $limits['minintelligence'];
$limits['curr_essence'] = $limits['minessence'];
$limits['curr_remaining'] = $limits['maxattribs'] - ( $limits['curr_agility']+$limits['curr_strength']+$limits['curr_constitution']+$limits['curr_dexterity']+$limits['curr_perception']+$limits['curr_willpower']+$limits['curr_intelligence']+$limits['curr_essence'] );
$limits['minremaining'] = 0;
$limits['maxremaining'] = $limits['maxattribs'];

calculateLimits( $limits );
$limit_text = generateLimitTexts( $limits );

$db =& Database::getPostgreSQL( 'accounts' );
$query = 'SELECT attr_name_us AS name, attr_str AS str, attr_agi AS agi, attr_dex AS dex, attr_con AS con, attr_int AS int, attr_per AS per, attr_wil AS wil, attr_ess AS ess'
.PHP_EOL.' FROM attribtemp'
.PHP_EOL.' ORDER BY attr_id'
;
$db->setQuery( $query );
$templates = $db->loadAssocList();

Page::setXHTML();
Page::addJavaScript( 'prototype' );
Page::addJavaScript( 'effects' );
Page::addCSS( 'slider' );
Page::addJavaScript( 'slider' );
Page::addJavaScript( 'wz_tooltip' );

?>
<h1>Create a new character</h1>

<h2>Step 3</h2>

<p>You have to put in the attributes of your character here. You should think well about this, because the attributes will not change anymore in the game.</p>
<div>
	<form action="<?php echo Page::getURL(); ?>/community/account/us_newchar_4.php?charid=<?php echo $charid,($_GET['server'] == '1' ? '&amp;server=1' : ''); ?>" method="post" name="create_char" id="create_char">
		<div>
			<h2>Attributes</h2>

			<table style="width:100%">
				<tbody>
					<tr>
						<td>
							Package
						</td>
						<td style="width:423px;">
							<select id="attrib_pack">
								<option>none</option>
								<?php foreach($templates as $template): ?>
								<option value="<?php echo $template['str'],'|',$template['agi'],'|',$template['dex'],'|',$template['con'],'|',$template['int'],'|',$template['per'],'|',$template['wil'],'|',$template['ess']; ?>"><?php echo $template['name']; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<a title="" class="tooltip" onmouseover="Tip('Your Strength attribute affects the following: Concussion Weapons, Slashing Weapons and the Wrestling skill',TITLE,'Strength',WIDTH,-300);"  (<?php echo $limits['minstrength'],' - ',$limits['maxstrength']; ?>)
						</td>
						<td style="width:423px;">
							<?php include_slider( $limits, 'strength' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<a title="" class="tooltip" onmouseover="Tip('Your Agility attribute affects the following: Dodge, Parry, and Puncture Weapons',TITLE,'Agility',WIDTH,-300);" onmouseout="UnTip();">Agility</a> (<?php echo $limits['minagility'],' - ',$limits['maxagility']; ?>)
						</td>
						<td>
							<?php include_slider( $limits, 'agility' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<a title="" class="tooltip" onmouseover="Tip('Your Constitution attribute affects the following: Farming, Firing Bricks, Fishing, Herblore, Mining, and Woodcutting',TITLE,'Constitution',WIDTH,-300);" onmouseout="UnTip();">Constitution</a> (<?php echo $limits['minconstitution'],' - ',$limits['maxconstitution']; ?>)
						</td>
						<td>
							<?php include_slider( $limits, 'constitution' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<a title="" class="tooltip" onmouseover="Tip('Your Dexterity attribute affects the following: Carpentry, Baking/Cooking, Gemcutting, Glass Blowing, Goldsmithing, Instruments, Smithing, and Tailoring ',TITLE,'Dexterity',WIDTH,-300);" onmouseout="UnTip();">Dexterity</a> (<?php echo $limits['mindexterity'],' - ',$limits['maxdexterity']; ?>)
						</td>
						<td>
							<?php include_slider( $limits, 'dexterity' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<a title="" class="tooltip" onmouseover="Tip('Your Intelligence attribute affects the following: Alchemy',TITLE,'Intelligence',WIDTH,-300);" onmouseout="UnTip();">Intelligence</a> (<?php echo $limits['minintelligence'],' - ',$limits['maxintelligence']; ?>)
						</td>
						<td>
							<?php include_slider( $limits, 'intelligence' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<a title="" class="tooltip" onmouseover="Tip('Your Perception attribute affects the following: Alchemy, Distance Weapons, and Poisioning',TITLE,'Perception',WIDTH,-300);" onmouseout="UnTip();">Perception</a> (<?php echo $limits['minperception'],' - ',$limits['maxperception']; ?>)
						</td>
						<td>
							<?php include_slider( $limits, 'perception' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<a title="" class="tooltip" onmouseover="Tip('Your Willpower attribute affects the following: This attribute is not yet utilised',TITLE,'Willpower',WIDTH,-300);" onmouseout="UnTip();">Willpower</a> (<?php echo $limits['minwillpower'],' - ',$limits['maxwillpower']; ?>)
						</td>
						<td>
							<?php include_slider( $limits, 'willpower' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<a title="" class="tooltip" onmouseover="Tip('Your Essence attribute affects the following: Alchemy and Magic Resistance',TITLE,'Essence',WIDTH,-300);" onmouseout="UnTip();">Essence</a> (<?php echo $limits['minessence'],' - ',$limits['maxessence']; ?>)
						<td>
							<?php include_slider( $limits, 'essence' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							Remaining points
						</td>
						<td>
							<?php include_slider( $limits, 'remaining' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
			<?php include_attribute_js( $limits ); ?>
			<p style="text-align:center;padding-bottom:10px;">
				<input type="hidden" name="action" value="newchar_3" />
				<input type="submit" name="submit" value="Save data" />
			</p>
		</div>
	</form>
</div>
