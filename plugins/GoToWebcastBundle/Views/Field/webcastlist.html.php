<?php
/**
 * @package     GoToWebcast
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */


 // Liste des webcasts disponibles
$webcastlist = unserialize($field['properties']['webcastlist_serialized']);

$inForm = isset($inForm) ? $inForm : false;
$formId = isset($formId) ? $formId : 0;
$formName = isset($formName) ? $formName : '';
$defaultInputFormClass = ' not-chosen';
$defaultInputClass     = 'selectbox';
$containerType         = 'select';


// Appel du fichier 'field_helper.php' présent dans MauticFormBundle/Views/Field, nécessaire à l'affichage correct du champ
include $field['properties']['mautic_field_helper_path'];


// $formButtons permet d'afficher les boutons 'edit' et 'delete' dans la configuration du formulaire
// Dans le rendu pour l'utilisateur final, cet élément est ignoré
$formButtons = '';
if ( !empty($inForm)) {
	$formButtons = $view->render('MauticFormBundle:Builder:actions.html.php', array(
		'deleted'  => (!empty($deleted)) ? $deleted : false,
		'id'       => $id,
		'formId'   => $formId,
		'formName' => $formName
	));
}

$label = '';
if ($field['showLabel']) {
	$label = "<label $labelAttr>".$view->escape($field['label'])."</label>";
}

$help = '';
if ( !empty($field['helpMessage'])) {
	$help = "<span class='mauticform-helpmessage'>" . $field['helpMessage'] . "</span>";
}

$options = array();

if ( !empty($inForm) && empty($webcastlist)) {
	$errorMessage = $view['translator']->trans('plugin.gotowebcast.field.webcastlist.error');
	$options[] = "<option>$errorMessage</option>";
}

if ( !empty($properties['empty_value'])) {
	$emptyValue = $view->escape( $properties['empty_value'] );
	$options[] = "<option value=''> $emptyValue </option>";
}

if ($webcastlist) {
	foreach ($webcastlist as $webcastKey => $webcast) {
		$selected = ($webcast === $field['defaultValue']) ? ' selected="selected"' : '';
		$webcast = $view->escape($webcast);
		$options[] = "<option value='$webcastKey' $selected > $webcast </option>";
	}
}

$optionsHtml = implode('', $options);

$html = "<div $containerAttr>".
			$formButtons.
			$label.
			$help.
			"<select $inputAttr>".
				$optionsHtml.
			"</select>".
			"<span class='mauticform-errormsg' style='display: none;'>$validationMessage</span>".
		"</div>";

echo $html;
