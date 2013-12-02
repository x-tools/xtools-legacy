<?php /* Smarty version 2.6.18, created on 2010-07-26 19:07:02
         compiled from ipcalc.tpl */ ?>
<?php if ($this->_tpl_vars['error'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['error']; ?>
</h2><?php endif; ?>
<?php if ($this->_tpl_vars['form'] != ""): ?><br /><?php echo $this->_tpl_vars['form']; ?>
<?php endif; ?>

<?php if ($this->_tpl_vars['showstats'] != ""): ?>
<b><?php echo $this->_config[0]['vars']['cidr']; ?>
:</b> <?php echo $this->_tpl_vars['cidr']; ?>
<br />
<b><?php echo $this->_config[0]['vars']['ip_start']; ?>
:</b> <?php echo $this->_tpl_vars['ip_start']; ?>
<br />
<b><?php echo $this->_config[0]['vars']['ip_end']; ?>
:</b> <?php echo $this->_tpl_vars['ip_end']; ?>
<br />
<b><?php echo $this->_config[0]['vars']['ip_number']; ?>
:</b> <?php echo $this->_tpl_vars['ip_number']; ?>
<br />
<?php endif; ?>

<?php if ($this->_tpl_vars['list'] != ""): ?>
<?php echo $this->_tpl_vars['list']; ?>

<?php endif; ?>