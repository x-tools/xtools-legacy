<?php /* Smarty version 2.6.18, created on 2012-03-01 01:55:48
         compiled from blame.tpl */ ?>
<?php if ($this->_tpl_vars['error'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['error']; ?>
</h2><?php endif; ?>
<?php if ($this->_tpl_vars['replag'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['replag']; ?>
</h2><?php endif; ?>

<?php if ($this->_tpl_vars['form'] != ""): ?>
<br />
<?php echo $this->_config[0]['vars']['welcome']; ?>

<br /><br />
<form action="//tools.wmflabs.org/xtools/blame/index.php" method="get" accept-charset="utf-8">
<table>
<tr><td><?php echo $this->_config[0]['vars']['article']; ?>
: </td><td><input type="text" name="article" /> <input type="checkbox" name="nofollowredir" /> <?php echo $this->_config[0]['vars']['nofollowredir']; ?>
</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['wiki']; ?>
: </td><td><input type="text" value="<?php echo $this->_tpl_vars['form']; ?>
" name="lang" size="9" />.<input type="text" value="wikipedia" size="10" name="wiki" />.org</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['tosearch']; ?>
: </td><td><textarea name="text" rows="10" cols="40"></textarea></td></tr>
<tr><td colspan="2"><input type="submit" value="<?php echo $this->_config[0]['vars']['submit']; ?>
" /></td></tr>
</table>
</form><br /><hr />
<?php endif; ?>

<?php if ($this->_tpl_vars['revs'] != ""): ?>
<?php echo $this->_config[0]['vars']['added']; ?>

<ul>
<?php $_from = $this->_tpl_vars['revs']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['id'] => $this->_tpl_vars['i']):
?>
<?php echo $this->_tpl_vars['i']; ?>

<?php endforeach; endif; unset($_from); ?>  
</ul>
<?php endif; ?> 