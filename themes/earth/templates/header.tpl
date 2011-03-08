<?php $this->display('baseheader.tpl'); ?>
	<!-- Logo -->
	<h1 id="main-logo">
		<img src="{$this->themeUrl()}tangocms-logo.png" alt="{$proj->prefs['project_title']}" />
	</h1>

	<!-- Project info -->
	<div id="main-description">
		<!-- Project Title -->
		<h2>{$proj->prefs['project_title']}</h2>
		<?php
			$show_message = array('details', 'index', 'newtask', 'reports', 'depends');
			$actions = explode('.', Req::val('action'));
            if ($proj->prefs['intro_message'] && (in_array($do, $show_message) || in_array(reset($actions), $show_message))): ?>
				<p id="intromessage">{!$this->text->render($proj->prefs['intro_message'], false, 'msg', $proj->id,
                               ($proj->prefs['last_updated'] < $proj->prefs['cache_update']) ? $proj->prefs['pm_instructions'] : '')}
				</p>
		<?php endif; ?>
	</div>


	<!-- Container -->
	<div id="main-container">

		<!-- Sidebar -->
		<div id="main-sidebar">
			<h3>{L('login')}</h3>
			<div id="main-user-box">
				<?php $this->display('links.tpl'); ?>
			</div>
			<h3>Project actions</h3>
			<div id="main-project-box">
				<?php $this->display('project.actions.tpl'); ?>
			</div>
		</div>

		<div id="main-content">

			<?php if (isset($_SESSION['SUCCESS']) && isset($_SESSION['ERROR'])): ?>
			<div id="mixedbar" class="mixed bar" onclick="this.style.display='none'"><div class="errpadding">{$_SESSION['SUCCESS']}<br />{$_SESSION['ERROR']}</div></div>
			<?php elseif (isset($_SESSION['ERROR'])): ?>
			<div id="errorbar" class="error bar" onclick="this.style.display='none'"><div class="errpadding">{$_SESSION['ERROR']}</div></div>
			<?php elseif (isset($_SESSION['SUCCESS'])): ?>
			<div id="successbar" class="success bar" onclick="this.style.display='none'"><div class="errpadding">{$_SESSION['SUCCESS']}</div></div>
			<?php endif; ?>

			<!--
			  <div id="showtask">
				<form action="{$baseurl}index.php" method="get">
				  <div>
					<button type="submit">{L('showtask')} #</button>
					<input id="taskid" name="show_task" class="text" type="text" size="10" accesskey="t" />
				  </div>
				</form>
			  </div>
			-->