<?php /* Smarty version 2.6.18, created on 2012-03-10 16:43:39
         compiled from autoedits.tpl */ ?>
<?php if ($this->_tpl_vars['error'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['error']; ?>
</h2><?php endif; ?>
<?php if ($this->_tpl_vars['notice'] != ""): ?><br /><h2 class="notice"><?php echo $this->_tpl_vars['notice']; ?>
</h2><?php endif; ?>
<?php if ($this->_tpl_vars['replag'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['replag']; ?>
</h2><?php endif; ?>

<?php if ($this->_tpl_vars['form'] != ""): ?>
<?php $this->assign('begintime', '1'); ?>
<br />
<form action="//tools.wmflabs.org/xtools/autoedits/index.php" method="get" accept-charset="utf-8">
<table>
<tr><td><?php echo $this->_config[0]['vars']['user']; ?>
: </td><td><input type="text" name="user" /></td></tr>
<tr><td><?php echo $this->_config[0]['vars']['wiki']; ?>
: </td><td><input type="text" value="<?php echo $this->_tpl_vars['form']; ?>
" name="lang" size="9" />.<input type="text" value="wikipedia" size="10" name="wiki" />.org</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['start']; ?>
: </td><td><input type="text" name="begin" /></td></tr>
<tr><td><?php echo $this->_config[0]['vars']['end']; ?>
: </td><td><input type="text" name="end" /></td></tr>

<tr><td colspan="2"><input type="submit" value="<?php echo $this->_config[0]['vars']['submit']; ?>
" /></td></tr>
</table>
</form><br /><hr />
<?php endif; ?>

<?php if ($this->_tpl_vars['showedits'] != ""): ?>

<?php echo $this->_config[0]['vars']['approximate']; ?>


<ul>
<?php $_from = $this->_tpl_vars['data']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['tool'] => $this->_tpl_vars['count']):
?>
   <li><a href="http://<?php echo $this->_tpl_vars['url']; ?>
/wiki/<?php echo $this->_tpl_vars['urls'][$this->_tpl_vars['tool']]; ?>
"><?php echo $this->_tpl_vars['tool']; ?>
</a> &ndash; <?php echo $this->_tpl_vars['count']; ?>
</li>
<?php endforeach; endif; unset($_from); ?>
</ul>

<table class="wikitable">
   <tr>
      <td><?php echo $this->_config[0]['vars']['totalauto']; ?>
</td><td><?php echo $this->_tpl_vars['totalauto']; ?>
</td>
   </tr>
   <tr>
      <td><?php echo $this->_config[0]['vars']['totalall']; ?>
</td><td><?php echo $this->_tpl_vars['totalall']; ?>
</td>
   </tr>
   <tr>
      <td><?php echo $this->_config[0]['vars']['autopct']; ?>
</td><td><?php echo $this->_tpl_vars['pct']; ?>
%</td>
   </tr>
</table>
<?php endif; ?> 