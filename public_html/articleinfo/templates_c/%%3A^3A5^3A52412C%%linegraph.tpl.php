<?php /* Smarty version 2.6.18, created on 2010-07-30 01:14:18
         compiled from linegraph.tpl */ ?>
<?php echo '<?xml'; ?>
 version="1.0" encoding="UTF-8"<?php echo '?>'; ?>

<chart>
	<series>
		<?php $_from = $this->_tpl_vars['data']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['year'] => $this->_tpl_vars['item']):
?>
			<?php $_from = $this->_tpl_vars['item']['months']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['month'] => $this->_tpl_vars['count']):
?>
				<value xid="<?php echo $this->_tpl_vars['count']['xid']; ?>
"><?php echo $this->_tpl_vars['month']; ?>
/<?php echo $this->_tpl_vars['year']; ?>
</value>
			<?php endforeach; endif; unset($_from); ?>
		<?php endforeach; endif; unset($_from); ?>
	</series>
	<graphs>
		<graph gid='0' title='<?php echo $this->_config[0]['vars']['monthly']; ?>
'>
			<?php $_from = $this->_tpl_vars['data']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['year'] => $this->_tpl_vars['item']):
?>
				<?php $_from = $this->_tpl_vars['item']['months']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['month'] => $this->_tpl_vars['count']):
?>
					<value xid="<?php echo $this->_tpl_vars['count']['xid']; ?>
"><?php echo $this->_tpl_vars['count']['all']; ?>
</value>
				<?php endforeach; endif; unset($_from); ?>
			<?php endforeach; endif; unset($_from); ?>
		</graph>
		<graph gid='1' title='<?php echo $this->_config[0]['vars']['cumulative']; ?>
'>
			<?php $_from = $this->_tpl_vars['data']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['year'] => $this->_tpl_vars['item']):
?>
				<?php $_from = $this->_tpl_vars['item']['months']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['month'] => $this->_tpl_vars['count']):
?>
					<?php $this->assign('datestamp', ($this->_tpl_vars['month']).($this->_tpl_vars['year'])); ?>
					<value xid="<?php echo $this->_tpl_vars['count']['xid']; ?>
" <?php if ($this->_tpl_vars['eventdata'][$this->_tpl_vars['datestamp']] != ""): ?>description="<?php echo $this->_tpl_vars['eventdata'][$this->_tpl_vars['datestamp']]; ?>
" bullet="round" bullet_color="#009900" bullet_size="7"<?php endif; ?>><?php echo $this->_tpl_vars['count']['cumulative']; ?>
</value>
				<?php endforeach; endif; unset($_from); ?>
			<?php endforeach; endif; unset($_from); ?>
		</graph>
		<graph gid='2' title='<?php echo $this->_config[0]['vars']['ips']; ?>
'>
			<?php $_from = $this->_tpl_vars['data']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['year'] => $this->_tpl_vars['item']):
?>
				<?php $_from = $this->_tpl_vars['item']['months']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['month'] => $this->_tpl_vars['count']):
?>
					<value xid="<?php echo $this->_tpl_vars['count']['xid']; ?>
"><?php echo $this->_tpl_vars['count']['anon']; ?>
</value>
				<?php endforeach; endif; unset($_from); ?>
			<?php endforeach; endif; unset($_from); ?>
		</graph>
		<graph gid='3' title='<?php echo $this->_config[0]['vars']['minor']; ?>
'>
			<?php $_from = $this->_tpl_vars['data']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['year'] => $this->_tpl_vars['item']):
?>
				<?php $_from = $this->_tpl_vars['item']['months']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['month'] => $this->_tpl_vars['count']):
?>
					<value xid="<?php echo $this->_tpl_vars['count']['xid']; ?>
"><?php echo $this->_tpl_vars['count']['minor']; ?>
</value>
				<?php endforeach; endif; unset($_from); ?>
			<?php endforeach; endif; unset($_from); ?>
		</graph>
	</graphs>
</chart>