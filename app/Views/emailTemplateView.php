<!DOCTYPE html>
<html lang="en">
<head>
<title>Soccer You</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style type="text/css">
body { padding:0 !important; margin:0 !important; display:block !important; min-width:100% !important; width:100% !important; -webkit-text-size-adjust:none }
* {box-sizing: border-box;}
table {border-collapse: collapse;border:0;font-family: "Poppins", sans-serif;}
table td, table th {vertical-align: top;padding: 20px;}
table.header td {
	vertical-align: middle;
}
p {
    margin-top: 0;
}
table.blue_table a {
	color: #fff;
    text-decoration: none;
}
table a {
	color: inherit;
	text-decoration: none;
}
</style>
</head>
<body class="body" style="padding:0 !important; margin:0 !important; display:block !important; min-width:100% !important; width:100% !important;-webkit-text-size-adjust:none">
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tbody>
			<tr>
				<td align="center" valign="top">
					<table width="600" border="0" cellspacing="0" cellpadding="0" class="mobile-shell">
						<tbody>
							<tr>
								<td class="td" style="width:600px; min-width:600px; padding:0; margin:0; font-weight:normal; margin:0; background: #072944;color: #fff;">
									<table width="100%" border="0" cellspacing="0" cellpadding="0">
										<tr>
											<td style="padding: 0;">
												<table width="100%" border="0" cellspacing="0" cellpadding="0" class="header">
													<tr>
														<td>
															<!-- <img src="<= site_url('uploads/email_white-logo.png');  ?>" style="width: auto; max-width: 200px;"> -->
															<img src="<?= site_url('uploads/email_white-logo-new.png');  ?>" width="200px" height="90px" style="width: 200px; max-width: 200px; object-fit: contain;">
														</td>
														<td align="right" style="white-space: nowrap;">
															<a href="https://www.instagram.com/succer_you_sports_ag/" target="_blank"><img src="<?= site_url('uploads/email_instagram.png');  ?>" alt="#"></a>
															<a href="https://www.linkedin.com/company/105691650" target="_blank"><img src="<?= site_url('uploads/email_linkedin.png');  ?>" alt="#"></a>
														</td>
													</tr>
												</table>
											</td>
										</tr>
										<tr>
											<td style="padding-top: 5px; padding-bottom: 5px;">
												<table width="100%" border="0" cellspacing="0" cellpadding="0" class="white_table" style="background: #fff; color: #072944;">
													<tr>
														<td>

                                                            <?= $message; ?>

															<!-- <p>Hi First Name,</p>
															<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p> -->
															<p><?= $footer['greeting']; ?><br /><?= $footer['greetingName']; ?></p>
															<!-- <img src="<= site_url('uploads/email_black-logo.png'); ?>" style="width: 160px; margin: 15px 0;"> -->
															<img src="<?= site_url('uploads/email_blue-logo.png'); ?>" width="160px" style="width: 160px; margin: 15px 0;">
															<!-- <p>Succer You Sports AG<br />Ida-Sträuli-Strasse 95 I CH-8404 Winterthur I Schweiz</p>
															<p>E-Mail: <a href="mailto: info@socceryou.ch">info@socceryou.ch</a><br />Website: <a href="www.socceryou.ch">www.socceryou.ch</a></p> -->

															<p><?= $footer['company']; ?><br /><?= $footer['address']; ?></p>
															<p><?= $footer['emailText']; ?>: <a href="mailto: <?= $footer['email']; ?>"><?= $footer['email']; ?></a><br /><?= $footer['websiteText']; ?>: <a href="<?= $footer['website']; ?>"><?= $footer['website']; ?></a></p>

														</td>
													</tr>
												</table>
											</td>
										</tr>
										<tr>
											<td>
												<table width="100%" border="0" cellspacing="0" cellpadding="0" class="blue_table">
													<tr>
														<td style="padding: 0; font-size: 14px;">
															<p><?= $disclaimerText ?? ''; ?></p>
															<!-- <p>Diese Nachricht ist ausschliesslich für den oder die angeführten Empfänger bestimmt und kann vertrauliche oder rechtlich geschützte Informationen beinhalten. Drittpersonen, welche nicht Adressaten sind oder deren Adresse versehentlich aufgeführt ist, werden gebeten, uns per Antwortmail zu informieren und das Mail samt allfälligen Anhängen ohne weitere Verwendung zu löschen. Die Nachrichtenübermittlung per E-Mail kann unsicher und fehlerbehaftet sein. Sie kann überdies von Dritten abgefangen oder überwacht werden.</p>
															<p>This message is for the addressee only and may contain confidential or privileged information. If you are not the intended recipient, you are kindly requested to inform us by return mail and to delete this mail together with any annexes without using it further. E-mail communications may be unsecure or contain errors. They may be intercepted or monitored by third parties.</p> -->
														</td>
													</tr>
												</table>
											</td>
										</tr>
                                        
										<tr>
											<td style="background: #BDE34F; color: #072944;padding: 0;">
												<table width="100%" border="0" cellspacing="0" cellpadding="0" class="blue_table">
													<tr>
														<td style="text-align: center;">© 2024 Succer You Sports AG, All Rights Reserved.</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
</div>

</body>
</html>