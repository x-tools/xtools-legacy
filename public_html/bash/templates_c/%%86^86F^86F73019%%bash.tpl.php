<?php /* Smarty version 2.6.18, created on 2013-08-30 12:33:14
         compiled from bash.tpl */ ?>
<?php if ($this->_tpl_vars['error'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['error']; ?>
</h2><?php endif; ?>
<?php if ($this->_tpl_vars['notice'] != ""): ?><br /><h2 class="notice"><?php echo $this->_tpl_vars['notice']; ?>
</h2><?php endif; ?>

<?php if ($this->_tpl_vars['form'] != ""): ?>
<br />
<form action="//tools.wmflabs.org/xtools/bash/index.php" method="get" accept-charset="utf-8">
<table class="wikitable">
<tr>
	<td colspan="2"><input type="radio" name="action" value="random" checked="checked" /> <?php echo $this->_config[0]['vars']['random']; ?>
</td>
</tr>
<tr>
	<td colspan="2"><input type="radio" name="action" value="showall" /> <?php echo $this->_config[0]['vars']['showall']; ?>
</td>
</tr>
<tr>
	<td><input type="radio" name="action" value="showone" /> <?php echo $this->_config[0]['vars']['showone']; ?>
 <input type="text" name="id" size="4" /></td>
</tr>
<tr>
	<td><input type="radio" name="action" value="search" /> <?php echo $this->_config[0]['vars']['search']; ?>
  <input type="text" name="search" /> <input type="checkbox" name="regex" /> <?php echo $this->_config[0]['vars']['regex']; ?>
</td>
</tr>
<tr><td colspan="2"><input type="submit" value="<?php echo $this->_config[0]['vars']['submit']; ?>
" /></td></tr>
</table>
</form><br /><hr />
<?php endif; ?>

<?php if ($this->_tpl_vars['random'] != ""): ?>
<h3><?php echo $this->_config[0]['vars']['quotenumber']; ?>
 <?php echo $this->_tpl_vars['id']; ?>
</h3>

<pre>
<?php echo $this->_tpl_vars['quote']; ?>

</pre>

<a href="<?php echo $this->_tpl_vars['thisurl']; ?>
"><?php echo $this->_config[0]['vars']['showanother']; ?>
</a>
<?php endif; ?>

<?php if ($this->_tpl_vars['showone'] != ""): ?>
<h3><?php echo $this->_config[0]['vars']['quotenumber']; ?>
 <?php echo $this->_tpl_vars['id']; ?>
</h3>

<pre>
<?php echo $this->_tpl_vars['quote']; ?>

</pre>

<?php endif; ?>


<?php if ($this->_tpl_vars['showall'] != ""): ?>
<h3><?php echo $this->_config[0]['vars']['allquotes']; ?>
</h3>

<?php $_from = $this->_tpl_vars['quotes']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['id'] => $this->_tpl_vars['quote']):
?>

<h3><?php echo $this->_config[0]['vars']['quotenumber']; ?>
 <?php echo $this->_tpl_vars['id']; ?>
</h3>
<pre>
<?php echo $this->_tpl_vars['quote']; ?>

</pre>
<?php endforeach; endif; unset($_from); ?>

<?php endif; ?>

<?php if ($this->_tpl_vars['search'] != ""): ?>
<h3><?php echo $this->_config[0]['vars']['searchresults']; ?>
</h3>

<?php $_from = $this->_tpl_vars['quotes']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['id'] => $this->_tpl_vars['quote']):
?>

<h3><?php echo $this->_config[0]['vars']['quotenumber']; ?>
 <?php echo $this->_tpl_vars['id']; ?>
</h3>
<pre>
<?php echo $this->_tpl_vars['quote']; ?>

</pre>
<?php endforeach; endif; unset($_from); ?>

<?php endif; ?>


