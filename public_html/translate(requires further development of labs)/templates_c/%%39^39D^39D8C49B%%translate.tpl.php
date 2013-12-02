<?php /* Smarty version 2.6.18, created on 2010-08-02 04:36:50
         compiled from translate.tpl */ ?>
<?php if ($this->_tpl_vars['error'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['error']; ?>
</h2><?php endif; ?>
<?php if ($this->_tpl_vars['notice'] != ""): ?><br /><h2 class="notice"><?php echo $this->_tpl_vars['notice']; ?>
</h2><?php endif; ?>
<?php if ($this->_tpl_vars['replag'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['replag']; ?>
</h2><?php endif; ?>

<?php if ($this->_tpl_vars['form'] != ""): ?>
<form action="//toolserver.org/~soxred93/translate/index.php" method="get" accept-charset="utf-8">
<input type="hidden" value="step1" name="action" />
<input type="hidden" value="<?php echo $this->_tpl_vars['uselang']; ?>
" name="uselang" />
<table>
<tr><td><?php echo $this->_config[0]['vars']['toolname']; ?>
: </td><td>
<select name="toolname">
	<?php $_from = $this->_tpl_vars['tools']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['tool'] => $this->_tpl_vars['uri']):
?>
		<option <?php if ($this->_tpl_vars['usetool'] == $this->_tpl_vars['tool']): ?>selected="selected"<?php endif; ?>><?php echo $this->_tpl_vars['tool']; ?>
</option>
	<?php endforeach; endif; unset($_from); ?>
</select>
</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['language']; ?>
: </td><td>
<select name="lang">
	<?php $_from = $this->_tpl_vars['langs']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['lang']):
?>
		<?php if ($this->_tpl_vars['lang'] == 'en'): ?>
			<option value="en" selected="selected">en</option>
		<?php else: ?>
			<option><?php echo $this->_tpl_vars['lang']; ?>
</option>
		<?php endif; ?>
	<?php endforeach; endif; unset($_from); ?>
</select>
</td></tr>
<tr><td colspan="2"><input type="submit" value="<?php echo $this->_config[0]['vars']['submit']; ?>
" /></td></tr>
</table>
</form><br /><hr />
<?php endif; ?>

<?php if ($this->_tpl_vars['showvars'] != ""): ?>
<?php echo $this->_config[0]['vars']['showvarshelp']; ?>


<?php if ($this->_tpl_vars['tool'] != 'Global'): ?><br /><br /><?php echo $this->_config[0]['vars']['globalnotice']; ?>
<?php endif; ?>
<form action="//toolserver.org/~soxred93/translate/index.php" method="post" accept-charset="utf-8">
<input type="hidden" value="<?php echo $this->_tpl_vars['uselang']; ?>
" name="uselang" />
<input type="hidden" value="step2" name="action" />
<input type="hidden" value="<?php echo $this->_tpl_vars['lang']; ?>
" name="lang" />
<input type="hidden" value="<?php echo $this->_tpl_vars['tool']; ?>
" name="toolname" />
<table class="wikitable">
<tr>
	<th><?php echo $this->_config[0]['vars']['id']; ?>
</th>
	<th><?php echo $this->_config[0]['vars']['text']; ?>
</th>
	<th><?php echo $this->_config[0]['vars']['explanation']; ?>
</th>
</tr>
<?php $_from = $this->_tpl_vars['config_vars']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['id'] => $this->_tpl_vars['data']):
?>
	<tr>
		<td><?php echo $this->_tpl_vars['id']; ?>
</td>
		<td><input size="60" type="text" name="<?php echo $this->_tpl_vars['id']; ?>
" value="<?php echo $this->_tpl_vars['data']['value']; ?>
"></td>
		<td><?php echo $this->_tpl_vars['data']['qqq']; ?>
</td>
	</tr>
<?php endforeach; endif; unset($_from); ?>
</table>
<input type="submit" value="<?php echo $this->_config[0]['vars']['submit']; ?>
" />
</form>
<?php endif; ?>

<?php if ($this->_tpl_vars['success'] != ""): ?>
<?php echo $this->_config[0]['vars']['success']; ?>

<?php endif; ?>

<?php if ($this->_tpl_vars['adminlist'] != ""): ?>
<form action="//toolserver.org/~soxred93/translate/index.php" method="post" accept-charset="utf-8">

<table width="100%" class="wikitable">

<?php $_from = $this->_tpl_vars['submissionlist']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['row']):
?>
	<tr>
		<th colspan="2"><?php echo $this->_tpl_vars['row']['tr_tool']; ?>
 - <?php echo $this->_tpl_vars['row']['tr_lang']; ?>
</th>
	</tr>
	<tr>
		<th>Diff</th>
		<th>Info</th>
	</tr>
	<tr>
		<td><?php echo $this->_tpl_vars['row']['tr_diff']; ?>
</td>
		<td>
			<ul>
				<li>Date: <?php echo $this->_tpl_vars['row']['tr_date']; ?>
</li>
				<li>IP: <?php echo $this->_tpl_vars['row']['tr_ip']; ?>
</li>
				<li><input type="radio" name="result-<?php echo $this->_tpl_vars['row']['tr_id']; ?>
" value="approve" /> Approve</li>
				<li><input type="radio" name="result-<?php echo $this->_tpl_vars['row']['tr_id']; ?>
" value="reject" /> Reject</li>
			</ul>
			
		</td>
	</tr>
<?php endforeach; endif; unset($_from); ?>


</table>

<input type="hidden" name="approve" value="1" />
<input type="hidden" name="action" value="admin" />
<input type="hidden" name="password" value="<?php echo $this->_tpl_vars['password']; ?>
" />
<input type="submit" value="<?php echo $this->_config[0]['vars']['submit']; ?>
" />

</form>
<?php endif; ?>

<?php if ($this->_tpl_vars['donemsg'] != ""): ?>
<?php echo $this->_tpl_vars['donemsg']; ?>

<?php endif; ?>

<?php if ($this->_tpl_vars['passmenu'] != ""): ?>
<form action="//toolserver.org/~soxred93/translate/index.php" method="post" accept-charset="utf-8">
<input type="hidden" name="action" value="admin" />
Password: <input type="password" name="password" />
<input type="submit" value="<?php echo $this->_config[0]['vars']['submit']; ?>
" />

</form>
<?php endif; ?>


