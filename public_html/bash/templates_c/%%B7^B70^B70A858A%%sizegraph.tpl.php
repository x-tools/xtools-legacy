<?php /* Smarty version 2.6.18, created on 2010-07-31 03:04:09
         compiled from sizegraph.tpl */ ?>
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
					<?php if ($this->_tpl_vars['lastcount'] != ""): ?>
						<?php if ($this->_tpl_vars['count']['size'] == 0): ?>
							<value xid="<?php echo $this->_tpl_vars['count']['xid']; ?>
"><?php echo $this->_tpl_vars['lastcount']; ?>
</value>
						<?php else: ?>
							<value xid="<?php echo $this->_tpl_vars['count']['xid']; ?>
"><?php echo $this->_tpl_vars['count']['size']; ?>
</value>
							<?php $this->assign('lastcount', ($this->_tpl_vars['count']['size'])); ?>
						<?php endif; ?>
					<?php else: ?>
						<value xid="<?php echo $this->_tpl_vars['count']['xid']; ?>
"><?php echo $this->_tpl_vars['count']['size']; ?>
</value>
						<?php $this->assign('lastcount', ($this->_tpl_vars['count']['size'])); ?>
					<?php endif; ?>
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
					
					<value xid="<?php echo $this->_tpl_vars['count']['xid']; ?>
"><?php echo $this->_tpl_vars['count']['sizecumulative']; ?>
</value>
				<?php endforeach; endif; unset($_from); ?>
			<?php endforeach; endif; unset($_from); ?>
		</graph>
	</graphs>
</chart>