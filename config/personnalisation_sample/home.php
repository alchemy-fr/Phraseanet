				<?php
					if(GV_captchas && trim(GV_captcha_private_key) !== '' && trim(GV_captcha_public_key) !== '')
					{
					?>
					<script type="text/javascript">
							var RecaptchaOptions = {
							   theme : 'custom',
							   tabindex : 3,
							   lang : '<?php echo $lng?>'
							};
							</script>
					<?php 
					}
				?>
					</head>
					<body>
						<div style="display:none;"><?php echo GV_metaDescription?></div>
						<noscript>
							<div style="width:100%;height:40px;background:#00a8FF;font-size:14px;font-weight:bold;text-align:center;">
								<?php echo _('phraseanet::noscript')?>
							</div>
						</noscript>
						<div id="wrongBrowser" style="display:none;text-align:center;width:100%;background:#00a8FF;font-size:14px;font-weight:bold;">
							<div>
							<?php echo _('phraseanet::browser not compliant').',<br/>',_('phraseanet::recommend browser')?>
							</div>
							<div style="height:30px;text-align:center;margin-top:15px;width:950px;margin-right:auto;margin-left:auto;">
									<?php
									if(strtolower(substr($client->find_platform(),0,7)) == 'windows')
									{
									?>
										<span style="margin:0 10px;padding:0;white-space:nowrap;height:25px;"><img style="vertical-align:middle;" src="/login/img/firefox.png"/> <a href="http://www.mozilla.com/firefox/" target="_blank">Mozilla Firefox 3</a></span>
										<span style="margin:0 10px;padding:0;white-space:nowrap;height:25px;"><img style="vertical-align:middle;" src="/login/img/safari.png"/> <a href="http://www.apple.com/safari/" target="_blank">Apple Safari 3</a></span>
										<span style="margin:0 10px;padding:0;white-space:nowrap;height:25px;"><img style="vertical-align:middle;" src="/login/img/chrome.png"/> <a href="http://www.google.com/chrome/" target="_blank">Google Chrome 1</a></span>
									<?php
									}
									elseif(strtolower(substr($client->find_platform(),0,7)) == 'mac')
									{
									?>
										<span style="margin:0 10px;padding:0;white-space:nowrap;height:25px;"><img style="vertical-align:middle;" src="/login/img/firefox.png"/> <a href="http://www.mozilla.com/firefox/" target="_blank">Mozilla Firefox 3</a></span>
										<span style="margin:0 10px;padding:0;white-space:nowrap;height:25px;"><img style="vertical-align:middle;" src="/login/img/safari.png"/> <a href="http://www.apple.com/safari/" target="_blank">Apple Safari 3</a></span>
										<span style="margin:0 10px;padding:0;white-space:nowrap;height:25px;"><img style="vertical-align:middle;" src="/login/img/opera.png"/> <a href="http://www.opera.com/download/" target="_blank">Opera 9</a></span>
									<?php
									}
									else
									{
										?>
										<span style="margin:0 10px;padding:0;white-space:nowrap;height:25px;"><img style="vertical-align:middle;" src="/login/img/firefox.png"/> <a href="http://www.mozilla.com/firefox/" target="_blank">Mozilla Firefox 3</a></span>
										<?php
									}
									?>
							</div>
						</div>
					
					<div style="width:950px;margin:0 auto;">
								<div style="margin-top:70px;height:35px;">
									<table style="width:100%;">
										<tr style="height:35px;">
											<td style="width:500px;white-space:nowrap"><span style="font-size:28px;color:#b1b1b1;margin:0 10px 0 0"><?php echo GV_homeTitle?></span><span class="title-desc"><?php echo GV_metaDescription?></span></td>
											<td style="color:#b1b1b1;text-align:right;">
													<a class="tab click" onclick="setTab('help',this);return false;"><?php echo _('phraseanet:: aide')?></a>
													<?php
													if(GV_register)
													{
														?>
															<a href="register.php" class="tab" id="register-tab"><?php echo _('login:: register');?></a>
														<?php
													}
													if(trim($conditions) != '')
													{
														?>
															<a class="tab" onclick="setTab('about',this);return false;"><?php echo _('login:: CGUs');?></a>
														<?php
													}
													?>
													<a class="tab" id="main-tab" onclick="setTab('main',this);return false;"><?php echo _('login:: accueil');?></a>
											</td>
										</tr>
									</table>
								</div>
								<div style="height:530px;" class="tab-pane">
									<div id="id-main" class="tab-content" style="display:block;">
											<form name="send" action="authenticate.php" method="post" >
											<?php 
											switch(GV_home_publi)
											{
												case 'DISPLAYx1':
													?>
													
													<div style="width:545px;float:left;position:relative;height:490px;">
													<?php 
													$flashW = 525;
													$flashH = 470;
													?>
														<div style="margin:10px 0 0 10px;position:relative;float:left;">
																<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="<?php echo $flashW?>" height="<?php echo $flashH?>" align="top" id="slideshow_as2" >
																<param name="allowScriptAccess" value="sameDomain" />
																<param name="movie" value="../include/player_homelink.swf" />
																<param name="quality" value="high" />
																<param name="wmode" value="transparent" />
																<param name="flashvars" value="xmls=/login/homepubli.php?zh=<?php echo $flashH?>&amp;zw=<?php echo $flashW?>" />
																<embed flashvars="xmls=/login/homepubli.php?zh=<?php echo $flashH?>&amp;zw=<?php echo $flashW?>" src="../include/player_homelink.swf" quality="high" bgcolor="#000000" width="<?php echo $flashW?>" height="<?php echo $flashH?>" name="slideshow_as2" align="top" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
																</object>
														
														</div>
														
													</div>
													<div style="width:370px;float:right;height:490px;position:relative;">
															<div style="margin:60px 25px 0 15px;">
																<div>
																	<?php echo $errorWarning.$confirmWarning?>
																</div>
																<div style="margin-top:20px;">
																	<div style="margin-top:3px;"><?php echo strtoupper(_('admin::compte-utilisateur identifiant'))?></div>
																	<div style="margin-top:3px;"><input tabindex="1" name="login" id="login" value="<?php echo (isset($_COOKIE['PIVLOG'.GV_sit])?$_COOKIE['PIVLOG'.GV_sit]:'')?>" type="text" style="width:100%" /></div>
																</div>										
																<div style="margin-top:20px;">
																	<div style="margin-top:3px;"><?php echo strtoupper(_('admin::compte-utilisateur mot de passe'))?></div>
																	<div style="margin-top:3px;"><input tabindex="2" name="pwd" id="pwd" value="" type="password" style="width:100%" /></div>
																	<div style="text-align:right;margin-top:3px;"><?php echo $findpwd?></div>
																</div>
																<?php
																if((GV_captchas && $parm['error'] == 'captcha'))
																{
																	echo $captchaSys;
																}
																?>
																<div style="margin-top:10px;">
																	<div><input tabindex="4" class="checkbox" <?php echo ((isset($_COOKIE['PIVLOG'.GV_sit]) && trim($_COOKIE['PIVLOG'.GV_sit])!='')?'checked="checked"':'')?> type="checkbox" name="remember" id="remember-me" value="1" /><label for="remember-me"><?php echo _('login::Remember me')?></label></div>
																</div>		
																<div style="margin-top:10px;height:35px;">
																	<div style="float:right;text-align:right;"><input tabindex="5" type="submit" value="<?php echo _('login:: connexion');?>"/></div>
																</div>		
																<div style="margin-top:20px;">
																	<?php echo $demandLinkBox?>
																</div>	
																<div style="margin-top:10px;">	
																	<?php echo $inviteBox?>
																</div>					
															</div>
													</div>
													<?php
													break;
												case 'DISPLAYx4':

													break;
												case 'SCROLL':
													?>
													<div style="height:180px;position:relative;">
															<div style="height:150px;width:220px;float:right;margin:10px 10px 0;">
																	<div style="margin-top:10px;height:35px;">
																		<div style="float:right;text-align:right;"><input tabindex="5" type="submit" value="<?php echo _('login:: connexion');?>"/></div>
																	</div>		
																	<div style="margin-top:20px;">
																		<?php echo $demandLinkBox?>
																		&nbsp;&nbsp;&nbsp;<?php echo $inviteBox?>
																	</div>	
																	<div style="margin-top:10px;">	
																		<?php echo $findpwd?>
																	</div>			
															</div>
																	<?php
																	if(GV_captchas && $parm['error'] == 'captcha')
																	{
																		echo $captchaSys;
																	}
																	?>
															<div style="height:150px;width:250px;float:right;margin:10px 50px 0 10px;">
																	<div>
																		<?php echo $errorWarning.$confirmWarning?>
																	</div>
																	<div style="margin-top:10px;">
																		<div style="margin-top:3px;"><?php echo strtoupper(_('admin::compte-utilisateur identifiant'))?></div>
																		<div style="margin-top:3px;"><input tabindex="1" name="login" id="login" value="<?php echo (isset($_COOKIE['PIVLOG'.GV_sit])?$_COOKIE['PIVLOG'.GV_sit]:'')?>" type="text" style="width:100%" /></div>
																	</div>										
																	<div style="margin-top:10px;">
																		<div style="margin-top:3px;"><?php echo strtoupper(_('admin::compte-utilisateur mot de passe'))?></div>
																		<div style="margin-top:3px;"><input tabindex="2" name="pwd" id="pwd" value="" type="password" style="width:100%" /></div>
																	</div>
															</div>
													</div>
													<div style="height:280px;margin-top:20px;">
														<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="930" height="260" align="top" id="slideshow_as2" >
														<param name="allowScriptAccess" value="sameDomain" />
														<param name="movie" value="../include/player_scroll.swf" />
														<param name="quality" value="high" />
														<param name="wmode" value="transparent" />
														<param name="flashvars" value="space=20&speed=2&media=content&zoom=0.4&rss=/login/homepubli.php" />
														<embed flashvars="space=20&speed=2&media=content&zoom=0.4&rss=/login/homepubli.php" src="../include/player_scroll.swf" quality="high" bgcolor="#000000" width="930" height="260" name="slideshow_as2" align="top" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
														</object>
													</div>
													<?php
													break;
												
											}
											
											
											?>
											<input type="hidden" id="lng" name="lng" size=20 value="<?php echo $parm["lng"]?>">
											<input type="hidden" name="app" value="<?php echo $parm["app"]?>">
										</form>
									</div>
									<?php
									if(trim($conditions) != '')
									{
										?>		
										<div id="id-about" class="tab-content" style="display:none;">
											<div style="position:relative;float:left;width:930px;height:490px;overflow:auto;">
												<?php echo $conditions?>
											</div>
										</div>
										<?php
									}
												?>
									<div id="id-help" class="tab-content" style="display:none;">
									</div>
									<div style="position:relative;margin:18px 10px 0 10px;font-size:10px;font-weight:normal;">
										<table style="border:none;width:100%" cellspacing="0" cellpadding="0">
											<tr>
												<td style="text-align:left;"><?php echo _('phraseanet:: language').' : '.$lngSelect?></td>
												<td style="text-align:right;"><span> &copy; Copyright Alchemy 2005-<?php echo date('Y')?></span></td>
											</tr>
										</table>
									</div>
								</div>
							</div>
					
						<script>
						
						$(document).ready(function(){
							if ($.browser.mozilla) {
								var ver = $.browser.version.split('.');
								if (ver[0] <= 1 && ver[1] < 9) {
									$('#wrongBrowser').show();
								}
							}

							if ($.browser.msie) {
								var ver = $.browser.version.split('.');
								if (ver[0] < 6) 
									$('#wrongBrowser').css('display', 'block');
							}
							if ($.browser.safari) {
								var ver = $.browser.version.split('.');
								if (ver[0] < 525 || (ver[0] <= 525 && ver[1] < 14)) 
									$('#wrongBrowser').css('display', 'block');
							}
							if ($.browser.opera) {
								var ver = $.browser.version.split('.');
								if (ver[0] < 9 || (ver[0] <= 9 && ver[1] < 52)) 
									$('#wrongBrowser').css('display', 'block');
							}
							$('.tab').hover(function(){
								$(this).addClass('active');
							}, function(){
								$(this).removeClass('active');
							});
							
							setTab('main', $('#main-tab'));
						});
						</script>
						<?php
						if(trim(GV_googleAnalytics) != '')
						{
							?>
							<script type="text/javascript">
								var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
								document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
							</script>
							<script type="text/javascript">
								try {
								var pageTracker = _gat._getTracker("<?php echo GV_googleAnalytics?>");
								pageTracker._setDomainName("none");
								pageTracker._setAllowLinker(true);
								pageTracker._trackPageview();
								} catch(err) {}
							</script>
							<?php
						}
						?>
				</body>