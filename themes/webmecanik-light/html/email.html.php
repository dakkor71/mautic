<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0" />
<!--[if gte mso 9]><xml>
<o:OfficeDocumentSettings>
<o:AllowPNG/>
<o:PixelsPerInch>96</o:PixelsPerInch>
</o:OfficeDocumentSettings>
</xml><![endif]-->
<?php $view['assets']->outputHeadDeclarations(); ?>
</head>
<body bgcolor="#ece9e8">
	<center>
	<link href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:700,400' rel='stylesheet' type='text/css'>
	<style type="text/css">
	<!--
	@import url(https://fonts.googleapis.com/css?family=Source+Sans+Pro:700,400);
	html { background-color: #ece9e8 }
	body { background-color: #ece9e8; overflow-x: hidden; margin: 0 !important; padding: 0 !important; color: #615654 }
	* { margin: 0; padding: 0, margin: 0 !important; padding: 0 !important }
	h1 { color: #615654; padding: 1px 20px; font-size: 28px; text-transform: uppercase }
	h2 { color: #76ABA9; padding: 0 0 10px; font-size: 28px; text-transform: uppercase }
	h3 { padding: 0 0 10px }
	p { margin: 0 0 10px }
	a,
	a:link{ color: #615654; text-decoration: none; color: #615654 !important; text-decoration: none !important }
	a:hover { text-decoration: none }
	td.cta_td a,
	td.cta_td a:link { display: block; padding: 10px 0; color: #ffffff; font-weight: bold; padding: 10px 0 !important; color: #ffffff !important; font-weight: bold !important }
	td.grey_td a,
	td.grey_td a:link { color: #5E0A4F; font-weight: bold; color: #5E0A4F !important; font-weight: bold !important }
	.footer_table { font-size: 12px }
	
	@media only screen and (max-width: 600px) {
		.header_logo { padding-bottom: 25px }
		table { width: 100% !important; max-width: 100% !important }
		table tr td { width: 100% !important; display: block !important; padding: 0 !important }
		table tr td img { max-width: 490px; height: auto }
		.header_table tr td { text-align: center !important }
		td.align_center_resp { text-align: center !important }
	}
	@media only screen and (max-width: 500px) {
		table tr td img { max-width: 390px; height: auto }
	}
	@media only screen and (max-width: 400px) {
		table tr td img { max-width: 290px; height: auto }
	}
	-->
	</style>
	<!--[if gte mso 9]>
	<style type="text/css">
		td, font, p, b, a, h1 { font-family: "Arial","sans-serif"; }
		table tr td { width: 100%; display: block; padding: 0 }
		table tr td img { max-width: 290px; height: auto }
		.header_table tr td { text-align: center !important; width: 100% !important; display: block !important; padding: 0 }
	</style>
	<![endif]-->
	<center>
		<table style="color: #615654; background-color: #ece9e8; width: 600px; max-width: 600px; font-family: 'Source Sans Pro', Arial, sans-serif; font-size: 12px; mso-cellspacing: 0px; mso-padding-alt: 0px;" bgcolor="#ece9e8" border="0" cellpadding="0" cellspacing="0" width="600px" class="header_table">
			<tr height="16px" style="height: 16px"><td colspan="3">&nbsp;</td></tr>
			<tr>
				<td align="center" valign="top" style="text-align: center">
				<?php $view['slots']->output('wmk_lien_browser'); ?>
				</td>
			</tr>
			<tr>
				<td align="center" valign="top" style="text-align: center">
					<center>
					<?php $view['slots']->output('wmk_header_logo'); ?>
					</center>
				</td>
			</tr>
			<tr height="16px" style="height: 16px"><td colspan="3">&nbsp;</td></tr>
		</table>
		<table style="color: #615654; background-color: #ffffff; width: 600px; max-width: 600px; font-family: 'Source Sans Pro', Arial, sans-serif; font-size: 16px; mso-cellspacing: 0px; mso-padding-alt: 0px; border-top: 4px solid #76afad; border-bottom: 4px solid #76afad" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="600px">
			<tr height="32px" style="height: 32px"><td>&nbsp;</td></tr>
			<tr>
				<td align="center" valign="top" style="text-align: center">
					<center>
					<?php $view['slots']->output('wmk_header_title'); ?>
					</center>
				</td>
			</tr>
			<tr height="16px" style="height: 16px"><td>&nbsp;</td></tr>
			<tr>
				<td align="center" valign="top" style="text-align: center">
				<?php $view['slots']->output('wmk_header_image'); ?>
				</td>
			</tr>
			<tr>
				<td align="center" valign="top" style="text-align: center">
					<center>
						<table width="450px" border="0" cellpadding="0" cellspacing="0" style="width: 450px; mso-cellspacing: 0px; mso-padding-alt: 0px;">
								<tr height="12px" style="height: 12px"><td>&nbsp;</td></tr>
								<tr>
									<td align="center" valign="top" style="text-align: center">
									<?php $view['slots']->output('wmk_main_intro'); ?>
									</td>
								</tr>
								<tr height="12px" style="height: 12px"><td>&nbsp;</td></tr>
								<tr>
									<td align="center" valign="top" style="text-align: center">
										<center>
											<table width="250px" border="0" cellpadding="0" cellspacing="0" style="width: 250px; mso-cellspacing: 0px; mso-padding-alt: 0px;">
												<tr>
													<td align="center" valign="middle" style="text-align: center; vertical-align: middle; width: 125px" width="125px">
													<?php $view['slots']->output('wmk_main_intro_image'); ?>
													</td>
													<td align="center" valign="middle" style="text-align: center; vertical-align: middle; width: 125px" width="125px" class="align_center_resp">
													<?php $view['slots']->output('wmk_main_intro_name'); ?>
													</td>
												</tr>
											</table>
										</center>
									</td>
								</tr>
								<tr height="24px" style="height: 24px"><td>&nbsp;</td></tr>
								<tr>
									<td align="center" valign="top" style="text-align: center">
										<table width="450px" border="0" cellpadding="0" cellspacing="0" style="width: 450px; mso-cellspacing: 0px; mso-padding-alt: 0px; border: 4px solid #ece9e8">
											<tr>
												<td align="center" valign="top" style="text-align: center">
													<center>
														<table width="430px" border="0" cellpadding="0" cellspacing="15" style="width: 430px; mso-cellspacing: 15px; mso-padding-alt: 0px">
															<tr>
																<td align="left" valign="top" style="text-align: left">
																<?php $view['slots']->output('wmk_main_section_2_title'); ?>
																</td>
															</tr>
														</table>
													</center>
												</td>
											</tr>
											<tr>
												<td align="center" valign="top" style="text-align: center">
												<?php $view['slots']->output('wmk_main_section_2_image'); ?>
												</td>
											</tr>
											<tr>
												<td align="center" valign="top" style="text-align: center">
													<center>
														<table width="430px" border="0" cellpadding="0" cellspacing="15" style="width: 430px; mso-cellspacing: 15px; mso-padding-alt: 0px">
															<tr>
																<td align="left" valign="top" style="text-align: left">
																<?php $view['slots']->output('wmk_main_section_2_description'); ?>
																</td>
															</tr>
														</table>
													</center>
												</td>
											</tr>
											<tr height="48px" style="height: 48px"><td>&nbsp;</td></tr>
											<tr>
												<td align="center" valign="top" bgcolor="#5E0A4F" style="text-align: center; background-color: #5E0A4F" class="cta_td">
													<center>
													<?php $view['slots']->output('wmk_main_section_2_link'); ?>
													</center>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr height="36px" style="height: 36px"><td>&nbsp;</td></tr>
								<tr>
									<td align="center" valign="top" style="text-align: center">
										<table width="450px" border="0" cellpadding="0" cellspacing="2" style="width: 450px; mso-cellspacing: 2px; mso-padding-alt: 0px">
											<tr>
												<td align="left" valign="top" style="text-align: left; background-color: #ece9e8;" bgcolor="#ece9e8">
													<table width="450px" border="0" cellpadding="0" cellspacing="15" style="width: 450px; mso-cellspacing: 15px; mso-padding-alt: 0px">
														<tr>
															<td align="left" valign="top" style="text-align: left" class="grey_td">
															<?php $view['slots']->output('wmk_secondary_section_2_1'); ?>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr height="36px" style="height: 36px"><td>&nbsp;</td></tr>
						</table>
					</center>
				</td>
			</tr>
		</table>
		<table style="color: #615654; background-color: #ece9e8; width: 600px; max-width: 600px; font-family: 'Source Sans Pro', Arial, sans-serif; font-size: 12px; mso-cellspacing: 0px; mso-padding-alt: 0px;" bgcolor="#ece9e8" border="0" cellpadding="0" cellspacing="0" width="600px" class="footer_table responsive_table">
			<tr height="16px" style="height: 16px"><td>&nbsp;</td></tr>
			<tr>
				<td align="center" valign="top" style="text-align: center">
					<center>
					<?php $view['slots']->output('wmk_footer'); ?>
					</center>
				</td>
			</tr>
			<tr height="16px" style="height: 16px"><td>&nbsp;</td></tr>
		</table>
	</center>
	<?php $view['slots']->output('builder'); ?>
</body>
</html>